# Transaction Status

Captured from the Daraja portal, 15 August 2026. Portal slug `TransactionStatus`.

Works for **C2B, B2B, B2C, IMT and Reversal** transactions. Intended as a
secondary reconciliation mechanism when a callback never arrives.

## Endpoint — v1 confirmed

```
POST https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query
POST https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query
```

## Request

```json
{
    "Initiator": "testapiuser",
    "SecurityCredential": "ClONZiMYBpc65lmpJ7nvnrDmUe0WvHvA5QbOsPjEo92B6IGFwDdvdeJIFL0kgwsEKWu6SQKG4ZZUxjC",
    "CommandID": "TransactionStatusQuery",
    "TransactionID": "NEF61H8J60",
    "OriginalConversationID": "7071-4170-a0e5-8345632bad442144258",
    "PartyA": "600782",
    "IdentifierType": "4",
    "ResultURL": "http://myservice:8080/transactionstatus/result",
    "QueueTimeOutURL": "http://myservice:8080/timeout",
    "Remarks": "OK",
    "Occasion": "OK"
}
```

> **Two corrections this forced in `src/Apis/TransactionStatus.php`:**
> 1. This API spells it **`Occasion`** with one "s" — B2C and Reversal use
>    `Occassion` with two. The class previously sent `Occassion` here.
> 2. You may query by **either** `TransactionID` (the M-Pesa receipt) **or**
>    `OriginalConversationID`. Only the former was supported; there is now a
>    `queryByConversationId()` method.

`PartyA` may be a short code (6–9 digits) or an MSISDN (12 digits).
`IdentifierType` `4` = organisation short code.

## Response

```json
{
    "OriginatorConversationID": "1236-7134259-1",
    "ConversationID": "AG_20210709_1234409f86436c583e3f",
    "ResponseCode": "0",
    "ResponseDescription": "Accept the service request successfully."
}
```

## Result callback

```json
{
    "Result": {
        "ConversationID": "AG_20180223_0000493344ae97d86f75",
        "OriginatorConversationID": "3213-416199-2",
        "ResultCode": 0,
        "ResultDesc": "The service request is processed successfully.",
        "ResultType": 0,
        "TransactionID": "MBN0000000",
        "ResultParameters": {
            "ResultParameter": [
                { "Key": "DebitPartyName", "Value": "600310 - Safaricom333" },
                { "Key": "DebitPartyName", "Value": "254708374149 - John Doe" },
                { "Key": "OriginatorConversationID", "Value": "3211-416020-3" },
                { "Key": "InitiatedTime", "Value": "20180223054112" },
                { "Key": "DebitAccountType", "Value": "Utility Account" },
                { "Key": "DebitPartyCharges", "Value": "Fee For B2C Payment|KES|22.40" },
                { "Key": "TransactionReason" },
                { "Key": "ReasonType", "Value": "Business Payment to Customer via API" },
                { "Key": "TransactionStatus", "Value": "Completed" },
                { "Key": "FinalisedTime", "Value": "20180223054112" },
                { "Key": "Amount", "Value": "300" },
                { "Key": "ConversationID", "Value": "AG_20180223_000041b09c22e613d6c9" },
                { "Key": "ReceiptNo", "Value": "MBN31H462N" }
            ]
        },
        "ReferenceData": { "ReferenceItem": { "Key": "Occasion" } }
    }
}
```

Three parsing hazards, all present in this single sample:

1. **`DebitPartyName` appears twice** with different values (credit and debit
   sides). Flattening `ResultParameter` into a keyed map silently loses one —
   the parser must keep duplicates or namespace them.
2. **`TransactionReason` has no `Value` key at all.** Accessing `['Value']`
   unguarded will throw.
3. `ReferenceItem` is a bare object here, an array elsewhere.

## Transaction statuses

| Stage | Values |
|---|---|
| Initial | `Initiated` — pending revalidation |
| Intermediate | `Authorized` / `Pending Authorized` |
| Final | `Cancelled`, `Declined`, `Completed`, `Expired` |

## Result codes

| Code | Meaning |
|---|---|
| 0 | Success |
| `SFC_IC0003` | The operator does not exist |

## Security credential algorithm (confirmed)

1. Write the unencrypted password into a byte array.
2. Encrypt with the M-Pesa public certificate using **RSA with PKCS #1.5
   padding — not OAEP**.
3. Base64-encode the result.

This matches `Support\SecurityCredential`, which uses `OPENSSL_PKCS1_PADDING`.

> The API user's password is **valid for 90 days**, after which the security
> credential must be regenerated. Worth documenting as an operational gotcha.
