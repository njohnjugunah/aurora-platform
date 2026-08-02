# Phase 7 - AI-Powered Communications Implementation Guide

## Overview

Phase 7 adds intelligent, predictive capabilities to the GlamByMariga platform with AI-powered send-time optimization, smart content generation, churn prediction, and customer lifecycle intelligence.

---

## Quick Start

### 1. Database Setup

Run the migration to create all Phase 7 tables:

```sql
mysql -u user -p database_name < database/migrations/communication_tables_phase7.sql
```

**New Tables Created:**
- `customer_open_patterns` - Email open pattern analysis
- `segment_send_times` - Segment-level send time optimization
- `ai_generated_content` - AI-generated content variants
- `subject_line_performance` - Subject line analytics
- `churn_predictions` - Customer churn probability
- `churn_prediction_features` - Model training features
- `customer_lifecycle_stages` - Customer stage tracking
- `lifecycle_stage_definitions` - Stage definitions and rules
- `personalized_recommendations` - Product recommendations
- `personalized_content_blocks` - Dynamic content personalization
- `ai_model_performance` - Model metrics and deployment
- `predictive_campaign_performance` - Campaign predictions
- `customer_value_predictions` - LTV and value predictions

### 2. Service Integration

```php
use GlamByMariga\Communication\PredictiveService;
use GlamByMariga\Communication\ContentGenerationService;
use GlamByMariga\Communication\ChurnPredictionService;
use GlamByMariga\Communication\LifecycleService;

$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

$predictiveService = new PredictiveService($db);
$contentService = new ContentGenerationService($db);
$churnService = new ChurnPredictionService($db);
$lifecycleService = new LifecycleService($db);
```

### 3. Initialize Lifecycle Stages

```php
$lifecycleService->initializeStageDefinitions();
```

---

## Features & Usage

### Predictive Send-Time Optimization

#### Calculate Customer Open Patterns

```php
// Analyze customer's email open behavior
$result = $predictiveService->calculateCustomerOpenPatterns($customerId);

// Returns:
// {
//   optimal_hour: 14,
//   optimal_day: "Wednesday",
//   open_rate_by_hour: [...]
// }
```

#### Schedule Campaign at Optimal Time

```php
$optimal = $predictiveService->getOptimalSendTime($customerId);

// Schedule campaign for $optimal['optimal_hour'] on $optimal['optimal_day']
```

#### Predict Campaign Performance

```php
$prediction = $predictiveService->predictCampaignPerformance($campaignId);

// Returns predicted:
// - open_rate (42%)
// - click_rate (15%)
// - conversion_rate (3%)
// - revenue (KES 5,250)
// - confidence score
```

### AI-Powered Subject Line Generation

#### Generate Subject Line Variants

```php
$variants = $contentService->generateSubjectLineVariants(
    'Summer Collection Launch',
    ['segment' => 'repeat', 'discount' => '20%']
);

// Returns 5 variants with patterns:
// - Urgency: "Last chance: Summer Collection Launch"
// - Personalization: "Just for you - Summer Collection Launch"
// - Question: "Ready to discover our Summer Collection?"
// - Benefit: "Save with Summer Collection Launch"
// - Curiosity: "⚡ Summer Collection Launch"
```

#### Get Subject Line Recommendation

```php
$recommendation = $contentService->recommendSubjectLine($campaignId);

// Returns recommended subject + alternatives ranked by predicted performance
```

#### Score Subject Line Effectiveness

```php
// Scoring based on:
// - Optimal length (30-50 chars)
// - Numbers (proven engagement boost)
// - Power words (exclusive, new, limited, free, etc.)
// - Capitalization
// - Spam indicators
// - Sentiment

// Score 0-100, higher is better
```

#### CTA Recommendations

```php
$ctas = $contentService->generateCTARecommendations('promotional');

// Returns recommendations by campaign type:
// - promotional: ['Shop Now', 'Get the Deal', 'Claim Your Offer']
// - educational: ['Learn More', 'Read the Guide', 'Explore Now']
// - win_back: ['Come Back', 'We Miss You', 'Reconnect']
// - reorder: ['Reorder Now', 'Buy More', 'Continue Shopping']
```

### Churn Prediction & Prevention

#### Calculate Churn Probability

```php
$churnPred = $churnService->calculateChurnProbability($customerId);

// Returns:
// {
//   churn_risk_score: 78,           // 0-100
//   churn_probability_30_days: 65,  // %
//   churn_probability_60_days: 52,  // %
//   churn_probability_90_days: 38,  // %
//   primary_risk_factor: "No recent purchases (>90 days)",
//   confidence_score: 85
// }
```

