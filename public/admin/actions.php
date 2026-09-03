<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

start_admin_session();
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/index.php');
}
csrf_check();

$pdo = db();
$action = $_POST['action'] ?? '';
$view = $_POST['view'] ?? 'overview';

/** Parses a free-text "35cl ₦2,000 · 50cl ₦3,000" field into [[label, price, min], ...]. */
function parse_sizes_input(string $raw): array
{
    $out = [];
    foreach (preg_split('/[·;\n]+/u', $raw) as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '') {
            continue;
        }
        if (!preg_match('/^(.*?)\s*₦?\s*([\d,]+)\s*$/u', $chunk, $m)) {
            continue;
        }
        $label = trim($m[1]) ?: 'Size';
        $price = (int) str_replace(',', '', $m[2]);
        $out[] = ['label' => $label, 'price' => $price, 'min' => 1];
    }
    return $out ?: [['label' => 'Standard', 'price' => 0, 'min' => 1]];
}

switch ($action) {
    case 'toggle_product':
        $id = (int) ($_POST['product_id'] ?? 0);
        $pdo->prepare('UPDATE products SET is_live = 1 - is_live WHERE id = ?')->execute([$id]);
        break;

    case 'add_product':
        $name = trim($_POST['name'] ?? '') ?: 'Untitled product';
        $cat = trim($_POST['cat'] ?? '') ?: 'Yoghurt';
        $sizes = parse_sizes_input($_POST['sizes'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . substr(md5(uniqid('', true)), 0, 5);
        $image = handle_image_upload('photo') ?? 'assets/plain.jpg';
        $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM products')->fetchColumn();
        $pdo->prepare('INSERT INTO products (slug, name, category, image, description, sold_30d, is_live, sort_order) VALUES (?, ?, ?, ?, ?, 0, 1, ?)')
            ->execute([$slug, $name, $cat, $image, '', $maxOrder + 1]);
        $productId = (int) $pdo->lastInsertId();
        foreach ($sizes as $i => $s) {
            $pdo->prepare('INSERT INTO product_sizes (product_id, label, price, min_qty, sort_order) VALUES (?, ?, ?, ?, ?)')
                ->execute([$productId, $s['label'], $s['price'], $s['min'], $i + 1]);
        }
        break;

    case 'toggle_section':
        $id = (int) ($_POST['section_id'] ?? 0);
        $pdo->prepare('UPDATE sections SET is_on = 1 - is_on WHERE id = ?')->execute([$id]);
        break;

    case 'save_banner':
        set_setting('banner', trim($_POST['banner'] ?? ''));
        break;

    case 'save_seo':
        set_setting('seo_title', trim($_POST['seo_title'] ?? ''));
        set_setting('seo_desc', trim($_POST['seo_desc'] ?? ''));
        break;

    case 'save_settings':
        set_setting('business_name', trim($_POST['business_name'] ?? ''));
        set_setting('whatsapp_orders', trim($_POST['whatsapp_orders'] ?? ''));
        set_setting('whatsapp_events', trim($_POST['whatsapp_events'] ?? ''));
        set_setting('contact_email', trim($_POST['contact_email'] ?? ''));
        set_setting('notice_hours', (string) max(1, (int) ($_POST['notice_hours'] ?? 24)));
        set_setting('event_notice_hours', (string) max(1, (int) ($_POST['event_notice_hours'] ?? 48)));
        set_setting('delivery_area', trim($_POST['delivery_area'] ?? ''));
        break;

    case 'save_hero_style':
        set_setting('hero_style', ($_POST['hero_style'] ?? 'Split photo') === 'Full-bleed' ? 'Full-bleed' : 'Split photo');
        break;

    case 'advance_order':
        $id = (int) ($_POST['order_id'] ?? 0);
        $flow = ['New', 'Confirmed', 'Batching', 'Delivered'];
        $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $status = $stmt->fetchColumn();
        if ($status !== false) {
            $idx = array_search($status, $flow, true);
            $next = $flow[min(count($flow) - 1, ($idx === false ? 0 : $idx) + 1)];
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$next, $id]);
        }
        break;

    case 'add_hero_image':
        $label = trim($_POST['hero_label'] ?? '') ?: 'Photo';
        $kicker = trim($_POST['hero_kicker'] ?? '') ?: $label;
        $image = handle_image_upload('hero_photo');
        if ($image) {
            $count = (int) $pdo->query('SELECT COUNT(*) FROM hero_images')->fetchColumn();
            if ($count < 5) {
                $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM hero_images')->fetchColumn();
                $pdo->prepare('INSERT INTO hero_images (image, label, kicker, sort_order) VALUES (?, ?, ?, ?)')
                    ->execute([$image, $label, $kicker, $maxOrder + 1]);
            }
        }
        break;

    case 'delete_hero_image':
        $id = (int) ($_POST['hero_id'] ?? 0);
        $pdo->prepare('DELETE FROM hero_images WHERE id = ?')->execute([$id]);
        break;

    case 'add_message':
        $name = trim($_POST['msg_name'] ?? '') ?: 'Someone';
        $channel = trim($_POST['msg_channel'] ?? '') ?: 'WhatsApp';
        $text = trim($_POST['msg_text'] ?? '');
        $href = trim($_POST['msg_href'] ?? '') ?: '#';
        $initials = strtoupper(substr($name, 0, 1) . (strpos($name, ' ') ? substr($name, strpos($name, ' ') + 1, 1) : ''));
        if ($text !== '') {
            $pdo->prepare('INSERT INTO messages (name, initials, channel, body, href) VALUES (?, ?, ?, ?, ?)')
                ->execute([$name, $initials, $channel, $text, $href]);
        }
        break;
}

redirect('/admin/index.php?view=' . urlencode($view) . '&saved=1');
