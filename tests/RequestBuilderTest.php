<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Tests;

use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tigusigalpa\Nansen\Config;
use Tigusigalpa\Nansen\Dto\SmartMoneyNetflowsResponse;
use Tigusigalpa\Nansen\Http\Client as HttpClient;
use Tigusigalpa\Nansen\RequestBuilder;

final class RequestBuilderTest extends TestCase
{
    private function createBuilder(): RequestBuilder
    {
        $mock = new MockHttpClient();
        $mock->addResponse(new Response(200, [], json_encode([
            'data' => [
                ['chain' => 'ethereum', 'netflow' => 123.45],
            ],
            'meta' => ['limit' => 10, 'offset' => 0],
        ])));

        $http = new HttpClient(new Config('key'), $mock);

        return new RequestBuilder($http, 'POST', 'api/v1/smart-money/netflow', SmartMoneyNetflowsResponse::class);
    }

    public function test_fluent_builders_aggregate_body(): void
    {
        $builder = $this->createBuilder()
            ->chains(['ethereum', 'arbitrum'])
            ->filters(['token' => ['symbol' => 'WETH']])
            ->orderBy('timestamp', 'desc')
            ->limit(10)
            ->offset(0);

        $response = $builder->get();

        self::assertInstanceOf(SmartMoneyNetflowsResponse::class, $response);
        self::assertCount(1, $response->items);
        self::assertSame('ethereum', $response->items[0]?->chain);
        self::assertSame(10, $response->meta?->limit);
    }

    public function test_builder_is_immutable(): void
    {
        $base = $this->createBuilder();
        $a = $base->limit(5);
        $b = $base->limit(10);

        self::assertNotSame($base, $a);
        self::assertNotSame($a, $b);

        self::assertInstanceOf(RequestBuilder::class, $b);
    }

    public function test_filters_are_merged_not_overwritten(): void
    {
        $mock = new MockHttpClient();
        $mock->addResponse(new Response(200, [], json_encode(['data' => []])));

        $http = new HttpClient(new Config('key'), $mock);
        $builder = new RequestBuilder($http, 'POST', 'api/v1/profiler/address/current-balance', SmartMoneyNetflowsResponse::class);

        $builder
            ->filters(['address' => '0xabc'])
            ->filters(['chain' => 'ethereum'])
            ->get();

        $sentBody = json_decode((string) $mock->getRequests()[0]->getBody(), true);

        self::assertSame(
            ['address' => '0xabc', 'chain' => 'ethereum'],
            $sentBody['filters'],
        );
    }

    public function test_order_by_rejects_invalid_direction(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createBuilder()->orderBy('timestamp', 'sideways');
    }
}
