# M-Pesa Ratiba (Standing Orders)

Captured from the Daraja portal, 15 August 2026. Portal slug `MpesaRatiba`.

Creates recurring M-Pesa standing orders. **Commercial API** — requires a signed
agreement before go-live.

## Endpoint

```
POST https://sandbox.safaricom.co.ke/standingorder/v1/createStandingOrderExternal
```

Note this sits outside the `/mpesa/` path prefix used by the other APIs.

## Request

```json
{
    "StandingOrderName": "erick",
    "ReceiverPartyIdentifierType": "2",
    "TransactionType": "Standing Order Pay Bill External Third Party",
    "BusinessShortCode": "300584",
    "PartyA": "254708374149",
    "Amount": "10",
    "StartDate": "20241221",
    "EndDate": "20241222",
    "Frequency": "4",
    "CustomStoId": "bae4ef32-3fb1-4998-988b-0063884a3d80",
    "AccountReference": "Test",
    "TransactionDesc": "Test",
    "CallBackURL": "https://mydomain.com/mpesa-ratiba/result/"
}
```

> **Two documentation inconsistencies to resolve by testing against the sandbox
> before shipping this API:**
> 1. The sample body says `StandingOrderNameName` (doubled), the parameter table
>    says `StandingOrderName`.
> 2. The sample body says `CustomStoId`, the parameter table says `CustomstdoId`.

| Name | Type | Notes |
|---|---|---|
| `StandingOrderName` | String | **Must be unique per customer** — a customer cannot have two standing orders with the same name (error 1050) |
| `StartDate` / `EndDate` | `yyyymmdd` | Execution window |
| `BusinessShortCode` | String | Short code receiving payment |
| `TransactionType` | String | `Standing Order Pay Bill External Third Party` (paybill) or `Standing Order Pay Merchant External Third Party` (till) |
| `CustomStoId` | String | Partner-generated UUID; echoed back in the callback as `ResponseRefId` / `RequestRefId` |
| `Amount` | String | Whole numbers only |
| `PartyA` | String | Payer's number, `2547XXXXXXXX` |
| `CallBackURL` | String | Secure URL for the result |
| `AccountReference` | Alphanumeric | Max 12 characters |
| `TransactionDesc` | String | Max 13 characters |
| `Frequency` | String | See table below |
| `ReceiverPartyIdentifierType` | String | `2` = till, `4` = paybill |

### Frequency values

| Value | Meaning | Value | Meaning |
|---|---|---|---|
| 1 | One Off | 6 | Bi-monthly |
| 2 | Daily | 7 | Quarterly |
| 3 | Weekly | 8 | Half Yearly |
| 4 | Bi-weekly | 9 | Yearly |
| 5 | Monthly | | |

## Response

Unlike the other APIs, this one nests everything under header/body objects and
uses **lowercase** key names:

```json
{
    "ResponseHeader": {
        "responseRefID": "4dd9b5d9-d738-42ba-9326-2cc99e966000",
        "responseCode": "200",
        "responseDescription": "Request accepted for processing",
        "ResultDesc": "The service request is processed successfully."
    },
    "ResponseBody": {
        "responseDescription": "Request accepted for processing",
        "responseCode": "200"
    }
}
```

`responseCode` here is an **HTTP-style code** (`200`, `401`, `500`), not the
`"0"` used elsewhere in Daraja.

## Callback

Success — note `responseCode` is `"0"` in the callback but `"200"` in the
synchronous response, and `responseBody.responseData` is an array of
`{name, value}` pairs rather than an object:

```json
{
    "responseHeader": {
        "responseRefID": "06aae68f-7d5a-4b44-a22d-8aa77126689b",
        "requestRefID": "06aae68f-7d5a-4b44-a22d-8aa77126689b",
        "responseCode": "0",
        "responseDescription": "Standing order created successfully"
    },
    "responseBody": {
        "responseData": [
            { "name": "standingOrderName", "value": "mpesa_Ratiba_test_Name" },
            { "name": "amount", "value": "500.00" },
            { "name": "issuePaymentReminderUntil", "value": "20280407" },
            { "name": "reminderScheduleId", "value": "2571168" },
            { "name": "firstPaymentReminderDate", "value": "20260807" },
            { "name": "status", "value": "ACTIVE" },
            { "name": "TransactionID", "value": "2571168" },
            { "name": "ResponseCode", "value": "0" },
            { "name": "Status", "value": "OKAY" },
            { "name": "Msisdn", "value": "*********867" }
        ]
    }
}
```

A DTO for this should flatten `responseData` into a keyed array — the wire
format is awkward to consume directly.

## Error codes

| Code | Cause |
|---|---|
| 1037 | STK prompt never reached the user (old SIM, phone offline), or user did not respond in time |
| 1025 | Error sending push request — partner system error |
| 1032 | Request cancelled by the user, or prompt timed out |
| 2001 | Invalid initiator information — wrong M-Pesa PIN |
| 1001 | Subscriber locked — an existing USSD session or concurrent transaction |
| 1050 | Customer already has a standing order with that name |
| 1051 | Bad request — one or more payload fields invalid |

## Charges

5% of transaction value, capped at KES 5 per standing order executed, exclusive
of VAT. C2B tariffs also apply on the credited paybill/till.
