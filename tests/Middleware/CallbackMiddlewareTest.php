<?php

namespace DMT\Test\Http\Client\Middleware;

use DMT\Http\Client\Middleware\CallbackMiddleware;
use DMT\Http\Client\RequestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CallbackMiddlewareTest extends TestCase
{
    public function testRequestCallable()
    {
        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    $response = new Response(200),
                ])
            )
        ]);

        $isCalled = false;
        $callback = function (RequestInterface $request) use (&$isCalled) {
            $isCalled = true;
            return $request;
        };

        $handler = new RequestHandler($client, new CallbackMiddleware($callback));

        $this->assertSame($response, $handler->handle($request));
        $this->assertTrue($isCalled);
    }

    public function testResponseCallable()
    {
        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    $response = new Response(200),
                ])
            )
        ]);

        $isCalled = false;
        $callback = function (ResponseInterface $response) use (&$isCalled) {
            $isCalled = true;
            return $response;
        };

        $handler = new RequestHandler($client, new CallbackMiddleware($callback, CallbackMiddleware::TYPE_RESPONSE));

        $this->assertSame($response, $handler->handle($request));
        $this->assertTrue($isCalled);
    }

    public function testRequestAndResponseCallable()
    {
        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    $response = new Response(200),
                ])
            )
        ]);

        $count = 0;
        $callback = function ($requestOrResponse) use (&$count) {
            $count++;
            return $requestOrResponse;
        };

        $handler = new RequestHandler(
            $client,
            new CallbackMiddleware(
                $callback,
                CallbackMiddleware::TYPE_REQUEST ^ CallbackMiddleware::TYPE_RESPONSE
            )
        );

        $this->assertSame($response, $handler->handle($request));
        $this->assertSame(2, $count);
    }
}
