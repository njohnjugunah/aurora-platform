# Phase 6B - Advanced Communications Implementation Guide

## Overview

Phase 6B extends Phase 6 email communications with comprehensive SMS, push notifications, behavioral automation, and advanced campaign management capabilities.

---

## Quick Start

### 1. Database Setup

Run the migration to create all Phase 6B tables:

```sql
-- Execute: database/migrations/communication_tables_phase6b.sql
mysql -u user -p database_name < database/migrations/communication_tables_phase6b.sql
```

**New Tables Created:**
- `push_subscriptions` - Device push registrations
- `push_notification_logs` - Push delivery history
- `in_app_notifications` - In-app notification persistence
- `automation_rules` - Behavioral automation rules
- `automation_executions` - Automation trigger logs
- `campaign_ab_tests` - A/B test configurations
- `campaign_schedules` - Campaign scheduling
- `abandoned_carts` - Abandoned cart tracking
- `customer_engagement_scores` - Engagement metrics

### 2. Configuration

Add to `.env`:

```env
# SMS Configuration
SMS_PROVIDER=africastalking          # or 'twilio'
SMS_API_KEY=your_api_key
SMS_API_SECRET=your_api_secret
SMS_SENDER_ID=GlamByMariga

# Twilio (if using Twilio)
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=+1234567890

# Push Notifications
PUSH_PUBLIC_KEY=your_vapid_public_key
PUSH_PRIVATE_KEY=your_vapid_private_key
```

### 3. Service Integration

Include services in your application:

```php
use GlamByMariga\Communication\PushNotificationService;
use GlamByMariga\Communication\SMSService;
use GlamByMariga\Communication\CampaignService;
use GlamByMariga\Communication\BehavioralAutomationService;

$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

$pushService = new PushNotificationService($db);
$smsService = new SMSService($db);
$emailService = new EmailService($db);
$campaignService = new CampaignService($db, $emailService);
$automationService = new BehavioralAutomationService($db, $emailService, $pushService, $smsService);
```

---

## Features & Usage

### Push Notifications

#### Client-Side Registration

```html
<!-- Include Push Notification Manager -->
<script src="/js/push-notifications.js"></script>

<!-- Notification Center Toggle -->
<button id="notification-bell" class="notification-bell">
    🔔
    <span class="notification-badge">0</span>
</button>

<!-- Notification Center Container -->
<div id="notification-center" class="notification-center"></div>
```

#### Server-Side Sending

```php
// Send web push notification
$result = $pushService->sendWebPush(
    $customerId,
    'Order Confirmed',
    'Your order #123 has been confirmed!',
    '/images/order-icon.png',
    '/orders?id=123'
);

// Send in-app notification
$result = $pushService->sendInAppNotification(
    $customerId,
    'Special Promotion',
    'Get 20% off on your next purchase!',
    'promotion',
    '/shop?promo=SAVE20'
);
```

#### Features:
- ✅ Web Push Protocol support
- ✅ In-app notification persistence
- ✅ Deep linking with action URLs
- ✅ Quiet hours/Do Not Disturb
- ✅ Multiple subscription support
- ✅ Engagement tracking

### SMS Integration

#### Africastalking Configuration

```php
// .env
SMS_PROVIDER=africastalking
SMS_API_KEY=your_africastalking_api_key
SMS_SENDER_ID=GlamByMariga
```

#### Twilio Configuration

```php
// .env
SMS_PROVIDER=twilio
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=+1234567890
```

#### Sending SMS

```php
// Send appointment reminder
$result = $smsService->sendAppointmentReminder($bookingId);

// Send order status
$result = $smsService->sendOrderStatus($orderId, 'shipped');

// Send custom message
$result = $smsService->send('+254712345678', 'Your message here');
```

### Campaign Management

#### Create Campaign

```php
$result = $campaignService->createCampaign([
    'name' => 'Summer Collection Launch',
    'description' => 'Introducing our new summer beauty collection',
    'template_id' => 5,
    'target_segment' => 'repeat'
]);
```

