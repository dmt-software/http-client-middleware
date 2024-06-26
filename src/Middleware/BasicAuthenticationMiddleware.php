<?php

namespace DMT\Http\Client\Middleware;

use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BasicAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $user, private readonly string $pass)
    {
    }

    /**
     * Append basic authentication header.
     *
     * @inheritDoc
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle(
            $request->withHeader(
                'Authorization', sprintf('Basic %s', base64_encode($this->user . ':' . $this->pass))
            )
        );
    }
}
