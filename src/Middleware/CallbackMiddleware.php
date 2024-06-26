<?php

namespace DMT\Http\Client\Middleware;

use Closure;
use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class CallbackMiddleware
 *
 * Callback middleware enables you to reuse other psr-7 compliant middleware that are applied to request or responses,
 * or use a closure to enable some functionalities which does not need a completely custom written middleware.
 */
class CallbackMiddleware implements MiddlewareInterface
{
    public const TYPE_REQUEST = 1;
    public const TYPE_RESPONSE = 2;

    private Closure $callback;

    /**
     * @param callable $callback A callback function to execute.
     *      - in case of a request callback the callback receives a RequestInterface, and it must return also
     *      - in case of a response callback it receives a ResponseInterface, which type it should return too
     *  It is possible to apply a callback on both request and response, in these cases ensure the same type is returned
     *  as the one that was received.
     * @param int $messageType The message type to apply the callback on
     */
    public function __construct(callable $callback, private readonly int $messageType = self::TYPE_REQUEST)
    {
        $this->callback = $callback(...);
    }

    /**
     * Execute the callback.
     *
     * {@inheritDoc}
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $callback = $this->callback;
        if ($this->messageType & self::TYPE_REQUEST) {
            $request = $callback($request);
        }

        $response = $handler->handle($request);
        if ($this->messageType & self::TYPE_RESPONSE) {
            $response = $callback($response);
        }

        return $response;
    }
}
