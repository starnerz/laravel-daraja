# Reversals

Captured from the Daraja portal, 15 August 2026. Portal slug `Reversal`.

Reverses **C2B** transactions only.

## Endpoint — v1 confirmed

```
POST https://sandbox.safaricom.co.ke/mpesa/reversal/v1/request
POST https://api.safaricom.co.ke/mpesa/reversal/v1/request
```

## Request

```json
{
    "Initiator": "apiop37",
    "SecurityCredential": "jUb+dOXJiBDui8FnruaFckZJQup3kmmCH5XJ4NY/Oo3KaUTmJbxUiVgzBjqdL533u5Q435MT2VJwr//1fuZvA===",
    "CommandID": "TransactionReversal",
    "TransactionID": "PDU91HIVIT",
    "Amount": "200",
    "ReceiverParty": "603021",
    "RecieverIdentifierType": "11",
    "ResultURL": "https://mydomain.com/reversal/result",
    "QueueTimeOutURL": "https://mydomain.com/reversal/queue",
    "Remarks": "Payment reversal"
}
```

> **`RecieverIdentifierType` must be the literal `"11"`** on this API — the
> portal states "Type of Organization (should be '11')". Every other API uses
> 1/2/4. `src/Apis/Reversal.php` previously derived it from `initiator.type`
> config and would have sent `4`.
>
> There is also **no `Occassion` field** on this API. The class was sending one;
> it has been removed to match the documented contract.

All fields are **required**, including `Remarks` (2–100 characters).

## Response

```json
{
    "OriginatorConversationID": "f1e2-4b95-a71d-b30d3cdbb7a7735297",
    "ConversationID": "AG_20210706_20106e9209f64bebd05b",
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
        "OriginatorConversationID": "dad6-4c34-8787-c8cb963a496d1268232",
        "ConversationID": "AG_20211114_201018edbbf9f1582eaa",
        "TransactionID": "SKE52PAWR9",
        "ResultParameters": {
            "ResultParameter": [
                { "Key": "DebitAccountBalance", "Value": "Utility Account|KES|7722179.62|7722179.62|0.00|0.00" },
                { "Key": "Amount", "Value": 1.0 },
                { "Key": "TransCompletedTime", "Value": 20211114132711 },
                { "Key": "OriginalTransactionID", "Value": "SKC82PACB8" },
                { "Key": "Charge", "Value": 0.0 },
                { "Key": "CreditPartyPublicName", "Value": "254705912645 - NICHOLAS JOHN SONGOK" },
                { "Key": "DebitPartyPublicName", "Value": "600992 - Safaricom Daraja 992" }
            ]
        },
        "ReferenceData": { "ReferenceItem": { "Key": "QueueTimeoutURL", "Value": "…" } }
    }
}
```

Note `TransactionID` in the callback is the **reversal's own** receipt;
`OriginalTransactionID` inside `ResultParameters` is the transaction that was
reversed.

## Result codes

| Code | Meaning |
|---|---|
| 0 | Success |
| `R000002` | The `OriginalTransactionID` is invalid or does not exist |
| `R000001` | The transaction has already been reversed |
| 1 | Insufficient balance to complete the reversal |
| 11 | DebitParty in an invalid state — account not active |
| 21 | Initiator lacks the `Org Reversals Initiator` role |
| 2001 | Invalid initiator information |
| 2006 | Account rule declined — account not active |
| 2028 | Short code not permitted for this request |
| 8006 | Security credential is locked |

`ResultCode` is a **string** here (`"R000002"`), unlike B2C where it's numeric.
Any comparison must not assume an integer.

On failure `TransactionID` is a placeholder (`SKE0000000`).

## Error codes

| errorCode | Meaning | HTTP |
|---|---|---|
| `404.001.03` | Invalid access token | 404 |
| `400.002.02` | Bad request — invalid field | 400 |
| `404.001.01` | Resource not found | 404 |
| `500.001.1001` | Internal server error | 500 |
| `500.003.02` | Spike arrest violation | 500 |
| `500.003.03` | Quota violation | 500 |

`Bad Request - Invalid RecieverIdentifierType` is called out explicitly in the
FAQs — caused by a wrong `Content-Type`, a misspelled parameter, or a value
other than `11`.
