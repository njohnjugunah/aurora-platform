-- Phase 7 - AI-Powered Communications
-- Predictive analytics, smart content, churn prediction, lifecycle management

-- Send-time Optimization
CREATE TABLE IF NOT EXISTS customer_open_patterns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    day_of_week_preferences JSON,
    hour_preferences JSON,
    timezone VARCHAR(50),
    optimal_send_hour INT,
    optimal_send_day VARCHAR(10),
    open_rate_by_hour JSON,
    last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id)
);

-- Segment-Level Send Time Optimization
CREATE TABLE IF NOT EXISTS segment_send_times (
    id INT PRIMARY KEY AUTO_INCREMENT,
    segment VARCHAR(100) NOT NULL,
    day_of_week VARCHAR(10) NOT NULL,
    optimal_hour INT NOT NULL,
    avg_open_rate DECIMAL(5,2),
    avg_click_rate DECIMAL(5,2),
    sample_size INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (segment, day_of_week)
);

-- AI-Generated Content
CREATE TABLE IF NOT EXISTS ai_generated_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT,
    content_type ENUM('subject_line', 'body_text', 'cta_text', 'product_recommendation') NOT NULL,
    original_content TEXT,
    generated_variants JSON,
    selected_variant TEXT,
    performance_score DECIMAL(5,2),
    a_b_test_id INT,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    INDEX (campaign_id),
    INDEX (content_type)
);

-- Subject Line Performance History
CREATE TABLE IF NOT EXISTS subject_line_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT,
    subject_line VARCHAR(500) NOT NULL,
    variant_type ENUM('original', 'ai_generated', 'manual') DEFAULT 'original',
    sent_count INT DEFAULT 0,
    open_count INT DEFAULT 0,
    click_count INT DEFAULT 0,
    open_rate DECIMAL(5,2),
    click_rate DECIMAL(5,2),
    conversion_count INT DEFAULT 0,
    conversion_rate DECIMAL(5,2),
    revenue_generated DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    INDEX (campaign_id),
    INDEX (open_rate),
    INDEX (click_rate)
);

-- Churn Prediction Model Results
CREATE TABLE IF NOT EXISTS churn_predictions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    churn_risk_score DECIMAL(5,2),
    churn_probability_30_days DECIMAL(5,2),
    churn_probability_60_days DECIMAL(5,2),
    churn_probability_90_days DECIMAL(5,2),
    primary_risk_factor VARCHAR(100),
    secondary_risk_factors JSON,
    predicted_churn_date DATE,
    confidence_score DECIMAL(5,2),
    intervention_sent BOOLEAN DEFAULT FALSE,
    intervention_campaign_id INT,
    intervention_sent_at TIMESTAMP,
    customer_retained BOOLEAN DEFAULT FALSE,
    retained_at TIMESTAMP,
    model_version INT DEFAULT 1,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (intervention_campaign_id) REFERENCES marketing_campaigns(id),
    INDEX (customer_id),
    INDEX (churn_risk_score),
    INDEX (intervention_sent),
    INDEX (customer_retained)
);

-- Churn Prediction Features (for model training)
CREATE TABLE IF NOT EXISTS churn_prediction_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    days_since_signup INT,
    total_orders INT,
    total_spent DECIMAL(10,2),
    avg_order_value DECIMAL(10,2),
    days_since_last_order INT,
    days_since_last_email_open INT,
    email_open_rate DECIMAL(5,2),
    email_click_rate DECIMAL(5,2),
    support_tickets INT,
    support_satisfaction_score DECIMAL(3,2),
    engagement_score DECIMAL(5,2),
    repeat_purchase_likelihood DECIMAL(5,2),
    session_frequency INT,
    avg_session_duration INT,
    cart_abandonment_count INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id)
);

-- Customer Lifecycle Stages
CREATE TABLE IF NOT EXISTS customer_lifecycle_stages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    current_stage ENUM('prospect', 'new_customer', 'active', 'at_risk', 'inactive', 'loyal', 'vip') DEFAULT 'new_customer',
    stage_start_date TIMESTAMP,
    days_in_stage INT,
    previous_stage VARCHAR(50),
    stage_progression JSON,
    stage_triggers JSON,
    recommended_actions JSON,
    next_milestone VARCHAR(255),
    days_to_milestone INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id),
    INDEX (current_stage)
);

