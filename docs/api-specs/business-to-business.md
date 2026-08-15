# Business Pay Bill & Business Buy Goods (B2B)

Captured from the Daraja portal, 15 August 2026. Portal slugs `BusinessPayBill`
and `BusinessBuyGoods`. Both use the **same endpoint** and differ only by `CommandID`.

## Endpoint

```
POST https://sandbox.safaricom.co.ke/mpesa/b2b/v1/paymentrequest
```

| CommandID | Destination | Money moves to |
|---|---|---|
| `BusinessPayBill` | Pay bill number or pay bill store | recipient's **utility** account |
| `BusinessBuyGoods` | Till number, merchant store number or merchant HO | recipient's **merchant** account |

Both debit your **MMF/Working** account.

## Request

```json
{
    "Initiator": "API_Username",
    "SecurityCredential": "FKXl/KPzT8hFOnozI+unz7mXDgTRbrlrZ…",
    "CommandID": "BusinessPayBill",
    "SenderIdentifierType": "4",
    "RecieverIdentifierType": "4",
    "Amount": "239",
    "PartyA": "123456",
    "PartyB": "000000",
    "AccountReference": "353353",
    "Requester": "254700000000",
    "Remarks": "OK",
    "QueueTimeOutURL": "https://mydomain.com/b2b/queue/",
    "ResultURL": "https://mydomain.com/b2b/result/"
}
```

> **Both identifier types must be `"4"`.** The portal states "For this API, only
> 4 is allowed" for `SenderIdentifierType` and "This API supports type 4 only"
> for `RecieverIdentifierType` — on **both** Pay Bill and Buy Goods.
>
> `src/Apis/B2B.php` originally derived the sender type from
> `initiator.type` config and used type `2` (Till) for Buy Goods. Both were wrong
> and are now hard-coded to `4`.

| Field | Notes |
|---|---|
| `Initiator` | Key is `Initiator` here, **not** `InitiatorName` as in B2C |
| `PartyA` | Your short code — money deducted from here |
| `PartyB` | Recipient short code |
| `AccountReference` | **Up to 13 characters** |
| `Requester` | **Optional** — mobile number of the consumer you're paying for |
| `Remarks` | Up to 100 characters |
| `Occassion` | Listed in the parameter table but absent from the sample body; optional, up to 100 chars |

### Documentation errata

The Business Buy Goods parameter table says *"For this API use BusinessPayBill
only"* and *"needs Org Business Pay Bill API initiator role"* — copy-paste
errors. The sample request body correctly shows `BusinessBuyGoods`, and the
role table elsewhere lists "Business Buy Goods Org API initiator".

Also note the sample bodies use `"Command ID"` with a space; the parameter table
and every other API use `CommandID`. The spaced form is a typo.

## Response

```json
{
    "OriginatorConversationID": "5118-111210482-1",
    "ConversationID": "AG_20230420_2010759fd5662ef6d054",
    "ResponseCode": "0",
    "ResponseDescription": "Accept the service request successfully."
}
```

Correctly spelled `OriginatorConversationID` here, unlike C2B's
`OriginatorCoversationID`. `Data\Acknowledgement` accepts both.

## Result callback

```json
{
    "Result": {
        "ResultType": "0",
        "ResultCode": "0",
        "ResultDesc": "The service request is processed successfully",
        "OriginatorConversationID": "626f6ddf-ab37-4650-b882-b1de92ec9aa4",
        "ConversationID": "12345677dfdf89099B3",
        "TransactionID": "QKA81LK5CY",
        "ResultParameters": {
            "ResultParameter": [
                { "Key": "DebitAccountBalance", "Value": "{Amount={CurrencyCode=KES, MinimumAmount=618683, BasicAmount=6186.83}}" },
                { "Key": "Amount", "Value": "190.00" },
                { "Key": "DebitPartyAffectedAccountBalance", "Value": "Working Account|KES|346568.83|6186.83|340382.00|0.00" },
                { "Key": "TransCompletedTime", "Value": "20221110110717" },
                { "Key": "DebitPartyCharges", "Value": "" },
                { "Key": "ReceiverPartyPublicName", "Value": "000000 – Biller Company" },
                { "Key": "Currency", "Value": "KES" },
                { "Key": "InitiatorAccountCurrentBalance", "Value": "{Amount={CurrencyCode=KES, …}}" }
            ]
        },
        "ReferenceData": {
            "ReferenceItem": [
                { "Key": "BillReferenceNumber", "Value": "19008" },
                { "Key": "QueueTimeoutURL", "Value": "…" }
            ]
        }
    }
}
```

Two parsing hazards for the callback DTO:

1. **`ResultParameter` is an array on success but a bare object on failure**
   (`{"Key":"BOCompletedTime","Value":…}`). Same for `ReferenceItem`. A parser
   must normalise single objects into a one-element array.
2. Balance values are **not JSON** — they're either pipe-delimited
   (`Working Account|KES|346568.83|6186.83|340382.00|0.00`) or a Java-style
   map literal (`{Amount={CurrencyCode=KES, MinimumAmount=618683, BasicAmount=6186.83}}`).
   These need bespoke parsing if we want typed balances.

Pipe-delimited format is: `AccountName|Currency|CurrentBalance|LockedBalance|AvailableBalance|Reserved`.

## Failure

```json
{
    "Result": {
        "ResultType": 0,
        "ResultCode": 2001,
        "ResultDesc": "The initiator information is invalid.",
        "TransactionID": "OAK0000000",
        "ResultParameters": { "ResultParameter": { "Key": "BOCompletedTime", "Value": 20200120164825 } },
        "ReferenceData": { "ReferenceItem": { "Key": "QueueTimeoutURL", "Value": "…" } }
    }
}
```

`TransactionID` is a placeholder (`OAK0000000`) on certain failures — do not
treat it as a real receipt.
