# Security Guidelines - Aurora Platform

## ⚠️ CRITICAL: Pre-Deployment Security Checklist

Before deploying to production, you MUST complete all items below:

### 1. Credentials & Secrets Management

**❌ PROBLEM FOUND:** Real M-Pesa credentials were committed to `.env`

**✅ SOLUTION:**
```bash
# 1. Regenerate ALL M-Pesa credentials immediately
# - Visit: https://developer.safaricom.co.ke/
# - Generate new Consumer Key
# - Generate new Consumer Secret
# - Generate new Passkey
# - Save credentials in a password manager, NOT in code

# 2. Clean git history
git filter-branch --tree-filter 'rm -f .env' -- --all
git push --force origin main

# 3. Create .env from .env.example
cp .env.example .env

# 4. Update .env with production values
APP_ENV=production
DB_HOST=prod-db.internal
DB_USERNAME=aurora_prod_user
DB_PASSWORD=$(openssl rand -base64 32)
MPESA_CONSUMER_KEY=your-new-key-from-safaricom
MPESA_CONSUMER_SECRET=your-new-secret-from-safaricom
MPESA_PASSKEY=your-new-passkey-from-safaricom
JWT_SECRET=$(openssl rand -base64 32)
APP_KEY=base64:$(openssl rand -base64 32)
REDIS_PASSWORD=$(openssl rand -base64 32)
```

**Best Practices:**
- Use a secrets management service (AWS Secrets Manager, HashiCorp Vault, etc.)
- Rotate credentials every 90 days
- Use environment variables, never commit `.env`
- Audit `.gitignore` before every deployment

---

### 2. HTTPS & TLS Configuration

**❌ PROBLEM:** nginx.conf only had HTTP (port 80)

**✅ SOLUTION:**

The nginx config has been updated with:
- ✅ HTTP → HTTPS redirect
- ✅ TLS 1.2+ enforcement
- ✅ Modern cipher suite
- ✅ HSTS headers
- ✅ Security headers (X-Frame-Options, CSP, etc.)

**Production Setup:**
```bash
# Generate self-signed cert (for testing only)
openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365

# For production, use Let's Encrypt:
# https://letsencrypt.org/

# Or use AWS Certificate Manager, GCP SSL, etc.

# Place certificates in: ./docker/ssl/
# - cert.pem
# - key.pem

# Verify SSL:
openssl s_client -connect your-domain.com:443
```

---

### 3. Session Security

**❌ PROBLEM:** No session security configuration

**✅ SOLUTION:**

Bootstrap file created at `config/bootstrap.php` with:
- ✅ Session ID regeneration after login
- ✅ HttpOnly cookies (prevent XSS)
- ✅ Secure flag (HTTPS only)
- ✅ SameSite=Lax (CSRF prevention)
- ✅ Strict session mode

All AJAX endpoints should start with:
```php
<?php
require_once '../../config/bootstrap.php';
require_once '../../includes/security/AuthMiddleware.php';

use GlamByMariga\Security\AuthMiddleware;

// Check authentication
AuthMiddleware::requireAdmin();
```

---

### 4. CSRF Protection

**❌ PROBLEM:** No CSRF token validation

**✅ SOLUTION:**

Created `includes/security/CsrfToken.php` with:
- ✅ Token generation & verification
- ✅ Timing-safe comparison
- ✅ Form field injection helper

**Usage in HTML Forms:**
```php
<form method="POST" action="/api/endpoint">
    <?php echo CsrfToken::field(); ?>
    <!-- other fields -->
</form>
```

**Usage in AJAX:**
```javascript
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': document.querySelector('input[name="_token"]').value,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
});
```

---

### 5. Input Validation

**❌ PROBLEM:** Minimal validation, email injection possible

**✅ SOLUTION:**

