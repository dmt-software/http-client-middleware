<?php

namespace DMT\Http\Client\Middleware;

use DateTime;
use DateTimeZone;
use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Client\ClientExceptionInterface;
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

    /**
     * The max amount of tries to connect to the server.
     *
     * @var int
     */
    private int $retries;

    /**
     * The max time to delay a retry.
     *
     * When a response was received and the max delay time is exceeded, the response is returned.
     * This limits the request handler execution time.
     *
     * @var int
     */
    private int $maxDelay;

    /**
     * @param int $retries The amount of retries until the request is aborted.
     * @param int $maxDelay The max time between the current request and the retry in seconds.
     */
    public function __construct(int $retries = 2, int $maxDelay = 30)
    {
        $this->retries = $retries;
        $this->maxDelay = $maxDelay;
    }

    /**
     * Retry the request.
     *
     * @param RequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ClientExceptionInterface
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
                        time_nanosleep(intval($after), ($after * 1000000) % 1000000 * 1000);

                        continue;
                    }
                }

                return $response;
            } catch (NetworkExceptionInterface $exception) {
                if ($i++ === $this->retries) {
                    throw $exception;
                }

                time_nanosleep(floor($i / 10), ($i * 100000000) % 1000000000);
            }
        }
    }
}
