<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Dto;

final class Meta
{
    public function __construct(
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
        public readonly ?int $total = null,
        public readonly ?bool $hasMore = null,
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
        public readonly ?bool $isLastPage = null,
    ) {
    }
}
