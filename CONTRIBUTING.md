# Contributing

## Getting set up

```bash
git clone https://github.com/starnerz/laravel-daraja.git
cd laravel-daraja
composer install
```

Requires PHP 8.3 or 8.4.

## Before opening a pull request

```bash
composer test        # Pest
composer analyse     # Larastan, level 6
composer format      # Pint
```

All three run in CI across PHP 8.3/8.4 and Laravel 12/13, and coverage must stay
at or above 90%.

## Tests

Never call the real API in a test. The package is built on Laravel's HTTP
client, so `Http::fake()` intercepts everything.

Use the `fakeDaraja()` helper in `tests/Pest.php`, listing overrides first —
`Http::fake()` merges successive calls and the first matching stub wins, so a
later fake cannot replace an earlier one.

```php
it('does the thing', function () {
    fakeDaraja(['*/mpesa/stkpush/*' => Http::response(payload('stk-push'))]);

    Daraja::stk()->push('0712345678', 100, 'INV-1');

    Http::assertSent(fn ($request) => $request->data()['Amount'] === '100');
});
```

Fixtures in `tests/Fixtures/` are **real payloads captured from the Daraja
portal**. Add new ones the same way rather than inventing shapes — Safaricom's
responses are inconsistent in ways that are hard to guess.

## Changing an API

`docs/api-specs/` records what the portal actually documents for each endpoint,
including quirks worth knowing:

- `ResultParameter` is an array on success and a bare object on failure
- Keys repeat — Transaction Status returns `DebitPartyName` twice
- A pair may carry no `Value` at all
- `Occasion` has one "s" on Transaction Status and two on B2C
- `RecieverIdentifierType` is misspelled by Safaricom everywhere

If you find something the specs do not cover, update the spec in the same pull
request. Please cite the portal page you took it from.

## Commit messages

[Conventional Commits](https://www.conventionalcommits.org/). The changelog is
assembled from them, so `!` on a breaking change matters.

```
feat: add support for the Foo API
fix: send Occasion with one s on transaction status
refactor!: rename the MpesaApi facade to Daraja
```

## Certificates

`certs/sandbox.cer` and `certs/production.cer` are shipped with the package and
selected automatically from `mode`. When Safaricom rotates one, replace the file
here and note it in the changelog — consumers should never have to fetch a
certificate themselves.

## Documentation

User-facing changes need a matching pull request against
[starnerz/daraja-docs](https://github.com/starnerz/daraja-docs).

## Reporting bugs

Include your PHP and Laravel versions, whether you are on sandbox or live, the
call you made, and the full exception including `errorCode` and `requestId` if
present.

**Never paste consumer secrets, initiator passwords or security credentials into
an issue.**

## Security

Report vulnerabilities privately to stanleykimathi@gmail.com. See
[SECURITY.md](SECURITY.md).
