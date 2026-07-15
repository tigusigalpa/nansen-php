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

        $request = $requestFactory->createRequest(strtoupper($method), $url)
            ->withHeader('apiKey', $this->config->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        if ($payload !== []) {
            try {
                $stream = $streamFactory->createStream(json_encode($payload, JSON_THROW_ON_ERROR));
            } catch (JsonException $e) {
                throw new ApiException('Failed to encode request body: ' . $e->getMessage(), 0, $e);
            }
            $request = $request->withBody($stream);
        }

        $lastException = null;
        $maxAttempts = max(0, $this->config->retries);

        for ($attempt = 0; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $client->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                throw new ApiException('HTTP request failed: ' . $e->getMessage(), 0, $e);
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
            'headers' => [
                'User-Agent' => 'tigusigalpa/nansen-php',
            ],
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
        $remaining = max(
            $this->readHeaderInt($response, 'X-RateLimit-Remaining'),
            $this->readHeaderInt($response, 'RateLimit-Remaining'),
        );

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
            if (is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])) {
                return $decoded['message'];
            }
            if (is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])) {
                return $decoded['error'];
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
        $baseDelay = max(1, $this->config->retryDelay);
        $exponential = $baseDelay * (2 ** $attempt);

        $delay = $retryAfter > 0 ? $retryAfter : $exponential;

        usleep($delay * 1_000_000);
    }

    private function sleepForBackoff(int $attempt): void
    {
        $baseDelay = max(1, $this->config->retryDelay);
        $delay = $baseDelay * (2 ** $attempt);

        usleep($delay * 1_000_000);
    }
}
