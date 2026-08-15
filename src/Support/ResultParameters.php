<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Support;

use Illuminate\Support\Collection;

/**
 * Normalises Daraja's key/value parameter bags.
 *
 * Every result callback carries its detail as a list of {Key, Value} pairs, but
 * the shape varies in three ways that break naive parsing:
 *
 *  1. The list is an array on success and a bare object on failure.
 *  2. Keys repeat — Transaction Status returns DebitPartyName twice, once for
 *     each side of the transaction.
 *  3. A pair may carry no Value at all, e.g. {"Key": "TransactionReason"}.
 *
 * Different APIs also case the keys differently: Key/Value in the Result
 * envelope, Name/Value in STK callbacks, name/value in M-Pesa Ratiba.
 */
final class ResultParameters
{
    /**
     * @param  Collection<int, array{key: string, value: mixed}>  $pairs
     */
    private function __construct(private readonly Collection $pairs) {}

    /**
     * @param  mixed  $raw  An array of pairs, a single pair, or null
     */
    public static function make(mixed $raw): self
    {
        if (! is_array($raw)) {
            return new self(collect());
        }

        // A single pair arrives as {"Key": "...", "Value": "..."} rather than a
        // list containing it, so wrap it before mapping.
        $rows = array_is_list($raw) ? $raw : [$raw];

        $pairs = collect($rows)
            ->filter(fn ($row): bool => is_array($row))
            ->map(fn (array $row): array => [
                'key' => (string) ($row['Key'] ?? $row['Name'] ?? $row['name'] ?? ''),
                // Absent deliberately on some pairs; null is the honest answer.
                'value' => $row['Value'] ?? $row['value'] ?? null,
            ])
            ->filter(fn (array $pair): bool => $pair['key'] !== '')
            ->values();

        /** @var Collection<int, array{key: string, value: mixed}> $pairs */
        return new self($pairs);
    }

    /**
     * The first value recorded for a key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $match = $this->pairs->firstWhere('key', $key);

        return $match['value'] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return $value === null ? $default : (string) $value;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->get($key);

        return $value === null ? $default : (float) $value;
    }

    /**
     * Every value recorded for a key, in order. Use this where Daraja repeats a
     * key, such as DebitPartyName on Transaction Status.
     *
     * @return list<mixed>
     */
    public function all(string $key): array
    {
        return $this->pairs
            ->where('key', $key)
            ->pluck('value')
            ->all();
    }

    public function has(string $key): bool
    {
        return $this->pairs->contains('key', $key);
    }

    /**
     * Flatten to a plain map. Repeated keys collapse to the first occurrence,
     * so prefer all() when duplicates matter.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->pairs as $pair) {
            if (! array_key_exists($pair['key'], $result)) {
                $result[$pair['key']] = $pair['value'];
            }
        }

        return $result;
    }

    public function isEmpty(): bool
    {
        return $this->pairs->isEmpty();
    }
}
