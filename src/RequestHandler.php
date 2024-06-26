<?php

namespace DMT\Http\Client;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RequestHandler implements RequestHandlerInterface
{
    /** @var array<MiddlewareInterface> */
    private array $middleware = [];

    public function __construct(private readonly ClientInterface $client, ?MiddlewareInterface ...$middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * @inheritDoc
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        if (count($this->middleware)) {
            $next = clone($this);
            return array_shift($next->middleware)->process($request, $next);
        }

        return $this->client->sendRequest($request);
    }
}
