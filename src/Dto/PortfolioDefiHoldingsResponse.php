<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Dto;

final class PortfolioDefiHoldingsResponse extends Dto
{
    public readonly ?Record $summary;

    public readonly RecordCollection $protocols;

    /**
     * @deprecated Use protocols. Kept as an alias for list-response
     *             consumers from earlier releases.
     */
    public readonly RecordCollection $items;

    /**
     * @deprecated Portfolio responses are not paginated.
     */
    public readonly ?Meta $meta;

    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(array $raw)
    {
        parent::__construct($raw);

        $summary = $raw['summary'] ?? null;
        $this->summary = is_array($summary) ? new Record($summary) : null;

        $protocols = $raw['protocols'] ?? [];
        $this->protocols = new RecordCollection(is_array($protocols) ? $protocols : [], $raw);
        $this->items = $this->protocols;
        $this->meta = null;
    }
}