#### Schedule Campaign

```php
$campaignService->scheduleCampaign($campaignId, [
    'schedule_type' => 'scheduled',
    'send_at' => '2026-08-15 10:00:00',
    'timezone' => 'Africa/Nairobi',
    'optimal_send_time' => true,
    'optimal_send_hour' => 10
]);
```

#### A/B Testing

```php
// Create A/B test
$testId = $campaignService->createABTest($campaignId, [
    'name' => 'Subject Line Test',
    'variant_a_template_id' => 5,
    'variant_b_template_id' => 6,
    'variant_a_subject' => 'Exclusive: 30% Off Summer Collection',
    'variant_b_subject' => '⭐ New Summer Beauty Products Inside',
    'split_percentage' => 50,
    'metric' => 'open_rate',
    'test_duration_days' => 3
]);

// Determine winner after test duration
$result = $campaignService->determineABTestWinner($testId);
// Result: { winner: 'A', results: [...] }
```

#### Segment Targeting

```php
// Get recipients for segment
$recipients = $campaignService->getSegmentRecipients('high_value');
// Returns: [{ id, email, name }, ...]

// Available segments:
// - 'all' - All customers
// - 'repeat' - Multiple purchases
// - 'high_value' - Spent >KES 50,000
// - 'inactive' - No orders in 90+ days
// - 'new' - Registered in last 30 days
```

#### Send Campaign

```php
$result = $campaignService->sendCampaign($campaignId);
// Result: { success: true, sent_count: 245, failed_count: 2 }
```

### Behavioral Automation

#### Create Automation Rule

```php
// Abandoned cart reminder after 1 hour
$automationService->createRule([
    'name' => 'Cart Reminder - 1 Hour',
    'trigger_type' => 'abandoned_cart',
    'trigger_condition' => ['hours_ago' => 1],
    'action_type' => 'multi',
    'action_config' => ['channels' => ['email', 'sms']],
    'target_segment' => 'all'
]);

// Win-back campaign for at-risk customers
$automationService->createRule([
    'name' => 'Win-Back for At-Risk',
    'trigger_type' => 'low_engagement',
    'trigger_condition' => ['engagement_threshold' => 30],
    'action_type' => 'email',
    'action_config' => ['discount_code' => 'WINBACK20'],
    'target_segment' => 'inactive'
]);

// Birthday offer
$automationService->createRule([
    'name' => 'Birthday Special',
    'trigger_type' => 'birthday',
    'action_type' => 'email',
    'action_config' => ['discount_code' => 'BIRTHDAY20']
]);
```

#### Track Abandoned Carts

```php
// When customer leaves checkout
$automationService->trackAbandonedCart(
    $customerId,
    $cartItems,      // Array of products
    $cartValue       // Total cart value
);
```

#### Send Cart Reminder

```php
$result = $automationService->sendAbandonedCartReminder($customerId);
// Sends email + SMS (if configured) with recovery link
```

#### Calculate Engagement Score

```php
$result = $automationService->calculateEngagementScore($customerId);
// Result:
// {
//   engagement_score: 65.5,
//   email_open_rate: 45.2,
//   email_click_rate: 12.8,
//   is_at_risk: false,
//   risk_score: 0
// }

// Engagement Scoring (0-100):
// - Email open rate: 30% weight
// - Email click rate: 20% weight
// - Order frequency: 25% weight
// - Lifetime value: 25% weight
//
// At-risk detection:
// - Score < 40 AND last order > 90 days ago
```

#### Send Win-Back Campaign

```php
$result = $automationService->sendWinBackCampaign($customerId);
// Automatically generates discount code and sends email
```

#### Process All Rules

```php
$result = $automationService->processAutomationRules();
// Executes all active automation rules
// Result: { results: [{ rule_id, rule_name, success, executed }, ...] }
```

#### Get At-Risk Customers

```php
$customers = $automationService->getAtRiskCustomers($limit = 50);
// Returns array of at-risk customers with engagement metrics
```

---

## API Endpoints

