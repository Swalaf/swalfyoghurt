# AI Code Studio (Laravel)

A self-hosted, white-label "describe it → AI builds it" platform, built with
Laravel 13 from the Claude Design project (`Landing Page`, `Auth`, `Installer`,
`AI Code Studio` and `Admin` screens).

People describe an app in plain language. The platform plans it (features,
pages, data, API, stack), AI agents write the code, check it, and publish it
to a live URL. Users approve every change, and admins run the whole thing
under their own brand with their own AI keys and pricing.

## What's in the box

| Screen (design file) | Route(s) | What it does |
|---|---|---|
| Landing Page | `/` | Marketing page. Headline, pricing (from the `plans` table) and FAQ. The idea box carries into sign-up. |
| Installer | `/install` | 11-step web installer: a real server check, database connection test, `.env` writing, migrations, seeding, admin account, AI key test, cron heartbeat check and license. It locks itself when done. |
| Auth | `/login`, `/register`, `/forgot-password`, `/reset-password/*`, `/two-factor`, `/verify-email`, `/onboarding`, 404/403/503 pages | Sign-in, sign-up with password strength, reset by email, 6-digit email verification, TOTP two-step verification (Google Authenticator etc.), and the "how much do you code?" onboarding that picks Simple or Developer mode. |
| AI Code Studio (Simple) | `/studio`, `/studio/new`, `/studio/projects/{slug}`, `…/deploy` | "My apps", the create wizard (kind → idea → plan with tickable features), the Builder (chat, approve/undo, live preview with Computer/Tablet/Phone and **click-to-edit**), and Publish. |
| AI Code Studio (Developer) | `/studio`, `…/plan`, `…/code`, `…/agents`, `…/deploy`, `/studio/providers`, `/studio/brand` | Dashboard, spec review with stack picker, the code workspace (file tree, tabs, highlighted editor with ⌘S, Problems/Output/Preview/History panels, and an AI agent panel with modes), the multi-agent run view, deployments with rollback, provider routing and white-label settings. ⌘K opens the command palette. |
| Admin | `/admin/*` | Setup checklist, KPIs, and a revenue vs AI cost chart. Users (search, filter, CSV export, add credits, change plan, send reset, log in as the user, suspend, delete), plans and payment-gateway keys, AI providers (connect and test, routing), branding with live preview, settings (sign-up, email/SMTP, storage, security scan, maintenance mode), system health, activity logs, and license/updates (backup + migrate). |

## How the AI part works

* **Providers:** OpenRouter, Anthropic, Google Gemini, Groq, OpenAI, or any
  OpenAI-compatible endpoint (Ollama, vLLM, etc.). Keys are stored
  encrypted. The client lives in `app/Services/Ai/AiClient.php`. It orders
  providers by the routing strategy (Balanced, Best quality, Cheapest,
  Fastest, …) and falls back to the next provider when one fails.
* **Credits:** every AI call records token usage (`ai_usage`) and deducts
  credits from the user: 1 credit per 1,000 tokens, configurable in
  `config/studio.php`. Admins aren't charged. Plans set the monthly
  allowance.
* **Change sets:** the agent replies with full file contents. These become a
  pending *change set* with line diff counts. The user approves (files
  written), discards, or later undoes it (previous contents restored).
  Nothing changes without approval.
* **Builds** (`app/Jobs/BuildProject.php`) run the Architect → Developer →
  QA → Security agents, then a Deploy agent that waits for the user to
  publish.
* **No AI key yet?** Everything still works end to end. Specs come from
  built-in blueprints, the Developer agent writes a starter app from the
  spec, and chat explains that an admin needs to connect a provider.

## Previews and publishing

* Apps are previewed and published as static front ends (`index.html` +
  CSS/JS). Backend source the AI writes (Laravel, Node, …) is kept in the
  project and included in the zip download, but it isn't executed.
* User-generated HTML is always served with
  `Content-Security-Policy: sandbox …` (opaque origin). It can't read the
  platform's cookies or call its routes.
* The live preview uses a per-project token URL (`/preview/{slug}/{token}`),
  because a sandboxed iframe can't send the session cookie.
* Published apps are served from `/p/{name}`. Set `STUDIO_PUBLISH_DOMAIN` (plus
  wildcard DNS and a vhost) to use `{name}.yourdomain.com` instead.

## Launch features (v1.3)

