<?php

namespace DMT\Http\Client\Middleware;

use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Middleware to decompress a gzip (.gz) compressed response.
 */
class ZlibInflateMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly StreamFactoryInterface $factory)
    {
        if (!extension_loaded('zlib')) {
            trigger_error('module zlib is not installed', E_USER_ERROR);
        }
    }

    /**
     * @inheritDoc
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response->getBody()->tell() != 0) {
            $response->getBody()->rewind();
        }

        if (str_contains($response->getHeaderLine('Content-Type'), 'application/x-gzip-compressed')) {
            $resource = $response->getBody()->detach();

            stream_filter_append($resource, 'zlib.inflate', STREAM_FILTER_READ, ['window' => 31, 'memory' => 9]);

            return $response->withBody(
                $this->factory->createStreamFromResource($resource)
            );
        }

        return $response;
    }
}
