<?php

namespace Iwgb\OutboundGateway;

class ThrottleType {

    public const LEAKY_BUCKET = 'leakyBucket';
    public const TIME_WINDOW = 'timeWindow';
}