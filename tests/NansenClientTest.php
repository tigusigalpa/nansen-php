<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Tests;

use PHPUnit\Framework\TestCase;
use Tigusigalpa\Nansen\Config;
use Tigusigalpa\Nansen\Endpoints\HistoricalData;
use Tigusigalpa\Nansen\Endpoints\Portfolio;
use Tigusigalpa\Nansen\Endpoints\Profiler;
use Tigusigalpa\Nansen\Endpoints\Search;
use Tigusigalpa\Nansen\Endpoints\SmartMoney;
use Tigusigalpa\Nansen\Endpoints\TokenGodMode;
use Tigusigalpa\Nansen\NansenClient;

final class NansenClientTest extends TestCase
{
    public function test_create_from_array_builds_config(): void
    {
        $client = NansenClient::create(['api_key' => 'abc']);

        self::assertSame('abc', $client->config->apiKey);
    }

    public function test_endpoints_return_expected_groups(): void
    {
        $client = new NansenClient(new Config('key'));

        self::assertInstanceOf(SmartMoney::class, $client->smartMoney());
        self::assertInstanceOf(TokenGodMode::class, $client->tokenGodMode());
        self::assertInstanceOf(Profiler::class, $client->profiler());
        self::assertInstanceOf(Portfolio::class, $client->portfolio());
        self::assertInstanceOf(Search::class, $client->search());
        self::assertInstanceOf(HistoricalData::class, $client->historicalData());
    }
}
