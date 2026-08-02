# M-Pesa Daraja API Integration Guide

## Overview

GlamByMariga is now integrated with M-Pesa Daraja API for secure, real-time payment processing. This guide documents the implementation, testing, and deployment.

---

## Architecture

### Components

1. **MpesaGateway** (`includes/payment/MpesaGateway.php`)
   - Handles API authentication
   - Manages STK Push requests
   - Queries transaction status
   - Token caching for performance

2. **PaymentProcessor** (`includes/payment/PaymentProcessor.php`)
   - Orchestrates payment flows
   - Creates and updates transactions
   - Processes callbacks
   - Handles audit logging

3. **PaymentValidator** (`includes/payment/PaymentValidator.php`)
   - Validates all input data
   - Ensures data integrity
   - Prevents injection attacks

4. **Frontend Handler** (`public/js/mpesa-payment.js`)
   - Initiates payments
   - Polls payment status
   - Shows user feedback
   - Handles timeouts

---

## Configuration

### Environment Variables (.env)

```env
# M-Pesa Daraja API
MPESA_ENVIRONMENT=production
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_BUSINESS_SHORTCODE=174379
MPESA_PASSKEY=your_passkey
MPESA_INITIATOR_NAME=testapi
MPESA_INITIATOR_PASSWORD=your_password
MPESA_PARTY_A=600992
MPESA_PARTY_B=600000
MPESA_CALLBACK_URL=https://glambymariga.com/public/ajax/mpesa/callback.php
MPESA_TIMEOUT_URL=https://glambymariga.com/public/ajax/mpesa/timeout.php
```

### Database Schema

Run the migration:

```bash
mysql -u root glambymariga_db < database/migrations/mpesa_payment_tables.sql
```

**Tables Created:**
- `mpesa_transactions` - Payment records
- `payment_retries` - Failed payment retry attempts
- `payment_audit_logs` - Audit trail for compliance
- `mpesa_webhook_logs` - M-Pesa callback logs

---

## Payment Flow

### 1. Initiation

```javascript
// Frontend
const result = await fetch('/public/ajax/mpesa/stk-push.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        booking_id: 123,
        amount: 3500,
        phone_number: '254712345678'
    })
});

// Backend creates transaction and initiates STK Push
// Returns checkout_request_id for tracking
```

### 2. Customer Action

- M-Pesa prompt appears on customer's phone
- Customer enters PIN to authorize
- M-Pesa processes payment

### 3. Callback Processing

- M-Pesa sends callback to `MPESA_CALLBACK_URL`
- System verifies transaction
- Updates booking payment status
- Sends confirmation email

### 4. Status Polling

- Frontend polls `/ajax/mpesa/transaction-query.php`
- Checks transaction status every 5 seconds
- Continues for 5 minutes max
- Updates UI based on status

---

## API Endpoints

### STK Push (Initiate Payment)

**POST** `/public/ajax/mpesa/stk-push.php`

**Request:**
```json
{
    "booking_id": 123,
    "amount": 3500,
    "phone_number": "254712345678"
}
```

**Response (Success):**
```json
{
    "success": true,
    "data": {
        "transaction_id": 42,
        "checkout_request_id": "ws_CO_DMZ_12345",
        "amount": 3500,
        "phone": "254712345678",
        "customer_message": "Enter your M-Pesa PIN"
    }
}
```

### Callback Handler

**POST** `/public/ajax/mpesa/callback.php`

*Automatically processes M-Pesa callbacks. No manual action needed.*

### Query Transaction Status

**GET** `/public/ajax/mpesa/transaction-query.php?transaction_id=42`

**Response:**
```json
{
    "success": true,
    "transaction": {
        "id": 42,
        "booking_id": 123,
        "amount": 3500,
        "status": "completed",
        "mpesa_receipt_number": "RKT12345",
        "timestamp": "2024-08-02 14:30:00"
    }
}
```

---

## Testing

### Demo Page

Navigate to `/payment-demo.html` to test the payment flow.

**Test Credentials:**
- Phone: 254708374149
- Booking ID: 1
- Amounts: 2500, 3500, 5000, 7500

**Sandbox Mode** (for testing without real charges):

1. Change `.env`:
   ```env
   MPESA_ENVIRONMENT=sandbox
   ```

2. Use Safaricom test numbers from developer portal

3. Transactions will be simulated

### Test Scenarios

| Scenario | Action | Result |
|----------|--------|--------|
| Successful Payment | Enter correct PIN | Status changes to "completed" |
| Wrong PIN | Enter wrong PIN 3 times | Status changes to "failed" |
| Timeout | Don't respond to prompt | Transaction expires after 5 min |
| Insufficient Balance | Insufficient M-Pesa funds | Status changes to "failed" |
| Network Error | System error occurs | Auto-retry up to 3 times |

---

## Security

### Features Implemented

✅ **Request Signing**
- All requests include timestamp-based signatures
- Prevents replay attacks

✅ **Callback Verification**
- Validates callback source
- Verifies amount consistency
- Logs all transactions

✅ **Input Validation**
- Phone number format validation
- Amount range validation (1-999999)
- Booking ID existence check

