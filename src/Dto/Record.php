<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Dto;

use ArrayAccess;
use OutOfBoundsException;

/**
 * A generic, future-proof DTO representing a single item from a Nansen API response.
 *
 * Specific field values can be read through dynamic property access. The full
 * original row is always available via `$record->raw`.
 */
final class Record extends Dto implements ArrayAccess
{
    public function __get(string $name): mixed
    {
        return $this->raw[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->raw[$name]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->raw[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            throw new OutOfBoundsException("Key \"{$offset}\" does not exist in record.");
        }

        return $this->raw[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('Records are immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('Records are immutable.');
    }

    /**
     * Return the value of a nested key using dot notation.
     *
     * @param array<int, string|int>|string $key
     */
    public function get(array|string $key, mixed $default = null): mixed
    {
        $keys = is_string($key) ? explode('.', $key) : $key;
        $value = $this->raw;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
