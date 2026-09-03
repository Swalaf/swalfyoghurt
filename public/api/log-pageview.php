<?php
require_once __DIR__ . '/../../includes/db.php';

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$path = substr((string) ($body['path'] ?? '/'), 0, 255);
$referrer = substr((string) ($body['referrer'] ?? ''), 0, 500);
$device = in_array($body['device'] ?? '', ['Mobile', 'Tablet', 'Desktop'], true) ? $body['device'] : 'Desktop';

db()->prepare('INSERT INTO pageviews (path, referrer, device) VALUES (?, ?, ?)')->execute([$path, $referrer, $device]);

http_response_code(204);