✅ **Audit Logging**
- Every transaction logged with:
  - IP address
  - User agent
  - Action taken
  - Timestamp

✅ **Rate Limiting** (Optional)
- Implement per-user limits
- Prevent abuse/spam

✅ **Data Encryption**
- Credentials stored in `.env`
- HTTPS required in production
- Database credentials secure

---

## Error Handling

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| `INVALID_AMOUNT` | Amount out of range | Use 1-999999 |
| `INVALID_PHONE` | Wrong format | Use 254712345678 or 0712345678 |
| `BOOKING_NOT_FOUND` | Booking doesn't exist | Verify booking ID |
| `GATEWAY_ERROR` | M-Pesa API error | Check credentials, retry |
| `CALLBACK_PROCESSING_FAILED` | Webhook processing issue | Check server logs |
| `PAYMENT_TIMEOUT` | No response for 5 min | Resend payment request |

### Logging

All errors logged to:
- `logs/error.log` - Application errors
- `logs/payment.log` - Payment-specific logs
- `mpesa_webhook_logs` table - M-Pesa callbacks

---

## Integration Examples

### Booking Payment

```php
<?php
use GlamByMariga\Payment\MpesaGateway;
use GlamByMariga\Payment\PaymentProcessor;
use GlamByMariga\Payment\PaymentValidator;

$gateway = new MpesaGateway();
$validator = new PaymentValidator();
$processor = new PaymentProcessor($db, $gateway, $validator);

// When booking is created
$result = $processor->initiateBookingPayment(
    $bookingId,
    $bookingAmount,
    $customerPhoneNumber
);

if ($result['success']) {
    // Store transaction ID in session
    $_SESSION['transaction_id'] = $result['transaction_id'];
}
?>
```

### Frontend Integration

```html
<script src="/public/js/mpesa-payment.js"></script>

<button onclick="payBooking()">Pay with M-Pesa</button>

<script>
function payBooking() {
    mpesaPaymentHandler.initiateBookingPayment(
        123,           // bookingId
        3500,          // amount in KES
        '254712345678', // phone
        (result) => {
            // Success
            console.log('Payment confirmed:', result);
            // Redirect or update UI
        },
        (error) => {
            // Error
            console.error('Payment failed:', error);
            alert('Payment failed: ' + error);
        }
    );
}
</script>
```

---

## Deployment

### Pre-Deployment Checklist

- [ ] M-Pesa Daraja account created
- [ ] Consumer Key/Secret obtained
- [ ] Business Shortcode confirmed
- [ ] Passkey generated
- [ ] `.env` configured with production credentials
- [ ] Callback URL accessible from internet
- [ ] HTTPS certificate installed
- [ ] Database migrations run
- [ ] Payment tables verified
- [ ] Email service configured
- [ ] Error logging configured
- [ ] Backups scheduled

### Production Setup

1. **Update .env:**
   ```env
   MPESA_ENVIRONMENT=production
   MPESA_CALLBACK_URL=https://glambymariga.com/public/ajax/mpesa/callback.php
   ```

2. **Create tables:**
   ```bash
   mysql -u user -p database < database/migrations/mpesa_payment_tables.sql
   ```

3. **Test callback:**
   - Send test transaction from M-Pesa portal
   - Verify callback is received
   - Check `mpesa_webhook_logs` table

4. **Monitor payments:**
   - Set up alerts for failed payments
   - Review `payment_audit_logs` regularly
   - Reconcile transactions weekly

---

## Monitoring & Analytics

### Dashboard Metrics (Phase 2+)

- Total payments processed
- Success rate
- Average transaction time
- Failed payments by reason
- Daily/weekly/monthly revenue

### Queries

```sql
-- Total revenue
SELECT SUM(amount) as total_revenue 
FROM mpesa_transactions 
WHERE status = 'completed';

-- Success rate
SELECT 
    COUNT(CASE WHEN status='completed' THEN 1 END) * 100 / COUNT(*) as success_rate
FROM mpesa_transactions;

-- Failed transactions
SELECT * FROM mpesa_transactions WHERE status='failed' ORDER BY timestamp DESC;

-- Audit trail for transaction
SELECT * FROM payment_audit_logs WHERE transaction_id = 42 ORDER BY created_at;
```

---

## Troubleshooting

### Issue: Callback not received

**Solution:**
1. Verify callback URL is publicly accessible
2. Check firewall allows M-Pesa IPs
3. Monitor `mpesa_webhook_logs` table
4. Check application logs for errors

### Issue: Token expiry errors

**Solution:**
- Token is auto-cached for 3599 seconds
- Refresh by deleting `$this->accessToken`
- Check consumer credentials are correct

### Issue: Amount mismatch in callback

**Solution:**
- Verify database transaction amount
- Check for rounding issues in calculations
- Ensure consistent decimal places

---

## Support & Resources

- **Daraja Documentation:** https://developer.safaricom.co.ke
- **Support Email:** info@glambymariga.com
- **Emergency Hotline:** +254 712 345 678

---

**Last Updated:** August 2, 2024  
**Version:** 1.0  
**Status:** Production Ready
