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
    ) {
        if ($this->apiKey === '') {
            throw new InvalidArgumentException('Nansen API key cannot be empty.');
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
        $httpClient = $config['http_client'] ?? $config['httpClient'] ?? null;

        return new self(
            (string) $apiKey,
            (string) $baseUri,
            $timeout,
            $retries,
            $retryDelay,
            $httpClient !== null ? (string) $httpClient : null,
        );
    }
}
