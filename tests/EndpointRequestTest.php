<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Tigusigalpa\Nansen\Config;
use Tigusigalpa\Nansen\Endpoints\HistoricalData;
use Tigusigalpa\Nansen\Endpoints\Portfolio;
use Tigusigalpa\Nansen\Endpoints\Profiler;
use Tigusigalpa\Nansen\Endpoints\Search;
use Tigusigalpa\Nansen\Http\Client as HttpClient;

final class EndpointRequestTest extends TestCase
{
    public function test_profiler_uses_v1_path_and_root_level_address_fields(): void
    {
        [$http, $mock] = $this->httpWithResponse(['data' => []]);

        (new Profiler($http))->addressCurrentBalance('0xabc', 'ethereum')->get();

        $request = $mock->getRequests()[0];
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('https://api.nansen.ai/api/v1/profiler/address/current-balance', (string) $request->getUri());
        self::assertSame(['address' => '0xabc', 'chain' => 'ethereum'], $body);
    }

    public function test_address_labels_uses_current_v1_endpoint(): void
    {
        [$http, $mock] = $this->httpWithResponse(['data' => []]);

        (new Profiler($http))->addressLabels('0xabc', 'base')->get();

        $request = $mock->getRequests()[0];
        self::assertSame('https://api.nansen.ai/api/v1/profiler/address/labels', (string) $request->getUri());
        self::assertSame(
            ['address' => '0xabc', 'chain' => 'base'],
            json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function test_portfolio_search_and_historical_helpers_use_documented_contracts(): void
    {
        [$portfolioHttp, $portfolioMock] = $this->httpWithResponse(['summary' => [], 'protocols' => []]);
        (new Portfolio($portfolioHttp))->defiHoldings('0xwallet')->get();

        [$searchHttp, $searchMock] = $this->httpWithResponse(['data' => []]);
        (new Search($searchHttp))->entityName('vitalik')->get();

        [$historicalHttp, $historicalMock] = $this->httpWithResponse(['data' => []]);
        (new HistoricalData($historicalHttp))->tokenFlowSummary('ethereum', '0xtoken', [
            'from' => '2026-01-01T00:00:00Z',
            'to' => '2026-01-02T00:00:00Z',
        ])->get();

        self::assertSame(
            ['wallet_address' => '0xwallet'],
            json_decode((string) $portfolioMock->getRequests()[0]->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
        self::assertSame(
            'https://api.nansen.ai/api/v1/search/entity-name',
            (string) $searchMock->getRequests()[0]->getUri(),
        );
        self::assertSame(
            ['search_query' => 'vitalik'],
            json_decode((string) $searchMock->getRequests()[0]->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
        self::assertSame(
            'https://api.nansen.ai/api/v1beta1/tgm/historical-token-flow-summary',
            (string) $historicalMock->getRequests()[0]->getUri(),
        );
    }

    /**
     * @param array<string, mixed> $response
     *
     * @return array{HttpClient, MockHttpClient}
     */
    private function httpWithResponse(array $response): array
    {
        $mock = new MockHttpClient();
        $mock->addResponse(new Response(200, [], json_encode($response, JSON_THROW_ON_ERROR)));

        return [new HttpClient(new Config('key'), $mock), $mock];
    }
}
