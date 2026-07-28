# INTEGRATION_GUIDE.md

**Aurora Platform - External Integration Guide**

Version: 1.0.0  
Status: Active  
Last Updated: 2026-07-28

---

## OVERVIEW

Aurora Platform integrates with external services for payments, notifications, and future capabilities.

---

## 1. M-PESA INTEGRATION

### Purpose
Process mobile money payments via Daraja API

### Implementation
**File**: `src/Infrastructure/Integrations/MpesaGateway.php`

### Configuration (.env)
```
MPESA_ENVIRONMENT=sandbox          # sandbox or production
MPESA_BUSINESS_SHORT_CODE=123456
MPESA_CONSUMER_KEY=your_key
MPESA_CONSUMER_SECRET=your_secret
MPESA_PASSKEY=your_passkey
```

### Test Credentials (Sandbox)
- Consumer Key: [Contact Safaricom]
- Consumer Secret: [Contact Safaricom]
- Test Phone: +254700000000
- Passkey: [Contact Safaricom]

### API Operations

**1. STK Push (Initiate Payment)**
```php
$gateway = new MpesaGateway($logger);
$result = $gateway->initiateStkPush(
    '+254712345678',           // Customer phone
    5000,                      // Amount in KES
    'SALE-12345',             // Reference
    'Aurora Payment'          // Description
);
// Returns: checkoutRequestId for status checking
```

**2. Query Status**
```php
$status = $gateway->queryTransactionStatus($checkoutRequestId);
// Returns: ResultCode, ResultDesc, MerchantRequestID
```

**3. Refund**
```php
$refund = $gateway->processRefund(
    'mpesa_transaction_id',
    5000,
    'Customer requested refund'
);
// Returns: success status and refund ID
```

### Daraja API Endpoints

- **OAuth**: `https://sandbox.safaricom.co.ke/oauth/v1/generate`
- **STK Push**: `https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest`
- **Query**: `https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query`
- **Refund**: `https://sandbox.safaricom.co.ke/mpesa/reversal/v1/request`

### Webhook Callback
- Endpoint: `https://prod.glambymariga.local/webhooks/mpesa/callback`
- Method: POST
- Body: M-Pesa response with transaction details
- Required: Signature verification

### Testing

```bash
# Test STK push
curl -X POST https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest \
  -H "Authorization: Bearer $OAUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "BusinessShortCode": "123456",
    "Password": "$PASSWORD",
    "Timestamp": "20260728103000",
    "TransactionType": "CustomerPayBillOnline",
    "Amount": 5000,
    "PartyA": "254712345678",
    "PartyB": "123456",
    "PhoneNumber": "254712345678",
    "CallBackURL": "https://prod.glambymariga.local/webhooks/mpesa/callback",
    "AccountReference": "SALE-12345",
    "TransactionDesc": "Payment"
  }'
```

---

## 2. SMS INTEGRATION (TWILIO)

### Purpose
Send SMS appointment reminders and confirmations

### Phase
- **Phase 1**: Not integrated (placeholder)
- **Phase 2**: Implement Twilio integration

### Configuration (.env)
```
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=+1234567890
```

### API Methods (To Implement)
```php
public function sendAppointmentReminder(Appointment $appointment): bool
public function sendPaymentConfirmation(Payment $payment): bool
public function sendStockAlert(Product $product): bool
```

---

## 3. EMAIL INTEGRATION

### Purpose
Send receipts, invoices, and notifications via email

### Phase
- **Phase 1**: Not integrated (placeholder)
- **Phase 2**: Implement email integration

