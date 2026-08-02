# Customer Communication & Email Integration - Phase 6 Guide

## Overview

Phase 6 introduces a comprehensive customer communication system with:
- Email notification automation
- Customizable email templates
- Customer communication preferences
- Marketing campaign management
- Customer feedback system
- Newsletter management
- SMS integration foundation

---

## Architecture

### Core Services

#### Email Service (`includes/communication/EmailService.php`)
Handles all email sending operations with PHPMailer integration.

**Key Methods:**
- `send()` - Send direct email with HTML and plain text
- `sendTemplate()` - Send templated email with variable replacement
- `sendBulk()` - Send bulk emails with rate limiting
- Built-in templates for common communications

**Supported Email Types:**
1. Order Confirmation
2. Order Shipped (with tracking)
3. Order Delivered (with review request)
4. Appointment Confirmed
5. Appointment Reminder
6. Welcome Email
7. Password Reset
8. Review Request

#### Notification Service (`includes/communication/NotificationService.php`)
Orchestrates notification delivery across channels.

**Key Methods:**
- `notifyOrderConfirmation()` - Send order confirmation email
- `notifyOrderShipped()` - Send shipping notification with tracking
- `notifyOrderDelivered()` - Send delivery notification
- `notifyAppointmentConfirmed()` - Send appointment confirmation
- `notifyAppointmentReminder()` - Send 24-hour reminder
- `sendWelcomeEmail()` - Welcome new customers
- `sendReviewRequest()` - Request product review
- `getPreferences()` - Get customer communication preferences
- `updatePreferences()` - Update customer preferences

### Database Schema

#### Core Tables

**email_templates** - Store customizable email templates
- name (unique): Template identifier
- subject: Email subject line
- body: HTML email template
- is_active: Enable/disable template

**email_logs** - Track all sent emails
- to_address: Recipient email
- subject: Email subject
- status: sent, failed, bounced, opened, clicked
- opened_at, clicked_at: Track engagement
- error: Failure reason if applicable

**notification_preferences** - Customer communication settings
- customer_id: Customer reference
- email_orders, email_appointments, email_promotions, email_reviews: Toggle by type
- sms_alerts, sms_phone: SMS preferences
- created_at, updated_at: Preference history

**notifications** - Audit trail of all notifications
- customer_id, type, reference_id
- status: sent, failed, pending
- created_at: Send timestamp

**marketing_campaigns** - Campaign management
- name, description: Campaign details
- template_id: Email template used
- target_segment: all, repeat, high_value, inactive customers
- status: draft, scheduled, sent, cancelled
- scheduled_at, sent_at: Timing info
- recipients_count, opened_count, clicked_count: Analytics

**campaign_recipients** - Campaign delivery tracking
- campaign_id, customer_id: Campaign + customer reference
- status: pending, sent, failed, opened, clicked
- opened_at, clicked_at, sent_at: Engagement tracking

**customer_feedback** - Feedback system
- customer_id, type: Feedback type (product, service, support, general)
- rating: 1-5 star rating
- subject, message: Feedback content
- status: new, read, responded
- response, responded_by, responded_at: Admin response

**newsletter_subscriptions** - Newsletter management
- email: Subscriber email
- customer_id: Optional customer link
- status: subscribed, unsubscribed, bounced

**sms_messages** - SMS communication log
- customer_id, phone_number
- message, status
- provider, provider_id: SMS provider tracking

---

## API Endpoints

### Send Notification
**POST** `/ajax/communication/send-notification.php`
```json
{
    "type": "order_confirmation|order_shipped|appointment_reminder|...",
    "reference_id": 123,
    "tracking_number": "TRK123456" // optional for shipped
}

Response:
{
    "success": true,
    "message": "Email sent successfully"
}
```

### Notification Preferences
**GET** `/ajax/communication/notification-preferences.php`
```
Returns customer's current notification preferences
Response:
{
    "success": true,
    "preferences": {
        "email_orders": true,
        "email_appointments": true,
        "email_promotions": true,
        "email_reviews": true,
        "sms_alerts": false,
        "sms_phone": null
    }
}
```

**POST** `/ajax/communication/notification-preferences.php`
```json
{
    "email_orders": true,
    "email_appointments": true,
    "email_promotions": false,
    "email_reviews": true,
    "sms_alerts": false
}

Response:
{
    "success": true,
    "message": "Preferences updated"
}
```

