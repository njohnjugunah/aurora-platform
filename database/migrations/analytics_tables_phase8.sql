-- Phase 8 - Advanced Analytics
-- LTV prediction, cohort analysis, attribution modeling, journey mapping

-- Customer Lifetime Value Predictions
CREATE TABLE IF NOT EXISTS customer_ltv_predictions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    predicted_ltv_3_months DECIMAL(10,2),
    predicted_ltv_6_months DECIMAL(10,2),
    predicted_ltv_12_months DECIMAL(10,2),
    predicted_ltv_24_months DECIMAL(10,2),
    current_ltv DECIMAL(10,2),
    ltv_growth_potential DECIMAL(5,2),
    ltv_confidence_score DECIMAL(5,2),
    value_segment VARCHAR(50),
    segment_tier ENUM('low', 'medium', 'high', 'very_high', 'vip') DEFAULT 'medium',
    is_high_value BOOLEAN DEFAULT FALSE,
    prediction_model_version INT DEFAULT 1,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id),
    INDEX (value_segment),
    INDEX (segment_tier)
);

-- Cohort Analysis
CREATE TABLE IF NOT EXISTS customer_cohorts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cohort_name VARCHAR(255) NOT NULL,
    cohort_type ENUM('signup_month', 'first_purchase_month', 'custom', 'behavior', 'demographic') NOT NULL,
    cohort_start_date DATE,
    cohort_end_date DATE,
    cohort_definition JSON,
    customer_count INT DEFAULT 0,
    total_revenue DECIMAL(12,2) DEFAULT 0,
    avg_customer_value DECIMAL(10,2) DEFAULT 0,
    retention_rate_1m DECIMAL(5,2),
    retention_rate_3m DECIMAL(5,2),
    retention_rate_6m DECIMAL(5,2),
    retention_rate_12m DECIMAL(5,2),
    avg_order_frequency DECIMAL(5,2),
    avg_order_value DECIMAL(10,2),
    churn_rate DECIMAL(5,2),
    ltv_contribution DECIMAL(12,2),
    health_score DECIMAL(5,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_analyzed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (cohort_type),
    INDEX (cohort_start_date)
);

-- Cohort Members
CREATE TABLE IF NOT EXISTS cohort_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cohort_id INT NOT NULL,
    customer_id INT NOT NULL,
    joined_cohort_date TIMESTAMP,
    months_in_cohort INT,
    cohort_value DECIMAL(10,2),
    retention_status VARCHAR(50),
    FOREIGN KEY (cohort_id) REFERENCES customer_cohorts(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (cohort_id),
    INDEX (customer_id)
);

-- Multi-Touch Attribution
CREATE TABLE IF NOT EXISTS attribution_models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    model_type ENUM('first_touch', 'last_touch', 'linear', 'time_decay', 'position_based', 'data_driven') NOT NULL,
    conversion_value DECIMAL(10,2),
    touch_points JSON,
    channel_attribution JSON,
    campaign_attribution JSON,
    attribution_accuracy DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (order_id),
    INDEX (customer_id),
    INDEX (model_type)
);

-- Customer Touch Points (interaction log)
CREATE TABLE IF NOT EXISTS customer_touchpoints (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    touchpoint_type VARCHAR(100),
    channel VARCHAR(100),
    campaign_id INT,
    campaign_name VARCHAR(255),
    engagement_type VARCHAR(100),
    engagement_value DECIMAL(10,2),
    device_type VARCHAR(50),
    location VARCHAR(100),
    session_id VARCHAR(255),
    related_order_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id),
    FOREIGN KEY (related_order_id) REFERENCES orders(id),
    INDEX (customer_id),
    INDEX (channel),
    INDEX (created_at),
    INDEX (campaign_id)
);

-- Customer Journey Stages
CREATE TABLE IF NOT EXISTS customer_journey_stages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    awareness_stage_date TIMESTAMP,
    consideration_stage_date TIMESTAMP,
    decision_stage_date TIMESTAMP,
    retention_stage_date TIMESTAMP,
    advocacy_stage_date TIMESTAMP,
    current_stage ENUM('awareness', 'consideration', 'decision', 'retention', 'advocacy') DEFAULT 'awareness',
    stage_transition_count INT DEFAULT 0,
    avg_stage_duration INT,
    total_journey_duration INT,
    conversion_path_length INT,
    conversion_path JSON,
    is_converged BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id),
    INDEX (current_stage)
);

-- Journey Stage Conversions
CREATE TABLE IF NOT EXISTS journey_stage_conversions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_stage VARCHAR(100) NOT NULL,
    to_stage VARCHAR(100) NOT NULL,
    conversion_count INT DEFAULT 0,
    conversion_rate DECIMAL(5,2),
    avg_days_to_convert INT,
    most_common_channel VARCHAR(100),
    revenue_per_conversion DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (from_stage, to_stage)
);

