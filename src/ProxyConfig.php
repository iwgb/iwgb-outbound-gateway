<?php

namespace Iwgb\OutboundGateway;

use Doctrine\Common\Cache\FilesystemCache;
use Stiphle\Storage;
use Stiphle\Throttle;
use Stiphle\Throttle\ThrottleInterface;

class ProxyConfig {

    private string $key;
    private int $throttleRequestLimit;
    private int $throttleWindowMs;
    private ThrottleInterface $throttle;

    public string $forwardHost;

    /**
     * ProxyConfig constructor.
     * @param string $key
     * @param array $json
     */
    public function __construct(string $key, array $json) {
        $this->key = $key;
        $this->forwardHost = $json['forwardHost'];
        $this->throttleRequestLimit = $json['throttleRequestLimit'];
        $this->throttleWindowMs = $json['throttleWindowMs'];

        if ($json['throttleType'] ?? '' === ThrottleType::TIME_WINDOW) {
            $this->throttle = new Throttle\TimeWindow();
        } else {
            $this->throttle = new Throttle\LeakyBucket();
        }

        $this->throttle->setStorage(
            new Storage\DoctrineCache(
                new FilesystemCache(APP_ROOT . '/var/storage'),
            ),
        );
    }

    public function throttle(): int {
        return $this->throttle->throttle(
            $this->key,
            $this->throttleRequestLimit,
            $this->throttleWindowMs,
        );
    }

    public function getForwardAddress(): string {
        return "https://{$this->forwardHost}";
    }
}