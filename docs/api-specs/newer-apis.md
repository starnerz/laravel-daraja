# Newer Daraja APIs

Captured from the Daraja portal, 15 August 2026. These are the APIs absent from
v1 of this package.

---

## B2C Account Top Up

Portal slug `B2CAccountTopUp`. Loads funds into a B2C short code so it can disburse.

**Endpoint:** `POST mpesa/b2b/v1/paymentrequest` — the *same* endpoint as Business
Pay Bill / Buy Goods, distinguished only by `CommandID`.

```json
{
    "Initiator": "testapi",
    "SecurityCredential": "IAJVUHDGj0yDU3aop/WI9oSPhkW3DVlh7EAt3iRyymTZ…",
    "CommandID": "BusinessPayToBulk",
    "SenderIdentifierType": "4",
    "RecieverIdentifierType": "4",
    "Amount": "239",
    "PartyA": "600979",
    "PartyB": "600000",
    "AccountReference": "353353",
    "Requester": "254708374149",
    "Remarks": "OK",
    "QueueTimeOutURL": "https://mydomain/path/timeout",
    "ResultURL": "https://mydomain/path/result"
}
```

Requires the **Org Business Pay to Bulk API initiator** role — distinct from the
Pay Bill and Buy Goods roles. Money moves MMF/Working → recipient's utility account.

Implemented as `Daraja::b2b()->accountTopUp()`.

---

## Business To Pochi

Portal slug `BusinessToPochi`. Pays into a customer's Pochi la Biashara wallet.

**Endpoint:** `POST mpesa/b2pochi/v1/paymentrequest`

Payload is **byte-for-byte identical to B2C v3**, with `CommandID: BusinessPayToPochi`.
Same response, same result-callback shape, same result codes.

The recipient must actually have a Pochi la Biashara (micro-SME) wallet.
Like B2C, these cannot be reversed via the Reversal API.

Implemented as `Daraja::b2c()->pochi()`.

---

## B2B Express Checkout (USSD Push to Till)

Portal slug `B2BExpressCheckout`. A vendor prompts a fellow merchant to pay from
their till into the vendor's pay bill.

**Endpoint:** `POST v1/ussdpush/get-msisdn` — outside `/mpesa/`.

> This API breaks every Daraja convention: **camelCase** field names, no
> `SecurityCredential`, and a response shaped `{"code","status"}` rather than
> `ResponseCode`/`ResponseDescription`. It needs its own DTO.

```json
{
    "primaryShortCode": "000001",
    "receiverShortCode": "000002",
    "amount": "100",
    "paymentRef": "paymentRef",
    "callbackUrl": "http://..../result",
    "partnerName": "Vendor",
    "RequestRefID": "550e8400-e29b-41d4-a716-446655440000"
}
```

Note `RequestRefID` is the one PascalCase field in an otherwise camelCase body.

**Acknowledgement:** `{"code": "0", "status": "USSD Initiated Successfully"}`

**Callback** — cancelled and successful shapes differ in which keys appear:

```json
{ "resultCode": "4001", "resultDesc": "User cancelled transaction", "requestId": "…", "amount": "71.0", "paymentReference": "MAndbubry3hi" }
```

```json
{ "resultCode": "0", "resultDesc": "…processed successfully.", "amount": "71.0", "requestId": "…", "resultType": "0", "conversationID": "AG_20230426_2010434680d9f5a73766", "transactionId": "RDQ01NFT1Q", "status": "SUCCESS" }
```

The merchant is prompted for an **operator ID and operator PIN**, and only the
operator whose number is the short code's *Nominated Number* can authorise.

Implemented as `Daraja::b2bExpress()->push()`.

---

## Pull Transactions

Portal slug `PullTransaction`. Reconciliation tool returning C2B transactions
from the **last 48 hours**, including ones whose callbacks never arrived.

| Operation | Endpoint |
|---|---|
| Register (one-time) | `POST pulltransactions/v1/register` |
| Query | `POST pulltransactions/v1/query` |

Register: `{"ShortCode","RequestType":"Pull","NominatedNumber","CallBackURL"}`
→ `{"ResponseRefID","ResponseStatus":"1000","ShortCode","ResponseDescription"}`

| Code | Meaning |
|---|---|
| 1000 | Short code registered successfully |
| 1001 | Short code already registered |

Query: `{"ShortCode","StartDate","EndDate","OffSetValue"}` with dates as
`YYYY-MM-DD HH:MM:SS`. `OffSetValue` is a **row offset, not a page number**.

> **Response nests the list twice** — `Response` is an array *of arrays*:
>
> ```json
> { "ResponseRefID": "…", "ResponseCode": "1000", "ResponseMessage": "Success",
>   "Response": [ [ { "transactionId": "yzlyrEsRG1", "trxDate": "2020-08-05T10:13:00Z",
>   "msisdn": 722000000, "sender": "UAT2", "transactiontype": "c2b-pay-bill-debit",
>   "billreference": "37207636392", "amount": "168.00",
>   "organizationname": "Daraja Pull API Test" } ] ] }
> ```
>
> Transaction keys are **lowercase**, unlike the rest of Daraja. `PullTransactions`
> flattens one level and maps to `PulledTransaction`.

