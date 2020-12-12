<?php

namespace Iwgb\OutboundGateway;

use GuzzleHttp;
use Proxy\Adapter\Guzzle\GuzzleAdapter;
use Proxy\Proxy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Stiphle\Storage\LockWaitTimeoutException;
use Teapot\StatusCode\RFC\RFC6585 as StatusCode;

class OutboundProxy extends Proxy {
    public function __construct(ProxyConfig $config) {
        parent::__construct(
            new GuzzleAdapter(
                new GuzzleHttp\Client(),
            ),
        );

        $this->filter(function (
            RequestInterface $request,
            ResponseInterface $response,
            callable $next
        ) use ($config): ResponseInterface {
            $request = $request
                ->withoutHeader(ProxyHeader::AUTH)
                ->withoutHeader(ProxyHeader::DESTINATION);

            /** @var ResponseInterface $response */
            $response = $next($request, $response);

            return $response
                ->withHeader(ProxyHeader::HOST, $config->forwardHost);
        });

        $this->filter(function (
            RequestInterface $request,
            ResponseInterface $response,
            callable $next
        ) use ($config): ResponseInterface {
            try {
                $waitedForMs = $config->throttle();
            } catch (LockWaitTimeoutException $e) {
                throw new HttpCompatibleException('Could not acquire lock', StatusCode::TOO_MANY_REQUESTS);
            }


            /** @var ResponseInterface $response */
            $response = $next($request, $response);

            return $response
                ->withHeader(ProxyHeader::DELAY, $waitedForMs);
        });
    }
}