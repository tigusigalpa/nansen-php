<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Tests;

use PHPUnit\Framework\TestCase;
use Tigusigalpa\Nansen\Dto\Meta;
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
}
