<?php
// One-off helper: creates and seeds sql/swalaf.sqlite for local dev/testing.
// Not used in production (cPanel uses schema.sql against real MySQL).

require_once __DIR__ . '/../includes/config.php';

if (DB_DRIVER !== 'sqlite') {
    fwrite(STDERR, "DB_DRIVER is not 'sqlite' — nothing to do.\n");
    exit(1);
}

if (file_exists(SQLITE_PATH)) {
    unlink(SQLITE_PATH);
}

$pdo = new PDO('sqlite:' . SQLITE_PATH);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = file_get_contents(__DIR__ . '/schema.sqlite.sql');
$pdo->exec($sql);

echo "Local SQLite database created at " . SQLITE_PATH . "\n";
