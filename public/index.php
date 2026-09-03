<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();

$products = $pdo->query('SELECT * FROM products WHERE is_live = 1 ORDER BY sort_order')->fetchAll();
$sizesByProduct = [];
foreach ($pdo->query('SELECT * FROM product_sizes ORDER BY sort_order') as $sz) {
    $sizesByProduct[$sz['product_id']][] = $sz;
}
foreach ($products as &$p) {
    $p['sizes'] = $sizesByProduct[$p['id']] ?? [];
}
unset($p);

$sectionsOn = [];
foreach ($pdo->query('SELECT slug, is_on FROM sections') as $s) {
    $sectionsOn[$s['slug']] = (bool) $s['is_on'];
}
$on = fn(string $slug) => $sectionsOn[$slug] ?? true;

$heroImages = $pdo->query('SELECT * FROM hero_images ORDER BY sort_order')->fetchAll();

$noticeHours = (int) setting('notice_hours', '24');
$eventNoticeHours = (int) setting('event_notice_hours', '48');
$heroStyle = setting('hero_style', 'Split photo');
$waOrders = setting('whatsapp_orders', '+2348066146476');
$waEvents = setting('whatsapp_events', '+2349038664560');
$email = setting('contact_email', 'romlah0008@gmail.com');
$instagram = setting('instagram_url', '#');
$banner = setting('banner', '');
$seoTitle = setting('seo_title', SITE_NAME);
$seoDesc = setting('seo_desc', '');
$deliveryArea = setting('delivery_area', 'Abuja');

$categories = ['All'];
foreach ($products as $p) {
    if (!in_array($p['category'], $categories, true)) {
        $categories[] = $p['category'];
    }
}

