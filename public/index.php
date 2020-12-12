<?php

define('APP_ROOT', __DIR__ . '/..');

require_once APP_ROOT . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Iwgb\OutboundGateway\HttpCompatibleException;
use Iwgb\OutboundGateway\OutboundProxy;
use Iwgb\OutboundGateway\ProxyConfig;
use Iwgb\OutboundGateway\ProxyHeader;
use Siler\Diactoros as Psr7;
use Siler\HttpHandlerRunner as Server;
use Siler\Http\Request;
use Siler\Http\Response;
use Teapot\StatusCode;

Dotenv::createImmutable(APP_ROOT)->load();

/**
 * @throws ErrorException|HttpCompatibleException
 */
function dispatch(): void {
    if (!in_array(
        Request\header(ProxyHeader::AUTH),
        explode(',', $_ENV['PROXY_AUTH_KEYS'])
    )) {
        throw new HttpCompatibleException('Incorrect ' . ProxyHeader::AUTH . ' header', StatusCode::FORBIDDEN);
    }

    $configs = json_decode(
        file_get_contents(APP_ROOT . '/config.json'),
        true,
    );
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ErrorException('Config invalid');
    }

    $destinationKey = Request\header(ProxyHeader::DESTINATION);

    if (empty($configs[$destinationKey] ?? null)) {
        throw new ErrorException('Config not found');
    }

    $config = new ProxyConfig($destinationKey, $configs[$destinationKey]);
    $proxy = new OutboundProxy($config);

    Server\sapi_emit($proxy
        ->forward(Psr7\request())
        ->to($config->getForwardAddress())
    );
}

try {
    dispatch();
} catch (HttpCompatibleException $e) {
    Response\json(
        ['error' => $e->getMessage()],
        $e->getHttpStatus(),
    );
} catch (Exception $e) {
    Response\json(
        ['error' => $e->getMessage()],
        StatusCode::INTERNAL_SERVER_ERROR,
    );
}

