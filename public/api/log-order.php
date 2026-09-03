<?php
require_once __DIR__ . '/../../includes/db.php';

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$kind = ($body['kind'] ?? 'order') === 'event_quote' ? 'event_quote' : 'order';
$items = is_array($body['items'] ?? null) ? $body['items'] : [];
$total = (int) ($body['total'] ?? 0);
$note = substr((string) ($body['note'] ?? ''), 0, 120);

if (!$items) {
    http_response_code(204);
    exit;
}

$summaryParts = [];
foreach (array_slice($items, 0, 6) as $it) {
    $name = substr((string) ($it['name'] ?? ''), 0, 60);
    $size = substr((string) ($it['size'] ?? ''), 0, 30);
    $qty = (int) ($it['qty'] ?? 0);
    if ($name === '' || $qty <= 0) {
        continue;
    }
    $summaryParts[] = "{$qty} × {$name} {$size}";
}
$summary = substr(implode(', ', $summaryParts), 0, 255);
if ($note !== '') {
    $summary = substr($note . ' — ' . $summary, 0, 255);
}

$stmt = db()->prepare(
    'INSERT INTO orders (kind, customer_name, phone, items_json, items_summary, total, due_text, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $kind,
    'Website visitor',
    '',
    json_encode($items, JSON_UNESCAPED_UNICODE),
    $summary,
    $total,
    'Awaiting WhatsApp reply',
    'New',
]);

http_response_code(204);
