# Http Client Middleware

Unfortunately [psr-15](https://www.php-fig.org/psr/psr-15/), the recommendation how to handle incoming server request, 
does not cover how to deal with an outgoing client request, or a client response.
 
This package solves this problem in a similar way.

## Installation
`composer require dmt-software/http-client-middleware`

## Usage

```php
use DMT\Http\Client\RequestHandler;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

/** @var ClientInterface $client */
$handler = new RequestHandler($client);
$response = $handler->handle($request);
 
if ($response->getStatusCode() === 200) {
   // process the response
}
```

### Middleware

Middleware can be used to process a request before it is sent to the server by the client or to handle the response,
for instance to apply authentication, store a login cookie or log the response.

```php
use DMT\Http\Client\MiddlewareInterface;
use DMT\Http\Client\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Apply basic authentication header to the request
 */
class BasicAuthMiddleware implements MiddlewareInterface
{
    private string $user = 'user';
    private string $pass = '*****';
    
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withHeader(
            'Authorization', sprintf('Basic %s', base64_encode("$this->user:$this->pass")) 
        );
        
        return $handler->handle($request);
    }
}
```

To enable middleware simply add these to the request handler.  

```php
use DMT\Http\Client\RequestHandler;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

/** @var ClientInterface $client */
$handler = new RequestHandler(
    $client,
    $basicAuthMiddleware,
    $otherMiddleware,
);
$response = $handler->handle($request);
```