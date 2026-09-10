<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Endpoints;

use InvalidArgumentException;
use Tigusigalpa\Nansen\Dto\HistoricalDataResponse;
use Tigusigalpa\Nansen\Http\Client as HttpClient;
use Tigusigalpa\Nansen\RequestBuilder;

final class HistoricalData
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    public function query(string $path): RequestBuilder
    {
        if ($path === '') {
            throw new InvalidArgumentException('Historical data path cannot be empty.');
        }

        $uri = 'api/v1beta1/' . ltrim($path, '/');

        return new RequestBuilder($this->http, 'POST', $uri, HistoricalDataResponse::class);
    }

    public function netflows(): RequestBuilder
    {
        return $this->query('smart-money/netflow');
    }

    public function holdings(): RequestBuilder
    {
        return $this->query('smart-money/holdings');
    }

    public function dexTrades(): RequestBuilder
    {
        return $this->query('smart-money/dex-trades');
    }

    /**
     * @param array{from: string, to: string} $dateRange
     */
    public function tokenFlowSummary(string $chain, string $tokenAddress, array $dateRange): RequestBuilder
    {
        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1beta1/tgm/historical-token-flow-summary',
            HistoricalDataResponse::class,
        ))->withAll([
            'chain' => $chain,
            'token_address' => $tokenAddress,
            'date_range' => $dateRange,
        ]);
    }

    public function smartMoneyTokenBalances(string $asOfDate): RequestBuilder
    {
        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1beta1/smart-money/historical-token-balances',
            HistoricalDataResponse::class,
        ))->with('as_of_date', $asOfDate);
    }
}
