# 02 - Operational Procedures

## Overview
This document defines standard operating procedures (SOPs) for deploying, monitoring, maintaining, and troubleshooting Nexus in production. It covers runbooks for common incidents, backup/recovery procedures, and day-to-day operational tasks.

---

## 1. Deployment Procedures

### 1.1 Pre-Deployment Checklist

**Environment Verification**:
- [ ] All tests passing (unit, integration, E2E)
- [ ] Code reviewed and approved
- [ ] Database migrations reviewed
- [ ] Dependencies updated and compatible
- [ ] Environment variables configured
- [ ] Secrets rotated if needed
- [ ] Performance baseline established

**Staging Validation**:
- [ ] Deploy to staging environment
- [ ] Run smoke tests (core workflows)
- [ ] Test all integrations (Gemini, WAHA, etc.)
- [ ] Monitor for 1 hour (latency, errors)
- [ ] Load test (simulate 10x normal traffic)
- [ ] Security scan (OWASP Top 10)

---

### 1.2 Production Deployment

**Step 1: Backup**
```bash
# Backup database before deployment
mysqldump -u root -p nexus_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Archive current code
git archive HEAD --format=tar.gz > release_$(date +%Y%m%d_%H%M%S).tar.gz
```

**Step 2: Gradual Rollout**
- Deploy to 10% of servers first (canary)
- Monitor error rate for 10 minutes
- If error rate < 0.5%, proceed
- Deploy to 50% of servers
- Monitor for 20 minutes
- Deploy to 100% of servers

**Step 3: Health Check**
```bash
# Verify API endpoints responding
curl https://api.nexus.com/health

# Check database connectivity
php artisan tinker
>>> DB::connection()->getPdo();

# Verify integrations
AiModelsHub::healthCheck();
```

**Step 4: Smoke Tests**
- Send test message via WAHA
- Generate test response (Gemini)
- Query semantic search (Pinecone)
- Check memory extraction

**Step 5: Monitoring**
- Alert on any error spike
- Track latency (p95 < 2s)
- Monitor resource utilization

---

### 1.3 Rollback Procedure

**Conditions for Rollback**:
- Error rate > 5% for 5 consecutive minutes
- P95 latency > 10 seconds
- Data corruption detected
- Critical bug causing data loss

**Rollback Steps**:
```bash
# Stop traffic to new version
# Keep routing to previous version

# Verify previous version is stable
curl https://api.nexus.com/health

# If database schema changed:
# 1. Review migration in git
# 2. Run down migration if reversible
# 3. If not reversible, use backup restore

# Restore from backup if needed
mysql nexus_db < backup_20250516_143022.sql

# Clear caches
redis-cli FLUSHALL

# Notify team
# Document incident
```

---

## 2. Monitoring & Alerting

### 2.1 Key Metrics Dashboard

**Performance Metrics**:
- API Response Time (p50, p95, p99)
- Request Rate (req/sec)
- Error Rate (errors/sec)
- Cache Hit Rate (%)

**Resource Metrics**:
- CPU Usage (%)
- Memory Usage (%)
- Disk Usage (%)
- Network I/O

**Business Metrics**:
- Messages Processed (per hour)
- Memory Extractions (per hour)
- AI Model Calls (per hour)
- Cost (USD per hour)

### 2.2 Alert Rules

**Severity: Critical**
- Error rate > 5% (page oncall)
- P95 latency > 10 seconds (page oncall)
- Database unreachable (page oncall)
- Integration provider down (page oncall)

**Severity: Warning**
- Error rate > 2% (Slack alert)
- P95 latency > 5 seconds (Slack alert)
- Memory usage > 85% (Slack alert)
- Disk usage > 90% (Slack alert)

**Severity: Info**
- Deployment completed
- Scheduled maintenance window
- Certificate renewal

---

### 2.3 Observability Setup

**Logging**:
- Centralized logging to ELK stack
- All requests logged with trace-id
- Structured JSON format
- Retention: 30 days (cold storage: 1 year)

