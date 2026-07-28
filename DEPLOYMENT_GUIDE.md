# DEPLOYMENT_GUIDE.md

**Aurora Platform - Deployment & Operations Guide**

Version: 1.0.0  
Status: Production Ready  
Last Updated: 2026-07-28

---

## TABLE OF CONTENTS

1. Deployment Overview
2. Prerequisites
3. Development Environment Setup
4. Staging Deployment
5. Production Deployment
6. Post-Deployment Verification
7. Rollback Procedures
8. Monitoring & Alerts
9. Troubleshooting

---

## 1. DEPLOYMENT OVERVIEW

### Deployment Channels

```
Development Branch
    ↓ (manual testing)
Staging Server (pre-release testing)
    ↓ (approval required)
Production Server (live system)
```

### Deployment Process

| Stage | Environment | Trigger | Duration |
|-------|-------------|---------|----------|
| **Build** | CI/CD Server | Git push to main | 5 min |
| **Test** | CI/CD Server | Build success | 10 min |
| **Staging** | Staging Server | Manual approval | 2 min |
| **Production** | Production Server | Manual approval | 5 min |
| **Verification** | Production | Post-deploy check | 5 min |

**Total Time**: ~27 minutes from code push to production

---

## 2. PREREQUISITES

### System Requirements

**Development Machine**:
- Docker & Docker Compose 20.10+
- Git 2.30+
- PHP 8.3 (optional, for local PHP testing)
- Node.js 18+ (optional, for frontend building)
- 4GB RAM minimum, 20GB disk space

