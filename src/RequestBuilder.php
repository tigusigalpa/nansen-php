<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen;

use InvalidArgumentException;
use Tigusigalpa\Nansen\Dto\Dto;
use Tigusigalpa\Nansen\Enums\SortDirection;
use Tigusigalpa\Nansen\Http\Client as HttpClient;

final class RequestBuilder
{
    /** @var array<string, mixed> */
    private array $body = [];

    public function __construct(
        private readonly HttpClient $http,
        private readonly string $method,
        private readonly string $uri,
        /** @var class-string<Dto> */
        private readonly string $responseClass,
    ) {
        if (!is_a($this->responseClass, Dto::class, true)) {
            throw new InvalidArgumentException('Response class must extend ' . Dto::class);
        }
    }

    /**
     * @param array<int, string> $chains
     */
    public function chains(array $chains): self
    {
        $clone = clone $this;
        $clone->body['chains'] = array_values($chains);

        return $clone;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function filters(array $filters): self
    {
        $clone = clone $this;
        $clone->body['filters'] = array_merge($clone->body['filters'] ?? [], $filters);

        return $clone;
    }

    public function orderBy(string $field, string|SortDirection $direction = 'asc'): self
    {
        if ($direction instanceof SortDirection) {
            $directionValue = $direction->value;
        } else {
            $normalized = strtolower($direction);
            if (!in_array($normalized, ['asc', 'desc'], true)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid sort direction "%s". Expected "asc" or "desc".', $direction),
                );
            }
            $directionValue = $normalized;
        }

        $clone = clone $this;
        $clone->body['order_by'] = [
            'field' => $field,
            'direction' => $directionValue,
        ];

        return $clone;
    }

    public function limit(int $limit): self
    {
        $clone = clone $this;
        $clone->body['pagination']['limit'] = max(1, $limit);

        return $clone;
    }

    public function offset(int $offset): self
    {
        $clone = clone $this;
        $clone->body['pagination']['offset'] = max(0, $offset);

        return $clone;
    }

    public function page(int $page, ?int $perPage = null): self
    {
        $clone = clone $this;
        $clone->body['pagination']['page'] = max(1, $page);

        if ($perPage !== null) {
            $clone->body['pagination']['per_page'] = max(1, $perPage);
        }

        return $clone;
    }

    /**
     * @param array<string, mixed> $pagination
     */
    public function pagination(array $pagination): self
    {
        $clone = clone $this;
        $clone->body['pagination'] = array_merge(
            $clone->body['pagination'] ?? [],
            $pagination,
        );

        return $clone;
    }

    public function get(): Dto
    {
        $raw = $this->http->send($this->method, $this->uri, $this->body);

        /** @var Dto */
        return new ($this->responseClass)($raw);
    }
}
