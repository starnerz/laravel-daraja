# Customer To Business (C2B)

Captured from the Daraja portal, 15 August 2026. Portal slug `CustomerToBusiness`.

## Endpoints — **v2**

| Operation | Sandbox | Production |
|---|---|---|
| Register URLs | `POST /mpesa/c2b/v2/registerurl` | same path on `api.safaricom.co.ke` |
| Simulate | `POST /mpesa/c2b/v2/simulate` | **not supported in production** |

> v1 vs v2 is the callback payload: v1 sent a SHA-256 hashed MSISDN,
> v2 sends a masked one (`2547 ***** 126`).

## Register URLs — request

```json
{
    "ShortCode": "600984",
    "ResponseType": "Completed",
    "ConfirmationURL": "https://your-domain/confirm",
    "ValidationURL": "https://your-domain/validate"
}
```

`ResponseType` is what M-Pesa does when your validation URL is unreachable —
only `Completed` or `Cancelled`, **sentence case, spelled exactly**.

## Simulate — request

```json
{
    "ShortCode": 600984,
    "CommandID": "CustomerPayBillOnline",
    "Amount": 1,
    "Msisdn": 254708374149,
    "BillRefNumber": "Account reference; null for buy goods"
}
```

## Response (both operations)

```json
{
    "OriginatorCoversationID": "6e86-45dd-91ac-fd5d4178ab523408729",
    "ResponseCode": "0",
    "ResponseDescription": "Success"
}
```

Note the key is `OriginatorCoversationID` — Safaricom's misspelling, handled in
`Data\Acknowledgement::fromArray()`.

## Confirmation / validation callback payload

```json
{
    "TransactionType": "Pay Bill",
    "TransID": "RKL51ZDR4F",
    "TransTime": "20231121121325",
    "TransAmount": "5.00",
    "BusinessShortCode": "600966",
    "BillRefNumber": "Sample Transaction",
    "InvoiceNumber": "",
    "OrgAccountBalance": "25.00",
    "ThirdPartyTransID": "",
    "MSISDN": "2547 ***** 126",
    "FirstName": "NICHOLAS",
    "MiddleName": "",
    "LastName": ""
}
```

`OrgAccountBalance` is blank on validation requests; on confirmation it is the
new balance after the payment.

## Validation response your app must return

Accept:

```json
{ "ResultCode": "0", "ResultDesc": "Accepted" }
```

Reject — any code except `0`:

| ResultCode | Meaning |
|---|---|
| `C2B00011` | Invalid MSISDN |
| `C2B00012` | Invalid Account Number |
| `C2B00013` | Invalid Amount |
| `C2B00014` | Invalid KYC Details |
| `C2B00015` | Invalid Short code |
| `C2B00016` | Other Error |

You have roughly **8 seconds** to respond. External validation is off by
default and must be enabled by emailing apisupport@safaricom.co.ke.

## URL requirements — affects our tunnel guidance

- Production URLs must be HTTPS; sandbox allows HTTP.
- URLs must not contain the words `M-PESA`, `Safaricom`, `mpesa`, `exe`, `exec`,
  `cmd`, `sql`, `query` or variants.
- **"Do not use public URL testers (e.g. ngrok, mockbin, requestbin), especially
  on production. These are usually blocked by the API."**

> This qualifies the plan's ngrok recommendation: fine for sandbox experimentation,
> but the docs warn it may be blocked, and it must never be used in production.

## Error codes

| HTTP | Code | Cause |
|---|---|---|
| 500 | `500.003.1001` | Internal server error, or URLs already registered |
| 400 | `400.003.01` | Invalid access token |
| 400 | `400.003.02` | Bad request — something missing |
| 500 | `500.003.03` | Quota violation (too many requests/second) |
| 500 | `500.003.02` | Spike arrest violation |
| 404 | `404.003.01` | Resource not found — wrong endpoint |
| 404 | `404.001.04` | Invalid authenticator header — all APIs are POST except Authorization |
| 400 | `400.002.05` | Invalid request payload |

## Operational notes

- Production registration is **one-time**. To change URLs, delete them under
  Self Services → URL Management, then re-register.
- Sandbox allows re-registering and overwriting freely.