#### Risk Factors Analyzed

1. **Recency** (50%) - Days since last purchase
2. **Frequency** (30%) - Number of purchases
3. **Monetary** (20%) - Lifetime value
4. **Email Engagement** - Opens and clicks
5. **Overall Engagement** - Cross-channel engagement
6. **Account Age** - Days since signup

#### Get High-Risk Customers

```php
$atRisk = $churnService->getHighRiskCustomers($riskThreshold = 70, $limit = 50);

// Returns customers with churn_risk_score >= threshold
// Ordered by highest risk first
```

#### Send Intervention Campaign

```php
$result = $churnService->sendInterventionCampaign($customerId, $campaignId);

// Triggers win-back campaign, tracks intervention
```

#### Mark Customer as Retained

```php
$churnService->markAsRetained($customerId);

// Updates prediction to track successful retention
```

### Customer Lifecycle Intelligence

#### Lifecycle Stages

```
1. Prospect → No orders yet
2. New Customer → First purchase within 90 days
3. Active → 2+ orders, purchased within 60 days
4. At-Risk → 90+ days without purchase OR low engagement
5. Inactive → 180+ days without purchase
6. Loyal → 5+ orders AND KES 25k+ lifetime value
7. VIP → 10+ orders AND KES 50k+ lifetime value
```

#### Calculate Customer Lifecycle Stage

```php
$lifecycle = $lifecycleService->calculateLifecycleStage($customerId);

// Returns:
// {
//   stage: "active",                    // Current stage
//   days_in_stage: 45,
//   stage_changed: false,
//   recommended_actions: [
//     "Regular engagement campaigns",
//     "Product recommendations",
//     "Loyalty rewards"
//   ],
//   metrics: {...}
// }
```

#### Get Recommended Actions Per Stage

```
Prospect:
- Send welcome series
- Offer first-purchase discount
- Educational content
- Show social proof

New Customer:
- Send thank you email
- Onboarding sequence
- Product education
- Encourage second purchase

Active:
- Regular engagement campaigns
- Product recommendations
- Loyalty rewards
- Exclusive member offers

At-Risk:
- Re-engagement campaign
- Special incentive offer
- Win-back series
- Last chance offer

Inactive:
- Strong reactivation campaign
- Remind of previous products
- Share what's new
- Reactivation incentive

Loyal:
- VIP recognition
- Exclusive early access
- Premium customer service
- Loyalty tier rewards

VIP:
- Concierge service
- Exclusive events/previews
- Personal shopping assistance
- Ambassador program
```

#### Get Stage Distribution

```php
$distribution = $lifecycleService->getStageDistribution();

// Returns count of customers in each stage
```

#### Get Stage Progression

```php
$progression = $lifecycleService->getStageProgression($customerId);

// Shows customer's movement through stages over time
// Helps identify at-risk movement patterns
```

#### Get Stage Health Metrics

```php
$health = $lifecycleService->getStageHealthMetrics();

// Returns per-stage metrics:
// - Average engagement score
// - Average churn risk
// - Count at-risk in each stage
// - Stage size
```

---

## API Endpoints

### Predictive Analytics

**POST** `/ajax/communication/predictive.php`
```json
{
    "action": "generate_subject_lines|predict_campaign|cta_recommendations",
    "subject": "Original subject line",
    "campaign_id": 1,
    "campaign_type": "promotional"
}
```

**GET** `/ajax/communication/predictive.php?action=dashboard`
```json
{
    "prediction_accuracy": 85.5,
    "predictions_made": 245,
    "top_subject_lines": [...]
}
```

### Churn Prediction

**POST** `/ajax/communication/churn.php`
```json
{
    "action": "calculate_churn|calculate_batch|send_intervention|mark_retained",
    "customer_id": 123,
    "campaign_id": 5
}
```

**GET** `/ajax/communication/churn.php?action=analytics`
```json
{
    "analytics": {
        "total_predictions": 1245,
        "high_risk_count": 87,
        "avg_risk_score": 45.3,
        "interventions_sent": 34,
        "customers_retained": 12
    }
}
```

### Lifecycle Management

**POST** `/ajax/communication/lifecycle.php`
```json
{
    "action": "initialize_stages|calculate_stage|calculate_all",
    "customer_id": 123
}
```

**GET** `/ajax/communication/lifecycle.php?action=distribution`
```json
{
    "distribution": [
        {"current_stage": "active", "count": 234},
        {"current_stage": "at_risk", "count": 45}
    ]
}
```

