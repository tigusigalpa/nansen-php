<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Endpoints;

use Tigusigalpa\Nansen\Dto\SearchResultsResponse;
use Tigusigalpa\Nansen\Http\Client as HttpClient;
use Tigusigalpa\Nansen\RequestBuilder;

final class Search
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    public function general(string $query): RequestBuilder
    {
        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1/search',
            SearchResultsResponse::class,
        ))->filters(['query' => $query]);
    }

    public function entity(string $entityId): RequestBuilder
    {
        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1/search/entity',
            SearchResultsResponse::class,
        ))->filters(['entity' => $entityId]);
    }
}
