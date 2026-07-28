# OPERATIONS_MANUAL.md

**Aurora Platform - Operations & Maintenance Manual**

Version: 1.0.0  
Status: Active  
Last Updated: 2026-07-28

---

## DAILY OPERATIONS

### Morning Checklist
- [ ] 8:00 AM: Check system health: `curl http://prod/api/health`
- [ ] 8:05 AM: Review overnight error logs: `docker-compose logs --since 6h php`
- [ ] 8:10 AM: Verify database backup completed successfully
- [ ] 8:15 AM: Check disk space: `df -h /`
- [ ] 8:20 AM: Verify all containers running: `docker-compose ps`

### During Business Hours
- Monitor dashboard for:
  - [ ] Revenue metrics updating
  - [ ] Payment processing working
  - [ ] No error spikes in logs
  - [ ] Response times acceptable (<2s)

### End of Day (6 PM)
- [ ] Reconcile transactions
- [ ] Archive backup file to remote storage
- [ ] Review any production issues
- [ ] Prepare for next day

### Weekly Operations

**Monday 10 AM**:
- Review past week performance metrics
- Check M-Pesa transaction reconciliation
- Verify all backups completed successfully

**Wednesday 2 PM**:
- Database maintenance check
- Run ANALYZE TABLE on large tables
- Check for orphaned records

**Friday 4 PM**:
- Weekly security log review
- Permission audit (check user access levels)
- Prepare deployment for next week if applicable

### Monthly Operations

**First Monday**:
- Full database integrity check
- Performance baseline comparison
- Capacity planning review

**Mid-Month**:
- Backup restoration test (verify recovery works)
- Security audit log review
- Payment reconciliation

**End of Month**:
- Financial report generation
- Archive old audit logs (>1 year)
- Infrastructure cost review

---

## ROUTINE MAINTENANCE

### Database Maintenance

```bash
# Weekly: Optimize tables
docker-compose exec mysql mysql -u aurora -p aurora -e \
  "OPTIMIZE TABLE users, customers, appointments, sales, products, stock;"

# Monthly: Check for errors
docker-compose exec mysql mysql -u aurora -p aurora -e \
  "CHECK TABLE users, customers, appointments, sales, products, stock;"

# Quarterly: Full backup verification
docker-compose exec mysql mysqldump -u aurora -p aurora > test_backup.sql
mysql -u aurora -p aurora < test_backup.sql
```

### Log Rotation

```bash
# Archive old logs (older than 30 days)
find /app/logs -name "*.log" -mtime +30 -exec gzip {} \;
find /app/logs -name "*.log.gz" -mtime +90 -delete
```

### Certificate Renewal

```bash
# Let's Encrypt auto-renewal (runs automatically)
certbot renew --dry-run  # Test
certbot renew            # Actually renew

# Manual renewal
docker-compose exec nginx certbot renew
```

---

## CAPACITY PLANNING

### Storage Monitoring

| Component | Current | Limit | Action |
|-----------|---------|-------|--------|
| **Database** | 100 MB | 1 GB | Archive old data |
| **Logs** | 50 MB | 500 MB | Rotate/compress |
| **Backups** | 200 MB | 5 GB | Delete old backups |
| **Total** | 350 MB | 10 GB | Monitor |

### Memory Management

- Monitor Redis memory: `redis-cli INFO memory`
- Monitor MySQL: `mysql -e "SHOW PROCESSLIST;"`
- Monitor PHP: `docker stats`

---

## INCIDENT RESPONSE

### System Outage (P1)

1. **First 15 min**: Diagnose root cause
   - Check containers: `docker-compose ps`
   - Check logs: `docker-compose logs php mysql nginx`
   - Check connectivity: `curl http://localhost:8080/api/health`

2. **Minute 15**: Notify stakeholders
   - Post to incident Slack channel
   - Customer status page: "Investigating"

3. **Minute 30**: Attempt remediation
   - Restart containers if needed
   - Check for recent deployments
   - Restore from backup if data corruption

4. **Ongoing**: Update stakeholders every 15 minutes

### Database Corruption (P1)

1. Immediate: Take system offline
2. Restore from latest clean backup
3. Run integrity checks
4. Verify data consistency
5. Bring system online
6. Post-incident: Investigate cause

### Security Incident (P1)

1. Immediate: Isolate affected system
2. Preserve evidence (logs, data)
3. Notify security team
4. Initiate incident response plan
5. Customer communication

---

## PERFORMANCE TUNING

### Database Optimization

```sql
-- Analyze query execution
EXPLAIN SELECT * FROM appointments 
WHERE staff_id = 3 AND start_time > '2026-08-01';

-- Add indexes if needed
CREATE INDEX idx_staff_start ON appointments(staff_id, start_time);

-- Check index usage
SELECT * FROM sys.schema_unused_indexes;
```

### Caching Strategy

- Redis: Session storage (essential)
- Redis: Query results (optional for Phase 2)
- Browser cache: Static assets (30 days)

### Connection Pooling

- MySQL max_connections: 100
- PHP-FPM: 10-20 processes
- Nginx: 1000 worker connections

---

## DOCUMENTATION

### Must Maintain

- [ ] Architecture diagrams (update on changes)
- [ ] Runbooks for common procedures
- [ ] Contact list for on-call
- [ ] Access control matrix
- [ ] Configuration management

### Quarterly Review

- [ ] Test all runbooks
- [ ] Update contact information
- [ ] Review security policies
- [ ] Assess infrastructure needs

---

**END OF OPERATIONS_MANUAL.md**

**See Also**: DEPLOYMENT_GUIDE.md, TROUBLESHOOTING.md, DECISION_LOG.md
