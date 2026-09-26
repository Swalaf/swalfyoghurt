# Admin guide

## First day checklist
1. **AI provider** — Admin → AI providers → Connect. OpenRouter is simplest (one key, many models). Add a second provider for automatic fallback.
2. **Branding** — name, logo, favicon, colour and support email.
3. **Email (SMTP)** — Settings → Email, then *Send test email*. Needed for password resets and verification codes. Turn on *Require email verification* once it works.
4. **Payments** — Plans & pricing → Stripe and/or Paystack → paste keys. For Stripe, add the webhook URL shown there (event `checkout.session.completed`) and paste its signing secret.
5. **Currency** — Settings → General → Currency (e.g. USD, EUR, NGN). Plans are charged in it.
6. **Legal** — Settings → Legal: add your company address and review the Terms and Privacy templates with a lawyer for your country.
7. **Security** — Settings → Security → *Run security scan*. Turn on two-step verification for your own account (Account & security).
8. **Cron** — make sure the cron line from the installer is running (System health shows “Scheduled tasks: Running”).

## Plans and credits
- 1 credit ≈ 1,000 AI tokens (change in `config/studio.php`). Admins are never charged.
- Paid plans run for the month/year bought; they don’t auto-renew. When a plan expires the user drops to the free plan. Credits refill monthly while a plan is active.
- You can give credits, change plans, suspend, delete or log in as any user from Users → Manage.

## Moderation
- Every published app shows a small “Report” link (Settings → Publishing to turn it off — not recommended).
- Reports arrive in **Abuse reports** (and by email to your support address). *Take down* replaces the app with a “removed” page; you can restore it later and optionally suspend the owner.
- Look-alike addresses (paypal, bank, login…) and reserved names are blocked; users are limited to N publishes per day (Settings → Publishing).

## Languages
English, Spanish, French and Portuguese ship with the platform. Visitors get their browser language; users can change it in Account & security; set the fallback in Settings → General. To add a language, copy `lang/es.json` to `lang/xx.json`, translate the values and add `'xx' => 'Name'` to `locales` in `config/studio.php`. The admin panel and Developer-mode screens are English-only.

## Data & privacy
- Users can download all their data and delete their account themselves (Account & security).
- Deleting a user (by them or by you) removes their projects and published files; payment records are kept without the user link for accounting.
- The platform sets only essential cookies and loads no third-party fonts, analytics or trackers.

## Storage
Published apps are stored under `storage/app/published` by default. To use S3 or an S3-compatible service, choose it in the installer or set in `.env`:

```
STUDIO_PUBLISH_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
AWS_ENDPOINT=            # for R2 / Wasabi / Spaces
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Apps published before the switch keep working only if their files are copied to the new disk, so republish them.
