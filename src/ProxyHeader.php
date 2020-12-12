<?php

namespace Iwgb\OutboundGateway;

class ProxyHeader {

    public const AUTH = 'x-proxy-auth';
    public const DELAY = 'x-proxy-delay';
    public const DESTINATION = 'x-proxy-destination-key';
    public const HOST = 'x-proxy-remote-host';
}