### Configuration (.env)
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASSWORD=your_app_password
```

### Templates
- `templates/receipt.html` - Sale receipt
- `templates/invoice.html` - Invoice
- `templates/appointment-reminder.html` - Appointment reminder

---

## 4. FUTURE INTEGRATIONS (Phase 3+)

### Xero Accounting
**Purpose**: Automatic journal entry creation

**API**: Xero OAuth 2.0
**Features**:
- Sales → Income entries
- Payments → Receipt entries
- Inventory → Cost tracking

### Mailchimp Email Marketing
**Purpose**: Customer newsletters

**API**: Mailchimp REST API
**Features**:
- Customer list sync
- Newsletter send

### Slack Notifications
**Purpose**: Team alerts

**API**: Slack Webhooks
**Events**:
- High revenue milestone
- Inventory alerts
- Error notifications

### Inventory Forecasting (AI)
**Purpose**: Predict stock needs

**Integration**: TensorFlow or similar
**Features**:
- Predict customer demand
- Optimize reorder quantities

---

## 5. WEBHOOK MANAGEMENT

### Webhook for M-Pesa Callback

**Endpoint**: `POST /webhooks/mpesa/callback`

**Security**:
1. Verify signature (Daraja provides)
2. Verify source IP (Daraja whitelist)
3. Update payment status in database
4. Emit event for side effects

**Example Implementation**:
```php
public function handleMpesaCallback(Request $request): Response {
    $payload = $request->json();
    
    // Verify signature
    if (!$this->verifySignature($payload)) {
        return response('Invalid signature', 403);
    }
    
    // Update payment
    $payment = Payment::find($payload['CheckoutRequestID']);
    $payment->status = 'verified';
    $payment->save();
    
    // Emit event
    event(new PaymentProcessed($payment));
    
    return response('OK', 200);
}
```

---

## 6. API RATE LIMITS

### M-Pesa Daraja API
- Limit: 50 requests per minute per API key
- Handling: Exponential backoff, queue requests

### Email Service
- Limit: 100 emails per minute
- Handling: Queue system with retry

### SMS Service
- Limit: 100 SMS per minute
- Handling: Queue system with retry

---

## 7. ERROR HANDLING

### Retry Strategy
```
Attempt 1: Immediate
Attempt 2: 5 seconds later
Attempt 3: 30 seconds later
Attempt 4: 5 minutes later
Attempt 5: 30 minutes later
Then: Manual intervention required
```

### Circuit Breaker
```
Threshold: 5 consecutive failures
Timeout: 60 seconds
Recovery: Test request after timeout
```

---

## 8. MONITORING INTEGRATIONS

### Sentry (Error Tracking)
- Capture all exceptions
- Track errors by version
- Alert on new error types

### Datadog (Performance Monitoring)
- Track API response times
- Monitor integration latency
- Alert on threshold violations

### Uptime Monitoring
- Health check every 5 minutes
- Alert if system down
- SMS alert for critical issues

---

## 9. COMPLIANCE & SECURITY

### PCI Compliance
- Never store credit card numbers
- All payments via secure gateway
- Audit trail for all transactions

### Data Privacy
- Customer data encrypted at rest
- GDPR compliant data handling
- Right to deletion implemented

### M-Pesa Security
- HTTPS only (TLS 1.2+)
- Signature verification
- OAuth 2.0 authentication
- Callback verification

---

## 10. TESTING INTEGRATIONS

### M-Pesa Sandbox Testing
```bash
# Test credentials available from Safaricom
# Sandbox phone: +254700000000
# Can test without real payment

# Load test
ab -n 1000 -c 50 https://sandbox.safaricom.co.ke/oauth/v1/generate
```

### Email Testing
```bash
# Mailtrap or similar for email testing
# Captures emails in development
# Doesn't send to real mailboxes
```

### Webhook Testing
```bash
# Local testing with ngrok
ngrok http 8080
# Then update callback URL in M-Pesa settings
```

---

## TROUBLESHOOTING

| Issue | Diagnosis | Solution |
|-------|-----------|----------|
| M-Pesa timeout | Check Daraja status | Retry after 30 sec |
| Wrong amount | Check currency | Ensure KES values |
| Callback not received | Verify callback URL | Test with ngrok |
| Email not sending | Check SMTP settings | Verify credentials |

---

**END OF INTEGRATION_GUIDE.md**

**Related Documents**: API_REFERENCE.md, DATABASE_SCHEMA.md, DEPLOYMENT_GUIDE.md