**Metrics**:
- Prometheus scrape interval: 30 seconds
- Retention: 15 days (aggregated: 1 year)
- Custom metrics from each hub

**Tracing**:
- OpenTelemetry instrumentation
- Trace sampling: 10% of requests
- End-to-end traces from API → storage

---

## 3. Incident Response

### 3.1 Incident Classification

**P1 (Critical)**: Complete outage or data loss
- Response time: 5 minutes
- Communication: Page oncall immediately
- Updates: Every 15 minutes
- Escalation: Engineering lead + CTO

**P2 (High)**: Partial outage or major degradation
- Response time: 15 minutes
- Communication: Alert team
- Updates: Every 30 minutes
- Escalation: Engineering lead

**P3 (Medium)**: Minor issues affecting subset of users
- Response time: 1 hour
- Communication: Log ticket
- Updates: As appropriate
- Escalation: Ticket owner

---

### 3.2 Incident Response Workflow

**Step 1: Detect**
- Automated alert fires
- OR team member reports issue
- Create incident ticket

**Step 2: Assess**
- Severity level (P1/P2/P3)
- Scope (how many contacts affected)
- Cause (preliminary)

**Step 3: Respond**
- Page appropriate oncall
- Assemble incident team
- Establish war room (Slack channel)

**Step 4: Mitigate**
- Stabilize system (if unstable)
- Redirect traffic if needed
- Increase monitoring

**Step 5: Remediate**
- Identify root cause
- Apply fix
- Verify fix works

**Step 6: Communicate**
- Update status page
- Notify affected contacts
- Provide ETA for resolution

**Step 7: Close**
- Confirm all systems normal
- Document incident (postmortem)
- Schedule follow-up actions

---

### 3.3 Common Incident Runbooks

**Incident: High Error Rate (> 5%)**

Diagnosis:
```bash
# Check error logs
tail -f logs/laravel.log | grep -i error

# Check integration health
php artisan tinker
>>> AiModelsHub::healthCheck();
>>> MemoryHub::healthCheck();

# Check database
mysql -e "SHOW PROCESSLIST;"
mysql -e "SHOW ENGINE INNODB STATUS\G"
```

Actions:
- If Gemini rate-limited: Route to OpenAI
- If database slow: Identify slow query, kill if blocking
- If memory leak: Restart affected service
- If cache issue: Flush Redis

---

**Incident: High Latency (P95 > 5s)**

Diagnosis:
```bash
# Identify slow endpoint
SELECT endpoint, COUNT(*), AVG(duration_ms) 
FROM request_logs 
WHERE timestamp > NOW() - INTERVAL 15 MINUTE 
GROUP BY endpoint 
ORDER BY AVG(duration_ms) DESC;

# Check resource usage
top (CPU), free (memory), df (disk)

# Database query analysis
EXPLAIN SELECT ... (check query plans)
```

Actions:
- If CPU high: Scale up servers, or identify runaway process
- If memory high: Check for memory leak, restart service
- If database slow: Add index, or optimize query
- If network slow: Check provider health

---

**Incident: Message Delivery Failure**

Diagnosis:
```bash
# Check WAHA connection
curl -X GET https://waha.example.com/health

# Check recent message queue
SELECT * FROM messages 
WHERE status = 'failed' 
ORDER BY created_at DESC 
LIMIT 10;

# Check contact profile
SELECT * FROM contacts WHERE id = ?;
```

Actions:
- If WAHA down: Use fallback (email/SMS)
- If contact number invalid: Alert Hédra
- If rate-limited: Queue and retry later

---

## 4. Backup & Recovery

### 4.1 Backup Strategy

**Database Backups**:
- Frequency: Hourly (differential), daily (full)
- Retention: 7 days (daily), 30 days (weekly), 1 year (monthly)
- Location: S3-compatible storage (encrypted)
- Verification: Test restore monthly

