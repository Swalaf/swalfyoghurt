<?php
require_once __DIR__ . '/../../includes/db.php';

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$label = substr((string) ($body['label'] ?? ''), 0, 80);

if ($label !== '') {
    db()->prepare('INSERT INTO clicks (label) VALUES (?)')->execute([$label]);
}

http_response_code(204);
