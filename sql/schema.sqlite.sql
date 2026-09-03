-- SQLite version of schema.sql, used only for local development/testing
-- (DB_DRIVER=sqlite). Production on cPanel always uses schema.sql (MySQL).

CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(80) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'owner',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(40) NOT NULL,
    image VARCHAR(255) NOT NULL,
    description TEXT,
    sold_30d INTEGER NOT NULL DEFAULT 0,
    is_live INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS product_sizes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    label VARCHAR(40) NOT NULL,
    price INTEGER NOT NULL,
    min_qty INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind VARCHAR(20) NOT NULL DEFAULT 'order',
    customer_name VARCHAR(120) NOT NULL DEFAULT 'Website visitor',
    phone VARCHAR(40) NOT NULL DEFAULT '',
    items_json TEXT NOT NULL,
    items_summary VARCHAR(255) NOT NULL DEFAULT '',
    total INTEGER NOT NULL DEFAULT 0,
    due_text VARCHAR(60) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'New',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    name VARCHAR(60) PRIMARY KEY,
    value TEXT
);

CREATE TABLE IF NOT EXISTS sections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL,
    note VARCHAR(160) NOT NULL DEFAULT '',
    is_on INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS hero_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    image VARCHAR(255) NOT NULL,
    label VARCHAR(80) NOT NULL,
    kicker VARCHAR(120) NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(120) NOT NULL,
    initials VARCHAR(4) NOT NULL DEFAULT '',
    channel VARCHAR(30) NOT NULL DEFAULT 'WhatsApp',
    body TEXT NOT NULL,
    href VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pageviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    path VARCHAR(255) NOT NULL DEFAULT '/',
    referrer VARCHAR(500) NOT NULL DEFAULT '',
    device VARCHAR(20) NOT NULL DEFAULT 'Desktop',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS clicks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    label VARCHAR(80) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO settings (name, value) VALUES
    ('banner', 'Order 24hrs ahead · Free delivery in Lugbe on orders over ₦20,000'),
    ('seo_title', 'Swalaf Yoghurt & Treats — Freshly Made Yoghurt, Parfait & Zobo in Abuja'),
    ('seo_desc', 'Thick, creamy yoghurt with no additives or preservatives. Greek yoghurt, parfaits, kids packs and Fruity Zobo, made to order and delivered chilled across Abuja.'),
    ('notice_hours', '24'),
    ('event_notice_hours', '48'),
    ('delivery_area', 'Abuja'),
    ('hero_style', 'Split photo'),
    ('whatsapp_orders', '+2348066146476'),
    ('whatsapp_events', '+2349038664560'),
    ('contact_email', 'romlah0008@gmail.com'),
    ('instagram_url', 'https://www.instagram.com/swalafyoghurt?igsi=eGdkcGRocGQ0Zm44'),
    ('business_name', 'Swalaf Yoghurt & Treats');

INSERT INTO products (slug, name, category, image, description, sold_30d, is_live, sort_order) VALUES
    ('plain',   'Plain Yoghurt',    'Yoghurt', 'assets/plain.jpg',   'The house classic — thick, tangy, drinkable. Chilled in a screw-cap bottle.', 142, 1, 1),
    ('coconut', 'Coconut Infused',  'Yoghurt', 'assets/coconut.jpg', 'Real coconut folded through the same creamy base. Lightly sweet, deeply rich.', 96, 1, 2),
    ('greek',   'Greek Yoghurt',    'Greek',   'assets/greek.jpg',   'Strained thick enough to hold a spoon. Sold by the tub for the whole house.', 71, 1, 3),
    ('parfait', 'Parfait',          'Parfait', 'assets/parfait.jpg', 'Layers of yoghurt, crunchy granola and fresh fruit in a lidded cup.', 188, 1, 4),
    ('kids',    'Kids Pack 100ml',  'Kids',    'assets/kids.jpg',    'Spouted pouches sized for lunchboxes. Sold in packs, priced per pack.', 64, 1, 5),
    ('zobo35',  'Fruity Zobo 35cl', 'Zobo',    'assets/zobo.jpg',    'Hibiscus steeped with real fruit and spice. Deep ruby, properly cold.', 110, 1, 6),
    ('zobo50',  'Fruity Zobo 50cl', 'Zobo',    'assets/zobo12.jpg',  'The bigger bottle, for events and long weekends. Same recipe, more of it.', 38, 0, 7);

