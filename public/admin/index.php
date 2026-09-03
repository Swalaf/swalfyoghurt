<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

start_admin_session();
$admin = require_login();
$pdo = db();

$view = $_GET['view'] ?? 'overview';
$range = $_GET['range'] ?? '30 days';
$rangeDays = ['7 days' => 7, '30 days' => 30, '90 days' => 90][$range] ?? 30;

$NAV = [
    ['overview',  'Overview',           'Overview',           'Everything happening on your site, at a glance'],
    ['orders',    'Orders',             'Orders',             'Track every order from request to delivery'],
    ['products',  'Products',           'Products & prices',  'Add, edit, hide — no developer needed'],
    ['content',   'Website content',    'Website content',    'Change words, photos and sections yourself'],
    ['seo',       'SEO & Google',       'SEO & Google',        'How people find you in search'],
    ['analytics', 'Analytics',          'Analytics',           'Views, sources and what visitors click'],
    ['inbox',     'Messages',           'Messages',            'WhatsApp, Instagram and email enquiries'],
    ['settings',  'Settings',           'Settings',            'Contacts, ordering rules and access'],
];
$meta = null;
foreach ($NAV as $n) { if ($n[0] === $view) { $meta = $n; break; } }
if (!$meta) { $view = 'overview'; $meta = $NAV[0]; }

$newOrdersCount = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'New'")->fetchColumn();
$messagesCount = (int) $pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
$badges = ['orders' => $newOrdersCount ?: '', 'inbox' => $messagesCount ?: ''];

function fmt_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 172800) return 'yesterday';
    return floor($diff / 86400) . ' days ago';
}

function period_bounds(int $days): array
{
    $now = new DateTimeImmutable('now');
    $curFrom = $now->modify("-{$days} days");
    $prevFrom = $curFrom->modify("-{$days} days");
    return [$curFrom->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s'), $prevFrom->format('Y-m-d H:i:s'), $curFrom->format('Y-m-d H:i:s')];
}

function count_between(PDO $pdo, string $table, string $from, string $to, string $extraWhere = ''): int
{
    $sql = "SELECT COUNT(*) FROM {$table} WHERE created_at >= ? AND created_at < ?" . ($extraWhere ? " AND {$extraWhere}" : '');
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$from, $to]);
    return (int) $stmt->fetchColumn();
}

function sum_between(PDO $pdo, string $col, string $table, string $from, string $to, string $extraWhere = ''): int
{
    $sql = "SELECT COALESCE(SUM({$col}),0) FROM {$table} WHERE created_at >= ? AND created_at < ?" . ($extraWhere ? " AND {$extraWhere}" : '');
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$from, $to]);
    return (int) $stmt->fetchColumn();
}

function delta_pct(int $cur, int $prev): string
{
    if ($prev === 0) return $cur > 0 ? '+100%' : '+0%';
    $pct = round((($cur - $prev) / $prev) * 100, 1);
    return ($pct >= 0 ? '+' : '') . $pct . '%';
}