Code `1001` on query means no transactions in the period; the body is `"[[]]"`.
The doc contradicts itself on method — "Good to Know" says GET for query, but the
query section documents a request body. POST is implemented; verify in sandbox.

Implemented as `Daraja::pull()->register()` and `->query()`.

---

## Lipa na Bonga

Portal slug `LipaNaBonga`. Accept Safaricom loyalty points as payment.
Points redeem at **KES 0.20 each**.

| Operation | Endpoint |
|---|---|
| Calculate points | `POST v1/lipa/na/bonga/calculate-points` |
| Redeem | `POST v1/lipa/na/bonga/redeem-paybill` |

Calculate: `{"points":"40"}` → header/body envelope with `{"amount":"8","points":"40","rate":"0.2"}`

Redeem: `{"msisdn","amount","bongaPoints","conversionRate","shortCode","accountNumber"}`
→ same envelope with `body: null`

```json
{
    "header": {
        "requestRefId": "55b2b8bd-0be4-4430-b0dc-792efccdc690",
        "responseCode": 200,
        "responseMessage": "Success",
        "customerMessage": "Request executed successfully.",
        "timestamp": "2025-02-24T12:29:05.484864516"
    },
    "body": { "amount": "8", "points": "40", "rate": "0.2" }
}
```

> `responseCode` is an **integer** here (200), while the documented Result Codes
> table lists `6000` for success — the portal contradicts itself. `BongaResponse::successful()`
> accepts both.
>
> The Redeem parameter table also lists `Username`/`Password`/`Request id` header
> parameters that appear nowhere in the JSON body. Verify against the sandbox.

**There is no callback URL on this API** — on success M-Pesa transfers funds to
your short code and the result arrives on your **C2B confirmation URL**.

| Code | Meaning |
|---|---|
| 6000 / 6001 | Success / Fail |
| 6004–6011 | Server, credential, payload, CBS/STK/broker/database errors |
| 1037 | DS timeout — customer has no STK applet |
| 1031 | STK timeout — PIN not entered in time |
| 2001 | Wrong PIN |
| 17 | Reversal failed — account balance limit |

Implemented as `Daraja::bonga()->calculatePoints()` and `->redeem()`.

---

## Bill Manager

Portal slug `BillManager`. Seven endpoints for invoicing and reconciliation.

| Operation | Endpoint |
|---|---|
| Opt in | `POST v1/billmanager-invoice/optin` |
| Update opt-in | `POST v1/billmanager-invoice/change-optin-details` |
| Single invoice | `POST v1/billmanager-invoice/single-invoicing` |
| Bulk invoice | `POST v1/billmanager-invoice/bulk-invoicing` |
| Reconciliation | `POST v1/billmanager-invoice/reconciliation` |
| Cancel one | `POST v1/billmanager-invoice/cancel-single-invoice` |
| Cancel many | `POST v1/billmanager-invoice/cancel-bulk-invoices` |

**Opt in first** — it whitelists the short code and returns an `app_key`:

```json
{ "app_key": "AG_2376487236_126732989KJ", "resmsg": "Success", "rescode": "200" }
```

> **Store that app key.** Bulk invoicing sends it as an `appKey` **header** and
> Safaricom does not return it again. It is configured as
> `laravel-daraja.bill_manager.app_key`.

Invoice payload:

```json
{
    "externalReference": "#9932340",
    "billedFullName": "John Doe",
    "billedPhoneNumber": "07XXXXXXXX",
    "billedPeriod": "August 2021",
    "invoiceName": "Jentrys",
    "dueDate": "2021-10-12",
    "accountReference": "1ASD678H",
    "amount": "800",
    "invoiceItems": [
        { "itemName": "food", "amount": "700" },
        { "itemName": "water", "amount": "100" }
    ]
}
```

Bulk takes a **top-level array** of those objects, max **1000** per call.

Response envelope throughout: `{"Status_Message"?, "resmsg", "rescode", "errors"?}`
with `rescode` `"200"` for success.

Operational notes:

- Payment reminders fire 7 days before, 3 days before, and on the due date.
  `sendReminders` toggles them (`0`/`1`).
- Bill Manager retries your payment callback **5 times** before giving up.
- **Partially or fully paid invoices cannot be cancelled** — you get `rescode` `409`
  with `"partially or fully paid invoices cannot be cancelled."`
- Reconciliation acknowledgement triggers the customer's e-receipt SMS.

Implemented as `Daraja::billManager()->…`.

---

## B2B Hakikisha — NOT captured

Portal slug `QueryOrgInfo`. Card description: "enables partners to look up the
name and applicable tariff of an M-Pesa organization account."

**Safaricom's own documentation page for this API is broken.** Navigating to
`?api=QueryOrgInfo` renders the portal's "Go back / Go Home" 404 view. Verified
twice; every other API renders correctly through the identical route.

No endpoint, payload or response shape could be obtained, so it is **not
implemented**. Options: ask apisupport@safaricom.co.ke, or check whether the
downloadable Postman collection includes it.
