# `iwgb-outbound-gateway`

Forwards requests to third party APIs after applying a rate limit.

## Onboarding
Clone the repo and install the dependencies using Composer:
```bash
composer install
```

Then run the application using the development server:
```bash
composer run-script start:dev
```

The app will now be running on port 49422.

In production, the entrypoint is `public/index.php`.

## Usage
### Setup
The gateway forwards requests based on their destination config in a `config.json` placed in the app root.
```json
{
  "example": {
    "forwardHost": "api.example.com",
    "throttleRequestLimit": 5,
    "throttleWindowMs": 1000
  }
}
```

By default, the gateway uses a leaky bucket to throttle requests. You can change this to a time window algorithm by adding a `throttleType` of `timeWindow` to the config.

### Making requests
You must provide two extra headers: `X-Proxy-Auth` and `X-Proxy-Destination-Key`. The auth header should contain the secret required to use the gateway, and the destination header the config key. In the example above, the config key is `example`. 

The gateway will return the verbatim response as received from the 3rd party, with two added headers: `X-Proxy-Delay` containing the amount of time your request was throttled for (in milliseconds), and `X-Proxy-Remote-Host`, containing the host of the upstream server that responded to your request.
