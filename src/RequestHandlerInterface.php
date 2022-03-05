<?php

namespace DMT\Http\Client;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface RequestHandlerInterface
{
    /**
     * Handle the client request.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function handle(RequestInterface $request): ResponseInterface;
}