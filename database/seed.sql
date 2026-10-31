-- 1. Insert the User
INSERT IGNORE INTO users (id, name, email, password)
VALUES (1, 'Test User', 'test@example.com', 'hash');

-- 2. Insert a Brand (Required to satisfy the brand_id foreign key)
INSERT IGNORE INTO brands (id, name)
VALUES (1, 'Test Brand');

-- 3. Insert the Product (Now including the mandatory brand_id and slug)
INSERT IGNORE INTO products (id, brand_id, name, slug, description, is_active)
VALUES (1, 1, 'Vanilla Sunset Eau de Parfum', 'vanilla-sunset', 'A warm vanilla fragrance.', 1);

-- 4. Insert the Variant
INSERT IGNORE INTO product_variants (id, product_id, price, stock)
VALUES (1, 1, 75.00, 50);

INSERT INTO product_variants (id, product_id, size_ml, price, stock)
VALUES (1, 1, 50, 75.00, 50);
SELECT CHECK_CLAUSE
FROM information_schema.CHECK_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'perfume_ecommerce'
  AND TABLE_NAME = 'product_variants'
  AND CONSTRAINT_NAME = 'CONSTRAINT_1';
-- 5. Insert the Order (Must be 'delivered' for the review API to allow it)
INSERT IGNORE INTO orders (id, user_id, status)
VALUES (1, 1, 'delivered');

-- 6. Insert the Order Item
INSERT IGNORE INTO order_items (order_id, variant_id, quantity)
VALUES (1, 1, 1);
