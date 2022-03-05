<?php

namespace DMT\Http\Client;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RequestHandler implements RequestHandlerInterface
{
    private ClientInterface $client;
    /** @var MiddlewareInterface[]  */
    private array $middleware = [];

    /**
     * @param ClientInterface $client
     * @param MiddlewareInterface|null ...$middleware
     */
    public function __construct(ClientInterface $client, ?MiddlewareInterface ...$middleware)
    {
        $this->client = $client;
        $this->middleware = $middleware;
    }

    /**
     * Handle the client request.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        while (count($this->middleware)) {
            return array_shift($this->middleware)->process($request, $this);
        }

        return $this->client->sendRequest($request);
    }
}
