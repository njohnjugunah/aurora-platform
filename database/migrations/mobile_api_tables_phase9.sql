-- Phase 9: Mobile App API Database Tables
-- Created: 2026-08-02

-- Device tokens for push notifications
CREATE TABLE IF NOT EXISTS device_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    device_id VARCHAR(255) NOT NULL UNIQUE,
    device_name VARCHAR(255),
    os_type ENUM('ios', 'android', 'web') NOT NULL,
    os_version VARCHAR(50),
    app_version VARCHAR(50),
    push_token TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_device_id (device_id),
    INDEX idx_push_token (push_token(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Device information and analytics
CREATE TABLE IF NOT EXISTS device_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id VARCHAR(255) NOT NULL UNIQUE,
    manufacturer VARCHAR(100),
    model VARCHAR(100),
    screen_size VARCHAR(50),
    screen_density VARCHAR(50),
    timezone VARCHAR(50),
    language VARCHAR(10),
    locale VARCHAR(10),
    carrier VARCHAR(100),
    network_type ENUM('wifi', 'mobile', 'unknown'),
    battery_saver_enabled BOOLEAN,
    geolocation_enabled BOOLEAN,
    last_lat DECIMAL(10, 8),
    last_lng DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_device_id (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API access logs for analytics
CREATE TABLE IF NOT EXISTS api_access_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    device_id VARCHAR(255),
    endpoint VARCHAR(255) NOT NULL,
    method ENUM('GET', 'POST', 'PUT', 'DELETE', 'PATCH') NOT NULL,
    status_code INT,
    response_time_ms INT,
    request_size INT,
    response_size INT,
    user_agent TEXT,
    ip_address VARCHAR(45),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Push notifications
CREATE TABLE IF NOT EXISTS push_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    device_id VARCHAR(255),
    title VARCHAR(255) NOT NULL,
    body TEXT,
    image_url TEXT,
    notification_type ENUM('reminder', 'promotion', 'loyalty', 'review', 'booking', 'payment', 'general') NOT NULL,
    related_id INT COMMENT 'ID of related object (booking, order, etc)',
    deep_link VARCHAR(500),
    data JSON COMMENT 'Additional metadata',
    sent_at TIMESTAMP,
    read_at TIMESTAMP,
    deleted_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_device_id (device_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_read_at (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offline request queue for sync
CREATE TABLE IF NOT EXISTS offline_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    method ENUM('GET', 'POST', 'PUT', 'DELETE') NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    payload JSON,
    request_id VARCHAR(100) UNIQUE,
    synced_at TIMESTAMP,
    error_message TEXT,
    retry_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_device_id (device_id),
    INDEX idx_synced_at (synced_at),
    INDEX idx_request_id (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User preferences for mobile app
CREATE TABLE IF NOT EXISTS user_preferences_mobile (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    notifications_enabled BOOLEAN DEFAULT TRUE,
    appointment_reminders BOOLEAN DEFAULT TRUE,
    promotion_notifications BOOLEAN DEFAULT TRUE,
    review_notifications BOOLEAN DEFAULT TRUE,
    sound_enabled BOOLEAN DEFAULT TRUE,
    vibration_enabled BOOLEAN DEFAULT TRUE,
    dark_mode BOOLEAN DEFAULT FALSE,
    language VARCHAR(10) DEFAULT 'en',
    currency VARCHAR(3) DEFAULT 'KES',
    auto_sync BOOLEAN DEFAULT TRUE,
    location_access BOOLEAN DEFAULT FALSE,
    biometric_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites/Wishlist
CREATE TABLE IF NOT EXISTS favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    type ENUM('service', 'staff', 'package') NOT NULL,
    item_id INT NOT NULL,
    item_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (customer_id, type, item_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Saved payment methods for quick checkout
CREATE TABLE IF NOT EXISTS saved_payment_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    payment_method ENUM('mpesa', 'card', 'bank') NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    last_four VARCHAR(4),
    expiry_date VARCHAR(7),
    mpesa_phone VARCHAR(20),
    encrypted_data TEXT COMMENT 'Encrypted payment details',
    nickname VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API session tokens (separate from main sessions)
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    access_token VARCHAR(500) NOT NULL UNIQUE,
    refresh_token VARCHAR(500) NOT NULL UNIQUE,
    access_expires_at TIMESTAMP NOT NULL,
    refresh_expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    is_revoked BOOLEAN DEFAULT FALSE,
    revoked_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_device_id (device_id),
    INDEX idx_access_token (access_token),
    INDEX idx_refresh_token (refresh_token),
    INDEX idx_access_expires (access_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Review ratings by services (mobile-specific)
CREATE TABLE IF NOT EXISTS service_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    rating INT COMMENT '1-5 stars',
    review_text TEXT,
    review_images JSON COMMENT 'Array of image URLs',
    helpful_count INT DEFAULT 0,
    unhelpful_count INT DEFAULT 0,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    visibility ENUM('public', 'private') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_service_id (service_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff ratings (mobile-specific)
CREATE TABLE IF NOT EXISTS staff_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    staff_id INT NOT NULL,
    rating INT COMMENT '1-5 stars',
    review_text TEXT,
    professionalism INT,
    customer_service INT,
    timeliness INT,
    expertise INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- App version management
CREATE TABLE IF NOT EXISTS app_versions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    platform ENUM('ios', 'android') NOT NULL,
    version VARCHAR(50) NOT NULL UNIQUE,
    build_number INT,
    release_notes TEXT,
    download_url VARCHAR(500),
    is_required BOOLEAN DEFAULT FALSE,
    minimum_version VARCHAR(50),
    released_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_platform (platform),
    INDEX idx_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature flags for mobile app
CREATE TABLE IF NOT EXISTS mobile_feature_flags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    platform ENUM('ios', 'android', 'both') NOT NULL,
    feature_name VARCHAR(100) NOT NULL,
    is_enabled BOOLEAN DEFAULT FALSE,
    min_version VARCHAR(50),
    description TEXT,
    rollout_percentage INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_feature (platform, feature_name),
    INDEX idx_platform (platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mobile app crashes and error reports
CREATE TABLE IF NOT EXISTS app_crashes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    device_id VARCHAR(255),
    app_version VARCHAR(50),
    os_version VARCHAR(50),
    error_message TEXT,
    stack_trace TEXT,
    crash_type VARCHAR(100),
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    is_resolved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_device_id (device_id),
    INDEX idx_app_version (app_version),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