**Staging/Production Server**:
- Ubuntu 20.04 LTS or compatible
- Docker & Docker Compose 20.10+
- 4GB RAM minimum, 50GB disk space
- Static IP address
- Domain name configured
- SSL/TLS certificate (let's encrypt recommended)

### Access & Credentials

**Required Credentials**:
- [ ] GitHub repository access
- [ ] DockerHub account (for custom images)
- [ ] Deployment server SSH access
- [ ] Database admin credentials
- [ ] M-Pesa sandbox credentials (testing)
- [ ] M-Pesa production credentials (production)

**Store Securely** in:
- GitHub Secrets (for CI/CD)
- Environment variables on deployment servers
- Password manager (1Password, Bitwarden, etc.)

---

## 3. DEVELOPMENT ENVIRONMENT SETUP

### Initial Setup

```bash
# 1. Clone repository
git clone https://github.com/yourusername/aurora-platform.git
cd aurora-platform

# 2. Create environment file
cp .env.example .env

# 3. Edit .env for local development
# Set:
# - APP_ENV=development
# - MPESA_ENVIRONMENT=sandbox
# - MPESA_BUSINESS_SHORT_CODE=123456
# - MPESA_CONSUMER_KEY=your_test_key
# - MPESA_CONSUMER_SECRET=your_test_secret
# - MPESA_PASSKEY=your_test_passkey
# - DB_PASSWORD=localpass
# - REDIS_PASSWORD=localpass

# 4. Build and start containers
docker-compose up -d

# 5. Install PHP dependencies
docker-compose exec php composer install

# 6. Install JavaScript dependencies
docker-compose exec php npm install

# 7. Run database migrations
docker-compose exec php php migrate.php up

# 8. Seed initial data (roles, permissions, test user)
docker-compose exec php php seed.php

# 9. Access application
# - Browser: http://localhost:8080
# - API: http://localhost:8080/api
# - Login: owner@glambymariga.local / password123
```

### Verify Development Setup

```bash
# Check container health
docker-compose ps

# Test database connection
docker-compose exec php php -r "require 'config/database.php'; echo 'DB OK';"

# Test API connectivity
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"owner@glambymariga.local","password":"password123"}'

# View logs
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f mysql
```

---

## 4. STAGING DEPLOYMENT

### Staging Environment Setup

**Server**: 192.168.x.x (VPS or staging server)

**Setup Steps**:

```bash
# 1. SSH into staging server
ssh ubuntu@staging.glambymariga.local

# 2. Clone repository
cd /app
git clone https://github.com/yourusername/aurora-platform.git
cd aurora-platform

# 3. Create environment file for staging
cat > .env << 'EOF'
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.glambymariga.local

DB_HOST=mysql
DB_DATABASE=aurora
DB_USER=aurora
DB_PASSWORD=$RANDOM_PASSWORD

REDIS_HOST=redis
REDIS_PASSWORD=$RANDOM_PASSWORD

MPESA_ENVIRONMENT=sandbox
MPESA_BUSINESS_SHORT_CODE=123456
MPESA_CONSUMER_KEY=your_sandbox_key
MPESA_CONSUMER_SECRET=your_sandbox_secret
MPESA_PASSKEY=your_sandbox_passkey

TWILIO_ACCOUNT_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
TWILIO_PHONE_NUMBER=+1234567890

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASSWORD=your_app_password
EOF

# 4. Build containers
docker-compose -f docker-compose.yml build

# 5. Start services
docker-compose up -d

# 6. Run migrations
docker-compose exec php php migrate.php up

# 7. Seed data
docker-compose exec php php seed.php

# 8. Verify
docker-compose ps
curl https://staging.glambymariga.local/api/login
```

### Staging Access

- **URL**: https://staging.glambymariga.local
- **API**: https://staging.glambymariga.local/api
- **Test User**: owner@glambymariga.local

---

## 5. PRODUCTION DEPLOYMENT

### Production Environment Setup

**Server**: 203.x.x.x (Production server)

**Pre-Deployment Checklist**:
- [ ] Database backup created
- [ ] All tests passing (CI/CD green)
- [ ] Security audit completed
- [ ] Performance baseline established
- [ ] Monitoring configured
- [ ] Support team notified
- [ ] Rollback plan documented
- [ ] 24/7 monitoring active

### Deployment Steps

**Automated via GitHub Actions** (`.github/workflows/deploy.yml`):

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    environment: production
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Build Docker image
        run: |
          docker build -t aurora:${{ github.sha }} .
          docker tag aurora:${{ github.sha }} aurora:latest
      
      - name: Run tests
        run: docker run aurora:${{ github.sha }} php vendor/bin/phpunit
      
      - name: Push to registry
        run: |
          echo ${{ secrets.DOCKER_PASSWORD }} | docker login -u ${{ secrets.DOCKER_USERNAME }} --password-stdin
          docker push aurora:${{ github.sha }}
          docker push aurora:latest
      
      - name: Deploy to production
        run: |
          ssh-keyscan -H ${{ secrets.PROD_SERVER }} >> ~/.ssh/known_hosts
          scp .github/scripts/deploy.sh ubuntu@${{ secrets.PROD_SERVER }}:/tmp/
          ssh ubuntu@${{ secrets.PROD_SERVER }} "/bin/bash /tmp/deploy.sh ${{ github.sha }}"
```

**Manual Production Deployment** (if needed):

```bash
# 1. SSH into production server
ssh ubuntu@prod.glambymariga.local

# 2. Navigate to application directory
cd /app/aurora-platform

# 3. Backup current version
sudo cp -r . /backups/aurora-$(date +%Y%m%d-%H%M%S)

# 4. Pull latest code
git pull origin main

# 5. Backup database
docker-compose exec mysql mysqldump -u aurora -p aurora > \
  /backups/db-$(date +%Y%m%d-%H%M%S).sql

# 6. Build new image
docker-compose build --no-cache

# 7. Run migrations (with transaction for rollback)
docker-compose exec php php migrate.php up

# 8. Stop old containers
docker-compose down

# 9. Start new containers
docker-compose up -d

# 10. Verify deployment
docker-compose ps
curl -s https://prod.glambymariga.local/api/login | jq .

# 11. Check logs for errors
docker-compose logs --tail=50 php
docker-compose logs --tail=50 nginx
```

### Production Environment Configuration

```bash
# .env.production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://glambymariga.local

# Database
DB_HOST=db-prod-1.c.internal
DB_DATABASE=aurora
DB_USER=aurora
DB_PASSWORD=$SECURE_PASSWORD

# Cache
REDIS_HOST=redis-prod-1.c.internal
REDIS_PASSWORD=$SECURE_PASSWORD

# M-Pesa (Production)
MPESA_ENVIRONMENT=production
MPESA_BUSINESS_SHORT_CODE=$ACTUAL_SHORT_CODE
MPESA_CONSUMER_KEY=$ACTUAL_CONSUMER_KEY
MPESA_CONSUMER_SECRET=$ACTUAL_SECRET
MPESA_PASSKEY=$ACTUAL_PASSKEY

# Monitoring
SENTRY_DSN=$SENTRY_DSN
DATADOG_API_KEY=$DATADOG_KEY
```

---

## 6. POST-DEPLOYMENT VERIFICATION

### Health Checks

```bash
#!/bin/bash
# health_check.sh

echo "Checking deployment health..."

# 1. Container status
echo "[1/5] Checking containers..."
docker-compose ps | grep -q "Up" && echo "✓ Containers running" || echo "✗ Containers down"

# 2. Database connectivity
echo "[2/5] Checking database..."
docker-compose exec mysql mysql -u aurora -p aurora -e "SELECT 1;" && echo "✓ Database OK" || echo "✗ Database connection failed"

# 3. API health
echo "[3/5] Checking API..."
curl -s http://localhost:8080/api/health | jq . && echo "✓ API OK" || echo "✗ API not responding"

# 4. SSL/TLS certificate
echo "[4/5] Checking SSL..."
openssl s_client -connect prod.glambymariga.local:443 -servername prod.glambymariga.local | grep "Verify return code" && echo "✓ SSL OK" || echo "✗ SSL error"

# 5. Response time
echo "[5/5] Checking response time..."
START=$(date +%s%N)
curl -s http://localhost:8080/api/login > /dev/null
END=$(date +%s%N)
DURATION=$((($END - $START) / 1000000))
echo "Response time: ${DURATION}ms"
[ $DURATION -lt 2000 ] && echo "✓ Performance OK" || echo "⚠ Slow response"

echo "✓ Deployment verification complete"
```

### Automated Verification

```bash
# Run post-deploy verification
chmod +x health_check.sh
./health_check.sh

# Monitor logs for errors
docker-compose logs --tail=100 --follow php &
docker-compose logs --tail=100 --follow nginx &
docker-compose logs --tail=100 --follow mysql &

# Wait 5 minutes and check for errors
sleep 300
docker-compose logs php | grep -i error || echo "✓ No PHP errors"
docker-compose logs nginx | grep -i error || echo "✓ No Nginx errors"
```

---

## 7. ROLLBACK PROCEDURES

### Rollback Trigger

Rollback is initiated if:
- [ ] API returns 500+ errors
- [ ] Database connectivity lost
- [ ] Critical features not working
- [ ] Performance degradation >50%
- [ ] Security vulnerability discovered
- [ ] Data corruption detected

### Automated Rollback

```bash
#!/bin/bash
# rollback.sh

BACKUP_DIR="/backups"
CURRENT_DIR="/app/aurora-platform"

echo "Rolling back deployment..."

# 1. Stop current containers
docker-compose down

# 2. Find latest backup
LATEST_BACKUP=$(ls -t $BACKUP_DIR/aurora-* | head -1)
echo "Restoring from: $LATEST_BACKUP"

# 3. Restore code
rm -rf $CURRENT_DIR
cp -r $LATEST_BACKUP $CURRENT_DIR
cd $CURRENT_DIR

# 4. Restore database (optional - manual confirmation)
read -p "Restore database from backup? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
  LATEST_DB=$(ls -t $BACKUP_DIR/db-*.sql | head -1)
  docker-compose exec mysql mysql -u aurora -p aurora < $LATEST_DB
fi

# 5. Start old containers
docker-compose up -d

# 6. Verify rollback
docker-compose ps
curl http://localhost:8080/api/health

echo "✓ Rollback complete"
```

### Manual Rollback

```bash
# 1. Restore from backup
sudo cp -r /backups/aurora-20260728-143022 /app/aurora-platform

# 2. Restart services
docker-compose down
docker-compose up -d

# 3. Verify services
docker-compose ps
curl http://localhost:8080/api/health

# 4. Check logs
docker-compose logs --tail=50 php
```

---

## 8. MONITORING & ALERTS

### Container Monitoring

```bash
# Real-time container stats
docker stats --no-stream

# Container logs with timestamps
docker-compose logs --timestamps --tail=100 php
docker-compose logs --timestamps --tail=100 nginx
docker-compose logs --timestamps --tail=100 mysql
```

### Application Monitoring

**Health Endpoint**:
```
GET /api/health
Response: {
  "status": "ok",
  "database": "connected",
  "redis": "connected",
  "timestamp": "2026-07-28T10:30:00Z"
}
```

**Metrics to Monitor**:
- API response time (target: <500ms)
- Error rate (target: <1%)
- Database query time (target: <100ms)
- Memory usage (alert: >80%)
- Disk usage (alert: >80%)
- HTTP request count
- Failed login attempts

### Alert Thresholds

| Alert | Threshold | Action |
|-------|-----------|--------|
| **High CPU** | >80% | Check running processes, consider scaling |
| **High Memory** | >90% | Restart containers, review memory leaks |
| **High Disk** | >90% | Archive logs, delete old backups |
| **API Errors** | >5% | Check application logs, potential service degradation |
| **DB Connection** | Any failure | Immediate escalation, attempt restart |
| **SSL Certificate** | Expiring in 30 days | Renew certificate |

---

## 9. TROUBLESHOOTING

### Common Issues & Solutions

#### Issue 1: "Connection refused" on API

**Symptoms**: `curl: (7) Failed to connect to localhost:8080`

**Diagnosis**:
```bash
docker-compose ps # Check if containers running
docker-compose logs nginx # Check Nginx logs
docker-compose logs php # Check PHP logs
```

**Solutions**:
```bash
# Restart containers
docker-compose restart

# Rebuild if port conflict
docker-compose down
docker-compose up -d

# Check port binding
netstat -tlnp | grep 8080
```

---

#### Issue 2: "Database connection failed"

**Symptoms**: API returns `503 Service Unavailable`

**Diagnosis**:
```bash
docker-compose logs mysql
docker-compose exec mysql mysql -u aurora -p aurora -e "SELECT 1;"
```

**Solutions**:
```bash
# Check MySQL is running
docker-compose ps mysql

# Verify credentials in .env
grep DB_ .env

# Restart MySQL
docker-compose restart mysql

# Check disk space
df -h /

# Restore from backup if corrupted
# (see rollback procedures)
```

---

#### Issue 3: "Out of memory"

**Symptoms**: Containers killed, OOMkilled errors

**Diagnosis**:
```bash
docker stats # Check memory usage
docker-compose logs # Look for OOMkilled
```

**Solutions**:
```bash
# Increase container memory limits in docker-compose.yml
# services:
#   php:
#     memswap_limit: 2g
#     mem_limit: 1g

docker-compose up -d

# Check for memory leaks in code
docker-compose exec php php memory_usage_report.php
```

---

#### Issue 4: "SSL certificate expired"

**Symptoms**: HTTPS shows "certificate expired"

**Diagnosis**:
```bash
openssl s_client -connect prod.glambymariga.local:443 -servername prod.glambymariga.local | grep -A2 "Validity"
```

**Solutions**:
```bash
# Renew with Let's Encrypt
sudo certbot renew --dry-run # Test first
sudo certbot renew # Actually renew

# If using Docker-based cert management
docker-compose restart nginx
```

---

#### Issue 5: "M-Pesa integration not working"

**Symptoms**: Payment requests fail with M-Pesa errors

**Diagnosis**:
```bash
# Check M-Pesa credentials
grep MPESA_ .env

# Test M-Pesa connectivity
docker-compose exec php php test_mpesa.php

# Check Daraja API status
curl -s https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials -u "$MPESA_CONSUMER_KEY:$MPESA_CONSUMER_SECRET"
```

**Solutions**:
```bash
# Verify credentials are correct for environment
# (sandbox vs production)

# Check firewall allows outbound HTTPS
docker-compose exec php curl -v https://sandbox.safaricom.co.ke/

# Ensure callback URL is reachable
# Verify Daraja API status page
```

---

## DEPLOYMENT CHECKLIST

**Pre-Deployment**:
- [ ] Code review completed
- [ ] All tests passing (100%)
- [ ] Security scan passed
- [ ] Performance baseline OK
- [ ] Database backup created
- [ ] Team notified
- [ ] Rollback plan ready
- [ ] Monitoring active

**During Deployment**:
- [ ] Pull latest code
- [ ] Backup database
- [ ] Run migrations
- [ ] Build containers
- [ ] Stop old containers
- [ ] Start new containers
- [ ] Run health checks

**Post-Deployment**:
- [ ] API responding
- [ ] Database connected
- [ ] Logs clean (no errors)
- [ ] Performance good
- [ ] Payment processing working
- [ ] Notifications sending
- [ ] Audit logs recording
- [ ] Team verified

**Rollback Decision**:
- [ ] Critical feature broken?
- [ ] Data corruption detected?
- [ ] Security issue found?
- [ ] Performance degraded >50%?

If ANY yes → **ROLLBACK IMMEDIATELY**

---

**END OF DEPLOYMENT_GUIDE.md**

**Last Updated**: 2026-07-28  
**Next Update**: After first production deployment  
**Owner**: DevOps Team