---

## Email Templates

### 1. Order Confirmation
**Trigger:** When order is created  
**Variables:** customer_name, order_id, order_total, order_date  
**Design:** Rose gold themed with order summary

### 2. Order Shipped
**Trigger:** When order status changes to shipped  
**Variables:** customer_name, order_id, tracking_number, delivery_date, carrier, tracking_url  
**Design:** Includes tracking link and delivery estimate

### 3. Order Delivered
**Trigger:** When order is delivered  
**Variables:** customer_name, order_id, review_url  
**Design:** Encourages customer to leave review

### 4. Appointment Confirmed
**Trigger:** When booking is confirmed  
**Variables:** customer_name, service_name, appointment_date, appointment_time, duration, price  
**Design:** Clear appointment details

### 5. Appointment Reminder
**Trigger:** 24 hours before appointment  
**Variables:** customer_name, service_name, appointment_time, location  
**Design:** Friendly reminder with location

### 6. Welcome Email
**Trigger:** New customer registration  
**Variables:** customer_name, shop_url  
**Design:** Introduction and shop invitation

### 7. Password Reset
**Trigger:** Password reset request  
**Variables:** customer_name, reset_url  
**Design:** Secure reset link with expiration info

### 8. Review Request
**Trigger:** Post-delivery  
**Variables:** customer_name, product_name, review_url  
**Design:** Encourages feedback

---

## Configuration

### Environment Variables (`.env`)
```
MAIL_DRIVER=smtp|mail
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=TLS
MAIL_FROM_EMAIL=noreply@glambymariga.com
MAIL_FROM_NAME=GlamByMariga
```

### Fallback
If SMTP not configured, uses PHP's native `mail()` function.

---

## Integration Points

### Phase 4 (E-Commerce)
- **Trigger on Order Confirmation**: Send order_confirmation email
- **Trigger on Status Change**: Send order_shipped when status → "shipped"
- **Trigger on Delivery**: Send order_delivered when status → "delivered"
- **Trigger on Review Prompt**: Send review_request to delivered orders

### Phase 3 (Appointments)
- **Trigger on Booking**: Send appointment_confirmed email
- **Cron Job**: Send appointment_reminder 24 hours before

### Dashboard (Phase 5)
- Admin can view email logs
- Admin can manage campaigns
- Admin can view customer feedback

---

## Features

### Email Automation
✅ Automatic order confirmations  
✅ Shipping notifications with tracking  
✅ Delivery confirmations  
✅ Appointment reminders (24 hours before)  
✅ Welcome emails for new customers  
✅ Review requests post-delivery  

### Customer Control
✅ Notification preference settings  
✅ Opt-out for promotional emails  
✅ SMS alert toggle  
✅ Per-communication-type controls  

### Marketing
✅ Campaign templates  
✅ Segment targeting (repeat, high-value, inactive customers)  
✅ Open and click tracking  
✅ Campaign analytics  
✅ Newsletter management  

### Feedback
✅ Customer feedback form  
✅ 1-5 star ratings  
✅ Feedback categorization  
✅ Admin response tracking  

### Audit Trail
✅ Email delivery logs  
✅ Engagement tracking (opened, clicked)  
✅ Notification history  
✅ Campaign performance metrics  

---

## Usage Examples

### Send Order Confirmation
```php
$notificationService->notifyOrderConfirmation($orderId);
// Automatically gets customer email and order details
// Sends templated email with order summary
```

### Send Appointment Reminder
```php
$notificationService->notifyAppointmentReminder($bookingId);
// Sends reminder 24 hours before appointment
// Includes service name, time, and location
```

### Update Customer Preferences
```php
$notificationService->updatePreferences($customerId, [
    'email_orders' => true,
    'email_appointments' => true,
    'email_promotions' => false,
    'sms_alerts' => false
]);
```

### Custom Email Template
```php
$emailService->sendTemplate(
    'customer@example.com',
    'custom_template_name',
    [
        'customer_name' => 'John Doe',
        'custom_variable' => 'value'
    ]
);
```

---

## Future Enhancements

### Phase 6B (Advanced Features)
- SMS integration (Twilio, Africastalking)
- Push notifications
- In-app notifications
- Email A/B testing
- Advanced campaign scheduling
- Behavioral trigger automation

