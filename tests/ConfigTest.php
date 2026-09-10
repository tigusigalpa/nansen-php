<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tigusigalpa\Nansen\Config;

final class ConfigTest extends TestCase
{
    public function test_default_values(): void
    {
        $config = new Config('secret');

        self::assertSame('secret', $config->apiKey);
        self::assertSame('https://api.nansen.ai', $config->baseUri);
        self::assertSame(30, $config->timeout);
        self::assertSame(3, $config->retries);
        self::assertSame(1, $config->retryDelay);
        self::assertSame(30, $config->maxRetryDelay);
        self::assertNull($config->httpClient);
    }

    public function test_from_array(): void
    {
        $config = Config::fromArray([
            'api_key' => 'key',
            'base_uri' => 'https://custom.example.com',
            'timeout' => 10,
            'retries' => 5,
            'retry_delay' => 2,
            'max_retry_delay' => 15,
            'http_client' => 'custom.client',
        ]);

        self::assertSame('key', $config->apiKey);
        self::assertSame('https://custom.example.com', $config->baseUri);
        self::assertSame(10, $config->timeout);
        self::assertSame(5, $config->retries);
        self::assertSame(2, $config->retryDelay);
        self::assertSame(15, $config->maxRetryDelay);
        self::assertSame('custom.client', $config->httpClient);
    }

    public function test_empty_api_key_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('');
    }

    public function test_invalid_base_uri_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('key', 'https://api.nansen.ai?tenant=wrong');
    }

    public function test_invalid_retry_configuration_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('key', retries: 1, retryDelay: 0);
    }
}
