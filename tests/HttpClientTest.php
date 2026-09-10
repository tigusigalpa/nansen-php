<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Tigusigalpa\Nansen\Config;
use Tigusigalpa\Nansen\Exceptions\NotFoundException;
use Tigusigalpa\Nansen\Exceptions\RateLimitException;
use Tigusigalpa\Nansen\Exceptions\UnauthorizedException;
use Tigusigalpa\Nansen\Http\Client as HttpClient;

final class HttpClientTest extends TestCase
{
    public function test_sends_post_with_api_key_and_json_body(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse(new Response(200, [], json_encode(['data' => [['chain' => 'ethereum']]])));

        $client = new HttpClient(new Config('test-key'), $mock);
        $result = $client->send('POST', 'api/v1/smart-money/netflow', ['chains' => ['ethereum']]);

        self::assertSame(['data' => [['chain' => 'ethereum']]], $result);

        $request = $mock->getRequests()[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.nansen.ai/api/v1/smart-money/netflow', (string) $request->getUri());
        self::assertSame('test-key', $request->getHeaderLine('apiKey'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame('nansen-php/1.0 (+https://github.com/tigusigalpa/nansen-php)', $request->getHeaderLine('User-Agent'));
    }

    public function test_throws_unauthorized_on_401(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse(new Response(401, [], json_encode(['message' => 'bad key'])));

        $client = new HttpClient(new Config('bad-key'), $mock);

        $this->expectException(UnauthorizedException::class);
        $client->send('GET', 'api/v1/smart-money/netflow');
    }

    public function test_throws_not_found_on_404(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse(new Response(404, [], json_encode(['message' => 'not found'])));

        $client = new HttpClient(new Config('key'), $mock);

        $this->expectException(NotFoundException::class);
        $client->send('GET', 'api/v1/foo');
    }

    public function test_retries_on_429_then_succeeds(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse(new Response(429, ['Retry-After' => '1', 'X-RateLimit-Remaining' => '0']));
        $mock->addResponse(new Response(200, [], json_encode(['data' => []])));

        $start = time();
        $client = new HttpClient(new Config('key', retries: 3, retryDelay: 1), $mock);
        $client->send('GET', 'api/v1/smart-money/netflow');
        $elapsed = time() - $start;

        self::assertCount(2, $mock->getRequests());
        self::assertGreaterThanOrEqual(1, $elapsed);
    }

    public function test_rate_limit_exception_contains_retry_info(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse(new Response(429, ['Retry-After' => '5', 'RateLimit-Remaining' => '2']));

        $client = new HttpClient(new Config('key', retries: 0), $mock);

        try {
            $client->send('GET', 'api/v1/smart-money/netflow');
            self::fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            self::assertSame(5, $e->retryAfter());
            self::assertSame(2, $e->remaining());
        }
    }

    public function test_retries_on_transient_server_error_then_succeeds(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse(new Response(503, [], json_encode(['detail' => 'temporarily unavailable'])));
        $mock->addResponse(new Response(200, [], json_encode(['data' => []])));

        $client = new HttpClient(new Config('key', retries: 1, retryDelay: 1), $mock);
        $result = $client->send('POST', 'api/v1/smart-money/netflow', ['chains' => ['ethereum']]);

        self::assertSame(['data' => []], $result);
        self::assertCount(2, $mock->getRequests());
        self::assertSame(
            ['chains' => ['ethereum']],
            json_decode((string) $mock->getRequests()[1]->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function test_empty_body_returns_empty_array(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse(new Response(204, [], ''));

        $client = new HttpClient(new Config('key'), $mock);
        $result = $client->send('DELETE', 'api/v1/foo');

        self::assertSame([], $result);
    }
}
