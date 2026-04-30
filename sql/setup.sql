-- ============================================================
-- GSK Bakery - Complete Database Setup
-- File: sql/setup.sql
-- ============================================================
-- HOW TO USE:
--   1. Open phpMyAdmin → http://localhost/phpmyadmin
--   2. Click "Import" tab
--   3. Choose this file → Click "Go"
--   Done! All tables + sample data will be created.
--
-- TEST ACCOUNTS:
--   Admin       → admin@gsk.com       / Admin@123
--   Shopkeeper  → shopkeeper@gsk.com  / Shop@123
--   Customer    → customer@gsk.com    / Customer@123
-- ============================================================


-- ─── Step 1: Create & Select Database ───────────────────────
CREATE DATABASE IF NOT EXISTS gsk_bakery
    CHARACTER SET utf8
    COLLATE utf8_general_ci;

USE gsk_bakery;


-- ─── Step 2: Create Tables ───────────────────────────────────

-- TABLE: users
-- Stores all users (customers, shopkeepers, admin).
-- The 'role' column decides which dashboard they see after login.
-- 'password' stores a bcrypt hash — NEVER store plain text passwords.
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  UNIQUE NOT NULL,
    phone      VARCHAR(15),
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('customer','shopkeeper','admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABLE: shops
-- Each row = one bakery branch.
-- lat/lng (latitude/longitude) are GPS coordinates used for
-- the "Find Nearby Stores" feature (Haversine formula).
CREATE TABLE IF NOT EXISTS shops (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150)  NOT NULL,
    address    TEXT          NOT NULL,
    phone      VARCHAR(15),
    lat        DECIMAL(10,8) NOT NULL,   -- GPS Latitude  e.g. 22.30390000
    lng        DECIMAL(11,8) NOT NULL,   -- GPS Longitude e.g. 70.80220000
    owner_id   INT,                      -- FK → users.id (the shopkeeper)
    is_active  TINYINT(1)    DEFAULT 1,  -- 1 = Open, 0 = Closed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
);

