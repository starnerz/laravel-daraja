# Business To Customer (B2C)

Captured from the Daraja portal, 15 August 2026. Portal slug `BusinessToCustomer`.

## Endpoint — **v3**

| Environment | URL |
|---|---|
| Sandbox | `POST https://sandbox.safaricom.co.ke/mpesa/b2c/v3/paymentrequest` |
| Production | `POST https://api.safaricom.co.ke/mpesa/b2c/v3/paymentrequest` |

## Request

```json
{
    "OriginatorConversationID": "600997_Test_32et3241ed8yu",
    "InitiatorName": "testapi",
    "SecurityCredential": "RC6E9WDxXR4b9X2c6z3gp0oC5Th ==",
    "CommandID": "BusinessPayment",
    "Amount": "10",
    "PartyA": "600992",
    "PartyB": "254705912645",
    "Remarks": "remarked",
    "QueueTimeOutURL": "https://mydomain.com/path",
    "ResultURL": "https://mydomain.com/path",
    "Occassion": "ChristmasPay"
}
```

> **`OriginatorConversationID` is required in v3** — a unique string per request
> that Safaricom uses to reject double disbursements. This did not exist in v1.
> `src/Apis/B2C.php` generates a UUID when the caller does not supply one.

| Name | Required | Notes |
|---|---|---|
| `OriginatorConversationID` | yes | Unique per request; also queryable via Transaction Status |
| `InitiatorName` | yes | API operator username — note the key is `InitiatorName`, not `Initiator` as in Balance/Reversal |
| `SecurityCredential` | yes | Encrypted initiator password |
| `CommandID` | yes | `SalaryPayment`, `BusinessPayment`, `PromotionPayment` |
| `Amount` | yes | Min KES 10, max KES 250,000 per transaction |
| `PartyA` | yes | B2C short code sending the money |
| `PartyB` | yes | Recipient MSISDN, 12 digits, no `+` |
| `Remarks` | yes | 2–100 characters |
| `QueueTimeOutURL` | yes | Timeout notification URL |
| `ResultURL` | yes | Where the result is posted |
| `Occassion` | no | 1–100 characters (Safaricom's spelling) |

`SalaryPayment` no longer supports sending to unregistered M-Pesa numbers.

## Response

```json
{
    "ConversationID": "AG_20240706_20106e9209f64bebd05b",
    "OriginatorConversationID": "600997_Test_32et3241ed8yu",
    "ResponseCode": "0",
    "ResponseDescription": "Accept the service request successfully."
}
```

## Result callback

```json
{
    "Result": {
        "ResultType": 0,
        "ResultCode": 0,
        "ResultDesc": "The service request is processed successfully.",
        "OriginatorConversationID": "53e3-4aa8-9fe0-8fb5e4092cdd3533373",
        "ConversationID": "AG_20240706_2010364430d9bbdaf872",
        "TransactionID": "SG632NMUAB",
        "ResultParameters": {
            "ResultParameter": [
                { "Key": "TransactionAmount", "Value": 10 },
                { "Key": "TransactionReceipt", "Value": "SG632NMUAB" },
                { "Key": "ReceiverPartyPublicName", "Value": "254705912645 - NICHOLAS JOHN SONGOK" },
                { "Key": "TransactionCompletedDateTime", "Value": "06.07.2024 22:48:52" },
                { "Key": "B2CUtilityAccountAvailableFunds", "Value": 8959269.6 },
                { "Key": "B2CWorkingAccountAvailableFunds", "Value": 1199371.0 },
                { "Key": "B2CRecipientIsRegisteredCustomer", "Value": "Y" },
                { "Key": "B2CChargesPaidAccountAvailableFunds", "Value": -1980.0 }
            ]
        },
        "ReferenceData": {
            "ReferenceItem": { "Key": "QueueTimeoutURL", "Value": "…" }
        }
    }
}
```

On failure `ResultParameters` is **absent** entirely — a DTO must not assume it exists:

```json
{
    "Result": {
        "ResultType": 0,
        "ResultCode": 2001,
        "ResultDesc": "The initiator information is invalid.",
        "OriginatorConversationID": "…",
        "ConversationID": "…",
        "TransactionID": "SG722NMVXQ",
        "ReferenceData": { "ReferenceItem": { "Key": "QueueTimeoutURL", "Value": "…" } }
    }
}
```

`ResultParameter` is an array of `{Key, Value}` pairs — worth flattening into a
keyed array in the callback DTO.

## Result codes

| Code | Meaning |
|---|---|
| 0 | Success |
| 1 | Insufficient balance in the Utility account |
| 2 | Below the minimum transaction amount |
| 3 | Above the maximum transaction amount |
| 4 | Would exceed the daily transfer limit (customer: KES 500,000) |
| 8 | Would exceed the maximum account balance (KES 500,000) |
| 11 | DebitParty invalid state — B2C account not active |
| 21 | Initiator lacks the ORG B2C API initiator role |
| 2001 | Invalid initiator information — wrong username, password, or encryption certificate |
| 2006 | Account rule declined — account not active |
| 2028 | PartyA short code has no B2C permission |
| 2040 | Recipient is not a registered customer |
| 8006 | Security credential is locked |
| `SFC_IC0003` | Operator does not exist — invalid phone number |

## Error response shape

```json
{
    "requestId": "1c5b-4ba8-815c-ac45c57a3db01469899",
    "errorCode": "500.002.1001",
    "errorMessage": "Duplicate OriginatorConversationID."
}
```

Already handled by `Exceptions\ApiRequestException::fromResponse()`.

## Operational notes worth documenting

- B2C debits the **Utility** account, not MMF/Working. "Insufficient balance"
  with money in the account is nearly always this.
- Moving MMF → Utility can be automated via B2B `BusinessTransferFromMMFToUtility`
  (requires whitelisting by apisupport@safaricom.co.ke).
- **B2C transactions cannot be reversed via the Reversal API** — manual only on
  the M-Pesa portal. Worth a warning in the Reversal docs page.
- The security credential can be reused across requests; it does not need
  regenerating each time.
- No passkey is needed for B2C — that is M-Pesa Express only.
