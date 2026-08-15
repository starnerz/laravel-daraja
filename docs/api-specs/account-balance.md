# Account Balance

Captured from the Daraja portal, 15 August 2026. Portal slug `AccountBalance`.

**No code changes required** — `src/Apis/AccountBalance.php` already matches
the documented contract exactly.

## Endpoint — v1 confirmed

```
POST https://sandbox.safaricom.co.ke/mpesa/accountbalance/v1/query
POST https://api.safaricom.co.ke/mpesa/accountbalance/v1/query
```

## Request

```json
{
    "Initiator": "testapiuser",
    "SecurityCredential": "SAFVNChNHfVtXEZMBuVo+a1Hwr+DtrUVN3zVg==",
    "CommandID": "AccountBalance",
    "PartyA": "600000",
    "IdentifierType": "4",
    "Remarks": "ok",
    "QueueTimeOutURL": "http://myservice:8080/queuetimeouturl",
    "ResultURL": "http://myservice:8080/result"
}
```

Note there is **no `Occasion`/`Occassion` field** on this API, unlike
Transaction Status and Reversal.

## Response

```json
{
    "OriginatorConversationID": "515-5258779-3",
    "ConversationID": "AG_20200123_0000417fed8ed666e976",
    "ResponseCode": "0",
    "ResponseDescription": "Accept the service request successfully"
}
```

## Result callback — the balance string

The balance arrives as a **single packed string**, not structured JSON:

```json
{
    "Key": "AccountBalance",
    "Value": "Working Account|KES|700000.00|700000.00|0.00|0.00&Float Account|KES|0.00|0.00|0.00|0.00&Utility Account|KES|228037.00|228037.00|0.00|0.00&Charges Paid Account|KES|-1540.00|-1540.00|0.00|0.00&Organization Settlement Account|KES|0.00|0.00|0.00|0.00"
}
```

Format: accounts separated by `&`, fields within an account separated by `|`:

```
<Account Name>|<Currency>|<Balance>|<Available>|<Reserved>|<Uncleared>
```

> This is the single strongest argument for a DTO in this package. Handing an
> application that string is not an integration. `AccountBalanceResult` should
> parse it into named, typed account balances.

The callback also carries `BOCompletedTime` (`YYYYMMDDHHmmss`).

## Account types

| Account | B2C | Pay Bill | Buy Goods | Purpose |
|---|---|---|---|---|
| Utility | ✓ | ✓ | — | Receives payments; in B2C, funds disbursement |
| Merchant | — | — | ✓ | Receives till payments |
| Working (MMF) | ✓ | ✓ | ✓ | Transition account awaiting settlement |
| Charges Paid | ✓ | ✓ | ✓ | Accrues tariff charges; always negative |
| Organization Settlement | ✓ | ✓ | ✓ | Passes money to Working after charges |

## Error codes

| errorCode | Meaning | HTTP |
|---|---|---|
| `401.002.01` | Invalid access token | 401 |
| `400.002.02` | Bad request — invalid field | 400 |
| `404.002.01` | Resource not found | 404 |
| `405.001` | Method not allowed — must be POST | 405 |
| `500.002.1001` | Duplicate `OriginatorConversationID` | 500 |
| `500.003.1001` | Internal server error | 500 |
| `500.003.02` | Spike arrest violation | 500 |
| `500.003.03` | Quota violation | 500 |

Result-level codes (`ApiResult` / `ApiResponse`) include 15 (duplicate detected),
18 (initiator credential check failure), 20 (unresolved initiator), 21 (initiator
lacks permission on primary party), 24 (missing mandatory fields), 25 (invalid
request parameters), 26 (traffic blocking in place), 29 (invalid command).

## Operational notes

- **No callback retries.** If your `ResultURL` is unreachable, M-Pesa does not
  retry — you must reconcile via Transaction Status or the M-Pesa portal.
- You can only query **your own** short code. Third parties cannot query another
  merchant's balance; only an initiator within the hierarchy can query a
  merchant store.
