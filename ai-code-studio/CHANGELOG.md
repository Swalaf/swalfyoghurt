# Changelog

## 1.3.0 — launch readiness

- **Billing:** Stripe and Paystack checkout (hosted pages), signed webhooks, monthly or yearly plans (yearly saves 20%), automatic expiry to the free plan, and a monthly credit refill. Adds a Plans & billing page for users.
- **Licensing:** licence activation (one production domain per key; local/staging free), weekly re-verification, and deactivating a licence to move it to another domain. Adds a vendor licence-server mode with key management, Envato purchase-code support, a storefront at `/buy`, and a releases/update channel.
- **Updates:** Check for updates in Admin, a signed download of the new release, and "Finish update" (backup + migrations). Adds `php artisan studio:package` to build release zips.
- **Privacy & legal:** editable Terms and Privacy pages with templates, required consent at sign-up, a cookie notice (essential cookies only), self-hosted fonts (no Google requests), and self-service data export and account deletion.
- **Languages:** English, Spanish, French and Portuguese for all public and customer-facing screens, picked automatically from the browser or chosen by the user.
- **Abuse:** a "Report" link on published apps, a public report form, an Admin → Abuse reports page with takedown/restore and owner suspension, publish rate limits and reserved or look-alike address blocking.
- **Storage:** published apps are stored on a configurable disk (local or S3/S3-compatible) instead of the database. The installer tests S3 credentials.
- **Security:** security headers (HSTS on https, nosniff, frame and referrer policies, permissions policy), and a secure session cookie on https installs.
- Licence changed to a commercial EULA (see `LICENSE`), with third-party notices.

## 1.2.0

- First release: installer, auth with 2FA, Simple and Developer studios, AI providers with fallback, multi-agent builds, publishing, and the admin panel.
