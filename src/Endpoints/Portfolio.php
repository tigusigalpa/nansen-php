<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Endpoints;

use Tigusigalpa\Nansen\Dto\PortfolioDefiHoldingsResponse;
use Tigusigalpa\Nansen\Http\Client as HttpClient;
use Tigusigalpa\Nansen\RequestBuilder;

final class Portfolio
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    public function defiHoldings(string $walletAddress): RequestBuilder
    {
        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1/portfolio/defi-holdings',
            PortfolioDefiHoldingsResponse::class,
        ))->with('wallet_address', $walletAddress);
    }
}