$dataForJs = [
    'products' => array_map(function ($p) {
        return [
            'id' => $p['slug'],
            'name' => $p['name'],
            'cat' => $p['category'],
            'img' => $p['image'],
            'desc' => $p['description'],
            'sizes' => array_map(fn($s) => ['label' => $s['label'], 'price' => (int) $s['price'], 'min' => (int) $s['min_qty']], $p['sizes']),
        ];
    }, $products),
    'slides' => array_map(fn($h) => ['img' => $h['image'], 'kicker' => $h['kicker'], 'label' => $h['label']], $heroImages),
    'noticeHours' => $noticeHours,
    'waOrders' => $waOrders,
    'waEvents' => $waEvents,
    'email' => $email,
    'instagram' => $instagram,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($seoTitle) ?></title>
<meta name="description" content="<?= h($seoDesc) ?>">
<link rel="icon" href="assets/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<div id="splash" class="splash">
  <img src="assets/logo.png" alt="Swalaf Yoghurt &amp; Treats">
  <div class="splash-ring">
    <div class="dash"></div>
    <div class="drop"></div>
    <div class="splash-cup">
      <div class="splash-fill">
        <div class="swirl-a"></div>
        <div class="swirl-b"></div>
      </div>
      <div class="splash-pct"><span id="splashPct">0%</span></div>
    </div>
  </div>
  <div class="splash-bar"><span id="splashBar" style="width:0%"></span></div>
  <span class="splash-caption">Freshly made · pouring</span>
</div>

<header class="site-header">
  <div class="wrap">
    <a href="#top" class="brand"><img src="assets/logo.png" alt="Swalaf Yoghurt &amp; Treats"></a>
    <nav class="main-nav">
      <a href="#menu">Products</a>
      <a href="#bundles">Bundles</a>
      <a href="#events">Events</a>
      <a href="#why">Why Swalaf</a>
      <a href="#order">How to order</a>
      <a href="#contact">Contact</a>
    </nav>
    <a href="<?= h(wa_link($waOrders, 'Hello Swalaf! I\'d like to place an order.')) ?>" target="_blank" rel="noreferrer" class="btn-wa js-outbound" data-label="Header WhatsApp">
      <span class="dot-green"></span>Order on WhatsApp
    </a>
  </div>
</header>

<?php if ($on('hero')): ?>
<?php if ($heroStyle === 'Full-bleed'): ?>
  <section id="top" class="hero-full">
    <img src="assets/lifestyle.jpg" alt="SWALAF products">
    <div class="shade"></div>
    <div class="content">
      <div class="eyebrow">Freshly made · Lugbe, <?= h($deliveryArea) ?></div>
      <h1>Creamy yoghurt, made fresh <em>for your order.</em></h1>
      <p>No additives. No preservatives. Batched the day it reaches you.</p>
      <div class="cta-row">
        <a href="#menu" class="btn-maroon">See prices</a>
        <a href="<?= h(wa_link($waOrders, 'Hello Swalaf! I\'d like to place an order.')) ?>" target="_blank" rel="noreferrer" class="btn-wa js-outbound" data-label="Hero WhatsApp">Order on WhatsApp</a>
      </div>
    </div>
  </section>
<?php else: ?>
  <section id="top" class="wrap hero-split">
    <div>
      <div class="eyebrow">Freshly made · Lugbe, <?= h($deliveryArea) ?></div>
      <h1>Creamy yoghurt,<br>made fresh<br><em>for your order.</em></h1>
      <p class="lede">No additives. No preservatives. Just thick, chilled yoghurt, layered parfaits and Fruity Zobo — batched the day it reaches you.</p>
      <div class="cta-row">
        <a href="#menu" class="btn-maroon">See prices</a>
        <a href="#order" class="btn-outline">How ordering works</a>
      </div>
      <div class="stat-row">
        <div class="stat"><b><?= count($products) ?></b><span>product lines</span></div>
        <div class="stat"><b><?= $noticeHours ?>hrs</b><span>order ahead</span></div>
        <div class="stat"><b>0</b><span>preservatives</span></div>
      </div>
    </div>
    <div class="hero-slides" id="heroSlides">
      <?php foreach ($heroImages as $i => $hImg): ?>
        <img src="<?= h($hImg['image']) ?>" alt="<?= h($hImg['label']) ?>" class="<?= $i === 0 ? 'active' : '' ?>" data-i="<?= $i ?>">
      <?php endforeach; ?>
      <div class="shade"></div>
      <div class="caption">
        <div>
          <span class="kicker" id="slideKicker"><?= h($heroImages[0]['kicker'] ?? '') ?></span>
          <span class="label" id="slideLabel"><?= h($heroImages[0]['label'] ?? '') ?></span>
        </div>
        <div class="hero-dots" id="heroDots">
          <?php foreach ($heroImages as $i => $hImg): ?>
            <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" data-i="<?= $i ?>" aria-label="<?= h($hImg['label']) ?>"></button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>
<?php endif; ?>

<div class="marquee">
  <div class="marquee-track">
    <?php for ($r = 0; $r < 2; $r++): ?>
    <span class="marquee-group">
      <span>Yoghurt Drink</span><span class="sep">◆</span>
      <span>Fruity Zobo</span><span class="sep">◆</span>
      <span>Parfait</span><span class="sep">◆</span>
      <span>Greek Yoghurt</span><span class="sep">◆</span>
      <span>Coconut Infused</span><span class="sep">◆</span>
      <span>Granola</span><span class="sep">◆</span>
      <span>Kids Pack</span><span class="sep">◆</span>
    </span>
    <?php endfor; ?>
  </div>
</div>

<?php if ($on('menu')): ?>
<section id="menu" class="wrap menu-section">
  <div class="section-head">
    <div>
      <span class="kicker">Price list</span>
      <h2>Pick a size, build your order</h2>
    </div>
    <p>Tap a size to see its price, then add it to your tray. We hand the whole list to WhatsApp when you're ready.</p>
  </div>

  <div class="cat-chips" id="catChips">
    <?php foreach ($categories as $c): ?>
      <button type="button" class="chip <?= $c === 'All' ? 'active' : '' ?>" data-cat="<?= h($c) ?>"><?= h($c) ?></button>
    <?php endforeach; ?>
  </div>

  <div class="product-grid" id="productGrid">
    <?php foreach ($products as $p): ?>
      <?php
        $firstSize = $p['sizes'][0] ?? ['label' => '', 'price' => 0, 'min_qty' => 1];
        $note = $firstSize['min_qty'] > 1 ? 'minimum order ' . $firstSize['min_qty'] : 'per ' . (in_array($p['category'], ['Kids', 'Zobo'], true) ? 'pack' : 'unit');
      ?>
      <article class="product-card" data-reveal="1" data-cat="<?= h($p['category']) ?>" data-product="<?= h($p['slug']) ?>">
        <div class="product-photo">
          <img src="<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>">
          <span class="product-cat"><?= h($p['category']) ?></span>
        </div>
        <div class="product-body">
          <div>
            <h3><?= h($p['name']) ?></h3>
            <p class="desc"><?= h($p['description']) ?></p>
          </div>
          <div class="size-row">
            <?php foreach ($p['sizes'] as $i => $s): ?>
              <button type="button" class="size-btn <?= $i === 0 ? 'active' : '' ?>" data-i="<?= $i ?>" data-label="<?= h($s['label']) ?>" data-price="<?= (int) $s['price'] ?>" data-min="<?= (int) $s['min_qty'] ?>"><?= h($s['label']) ?></button>
            <?php endforeach; ?>
          </div>
          <div class="price-row">
            <div class="price-col">
              <span class="price"><?= ngn((int) $firstSize['price']) ?></span>
              <span class="note"><?= h($note) ?></span>
            </div>
            <button type="button" class="btn-add">Add</button>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('bundles')): ?>