**Configuration Backups**:
- Frequency: On every change + daily
- Items: Environment variables, SSL certs, integrations
- Location: S3 + secure backup service

**Code Backups**:
- Frequency: On every deployment
- Items: All production code + dependencies
- Location: Git (primary), S3 (archive)

### 4.2 Recovery Procedures

**Database Recovery (Point-in-Time)**:
```bash
# Identify time to recover to
# t0 = problem time, recover to t0 - 1 hour

# Restore from latest full backup before t0
mysql nexus_db < full_backup_20250515_000000.sql

# Restore transaction logs from t0-1h to t0
mysqlbinlog binlog.000123 --start-datetime='2025-05-15 14:00:00' \
            --stop-datetime='2025-05-15 15:00:00' | mysql nexus_db

# Verify data integrity
SELECT COUNT(*) FROM contacts;
SELECT COUNT(*) FROM memory_fragments;
```

**Configuration Recovery**:
```bash
# Download backup from S3
aws s3 cp s3://nexus-backups/config/production_env.enc .

# Decrypt
openssl enc -d -aes-256-cbc -in production_env.enc -out .env.prod

# Redeploy with recovered config
```

---

## 5. Scaling & Capacity Planning

### 5.1 Scaling Indicators

**Scale Up When**:
- CPU usage > 70% consistently
- Memory usage > 80% consistently
- Request queue depth growing
- P95 latency trending upward

**Scale Down When**:
- CPU usage < 30% for 1 hour
- Memory usage < 50% for 1 hour
- Request queue empty
- Peak load passed

### 5.2 Capacity Planning

**Current Infrastructure**:
- API servers: 3 instances (m5.large)
- Database: 1 master + 2 read replicas (db.r5.xlarge)
- Redis: 1 cluster (cache.r5.large)
- Storage: S3 with 1TB budget

**Growth Projections**:
- 10x contacts: Add 2 more API servers
- 100x contacts: Shard database by contact_id
- 1000x contacts: Consider multi-region

---

## 6. Security Maintenance

### 6.1 Regular Security Tasks

**Daily**:
- Review access logs for anomalies
- Check for failed authentication attempts

**Weekly**:
- Review audit logs (access to sensitive data)
- Update security patches
- Test backup restoration

**Monthly**:
- Rotate secrets/API keys
- Security audit of code changes
- Penetration testing in staging

**Quarterly**:
- Full security audit
- Update SSL/TLS certificates
- Review access controls

### 6.2 Incident Post-Mortems

**Template**:
- What happened?
- Why did it happen? (root cause)
- How did we detect it?
- How did we fix it?
- What will we do to prevent it? (action items)
- Timeline of events

---

## 7. Maintenance Windows

### 7.1 Scheduled Maintenance

**Database Maintenance**:
- Monthly: Optimize tables, rebuild indexes
- Quarterly: Upgrade MySQL minor version

**Cache Maintenance**:
- Weekly: Clear expired entries
- Monthly: Rebuild indices

**Dependencies Update**:
- Monthly: Update non-critical packages
- Quarterly: Major version updates (test heavily)

### 7.2 Communication

**Before Maintenance**:
- Announce 1 week in advance
- Specify duration (e.g., 2-4 AM UTC)
- Describe impact

**During Maintenance**:
- Update status page
- Respond to support queries
- Monitor carefully

**After Maintenance**:
- Confirm all systems normal
- Send completion notification
- Document any issues

---

## Summary Checklist

- [ ] Deployment procedures documented and tested
- [ ] Monitoring dashboard configured
- [ ] Alert thresholds set appropriately
- [ ] Incident response team trained
- [ ] Runbooks written and available
- [ ] Backup restoration tested
- [ ] Security maintenance schedule set
- [ ] On-call rotation established

---

**Document Status**: COMPLETE - Operational procedures defined  
**Last Updated**: 2025-05-16  
**Next Document**: 03-TROUBLESHOOTING_GUIDE.md
