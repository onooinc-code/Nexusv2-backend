# 03 - Troubleshooting Guide

## Overview
This document provides step-by-step troubleshooting procedures for common issues in Nexus. Each section identifies a problem, explains diagnostic steps, and provides solutions.

---

## 1. Message Processing Issues

### Problem: Messages Not Being Received

**Symptoms**:
- Contact sends message but Nexus doesn't respond
- No message in database
- WAHA logs show message received

**Diagnostic Steps**:

1. **Check WAHA Connection**
```bash
# Verify WAHA instance is running
curl -X GET https://waha.example.com/health

# Check phone number is registered
curl -X GET https://waha.example.com/sessions
```

2. **Check Webhook Delivery**
```bash
# Review webhook logs
tail -f logs/webhooks.log | grep -i error

# Verify webhook URL is correct
SELECT * FROM integrations WHERE service = 'waha';

# Test webhook manually
curl -X POST https://api.nexus.com/webhooks/whatsapp \
  -H "Content-Type: application/json" \
  -d '{"from": "+201234567890", "body": "test"}'
```

3. **Check Message Queue**
```bash
# Verify message was enqueued
SELECT * FROM messages WHERE contact_id = ? 
ORDER BY created_at DESC LIMIT 1;

# Check if job processor is running
ps aux | grep queue:work
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| WAHA instance down | Restart WAHA service or redeploy |
| Webhook signature invalid | Verify WAHA API key hasn't changed |
| Database connection error | Check MySQL credentials, restart Laravel |
| Queue worker not running | `php artisan queue:work` or restart service |
| Contact not found | Verify contact exists in database |
| Message parser error | Check message format, review parser logic |

---

### Problem: Response Not Being Sent

**Symptoms**:
- Message received but no response sent
- Response generated but not delivered
- Contact sees "Nexus is typing..." indefinitely

**Diagnostic Steps**:

1. **Check Response Generation**
```bash
# Review response logs
SELECT * FROM response_logs 
WHERE contact_id = ? 
ORDER BY created_at DESC LIMIT 5;

# Check AI model latency
SELECT provider, AVG(latency_ms) 
FROM model_calls 
WHERE created_at > NOW() - INTERVAL 1 HOUR
GROUP BY provider;
```

2. **Check Response Delivery**
```bash
# Look for delivery errors
SELECT * FROM delivery_logs 
WHERE message_id = ? 
AND status = 'failed';

# Check if message is in queue
redis-cli LLEN queue:whatsapp_outbound
```

3. **Check WAHA Send**
```bash
# Test WAHA send directly
curl -X POST https://waha.example.com/sendMessage \
  -H "Content-Type: application/json" \
  -d '{
    "chatId": "+201234567890",
    "body": "test message"
  }'
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| Gemini rate-limited | Wait for quota reset, or route to OpenAI |
| Gemini latency > 30s | Timeout fallback triggered, retry with smaller context |
| WAHA rate-limited | Queue message, retry after backoff |
| Invalid phone number | Verify contact's phone number is correct |
| Message too large | Split message or send without media |
| Delivery queue stuck | Restart queue worker, clear stuck jobs |

---

## 2. Memory & Context Issues

### Problem: Memory Not Being Extracted

**Symptoms**:
- Message processed but no beliefs/preferences extracted
- Memory extraction job fails
- Memory extraction very slow

**Diagnostic Steps**:

1. **Check Extraction Job**
```bash
# Review extraction logs
SELECT * FROM extraction_jobs 
WHERE contact_id = ? 
ORDER BY created_at DESC LIMIT 5;

# Check job status
php artisan tinker
>>> Job::where('status', 'failed')->latest()->first();
```

2. **Check Memory Table**
```bash
# Verify memory records exist
SELECT COUNT(*) FROM memory_fragments 
WHERE contact_id = ?;

# Check extraction confidence
SELECT * FROM memory_fragments 
WHERE contact_id = ? 
AND confidence < 0.7;
```