<section id="bundles" class="wrap bundles-section">
  <div class="bundles-grid" data-reveal="1">
    <div class="bundle-hero">
      <img src="assets/zobo12.jpg" alt="Fruity Zobo pack of 12">
      <div class="shade"></div>
      <div class="content">
        <span class="kicker">Party &amp; events</span>
        <h3>Fruity Zobo by the crate</h3>
        <p>Hibiscus, real fruit, no shortcuts. 35cl from ₦4,600 for six — 50cl packs of twelve at ₦15,000.</p>
      </div>
    </div>
    <div class="bundle-stack">
      <div class="bundle-card">
        <img src="assets/kids.jpg" alt="Kids pack pouches">
        <div class="fade"></div>
        <div class="content">
          <h3>Kids Pack 100ml</h3>
          <p>Six for ₦5,500 · twelve for ₦9,500. Lunchbox-sized, spill-proof spouts.</p>
        </div>
      </div>
      <div class="bundle-card">
        <img src="assets/parfait.jpg" alt="Parfait cup">
        <div class="fade"></div>
        <div class="content">
          <h3>Layered Parfaits</h3>
          <p>Granola, fruit, thick yoghurt. 150ml minimum 5 · 250ml minimum 2.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($on('events')): ?>
<section id="events" class="wrap events-section">
  <div class="section-head">
    <div>
      <span class="kicker">Events &amp; custom orders</span>
      <h2>Feeding a crowd? We batch for that.</h2>
    </div>
    <p>Naming ceremonies, weddings, office Fridays, hampers. Tell us the headcount and we build the tray for you.</p>
  </div>

  <div class="events-grid">
    <div class="event-form">
      <span class="label">Tell us about your event</span>
      <div class="chip-row" id="guestChips">
        <?php foreach ([25, 50, 100, 200] as $g): ?>
          <button type="button" class="chip <?= $g === 50 ? 'active' : '' ?>" data-guests="<?= $g ?>"><?= $g === 200 ? '200+ guests' : $g . ' guests' ?></button>
        <?php endforeach; ?>
      </div>
      <div class="chip-row" id="eventStyleChips" style="margin-bottom:24px">
        <?php foreach (['Drinks only', 'Drinks + parfait', 'Full spread'] as $es): ?>
          <button type="button" class="size-btn <?= $es === 'Drinks + parfait' ? 'active' : '' ?>" data-style="<?= h($es) ?>"><?= h($es) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="plan-list">
        <span class="heading">A spread this size usually looks like</span>
        <div id="eventPlan"></div>
      </div>
      <a href="#" id="eventWaLink" target="_blank" rel="noreferrer" class="btn-green js-outbound" data-label="Event quote" style="margin-top:26px">Send this request on WhatsApp</a>
      <p class="footnote">We'll come back with a quote and a delivery slot. Event orders need <?= $eventNoticeHours ?> hours' notice.</p>
    </div>

    <div class="event-side">
      <div class="event-photo">
        <img src="assets/lifestyle.jpg" alt="Swalaf event spread">
        <div class="shade"></div>
        <div class="content">
          <h3>Branded hampers</h3>
          <p>Gift boxes of yoghurt, parfait and Zobo with a handwritten card — for clients, bridal parties and thank-yous.</p>
        </div>
      </div>
      <div class="also-card">
        <h3>Also available on request</h3>
        <div class="also-list">
          <div class="plan-line"><span class="bullet" style="background:#E8156B"></span><span class="item">Parfait grazing table, assembled on site</span></div>
          <div class="plan-line"><span class="bullet" style="background:#17692B"></span><span class="item">Custom label printing for corporate events</span></div>
          <div class="plan-line"><span class="bullet" style="background:#C79A3A"></span><span class="item">Weekly office subscription — same delivery slot each week</span></div>
          <div class="plan-line"><span class="bullet" style="background:#6E1130"></span><span class="item">Granola and banana bread add-ons</span></div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($on('why')): ?>
