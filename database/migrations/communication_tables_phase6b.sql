-- Phase 6B - Advanced Communications
-- SMS, Push Notifications, In-App Notifications, and Behavioral Automation

-- Push Subscriptions for Web Push Notifications
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    endpoint VARCHAR(500) NOT NULL UNIQUE,
    public_key VARCHAR(255),
    auth_token VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id),
    INDEX (is_active)
);

-- Push Notification Logs
CREATE TABLE IF NOT EXISTS push_notification_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id),
    INDEX (created_at)
);

-- In-App Notifications
CREATE TABLE IF NOT EXISTS in_app_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type ENUM('info', 'success', 'warning', 'error', 'order', 'appointment', 'promotion', 'alert') DEFAULT 'info',
    action_url VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id),
    INDEX (is_read),
    INDEX (created_at)
);

-- SMS Messages Log (extends existing sms_messages table)
-- Ensure sms_messages table has these fields
ALTER TABLE sms_messages ADD COLUMN IF NOT EXISTS provider VARCHAR(50);
ALTER TABLE sms_messages ADD COLUMN IF NOT EXISTS provider_id VARCHAR(255);
ALTER TABLE sms_messages ADD COLUMN IF NOT EXISTS status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending';

-- Behavioral Automation Rules
CREATE TABLE IF NOT EXISTS automation_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    trigger_type ENUM('abandoned_cart', 'low_engagement', 'birthday', 'anniversary', 'reorder_due', 'low_stock_alert', 'review_request', 'win_back', 'custom') NOT NULL,
    trigger_condition JSON,
    action_type ENUM('email', 'sms', 'push', 'in_app', 'multi') NOT NULL,
    action_config JSON,
    target_segment VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    max_executions INT DEFAULT -1,
    execution_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (trigger_type),
    INDEX (is_active)
);

-- Automation Execution History
CREATE TABLE IF NOT EXISTS automation_executions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rule_id INT NOT NULL,
    customer_id INT NOT NULL,
    trigger_data JSON,
    action_taken VARCHAR(500),
    status ENUM('triggered', 'executed', 'failed', 'skipped') DEFAULT 'triggered',
    result JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_id) REFERENCES automation_rules(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (rule_id),
    INDEX (customer_id),
    INDEX (created_at)
);

-- Campaign A/B Testing
CREATE TABLE IF NOT EXISTS campaign_ab_tests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    variant_a_template_id INT,
    variant_b_template_id INT,
    variant_a_subject VARCHAR(255),
    variant_b_subject VARCHAR(255),
    split_percentage DECIMAL(5,2) DEFAULT 50.00,
    winner ENUM('A', 'B', 'none') DEFAULT 'none',
    metric ENUM('open_rate', 'click_rate', 'conversion_rate') DEFAULT 'open_rate',
    test_duration_days INT DEFAULT 3,
    started_at TIMESTAMP,
    ended_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_a_template_id) REFERENCES email_templates(id),
    FOREIGN KEY (variant_b_template_id) REFERENCES email_templates(id),
    INDEX (campaign_id)
);

-- Campaign A/B Test Recipients
CREATE TABLE IF NOT EXISTS campaign_ab_test_recipients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    test_id INT NOT NULL,
    campaign_recipient_id INT NOT NULL,
    variant ENUM('A', 'B') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES campaign_ab_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_recipient_id) REFERENCES campaign_recipients(id) ON DELETE CASCADE,
    INDEX (test_id),
    INDEX (variant)
);

-- Advanced Campaign Scheduling
CREATE TABLE IF NOT EXISTS campaign_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    schedule_type ENUM('immediate', 'scheduled', 'recurring', 'triggered') DEFAULT 'immediate',
    send_at TIMESTAMP,
    recurrence_pattern VARCHAR(255),
    next_send_at TIMESTAMP,
    timezone VARCHAR(50),
    optimal_send_time BOOLEAN DEFAULT FALSE,
    optimal_send_hour INT,
    is_active BOOLEAN DEFAULT TRUE,
    last_sent_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    INDEX (campaign_id),
    INDEX (next_send_at),
    INDEX (is_active)
);

-- Abandoned Cart Tracking
CREATE TABLE IF NOT EXISTS abandoned_carts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    cart_data JSON,
    cart_value DECIMAL(10,2),
    reminder_count INT DEFAULT 0,
    last_reminder_at TIMESTAMP,
    recovered_at TIMESTAMP,
    abandoned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id),
    INDEX (abandoned_at)
);

-- Customer Engagement Scores
CREATE TABLE IF NOT EXISTS customer_engagement_scores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    engagement_score DECIMAL(5,2) DEFAULT 0.00,
    email_open_rate DECIMAL(5,2) DEFAULT 0.00,
    email_click_rate DECIMAL(5,2) DEFAULT 0.00,
    order_frequency INT DEFAULT 0,
    average_order_value DECIMAL(10,2) DEFAULT 0.00,
    last_order_days INT DEFAULT 0,
    is_at_risk BOOLEAN DEFAULT FALSE,
    risk_score DECIMAL(5,2) DEFAULT 0.00,
    last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (engagement_score),
    INDEX (is_at_risk)
);

-- Push Notification Permissions/Settings
CREATE TABLE IF NOT EXISTS push_notification_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    push_enabled BOOLEAN DEFAULT TRUE,
    push_orders BOOLEAN DEFAULT TRUE,
    push_appointments BOOLEAN DEFAULT TRUE,
    push_promotions BOOLEAN DEFAULT FALSE,
    push_alerts BOOLEAN DEFAULT TRUE,
    quiet_hours_start TIME,
    quiet_hours_end TIME,
    timezone VARCHAR(50) DEFAULT 'UTC',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Create indexes for performance optimization
CREATE INDEX idx_sms_messages_customer ON sms_messages(customer_id);
CREATE INDEX idx_sms_messages_status ON sms_messages(status);
CREATE INDEX idx_push_notifications_customer ON push_notification_logs(customer_id);
CREATE INDEX idx_in_app_read ON in_app_notifications(customer_id, is_read);
CREATE INDEX idx_automation_executions_rule ON automation_executions(rule_id);
