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
     * @param RequestInterface $request The request massage to send.
     * @param RequestHandlerInterface $handler The request handler that handles the request.
     * @return ResponseInterface The http response message.
     * @throws ClientExceptionInterface When an error occurs while processing the request.
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
