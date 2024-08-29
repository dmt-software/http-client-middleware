<?php

namespace DMT\Test\Http\Client\Middleware;

use DMT\Http\Client\Middleware\FollowRedirectMiddleware;
use DMT\Http\Client\RequestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class FollowRedirectMiddlewareTest extends TestCase
{
    /**
     * @dataProvider provideFollowRedirect
     */
    public function testProcess(string $method, int $statusCode, string $expectedMethod): void
    {
        $client = $this->getMockBuilder(Client::class)
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $client
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($method, $statusCode, &$response) {
                if (!$response) {
                    return $response = new Response($statusCode, ['Location' => 'https://new-location.org/path']);
                }
                return new Response(200, ['RequestMethod' => $request->getMethod()]);
            });

        $requestHandler = new RequestHandler($client, new FollowRedirectMiddleware(new HttpFactory()));
        $response = $requestHandler->handle(new Request($method, 'https://some-location.org/path'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedMethod, $response->getHeaderLine('requestMethod'));
    }

    public static function provideFollowRedirect(): iterable
    {
        return [
            ['POST', 301, 'GET'],
            ['GET', 302, 'GET'],
            ['HEAD', 302, 'HEAD'],
            ['PUT', 303, 'GET'],
            ['PUT', 307, 'PUT'],
            ['POST', 308, 'POST'],
        ];
    }

    /**
     * @dataProvider provideNoFollow
     */
    public function testProcessNoFollow(string $method, int $statusCode, array $headers = []): void
    {
        $client = $this->getMockBuilder(Client::class)
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $client
            ->expects($this->exactly(1))
            ->method('sendRequest')
            ->willReturn($response = new Response($statusCode, $headers));

        $requestHandler = new RequestHandler($client, new FollowRedirectMiddleware(new HttpFactory()));

        $this->assertSame($response, $requestHandler->handle(new Request($method, 'https://some-location.org/path')));
    }

    public static function provideNoFollow(): iterable
    {
        return [
            ['PUT', 300, ['Location' => 'https://new-location.org/path']],
            ['POST', 303],
            ['GET', 304],
        ];
    }
}
