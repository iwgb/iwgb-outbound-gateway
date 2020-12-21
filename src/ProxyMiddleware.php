<?php

namespace Iwgb\OutboundGateway;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Stiphle\Storage\LockWaitTimeoutException;
use Teapot\StatusCode\RFC\RFC6585 as StatusCode;

class ProxyMiddleware {

    private ProxyConfig $config;

    public function __construct(ProxyConfig $config) {
        $this->config = $config;
    }

    public function proxyHeaderMiddleware(): callable {
        return function (
            RequestInterface $request,
            ResponseInterface $response,
            callable $next
        ) {
            $request = $request
                ->withoutHeader(ProxyHeader::AUTH)
                ->withoutHeader(ProxyHeader::DESTINATION);

            /** @var ResponseInterface $response */
            $response = $next($request, $response);

            return $response
                ->withHeader(ProxyHeader::HOST, $this->config->forwardHost);
        };
    }

    public function rateLimit(): callable {
        return function (
            RequestInterface $request,
            ResponseInterface $response,
            callable $next
        ): ResponseInterface {
            try {
                $waitedForMs = $this->config->throttle();
            } catch (LockWaitTimeoutException $e) {
                throw new HttpCompatibleException('Could not acquire lock', StatusCode::TOO_MANY_REQUESTS);
            }

            /** @var ResponseInterface $response */
            $response = $next($request, $response);

            return $response
                ->withHeader(ProxyHeader::DELAY, $waitedForMs);
        };
    }

    public function noCache(): callable {
        return function (
            RequestInterface $request,
            ResponseInterface $response,
            callable $next
        ): ResponseInterface {
            $response = $next($request, $response);
            return $response
                ->withHeader('cache-control', 'no-store');
        };
    }
}