-- Attribution Channel Performance
CREATE TABLE IF NOT EXISTS channel_attribution_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    channel_name VARCHAR(100) NOT NULL,
    model_type VARCHAR(50),
    total_attributed_revenue DECIMAL(12,2) DEFAULT 0,
    first_touch_revenue DECIMAL(12,2) DEFAULT 0,
    last_touch_revenue DECIMAL(12,2) DEFAULT 0,
    assist_revenue DECIMAL(12,2) DEFAULT 0,
    conversions INT DEFAULT 0,
    cpa DECIMAL(10,2),
    roi DECIMAL(5,2),
    roas DECIMAL(5,2),
    cost_per_acquisition DECIMAL(10,2),
    period_start DATE,
    period_end DATE,
    is_current BOOLEAN DEFAULT TRUE,
    INDEX (channel_name),
    INDEX (model_type)
);

-- Campaign Performance Attribution
CREATE TABLE IF NOT EXISTS campaign_attribution_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    campaign_name VARCHAR(255),
    first_touch_conversions INT DEFAULT 0,
    last_touch_conversions INT DEFAULT 0,
    assist_conversions INT DEFAULT 0,
    attributed_revenue DECIMAL(12,2) DEFAULT 0,
    true_roi DECIMAL(5,2),
    halo_effect_revenue DECIMAL(12,2),
    incremental_revenue DECIMAL(12,2),
    brand_lift_percentage DECIMAL(5,2),
    period_start DATE,
    period_end DATE,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id),
    INDEX (campaign_id),
    INDEX (period_start)
);

-- Cohort Retention Analysis
CREATE TABLE IF NOT EXISTS cohort_retention_matrix (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cohort_id INT NOT NULL,
    cohort_month VARCHAR(7),
    month_0_users INT,
    month_1_users INT,
    month_2_users INT,
    month_3_users INT,
    month_4_users INT,
    month_5_users INT,
    month_6_users INT,
    month_7_users INT,
    month_8_users INT,
    month_9_users INT,
    month_10_users INT,
    month_11_users INT,
    month_12_users INT,
    month_0_revenue DECIMAL(12,2),
    month_1_revenue DECIMAL(12,2),
    month_6_revenue DECIMAL(12,2),
    month_12_revenue DECIMAL(12,2),
    FOREIGN KEY (cohort_id) REFERENCES customer_cohorts(id) ON DELETE CASCADE,
    INDEX (cohort_id)
);

-- LTV Components Analysis
CREATE TABLE IF NOT EXISTS ltv_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL UNIQUE,
    base_ltv DECIMAL(10,2),
    upsell_potential DECIMAL(10,2) DEFAULT 0,
    cross_sell_potential DECIMAL(10,2) DEFAULT 0,
    retention_value DECIMAL(10,2) DEFAULT 0,
    referral_value DECIMAL(10,2) DEFAULT 0,
    brand_advocacy_value DECIMAL(10,2) DEFAULT 0,
    total_potential_ltv DECIMAL(10,2),
    expansion_opportunity DECIMAL(10,2),
    churn_risk_adjusted_ltv DECIMAL(10,2),
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX (customer_id)
);

-- Analytics Snapshots (for trend tracking)
CREATE TABLE IF NOT EXISTS analytics_snapshots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    snapshot_date DATE NOT NULL,
    snapshot_type VARCHAR(100),
    metric_name VARCHAR(255),
    metric_value DECIMAL(15,2),
    segmentation JSON,
    previous_value DECIMAL(15,2),
    change_percentage DECIMAL(5,2),
    trend_direction VARCHAR(10),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (snapshot_date),
    INDEX (metric_name)
);

-- Customer Segment Performance
CREATE TABLE IF NOT EXISTS segment_performance_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    segment_name VARCHAR(100) NOT NULL,
    segment_type VARCHAR(100),
    customer_count INT,
    total_ltv DECIMAL(12,2),
    avg_ltv DECIMAL(10,2),
    avg_order_value DECIMAL(10,2),
    order_frequency DECIMAL(5,2),
    retention_rate DECIMAL(5,2),
    churn_rate DECIMAL(5,2),
    engagement_score DECIMAL(5,2),
    revenue_contribution DECIMAL(5,2),
    growth_rate DECIMAL(5,2),
    period_start DATE,
    period_end DATE,
    INDEX (segment_name),
    INDEX (period_start)
);

-- Create indexes for performance
CREATE INDEX idx_ltv_predictions_segment ON customer_ltv_predictions(segment_tier);
CREATE INDEX idx_cohort_retention ON customer_cohorts(retention_rate_1m);
CREATE INDEX idx_touchpoints_channel ON customer_touchpoints(channel);
CREATE INDEX idx_journey_stage ON customer_journey_stages(current_stage);
CREATE INDEX idx_attribution_model ON attribution_models(model_type);
CREATE INDEX idx_channel_performance ON channel_attribution_performance(channel_name, model_type);
