# Swalaf Yoghurt & Treats — website + admin

A PHP + MySQL rebuild of the two Claude Design mockups (`Swalaf Website.dc.html`
and `Swalaf Admin.dc.html`), built specifically to publish on ordinary shared
cPanel hosting with no build step, no Node process, and no dependencies
beyond what cPanel already ships with (PHP + MySQL).

## Stack

- **Frontend:** plain HTML/CSS/vanilla JS (`public/index.php`, `public/css/style.css`,
  `public/js/site.js`). No framework, no build step — matches the design 1:1.
- **Backend/admin:** PHP 8 + MySQL (PDO). Sessions for admin login, real CRUD
  for products/orders/content/SEO/settings.
- **Why this stack for cPanel:** every shared cPanel plan has first-class PHP
  + MySQL/phpMyAdmin support with zero extra setup (no Passenger config, no
  Node process to keep alive, no reverse proxy). Deploying is "upload files,
  import one SQL file, edit one config file."

## What's real vs. what's a placeholder right now

- **Products, sizes, orders, homepage sections, hero slider, banner, SEO
  fields, settings** — all backed by real MySQL tables and editable from
  `/admin`. Nothing here is mock data once you're on your own DB.
- **Analytics (Overview + Analytics tabs)** — real, computed from actual
  page views and outbound-link clicks your own visitors generate (see
  `public/js/site.js` beacons to `public/api/*.php`). "Best sellers" and
  "Latest orders" come from real order logs created when a visitor taps
  "Send order on WhatsApp" or the event quote button.
- **Messages / Inbox** — logged manually from the admin screen. WhatsApp
  and Instagram don't hand a website their DMs for free; wiring that up for
  real needs the WhatsApp Business Platform API / Instagram Graph API,
  which needs a Meta Business verification and paid API access — a
  deliberate follow-up, not something to fake.
- **"Search terms bringing visitors" (SEO tab)** — clearly labeled sample
  data. Real search-query data needs a connected Google Search Console
  property, which only you can authorize.
- **9 of the product/hero photos** (logo, hero, plain, coconut, greek, kids,
  zobo, zobo50, lifestyle) are placeholder graphics — see "Swapping in the
  real photos" below.

## Swapping in the real photos

The AI-generated product photos in the original design were too large for
this session's read tool to pull down in one piece, so `public/assets/`
currently has generated placeholders for: `logo.png`, `hero.jpg`, `plain.jpg`,
`coconut.jpg`, `greek.jpg`, `kids.jpg`, `zobo.jpg`, `zobo12.jpg`,
`lifestyle.jpg`. (`real1.jpg`, `real2.jpg`, `real3.jpg`, and `parfait.jpg`
are the real files.)

To fix this, just replace those 9 files in `public/assets/` with the real
ones — same filenames, any reasonable size (roughly square for product
shots, ~1400×780 for hero/lifestyle) — and redeploy. No code changes needed.
You can also just re-upload them straight from the admin's Website
content → Hero slider images uploader, or Products → Add product photo
upload, once real files are in hand.

## Local development

Needs PHP 8.1+ with `pdo_sqlite` (for local dev) or `pdo_mysql` (matches
prod). No Composer, no npm.

```bash
php sql/init-local-db.php        # creates sql/swalaf.sqlite with seed data
php -S 127.0.0.1:8000 -t public  # serve the site
```

Visit `http://127.0.0.1:8000/` for the site and
`http://127.0.0.1:8000/admin/login.php` for the admin
(default login: `romlah` / `change-this-password` — see
`includes/config.php`).

## Deploying to cPanel

1. **Create the database.** cPanel → *MySQL® Databases* → create a database
   and a user, add the user to the database with **All Privileges**. Note
   the full names (cPanel prefixes both with your account name, e.g.
   `cpanuser_swalaf` / `cpanuser_swalaf_admin`).
2. **Import the schema.** cPanel → *phpMyAdmin* → select your new database →
   *Import* → choose `sql/schema.sql` from this repo → Go. This creates all
   tables and seeds the products/settings/sections/sample orders shown in
   the design.
3. **Upload the files.** Either:
   - cPanel → *Git Version Control* → clone this repository directly (if
     your host supports it), or
   - cPanel → *File Manager* (or FTP) → upload everything.
4. **Point the domain's document root at `public/`.** cPanel → *Domains*
   (or *Addon Domains*) → edit the domain → set **Document Root** to the
   `public` folder inside where you uploaded the repo (e.g.
   `/home/cpanuser/swalaf/public`). This is what keeps `includes/`, `sql/`,
   and `.git/` outside the web-servable area — the safest setup. If your
   plan genuinely can't change the document root, the root `.htaccess` in
   this repo blocks direct access to `includes/` and `sql/` as a fallback,
   but changing the document root is strongly preferred.
5. **Edit `includes/config.php`:**
   ```php
   define('DB_DRIVER', 'mysql');
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'cpanuser_swalaf');
   define('DB_USER', 'cpanuser_swalaf');
   define('DB_PASS', 'the password you set in step 1');
   define('ADMIN_DEFAULT_PASSWORD', 'pick a real password before first login');
   define('SITE_URL', 'https://yourdomain.com');
   ```
   (Or set these as environment variables in cPanel → *MySQL® Databases* /
   *Setup Node.js App*-style env panels if your host exposes one — the
   config file reads `getenv()` first.)
6. **Visit `/admin/login.php`** and sign in with `ADMIN_DEFAULT_USERNAME` /
   `ADMIN_DEFAULT_PASSWORD` from `config.php` (the account is created
   automatically on first login attempt). Change the password by editing
   the `admin_users` row's `password_hash` (via a tiny one-off PHP script
   using `password_hash()`) — a password-change screen in the UI is a
   natural next addition.
7. **Force HTTPS** via cPanel → *SSL/TLS Status* → AutoSSL (free), then
   *Domains* → *Force HTTPS Redirect*.

That's the whole deploy. No `composer install`, no `npm run build`, no
background process to keep alive — cPanel's normal PHP handler runs
everything.

## Project layout

```
includes/         Config, DB connection, auth, helpers (outside the web root ideally)
public/           Everything cPanel serves — point the domain here
  index.php       The marketing site
  admin/          Login-protected dashboard (login.php, index.php, actions.php)
  api/            Tiny JSON endpoints the site's JS beacons to (pageviews/clicks/orders)
  assets/         Images (uploads/ is writable, for admin-uploaded photos)
  css/, js/       Stylesheets and vanilla JS
sql/
  schema.sql          MySQL schema + seed data — import this on cPanel
  schema.sqlite.sql   SQLite equivalent, local dev only
  init-local-db.php   Rebuilds the local SQLite DB from schema.sqlite.sql
```
