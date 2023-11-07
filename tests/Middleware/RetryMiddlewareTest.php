<?php

namespace DMT\Test\Http\Client\Middleware;

use DateTime;
use DateTimeInterface;
use DMT\Http\Client\Middleware\RetryMiddleware;
use DMT\Http\Client\RequestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RetryMiddlewareTest extends TestCase
{
    public function testRetrySuccessAfterConnectionFailure(): void
    {
        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new ConnectException('can not connect', $request),
                    new ConnectException('can not connect', $request),
                    $response = new Response(200),
                ])
            )
        ]);

        $handler = new RequestHandler($client, new RetryMiddleware(2));

        $this->assertSame($response, $handler->handle($request));
    }

    public function testNoMoreRetriesOnConnectionFailure(): void
    {
        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new ConnectException('can not connect', $request),
                    $exception = new ConnectException('can not connect', $request),
                ])
            )
        ]);

        $handler = new RequestHandler($client, new RetryMiddleware(1));

        $this->expectExceptionObject($exception);

        $handler->handle($request);
    }

    public function testRetrySuccessAfterServerUnavailableResponse(): void
    {
        $start = time();
        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new Response(503, [] ,'Server Unavailable'),
                    $response = new Response(200),
                ])
            )
        ]);

        $handler = new RequestHandler($client, new RetryMiddleware(2));

        $this->assertSame($response, $handler->handle($request));
        $this->assertGreaterThanOrEqual(1, abs($start - time()));
    }

    public function testRetrySuccessWithRetryAfterHeader(): void
    {
        $date = new DateTime(date('Y-m-d H:i:s.000', strtotime('+2 seconds')));
        $date->setTimezone(new \DateTimeZone('UTC'));

        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new Response(429, ['Retry-After' => $date->format(DateTimeInterface::RFC7231)]),
                    $response = new Response(200),
                ])
            )
        ]);

        $handler = new RequestHandler($client, new RetryMiddleware(2));

        $this->assertSame($response, $handler->handle($request));
        $this->assertGreaterThanOrEqual($date, new DateTime());
    }

    public function testStopRetriesOnExceedingMaxDelay(): void
    {
        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    $response = new Response(429, ['Retry-After' => '60']),
                ])
            )
        ]);

        $handler = new RequestHandler($client, new RetryMiddleware(2, 30));

        $this->assertSame($response, $handler->handle($request));
    }
}
