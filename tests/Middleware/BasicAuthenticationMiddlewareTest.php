<?php

namespace DMT\Test\Http\Client\Middleware;

use DMT\Http\Client\Middleware\BasicAuthenticationMiddleware;
use DMT\Http\Client\RequestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class BasicAuthenticationMiddlewareTest extends TestCase
{
    public function testBasicAuth(): void
    {
        $originalRequest = new Request('GET', '/');

        $container = [];
        $handler = HandlerStack::create(new MockHandler([new Response(401)]));
        $handler->push(Middleware::history($container));
        $client = new Client(compact('handler'));

        $handler = new RequestHandler($client, new BasicAuthenticationMiddleware('thomas', 'cook'));
        $handler->handle($originalRequest);

        $processedRequest = $container[0]['request'];

        $this->assertArrayNotHasKey('Authorization', $originalRequest->getHeaders());
        $this->assertArrayHasKey('Authorization', $processedRequest->getHeaders());
    }
}
