<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Starnerz\LaravelDaraja\Data\PulledTransaction;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * Pull Transactions: a reconciliation tool that returns C2B transactions from
 * the last 48 hours, including ones whose callbacks never reached you.
 *
 * Confirmed against the Daraja portal on 15 Aug 2026.
 */
final class PullTransactions extends Api
{
    private const REGISTER_ENDPOINT = 'pulltransactions/v1/register';

    private const QUERY_ENDPOINT = 'pulltransactions/v1/query';

    /**
     * Register a short code for pulling. This is a one-time operation.
     *
     * @param  string|null  $nominatedNumber  The MSISDN on the short code's KYC
     *                                        details in the M-Pesa portal.
     * @return array<string, mixed>
     */
    public function register(
        ?string $nominatedNumber = null,
        ?string $callbackUrl = null,
        ?string $shortCode = null,
    ): array {
        return $this->client->post(self::REGISTER_ENDPOINT, [
            'ShortCode' => $this->initiatorShortCode($shortCode),
            'RequestType' => 'Pull',
            'NominatedNumber' => PhoneNumber::normalise(
                $nominatedNumber ?? (string) $this->setting('pull.nominated_number'),
            ),
            'CallBackURL' => $this->urls->resolve(
                $callbackUrl ?? $this->setting('urls.result.pull'),
                'urls.result.pull',
            ),
        ]);
    }

    /**
     * Fetch transactions for a period. Only the last 48 hours are retained.
     *
     * @param  int  $offset  Row to start from, not a page number
     * @return Collection<int, PulledTransaction>
     */
    public function query(
        DateTimeInterface|string $from,
        DateTimeInterface|string $to,
        int $offset = 0,
        ?string $shortCode = null,
    ): Collection {
        $response = $this->client->post(self::QUERY_ENDPOINT, [
            'ShortCode' => $this->initiatorShortCode($shortCode),
            'StartDate' => $this->formatDate($from),
            'EndDate' => $this->formatDate($to),
            'OffSetValue' => (string) $offset,
        ]);

        return $this->transactions($response);
    }

    /**
     * The API nests the transaction list one level deeper than you would
     * expect: "Response" is an array of arrays.
     *
     * @param  array<string, mixed>  $response
     * @return Collection<int, PulledTransaction>
     */
    private function transactions(array $response): Collection
    {
        $rows = $response['Response'] ?? [];

        return collect(is_array($rows) ? $rows : [])
            ->flatten(1)
            ->filter(fn ($row): bool => is_array($row))
            ->map(fn (array $row): PulledTransaction => PulledTransaction::fromArray($row))
            ->values();
    }

    private function formatDate(DateTimeInterface|string $date): string
    {
        return $date instanceof DateTimeInterface
            ? $date->format('Y-m-d H:i:s')
            : $date;
    }
}