### Push Notifications

**GET** `/ajax/communication/push-notifications.php`
```json
Response:
{
    "success": true,
    "notifications": [
        {
            "id": 1,
            "customer_id": 123,
            "title": "Order Confirmed",
            "message": "Your order has been confirmed",
            "type": "order",
            "is_read": false,
            "created_at": "2026-08-02 10:30:00"
        }
    ]
}
```

**POST** `/ajax/communication/push-notifications.php`
```json
{
    "action": "register_subscription|mark_read|send_promotion",
    "subscription": {
        "endpoint": "https://...",
        "keys": {
            "p256dh": "...",
            "auth": "..."
        }
    },
    "notification_id": 1,
    "title": "Promotion",
    "message": "20% off today",
    "promo_code": "SUMMER20"
}
```

### Automation

**GET** `/ajax/communication/automation.php`
```json
Response:
{
    "success": true,
    "statistics": {
        "total_rules": 5,
        "active_rules": 4,
        "total_executions": 1240
    }
}
```

**POST** `/ajax/communication/automation.php`
```json
{
    "action": "create_rule|track_abandoned_cart|send_cart_reminder|calculate_engagement|win_back_campaign|process_rules|at_risk_customers",
    "rule": { "name": "...", "trigger_type": "...", ... },
    "customer_id": 123,
    "cart_items": [...],
    "cart_value": 5000,
    "limit": 50
}
```

### Campaigns

**GET** `/ajax/communication/campaigns.php?action=list&page=1&limit=20`

**GET** `/ajax/communication/campaigns.php?action=analytics&campaign_id=1`

**POST** `/ajax/communication/campaigns.php`
```json
{
    "action": "create|schedule|send|create_ab_test|determine_winner|segment_recipients",
    "campaign_id": 1,
    "schedule": {
        "send_at": "2026-08-15 10:00:00",
        "timezone": "Africa/Nairobi"
    },
    "test": {
        "variant_a_template_id": 5,
        "variant_b_template_id": 6,
        "metric": "open_rate"
    }
}
```

---

## Admin Dashboard

Access communications management at `/admin/communications.html`

**Features:**
- 📊 Campaign metrics and analytics
- 🤖 Automation rule management
- 📈 Customer engagement scoring
- ⚠️ At-risk customer identification
- 🔔 SMS & push notification settings
- 📧 Template management
- 📞 SMS provider configuration

---

## Scheduled Tasks (Cron)

### Run Automation Rules (Every hour)

```bash
0 * * * * php /path/to/automation-runner.php
```

**automation-runner.php:**
```php
<?php
require_once 'config/database.php';
require_once 'includes/communication/BehavioralAutomationService.php';

$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
$automationService = new BehavioralAutomationService($db);
$result = $automationService->processAutomationRules();

error_log('Automation rules executed: ' . json_encode($result));
?>
```

### Send Scheduled Campaigns (Every 15 minutes)

```bash
*/15 * * * * php /path/to/campaign-scheduler.php
```

### Calculate Engagement Scores (Daily - 2 AM)

```bash
0 2 * * * php /path/to/engagement-calculator.php
```

---

## Testing

### Manual Testing

```bash
# Test SMS sending
curl -X POST http://localhost/ajax/communication/push-notifications.php \
  -H "Content-Type: application/json" \
  -d '{"action":"send_promotion","title":"Test","message":"Test message"}'

# Test automation
curl -X POST http://localhost/ajax/communication/automation.php \
  -H "Content-Type: application/json" \
  -d '{"action":"calculate_engagement","customer_id":123}'

# Test campaigns
curl -X GET http://localhost/ajax/communication/campaigns.php?action=list
```

### Unit Testing

```php
// tests/CampaignServiceTest.php
use GlamByMariga\Communication\CampaignService;

class CampaignServiceTest extends TestCase {
    public function testCreateCampaign() {
        $result = $this->campaignService->createCampaign([
            'name' => 'Test Campaign',
            'target_segment' => 'all'
        ]);
        $this->assertTrue($result['success']);
    }

    public function testABTesting() {
        $testId = $this->campaignService->createABTest($campaignId, [
            'variant_a_template_id' => 1,
            'variant_b_template_id' => 2
        ]);
        $this->assertNotNull($testId);
    }
}
```

