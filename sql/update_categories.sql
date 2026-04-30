USE gsk_bakery;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category (shop_id, name)
);

-- Adding category_id to products
ALTER TABLE products 
ADD COLUMN category_id INT NULL AFTER name;

-- Adding foreign key
ALTER TABLE products
ADD CONSTRAINT fk_product_category
FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

-- Setup sample category
INSERT IGNORE INTO categories (shop_id, name) SELECT id, 'Cakes' FROM shops;
UPDATE products p JOIN categories c ON p.shop_id = c.shop_id AND c.name = 'Cakes' SET p.category_id = c.id;