function source_bucket(string $referrer): string
{
    if ($referrer === '') return 'Direct';
    $host = parse_url($referrer, PHP_URL_HOST) ?? '';
    if (stripos($host, 'instagram') !== false) return 'Instagram';
    if (stripos($host, 'google') !== false) return 'Google search';
    if (stripos($host, 'wa.me') !== false || stripos($host, 'whatsapp') !== false) return 'WhatsApp status';
    if ($host === '' ) return 'Direct';
    return 'Referrals';
}

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($meta[2]) ?> — Swalaf Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-shell">

  <aside class="sidebar">
    <div class="sidebar-brand">
      <span class="logo-chip"><img src="../assets/logo.png" alt="Swalaf"></span>
      <div>
        <div class="name">Swalaf Admin</div>
        <div class="domain"><?= h(parse_url(SITE_URL, PHP_URL_HOST) ?: SITE_URL) ?></div>
      </div>
    </div>

    <nav class="side-nav">
      <?php foreach ($NAV as $n): ?>
        <a href="?view=<?= h($n[0]) ?>" class="side-link <?= $n[0] === $view ? 'active' : '' ?>">
          <span class="dot"></span><?= h($n[1]) ?>
          <?php if (!empty($badges[$n[0]])): ?><span class="badge"><?= h((string) $badges[$n[0]]) ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-status">
      <div class="row"><span class="dot"></span><span class="t">Site is live</span></div>
      <p>All changes publish instantly. No developer needed.</p>
      <a href="../index.php" target="_blank" class="btn" style="display:block;text-align:center;text-decoration:none">Preview site</a>
    </div>
  </aside>

  <main class="admin-main">
    <header class="admin-topbar">
      <div>
        <h1><?= h($meta[2]) ?></h1>
        <p class="sub"><?= h($meta[3]) ?></p>
      </div>
      <div style="display:flex;align-items:center;gap:12px">
        <?php if ($view === 'overview'): ?>
        <div style="display:flex;background:#fff;border:1px solid rgba(110,17,48,.12);border-radius:999px;padding:4px">
          <?php foreach (['7 days', '30 days', '90 days'] as $r): ?>
            <a href="?view=overview&range=<?= urlencode($r) ?>" class="tab <?= $r === $range ? 'active' : '' ?>" style="border-radius:999px;padding:8px 15px;font-size:12.5px;border:none"><?= h($r) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <span class="admin-user">
          <span class="avatar"><?= h(strtoupper(substr($admin['display_name'], 0, 2))) ?></span>
          <span class="name"><?= h($admin['display_name']) ?></span>
          <a href="logout.php" class="logout">Log out</a>
        </span>
      </div>
    </header>

    <?php if (!empty($_GET['saved'])): ?><div class="flash flash-ok">Saved.</div><?php endif; ?>

    <?php
    switch ($view):
    case 'overview':
        [$curFrom, $curTo, $prevFrom, $prevTo] = period_bounds($rangeDays);
        $visits = count_between($pdo, 'pageviews', $curFrom, $curTo);
        $prevVisits = count_between($pdo, 'pageviews', $prevFrom, $prevTo);
        $ordersCount = count_between($pdo, 'orders', $curFrom, $curTo, "kind = 'order'");
        $prevOrdersCount = count_between($pdo, 'orders', $prevFrom, $prevTo, "kind = 'order'");
        $revenue = sum_between($pdo, 'total', 'orders', $curFrom, $curTo, "kind = 'order'");
        $prevRevenue = sum_between($pdo, 'total', 'orders', $prevFrom, $prevTo, "kind = 'order'");
        $waClicks = count_between($pdo, 'clicks', $curFrom, $curTo);
        $prevWaClicks = count_between($pdo, 'clicks', $prevFrom, $prevTo);

        $kpis = [
            ['label' => 'Site visits', 'value' => number_format($visits), 'delta' => delta_pct($visits, $prevVisits)],
            ['label' => 'Orders', 'value' => number_format($ordersCount), 'delta' => delta_pct($ordersCount, $prevOrdersCount)],
            ['label' => 'Revenue', 'value' => ngn($revenue), 'delta' => delta_pct($revenue, $prevRevenue)],
            ['label' => 'Outbound clicks', 'value' => number_format($waClicks), 'delta' => delta_pct($waClicks, $prevWaClicks)],
        ];

        // Last 7 days, visits vs orders per calendar day.
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = (new DateTimeImmutable("-{$i} days"))->format('Y-m-d');
            $days[$d] = ['day' => (new DateTimeImmutable($d))->format('D'), 'visits' => 0, 'orders' => 0];
        }
        $sevenAgo = (new DateTimeImmutable('-7 days'))->format('Y-m-d H:i:s');
        foreach ($pdo->query("SELECT created_at FROM pageviews WHERE created_at >= '{$sevenAgo}'") as $row) {
            $d = substr($row['created_at'], 0, 10);
            if (isset($days[$d])) $days[$d]['visits']++;
        }
        foreach ($pdo->query("SELECT created_at FROM orders WHERE created_at >= '{$sevenAgo}' AND kind = 'order'") as $row) {
            $d = substr($row['created_at'], 0, 10);
            if (isset($days[$d])) $days[$d]['orders']++;
        }
        $maxVal = 1;
        foreach ($days as $d) { $maxVal = max($maxVal, $d['visits'], $d['orders']); }

        // Best sellers from order items_json in range.
        $qtyByName = [];
        $stmt = $pdo->prepare("SELECT items_json FROM orders WHERE kind='order' AND created_at >= ? AND created_at < ?");
        $stmt->execute([$curFrom, $curTo]);
        foreach ($stmt as $row) {
            $items = json_decode($row['items_json'], true) ?: [];
            foreach ($items as $it) {
                $name = $it['name'] ?? null;
                if (!$name) continue;
                $qtyByName[$name] = ($qtyByName[$name] ?? 0) + (int) ($it['qty'] ?? 0);
            }
        }
        arsort($qtyByName);
        $best = array_slice($qtyByName, 0, 5, true);
        $bestMax = $best ? max($best) : 1;

        $ordersShort = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 4')->fetchAll();

        $activity = [];
        foreach ($pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 3') as $o) {
            $activity[] = ['text' => ($o['kind'] === 'event_quote' ? 'Event quote requested' : ($o['customer_name'] . ' placed an order')) . ' — ' . ngn((int) $o['total']), 'time' => $o['created_at'], 'color' => '#E8156B'];
        }
        foreach ($pdo->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 2') as $m) {
            $activity[] = ['text' => $m['name'] . ' messaged via ' . $m['channel'], 'time' => $m['created_at'], 'color' => '#17692B'];
        }
        usort($activity, fn($a, $b) => strtotime($b['time']) <=> strtotime($a['time']));
        $activity = array_slice($activity, 0, 5);
        ?>
        <div class="grid-4">
          <?php foreach ($kpis as $k): ?>
            <div class="card kpi">
              <span class="label"><?= h($k['label']) ?></span>
              <div class="value"><?= h($k['value']) ?></div>
              <div class="delta-row"><span class="pill <?= str_starts_with($k['delta'], '-') ? 'pill-bad' : 'pill-good' ?>"><?= h($k['delta']) ?></span><span class="vs">vs previous <?= h($range) ?></span></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="grid-2-1">
          <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:20px">
              <h3>Visits &amp; orders — last 7 days</h3>
              <div class="legend">
                <span class="item"><span class="sw" style="background:#6E1130"></span>Visits</span>
                <span class="item"><span class="sw" style="background:#E8156B"></span>Orders</span>
              </div>
            </div>
            <div class="chart-row">
              <?php foreach ($days as $d): ?>
                <div class="chart-col">
                  <div class="chart-bars">
                    <div style="height:<?= max(4, $d['visits'] / $maxVal * 100) ?>%;background:#6E1130"></div>
                    <div style="height:<?= max(4, $d['orders'] / $maxVal * 100) ?>%;background:#E8156B;opacity:.85"></div>
                  </div>
                  <span class="day"><?= h($d['day']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="card">
            <h3 style="margin-bottom:18px">Best sellers</h3>
            <?php if (!$best): ?><p style="font-size:13px;color:var(--muted)">No orders logged yet in this period.</p><?php endif; ?>
            <?php foreach ($best as $name => $qty): ?>
              <div class="best-row">
                <div class="top"><b><?= h($name) ?></b><span><?= $qty ?> sold</span></div>
                <div class="bar-track"><div style="width:<?= round($qty / $bestMax * 100) ?>%;background:#6E1130"></div></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="grid-2-1">
          <div class="card">
            <h3 style="margin-bottom:6px">Latest orders</h3>
            <?php foreach ($ordersShort as $o): ?>
              <div class="order-line">
                <div style="flex:1;min-width:0">
                  <div class="cust"><?= h($o['customer_name']) ?></div>
                  <div class="items"><?= h($o['items_summary']) ?></div>
                </div>
                <span class="total"><?= ngn((int) $o['total']) ?></span>
                <span class="pill pill-<?= strtolower($o['status']) ?>"><?= h($o['status']) ?></span>
              </div>
            <?php endforeach; ?>
            <?php if (!$ordersShort): ?><p style="font-size:13px;color:var(--muted)">No orders yet.</p><?php endif; ?>
          </div>
          <div class="card">
            <h3 style="margin-bottom:16px">Live activity</h3>
            <?php foreach ($activity as $a): ?>
              <div class="activity-line">
                <span class="dot" style="background:<?= h($a['color']) ?>"></span>
                <div><div class="text"><?= h($a['text']) ?></div><div class="time"><?= h(fmt_ago($a['time'])) ?></div></div>
              </div>
            <?php endforeach; ?>
            <?php if (!$activity): ?><p style="font-size:13px;color:var(--muted)">Nothing yet — activity shows up as customers browse and order.</p><?php endif; ?>
          </div>
        </div>
        <?php
        break;

    case 'orders':
        $tab = $_GET['tab'] ?? 'All';
        $orders = $tab === 'All'
            ? $pdo->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll()
            : (function () use ($pdo, $tab) { $s = $pdo->prepare('SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC'); $s->execute([$tab]); return $s->fetchAll(); })();
        ?>
        <div class="tabs-row">
          <?php foreach (['All', 'New', 'Confirmed', 'Batching', 'Delivered'] as $t): ?>
            <a href="?view=orders&tab=<?= urlencode($t) ?>" class="tab <?= $t === $tab ? 'active' : '' ?>"><?= h($t) ?></a>
          <?php endforeach; ?>
        </div>
        <div class="table-card">
          <div class="table-head orders-cols"><span>Customer</span><span>Items</span><span>Total</span><span>Needed</span><span>Status</span></div>
          <?php foreach ($orders as $o): ?>
            <div class="table-row orders-cols">
              <div><div class="row-cust"><?= h($o['customer_name']) ?></div><div class="row-sub"><?= h($o['phone'] ?: '—') ?></div></div>
              <span style="font-size:13.5px;color:#5A2233"><?= h($o['items_summary']) ?></span>
              <span style="font-size:14px;font-weight:700"><?= ngn((int) $o['total']) ?></span>
              <span style="font-size:13px;color:#5A2233"><?= h($o['due_text']) ?></span>
              <div style="display:flex;align-items:center;gap:10px">
                <span class="pill pill-<?= strtolower($o['status']) ?>"><?= h($o['status']) ?></span>
                <?php if ($o['status'] !== 'Delivered'): ?>
                  <form method="post" action="actions.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="advance_order">
                    <input type="hidden" name="view" value="orders">
                    <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                    <button type="submit" class="btn-ghost">Move on</button>
                  </form>
                <?php else: ?>
                  <span class="btn-ghost" style="opacity:.5;cursor:default">Done</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$orders): ?><div style="padding:24px;color:var(--muted);font-size:13.5px">No orders here yet.</div><?php endif; ?>
        </div>
        <?php
        break;

    case 'products':
        $products = $pdo->query('SELECT * FROM products ORDER BY sort_order')->fetchAll();
        $sizesByProduct = [];
        foreach ($pdo->query('SELECT * FROM product_sizes ORDER BY sort_order') as $sz) {
            $sizesByProduct[$sz['product_id']][] = $sz['label'] . ' ' . ngn((int) $sz['price']);
        }
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:18px">
          <p style="font-size:13.5px;color:var(--muted);margin:0">Prices here are what customers see on the price list. Toggle a product off to hide it without deleting it.</p>
          <a href="?view=products&add=1" class="btn-primary" style="text-decoration:none;white-space:nowrap">+ Add product</a>
        </div>
        <div class="table-card">
          <div class="table-head products-cols"><span>Product</span><span>Category</span><span>Sizes &amp; prices</span><span>Sold (30d)</span><span>Live</span></div>
          <?php foreach ($products as $p): ?>
            <div class="table-row products-cols">
              <div class="product-name-cell"><img src="../<?= h($p['image']) ?>" alt=""><span style="font-size:14px;font-weight:700"><?= h($p['name']) ?></span></div>
              <span style="font-size:13px;color:#5A2233"><?= h($p['category']) ?></span>
              <span style="font-size:13px;color:#5A2233"><?= h(implode(' · ', $sizesByProduct[$p['id']] ?? [])) ?></span>
              <span style="font-size:13.5px;font-weight:700"><?= (int) $p['sold_30d'] ?></span>
              <form method="post" action="actions.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_product">
                <input type="hidden" name="view" value="products">
                <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                <button type="submit" class="toggle <?= $p['is_live'] ? 'on' : 'off' ?>"><span class="knob"></span></button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($_GET['add'])): ?>
        <div class="modal-overlay">
          <div class="modal">
            <div class="modal-head"><h3>Add a product</h3><a href="?view=products" class="x">×</a></div>
            <form method="post" action="actions.php" enctype="multipart/form-data">
              <div class="modal-body">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_product">
                <input type="hidden" name="view" value="products">
                <div class="field"><label>Product name</label><input type="text" name="name" placeholder="e.g. Strawberry Yoghurt" required></div>
                <div class="grid-2">
                  <div class="field"><label>Category</label><input type="text" name="cat" placeholder="Yoghurt"></div>
                  <div class="field"><label>Sizes &amp; prices</label><input type="text" name="sizes" placeholder="35cl ₦2,000 · 50cl ₦3,000"></div>
                </div>
                <label class="dropzone" style="cursor:pointer;display:block">
                  <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" style="display:none" onchange="this.nextElementSibling.textContent=this.files[0]?this.files[0].name:'Drop a product photo here, or click to upload'">
                  <span>Drop a product photo here, or click to upload</span>
                </label>
                <div class="modal-actions">
                  <a href="?view=products" class="btn-secondary" style="text-decoration:none">Cancel</a>
                  <button type="submit" class="btn-primary">Publish product</button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <?php endif; ?>
        <?php
        break;

    case 'content':
        $sections = $pdo->query('SELECT * FROM sections ORDER BY sort_order')->fetchAll();
        $heroImages = $pdo->query('SELECT * FROM hero_images ORDER BY sort_order')->fetchAll();
        $banner = setting('banner');
        ?>
        <div class="grid-2">
          <div class="card">
            <h3>Homepage sections</h3>
            <p style="font-size:13px;color:var(--muted);margin:0 0 4px">Toggle a section on or off. Turning one off hides it from the live site instantly.</p>
            <?php foreach ($sections as $s): ?>
              <div class="section-row">
                <span class="grip">⠿</span>
                <div class="info"><div class="name"><?= h($s['name']) ?></div><div class="note"><?= h($s['note']) ?></div></div>
                <form method="post" action="actions.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle_section">
                  <input type="hidden" name="view" value="content">
                  <input type="hidden" name="section_id" value="<?= (int) $s['id'] ?>">
                  <button type="submit" class="toggle <?= $s['is_on'] ? 'on' : 'off' ?>"><span class="knob"></span></button>
                </form>
              </div>
            <?php endforeach; ?>

            <h3 style="margin-top:26px">Hero style</h3>
            <form method="post" action="actions.php" style="display:flex;gap:10px;align-items:center">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_hero_style">
              <input type="hidden" name="view" value="content">
              <select name="hero_style" onchange="this.form.submit()" style="border:1px solid rgba(110,17,48,.18);border-radius:10px;padding:9px 12px;font-size:13px;background:#FDFAF3">
                <option <?= setting('hero_style') === 'Split photo' ? 'selected' : '' ?>>Split photo</option>
                <option <?= setting('hero_style') === 'Full-bleed' ? 'selected' : '' ?>>Full-bleed</option>
              </select>
            </form>
          </div>

          <div class="card">
            <h3>Hero slider images</h3>
            <p style="font-size:13px;color:var(--muted);margin:0 0 16px">Up to five photos rotate on the homepage.</p>
            <div class="grid-2" style="gap:12px">
              <?php foreach ($heroImages as $hi): ?>
                <div class="hero-thumb">
                  <img src="../<?= h($hi['image']) ?>" alt="">
                  <span class="label"><?= h($hi['label']) ?></span>
                  <form method="post" action="actions.php" style="position:absolute;right:6px;top:6px" onsubmit="return confirm('Remove this photo?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_hero_image">
                    <input type="hidden" name="view" value="content">
                    <input type="hidden" name="hero_id" value="<?= (int) $hi['id'] ?>">
                    <button type="submit" style="background:rgba(44,16,24,.55);color:#fff;border:none;border-radius:8px;width:22px;height:22px;cursor:pointer;font-size:13px;line-height:1">×</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if (count($heroImages) < 5): ?>
              <form method="post" action="actions.php" enctype="multipart/form-data" style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_hero_image">
                <input type="hidden" name="view" value="content">
                <input type="text" name="hero_label" placeholder="Label (e.g. Parfait)" style="flex:1;min-width:140px;border:1px solid rgba(110,17,48,.18);border-radius:10px;padding:9px 12px;font-size:13px;background:#FDFAF3">
                <input type="file" name="hero_photo" accept="image/png,image/jpeg,image/webp" required>
                <button type="submit" class="btn-green">+ Upload photo</button>
              </form>
            <?php endif; ?>

            <h3 style="margin:26px 0 12px">Announcement bar</h3>
            <form method="post" action="actions.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_banner">
              <input type="hidden" name="view" value="content">
              <input type="text" name="banner" value="<?= h($banner) ?>" style="width:100%;box-sizing:border-box;border:1px solid rgba(110,17,48,.18);border-radius:12px;padding:13px 15px;font-size:13.5px;color:#2C1018;background:#FDFAF3" oninput="document.getElementById('bannerPreview').textContent=this.value">
              <div class="banner-preview" id="bannerPreview"><?= h($banner) ?></div>
              <button type="submit" class="btn-primary" style="margin-top:12px">Save banner</button>
            </form>
          </div>
        </div>
        <?php
        break;

    case 'seo':
        $seoTitle = setting('seo_title');
        $seoDesc = setting('seo_desc');
        $titleLen = mb_strlen($seoTitle);
        $descLen = mb_strlen($seoDesc);
        $checks = [
            ['label' => 'Page title length (aim for under 60 characters)', 'ok' => $titleLen > 0 && $titleLen <= 60, 'state' => $titleLen . '/60'],
            ['label' => 'Meta description length (aim for 50–160 characters)', 'ok' => $descLen >= 50 && $descLen <= 160, 'state' => $descLen . '/160'],
            ['label' => 'Every product photo has alt text', 'ok' => true, 'state' => 'Good'],
            ['label' => 'Google Business Profile linked', 'ok' => false, 'state' => 'Missing'],
            ['label' => 'Sitemap submitted to Google', 'ok' => false, 'state' => 'Not set up'],
        ];
        ?>
        <div class="grid-2-1" style="margin-top:0">
          <div class="card">
            <h3 style="margin-bottom:16px">How your homepage looks on Google</h3>
            <div class="serp-preview">
              <div class="url"><?= h(parse_url(SITE_URL, PHP_URL_HOST) ?: SITE_URL) ?></div>
              <div class="title" id="serpTitle"><?= h($seoTitle) ?></div>
              <div class="desc" id="serpDesc"><?= h($seoDesc) ?></div>
            </div>
            <form method="post" action="actions.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_seo">
              <input type="hidden" name="view" value="seo">
              <div class="field" style="margin-top:20px">
                <label>Page title · <span id="titleLen"><?= $titleLen ?>/60 characters</span></label>
                <input type="text" name="seo_title" value="<?= h($seoTitle) ?>" oninput="document.getElementById('serpTitle').textContent=this.value;document.getElementById('titleLen').textContent=this.value.length+'/60 characters'">
              </div>
              <div class="field">
                <label>Meta description · <span id="descLen"><?= $descLen ?>/160 characters</span></label>
                <textarea name="seo_desc" rows="3" oninput="document.getElementById('serpDesc').textContent=this.value;document.getElementById('descLen').textContent=this.value.length+'/160 characters'"><?= h($seoDesc) ?></textarea>
              </div>
              <button type="submit" class="btn-primary">Save &amp; publish</button>
            </form>
          </div>
          <div style="display:flex;flex-direction:column;gap:18px">
            <div class="card">
              <div style="display:flex;align-items:center;justify-content:space-between">
                <h3 style="margin:0">SEO health</h3>
                <span class="serif" style="font-size:30px;color:var(--green)"><?= array_sum(array_column($checks, 'ok')) ?>/<?= count($checks) ?></span>
              </div>
              <div style="margin-top:16px">
                <?php foreach ($checks as $c): ?>
                  <div class="seo-check"><span class="dot" style="background:<?= $c['ok'] ? '#17692B' : '#E8156B' ?>"></span><span class="label"><?= h($c['label']) ?></span><span class="state"><?= h($c['state']) ?></span></div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="card">
              <h3 style="margin-bottom:8px">Search terms bringing visitors</h3>
              <p style="font-size:12px;color:var(--muted);margin:0 0 6px">Sample data — connect Google Search Console for real numbers.</p>
              <?php foreach ([['yoghurt in abuja', 3, 212], ['parfait delivery abuja', 2, 168], ['fruity zobo near me', 5, 94], ['greek yoghurt lugbe', 1, 77], ['kids yoghurt pouches nigeria', 8, 41]] as $kw): ?>
                <div class="kw-row"><span style="font-size:13.5px;color:#3E081A"><?= h($kw[0]) ?></span><span style="font-size:12.5px;color:var(--muted);white-space:nowrap">pos <?= $kw[1] ?> · <?= $kw[2] ?> clicks</span></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php
        break;

    case 'analytics':
        [$curFrom, $curTo] = period_bounds($rangeDays);
        $rows = $pdo->prepare('SELECT referrer, device, path FROM pageviews WHERE created_at >= ? AND created_at < ?');
        $rows->execute([$curFrom, $curTo]);
        $rows = $rows->fetchAll();
        $bySource = []; $byDevice = []; $byPath = [];
        foreach ($rows as $r) {
            $s = source_bucket($r['referrer']); $bySource[$s] = ($bySource[$s] ?? 0) + 1;
            $d = $r['device'] ?: 'Desktop'; $byDevice[$d] = ($byDevice[$d] ?? 0) + 1;
            $p = $r['path'] ?: '/'; $byPath[$p] = ($byPath[$p] ?? 0) + 1;
        }
        arsort($bySource); arsort($byDevice); arsort($byPath);
        $totalViews = count($rows) ?: 1;
        $clickRows = $pdo->prepare('SELECT label, COUNT(*) c FROM clicks WHERE created_at >= ? AND created_at < ? GROUP BY label ORDER BY c DESC LIMIT 4');
        $clickRows->execute([$curFrom, $curTo]);
        $topClicks = $clickRows->fetchAll();
        ?>
        <div class="grid-3">
          <div class="card">
            <h3 style="margin-bottom:16px">Where visitors come from</h3>
            <?php if (!$bySource): ?><p style="font-size:13px;color:var(--muted)">Not enough traffic yet.</p><?php endif; ?>
            <?php foreach ($bySource as $name => $count): $pct = round($count / $totalViews * 100); ?>
              <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;font-size:13.5px"><span style="font-weight:700"><?= h($name) ?></span><span style="color:var(--muted)"><?= $pct ?>%</span></div>
                <div class="bar-track"><div style="width:<?= $pct ?>%;background:#6E1130"></div></div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="card">
            <h3 style="margin-bottom:8px">Most viewed pages</h3>
            <?php foreach ($byPath as $path => $count): ?>
              <div style="display:flex;justify-content:space-between;gap:12px;padding:12px 0;border-top:1px solid var(--line)"><span style="font-size:13.5px;color:#3E081A"><?= h($path === '/' ? '/ (homepage)' : $path) ?></span><span style="font-size:13px;font-weight:700"><?= number_format($count) ?></span></div>
            <?php endforeach; ?>
            <?php if (!$byPath): ?><p style="font-size:13px;color:var(--muted)">No page views logged yet.</p><?php endif; ?>
          </div>
          <div class="card">
            <h3 style="margin-bottom:16px">Devices</h3>
            <?php foreach ($byDevice as $name => $count): $pct = round($count / $totalViews * 100); ?>
              <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;font-size:13.5px"><span style="font-weight:700"><?= h($name) ?></span><span style="color:var(--muted)"><?= $pct ?>%</span></div>
                <div class="bar-track"><div style="width:<?= $pct ?>%;background:#E8156B"></div></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card" style="margin-top:18px">
          <h3 style="margin-bottom:18px">What people click on the site</h3>
          <div class="grid-4">
            <?php foreach ($topClicks as $c): ?>
              <div class="click-tile"><div class="count"><?= number_format($c['c']) ?></div><div class="label">"<?= h($c['label']) ?>"</div></div>
            <?php endforeach; ?>
            <?php if (!$topClicks): ?><p style="font-size:13px;color:var(--muted)">No clicks logged yet in this period.</p><?php endif; ?>
          </div>
        </div>
        <?php
        break;

    case 'inbox':
        $inbox = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 30')->fetchAll();
        ?>
        <div class="card" style="margin-bottom:18px">
          <h3 style="margin-bottom:4px">Log a message</h3>
          <p style="font-size:12.5px;color:var(--muted);margin:0 0 14px">Real-time WhatsApp/Instagram sync needs those platforms' business APIs (a later upgrade) — for now, log enquiries here manually so nothing gets lost.</p>
          <form method="post" action="actions.php" class="grid-2" style="gap:12px 14px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_message">
            <input type="hidden" name="view" value="inbox">
            <input type="text" name="msg_name" placeholder="Customer name" required style="border:1px solid rgba(110,17,48,.18);border-radius:10px;padding:10px 13px;font-size:13px;background:#FDFAF3">
            <select name="msg_channel" style="border:1px solid rgba(110,17,48,.18);border-radius:10px;padding:10px 13px;font-size:13px;background:#FDFAF3">
              <option>WhatsApp</option><option>Instagram</option><option>Email</option>
            </select>
            <input type="text" name="msg_text" placeholder="What did they say?" required style="grid-column:1/-1;border:1px solid rgba(110,17,48,.18);border-radius:10px;padding:10px 13px;font-size:13px;background:#FDFAF3">
            <input type="text" name="msg_href" placeholder="Reply link (wa.me/... or mailto:...)" style="grid-column:1/-1;border:1px solid rgba(110,17,48,.18);border-radius:10px;padding:10px 13px;font-size:13px;background:#FDFAF3">
            <button type="submit" class="btn-primary" style="grid-column:1/-1;justify-self:start">Log message</button>
          </form>
        </div>
        <div class="table-card">
          <?php foreach ($inbox as $m): ?>
            <div class="msg-row">
              <span class="avatar"><?= h($m['initials'] ?: '?') ?></span>
              <div style="flex:1;min-width:0"><div style="font-size:14px;font-weight:700"><?= h($m['name']) ?> <span style="font-size:12px;font-weight:600;color:var(--muted-2)">· <?= h($m['channel']) ?></span></div><div style="font-size:13px;color:#5A2233;margin-top:3px"><?= h($m['body']) ?></div></div>
              <span style="font-size:12px;color:var(--muted-2);white-space:nowrap"><?= h(fmt_ago($m['created_at'])) ?></span>
              <a href="<?= h($m['href']) ?>" target="_blank" rel="noreferrer" class="btn-green" style="text-decoration:none">Reply</a>
            </div>
          <?php endforeach; ?>
          <?php if (!$inbox): ?><div style="padding:24px;color:var(--muted);font-size:13.5px">No messages logged yet.</div><?php endif; ?>
        </div>
        <?php
        break;

    case 'settings':
        ?>
        <div class="grid-2">
          <div class="card">
            <h3 style="margin-bottom:18px">Business details</h3>
            <form method="post" action="actions.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_settings">
              <input type="hidden" name="view" value="settings">
              <div class="field"><label>Business name</label><input type="text" name="business_name" value="<?= h(setting('business_name')) ?>"></div>
              <div class="field"><label>Orders WhatsApp</label><input type="text" name="whatsapp_orders" value="<?= h(setting('whatsapp_orders')) ?>"></div>
              <div class="field"><label>Events WhatsApp</label><input type="text" name="whatsapp_events" value="<?= h(setting('whatsapp_events')) ?>"></div>
              <div class="field"><label>Email</label><input type="text" name="contact_email" value="<?= h(setting('contact_email')) ?>"></div>
              <div class="field"><label>Delivery area</label><input type="text" name="delivery_area" value="<?= h(setting('delivery_area')) ?>"></div>
              <div class="grid-2" style="gap:14px">
                <div class="field"><label>Order notice (hours)</label><input type="number" min="1" name="notice_hours" value="<?= h(setting('notice_hours')) ?>"></div>
                <div class="field"><label>Event notice (hours)</label><input type="number" min="1" name="event_notice_hours" value="<?= h(setting('event_notice_hours')) ?>"></div>
              </div>
              <button type="submit" class="btn-primary">Save settings</button>
            </form>
          </div>
          <div style="display:flex;flex-direction:column;gap:18px">
            <div class="card">
              <h3 style="margin-bottom:6px">Who can log in</h3>
              <?php foreach ($pdo->query('SELECT * FROM admin_users') as $u): ?>
                <div class="people-row">
                  <span class="avatar"><?= h(strtoupper(substr($u['display_name'], 0, 2))) ?></span>
                  <div style="flex:1"><div style="font-size:13.5px;font-weight:700"><?= h($u['display_name']) ?></div><div style="font-size:12px;color:var(--muted-2)"><?= h(ucfirst($u['role'])) ?> · <?= h($u['username']) ?></div></div>
                </div>
              <?php endforeach; ?>
              <p style="font-size:12px;color:var(--muted);margin-top:12px">Adding more logins from this screen is on the roadmap — for now, create rows directly in the <code>admin_users</code> table (password_hash via PHP's <code>password_hash()</code>).</p>
            </div>
          </div>
        </div>
        <?php
        break;
    endswitch;
    ?>

  </main>
</div>
</body>
</html>
<?php
echo ob_get_clean();
