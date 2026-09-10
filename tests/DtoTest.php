<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Tests;

use PHPUnit\Framework\TestCase;
use Tigusigalpa\Nansen\Dto\Meta;
use Tigusigalpa\Nansen\Dto\PortfolioDefiHoldingsResponse;
use Tigusigalpa\Nansen\Dto\Record;
use Tigusigalpa\Nansen\Dto\RecordCollection;
use Tigusigalpa\Nansen\Dto\SmartMoneyNetflowsResponse;

final class DtoTest extends TestCase
{
    public function test_response_preserves_raw_payload(): void
    {
        $raw = [
            'data' => [
                ['chain' => 'ethereum', 'extra_field' => 123],
            ],
            'meta' => ['limit' => 10, 'total' => 1],
        ];

        $response = new SmartMoneyNetflowsResponse($raw);

        self::assertSame($raw, $response->raw);
        self::assertCount(1, $response->items);
        self::assertSame('ethereum', $response->items[0]?->chain);
        self::assertSame(123, $response->items[0]?->extra_field);
    }

    public function test_collection_implements_required_interfaces(): void
    {
        $collection = new RecordCollection([
            ['id' => 1],
            ['id' => 2],
        ]);

        self::assertInstanceOf(\Countable::class, $collection);
        self::assertInstanceOf(\IteratorAggregate::class, $collection);
        self::assertInstanceOf(\ArrayAccess::class, $collection);
        self::assertCount(2, $collection);
        self::assertSame(1, $collection[0]?->id);
        self::assertSame(1, $collection->first()?->id);
    }

    public function test_record_dynamic_access_and_nested_get(): void
    {
        $record = new Record([
            'symbol' => 'WETH',
            'nested' => ['value' => 42],
        ]);

        self::assertSame('WETH', $record->symbol);
        self::assertSame(42, $record->get('nested.value'));
        self::assertNull($record->get('missing'));
        self::assertSame('default', $record->get('missing', 'default'));
        self::assertTrue(isset($record->symbol));
        self::assertFalse(isset($record->missing));
    }

    public function test_meta_values(): void
    {
        $meta = new Meta(limit: 25, offset: 50, total: 100, hasMore: true);

        self::assertSame(25, $meta->limit);
        self::assertSame(50, $meta->offset);
        self::assertSame(100, $meta->total);
        self::assertTrue($meta->hasMore);
    }

    public function test_v1_pagination_metadata_is_exposed(): void
    {
        $response = new SmartMoneyNetflowsResponse([
            'data' => [],
            'pagination' => ['page' => 2, 'per_page' => 25, 'is_last_page' => false],
        ]);

        self::assertSame(2, $response->meta?->page);
        self::assertSame(25, $response->meta?->perPage);
        self::assertFalse($response->meta?->isLastPage);
        self::assertTrue($response->meta?->hasMore);
    }

    public function test_portfolio_response_exposes_summary_and_protocols(): void
    {
        $response = new PortfolioDefiHoldingsResponse([
            'summary' => ['total_value_usd' => 125.5],
            'protocols' => [['protocol_name' => 'Aave', 'chain' => 'ethereum']],
        ]);

        self::assertSame(125.5, $response->summary?->total_value_usd);
        self::assertCount(1, $response->protocols);
        self::assertSame('Aave', $response->protocols->first()?->protocol_name);
        self::assertSame($response->protocols, $response->items);
        self::assertNull($response->meta);
    }
}
