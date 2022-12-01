<?php

namespace DMT\Http\Client\Middleware;

use DateInterval;
use DateTime;
use DateTimeInterface;
use DMT\Http\Client\Middleware\RateLimit\Counter;
use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $limit;
    private int $duration;
    private ResponseFactoryInterface $factory;
    private CacheInterface $cache;
    private string $cacheKey;

    /**
     * @param int $limit
     * @param int $duration
     * @param ResponseFactoryInterface $factory
     * @param CacheInterface $cache
     * @param string|null $cacheKey
     */
    public function __construct(
        int $limit,
        int $duration,
        ResponseFactoryInterface $factory,
        CacheInterface $cache,
        string $cacheKey = null
    ) {
        $this->limit = $limit;
        $this->duration = $duration;
        $this->factory = $factory;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey ?? sprintf('DMT_RateLimit_%d_%d', $duration, $limit);
    }

    /**
     * Rate limit the request.
     *
     * @param RequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $counter = $this->getCounter();

        try {
            if ($counter->count > $this->limit && new DateTime() < $counter->expireTime) {
                $expireTime = clone($counter->expireTime);
                if ($expireTime->format('u') > 0) {
                    $expireTime = $expireTime->add(new DateInterval('PT1S'));
                }
                return $this->factory->createResponse(429, 'Too Many Requests')
                    ->withHeader('Retry-After', $expireTime->format(DateTimeInterface::RFC7231));
            }
            $counter->count++;
            $this->cache->set($this->cacheKey, $counter, $counter->expireTime->diff(new DateTime(), true));

            return $handler->handle($request);
        } catch (ClientExceptionInterface $exception) {
            $rewind = $this->getCounter();

            if ($counter->expireTime == $rewind->expireTime) {
                $rewind->count--;
                $this->cache->set($this->cacheKey, $rewind, $rewind->expireTime->diff(new DateTime(), true));
            }

            throw $exception;
        }
    }

    private function getCounter(): Counter
    {
        $default = new Counter(DateTime::createFromFormat('U', $this->duration + time()));

        /** @var Counter $counter */
        $counter = $this->cache->get($this->cacheKey, $default);

        if ($counter->expireTime <= new DateTime()) {
            $this->cache->delete($this->cacheKey);
            return $default;
        }

        return $counter;
    }
}
