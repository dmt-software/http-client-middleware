<?php

namespace DMT\Test\Http\Client\Middleware;

use DMT\Http\Client\Middleware\ZlibInflateMiddleware;
use DMT\Http\Client\RequestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ZlibInflateMiddlewareTest extends TestCase
{
    public function testProcess(): void
    {
        $expected = '<html><body>html snippet</body></html>';

        $handle = fopen('php://temp', 'w+');
        fwrite($handle, zlib_encode($expected, ZLIB_ENCODING_GZIP));
        rewind($handle);

        $request = new Request('GET', '/');
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    (new Response(200))
                        ->withBody((new HttpFactory())->createStreamFromResource($handle))
                        ->withHeader('Content-Type', 'application/x-gzip-compressed'),
                ])
            )
        ]);

        $handler = new RequestHandler($client, new ZlibInflateMiddleware(new HttpFactory()));

        $this->assertSame($expected, $handler->handle($request)->getBody()->getContents());
    }
}
