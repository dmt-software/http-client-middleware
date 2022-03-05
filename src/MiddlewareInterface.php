<?php

namespace DMT\Http\Client;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface
{
    /**
     * Process the request
     *
     * @param RequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}