---

## Deployment Checklist

- [ ] Run database migrations (communication_tables_phase6b.sql)
- [ ] Configure SMS provider credentials in .env
- [ ] Generate and configure VAPID keys for push notifications
- [ ] Set up service worker (public/service-worker.js)
- [ ] Include push notification script in HTML (public/js/push-notifications.js)
- [ ] Include notification styles in HTML (public/css/notifications.css)
- [ ] Configure cron jobs for automation and campaigns
- [ ] Test SMS sending with test phone number
- [ ] Test push notifications in browser
- [ ] Create sample automation rules
- [ ] Train staff on communications dashboard
- [ ] Monitor email, SMS, and push delivery rates
- [ ] Set up analytics dashboard for engagement tracking

---

## Performance Considerations

### Database Optimization

```sql
-- Index key columns for faster queries
CREATE INDEX idx_automation_rules_active ON automation_rules(is_active);
CREATE INDEX idx_abandoned_carts_recovered ON abandoned_carts(recovered_at);
CREATE INDEX idx_engagement_scores_at_risk ON customer_engagement_scores(is_at_risk);
CREATE INDEX idx_campaign_schedules_next_send ON campaign_schedules(next_send_at);
```

### Optimization Tips

1. **Batch Processing**: Process automation rules in batches to avoid timeouts
2. **Caching**: Cache segment recipient lists for 1 hour
3. **Throttling**: Limit SMS/push sending to 100/second
4. **Archive**: Archive old notification logs quarterly
5. **Indexing**: Index frequently queried columns (customer_id, created_at, status)

---

## Troubleshooting

### SMS Not Sending

```php
// Check SMS logs
SELECT * FROM sms_messages WHERE status = 'failed' ORDER BY created_at DESC;

// Verify provider credentials in .env
// Test with SMSService directly:
$smsService = new SMSService();
$result = $smsService->send('+254712345678', 'Test message');
var_dump($result);
```

### Push Notifications Not Appearing

```php
// Check push subscriptions exist
SELECT * FROM push_subscriptions WHERE customer_id = ? AND is_active = TRUE;

// Check service worker registered
// In browser console: navigator.serviceWorker.controller

// Check push notification logs
SELECT * FROM push_notification_logs WHERE customer_id = ? ORDER BY created_at DESC;
```

### Campaign Not Sending

```php
// Check campaign schedule
SELECT * FROM campaign_schedules WHERE campaign_id = ? AND is_active = TRUE;

// Check recipients were added
SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = ?;

// Check email logs for failures
SELECT * FROM email_logs WHERE subject LIKE '%campaign_name%' AND status = 'failed';
```

---

## Future Enhancements

### Phase 7 - AI-Powered Communications
- Predictive send-time optimization
- Personalized content blocks
- Churn prediction models
- Dynamic product recommendations
- AI-generated subject lines

### Phase 8 - Advanced Analytics
- Customer lifetime value prediction
- Cohort analysis
- Attribution modeling
- Multi-touch attribution
- Customer journey mapping

---

**Phase 6B Status:** ✅ COMPLETE

**Components:**
- ✅ Push Notification Service
- ✅ SMS Service (Africastalking & Twilio)
- ✅ Campaign Service with A/B testing
- ✅ Behavioral Automation Service
- ✅ Client-side Push Notification Manager
- ✅ Service Worker implementation
- ✅ Admin Communications Dashboard
- ✅ Comprehensive API endpoints
- ✅ Database schema and migrations

**Next Steps:**
1. Run database migrations
2. Configure SMS credentials
3. Set up service worker and push keys
4. Deploy cron jobs
5. Test all systems end-to-end
6. Train admin staff on dashboard
7. Monitor initial metrics
8. Optimize based on performance
