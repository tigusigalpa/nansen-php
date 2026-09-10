<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tigusigalpa\Nansen\Config;
use Tigusigalpa\Nansen\Exceptions\ApiException;
use Tigusigalpa\Nansen\Exceptions\NotFoundException;
use Tigusigalpa\Nansen\Exceptions\RateLimitException;
use Tigusigalpa\Nansen\Exceptions\UnauthorizedException;

final class Client
{
    private const USER_AGENT = 'nansen-php/1.0 (+https://github.com/tigusigalpa/nansen-php)';

    private readonly string $baseUri;

    private ?ClientInterface $resolvedClient = null;

    private ?RequestFactoryInterface $resolvedRequestFactory = null;

    private ?StreamFactoryInterface $resolvedStreamFactory = null;

    public function __construct(
        private readonly Config $config,
        private readonly ?ClientInterface $httpClient = null,
        private readonly ?RequestFactoryInterface $requestFactory = null,
        private readonly ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->baseUri = rtrim($this->config->baseUri, '/');
    }

    /**
     * Send a JSON request to the Nansen API.
     *
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    public function send(string $method, string $uri, array $body = []): array
    {
        $client = $this->httpClient ?? $this->resolvedClient ??= $this->defaultClient();
        $requestFactory = $this->requestFactory ?? $this->resolvedRequestFactory ??= new HttpFactory();
        $streamFactory = $this->streamFactory ?? $this->resolvedStreamFactory ??= new HttpFactory();

        $url = $this->baseUri . '/' . ltrim($uri, '/');
        $payload = $this->filterBody($body);

        $encodedPayload = null;
        if ($payload !== []) {
            try {
                $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new ApiException('Failed to encode request body: ' . $e->getMessage(), 0, $e);
            }
        }

        $lastException = null;
        $maxAttempts = max(0, $this->config->retries);

        for ($attempt = 0; $attempt <= $maxAttempts; $attempt++) {
            $request = $requestFactory->createRequest(strtoupper($method), $url)
                ->withHeader('apiKey', $this->config->apiKey)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Accept', 'application/json')
                ->withHeader('User-Agent', self::USER_AGENT);

            if ($encodedPayload !== null) {
                $request = $request->withBody($streamFactory->createStream($encodedPayload));
            }

            try {
                $response = $client->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                $lastException = new ApiException('HTTP request failed: ' . $e->getMessage(), 0, $e);

                if ($attempt < $maxAttempts) {
                    $this->sleepForBackoff($attempt);
                    continue;
                }

                throw $lastException;
            }

            $status = $response->getStatusCode();

            if ($status >= 200 && $status < 300) {
                return $this->parseResponse($response);
            }

            if ($status === 429) {
                $lastException = $this->buildRateLimitException($response);

                if ($attempt < $maxAttempts) {
                    $this->sleepForRetry($response, $attempt);
                    continue;
                }

                throw $lastException;
            }

            if ($status >= 500) {
                $lastException = $this->buildApiException($response);

                if ($attempt < $maxAttempts) {
                    $this->sleepForBackoff($attempt);
                    continue;
                }

                throw $lastException;
            }

            throw $this->buildApiException($response);
        }

        throw $lastException ?? new ApiException('Unexpected HTTP error');
    }

    private function defaultClient(): ClientInterface
    {
        return new GuzzleClient([
            'timeout' => $this->config->timeout,
            'http_errors' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function filterBody(array $body): array
    {
        return array_filter($body, static fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        if ($body === '') {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiException('Failed to decode API response: ' . $e->getMessage(), 0, $e, $response);
        }

        if (!is_array($decoded)) {
            throw new ApiException('Unexpected API response format: expected JSON object', 0, null, $response);
        }

        return $decoded;
    }

    private function buildApiException(ResponseInterface $response): ApiException
    {
        $status = $response->getStatusCode();
        $message = $this->extractErrorMessage($response);

        return match ($status) {
            401 => new UnauthorizedException($message, $status, null, $response),
            404 => new NotFoundException($message, $status, null, $response),
            default => new ApiException($message, $status, null, $response),
        };
    }

    private function buildRateLimitException(ResponseInterface $response): RateLimitException
    {
        $retryAfter = $this->readHeaderInt($response, 'Retry-After');
        $remaining = $this->readHeaderInt($response, 'RateLimit-Remaining');
        if ($remaining === 0) {
            $remaining = $this->readHeaderInt($response, 'X-RateLimit-Remaining');
        }

        return new RateLimitException(
            $this->extractErrorMessage($response),
            $response->getStatusCode(),
            null,
            $response,
            $retryAfter,
            $remaining,
        );
    }

    private function extractErrorMessage(ResponseInterface $response): string
    {
        $body = (string) $response->getBody();

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                foreach (['message', 'error', 'detail'] as $key) {
                    if (isset($decoded[$key]) && is_string($decoded[$key])) {
                        return $decoded[$key];
                    }
                }
            }
        } catch (JsonException) {
            // Fall back to raw body.
        }

        if ($body !== '') {
            return $body;
        }

        return 'HTTP ' . $response->getStatusCode() . ' error';
    }

    private function readHeaderInt(ResponseInterface $response, string $name): int
    {
        $value = $response->getHeaderLine($name);
        if ($value === '') {
            return 0;
        }

        if (str_contains($value, ',')) {
            [$value] = explode(',', $value, 2);
        }

        $value = trim($value);
        if (is_numeric($value)) {
            return (int) $value;
        }

        $date = strtotime($value);
        if ($date !== false) {
            return max(0, $date - time());
        }

        return 0;
    }

    private function sleepForRetry(ResponseInterface $response, int $attempt): void
    {
        $retryAfter = $this->readHeaderInt($response, 'Retry-After');
        $rateLimitReset = $this->readHeaderInt($response, 'RateLimit-Reset');
        if ($rateLimitReset === 0) {
            $rateLimitReset = $this->readHeaderInt($response, 'X-RateLimit-Reset');
        }

        $delay = $retryAfter > 0
            ? $retryAfter
            : ($rateLimitReset > 0 ? $rateLimitReset : $this->backoffDelay($attempt));

        $this->sleep($delay);
    }

    private function sleepForBackoff(int $attempt): void
    {
        $this->sleep($this->backoffDelay($attempt));
    }

    private function backoffDelay(int $attempt): int
    {
        $baseDelay = max(1, $this->config->retryDelay);
        $maxDelay = max($baseDelay, $this->config->maxRetryDelay);
        $multiplier = 2 ** min($attempt, 30);

        return min($maxDelay, $baseDelay * $multiplier);
    }

    private function sleep(int $delay): void
    {
        usleep(max(0, $delay) * 1_000_000);
    }
}
