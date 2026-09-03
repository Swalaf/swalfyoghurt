<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_COOKIE_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (($_SERVER['HTTPS'] ?? '') === 'on'),
        ]);
        session_start();
    }
}

function ensure_admin_seeded(): void
{
    $pdo = db();
    $count = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($count === 0) {
        $pdo->prepare('INSERT INTO admin_users (username, password_hash, display_name, role) VALUES (?, ?, ?, ?)')
            ->execute([
                ADMIN_DEFAULT_USERNAME,
                password_hash(ADMIN_DEFAULT_PASSWORD, PASSWORD_DEFAULT),
                'Romlah',
                'owner',
            ]);
    }
}

function attempt_login(string $username, string $password): bool
{
    ensure_admin_seeded();
    $stmt = db()->prepare('SELECT * FROM admin_users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['display_name'];
        return true;
    }
    return false;
}

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, username, display_name, role FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_login(): array
{
    $user = current_admin();
    if (!$user) {
        redirect('/admin/login.php');
    }
    return $user;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
