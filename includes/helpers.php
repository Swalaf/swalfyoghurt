<?php
require_once __DIR__ . '/db.php';

/** Escape for HTML output. */
function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** Format an integer amount of Naira the same way the design's NGN() helper does. */
function ngn(int $amount): string
{
    return '₦' . number_format($amount);
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Invalid or expired form submission. Go back and try again.');
    }
}

/** Read one row from the key/value settings table, with a fallback default. */
function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT name, value FROM settings') as $row) {
            $cache[$row['name']] = $row['value'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE name = ?');
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->prepare('UPDATE settings SET value = ? WHERE name = ?')->execute([$value, $key]);
    } else {
        $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?)')->execute([$key, $value]);
    }
}

/**
 * Validates and stores an uploaded image under public/assets/uploads.
 * Returns the relative path to store in the DB (e.g. "assets/uploads/xyz.jpg"), or null.
 */
function handle_image_upload(string $field): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $tmp = $_FILES[$field]['tmp_name'];
    $info = @getimagesize($tmp);
    if (!$info) {
        return null;
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $ext = $allowed[$info['mime']] ?? null;
    if (!$ext || $_FILES[$field]['size'] > 8 * 1024 * 1024) {
        return null;
    }
    $dir = __DIR__ . '/../public/assets/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        return null;
    }
    return 'assets/uploads/' . $name;
}

function wa_link(string $phone, string $message = ''): string
{
    $digits = preg_replace('/[^0-9+]/', '', $phone);
    $url = 'https://wa.me/' . ltrim($digits, '+');
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}
