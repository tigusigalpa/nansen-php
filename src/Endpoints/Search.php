<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Endpoints;

use InvalidArgumentException;
use Tigusigalpa\Nansen\Dto\SearchResultsResponse;
use Tigusigalpa\Nansen\Http\Client as HttpClient;
use Tigusigalpa\Nansen\RequestBuilder;

final class Search
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    public function entityName(string $searchQuery): RequestBuilder
    {
        if (strlen($searchQuery) < 2) {
            throw new InvalidArgumentException('Entity name search query must be at least two characters long.');
        }

        return (new RequestBuilder(
            $this->http,
            'POST',
            'api/v1/search/entity-name',
            SearchResultsResponse::class,
        ))->with('search_query', $searchQuery);
    }

    /**
     * @deprecated Use entityName().
     */
    public function general(string $query): RequestBuilder
    {
        return $this->entityName($query);
    }

    /**
     * @deprecated Use entityName(). The legacy entity search endpoint is no
     *             longer part of the documented Nansen v1 API.
     */
    public function entity(string $entityId): RequestBuilder
    {
        return $this->entityName($entityId);
    }
}
