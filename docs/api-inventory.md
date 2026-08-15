# Daraja API inventory

Source: <https://developer.safaricom.co.ke/dashboard/apis> (authenticated dashboard),
walked page by page on 15 August 2026.

**Scope agreed:** Payments + Disbursement + Experience tabs, excluding Tax
Remittance, Mobile Data Bundles and IoT SIM Management, and excluding the
Security-tab identity APIs (Swap, IMSI, Mobile Number Validation, Age on Network).

## Implemented

| API | Portal slug | Endpoint | Class |
|---|---|---|---|
| Authorization | `Authorization` | `oauth/v1/generate` | `Http\TokenRepository` |
| M-Pesa Express | `MpesaExpressSimulate` | `mpesa/stkpush/v1/processrequest` | `Apis\MpesaExpress::push()` |
| M-Pesa Express Query | `MpesaExpressQuery` | `mpesa/stkpushquery/v1/query` | `Apis\MpesaExpress::query()` |
| Customer To Business | `CustomerToBusiness` | `mpesa/c2b/**v2**/registerurl`, `/simulate` | `Apis\C2B` |
| Business To Customer | `BusinessToCustomer` | `mpesa/b2c/**v3**/paymentrequest` | `Apis\B2C` |
| Business To Pochi | `BusinessToPochi` | `mpesa/b2pochi/v1/paymentrequest` | `Apis\B2C::pochi()` |
| Business Pay Bill | `BusinessPayBill` | `mpesa/b2b/v1/paymentrequest` | `Apis\B2B::payBill()` |
| Business Buy Goods | `BusinessBuyGoods` | `mpesa/b2b/v1/paymentrequest` | `Apis\B2B::buyGoods()` |
| B2C Account Top Up | `B2CAccountTopUp` | `mpesa/b2b/v1/paymentrequest` | `Apis\B2B::accountTopUp()` |
| Account Balance | `AccountBalance` | `mpesa/accountbalance/v1/query` | `Apis\AccountBalance` |
| Transaction Status | `TransactionStatus` | `mpesa/transactionstatus/v1/query` | `Apis\TransactionStatus` |
| Reversals | `Reversal` | `mpesa/reversal/v1/request` | `Apis\Reversal` |
| Dynamic QR | `DynamicQRCode` | `mpesa/qrcode/v1/generate` | `Apis\DynamicQr` |
| M-Pesa Ratiba | `MpesaRatiba` | `standingorder/v1/createStandingOrderExternal` | `Apis\StandingOrder` |
| B2B Express CheckOut | `B2BExpressCheckout` | `v1/ussdpush/get-msisdn` | `Apis\B2BExpressCheckout` |
| Pull Transactions | `PullTransaction` | `pulltransactions/v1/register`, `/query` | `Apis\PullTransactions` |
| Lipa na Bonga | `LipaNaBonga` | `v1/lipa/na/bonga/calculate-points`, `/redeem-paybill` | `Apis\LipaNaBonga` |
| Bill Manager | `BillManager` | `v1/billmanager-invoice/*` (7 endpoints) | `Apis\BillManager` |

## Blocked

| API | Portal slug | Reason |
|---|---|---|
| B2B Hakikisha | `QueryOrgInfo` | **Safaricom's documentation page 404s.** Verified twice — every other API renders through the identical route. No endpoint or payload obtainable. |

## Excluded by scope

Tax Remittance (`TaxRemittance`), Mobile Data Bundles (`MobileCenter`), IoT SIM
Management (`IotSimManagement`), Swap (`Swap`), IMSI (`IMSI`), Mobile Number
Validation (`MobileNumberValidation`), Age on Network (`AgeOnNetwork`),
Getting Started (`GettingStarted`, a guide rather than an API).

## Defects the portal caught in code written before this sweep

| API | Defect | Impact if unfixed |
|---|---|---|
| C2B | Used `c2b/v1/*` | Wrong version — v2 masks the MSISDN, v1 hashes it |
| B2C | Used `b2c/v1/paymentrequest` and omitted `OriginatorConversationID` | v3 rejects requests without it |
| M-Pesa Express | Forced `PartyB = BusinessShortCode` | Buy Goods broken — `PartyB` is the till, the short code is the HO/store number |
| B2B | Derived identifier types from config; used `2` for Buy Goods | Both must be `4` on this API |
| B2B | No `Requester`; `AccountReference` uncapped | Missing field; 13-char limit unenforced |
| Transaction Status | Sent `Occassion` | This API spells it `Occasion`, one "s" |
| Transaction Status | Only supported `TransactionID` | `OriginalConversationID` is an equally valid key |
| Reversal | Derived `RecieverIdentifierType` from config | Must be the literal `11` |
| Reversal | Sent `Occassion` | Field does not exist on this API |

## Cross-cutting notes for the callback work

1. **`ResultParameter` and `ReferenceItem` are arrays on success but bare objects
   on failure.** A parser must normalise single objects into one-element arrays.
2. **Duplicate keys occur** — Transaction Status returns `DebitPartyName` twice
   (debit and credit sides). Flattening to a map loses one.
3. **Keys can be absent entirely** — Transaction Status returns
   `{"Key":"TransactionReason"}` with no `Value`.
4. **Balances are not JSON.** Either pipe-delimited
   (`Working Account|KES|346568.83|6186.83|340382.00|0.00`) or Java-map literals
   (`{Amount={CurrencyCode=KES, MinimumAmount=618683, BasicAmount=6186.83}}`).
   Account Balance packs several accounts into one string separated by `&`.
5. **`ResultCode` type varies** — numeric in B2C, string in Reversal (`"R000002"`),
   numeric in STK callbacks but string in STK query responses.
6. **Envelope conventions vary by API**: standard `ResponseCode`/`ResponseDescription`;
   Ratiba's `ResponseHeader`/`ResponseBody`; Bonga's `header`/`body`; Bill Manager's
   `rescode`/`resmsg`; B2B Express's `code`/`status`.

## Certificates — still outstanding

The bundled `certs/production.cer` **expired 21 March 2018** and `certs/sandbox.cer`
is absent. Both must be downloaded from the portal before `SecurityCredential`
can be used against either environment.

## Operational gotchas worth documenting

- The API user's password is **valid for 90 days**; the security credential must
  be regenerated after that.
- Safaricom's docs explicitly warn against **ngrok, mockbin and requestbin** for
  callback URLs — "usually blocked by the API", especially in production.
- Callback URLs must not contain the words `M-PESA`, `Safaricom`, `mpesa`, `exe`,
  `exec`, `cmd`, `sql` or `query`.
- C2B URL registration is **one-time in production**; changing it requires
  deletion via Self Services → URL Management.
- **B2C and B2Pochi payouts cannot be reversed** via the Reversal API. M-Pesa
  Express transactions can.
- B2C debits the **Utility** account, not MMF/Working — the usual cause of
  "insufficient balance" when the account visibly has money.