* **Billing:** Stripe and Paystack hosted checkout for monthly or yearly plans, verified on return and by signed webhooks. Plans expire back to free, credits refill monthly, and users get a Plans & billing page.
* **Licensing & distribution:** commercial EULA (`LICENSE`), third-party notices, licence activation per domain, update checks and downloads, and `php artisan studio:package` for release zips. Set `STUDIO_LICENSE_SERVER=true` on the seller's own install to run the licence server, `/buy` storefront and Envato purchase-code support. See `docs/SELLING.md`.
* **Privacy & legal:** Terms and Privacy pages (editable templates), consent at sign-up, a cookie notice (essential cookies only), self-hosted fonts, and self-service data export and account deletion.
* **Languages:** English, Spanish, French and Portuguese for all public and customer-facing screens.
* **Abuse handling:** a Report link on published apps, a report form, admin takedown/restore with optional suspension, per-day publish limits and blocked look-alike addresses.
* **Storage:** published apps go to local disk or S3/S3-compatible storage.
* **Security headers:** HSTS on https, nosniff, frame, referrer and permissions policies.

## Known limits

* **Payments are one-off per period.** There are no automatic recurring subscriptions; users renew from Plans & billing. PayPal isn't supported.
* **Social sign-in** (GitHub/Google) isn't built.
* **Translations:** the admin panel, Developer-mode tools and some server messages (e.g. form validation errors) are English-only.
* **Licensing is a soft check.** An invalid licence shows admins a warning and blocks update downloads; it never disables a site. PHP source can always be edited, so the EULA is the real protection.
* **Security:** the code has automated tests for auth, access control, signatures and sandboxing, but no independent penetration test. Get one before handling significant revenue.
* **Legal templates** are starting points, not legal advice.
* **Published apps** are static front ends. Backend code the AI writes is downloadable, not executed.
* **Terminal/Git:** the design's terminal and Git panels became Output (agent logs) and History (change sets).

## Install

Requirements: PHP 8.3+, one of MySQL/MariaDB/PostgreSQL/SQLite, and the pdo,
mbstring, openssl, curl, zip and gd extensions. Composer is needed once to
build `vendor/`.

### cPanel / shared hosting

1. Use a release zip (`php artisan studio:package` after `composer install --no-dev --optimize-autoloader`), or run that composer command yourself.
2. Upload the folder, and point the domain's document root at `public/`.
   If you can't change the document root, upload `public/`'s contents into
   `public_html` and fix the two paths in `public_html/index.php`.
3. Make `storage/` and `bootstrap/cache/` writable (775). The installer
   creates `.env` from `.env.example` automatically.
4. Create an empty MySQL database in cPanel, then open
   `https://your-domain/install` and follow the wizard.
5. Add the cron job it shows (once per minute):
   `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`.
   This drains the queue (builds, deploys) and sends the heartbeat that
   System health checks.

### VPS / local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve                      # then open http://localhost:8000/install
```

`php artisan serve` restarts itself whenever `.env` changes, and the installer
writes `.env` at the very end. If the last step shows "Failed to fetch", just
reload: the install finished. Apache, nginx and `php -S` don't do this.

For a quick local run pick **SQLite** in the database step and **Sync** in the
queue step (jobs then run inside the request, so no worker or cron is needed).

## Tests

```bash
php artisan test
```

46 tests cover billing (Stripe/Paystack checkout and webhook signatures), licensing (activation, domain binding, Envato, update downloads), abuse takedowns, storage, legal consent, data export/deletion, languages, security headers, sign-up, email codes, 2FA, password reset, the plan → build →
preview → publish → rollback flow, project isolation, path sanitising, plan
limits, AI change sets with approve/undo, provider fallback, credit charging,
admin actions, maintenance mode and encrypted secrets. AI calls are faked
with `Http::fake`.

## Code map

```
app/Http/Controllers/InstallController.php   installer
app/Http/Controllers/Auth/*                  login, 2FA, sign-up, verify, reset, onboarding
app/Http/Controllers/Studio/*                dashboard, projects, builder, workspace, agents, deploy
app/Http/Controllers/Admin/*                 admin panel
app/Services/Ai/AiClient.php                 provider drivers, routing, fallback, usage
app/Services/Studio/ProjectAgent.php         specs, code generation, change sets
app/Jobs/BuildProject.php, DeployProject.php background work
app/Services/Billing/*                       Stripe + Paystack checkout, webhooks, plan expiry/credit refill
app/Services/Licensing/*                     licence server (vendor) and licence client (buyers)
app/Console/Commands/PackageRelease.php      php artisan studio:package
app/Support/*                                settings, installer checks, TOTP, .env writer, line diff, legal templates
lang/*.json                                  es, fr, pt translations
docs/                                        ADMIN-GUIDE, UPGRADING, SELLING
resources/views/*                            Blade views (ported from the .dc.html designs)
public/css/studio.css, public/js/*           styles + vendored Alpine.js / QRCode.js (no build step)
```
