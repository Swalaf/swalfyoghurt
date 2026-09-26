# Selling AI Code Studio

This project can be sold two ways; you can do both.

## 1. Your own store (direct)
Run one installation as your **licence server** (it can also be your own public platform):

```
STUDIO_LICENSE_SERVER=true
```

Then:
- **Payments:** connect Stripe/Paystack under Admin → Plans & pricing.
- **Prices:** Admin → Licences & releases → Storefront. Customers buy at `https://your-site/buy`, get the key on screen and by email, plus a download link to the latest release.
- **Releases:** on your dev machine run `composer install --no-dev --optimize-autoloader && php artisan studio:package`, then upload `build/ai-code-studio-X.Y.Z.zip` under *Publish a release* (or run `php artisan studio:package --publish --notes="…"` on the licence server itself).
- **Manage keys:** issue keys by hand, revoke, reset the bound domain, or extend support by 12 months.

Buyers' installations must know where your server is. Set it in the `.env.example` you ship:

```
STUDIO_LICENSE_URL=https://your-licence-server.example
```

## 2. A marketplace (Envato / CodeCanyon)
- Create an Envato personal token with *View your items’ sales history* and paste it under Storefront → Envato API token.
- Buyers enter their **purchase code** in the installer or Admin → License & updates. Your licence server verifies it with Envato once, then tracks it like any other key (Regular or Extended is read from the sale).
- Upload the same release zip to the marketplace.

## How the licence check behaves
- One production domain per key. `localhost`, `*.test`, `*.local`, `127.*`, `staging.*` and `dev.*` never use up the slot.
- Installs re-verify weekly. A revoked, invalid or already-in-use key shows a **warning banner to admins only** — the buyer’s platform and their customers are never switched off. This is deliberate: hard lockouts hurt honest buyers and can be removed from PHP source anyway. Your real protection is the EULA plus control over updates and support.
- Updates can only be downloaded with an active key whose support period hasn’t ended.

## Before your first sale
- Put your legal name in `LICENSE` and have the EULA reviewed.
- Set `STUDIO_LICENSE_URL` in `.env.example`, bump `version` in `config/studio.php`, update `CHANGELOG.md`, then package.
- Test the full buyer path yourself: buy → key email → download → install → activate → check for updates.
