<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Nansen API Key
    |--------------------------------------------------------------------------
    |
    | Your personal Nansen API key. You can generate one from the Nansen
    | dashboard. The key is sent with every request as the `apiKey` header.
    |
    */
    'api_key' => env('NANSEN_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Nansen API Base URI
    |--------------------------------------------------------------------------
    |
    | The base URI for all API requests. Change this only if you are using
    | a custom gateway or Nansen provides a different endpoint.
    |
    */
    'base_uri' => env('NANSEN_BASE_URI', 'https://api.nansen.ai'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Default timeout in seconds for HTTP requests to the Nansen API.
    |
    */
    'timeout' => (int) env('NANSEN_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    |
    | Number of retries when the API responds with HTTP 429 (Too Many Requests)
    | or a transient server error. Set to 0 to disable retries.
    |
    */
    'retries' => (int) env('NANSEN_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Retry Delay
    |--------------------------------------------------------------------------
    |
    | Base delay in seconds used for exponential backoff between retries.
    | The actual delay may be overridden by the API's `Retry-After` header.
    |
    */
    'retry_delay' => (int) env('NANSEN_RETRY_DELAY', 1),

    /*
    |--------------------------------------------------------------------------
    | Maximum Retry Delay
    |--------------------------------------------------------------------------
    |
    | Caps exponential retry backoff in seconds. A `Retry-After` or
    | rate-limit reset header sent by the API still takes precedence.
    |
    */
    'max_retry_delay' => (int) env('NANSEN_MAX_RETRY_DELAY', 30),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    |
    | Optional PSR-18 compatible HTTP client service container binding.
    | When left empty, Guzzle will be used automatically.
    |
    */
    'http_client' => env('NANSEN_HTTP_CLIENT'),
];
