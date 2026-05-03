USE gsk_bakery;

-- Create variants table
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    weight_label VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_variant (product_id, weight_label)
);

-- Add column to products to indicate if variants are enabled
ALTER TABLE products ADD COLUMN has_variants TINYINT(1) DEFAULT 0;

-- Drop cart table and recreate it
DROP TABLE IF EXISTS cart;

CREATE TABLE IF NOT EXISTS cart (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    shop_id    INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    variant_weight VARCHAR(50) DEFAULT '',
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item_variant (user_id, product_id, variant_weight),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Add variant_weight to order_items
ALTER TABLE order_items ADD COLUMN variant_weight VARCHAR(50) DEFAULT '';
