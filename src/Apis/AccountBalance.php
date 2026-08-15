<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Illuminate\Support\Str;
use Starnerz\LaravelDaraja\Data\Acknowledgement;
use Starnerz\LaravelDaraja\Enums\CommandId;
use Starnerz\LaravelDaraja\Enums\IdentifierType;

/**
 * Query the balance of your short code. The balance itself is delivered to your
 * result URL, not returned here.
 */
final class AccountBalance extends Api
{
    private const ENDPOINT = 'mpesa/accountbalance/v1/query';

    public function query(
        string $remarks = 'Account balance query',
        ?string $shortCode = null,
        ?IdentifierType $identifierType = null,
    ): Acknowledgement {
        $identifierType ??= IdentifierType::fromConfig($this->setting('initiator.type'));

        $response = $this->client->post(self::ENDPOINT, [
            'Initiator' => $this->initiatorName(),
            'SecurityCredential' => $this->credential->generate(),
            'CommandID' => CommandId::AccountBalance->value,
            'PartyA' => $this->initiatorShortCode($shortCode),
            'IdentifierType' => (string) $identifierType->value,
            'Remarks' => Str::limit($remarks, 100, ''),
            'QueueTimeOutURL' => $this->urls->resolve(
                $this->setting('urls.timeout.balance'),
                'urls.timeout.balance',
            ),
            'ResultURL' => $this->urls->resolve(
                $this->setting('urls.result.balance'),
                'urls.result.balance',
            ),
        ]);

        return Acknowledgement::fromArray($response);
    }
}
