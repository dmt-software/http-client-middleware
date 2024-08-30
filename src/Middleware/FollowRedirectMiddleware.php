<?php

namespace DMT\Http\Client\Middleware;

use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;

final class FollowRedirectMiddleware implements MiddlewareInterface
{
    private UriFactoryInterface $factory;
    private array $statusCodes;

    public function __construct(
        UriFactoryInterface $factory,
        array $statusCodes = [301, 302, 303, 307, 308]
    ) {
        $this->factory = $factory;
        $this->statusCodes = $statusCodes;
    }

    /**
     * Automatic follow redirects.
     *
     * @param RequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!in_array($response->getStatusCode(), $this->statusCodes)) {
            return $response;
        }

        if (in_array($response->getStatusCode(), [301, 302, 303])
            && !in_array(strtoupper($request->getMethod()), ['GET', 'HEAD', 'OPTIONS'])
        ) {
            $request = $request->withMethod('GET');
        }

        $location = $response->getHeaderLine('location');
        if ($location) {
            $response = $handler->handle(
                $request->withUri($this->factory->createUri($location))
            );
        }

        return $response;
    }
}
