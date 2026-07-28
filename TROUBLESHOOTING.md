# TROUBLESHOOTING.md

**Aurora Platform - Troubleshooting & Support Guide**

Version: 1.0.0  
Status: Active  
Last Updated: 2026-07-28

---

## QUICK DIAGNOSIS FLOWCHART

```
System Down?
├─ No → Check specific issue below
└─ Yes → 
   ├─ Containers running? (docker-compose ps)
   │  └─ No → Restart: docker-compose up -d
   ├─ Database responding? (curl http://localhost:8080/api/health)
   │  └─ No → Check MySQL: docker-compose logs mysql
   ├─ API responding? (curl http://localhost:8080/api/login)
   │  └─ No → Check PHP: docker-compose logs php
   └─ If still down → Check DEPLOYMENT_GUIDE.md Rollback section
```

---

## COMMON ISSUES & SOLUTIONS

### Issue 1: "Unable to connect to API"

**Error Message**: `Connection refused` or `Failed to connect`

**Diagnosis**:
```bash
# Check containers
docker-compose ps

# Check ports
netstat -tlnp | grep 8080

# Check logs
docker-compose logs nginx
docker-compose logs php
```

**Solutions**:
1. Restart containers: `docker-compose restart`
2. Rebuild if stuck: `docker-compose down && docker-compose up -d`
3. Check port conflict: Kill process on 8080 or change port in docker-compose.yml
4. Check firewall: Ensure port 8080 open

---

### Issue 2: "Appointment booking fails with validation error"

**Scenario**: `POST /api/appointments` returns 422 Unprocessable Entity

**Common Validation Errors**:
- `Minimum 1-hour lead time required` → startTime must be 1+ hour in future
- `Staff member not available` → Check staff_members status and schedule
- `Service duration invalid` → Check services table for duration_minutes
- `Customer does not exist` → Verify customer ID in customers table

**Diagnosis**:
```bash
# Check error response details
curl -X POST http://localhost:8080/api/appointments \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"customerId":999,...}' | jq .error.details

# Verify data in database
docker-compose exec mysql mysql -u aurora -p aurora \
  -e "SELECT * FROM customers WHERE id=999;"
```

---

### Issue 3: "M-Pesa payment fails"

**Error**: `M-Pesa gateway timeout` or `Verification failed`

**Diagnosis**:
```bash
# Check M-Pesa credentials in .env
grep MPESA_ .env

# Test Daraja API connectivity
docker-compose exec php curl -v \
  https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials \
  -u "$MPESA_CONSUMER_KEY:$MPESA_CONSUMER_SECRET"

# Check logs for M-Pesa errors
docker-compose logs php | grep -i mpesa
```

**Solutions**:
1. Verify credentials: Check MPESA_* in .env match Daraja portal
2. Check environment: Ensure MPESA_ENVIRONMENT is `sandbox` for testing
3. Verify callback URL: Ensure public IP and port configured in Daraja
4. Check firewall: Ensure outbound HTTPS to Daraja API allowed
5. Test in sandbox: Use +254700000000 test number

---

### Issue 4: "Database connection failed"

**Error**: `SQLSTATE[HY000]: General error: 2002 Can't connect to MySQL server`

**Diagnosis**:
```bash
# Check MySQL container
docker-compose ps mysql

# Check database logs
docker-compose logs mysql

# Test connection from PHP container
docker-compose exec php mysql -h mysql -u aurora -p -e "SELECT 1;"
```

**Solutions**:
1. Restart MySQL: `docker-compose restart mysql`
2. Check credentials: Verify DB_USER, DB_PASSWORD in .env
3. Check hostname: Ensure DB_HOST=mysql (not localhost) in docker-compose network
4. Check disk space: `df -h /` may need cleanup if disk full
5. Reset MySQL: `docker-compose down && docker volume rm aurora-mysql && docker-compose up -d`

---

### Issue 5: "Out of memory errors"

**Error**: `OOMkilled` or `Unable to allocate memory`

