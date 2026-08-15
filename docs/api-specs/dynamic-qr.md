# Dynamic QR

Captured from the Daraja portal, 15 August 2026. Portal slug `DynamicQRCode`.

Generates a QR code that M-Pesa app users scan to capture till number and amount,
then authorise payment.

## Endpoint

```
POST https://sandbox.safaricom.co.ke/mpesa/qrcode/v1/generate
```

## Request

```json
{
    "MerchantName": "TEST SUPERMARKET",
    "RefNo": "Invoice Test",
    "Amount": 1,
    "TrxCode": "BG",
    "CPI": "373132",
    "Size": "300"
}
```

| Name | Type | Notes |
|---|---|---|
| `MerchantName` | String | Company / M-Pesa merchant name |
| `RefNo` | String | Transaction reference |
| `Amount` | Numeric | Total for the sale |
| `TrxCode` | String | Transaction type — see below |
| `CPI` | String | Credit Party Identifier: mobile number, business number, agent till, paybill or merchant buy goods |
| `Size` | String | QR image size in pixels; always square |

### TrxCode values

| Code | Meaning |
|---|---|
| `BG` | Pay Merchant (Buy Goods) |
| `WA` | Withdraw Cash at Agent Till |
| `PB` | Paybill / business number |
| `SM` | Send Money (mobile number) |
| `SB` | Sent to Business — business number CPI in MSISDN format |

## Response

```json
{
    "ResponseCode": "AG_20191219_000043fdf61864fe9ff5",
    "RequestID": "16738-27456357-1",
    "ResponseDescription": "QR Code Successfully Generated.",
    "QRCode": "iVBORw0KGgoAAAANSUhEUgAAASwAAAEs…"
}
```

`QRCode` is a base64-encoded PNG. Note the portal's own sample shows
`ResponseDescription` unquoted — a documentation typo, not a payload shape.

## Implementation notes

- `ResponseCode` here is **not** the usual `"0"` success flag; it carries a
  transaction-type identifier string, so a `DynamicQrResponse` DTO should not
  reuse `Acknowledgement`.
- Worth exposing a helper that decodes `QRCode` into raw PNG bytes, since almost
  every consumer will want to render or store the image.