Created `includes/security/InputValidator.php` with:
- ✅ Email validation
- ✅ Phone number validation (Kenya format)
- ✅ URL validation
- ✅ Type validation (int, float, string, date, JSON)
- ✅ HTML sanitization
- ✅ Array validation & sanitization

**Usage:**
```php
use GlamByMariga\Security\InputValidator;

// Validate email
if (!InputValidator::email($email)) {
    throw new Exception('Invalid email');
}

// Sanitize HTML
$safe = InputValidator::sanitizeHtml($userInput);

// Validate required fields
if (!InputValidator::requiredKeys($data, ['email', 'name', 'phone'])) {
    throw new Exception('Missing required fields');
}

// Extract only safe fields
$safe = InputValidator::extractFields($data, ['name', 'email', 'phone']);
```

---

### 6. M-Pesa Callback Security

**❌ PROBLEM:** No signature verification, no IP validation, no replay protection

**✅ SOLUTION:**

Updated `public/ajax/mpesa/callback.php` with:
- ✅ IP whitelist verification
- ✅ Timestamp validation (prevent replay attacks)
- ✅ Email injection prevention
- ✅ Proper email header handling

**Update M-Pesa IP Whitelist:**
```php
// In public/ajax/mpesa/callback.php, update trustedIps:
$trustedIps = [
    '196.201.214.0/24',  // Actual M-Pesa IP range
    '196.201.215.0/24',  // Update with current ranges
];
```

**Verify with M-Pesa:**
Contact Safaricom to get current IP ranges for callbacks.

---

### 7. Rate Limiting & Brute Force Protection

**✅ SOLUTION:**

Created `includes/security/RateLimiter.php`:
- ✅ Per-endpoint rate limiting
- ✅ Configurable attempts/window
- ✅ File-based cache (no external dependency)

**Usage in login endpoint:**
```php
use GlamByMariga\Security\AuthMiddleware;

// Check rate limit: 5 attempts per 60 seconds per IP
AuthMiddleware::checkRateLimit($_SERVER['REMOTE_ADDR'], 5, 60);

// Process login...
```

**Configuration:**
```bash
# In .env
RATE_LIMIT_ATTEMPTS=5      # Max attempts
RATE_LIMIT_WINDOW=60       # Time window in seconds
```

---

### 8. Database Security

**Updates Made:**
- ✅ Changed docker-compose to use environment variables
- ✅ Removed exposed MySQL port from production
- ✅ Added password requirement to Redis
- ✅ Added strict SQL mode

**Production Setup:**
```bash
# Create strong database password
DB_PASSWORD=$(openssl rand -base64 32)

# Create minimal-privilege database user:
CREATE USER 'aurora_prod'@'app.internal' IDENTIFIED BY 'DB_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE ON aurora_prod.* TO 'aurora_prod'@'app.internal';
FLUSH PRIVILEGES;

# DO NOT grant FILE privileges

# Update docker-compose.yml:
DB_USERNAME=aurora_prod
DB_PASSWORD=<generated-password>
```

**Important:**
- ✅ Never expose database port to internet
- ✅ Use internal networks only
- ✅ Regular backups with encryption
- ✅ Test restore procedures

---

### 9. Debug Mode & Error Handling

**❌ PROBLEM:** APP_DEBUG can be true in production

**✅ SOLUTION:**

Updated configs:
- ✅ `config/app.php` defaults debug=false
- ✅ `docker-compose.yml` uses `APP_DEBUG=${APP_DEBUG:-false}`
- ✅ Production never shows stack traces to users

**Production Configuration:**
```bash
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
```

**Monitoring Setup:**
```bash
# Enable Sentry for error tracking
SENTRY_DSN=https://key@sentry.io/project-id

# Or use New Relic
NEW_RELIC_LICENSE_KEY=your-license-key
```

---

### 10. Security Headers

**✅ SOLUTION:**

Updated nginx.conf with comprehensive security headers:
```
Strict-Transport-Security: max-age=31536000
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'
```