**Diagnosis**:
```bash
# Check memory usage
docker stats

# Check container limits
docker-compose config | grep -A2 "mem_limit"

# Check PHP memory usage
docker-compose exec php php -r "echo ini_get('memory_limit');"
```

**Solutions**:
1. Increase container memory in docker-compose.yml
2. Check for memory leaks in PHP code
3. Restart containers: `docker-compose restart`
4. Reduce concurrent connections in my.cnf

---

### Issue 6: "Slow queries / High database load"

**Error**: "Response time > 2 seconds" or "Database CPU at 80%+"

**Diagnosis**:
```bash
# Check slow queries
docker-compose exec mysql mysql -u aurora -p aurora -e \
  "SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;"

# Check running queries
docker-compose exec mysql mysql -u aurora -p aurora -e "SHOW PROCESSLIST;"

# Check table sizes
docker-compose exec mysql mysql -u aurora -p aurora -e \
  "SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb FROM information_schema.tables WHERE table_schema = 'aurora' ORDER BY size_mb DESC;"
```

**Solutions**:
1. Add missing indexes (see DATABASE_SCHEMA.md)
2. Optimize slow queries using EXPLAIN
3. Archive old data from audit_logs, stock_movements
4. Increase MySQL resources
5. Add Redis caching for frequently accessed data

---

### Issue 7: "File permission denied when writing logs"

**Error**: `Permission denied` writing to logs directory

**Diagnosis**:
```bash
# Check log directory permissions
ls -la logs/

# Check Docker user
docker-compose exec php whoami
```

**Solutions**:
1. Fix permissions: `sudo chown -R www-data:www-data /app/logs`
2. Create logs directory if missing: `mkdir -p logs && chmod 755 logs`
3. Check volume mount in docker-compose.yml

---

## ERROR LOG LOCATIONS

| Component | Log Location | View Command |
|-----------|--------------|--------------|
| **PHP** | Container stdout | `docker-compose logs php` |
| **Nginx** | Container stdout | `docker-compose logs nginx` |
| **MySQL** | `/var/log/mysql/error.log` | `docker-compose exec mysql cat /var/log/mysql/error.log` |
| **Application** | `logs/app.log` | `docker-compose exec php tail -f logs/app.log` |

---

## PERFORMANCE DIAGNOSTICS

### Check API Response Times

```bash
# Test response time
time curl -s http://localhost:8080/api/appointments | jq . > /dev/null

# Load test with Apache Bench
ab -n 100 -c 10 http://localhost:8080/api/appointments
```

### Check Database Query Performance

```bash
# Enable query logging
docker-compose exec mysql mysql -u aurora -p aurora -e \
  "SET GLOBAL general_log = 'ON';"

# View queries
docker-compose exec mysql mysql -u aurora -p aurora -e \
  "SELECT * FROM mysql.general_log ORDER BY event_time DESC LIMIT 20;"

# Disable logging
docker-compose exec mysql mysql -u aurora -p aurora -e \
  "SET GLOBAL general_log = 'OFF';"
```

---

## SUPPORT ESCALATION

### Severity Levels

| Level | Response Time | Examples |
|-------|---------------|----------|
| **P1-Critical** | 15 min | System down, data loss, security breach |
| **P2-High** | 1 hour | Feature not working, payment processing down |
| **P3-Medium** | 4 hours | Performance issue, minor feature bug |
| **P4-Low** | Next business day | UI/UX improvement, documentation |

### On-Call Escalation

1. Try basic troubleshooting (this document)
2. Check status: `./health_check.sh` (see DEPLOYMENT_GUIDE.md)
3. Page on-call engineer: [Contact info in team Slack]
4. If unresolved in 15 min: Escalate to on-call manager

---

**END OF TROUBLESHOOTING.md**

**Related Documents**:
- DEPLOYMENT_GUIDE.md - Deployment and rollback
- OPERATIONS_MANUAL.md - Daily operations
- DATABASE_SCHEMA.md - Query optimization