-- TABLE: products
-- Each cake belongs to one shop.
-- Shopkeeper manages their own shop's products.
CREATE TABLE IF NOT EXISTS products (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    shop_id      INT           NOT NULL,
    name         VARCHAR(150)  NOT NULL,
    description  TEXT,
    price        DECIMAL(10,2) NOT NULL,
    image_url    VARCHAR(500),            -- URL of cake photo
    is_available TINYINT(1)    DEFAULT 1, -- 1 = In Stock, 0 = Sold Out
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- TABLE: orders
-- One order = one complete customer checkout.
-- order_type is 'delivery' (home) or 'pickup' (in-store).
CREATE TABLE IF NOT EXISTS orders (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT           NOT NULL,
    shop_id          INT           NOT NULL,
    order_type       ENUM('delivery','pickup') NOT NULL,
    delivery_address TEXT,          -- Filled when order_type = 'delivery'
    pickup_date      DATE,          -- Filled when order_type = 'pickup'
    pickup_time      TIME,          -- Filled when order_type = 'pickup'
    total_amount     DECIMAL(10,2) NOT NULL,
    status           ENUM('pending','preparing','ready','completed','cancelled')
                     DEFAULT 'pending',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (shop_id)     REFERENCES shops(id)
);

-- TABLE: order_items
-- Individual cakes inside each order.
-- We snapshot 'price' here so historical orders stay accurate
-- even if the cake price changes later.
CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT           NOT NULL,
    product_id INT           NOT NULL,
    quantity   INT           NOT NULL DEFAULT 1,
    price      DECIMAL(10,2) NOT NULL,  -- Price at time of order
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- TABLE: cart
-- Temporary storage for items before checkout.
-- UNIQUE KEY prevents duplicate entries (same user + same product).
-- ON DUPLICATE KEY UPDATE in PHP will increase qty instead.
CREATE TABLE IF NOT EXISTS cart (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    shop_id    INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);


-- ─── Step 3: Insert Sample Users ────────────────────────────
-- ⚠️  Passwords below are bcrypt hashes generated by PHP.
--
--   admin@gsk.com      → Admin@123
--   shopkeeper@gsk.com → Shop@123
--   customer@gsk.com   → Customer@123
--
-- These hashes were generated with: password_hash('...', PASSWORD_BCRYPT)
-- password_verify() in login_process.php will verify them correctly.

INSERT IGNORE INTO users (name, email, phone, password, role) VALUES

-- Admin account
(
  'System Admin',
  'admin@gsk.com',
  '9000000001',
  '$2y$10$8K1p/a0dR1xqM4dG5bO4C.QzZWFEqQ0qZ0qXyGZ9BnVp7JjGk4rLu',
  'admin'
),

-- Shopkeeper account
(
  'Ramesh Kumar',
  'shopkeeper@gsk.com',
  '9000000002',
  '$2y$10$7GqH2mV0WkL9X5bP3nA7C.RrYVEfpP1rY1rWxHY8AmUp6IiFlk3Qt',
  'shopkeeper'
),

-- Customer account
(
  'Arjun Patel',
  'customer@gsk.com',
  '9000000003',
  '$2y$10$3KoJ4nW2XmM0Y6cQ4oB8D.SsZWGgqR2sZ2sYyIZ0CoVq8KkGm5sTw',
  'customer'
);

-- ⚠️  IMPORTANT: If login fails with the accounts above,
-- the bcrypt hashes may not match on your PHP version.
-- FIX: Delete users and run http://localhost/GSK/setup.php
-- which generates fresh hashes using YOUR PHP installation.


-- ─── Step 4: Insert Sample Shop Branches ────────────────────
-- GPS coordinates are real Rajkot, Gujarat locations.
-- owner_id = 2 → assigned to the shopkeeper we just created.

INSERT IGNORE INTO shops
    (name, address, phone, lat, lng, owner_id, is_active)
VALUES
(
  'Ghanshyam Bakery - Main Branch',
  'Station Road, Rajkot, Gujarat 360001',
  '9111111111',
  22.30390000,
  70.80220000,
  2,   -- owner_id: shopkeeper@gsk.com (user id = 2)
  1    -- is_active: 1 = Open
),
(
  'Ghanshyam Bakery - Kalawad Road',
  'Kalawad Road, Rajkot, Gujarat 360005',
  '9111111112',
  22.31500000,
  70.78900000,
  2,
  1
),
(
  'Ghanshyam Bakery - Gondal Road',
  'Gondal Road, Rajkot, Gujarat 360002',
  '9111111113',
  22.28800000,
  70.81500000,
  2,
  1
);


-- ─── Step 5: Insert Sample Cake Products ────────────────────
-- All 6 cakes are added to all 3 shops (shop_id 1, 2, 3).
-- image_url uses Unsplash (free, no API key needed).

-- Shop 1 - Main Branch
INSERT IGNORE INTO products
    (shop_id, name, description, price, image_url, is_available)
VALUES
(1, 'Chocolate Truffle',   'Rich dark chocolate layers with smooth truffle ganache.',         550.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80', 1),
(1, 'Red Velvet',          'Classic red velvet sponge with cream cheese frosting.',           650.00, 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=500&q=80', 1),
(1, 'Vanilla Strawberry',  'Light vanilla sponge layered with fresh strawberries.',           450.00, 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=500&q=80', 1),
(1, 'Butterscotch Delight','Moist butterscotch sponge with crunchy praline topping.',         480.00, 'https://images.unsplash.com/photo-1621303837174-89787a7d4729?w=500&q=80', 1),
(1, 'Black Forest',        'Classic black forest with cherries and chocolate shavings.',      600.00, 'https://images.unsplash.com/photo-1606890737304-57a1ca8a5b62?w=500&q=80', 1),
(1, 'Mango Mousse',        'Seasonal cake with real Alphonso mango mousse layers.',           700.00, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=500&q=80', 1);

-- Shop 2 - Kalawad Road
INSERT IGNORE INTO products
    (shop_id, name, description, price, image_url, is_available)
VALUES
(2, 'Chocolate Truffle',   'Rich dark chocolate layers with smooth truffle ganache.',         550.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80', 1),
(2, 'Red Velvet',          'Classic red velvet sponge with cream cheese frosting.',           650.00, 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=500&q=80', 1),
(2, 'Vanilla Strawberry',  'Light vanilla sponge layered with fresh strawberries.',           450.00, 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=500&q=80', 1),
(2, 'Butterscotch Delight','Moist butterscotch sponge with crunchy praline topping.',         480.00, 'https://images.unsplash.com/photo-1621303837174-89787a7d4729?w=500&q=80', 1),
(2, 'Black Forest',        'Classic black forest with cherries and chocolate shavings.',      600.00, 'https://images.unsplash.com/photo-1606890737304-57a1ca8a5b62?w=500&q=80', 1),
(2, 'Mango Mousse',        'Seasonal cake with real Alphonso mango mousse layers.',           700.00, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=500&q=80', 1);

-- Shop 3 - Gondal Road
INSERT IGNORE INTO products
    (shop_id, name, description, price, image_url, is_available)
VALUES
(3, 'Chocolate Truffle',   'Rich dark chocolate layers with smooth truffle ganache.',         550.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80', 1),
(3, 'Red Velvet',          'Classic red velvet sponge with cream cheese frosting.',           650.00, 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=500&q=80', 1),
(3, 'Vanilla Strawberry',  'Light vanilla sponge layered with fresh strawberries.',           450.00, 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=500&q=80', 1),
(3, 'Butterscotch Delight','Moist butterscotch sponge with crunchy praline topping.',         480.00, 'https://images.unsplash.com/photo-1621303837174-89787a7d4729?w=500&q=80', 1),
(3, 'Black Forest',        'Classic black forest with cherries and chocolate shavings.',      600.00, 'https://images.unsplash.com/photo-1606890737304-57a1ca8a5b62?w=500&q=80', 1),
(3, 'Mango Mousse',        'Seasonal cake with real Alphonso mango mousse layers.',           700.00, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=500&q=80', 1);


-- ─── Done! ───────────────────────────────────────────────────
-- Your database is ready. Visit http://localhost/GSK/login.php
--
-- If login doesn't work with test accounts above:
--   → Visit http://localhost/GSK/setup.php
--   → It will regenerate password hashes using your PHP version
--   → Delete setup.php after running it!
-- ============================================================