### Phase 7 (AI-Powered)
- Predictive send times
- Content personalization
- Churn prediction
- Recommendation emails
- Dynamic content blocks

---

## Deployment Checklist

- [ ] Configure email settings (.env)
- [ ] Run database migrations (communication_tables.sql)
- [ ] Test SMTP connection
- [ ] Set up email templates
- [ ] Configure default preferences
- [ ] Test all notification types
- [ ] Set up email bouncing/complaint handling
- [ ] Monitor email delivery rates
- [ ] Set up email authentication (SPF, DKIM, DMARC)
- [ ] Create admin email management panel (Phase 5 enhancement)
- [ ] Set up reminder cron jobs
- [ ] Test campaign sending
- [ ] Monitor email logs

---

## Phase 6B - Advanced Communications

### New Services

#### Push Notification Service (`includes/communication/PushNotificationService.php`)
Web push and in-app notifications with subscription management.

**Key Methods:**
- `sendWebPush()` - Send browser push notifications
- `sendInAppNotification()` - Send in-app notifications
- `registerSubscription()` - Register device for push notifications
- `getInAppNotifications()` - Retrieve unread in-app notifications
- `markAsRead()` - Mark notification as read
- `notifyOrderStatus()` - Send order status notifications
- `sendPromotion()` - Send promotional notifications

**Features:**
- Web Push Protocol support
- In-app notification persistence
- Quiet hours support
- Notification history tracking
- Deep linking with action URLs

#### SMS Service (`includes/communication/SMSService.php`)
SMS delivery via Africastalking or Twilio.

**Key Methods:**
- `send()` - Send SMS message
- `sendViaAfricasTalking()` - Africastalking provider
- `sendViaTwilio()` - Twilio provider
- `sendAppointmentReminder()` - Appointment reminder SMS
- `sendOrderStatus()` - Order status SMS

**Configuration:**
```
SMS_PROVIDER=africastalking|twilio
SMS_API_KEY=your_api_key
SMS_API_SECRET=your_api_secret
SMS_SENDER_ID=GlamByMariga
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=+1234567890
```

#### Campaign Service (`includes/communication/CampaignService.php`)
Advanced campaign scheduling, A/B testing, and segment targeting.

**Key Methods:**
- `createCampaign()` - Create marketing campaign
- `scheduleCampaign()` - Schedule campaign with recurrence
- `createABTest()` - Create A/B test variant
- `determineABTestWinner()` - Analyze test results
- `sendCampaign()` - Send campaign to segment
- `getSegmentRecipients()` - Get recipients by segment
- `getCampaignAnalytics()` - Get performance metrics

**Segments:**
- `all` - All customers
- `repeat` - Customers with multiple orders
- `high_value` - Top spenders (>KES 50,000)
- `inactive` - No orders in 90+ days
- `new` - Registered in last 30 days

#### Behavioral Automation Service (`includes/communication/BehavioralAutomationService.php`)
Automated triggers based on customer behavior and engagement.

**Key Methods:**
- `createRule()` - Create automation rule
- `trackAbandonedCart()` - Track cart abandonment
- `sendAbandonedCartReminder()` - Abandoned cart email/SMS
- `calculateEngagementScore()` - Calculate customer engagement (0-100)
- `sendWinBackCampaign()` - Send win-back offer to at-risk customers
- `processAutomationRules()` - Execute all active rules
- `getAtRiskCustomers()` - List customers at churn risk

**Trigger Types:**
- `abandoned_cart` - When cart left for N hours
- `low_engagement` - When engagement score < threshold
- `birthday` - On customer birthday
- `anniversary` - On customer account anniversary
- `reorder_due` - When customer hasn't ordered in N days
- `low_stock_alert` - When product stock runs low
- `review_request` - Request review for delivered items
- `win_back` - Target at-risk customers
- `custom` - Custom trigger conditions

### Database Schema - Phase 6B

**New Tables:**
1. `push_subscriptions` - Device push subscriptions
2. `push_notification_logs` - Push delivery logs
3. `in_app_notifications` - In-app notification history
4. `automation_rules` - Automation rule definitions
5. `automation_executions` - Automation execution logs
6. `campaign_ab_tests` - A/B test configurations
7. `campaign_ab_test_recipients` - A/B test variant assignments
8. `campaign_schedules` - Campaign scheduling
9. `abandoned_carts` - Abandoned cart tracking
10. `customer_engagement_scores` - Engagement metrics
11. `push_notification_settings` - Customer push preferences