3. **Test Extraction Directly**
```php
php artisan tinker
>>> $contact = Contact::find(?);
>>> $message = "I just got promoted at work!";
>>> $service = app(MemoryExtractionService::class);
>>> $service->extract($contact, $message);
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| Extraction confidence too low | Review extraction model, lower threshold if appropriate |
| Message language not Egyptian Arabic | Translate message to Arabic, retry |
| Extraction job stuck | Restart queue worker, mark job as failed |
| Pinecone not working | Check Pinecone connection, verify API key |
| Storage quota exceeded | Archive old memories, clean up test data |

---

### Problem: Wrong Context Used in Response

**Symptoms**:
- Response doesn't match contact personality
- Wrong tone for relationship type
- Old or irrelevant information used
- Contradictory statements in response

**Diagnostic Steps**:

1. **Check Context Assembly**
```bash
# Review context that was used
SELECT * FROM context_logs 
WHERE message_id = ? 
AND created_at > NOW() - INTERVAL 1 DAY;

# Check semantic search results
SELECT * FROM memory_fragments 
WHERE contact_id = ? 
ORDER BY relevance DESC LIMIT 10;
```

2. **Check Memory Accuracy**
```bash
# Find conflicting beliefs
SELECT * FROM memory_fragments 
WHERE contact_id = ? 
AND conflict_with_id IS NOT NULL;

# Check memory confidence scores
SELECT * FROM memory_fragments 
WHERE contact_id = ? 
AND confidence < 0.8;
```

3. **Test Context Assembly**
```php
php artisan tinker
>>> $contact = Contact::find(?);
>>> $service = app(ContextAssemblyService::class);
>>> $context = $service->assemble($contact);
>>> echo $context->toDebugString();
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| Outdated memory not refreshed | Manually update memory, or wait for TTL to decay confidence |
| Conflicting memories both used | Resolve conflict in UI, mark one as superseded |
| Wrong contact matched | Verify contact disambiguation in message |
| Semantic search returning irrelevant results | Check embeddings quality, rebuild Pinecone index |
| Memory confidence decay not working | Verify decay job is running daily |

---

## 3. AI Model Issues

### Problem: Gemini Errors or Timeouts

**Symptoms**:
- "API error" responses
- Timeout errors (>30s)
- Rate limit errors (429)
- Invalid request errors (400)

**Diagnostic Steps**:

1. **Check Gemini Status**
```bash
# Check Google Cloud status
# https://status.cloud.google.com/

# Check API quota usage
gcloud compute project-info describe --project=YOUR_PROJECT
gcloud logging read "resource.type=api" --limit 20 --project=YOUR_PROJECT
```

2. **Check Request Logs**
```bash
# Review Gemini request/response
SELECT * FROM model_calls 
WHERE provider = 'gemini' 
AND created_at > NOW() - INTERVAL 1 HOUR 
ORDER BY created_at DESC;

# Check error patterns
SELECT error_code, COUNT(*) 
FROM model_calls 
WHERE provider = 'gemini' 
AND error_code IS NOT NULL 
GROUP BY error_code;
```

3. **Test Gemini Connection**
```php
php artisan tinker
>>> $service = app(GeminiService::class);
>>> $response = $service->testConnection();
>>> dd($response);
```

**Solutions**:

| Error | Fix |
|-------|-----|
| 429 (Rate Limited) | Wait for quota reset, route to OpenAI, or increase quota in Google Cloud |
| 401 (Unauthorized) | Verify API key is correct, check key hasn't been rotated |
| 400 (Bad Request) | Check prompt is valid JSON, verify model name is correct |
| 503 (Service Unavailable) | Retry with exponential backoff, use fallback provider |
| Timeout (>30s) | Reduce context size, use faster model, increase timeout |
| "Invalid token" | Regenerate API key, clear expired keys from config |

---

### Problem: Low Quality Responses

**Symptoms**:
- Responses are generic or inappropriate
- Tone doesn't match contact relationship
- Repetitive or unhelpful suggestions
- Responses contradict earlier messages

**Diagnostic Steps**:

1. **Compare Generations**
```bash
# Review multiple responses for same contact
SELECT * FROM responses 
WHERE contact_id = ? 
ORDER BY created_at DESC LIMIT 10;

# Check if same prompt used repeatedly
SELECT prompt_hash, COUNT(*) 
FROM model_calls 
GROUP BY prompt_hash 
ORDER BY COUNT(*) DESC;
```

