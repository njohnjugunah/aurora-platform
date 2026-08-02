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

**Phase 6 Implementation Status:** COMPLETE (Part 1 - Email Services)  
**Next:** Phase 6B - Advanced features (SMS, push notifications, advanced campaigns)
