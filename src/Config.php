<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen;

use InvalidArgumentException;

final class Config
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $baseUri = 'https://api.nansen.ai',
        public readonly int $timeout = 30,
        public readonly int $retries = 3,
        public readonly int $retryDelay = 1,
        public readonly ?string $httpClient = null,
        public readonly int $maxRetryDelay = 30,
    ) {
        if ($this->apiKey === '') {
            throw new InvalidArgumentException('Nansen API key cannot be empty.');
        }

        $this->assertValidBaseUri($this->baseUri);

        if ($this->timeout <= 0) {
            throw new InvalidArgumentException('Nansen timeout must be positive.');
        }

        if ($this->retries < 0) {
            throw new InvalidArgumentException('Nansen retries cannot be negative.');
        }

        if ($this->retries > 0 && $this->retryDelay <= 0) {
            throw new InvalidArgumentException('Nansen retry delay must be positive when retries are enabled.');
        }

        if ($this->retries > 0 && $this->maxRetryDelay <= 0) {
            throw new InvalidArgumentException('Nansen maximum retry delay must be positive when retries are enabled.');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $apiKey = $config['api_key'] ?? $config['apiKey'] ?? '';
        $baseUri = $config['base_uri'] ?? $config['baseUri'] ?? 'https://api.nansen.ai';
        $timeout = (int) ($config['timeout'] ?? 30);
        $retries = (int) ($config['retries'] ?? 3);
        $retryDelay = (int) ($config['retry_delay'] ?? $config['retryDelay'] ?? 1);
        $maxRetryDelay = (int) ($config['max_retry_delay'] ?? $config['maxRetryDelay'] ?? 30);
        $httpClient = $config['http_client'] ?? $config['httpClient'] ?? null;

        return new self(
            (string) $apiKey,
            (string) $baseUri,
            $timeout,
            $retries,
            $retryDelay,
            $httpClient !== null ? (string) $httpClient : null,
            $maxRetryDelay,
        );
    }

    private function assertValidBaseUri(string $baseUri): void
    {
        $parsed = parse_url($baseUri);
        $scheme = is_array($parsed) ? strtolower((string) ($parsed['scheme'] ?? '')) : '';
        $host = is_array($parsed) ? $parsed['host'] ?? null : null;

        if (
            $parsed === false
            || filter_var($baseUri, FILTER_VALIDATE_URL) === false
            || !in_array($scheme, ['http', 'https'], true)
            || !is_string($host)
            || $host === ''
        ) {
            throw new InvalidArgumentException('Nansen base URI must be an absolute HTTP(S) URL.');
        }

        if (isset($parsed['query']) || isset($parsed['fragment'])) {
            throw new InvalidArgumentException('Nansen base URI cannot include a query string or fragment.');
        }
    }
}
