<?php

namespace DMT\Test\Http\Client;

use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandler;
use DMT\Http\Client\RequestHandlerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestHandlerTest extends TestCase
{
    public function testHandle(): void
    {
        $response = (new RequestHandler($this->getClient()))->handle(new Request('GET', '/'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleWithMiddleware(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request)->withStatus(404);
            }
        };

        $response = (new RequestHandler($this->getClient(), $middleware))->handle(new Request('GET', '/'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    private function getClient(): ClientInterface
    {
        return new Client([
            'handler' => HandlerStack::create(
                new MockHandler([new Response()])
            )
        ]);
    }
}