These are automatically sent by nginx in production.

---

### 11. Authorization Checks

**✅ SOLUTION:**

Created `includes/security/AuthMiddleware.php`:
- ✅ `requireAdmin()` - Admin-only endpoints
- ✅ `requireCustomer()` - Customer-only endpoints
- ✅ `require($permission)` - Permission-based access
- ✅ `checkRateLimit()` - Rate limiting

**Usage:**
```php
use GlamByMariga\Security\AuthMiddleware;

// Require admin authentication
AuthMiddleware::requireAdmin();

// Check specific permission
AuthMiddleware::require('admin_only');

// Apply rate limiting
AuthMiddleware::checkRateLimit($_SERVER['REMOTE_ADDR']);
```

---

## Deployment Checklist

Before going live:

### Security
- [ ] Regenerate ALL M-Pesa credentials
- [ ] Rewrite git history to remove exposed secrets
- [ ] Generate SSL certificate (Let's Encrypt or paid)
- [ ] Update database passwords
- [ ] Generate JWT_SECRET with `openssl rand -base64 32`
- [ ] Generate APP_KEY properly
- [ ] Update M-Pesa IP whitelist
- [ ] Configure Sentry or error tracking
- [ ] Test HTTPS on all endpoints

### Database
- [ ] Run all migrations
- [ ] Create database backups
- [ ] Test backup restoration
- [ ] Disable external port access
- [ ] Create minimal-privilege user
- [ ] Enable binary logging for replication

### Infrastructure
- [ ] Set up firewall rules
- [ ] Block direct database/Redis access
- [ ] Configure CDN for static assets
- [ ] Set up monitoring and alerting
- [ ] Configure log aggregation
- [ ] Set up DDoS protection

### Testing
- [ ] Load test with realistic traffic
- [ ] Test payment flows end-to-end
- [ ] Security scanning with OWASP ZAP
- [ ] Penetration test (hire professional)
- [ ] Test all critical paths
- [ ] Test error handling and fallbacks

### Monitoring
- [ ] Set up uptime monitoring
- [ ] Configure alert thresholds
- [ ] Set up status page
- [ ] Create incident response plan
- [ ] Train support team

---

## Ongoing Security Maintenance

### Weekly
- [ ] Review error logs for anomalies
- [ ] Check for failed login attempts
- [ ] Monitor database size

### Monthly
- [ ] Update dependencies (`composer update`)
- [ ] Review access logs
- [ ] Test backup restoration
- [ ] Security patch review

### Quarterly
- [ ] Rotate credentials
- [ ] Penetration test
- [ ] Security audit
- [ ] Disaster recovery drill

### Annually
- [ ] Full security assessment
- [ ] Compliance audit
- [ ] Update security policies
- [ ] Conduct security training

---

## Incident Response

If you suspect a security breach:

1. **Immediately:**
   - Disable affected accounts
   - Kill active sessions
   - Enable audit logging
   - Notify stakeholders

2. **Within 1 hour:**
   - Isolate affected system
   - Preserve evidence/logs
   - Start incident timeline
   - Notify customers if data exposed

3. **Within 24 hours:**
   - Root cause analysis
   - Patch/fix vulnerability
   - Review access logs
   - Security scan

4. **Communication:**
   - Document everything
   - Notify regulators if required
   - Update customers
   - Public postmortem

---

## Additional Resources

- [OWASP Top 10 2021](https://owasp.org/www-project-top-ten/)
- [PHP Security Guidelines](https://www.php.net/manual/en/security.php)
- [Mozilla Web Security](https://infosec.mozilla.org/guidelines/web_security)
- [Let's Encrypt](https://letsencrypt.org/)
- [Safaricom M-Pesa API Docs](https://developer.safaricom.co.ke/)

---

**Last Updated:** 2026-08-02
**Aurora Version:** 1.0.0
**Status:** Security hardening complete ✅
