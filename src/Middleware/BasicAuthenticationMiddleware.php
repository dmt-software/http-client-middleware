<?php

namespace DMT\Http\Client\Middleware;

use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BasicAuthenticationMiddleware implements MiddlewareInterface
{
    private string $user;
    private string $pass;

    /**
     * @param string $user
     * @param string $pass
     */
    public function __construct(string $user, string $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
    }

    /**
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