2. **Check Prompt Assembly**
```php
php artisan tinker
>>> $contact = Contact::find(?);
>>> $message = "...";
>>> $service = app(PromptAssemblyService::class);
>>> $prompt = $service->assemble($contact, $message);
>>> echo $prompt;
```

3. **Test Different Models**
```php
php artisan tinker
>>> $service = app(GeminiService::class);
>>> $response1 = $service->generate($prompt, 'gemini-2.0-flash');
>>> $response2 = $service->generate($prompt, 'gemini-1.5-pro');
>>> echo "Flash: " . $response1;
>>> echo "Pro: " . $response2;
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| Wrong model selected | Check model selection logic, use premium model for VIP |
| Insufficient context | Add more recent messages or beliefs to prompt |
| Conflicting instructions in prompt | Review system prompt, remove contradictions |
| Model behavior drift | Check model version, regenerate embeddings |
| Contact profile incomplete | Add missing beliefs or preferences |

---

## 4. Database Issues

### Problem: Slow Queries

**Symptoms**:
- Database queries taking >1 second
- API latency increasing
- MySQL connection pool exhausted
- Queries timing out

**Diagnostic Steps**:

1. **Enable Slow Query Log**
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
```

2. **Identify Slow Queries**
```sql
-- Review slow query log
SELECT * FROM mysql.general_log WHERE command_type = 'Query' 
ORDER BY event_time DESC LIMIT 20;

-- Or use Performance Schema
SELECT * FROM performance_schema.events_statements_summary_by_digest 
WHERE DIGEST_TEXT LIKE '%memory%' 
ORDER BY SUM_TIMER_WAIT DESC;
```

3. **Analyze Query Plan**
```sql
EXPLAIN SELECT * FROM memory_fragments 
WHERE contact_id = ? 
AND confidence > 0.7;
```

**Solutions**:

| Issue | Fix |
|-------|-----|
| Full table scan | Add index on frequently queried columns |
| N+1 query problem | Use eager loading (with()) in Eloquent |
| Large result set | Add pagination or filtering |
| No index on JOIN column | Create index on foreign key |
| Index not being used | Check index statistics, rebuild if needed |

---

### Problem: Database Connection Errors

**Symptoms**:
- "Connection refused" errors
- "Too many connections" errors
- Intermittent connection failures
- Application freezes

**Diagnostic Steps**:

1. **Check MySQL Status**
```bash
# Verify MySQL is running
systemctl status mysql

# Check connections
mysql -e "SHOW PROCESSLIST;"
mysql -e "SHOW STATUS LIKE 'Threads%';"
```

2. **Check Connection Pool**
```php
php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::connection()->statement("SELECT 1");
```

3. **Check Network**
```bash
# Verify network connectivity
telnet localhost 3306

# Check firewall rules
sudo iptables -L | grep 3306
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| MySQL not running | `systemctl start mysql` or restart container |
| Connection pool exhausted | Increase pool size in .env `DB_CONNECTIONS` |
| Too many connections | Kill idle connections with `KILL CONNECTION id;` |
| Firewall blocking | Update firewall rules to allow port 3306 |
| Wrong credentials | Verify DB_USERNAME, DB_PASSWORD in .env |
| DNS resolution failing | Check /etc/hosts or DNS configuration |

---

## 5. Cache Issues

### Problem: Stale Cache

**Symptoms**:
- Updated data not reflected in responses
- Changes taking minutes to appear
- Cache inconsistency across servers

**Diagnostic Steps**:

1. **Check Cache Status**
```bash
# Verify Redis is running
redis-cli ping

# Check cache keys
redis-cli KEYS "nexus:*" | head -20

# Check cache TTL
redis-cli TTL nexus:contact:123
```

2. **Verify Cache Invalidation**
```bash
# Review invalidation logs
grep "cache" logs/laravel.log | grep -i invalidat

# Check if invalidation is firing
php artisan tinker
>>> Cache::forget('nexus:contact:123');
>>> Cache::get('nexus:contact:123') // should be null
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| Cache not invalidated on update | Add cache invalidation to update method |
| Wrong cache key used | Verify cache key naming convention |
| Cache never expires | Set TTL on cache write, or manually flush |
| Redis connection lost | Restart Redis, verify connection string |
| Multiple cache stores out of sync | Use centralized cache (Redis), not file cache |