<section id="why" class="wrap why-section">
  <div class="why-grid" data-reveal="1">
    <div class="why-card">
      <div class="badge" style="background:#17692B"></div>
      <h3>Nothing added</h3>
      <p>No additives, no preservatives, no thickeners. Milk, culture, fruit — that's the whole list.</p>
    </div>
    <div class="why-card">
      <div class="badge" style="background:#E8156B"></div>
      <h3>Made to order</h3>
      <p>Every batch is cultured for your order, not for a shelf. That's why we ask for <?= $noticeHours ?> hours.</p>
    </div>
    <div class="why-card">
      <div class="badge" style="background:#C79A3A"></div>
      <h3>Chilled all the way</h3>
      <p>Packed cold and handed over cold, so the first sip tastes like the kitchen it left.</p>
    </div>
  </div>
</section>
<?php endif; ?>

<section id="order" class="order-section">
  <div class="order-panel" data-reveal="1">
    <div>
      <span class="kicker">How ordering works</span>
      <h2>Place your order at least <?= $noticeHours ?> hours ahead</h2>
      <p>Everything is made fresh, so we batch against confirmed orders. Send your list on WhatsApp, we confirm the total and delivery slot.</p>
      <a href="<?= h(wa_link($waOrders, 'Hello Swalaf! I\'d like to place an order.')) ?>" target="_blank" rel="noreferrer" class="btn-green js-outbound" data-label="Start an order" style="margin-top:28px">Start an order</a>
    </div>
    <div class="steps">
      <div class="step"><span class="num">01</span><div><div class="title">Send your list</div><div class="desc">Sizes, quantities, delivery area and date.</div></div></div>
      <div class="step"><span class="num">02</span><div><div class="title">We confirm &amp; batch</div><div class="desc">You get a total; we culture and pack it fresh.</div></div></div>
      <div class="step"><span class="num">03</span><div><div class="title">Chilled delivery</div><div class="desc">Delivered cold across <?= h($deliveryArea) ?>, or picked up at Lugbe.</div></div></div>
    </div>
  </div>
</section>

<?php if ($on('gallery')): ?>
<section class="wrap gallery-section">
  <div class="gallery-grid" data-reveal="1">
    <img src="assets/real1.jpg" alt="Swalaf yoghurt">
    <img src="assets/real2.jpg" alt="Swalaf parfait cups">
    <img src="assets/real3.jpg" alt="Swalaf bottles">
  </div>
</section>
<?php endif; ?>