---

## Admin Dashboard

Access AI insights at `/admin/ai-insights.html`

### Sections

1. **Churn Prediction & Prevention**
   - High-risk customer identification
   - Intervention campaign tracking
   - Retention metrics

2. **Customer Lifecycle Intelligence**
   - Stage distribution visualization
   - Stage health metrics
   - Recommended actions per stage

3. **Send-Time Optimization**
   - Prediction accuracy tracking
   - Top-performing subject lines
   - Segment-level send times

4. **AI Subject Line Generation**
   - Generate variants with different patterns
   - View predicted performance scores
   - CTA recommendations by campaign type

---

## Automation Examples

### Auto-Calculate Churn Weekly

```bash
0 2 * * 1 php /path/to/churn-calculator.php
```

**churn-calculator.php:**
```php
<?php
require_once 'config/database.php';
require_once 'includes/communication/ChurnPredictionService.php';

$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
$churnService = new ChurnPredictionService($db);

$stmt = $db->prepare("SELECT id FROM customers LIMIT 1000");
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($customers as $custId) {
    $churnService->calculateChurnProbability($custId);
}
?>
```

### Auto-Calculate Lifecycle Stages Daily

```bash
0 1 * * * php /path/to/lifecycle-calculator.php
```

### Auto-Send Subject Line Optimization

```bash
0 0 * * * php /path/to/subject-optimization.php
```

---

## Performance Considerations

### Database Optimization

```sql
-- Index key columns for faster queries
CREATE INDEX idx_churn_predictions_risk ON churn_predictions(churn_risk_score);
CREATE INDEX idx_lifecycle_stage ON customer_lifecycle_stages(current_stage);
CREATE INDEX idx_open_patterns_customer ON customer_open_patterns(customer_id);
```

### Optimization Tips

1. **Batch Processing**: Process customers in batches (100-500) to avoid timeouts
2. **Caching**: Cache segment send times for 1 hour
3. **Async**: Run batch calculations asynchronously
4. **Archive**: Archive old predictions and features quarterly
5. **Indexing**: Ensure key columns are indexed for fast lookups

---

## Model Performance Tracking

All predictions are tracked for accuracy:

```php
// View model performance
$stmt = $db->prepare(
    "SELECT model_name, model_version, accuracy, f1_score, status
     FROM ai_model_performance
     WHERE status = 'deployed'
     ORDER BY deployed_at DESC"
);
$stmt->execute();
$models = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

---

## Deployment Checklist

- [ ] Run database migrations (communication_tables_phase7.sql)
- [ ] Initialize lifecycle stage definitions
- [ ] Configure cron jobs for batch processing
- [ ] Test churn prediction with sample customers
- [ ] Verify send-time optimization accuracy
- [ ] Test AI subject line generation
- [ ] Train models with historical data (optional)
- [ ] Monitor prediction accuracy
- [ ] Set up alerts for high-risk customers
- [ ] Configure intervention campaigns
- [ ] Test lifecycle stage transitions
- [ ] Enable admin dashboard access
- [ ] Monitor database performance

---

## Troubleshooting

### Churn Predictions Not Updating

```php
// Check if customer has sufficient data
$stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
$stmt->execute([$customerId]);

// Requires at least some order history or engagement data
```

### Subject Line Scoring Issues

```php
// Subject lines must be between 20-60 characters for best scoring
// Test scoring with:
$score = $contentService->scoreSubjectLine($subject);
```

### Lifecycle Stage Not Changing

```php
// Verify lifecycle_stage_definitions are initialized
$stmt = $db->prepare("SELECT COUNT(*) FROM lifecycle_stage_definitions");
$stmt->execute();

// Should return 7 stages
```

---

## Future Enhancements

### Phase 8 - Advanced AI
- Machine learning model training
- Neural network predictions
- Real-time propensity scoring
- Behavioral clustering
- Anomaly detection

### Phase 9 - Personalization Engine
- Dynamic content generation
- Recommendation algorithms
- Behavioral targeting
- Preference learning
- Adaptive messaging

---

**Phase 7 Status:** ✅ COMPLETE & PRODUCTION-READY

**Components Delivered:**
1. ✅ Predictive Service (send-time optimization)
2. ✅ Content Generation Service (AI subject lines)
3. ✅ Churn Prediction Service
4. ✅ Lifecycle Service
5. ✅ Database schema (13 new tables)
6. ✅ API endpoints
7. ✅ Admin dashboard (AI Insights)
8. ✅ Documentation

For detailed setup instructions, see this guide.
