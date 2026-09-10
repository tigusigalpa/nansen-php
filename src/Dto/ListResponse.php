<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Dto;

abstract class ListResponse extends Dto
{
    public readonly RecordCollection $items;
    public readonly ?Meta $meta;

    public function __construct(array $raw)
    {
        parent::__construct($raw);
        $this->items = new RecordCollection($this->buildItems($raw), $raw);
        $this->meta = $this->buildMeta($raw);
    }

    /**
     * @return class-string<Record>
     */
    abstract protected function itemClass(): string;

    /**
     * @return array<int, array<string, mixed>|Record>
     */
    private function buildItems(array $raw): array
    {
        $items = $raw['data'] ?? $raw['items'] ?? $raw['results'] ?? $raw['result'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        if ($items !== [] && !array_is_list($items)) {
            $items = [$items];
        }

        $class = $this->itemClass();
        $normalized = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $normalized[] = new $class($item);
            }
        }

        return $normalized;
    }

    private function buildMeta(array $raw): ?Meta
    {
        $meta = $raw['meta'] ?? $raw['pagination'] ?? null;

        if (!is_array($meta)) {
            return null;
        }

        $isLastPage = isset($meta['is_last_page']) ? (bool) $meta['is_last_page'] : null;

        return new Meta(
            limit: isset($meta['limit']) && is_numeric($meta['limit']) ? (int) $meta['limit'] : null,
            offset: isset($meta['offset']) && is_numeric($meta['offset']) ? (int) $meta['offset'] : null,
            total: isset($meta['total']) && is_numeric($meta['total']) ? (int) $meta['total'] : null,
            hasMore: isset($meta['has_more']) ? (bool) $meta['has_more'] : ($isLastPage !== null ? !$isLastPage : null),
            page: isset($meta['page']) && is_numeric($meta['page']) ? (int) $meta['page'] : null,
            perPage: isset($meta['per_page']) && is_numeric($meta['per_page']) ? (int) $meta['per_page'] : null,
            isLastPage: $isLastPage,
        );
    }
}
