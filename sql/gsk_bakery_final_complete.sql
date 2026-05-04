-- ============================================================
-- GSK BAKERY - FINAL COMPLETE DATABASE SETUP
-- Version: 2.0 (Complete with Variants, Categories & Flavor)
-- Last Updated: May 2026
-- ============================================================
--
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
--
-- MIGRATION NOTES (For Existing Databases):
--   If you already have GSK Bakery installed and want to add
--   the Flavor feature to an existing database, run:
--     ALTER TABLE products
--     ADD COLUMN flavor VARCHAR(100) NULL AFTER name;
--   This will add the flavor column to existing products.
--
-- ============================================================


-- ────────────────────────────────────────────────────────────
-- STEP 1: Create & Select Database
-- ────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS gsk_bakery
    CHARACTER SET utf8
    COLLATE utf8_general_ci;

USE gsk_bakery;


-- ────────────────────────────────────────────────────────────
-- STEP 2: Create All Tables
-- ────────────────────────────────────────────────────────────

-- TABLE: users
-- Stores all users: customers, shopkeepers, admin.
-- The 'role' column determines which dashboard they access.
-- Password stores bcrypt hash — NEVER store plain text passwords.
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
-- Each row represents one bakery branch.
-- lat/lng store GPS coordinates for "Find Nearby Stores" feature.
-- owner_id links to the shopkeeper managing this shop.
CREATE TABLE IF NOT EXISTS shops (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150)  NOT NULL,
    address    TEXT          NOT NULL,
    phone      VARCHAR(15),
    lat        DECIMAL(10,8) NOT NULL,
    lng        DECIMAL(11,8) NOT NULL,
    owner_id   INT,
    is_active  TINYINT(1)    DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
);

-- TABLE: categories
-- Shopkeepers organize their products by category.
-- Each shop can have multiple categories (e.g., Cakes, Pastries).
-- UNIQUE KEY ensures no duplicate category names per shop.
CREATE TABLE IF NOT EXISTS categories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    shop_id    INT           NOT NULL,
    name       VARCHAR(100)  NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category (shop_id, name)
);

-- TABLE: products
-- Each cake/product belongs to one shop and one category.
-- Flavor: Optional text field describing the cake flavor (e.g., Chocolate, Mango).
-- has_variants: If 1, product has multiple weight/price options (See product_variants table).
-- is_available: 1 = In Stock, 0 = Sold Out.
CREATE TABLE IF NOT EXISTS products (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    shop_id      INT           NOT NULL,
    name         VARCHAR(150)  NOT NULL,
    flavor       VARCHAR(100),
    category_id  INT           NULL,
    description  TEXT,
    price        DECIMAL(10,2) NOT NULL,
    image_url    VARCHAR(500),
    is_available TINYINT(1)    DEFAULT 1,
    has_variants TINYINT(1)    DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id)     REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- TABLE: product_variants
-- Allows products to have multiple weight/price combinations.
-- E.g., "Chocolate Truffle" may have: 500g @ ₹550, 1kg @ ₹950, 2kg @ ₹1800.
-- UNIQUE KEY ensures no duplicate weight options per product.
CREATE TABLE IF NOT EXISTS product_variants (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    product_id    INT           NOT NULL,
    weight_label  VARCHAR(50)   NOT NULL,
    price         DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_variant (product_id, weight_label)
);

-- TABLE: orders
-- One order = one complete customer checkout.
-- order_type: 'delivery' (home) or 'pickup' (in-store).
-- status tracks order progression: pending → preparing → ready → completed.
CREATE TABLE IF NOT EXISTS orders (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT           NOT NULL,
    shop_id          INT           NOT NULL,
    order_type       ENUM('delivery','pickup') NOT NULL,
    delivery_address TEXT,
    pickup_date      DATE,
    pickup_time      TIME,
    total_amount     DECIMAL(10,2) NOT NULL,
    status           ENUM('pending','preparing','ready','completed','cancelled')
                     DEFAULT 'pending',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (shop_id)     REFERENCES shops(id)
);

