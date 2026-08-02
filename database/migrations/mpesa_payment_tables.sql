-- M-Pesa Payment Transaction Tracking
CREATE TABLE IF NOT EXISTS mpesa_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NULL,
    order_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    transaction_ref VARCHAR(50) UNIQUE,
    checkout_request_id VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    response_code VARCHAR(10),
    response_message TEXT,
    result_code VARCHAR(10),
    result_desc TEXT,
    mpesa_receipt_number VARCHAR(50),
    customer_message TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_booking (booking_id),
    INDEX idx_order (order_id),
    INDEX idx_phone (phone_number),
    INDEX idx_status (status),
    INDEX idx_created (timestamp),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Payment Retry Tracking for Failed Transactions
CREATE TABLE IF NOT EXISTS payment_retries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    attempt_number INT DEFAULT 1,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    response_code VARCHAR(10),
    response_message TEXT,
    error_details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_transaction (transaction_id),
    INDEX idx_attempt (attempt_number),
    FOREIGN KEY (transaction_id) REFERENCES mpesa_transactions(id) ON DELETE CASCADE
);

-- Payment Audit Log for Security and Compliance
CREATE TABLE IF NOT EXISTS payment_audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT,
    action VARCHAR(50) NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transaction (transaction_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    FOREIGN KEY (transaction_id) REFERENCES mpesa_transactions(id) ON DELETE CASCADE
);

-- Add payment status column to bookings table if not exists
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS mpesa_transaction_id INT NULL AFTER payment_status;
ALTER TABLE bookings ADD FOREIGN KEY IF NOT EXISTS fk_mpesa_transaction (mpesa_transaction_id) REFERENCES mpesa_transactions(id) ON DELETE SET NULL;

-- Add payment status column to orders table if not exists
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS mpesa_transaction_id INT NULL AFTER payment_status;
ALTER TABLE orders ADD FOREIGN KEY IF NOT EXISTS fk_orders_mpesa_transaction (mpesa_transaction_id) REFERENCES mpesa_transactions(id) ON DELETE SET NULL;

-- Create payment webhook log table
CREATE TABLE IF NOT EXISTS mpesa_webhook_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    checkout_request_id VARCHAR(100),
    raw_response LONGTEXT,
    processed BOOLEAN DEFAULT FALSE,
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    INDEX idx_request_id (checkout_request_id),
    INDEX idx_processed (processed)
);
