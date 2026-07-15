<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Endpoints;

use Tigusigalpa\Nansen\Dto\AddressBalanceResponse;
use Tigusigalpa\Nansen\Dto\AddressDexTradesResponse;
use Tigusigalpa\Nansen\Dto\AddressLabelsResponse;
use Tigusigalpa\Nansen\Http\Client as HttpClient;
use Tigusigalpa\Nansen\RequestBuilder;

final class Profiler
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    public function addressBalance(string $address): RequestBuilder
    {
        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1/profiler/address/current-balance',
            AddressBalanceResponse::class,
        ))->filters(['address' => $address]);
    }

    public function addressDexTrades(string $address): RequestBuilder
    {
        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1/profiler/dex-trades',
            AddressDexTradesResponse::class,
        ))->filters(['address' => $address]);
    }

    public function addressLabels(string $address): RequestBuilder
    {
        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/beta/profiler/address/labels',
            AddressLabelsResponse::class,
        ))->filters(['address' => $address]);
    }
}
