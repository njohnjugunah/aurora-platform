-- E-Commerce Enhancement - Phase 4
-- Product catalog, inventory, and checkout system

-- Product Images Gallery
CREATE TABLE IF NOT EXISTS product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_primary (is_primary),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Product Variants (Size, Color, etc.)
CREATE TABLE IF NOT EXISTS product_variants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    barcode VARCHAR(100),
    size VARCHAR(50),
    color VARCHAR(50),
    stock INT DEFAULT 0,
    price DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_sku (sku),
    INDEX idx_stock (stock),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Product Reviews and Ratings
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating INT NOT NULL COMMENT '1-5 stars',
    title VARCHAR(255),
    comment TEXT,
    verified_purchase BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    unhelpful_count INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_customer (customer_id),
    INDEX idx_rating (rating),
    INDEX idx_status (status),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Customer Wishlist
CREATE TABLE IF NOT EXISTS wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wish (customer_id, product_id),
    INDEX idx_customer (customer_id),
    INDEX idx_product (product_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Discount and Coupon System
CREATE TABLE IF NOT EXISTS coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    max_uses INT,
    current_uses INT DEFAULT 0,
    min_order_amount DECIMAL(10,2),
    applicable_products VARCHAR(500) COMMENT 'JSON array of product IDs',
    applicable_categories VARCHAR(500) COMMENT 'JSON array of category IDs',
    start_date DATETIME,
    end_date DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Cart Items (Temporary shopping cart)
CREATE TABLE IF NOT EXISTS cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT,
    quantity INT NOT NULL DEFAULT 1,
    price_at_time DECIMAL(10,2),
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME COMMENT 'Cart item auto-remove after 30 days',
    INDEX idx_customer (customer_id),
    INDEX idx_product (product_id),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

-- Enhanced Orders Table
ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(50);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_method VARCHAR(50);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_address TEXT;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_cost DECIMAL(10,2) DEFAULT 0;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(100);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_date DATE;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS notes TEXT;

-- Order Items (Line items in order)
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT,
    quantity INT NOT NULL,
    price_per_item DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

-- Shipping Options
CREATE TABLE IF NOT EXISTS shipping_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    base_cost DECIMAL(10,2) NOT NULL,
    per_kg_cost DECIMAL(10,2) DEFAULT 0,
    max_days INT COMMENT 'Expected delivery days',
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
);

-- Stock History (Audit trail)
CREATE TABLE IF NOT EXISTS stock_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    variant_id INT,
    old_stock INT,
    new_stock INT,
    change_amount INT,
    reason VARCHAR(100) COMMENT 'sale, return, adjustment, restock',
    reference_id INT COMMENT 'order_id or other reference',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_reason (reason),
    INDEX idx_date (created_at),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Low Stock Alerts
CREATE TABLE IF NOT EXISTS low_stock_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    variant_id INT,
    alert_threshold INT NOT NULL,
    current_stock INT,
    is_sent BOOLEAN DEFAULT FALSE,
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_alert (product_id, variant_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

-- Featured Products (For homepage display)
CREATE TABLE IF NOT EXISTS featured_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    featured_until DATETIME,
    section VARCHAR(100) COMMENT 'homepage, bestsellers, new_arrivals',
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product_section (product_id, section),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Product Metadata
ALTER TABLE products ADD COLUMN IF NOT EXISTS weight_kg DECIMAL(8,3);
ALTER TABLE products ADD COLUMN IF NOT EXISTS dimensions VARCHAR(100) COMMENT 'Length x Width x Height';
ALTER TABLE products ADD COLUMN IF NOT EXISTS material VARCHAR(100);
ALTER TABLE products ADD COLUMN IF NOT EXISTS care_instructions TEXT;
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE;
ALTER TABLE products ADD COLUMN IF NOT EXISTS featured_until DATETIME;
ALTER TABLE products ADD COLUMN IF NOT EXISTS avg_rating DECIMAL(3,2) DEFAULT 0;
ALTER TABLE products ADD COLUMN IF NOT EXISTS total_reviews INT DEFAULT 0;
ALTER TABLE products ADD COLUMN IF NOT EXISTS total_sales INT DEFAULT 0;

-- Default shipping options
INSERT IGNORE INTO shipping_options (name, description, base_cost, max_days, is_active, sort_order) VALUES
('Standard Delivery', 'Delivery in 3-5 business days', 250.00, 5, TRUE, 1),
('Express Delivery', 'Delivery in 1-2 business days', 500.00, 2, TRUE, 2),
('Same Day Pickup', 'Free pickup from our salon', 0.00, 0, TRUE, 3),
('Next Day Delivery', 'Guaranteed next day delivery', 800.00, 1, TRUE, 4);

-- Create indexes for better performance
CREATE INDEX idx_product_category ON products(category_id);
CREATE INDEX idx_product_active ON products(is_active);
CREATE INDEX idx_product_featured ON products(is_featured);
CREATE INDEX idx_product_price ON products(price);
CREATE INDEX idx_review_product ON product_reviews(product_id);
CREATE INDEX idx_cart_customer ON cart_items(customer_id);
CREATE INDEX idx_order_customer ON orders(customer_id);
CREATE INDEX idx_coupon_code ON coupons(code);
