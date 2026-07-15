<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Dto;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * A typed collection of Record DTOs implementing Countable, IteratorAggregate and ArrayAccess.
 *
 * @implements IteratorAggregate<int, Record>
 * @implements ArrayAccess<int, Record>
 */
final class RecordCollection implements Countable, IteratorAggregate, ArrayAccess
{
    /** @var array<int, Record> */
    private readonly array $items;

    public function __construct(
        array $items = [],
        public readonly array $raw = [],
    ) {
        $normalized = [];
        foreach ($items as $item) {
            if ($item instanceof Record) {
                $normalized[] = $item;
                continue;
            }

            if (is_array($item)) {
                $normalized[] = new Record($item);
                continue;
            }

            throw new InvalidArgumentException(
                'Collection items must be arrays or instances of ' . Record::class,
            );
        }

        $this->items = $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn (Record $record): array => $record->raw, $this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, Record>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): ?Record
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('Record collections are immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('Record collections are immutable.');
    }

    public function first(): ?Record
    {
        return $this->items[0] ?? null;
    }
}
