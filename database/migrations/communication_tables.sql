-- Communication & Email System - Phase 6
-- Email templates, notification preferences, and communication logs

-- Email Templates
CREATE TABLE IF NOT EXISTS email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_active (is_active)
);

-- Email Logs
CREATE TABLE IF NOT EXISTS email_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    to_address VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT,
    status ENUM('sent', 'failed', 'bounced', 'opened', 'clicked') DEFAULT 'sent',
    error TEXT,
    opened_at DATETIME,
    clicked_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_to_address (to_address),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Notification Preferences
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT UNIQUE NOT NULL,
    email_orders BOOLEAN DEFAULT TRUE,
    email_appointments BOOLEAN DEFAULT TRUE,
    email_promotions BOOLEAN DEFAULT TRUE,
    email_reviews BOOLEAN DEFAULT TRUE,
    sms_alerts BOOLEAN DEFAULT FALSE,
    sms_phone VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id)
);

-- Notifications Log
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'order_confirmation, order_shipped, appointment_reminder, etc',
    reference_id INT COMMENT 'order_id or booking_id',
    status ENUM('sent', 'failed', 'pending') DEFAULT 'sent',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Marketing Campaigns
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    template_id INT,
    subject VARCHAR(255),
    body LONGTEXT,
    target_segment VARCHAR(100) COMMENT 'all, repeat, high_value, inactive',
    status ENUM('draft', 'scheduled', 'sent', 'cancelled') DEFAULT 'draft',
    scheduled_at DATETIME,
    sent_at DATETIME,
    recipients_count INT DEFAULT 0,
    opened_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at)
);

-- Campaign Recipients
CREATE TABLE IF NOT EXISTS campaign_recipients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    customer_id INT NOT NULL,
    email VARCHAR(255),
    status ENUM('pending', 'sent', 'failed', 'opened', 'clicked') DEFAULT 'pending',
    opened_at DATETIME,
    clicked_at DATETIME,
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_campaign (campaign_id),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_campaign_customer (campaign_id, customer_id)
);

-- Customer Feedback
CREATE TABLE IF NOT EXISTS customer_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    type VARCHAR(50) COMMENT 'product, service, support, general',
    rating INT COMMENT '1-5 stars',
    subject VARCHAR(255),
    message TEXT,
    status ENUM('new', 'read', 'responded') DEFAULT 'new',
    response TEXT,
    responded_by INT,
    responded_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Newsletter Subscriptions
CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    customer_id INT,
    status ENUM('subscribed', 'unsubscribed', 'bounced') DEFAULT 'subscribed',
    unsubscribed_at DATETIME,
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_customer (customer_id)
);

-- SMS Messages (for future SMS integration)
CREATE TABLE IF NOT EXISTS sms_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
    provider VARCHAR(50),
    provider_id VARCHAR(100),
    error_message TEXT,
    sent_at DATETIME,
    delivered_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Default Email Templates
INSERT IGNORE INTO email_templates (name, subject, description, is_active) VALUES
('order_confirmation', 'Order Confirmation - #{{ORDER_ID}}', 'Sent when customer places an order', TRUE),
('order_shipped', 'Your Order Has Shipped - #{{ORDER_ID}}', 'Sent when order is shipped with tracking info', TRUE),
('order_delivered', 'Your Order Has Been Delivered - #{{ORDER_ID}}', 'Sent when order is delivered', TRUE),
('appointment_confirmed', 'Appointment Confirmation - {{SERVICE_NAME}}', 'Sent when appointment is confirmed', TRUE),
('appointment_reminder', 'Reminder: Your Appointment Tomorrow', 'Sent 24 hours before appointment', TRUE),
('welcome_email', 'Welcome to GlamByMariga!', 'Sent to new customers', TRUE),
('reset_password', 'Reset Your Password', 'Sent for password reset requests', TRUE),
('review_request', 'How was your experience with {{PRODUCT_NAME}}?', 'Request review after purchase', TRUE);

-- Create indexes for better performance
CREATE INDEX idx_email_logs_status_date ON email_logs(status, created_at);
CREATE INDEX idx_notifications_type_status ON notifications(type, status, created_at);
CREATE INDEX idx_campaign_recipients_campaign_status ON campaign_recipients(campaign_id, status);
CREATE INDEX idx_feedback_status ON customer_feedback(status, created_at);
CREATE INDEX idx_newsletter_status_email ON newsletter_subscriptions(status, email);
