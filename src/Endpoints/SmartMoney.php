<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Endpoints;

use Tigusigalpa\Nansen\Dto\SmartMoneyDexTradesResponse;
use Tigusigalpa\Nansen\Dto\SmartMoneyHoldingsResponse;
use Tigusigalpa\Nansen\Dto\SmartMoneyNetflowsResponse;
use Tigusigalpa\Nansen\Http\Client as HttpClient;
use Tigusigalpa\Nansen\RequestBuilder;

final class SmartMoney
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    public function netflows(): RequestBuilder
    {
        return new RequestBuilder($this->http, 'POST', 'api/v1/smart-money/netflow', SmartMoneyNetflowsResponse::class);
    }

    public function holdings(): RequestBuilder
    {
        return new RequestBuilder($this->http, 'POST', 'api/v1/smart-money/holdings', SmartMoneyHoldingsResponse::class);
    }

    public function dexTrades(): RequestBuilder
    {
        return new RequestBuilder($this->http, 'POST', 'api/v1/smart-money/dex-trades', SmartMoneyDexTradesResponse::class);
    }
}
