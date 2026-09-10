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

    public function addressBalance(string $address, string $chain = 'all'): RequestBuilder
    {
        return $this->addressCurrentBalance($address, $chain);
    }

    public function addressCurrentBalance(string $address, string $chain = 'all'): RequestBuilder
    {
        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1/profiler/address/current-balance',
            AddressBalanceResponse::class,
        ))->withAll([
            'address' => $address,
            'chain' => $chain,
        ]);
    }

    /**
     * @param array{from: string, to: string}|null $date
     */
    public function addressDexTrades(string $address, ?string $chain = null, ?array $date = null): RequestBuilder
    {
        $builder = (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1/profiler/dex-trades',
            AddressDexTradesResponse::class,
        ))->with('address', $address);

        if ($chain !== null) {
            $builder = $builder->with('chain', $chain);
        }

        if ($date !== null) {
            $builder = $builder->with('date', $date);
        }

        return $builder;
    }

    public function addressLabels(string $address, string $chain = 'all'): RequestBuilder
    {
        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1/profiler/address/labels',
            AddressLabelsResponse::class,
        ))->withAll([
            'address' => $address,
            'chain' => $chain,
        ]);
    }
}
