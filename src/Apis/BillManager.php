<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Starnerz\LaravelDaraja\Data\BillManagerResponse;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * Bill Manager: send e-invoices, receive payments and reconcile them.
 *
 * Confirmed against the Daraja portal on 15 Aug 2026.
 *
 * Opt-in must happen first — it whitelists your short code and returns the
 * app key that bulk invoicing requires as a header.
 */
final class BillManager extends Api
{
    private const OPTIN_ENDPOINT = 'v1/billmanager-invoice/optin';

    private const UPDATE_OPTIN_ENDPOINT = 'v1/billmanager-invoice/change-optin-details';

    private const SINGLE_INVOICE_ENDPOINT = 'v1/billmanager-invoice/single-invoicing';

    private const BULK_INVOICE_ENDPOINT = 'v1/billmanager-invoice/bulk-invoicing';

    private const RECONCILIATION_ENDPOINT = 'v1/billmanager-invoice/reconciliation';

    private const CANCEL_SINGLE_ENDPOINT = 'v1/billmanager-invoice/cancel-single-invoice';

    private const CANCEL_BULK_ENDPOINT = 'v1/billmanager-invoice/cancel-bulk-invoices';

    /**
     * Safaricom caps a bulk invoice call at 1000 invoices.
     */
    private const BULK_LIMIT = 1000;

    /**
     * Opt your short code in to Bill Manager. Returns the app key — store it,
     * it is required by bulk invoicing and is not returned again.
     */
    public function optIn(
        string $email,
        string $officialContact,
        bool $sendReminders = true,
        ?string $logo = null,
        ?string $callbackUrl = null,
        ?string $shortCode = null,
    ): BillManagerResponse {
        return $this->send(self::OPTIN_ENDPOINT, array_filter([
            'shortcode' => $this->initiatorShortCode($shortCode),
            'email' => $email,
            'officialContact' => $officialContact,
            'sendReminders' => $sendReminders ? '1' : '0',
            'logo' => $logo,
            'callbackurl' => $this->urls->resolve(
                $callbackUrl ?? $this->setting('urls.result.bill_manager'),
                'urls.result.bill_manager',
            ),
        ], fn ($value): bool => $value !== null));
    }

    /**
     * Update the details supplied at opt-in.
     */
    public function updateOptIn(
        string $email,
        string $officialContact,
        bool $sendReminders = true,
        ?string $logo = null,
        ?string $callbackUrl = null,
        ?string $shortCode = null,
    ): BillManagerResponse {
        return $this->send(self::UPDATE_OPTIN_ENDPOINT, array_filter([
            'shortcode' => $this->initiatorShortCode($shortCode),
            'email' => $email,
            'officialContact' => $officialContact,
            'sendReminders' => $sendReminders ? 1 : 0,
            'logo' => $logo,
            'callbackurl' => $this->urls->resolveOptional($callbackUrl),
        ], fn ($value): bool => $value !== null));
    }

    /**
     * Send one e-invoice.
     *
     * @param  array<int, array{itemName: string, amount: int|float|string}>  $items
     */
    public function invoice(
        string $externalReference,
        string $billedFullName,
        string $billedPhoneNumber,
        string $billedPeriod,
        string $invoiceName,
        string $dueDate,
        string $accountReference,
        int|float|string $amount,
        array $items = [],
    ): BillManagerResponse {
        return $this->send(self::SINGLE_INVOICE_ENDPOINT, $this->invoicePayload(
            $externalReference,
            $billedFullName,
            $billedPhoneNumber,
            $billedPeriod,
            $invoiceName,
            $dueDate,
            $accountReference,
            $amount,
            $items,
        ));
    }

    /**
     * Send up to 1000 e-invoices in one call.
     *
     * @param  array<int, array<string, mixed>>  $invoices  Each entry uses the same
     *                                                      keys as invoice()
     * @param  string|null  $appKey  The key returned at opt-in; required here
     */
    public function bulkInvoice(array $invoices, ?string $appKey = null): BillManagerResponse
    {
        if ($invoices === []) {
            throw new DarajaException('At least one invoice is required for a bulk invoice request.');
        }

        if (count($invoices) > self::BULK_LIMIT) {
            throw new DarajaException(
                'Bill Manager accepts a maximum of '.self::BULK_LIMIT.' invoices per bulk request, '
                .count($invoices).' given.',
            );
        }

        $appKey ??= $this->setting('bill_manager.app_key');

        if (blank($appKey)) {
            throw ConfigurationException::missing('bill_manager.app_key');
        }

        $response = $this->client->pendingRequest()
            ->withHeaders(['appKey' => (string) $appKey])
            ->post(self::BULK_INVOICE_ENDPOINT, array_values($invoices));

        return BillManagerResponse::fromArray((array) $response->json());
    }

    /**
     * Acknowledge a payment pushed to your callback so Bill Manager sends the
     * customer an e-receipt.
     */
    public function acknowledge(
        string $paymentDate,
        int|float|string $paidAmount,
        string $accountReference,
        string $transactionId,
        string $phoneNumber,
        string $fullName,
        string $invoiceName,
        string $externalReference,
    ): BillManagerResponse {
        return $this->send(self::RECONCILIATION_ENDPOINT, [
            'paymentDate' => $paymentDate,
            'paidAmount' => $this->amount($paidAmount),
            'accountReference' => $accountReference,
            'transactionId' => $transactionId,
            'phoneNumber' => PhoneNumber::normalise($phoneNumber),
            'fullName' => $fullName,
            'invoiceName' => $invoiceName,
            'externalReference' => $externalReference,
        ]);
    }

    /**
     * Recall a sent invoice. Partially or fully paid invoices cannot be
     * cancelled — Safaricom answers 409 in that case.
     */
    public function cancelInvoice(string $externalReference): BillManagerResponse
    {
        return $this->send(self::CANCEL_SINGLE_ENDPOINT, [
            'externalReference' => $externalReference,
        ]);
    }

    /**
     * Recall several invoices at once.
     *
     * @param  array<int, string>  $externalReferences
     */
    public function cancelInvoices(array $externalReferences): BillManagerResponse
    {
        $payload = array_map(
            fn (string $reference): array => ['externalReference' => $reference],
            array_values($externalReferences),
        );

        return $this->send(self::CANCEL_BULK_ENDPOINT, $payload);
    }

    /**
     * @param  array<int, array{itemName: string, amount: int|float|string}>  $items
     * @return array<string, mixed>
     */
    private function invoicePayload(
        string $externalReference,
        string $billedFullName,
        string $billedPhoneNumber,
        string $billedPeriod,
        string $invoiceName,
        string $dueDate,
        string $accountReference,
        int|float|string $amount,
        array $items,
    ): array {
        $payload = [
            'externalReference' => $externalReference,
            'billedFullName' => $billedFullName,
            'billedPhoneNumber' => PhoneNumber::normalise($billedPhoneNumber),
            'billedPeriod' => $billedPeriod,
            'invoiceName' => $invoiceName,
            'dueDate' => $dueDate,
            'accountReference' => $accountReference,
            'amount' => $this->amount($amount),
        ];

        if ($items !== []) {
            $payload['invoiceItems'] = array_map(fn (array $item): array => [
                'itemName' => (string) $item['itemName'],
                'amount' => $this->amount($item['amount']),
            ], array_values($items));
        }

        return $payload;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    private function send(string $endpoint, array $payload): BillManagerResponse
    {
        return BillManagerResponse::fromArray($this->client->post($endpoint, $payload));
    }
}
