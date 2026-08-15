# M-Pesa Express (STK Push)

Captured from the Daraja portal, 15 August 2026. Portal slugs
`MpesaExpressSimulate` (push) and `MpesaExpressQuery` (status).

## Endpoints — **v1 confirmed correct**

| Environment | URL |
|---|---|
| Sandbox | `POST https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest` |
| Production | `POST https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest` |

## Request

```json
{
    "BusinessShortCode": 174379,
    "Password": "MTc0Mzc5YmZiMjc5…",
    "Timestamp": "20210628092408",
    "TransactionType": "CustomerPayBillOnline",
    "Amount": "1",
    "PartyA": "254722000000",
    "PartyB": "174379",
    "PhoneNumber": "254722111111",
    "CallBackURL": "https://mydomain.com/path",
    "AccountReference": "accountref",
    "TransactionDesc": "txndesc"
}
```

`Password` = `base64(Shortcode + Passkey + Timestamp)`, `Timestamp` = `YYYYMMDDHHmmss`.
**Every field except `TransactionDesc` is mandatory.**

| Field | Limit |
|---|---|
| `AccountReference` | max 12 characters — shown to the customer in the prompt |
| `TransactionDesc` | max 13 characters |
| `Amount` | min KES 1, max KES 250,000 per transaction |

### Till numbers — `PartyB` is not the short code

> For Buy Goods, the portal is explicit:
> - `BusinessShortCode`: the short code used on Go Live (**HO / store number**)
> - `PartyB`: the **Till Number**
> - `TransactionType`: `CustomerBuyGoodsOnline`
>
> These are two different numbers. `src/Apis/MpesaExpress.php` originally forced
> `PartyB = BusinessShortCode`, which is only correct for Pay Bill. It now takes
> an optional `$partyB`.

## Response

```json
{
    "MerchantRequestID": "2654-4b64-97ff-b827b542881d3130",
    "CheckoutRequestID": "ws_CO_1007202409152617172396192",
    "ResponseCode": "0",
    "ResponseDescription": "Success. Request accepted for processing",
    "CustomerMessage": "Success. Request accepted for processing"
}
```

## Callback

Successful — note `CallbackMetadata.Item` is an array of `{Name, Value}` pairs:

```json
{
    "Body": {
        "stkCallback": {
            "MerchantRequestID": "29115-34620561-1",
            "CheckoutRequestID": "ws_CO_191220191020363925",
            "ResultCode": 0,
            "ResultDesc": "The service request is processed successfully.",
            "CallbackMetadata": {
                "Item": [
                    { "Name": "Amount", "Value": 1.0 },
                    { "Name": "MpesaReceiptNumber", "Value": "NLJ7RT61SV" },
                    { "Name": "TransactionDate", "Value": 20191219102115 },
                    { "Name": "PhoneNumber", "Value": 254708374149 }
                ]
            }
        }
    }
}
```

Unsuccessful — `CallbackMetadata` is **absent**:

```json
{
    "Body": {
        "stkCallback": {
            "MerchantRequestID": "f1e2-4b95-a71d-b30d3cdbb7a7942864",
            "CheckoutRequestID": "ws_CO_21072024125243250722943992",
            "ResultCode": 1032,
            "ResultDesc": "Request cancelled by user"
        }
    }
}
```

`ResultCode` is **numeric** in the callback but a **string** in the query
response — a callback DTO must cast rather than compare strictly.

A `Balance` item may also appear in `CallbackMetadata` (balance of the `PartyB`
short code).

## Error codes

| errorCode | Meaning | HTTP |
|---|---|---|
| `400.002.02` | Bad request — invalid field. Often a missing `Content-Type: application/json` | 400 |
| `404.001.03` | Invalid access token | 404 |
| `404.001.01` | Resource not found — wrong endpoint | 404 |
| `405.001` | Method not allowed — must be POST | 405 |
| `500.001.1001` | Merchant does not exist / wrong credentials / subscriber locked | 500 |
| `500.003.02` | System busy, or spike arrest violation | 500 |
| `500.003.03` | Quota violation | 500 |
| `500.003.1001` | Internal server error | 500 |

"Wrong credentials" specifically means the `Password` encoding does not match —
either the passkey is wrong, or the `BusinessShortCode`/`Timestamp` used to build
the password differ from those in the body.

"Unable to lock subscriber" means a conflicting session; **wait at least 1 minute**
between pushes to the same number.

## Notes

- Unlike B2C, **M-Pesa Express transactions can be reversed** via the Reversal API.
- The passkey is sandbox-provided; in production it is emailed after go-live.