<footer id="contact" class="site-footer">
  <div class="footer-grid">
    <div>
      <span class="footer-logo"><img src="assets/logo.png" alt="Swalaf Yoghurt &amp; Treats"></span>
      <p>Freshly made yoghurt, parfaits and Fruity Zobo. No additives, no preservatives. Lugbe, <?= h($deliveryArea) ?>.</p>
    </div>
    <div class="footer-col">
      <span class="heading">Order</span>
      <a href="<?= h(wa_link($waOrders)) ?>" target="_blank" rel="noreferrer" class="js-outbound" data-label="Footer WhatsApp orders">WhatsApp · <?= h($waOrders) ?></a>
      <a href="<?= h(wa_link($waEvents)) ?>" target="_blank" rel="noreferrer" class="js-outbound" data-label="Footer WhatsApp events">WhatsApp · <?= h($waEvents) ?></a>
      <a href="mailto:<?= h($email) ?>" class="js-outbound" data-label="Footer email"><?= h($email) ?></a>
    </div>
    <div class="footer-col">
      <span class="heading">Follow</span>
      <a href="<?= h($instagram) ?>" target="_blank" rel="noreferrer" class="js-outbound" data-label="Footer Instagram">Instagram · @swalafyoghurttreats</a>
      <a href="#menu">Full price list</a>
      <a href="#order">Delivery &amp; pickup</a>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© <?= date('Y') ?> Swalaf Yoghurt &amp; Treats · A gift from Aasiyah, by Abu Aasiyah ♥</span>
    <span>Website developed by <a href="https://wa.me/2347011401387" target="_blank" rel="noreferrer">Abu Aasiyah</a> — want one like this? Say hello on WhatsApp.</span>
  </div>
</footer>

<div class="chat-widget">
  <div class="chat-panel" id="chatPanel" hidden>
    <div class="head">
      <div class="row"><span class="dot-green"></span><span class="title">Swalaf Yoghurt &amp; Treats</span></div>
      <div class="sub">Online now · usually replies within an hour</div>
    </div>
    <div class="body">
      <div class="chat-bubble">Hi there ♥ Tell us what you'd like and the day you need it — we'll confirm your order right away.</div>
      <div class="chat-links">
        <span class="heading">Start a chat</span>
        <a class="chat-link js-outbound" data-label="Chat: place order" href="<?= h(wa_link($waOrders, 'Hello Swalaf! I\'d like to place an order.')) ?>" target="_blank" rel="noreferrer">
          <span><span class="t">Place an order</span><span class="s"><?= h($waOrders) ?> · WhatsApp</span></span><span class="arrow">→</span>
        </a>
        <a class="chat-link js-outbound" data-label="Chat: events" href="<?= h(wa_link($waEvents, 'Hello Swalaf! I\'d like to ask about an event order.')) ?>" target="_blank" rel="noreferrer">
          <span><span class="t">Events &amp; hampers</span><span class="s"><?= h($waEvents) ?> · WhatsApp</span></span><span class="arrow">→</span>
        </a>
        <a class="chat-link js-outbound" data-label="Chat: email" href="mailto:<?= h($email) ?>">
          <span><span class="t">Email us</span><span class="s"><?= h($email) ?></span></span><span class="arrow">→</span>
        </a>
      </div>
    </div>
  </div>
  <button type="button" class="chat-toggle" id="chatToggle"><span class="dot-green"></span><span id="chatToggleLabel">Chat with us</span></button>
</div>

<aside class="tray" id="tray" hidden>
  <div class="tray-head">
    <span class="title" id="trayCount">Your tray · 0 items</span>
    <button type="button" id="trayClear">Clear</button>
  </div>
  <div class="tray-items" id="trayItems"></div>
  <div class="tray-footer">
    <div class="total-row"><span class="label">Estimated total</span><span class="amount" id="trayTotal">₦0</span></div>
    <a href="#" id="trayWaLink" target="_blank" rel="noreferrer" class="btn-green js-outbound" data-label="Tray checkout">Send order on WhatsApp</a>
    <span class="footnote">Orders need <?= $noticeHours ?> hours notice · <?= h($deliveryArea) ?> delivery</span>
  </div>
</aside>

<script id="swalaf-data" type="application/json"><?= json_encode($dataForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script src="js/site.js"></script>
</body>
</html>
