-- ============================================================
-- GSK Bakery - FINAL COMPLETE DATABASE SETUP
-- Version: 2.0 (Includes Variants & Categories)
-- File: sql/final_setup.sql
-- ============================================================

-- ─── Step 1: Create & Select Database ───────────────────────
CREATE DATABASE IF NOT EXISTS gsk_bakery
    CHARACTER SET utf8
    COLLATE utf8_general_ci;

USE gsk_bakery;

-- ─── Step 2: Create Tables ───────────────────────────────────

-- TABLE: users
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
CREATE TABLE IF NOT EXISTS categories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    shop_id    INT           NOT NULL,
    name       VARCHAR(100)  NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category (shop_id, name)
);

-- TABLE: products
CREATE TABLE IF NOT EXISTS products (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    shop_id      INT           NOT NULL,
    name         VARCHAR(150)  NOT NULL,
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
CREATE TABLE IF NOT EXISTS product_variants (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    product_id    INT           NOT NULL,
    weight_label  VARCHAR(50)   NOT NULL,
    price         DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_variant (product_id, weight_label)
);

-- TABLE: orders
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

-- ─── Step 3: Insert Sample Data ─────────────────────────────

INSERT IGNORE INTO users (name, email, phone, password, role) VALUES
('System Admin', 'admin@gsk.com', '9000000001', '$2y$10$8K1p/a0dR1xqM4dG5bO4C.QzZWFEqQ0qZ0qXyGZ9BnVp7JjGk4rLu', 'admin'),
('Ramesh Kumar', 'shopkeeper@gsk.com', '9000000002', '$2y$10$7GqH2mV0WkL9X5bP3nA7C.RrYVEfpP1rY1rWxHY8AmUp6IiFlk3Qt', 'shopkeeper'),
('Arjun Patel', 'customer@gsk.com', '9000000003', '$2y$10$3KoJ4nW2XmM0Y6cQ4oB8D.SsZWGgqR2sZ2sYyIZ0CoVq8KkGm5sTw', 'customer');

INSERT IGNORE INTO shops (name, address, phone, lat, lng, owner_id, is_active) VALUES
('Ghanshyam Bakery - Main Branch', 'Station Road, Rajkot, Gujarat 360001', '9111111111', 22.30390000, 70.80220000, 2, 1),
('Ghanshyam Bakery - Kalawad Road', 'Kalawad Road, Rajkot, Gujarat 360005', '9111111112', 22.31500000, 70.78900000, 2, 1),
('Ghanshyam Bakery - Gondal Road', 'Gondal Road, Rajkot, Gujarat 360002', '9111111113', 22.28800000, 70.81500000, 2, 1);

INSERT IGNORE INTO categories (shop_id, name) VALUES
(1, 'Cakes'), (1, 'Pastries'),
(2, 'Cakes'), (2, 'Cookies'),
(3, 'Cakes'), (3, 'Donuts');

INSERT IGNORE INTO products (shop_id, category_id, name, description, price, image_url, is_available) VALUES
(1, 1, 'Chocolate Truffle', 'Rich dark chocolate layers with smooth truffle ganache.', 550.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80', 1),
(1, 1, 'Red Velvet', 'Classic red velvet sponge with cream cheese frosting.', 650.00, 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=500&q=80', 1),
(1, 2, 'Strawberry Pastry', 'Light vanilla sponge with fresh strawberries.', 80.00, 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=500&q=80', 1);

-- ============================================================