INSERT INTO product_sizes (product_id, label, price, min_qty, sort_order) VALUES
    ((SELECT id FROM products WHERE slug='plain'),   '35cl', 2000, 1, 1),
    ((SELECT id FROM products WHERE slug='plain'),   '50cl', 3000, 1, 2),
    ((SELECT id FROM products WHERE slug='coconut'), '35cl', 2500, 1, 1),
    ((SELECT id FROM products WHERE slug='coconut'), '50cl', 3000, 1, 2),
    ((SELECT id FROM products WHERE slug='greek'),   '300ml', 3300, 1, 1),
    ((SELECT id FROM products WHERE slug='greek'),   '500ml', 5000, 1, 2),
    ((SELECT id FROM products WHERE slug='greek'),   '1 litre', 9500, 1, 3),
    ((SELECT id FROM products WHERE slug='parfait'), '150ml', 2100, 5, 1),
    ((SELECT id FROM products WHERE slug='parfait'), '250ml', 3900, 2, 2),
    ((SELECT id FROM products WHERE slug='parfait'), '500ml', 8500, 1, 3),
    ((SELECT id FROM products WHERE slug='kids'),    'Pack of 6', 5500, 1, 1),
    ((SELECT id FROM products WHERE slug='kids'),    'Pack of 12', 9500, 1, 2),
    ((SELECT id FROM products WHERE slug='zobo35'),  'Pack of 6', 4600, 1, 1),
    ((SELECT id FROM products WHERE slug='zobo35'),  'Pack of 12', 9000, 1, 2),
    ((SELECT id FROM products WHERE slug='zobo50'),  'Pack of 6', 7600, 1, 1),
    ((SELECT id FROM products WHERE slug='zobo50'),  'Pack of 12', 15000, 1, 2);

INSERT INTO sections (slug, name, note, is_on, sort_order) VALUES
    ('hero',    'Hero slider',       '4 photos · headline and buttons',   1, 1),
    ('menu',    'Price list',        '7 products, filter chips',          1, 2),
    ('bundles', 'Bundles',           'Zobo crate, kids pack, parfait',    1, 3),
    ('events',  'Events & requests', 'Guest picker, quote request',       1, 4),
    ('why',     'Why Swalaf',        'Three promises',                    1, 5),
    ('gallery', 'Photo gallery',     '3 photos',                          0, 6);

INSERT INTO hero_images (image, label, kicker, sort_order) VALUES
    ('assets/hero.jpg',    'Full range', 'The full range',  1),
    ('assets/parfait.jpg', 'Parfait',    'Layered parfait',  2),
    ('assets/zobo.jpg',    'Fruity Zobo','Fruity Zobo',      3),
    ('assets/greek.jpg',   'Greek',      'Greek Yoghurt',    4);

INSERT INTO messages (name, initials, channel, body, href, created_at) VALUES
    ('Aisha Bello',  'AB', 'WhatsApp',  'Do you deliver to Gwarinpa on Saturday morning?', 'https://wa.me/2348066146476', datetime('now','-4 minutes')),
    ('Kemi Alabi',   'KA', 'Instagram', 'Can I get 20 parfaits for a bridal shower?',       'https://www.instagram.com/swalafyoghurt', datetime('now','-1 hours')),
    ('Musa Ibrahim', 'MI', 'WhatsApp',  'Is the Greek yoghurt available in 1 litre today?', 'https://wa.me/2348066146476', datetime('now','-3 hours')),
    ('Ngozi Eze',    'NE', 'Email',     'Corporate hampers for 40 staff — please send options.', 'mailto:romlah0008@gmail.com', datetime('now','-1 days')),
    ('Sadiq Umar',   'SU', 'WhatsApp',  'Zobo pack of 12 for Sunday, can I pay on delivery?', 'https://wa.me/2349038664560', datetime('now','-1 days'));

INSERT INTO orders (kind, customer_name, phone, items_json, items_summary, total, due_text, status, created_at) VALUES
    ('order', 'Aisha Bello',   '0803 442 1180', '[]', '6 × Parfait 250ml, 2 × Plain 50cl',        29400, 'Tomorrow, 2pm', 'New',       datetime('now','-2 hours')),
    ('order', 'Tunde Adeyemi', '0810 776 2094', '[]', 'Kids Pack of 12, Zobo pack of 6 (35cl)',   14100, 'Fri, 10am',     'Confirmed', datetime('now','-5 hours')),
    ('order', 'Grace Okon',    '0705 331 8842', '[]', 'Greek 1L, Greek 500ml',                    14500, 'Today, 5pm',    'Batching',  datetime('now','-1 days')),
    ('order', 'Hauwa Sani',    '0902 118 6633', '[]', '12 × Zobo 50cl (event)',                   15000, 'Sat, 12pm',     'Confirmed', datetime('now','-1 days')),
    ('order', 'Chidi Nwosu',   '0816 220 4471', '[]', '4 × Coconut 35cl',                         10000, 'Delivered',     'Delivered', datetime('now','-3 days')),
    ('order', 'Fatima Yusuf',  '0807 909 5512', '[]', 'Parfait 150ml × 5, Plain 35cl × 3',        16500, 'Delivered',     'Delivered', datetime('now','-4 days'));
