<?php

namespace DMT\Test\Http\Client\Middleware;

use ArrayObject;
use DateTime;
use DMT\Http\Client\Middleware\RateLimitMiddleware;
use DMT\Http\Client\RequestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class RateLimitMiddlewareTest extends TestCase
{
    public function testExceedsLimit(): void
    {
        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    $response = new Response(202),
                    new Response(202),
                    new Response(202),
                    new Response(200),
                ])
            )
        ]);

        $cache = $this->getCache();
        $cache->expects($this->exactly(4))->method('get');

        $handler = new RequestHandler(
            $client,
            new RateLimitMiddleware(3, 10, new HttpFactory(), $cache)
        );

        while ($response->getStatusCode() === 202) {
            $response = $handler->handle($request);
        }

        $this->assertSame(429, $response->getStatusCode());
        $this->assertGreaterThanOrEqual(
            new DateTime(),
            new DateTime($response->getHeaderLine('Retry-After'), new \DateTimeZone('UTC'))
        );
    }

    public function testNotExceedsLimit(): void
    {
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    $response = new Response(202),
                    new Response(202),
                    new Response(202),
                    new Response(200),
                ])
            )
        ]);

        $handler = new RequestHandler(
            $client,
            new RateLimitMiddleware(2, 1, new HttpFactory(), $this->getCache())
        );

        while ($response->getStatusCode() === 202) {
            $response = $handler->handle(new Request('GET', '/'));
            usleep(500000);
        }

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testResetCounter(): void
    {
        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    $response = new Response(202),
                    new Response(202),
                    new ConnectException('can not connect', $request),
                ])
            )
        ]);

        $cache = $this->getCache();
        $handler = new RequestHandler(
            $client,
            new RateLimitMiddleware(10, 4, new HttpFactory(), $cache, 'cacheKey')
        );

        try {
            while ($response->getStatusCode() === 202) {
                $response = $handler->handle($request);
            }
        } catch (ConnectException $exception) {
        }

        $this->assertSame(3, $cache->get('cacheKey')->count);
    }

    /**
     * @return CacheInterface|MockObject
     */
    private function getCache(): CacheInterface
    {
        $storage = new ArrayObject(['ttl' => []], ArrayObject::ARRAY_AS_PROPS);
        $cache = $this->getMockBuilder(CacheInterface::class)
            ->onlyMethods(['get', 'set', 'delete'])
            ->getMockForAbstractClass();

        $cache->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key, $default = null) use (&$storage) {
                if (!isset($storage->ttl[$key]) || $storage->ttl[$key] < new DateTime()) {
                    return $default;
                }
                return $storage->$key ?? $default;
            });

        $cache->expects($this->any())
            ->method('set')
            ->willReturnCallback(function ($key, $value, $ttl) use (&$storage) {
                $storage->ttl[$key] = (new DateTime())->add($ttl);
                $storage[$key] = $value;
            });

        $cache->expects($this->any())
            ->method('delete')
            ->willReturnCallback(function ($key) use (&$storage) {
                if (isset($storage->ttl[$key])) {
                    unset($storage->ttl[$key]);
                }
                unset($storage->$key);
            });

        return $cache;
    }
}
