<?php

namespace Iwgb\OutboundGateway;

use GuzzleHttp;
use Proxy\Adapter\Guzzle\GuzzleAdapter;
use Proxy\Proxy;

class OutboundProxy extends Proxy {

    private ProxyMiddleware $middleware;
    public function __construct(ProxyConfig $config) {
        parent::__construct(
            new GuzzleAdapter(
                new GuzzleHttp\Client(),
            ),
        );

        $this->middleware = new ProxyMiddleware($config);

        $this->filter($this->middleware->proxyHeaderMiddleware());
        $this->filter($this->middleware->rateLimit());
        $this->filter($this->middleware->noCache());
    }
}