-- TABLE: order_items
-- Individual products inside each order.
-- We snapshot 'price' here so historical orders stay accurate
-- even if product prices change later.
-- variant_weight stores the selected weight option (if any).
CREATE TABLE IF NOT EXISTS order_items (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT           NOT NULL,
    product_id     INT           NOT NULL,
    quantity       INT           NOT NULL DEFAULT 1,
    price          DECIMAL(10,2) NOT NULL,
    variant_weight VARCHAR(50)   DEFAULT '',
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- TABLE: cart
-- Temporary storage for items before checkout.
-- UNIQUE KEY prevents duplicate entries.
-- PHP code uses: ON DUPLICATE KEY UPDATE quantity = quantity + new_quantity
CREATE TABLE IF NOT EXISTS cart (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT           NOT NULL,
    product_id     INT           NOT NULL,
    shop_id        INT           NOT NULL,
    quantity       INT           NOT NULL DEFAULT 1,
    variant_weight VARCHAR(50)   DEFAULT '',
    added_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item_variant (user_id, product_id, variant_weight),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);


-- ────────────────────────────────────────────────────────────
-- STEP 3: Insert Sample Users
-- ────────────────────────────────────────────────────────────
-- ⚠️  Passwords are bcrypt hashes. To regenerate them:
--     1. Visit http://localhost/GSK/setup.php
--     2. It will create fresh hashes using your PHP version
--     3. Delete setup.php after running

INSERT IGNORE INTO users (name, email, phone, password, role) VALUES
('System Admin', 'admin@gsk.com', '9000000001', '$2y$10$8K1p/a0dR1xqM4dG5bO4C.QzZWFEqQ0qZ0qXyGZ9BnVp7JjGk4rLu', 'admin'),
('Ramesh Kumar', 'shopkeeper@gsk.com', '9000000002', '$2y$10$7GqH2mV0WkL9X5bP3nA7C.RrYVEfpP1rY1rWxHY8AmUp6IiFlk3Qt', 'shopkeeper'),
('Arjun Patel', 'customer@gsk.com', '9000000003', '$2y$10$3KoJ4nW2XmM0Y6cQ4oB8D.SsZWGgqR2sZ2sYyIZ0CoVq8KkGm5sTw', 'customer');


-- ────────────────────────────────────────────────────────────
-- STEP 4: Insert Sample Shops
-- ────────────────────────────────────────────────────────────
-- All shops are owned by shopkeeper@gsk.com (user_id = 2)
-- GPS coordinates are real Rajkot, Gujarat locations.

INSERT IGNORE INTO shops (name, address, phone, lat, lng, owner_id, is_active) VALUES
('Ghanshyam Bakery - Main Branch', 'Station Road, Rajkot, Gujarat 360001', '9111111111', 22.30390000, 70.80220000, 2, 1),
('Ghanshyam Bakery - Kalawad Road', 'Kalawad Road, Rajkot, Gujarat 360005', '9111111112', 22.31500000, 70.78900000, 2, 1),
('Ghanshyam Bakery - Gondal Road', 'Gondal Road, Rajkot, Gujarat 360002', '9111111113', 22.28800000, 70.81500000, 2, 1);


-- ────────────────────────────────────────────────────────────
-- STEP 5: Insert Sample Categories
-- ────────────────────────────────────────────────────────────

INSERT IGNORE INTO categories (shop_id, name) VALUES
(1, 'Cakes'),
(1, 'Pastries'),
(2, 'Cakes'),
(2, 'Cookies'),
(3, 'Cakes'),
(3, 'Donuts');


-- ────────────────────────────────────────────────────────────
-- STEP 6: Insert Sample Products (with Flavor)
-- ────────────────────────────────────────────────────────────
-- All 6 cake types are added to all 3 shops.
-- Each product includes the NEW 'flavor' column with the cake flavor.
-- Images use Unsplash (free, no API key needed).

-- SHOP 1 - Main Branch
INSERT IGNORE INTO products (shop_id, category_id, name, flavor, description, price, image_url, is_available) VALUES
(1, 1, 'Chocolate Truffle', 'Chocolate', 'Rich dark chocolate layers with smooth truffle ganache.', 550.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80', 1),
(1, 1, 'Red Velvet', 'Red Velvet', 'Classic red velvet sponge with cream cheese frosting.', 650.00, 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=500&q=80', 1),
(1, 1, 'Vanilla Strawberry', 'Vanilla Strawberry', 'Light vanilla sponge layered with fresh strawberries.', 450.00, 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=500&q=80', 1),
(1, 1, 'Butterscotch Delight', 'Butterscotch', 'Moist butterscotch sponge with crunchy praline topping.', 480.00, 'https://images.unsplash.com/photo-1621303837174-89787a7d4729?w=500&q=80', 1),
(1, 1, 'Black Forest', 'Black Forest', 'Classic black forest with cherries and chocolate shavings.', 600.00, 'https://images.unsplash.com/photo-1606890737304-57a1ca8a5b62?w=500&q=80', 1),
(1, 2, 'Mango Mousse', 'Mango', 'Seasonal cake with real Alphonso mango mousse layers.', 700.00, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=500&q=80', 1);

-- SHOP 2 - Kalawad Road
INSERT IGNORE INTO products (shop_id, category_id, name, flavor, description, price, image_url, is_available) VALUES
(2, 3, 'Chocolate Truffle', 'Chocolate', 'Rich dark chocolate layers with smooth truffle ganache.', 550.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80', 1),
(2, 3, 'Red Velvet', 'Red Velvet', 'Classic red velvet sponge with cream cheese frosting.', 650.00, 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=500&q=80', 1),
(2, 3, 'Vanilla Strawberry', 'Vanilla Strawberry', 'Light vanilla sponge layered with fresh strawberries.', 450.00, 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=500&q=80', 1),
(2, 3, 'Butterscotch Delight', 'Butterscotch', 'Moist butterscotch sponge with crunchy praline topping.', 480.00, 'https://images.unsplash.com/photo-1621303837174-89787a7d4729?w=500&q=80', 1),
(2, 3, 'Black Forest', 'Black Forest', 'Classic black forest with cherries and chocolate shavings.', 600.00, 'https://images.unsplash.com/photo-1606890737304-57a1ca8a5b62?w=500&q=80', 1),
(2, 4, 'Mango Mousse', 'Mango', 'Seasonal cake with real Alphonso mango mousse layers.', 700.00, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=500&q=80', 1);

-- SHOP 3 - Gondal Road
INSERT IGNORE INTO products (shop_id, category_id, name, flavor, description, price, image_url, is_available) VALUES
(3, 5, 'Chocolate Truffle', 'Chocolate', 'Rich dark chocolate layers with smooth truffle ganache.', 550.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80', 1),
(3, 5, 'Red Velvet', 'Red Velvet', 'Classic red velvet sponge with cream cheese frosting.', 650.00, 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=500&q=80', 1),
(3, 5, 'Vanilla Strawberry', 'Vanilla Strawberry', 'Light vanilla sponge layered with fresh strawberries.', 450.00, 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=500&q=80', 1),
(3, 5, 'Butterscotch Delight', 'Butterscotch', 'Moist butterscotch sponge with crunchy praline topping.', 480.00, 'https://images.unsplash.com/photo-1621303837174-89787a7d4729?w=500&q=80', 1),
(3, 5, 'Black Forest', 'Black Forest', 'Classic black forest with cherries and chocolate shavings.', 600.00, 'https://images.unsplash.com/photo-1606890737304-57a1ca8a5b62?w=500&q=80', 1),
(3, 6, 'Mango Mousse', 'Mango', 'Seasonal cake with real Alphonso mango mousse layers.', 700.00, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=500&q=80', 1);


-- ────────────────────────────────────────────────────────────
-- STEP 7: Insert Sample Product Variants (Weight-based Pricing)
-- ────────────────────────────────────────────────────────────
-- Demonstrates how customers can choose cake sizes/weights
-- Example: Chocolate Truffle available in 500g @ ₹550, 1kg @ ₹950, 2kg @ ₹1800

-- Product ID 1: Chocolate Truffle (Shop 1)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(1, '500g', 550.00),
(1, '1kg', 950.00),
(1, '2kg', 1800.00);

-- Product ID 2: Red Velvet (Shop 1)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(2, '500g', 650.00),
(2, '1kg', 1100.00),
(2, '2kg', 2000.00);

-- Product ID 3: Vanilla Strawberry (Shop 1)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(3, '500g', 450.00),
(3, '1kg', 800.00),
(3, '2kg', 1500.00);

-- Product ID 4: Butterscotch Delight (Shop 1)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(4, '500g', 480.00),
(4, '1kg', 850.00),
(4, '2kg', 1600.00);

-- Product ID 5: Black Forest (Shop 1)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(5, '500g', 600.00),
(5, '1kg', 1050.00),
(5, '2kg', 1950.00);

-- Product ID 6: Mango Mousse (Shop 1)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(6, '500g', 700.00),
(6, '1kg', 1200.00),
(6, '2kg', 2200.00);

-- Product ID 7: Chocolate Truffle (Shop 2)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(7, '500g', 550.00),
(7, '1kg', 950.00),
(7, '2kg', 1800.00);

-- Product ID 8: Red Velvet (Shop 2)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(8, '500g', 650.00),
(8, '1kg', 1100.00),
(8, '2kg', 2000.00);

-- Product ID 9: Vanilla Strawberry (Shop 2)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(9, '500g', 450.00),
(9, '1kg', 800.00),
(9, '2kg', 1500.00);

-- Product ID 10: Butterscotch Delight (Shop 2)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(10, '500g', 480.00),
(10, '1kg', 850.00),
(10, '2kg', 1600.00);

-- Product ID 11: Black Forest (Shop 2)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(11, '500g', 600.00),
(11, '1kg', 1050.00),
(11, '2kg', 1950.00);

-- Product ID 12: Mango Mousse (Shop 2)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(12, '500g', 700.00),
(12, '1kg', 1200.00),
(12, '2kg', 2200.00);

-- Product ID 13: Chocolate Truffle (Shop 3)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(13, '500g', 550.00),
(13, '1kg', 950.00),
(13, '2kg', 1800.00);

-- Product ID 14: Red Velvet (Shop 3)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(14, '500g', 650.00),
(14, '1kg', 1100.00),
(14, '2kg', 2000.00);

-- Product ID 15: Vanilla Strawberry (Shop 3)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(15, '500g', 450.00),
(15, '1kg', 800.00),
(15, '2kg', 1500.00);

-- Product ID 16: Butterscotch Delight (Shop 3)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(16, '500g', 480.00),
(16, '1kg', 850.00),
(16, '2kg', 1600.00);

-- Product ID 17: Black Forest (Shop 3)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(17, '500g', 600.00),
(17, '1kg', 1050.00),
(17, '2kg', 1950.00);

-- Product ID 18: Mango Mousse (Shop 3)
INSERT IGNORE INTO product_variants (product_id, weight_label, price) VALUES
(18, '500g', 700.00),
(18, '1kg', 1200.00),
(18, '2kg', 2200.00);


-- ============================================================
-- SETUP COMPLETE! ✓
-- ============================================================
--
-- FEATURES INCLUDED:
--   ✓ User authentication (customers, shopkeepers, admins)
--   ✓ Multi-shop support with GPS location
--   ✓ Product catalog with categories
--   ✓ Weight-based variants (500g, 1kg, 2kg options)
--   ✓ Flavor tracking (NEW - May 2026)
--   ✓ Shopping cart with variant support
--   ✓ Delivery & Pickup orders
--   ✓ Order history & analytics
--
-- ============================================================
