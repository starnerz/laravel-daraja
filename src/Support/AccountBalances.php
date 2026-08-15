<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Support;

use Illuminate\Support\Collection;

/**
 * Parses the packed balance string returned by the Account Balance API.
 *
 * Safaricom returns every account in a single string rather than JSON:
 *
 *   Working Account|KES|700000.00|700000.00|0.00|0.00&Utility Account|KES|…
 *
 * Accounts are separated by "&" and fields by "|", in the order
 * name | currency | balance | available | reserved | uncleared.
 */
final class AccountBalances
{
    /**
     * @param  Collection<string, DarajaAccountBalance>  $accounts
     */
    private function __construct(private readonly Collection $accounts) {}

    public static function parse(?string $packed): self
    {
        if (blank($packed)) {
            return new self(collect());
        }

        $accounts = collect(explode('&', $packed))
            ->map(fn (string $segment): array => explode('|', trim($segment)))
            ->filter(fn (array $fields): bool => trim($fields[0] ?? '') !== '')
            ->mapWithKeys(function (array $fields): array {
                $name = trim($fields[0]);

                return [$name => [
                    'name' => $name,
                    'currency' => trim($fields[1] ?? ''),
                    'balance' => (float) ($fields[2] ?? 0),
                    'available' => (float) ($fields[3] ?? 0),
                    'reserved' => (float) ($fields[4] ?? 0),
                    'uncleared' => (float) ($fields[5] ?? 0),
                ]];
            });

        return new self($accounts);
    }

    /**
     * @return DarajaAccountBalance|null
     */
    public function account(string $name): ?array
    {
        return $this->accounts->get($name);
    }

    /**
     * Receives customer payments on a pay bill; funds disbursement on B2C.
     *
     * @return DarajaAccountBalance|null
     */
    public function utility(): ?array
    {
        return $this->account('Utility Account');
    }

    /**
     * Holds money awaiting settlement. Also called the MMF account.
     *
     * @return DarajaAccountBalance|null
     */
    public function working(): ?array
    {
        return $this->account('Working Account');
    }

    /**
     * Accrues tariff charges; always carries a negative balance.
     *
     * @return DarajaAccountBalance|null
     */
    public function chargesPaid(): ?array
    {
        return $this->account('Charges Paid Account');
    }

    public function balanceOf(string $name): float
    {
        return $this->account($name)['balance'] ?? 0.0;
    }

    /**
     * @return array<string, DarajaAccountBalance>
     */
    public function toArray(): array
    {
        return $this->accounts->all();
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->accounts->keys()->all();
    }
}
