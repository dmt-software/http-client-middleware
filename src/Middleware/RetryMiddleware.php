<?php

namespace DMT\Http\Client\Middleware;

use DateTime;
use DateTimeZone;
use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RetryMiddleware
 *
 * This middleware will retry to connect if a request resulted in a NetworkException, a 429 or a 503 response.
 */
class RetryMiddleware implements MiddlewareInterface
{
    public const RETRY_RESPONSE_STATUS = [429, 503];

    public function __construct(
        /** The amount of retries until the request is aborted. */
        private readonly int $retries = 2,
        /** The max time between the current request and the retry in seconds.*/
        private readonly int $maxDelay = 30
    ) {
    }

    /**
     * Retry the request.
     *
     * {@inheritDoc}
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $i = 0;
        while (true) {
            try {
                $response = $handler->handle($request);

                if (in_array($response->getStatusCode(), self::RETRY_RESPONSE_STATUS)) {
                    if (!$response->hasHeader('Retry-After')) {
                        time_nanosleep(1, 0);
                        continue;
                    }

                    $after = $response->getHeaderLine('Retry-After');
                    if (!preg_match('~^\d+$~', $after)) {
                        $resumeTime = new DateTime($after, new DateTimeZone('UTC'));
                        $resumeTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
                        $after = max(0, (int)$resumeTime->format('U') - microtime(true));
                    }

                    if ($after <= $this->maxDelay) {
                        time_nanosleep(intval($after), intval($after * 1000000) % 1000000 * 1000);

                        continue;
                    }
                }

                break;
            } catch (NetworkExceptionInterface $exception) {
                if ($i++ === $this->retries) {
                    throw $exception;
                }

                time_nanosleep(intval($i / 10), ($i * 100000000) % 1000000000);
            }
        }

        return $response;
    }
}
