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
    public function __construct(
        private readonly int $limit,
        private readonly int $duration,
        private readonly ResponseFactoryInterface $factory,
        private readonly CacheInterface $cache,
        private ?string $cacheKey = null
    ) {
        $this->cacheKey ??= sprintf('DMT_RateLimit_%d_%d', $duration, $limit);
    }

    /**
     * Rate limit the request.
     *
     * {@inheritDoc}
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
