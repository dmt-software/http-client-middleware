<?php

namespace DMT\Http\Client\Middleware;

use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;

class FollowRedirectMiddleware implements MiddlewareInterface
{
    protected array $statusCodes = [
        301, // Moved Permanently
        302, // Found
        303, // See Other
        307, // Temporary Redirect
        308, // Permanent Redirect
    ];

    private UriFactoryInterface $factory;

    /**
     * @param UriFactoryInterface $factory
     * @param array|null $statusCodes
     */
    public function __construct(UriFactoryInterface $factory, array $statusCodes = null)
    {
        $this->factory = $factory;
        $this->statusCodes = $statusCodes ?? $this->statusCodes;
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
