<?php
/**
 * Site configuration.
 *
 * BEFORE GOING LIVE ON CPANEL:
 *   1. Set DB_DRIVER to 'mysql' and fill in the DB_* values from
 *      cPanel > MySQL Databases (database name, username, password are all
 *      usually prefixed with your cPanel username, e.g. "cpanuser_swalaf").
 *   2. Change ADMIN_DEFAULT_PASSWORD below, then delete it from this file
 *      once you've logged in and changed the password from the Admin UI
 *      (or just leave it — it is only used the very first time the
 *      admin_users table is seeded).
 *   3. Change the session cookie name if you host more than one app on the
 *      same domain.
 */

// 'mysql' for production (cPanel), 'sqlite' for local development/testing.
define('DB_DRIVER', getenv('SWALAF_DB_DRIVER') ?: 'sqlite');

// --- MySQL settings (used when DB_DRIVER === 'mysql') ---
define('DB_HOST', getenv('SWALAF_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('SWALAF_DB_NAME') ?: 'swalaf');
define('DB_USER', getenv('SWALAF_DB_USER') ?: 'swalaf');
define('DB_PASS', getenv('SWALAF_DB_PASS') ?: '');

// --- SQLite settings (used when DB_DRIVER === 'sqlite', local dev only) ---
define('SQLITE_PATH', getenv('SWALAF_SQLITE_PATH') ?: (__DIR__ . '/../sql/swalaf.sqlite'));

// Seeded only the very first time the admin_users table is empty.
define('ADMIN_DEFAULT_USERNAME', 'romlah');
define('ADMIN_DEFAULT_PASSWORD', 'change-this-password');

define('SESSION_COOKIE_NAME', 'swalaf_admin_session');

define('SITE_NAME', 'Swalaf Yoghurt & Treats');
define('SITE_URL', getenv('SWALAF_SITE_URL') ?: 'https://swalafyoghurt.com');