### API Endpoints - Phase 6B

#### Push Notifications
**POST** `/ajax/communication/push-notifications.php`
```json
{
    "action": "register_subscription|mark_read|send_promotion",
    "subscription": { "endpoint": "...", "keys": {...} },
    "notification_id": 123,
    "title": "Promotion",
    "message": "20% off today!"
}
```

#### Automation Management
**POST** `/ajax/communication/automation.php`
```json
{
    "action": "create_rule|track_abandoned_cart|send_cart_reminder|calculate_engagement|win_back_campaign|process_rules|at_risk_customers",
    "rule": { "name": "...", "trigger_type": "abandoned_cart", ... },
    "customer_id": 123,
    "cart_items": [...],
    "cart_value": 5000
}
```

#### Campaign Management
**POST** `/ajax/communication/campaigns.php`
```json
{
    "action": "create|schedule|send|create_ab_test|determine_winner|segment_recipients",
    "campaign_id": 1,
    "schedule": { "send_at": "2026-08-15 10:00:00", "timezone": "Africa/Nairobi" },
    "test": { "variant_a_template_id": 1, "variant_b_template_id": 2, "metric": "open_rate" }
}
```

### Engagement Scoring Algorithm

**Components (0-100 total):**
- Email open rate: 0-30 points (weighted 0.3)
- Email click rate: 0-20 points (weighted 0.2)
- Order frequency: 0-25 points (weighted 0.25)
- Lifetime value: 0-25 points (weighted 0.25)

**Risk Detection:**
- At-risk if: score < 40 AND last order > 90 days ago
- Win-back offer: 20% discount code auto-generated

### Automation Examples

**Abandoned Cart Reminder (1 hour):**
```php
$automationService->createRule([
    'name' => 'Cart Reminder - 1 hour',
    'trigger_type' => 'abandoned_cart',
    'trigger_condition' => ['hours_ago' => 1],
    'action_type' => 'multi',
    'action_config' => ['channels' => ['email', 'sms']]
]);
```

**Win-Back Campaign:**
```php
$automationService->createRule([
    'name' => 'Win-Back for At-Risk',
    'trigger_type' => 'low_engagement',
    'trigger_condition' => ['engagement_threshold' => 30],
    'action_type' => 'email',
    'target_segment' => 'inactive'
]);
```

**Birthday Offer:**
```php
$automationService->createRule([
    'name' => 'Birthday 20% Off',
    'trigger_type' => 'birthday',
    'action_type' => 'email',
    'action_config' => ['discount_code' => 'BIRTHDAY20']
]);
```

### A/B Testing Workflow

1. **Create Campaign**
   ```php
   $campaignService->createCampaign([
       'name' => 'Summer Sale',
       'target_segment' => 'repeat'
   ]);
   ```

2. **Setup A/B Test**
   ```php
   $campaignService->createABTest($campaignId, [
       'variant_a_template_id' => 5,
       'variant_b_template_id' => 6,
       'split_percentage' => 50,
       'metric' => 'open_rate',
       'test_duration_days' => 3
   ]);
   ```

3. **Send Campaign**
   ```php
   $campaignService->sendCampaign($campaignId);
   ```

4. **Analyze Results** (after test duration)
   ```php
   $campaignService->determineABTestWinner($testId);
   ```

### Feature Summary

✅ SMS integration (Africastalking & Twilio)
✅ Web push notifications
✅ In-app notification system
✅ Abandoned cart tracking
✅ Customer engagement scoring
✅ At-risk customer detection
✅ Win-back campaigns
✅ A/B testing for campaigns
✅ Advanced scheduling (recurring, optimal send time)
✅ Behavioral automation triggers
✅ Quiet hours / Do Not Disturb
✅ Multi-channel notifications
✅ Campaign analytics & reporting

---

**Phase 6 Implementation Status:** COMPLETE (Part 1 - Email Services + Part 2 - Advanced Communications)  
**Next:** Phase 6.5 - Admin Dashboard integration for communication management