---

## 6. Integration Issues

### Problem: WAHA Connection Lost

**Symptoms**:
- "WAHA unavailable" errors
- Messages not being received
- WhatsApp number appears offline

**Diagnostic Steps**:

1. **Check WAHA Health**
```bash
curl -X GET https://waha.example.com/health

# Check specific session
curl -X GET https://waha.example.com/sessions/nexus_instance
```

2. **Check WAHA Logs**
```bash
# If running locally
docker logs waha_container

# Or SSH to WAHA server and check logs
```

3. **Verify Phone Number**
```bash
# Check if phone is registered
curl -X GET https://waha.example.com/sessions | jq '.sessions'
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| WAHA service down | Restart WAHA: `docker restart waha_container` |
| Phone number session expired | Re-authenticate phone via WAHA UI |
| Network connectivity issue | Check firewall rules, VPN connection |
| API key invalid | Regenerate API key in WAHA settings |
| Rate-limited by WhatsApp | Wait 24 hours, or use different phone number |

---

### Problem: Email Not Sending

**Symptoms**:
- Email job fails silently
- No delivery confirmation
- SendGrid quota exceeded

**Diagnostic Steps**:

1. **Check Email Configuration**
```php
php artisan tinker
>>> config('mail');
```

2. **Test Mail Connection**
```php
php artisan tinker
>>> Mail::raw('test', function($msg) { 
    $msg->to('test@example.com'); 
});
```

3. **Check SendGrid**
```bash
# Check quota usage
curl -X GET https://api.sendgrid.com/v3/mail/send/statistics \
  -H "Authorization: Bearer $SENDGRID_API_KEY"
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| SendGrid credentials invalid | Verify API key in .env, regenerate if needed |
| Recipient email invalid | Check email format, verify not blacklisted |
| Quota exceeded | Upgrade SendGrid plan or wait for limit reset |
| SMTP port blocked | Change port (587 vs 465), check firewall |
| Email marked as spam | Review content, use authenticated domain |

---

## 7. Performance Issues

### Problem: High CPU Usage

**Symptoms**:
- CPU usage > 80% consistently
- Server slow to respond
- Background jobs slow

**Diagnostic Steps**:

```bash
# Identify process using CPU
top -b -n 1 | head -20

# Find PHP processes consuming CPU
ps aux | grep php | grep -v grep

# Check Laravel processes
ps aux | grep "php artisan"
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| Queue worker processing heavy job | Identify job, optimize or split into smaller jobs |
| N+1 database queries | Use eager loading, check logs for duplicate queries |
| Memory leak in background worker | Restart worker after N jobs, monitor memory |
| Unoptimized search or aggregation | Add indexes, cache results, use pagination |
| Spam or DDoS attack | Enable rate limiting, check firewall, block IPs |

---

### Problem: High Memory Usage

**Symptoms**:
- Memory > 90% utilized
- Process killed by OOM killer
- Swapping to disk

**Diagnostic Steps**:

```bash
# Check memory usage
free -h
vmstat 1 10

# Check PHP memory limit
php -i | grep memory_limit

# Check process memory
ps aux --sort=-%mem | head -10
```

**Solutions**:

| Root Cause | Fix |
|-----------|-----|
| PHP memory_limit too high | Reduce in php.ini, set appropriate limit |
| Memory leak in application | Profile code, identify leaking reference |
| Large collection loaded into memory | Paginate or use streaming, don't load all at once |
| Embedded large data in process | Cache separately, reduce payload size |

---

## Quick Reference Checklist

### Before You Call Support
- [ ] Verified the issue is reproducible
- [ ] Collected relevant log files
- [ ] Checked status page for known issues
- [ ] Tried restarting the affected service
- [ ] Checked resource utilization (CPU, memory, disk)
- [ ] Verified integrations are connected
- [ ] Confirmed database connectivity
- [ ] Checked network connectivity to providers

---

**Document Status**: COMPLETE - Troubleshooting procedures defined  
**Last Updated**: 2025-05-16  
**Next Document**: 04-DEVELOPER_QUICK_START.md
