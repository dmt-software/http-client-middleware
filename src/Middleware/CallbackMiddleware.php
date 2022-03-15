<?php

namespace DMT\Http\Client\Middleware;

use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Client\ClientExceptionInterface;
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

    /** @var callable */
    private $callback;
    private int $messageType;

    /**
     * @param callable $callback A callback function to execute.
     *      - in case of a request callback the callback receives a RequestInterface, and it must return also
     *      - in case of a response callback it receives a ResponseInterface, which type it should return too
     *  It is possible to apply a callback on both request and response, in these cases ensure the same type is returned
     *  as the one that was received.
     * @param int $messageType The message type to apply the callback on
     */
    public function __construct(callable $callback, int $messageType = self::TYPE_REQUEST)
    {
        $this->callback = $callback;
        $this->messageType = $messageType;
    }

    /**
     * Execute the callback.
     *
     * @param RequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->messageType & self::TYPE_REQUEST) {
            $request = call_user_func($this->callback, $request);
        }

        $response = $handler->handle($request);

        if ($this->messageType & self::TYPE_RESPONSE) {
            $response = call_user_func($this->callback, $response);
        }

        return $response;
    }
}