-- Lifecycle Stage Definitions
CREATE TABLE IF NOT EXISTS lifecycle_stage_definitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stage_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    criteria JSON,
    min_days_in_stage INT,
    max_days_in_stage INT,
    recommended_campaign_type VARCHAR(100),
    engagement_goal VARCHAR(255),
    success_metric VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Personalized Product Recommendations
CREATE TABLE IF NOT EXISTS personalized_recommendations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    product_id INT,
    recommendation_type ENUM('similar', 'complementary', 'trending', 'seasonal', 'personalized', 'win_back') NOT NULL,
    recommendation_score DECIMAL(5,2),
    reason TEXT,
    clicked BOOLEAN DEFAULT FALSE,
    purchased BOOLEAN DEFAULT FALSE,
    clicked_at TIMESTAMP,
    purchased_at TIMESTAMP,
    revenue_generated DECIMAL(10,2) DEFAULT 0,
    sent_via_channel VARCHAR(50),
    campaign_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id),
    INDEX (customer_id),
    INDEX (recommendation_type),
    INDEX (purchased)
);

-- AI Content Personalization
CREATE TABLE IF NOT EXISTS personalized_content_blocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    content_block_id VARCHAR(100),
    content_type ENUM('product_block', 'offer_block', 'story_block', 'educational_block') NOT NULL,
    original_content TEXT,
    personalized_content TEXT,
    personalization_factors JSON,
    engagement_score DECIMAL(5,2),
    clicked BOOLEAN DEFAULT FALSE,
    converted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id),
    INDEX (content_type)
);

-- AI Model Performance Tracking
CREATE TABLE IF NOT EXISTS ai_model_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    model_name VARCHAR(100) NOT NULL,
    model_version INT,
    metric_type VARCHAR(100),
    metric_value DECIMAL(10,4),
    test_set_size INT,
    training_date TIMESTAMP,
    accuracy DECIMAL(5,2),
    precision DECIMAL(5,2),
    recall DECIMAL(5,2),
    f1_score DECIMAL(5,2),
    status ENUM('training', 'evaluating', 'deployed', 'deprecated') DEFAULT 'training',
    deployed_at TIMESTAMP,
    retired_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (model_name, model_version)
);

-- Predictive Campaign Performance
CREATE TABLE IF NOT EXISTS predictive_campaign_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    predicted_open_rate DECIMAL(5,2),
    predicted_click_rate DECIMAL(5,2),
    predicted_conversion_rate DECIMAL(5,2),
    predicted_revenue DECIMAL(12,2),
    confidence_interval DECIMAL(5,2),
    actual_open_rate DECIMAL(5,2),
    actual_click_rate DECIMAL(5,2),
    actual_conversion_rate DECIMAL(5,2),
    actual_revenue DECIMAL(12,2),
    prediction_accuracy DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    INDEX (campaign_id)
);

-- Customer Value Prediction
CREATE TABLE IF NOT EXISTS customer_value_predictions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    predicted_ltv_6_months DECIMAL(10,2),
    predicted_ltv_12_months DECIMAL(10,2),
    predicted_purchase_probability_30_days DECIMAL(5,2),
    predicted_avg_order_value DECIMAL(10,2),
    predicted_order_frequency INT,
    confidence_score DECIMAL(5,2),
    value_segment VARCHAR(50),
    growth_potential VARCHAR(50),
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id),
    INDEX (value_segment)
);

-- Create indexes for performance
CREATE INDEX idx_churn_predictions_risk ON churn_predictions(churn_risk_score);
CREATE INDEX idx_lifecycle_stage_progression ON customer_lifecycle_stages(current_stage, updated_at);
CREATE INDEX idx_recommendations_type_score ON personalized_recommendations(recommendation_type, recommendation_score);
CREATE INDEX idx_subject_line_open_rate ON subject_line_performance(open_rate DESC);
CREATE INDEX idx_ai_content_performance ON ai_generated_content(performance_score DESC);
