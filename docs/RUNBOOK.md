# Semantic Knowledge - Operations Runbook

Operational procedures, checklists, and maintenance tasks for the Semantic Knowledge plugin.

## Table of Contents

- [Overview](#overview)
- [Daily Operations](#daily-operations)
- [Weekly Maintenance](#weekly-maintenance)
- [Monthly Review](#monthly-review)
- [Monitoring and Alerting](#monitoring-and-alerting)
- [Log Management](#log-management)
- [Cache Management](#cache-management)
- [Database Maintenance](#database-maintenance)
- [Performance Monitoring](#performance-monitoring)
- [API Key Management](#api-key-management)
- [Backup and Recovery](#backup-and-recovery)
- [Routine Tasks Reference](#routine-tasks-reference)

## Overview

This runbook provides standard operating procedures for maintaining the Semantic Knowledge plugin in production. It covers routine tasks, monitoring, and preventive maintenance.

### Service Level Objectives (SLOs)

- **Availability**: 99.9% uptime
- **Search Response Time**: < 2 seconds (95th percentile)
- **Chatbot Response Time**: < 3 seconds (95th percentile)
- **Indexer Success Rate**: > 95%
- **Error Rate**: < 1% of requests

### On-Call Responsibilities

- Monitor alerts and respond within 15 minutes
- Execute runbook procedures for incidents
- Escalate to senior engineer if unable to resolve within 30 minutes
- Document all incidents in incident log

## Daily Operations

### Morning Checklist (Start of Business)

#### 1. System Health Check

```bash
# Check Pantheon site status
terminus site:info kanopi-2019

# Check for errors in last 24 hours
terminus logs kanopi-2019.live --type=php-error --since="24 hours ago" | grep "Semantic_Knowledge_ASSISTANT"

# Verify plugin is active
terminus wp kanopi-2019.live -- plugin list --status=active --name=semantic-knowledge
```

**Expected Results**:
- Site status: Normal
- No critical errors
- Plugin active

**If Failed**: See [Troubleshooting](#troubleshooting-daily-checks)

#### 2. Check CircleCI Status

```bash
# View recent builds
open https://circleci.com/gh/kanopi/kanopi-2019

# Or use CLI
circleci build list --limit 5
```

**Expected Results**:
- Last build: Success
- No failed tests
- No blocked deployments

**If Failed**: Review failed job logs, fix issues, redeploy

#### 3. Review AI Service Status

Check external service status pages:

- **OpenAI**: https://status.openai.com
- **Pinecone**: https://status.pinecone.io

**If Degraded**: Check [Incident Response](INCIDENT-RESPONSE.md#api-outages)

#### 4. Monitor API Usage

```bash
# Check OpenAI API usage (via OpenAI dashboard)
# Navigate to: https://platform.openai.com/usage

# Check Pinecone index statistics
# Dashboard: https://app.pinecone.io/
```

**Expected Results**:
- Usage within expected range
- No rate limit warnings
- Index vectors match expected count

**If Exceeded**: Review usage patterns, optimize queries

#### 5. Review Log Statistics

```bash
# Get yesterday's activity stats
terminus wp kanopi-2019.live -- db query "
SELECT
  DATE(created_at) as date,
  COUNT(*) as total,
  AVG(response_time) as avg_response_ms
FROM wp_sk_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY DATE(created_at)"

# Check search logs
terminus wp kanopi-2019.live -- db query "
SELECT
  DATE(created_at) as date,
  COUNT(*) as total,
  AVG(response_time) as avg_response_ms
FROM wp_sk_search_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY DATE(created_at)"
```

**Expected Results**:
- Chat interactions: 50-200 per day
- Search queries: 100-500 per day
- Average response time: < 2000ms

**If Anomalous**: Investigate spikes or drops

### Evening Checklist (End of Business)

#### 1. Review Day's Activity

```bash
# Check for new errors
terminus logs kanopi-2019.live --type=php-error --since="8 hours ago" | grep "Semantic_Knowledge_ASSISTANT" | tail -20

# Check New Relic (if available)
# Navigate to: https://one.newrelic.com
# Review: Error rate, response times, throughput
```

#### 2. Verify Backups

```bash
# List recent backups
terminus backup:list kanopi-2019.live --element=all

# Expected: Daily automated backups exist
```

#### 3. Check Pending Updates

```bash
# Check for WordPress core updates
terminus wp kanopi-2019.live -- core check-update

# Check for plugin updates
terminus wp kanopi-2019.live -- plugin list --update=available

# Check for theme updates
terminus wp kanopi-2019.live -- theme list --update=available
```

**Action**: Schedule updates during maintenance window

## Weekly Maintenance

### Monday Morning - Full Reindex

The indexer automatically runs a full reindex every Monday. Monitor the process:

#### 1. Verify Automatic Reindex

```bash
# Check CircleCI for ai-assistant-indexer workflow
# Expected: Runs after Sunday midnight deployment

# View indexer logs
terminus logs kanopi-2019.live --type=nginx-access | grep "ai-indexer"
```

#### 2. Manual Reindex (If Needed)

```bash
# Run full reindex manually
terminus wp kanopi-2019.live -- ai-indexer index

# Expected output:
# ✓ Fetching content from WordPress...
# ✓ Processing 250 posts...
# ✓ Successfully indexed 250 posts
# Duration: 5m 32s
```

#### 3. Verify Index Health

```bash
# Check Pinecone dashboard
# Navigate to: https://app.pinecone.io/
# Verify:
# - Vector count matches expected
# - No errors in index
# - Index size appropriate
```

### Wednesday - Performance Review

#### 1. Analyze Response Times

```bash
# Query average response times (last 7 days)
terminus wp kanopi-2019.live -- db query "
SELECT
  DATE(created_at) as date,
  AVG(response_time) as avg_chat_ms,
  MIN(response_time) as min_ms,
  MAX(response_time) as max_ms
FROM wp_sk_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC"
```

**Expected Results**:
- Average: < 2000ms
- Max: < 5000ms

**If Degraded**: See [Performance Monitoring](#performance-monitoring)

#### 2. Review Cache Hit Rates

```bash
# Check Redis cache statistics (if available)
terminus redis kanopi-2019.live -- info stats

# Look for:
# - keyspace_hits / keyspace_misses ratio
# - evicted_keys (should be low)
```

**Expected**: Hit rate > 80%

#### 3. Database Query Analysis

```bash
# Check slow query log
terminus wp kanopi-2019.live -- db query "
SELECT * FROM mysql.slow_log
WHERE db = 'pantheon'
AND query_time > 1
ORDER BY start_time DESC
LIMIT 20"
```

**If Slow Queries Found**: Optimize queries, add indexes

### Friday - Security and Updates

#### 1. Security Audit

```bash
# Run composer security audit
cd web/wp-content/plugins/semantic-knowledge
composer audit

# Run npm security audit
cd packages/wp-ai-indexer
npm audit

# Check WordPress security
terminus wp kanopi-2019.live -- plugin list --update=available
terminus wp kanopi-2019.live -- core check-update
```

**Action**: Apply security updates immediately if critical

#### 2. Review Access Logs

```bash
# Check for suspicious activity
terminus logs kanopi-2019.live --type=nginx-access | grep "wp-json.*ai-indexer"

# Look for:
# - Unusual IP addresses
# - High request volumes
# - Failed authentication attempts
```

#### 3. API Key Rotation Check

```bash
# Verify API keys are not approaching rotation date
# OpenAI keys: Rotate every 90 days
# Pinecone keys: Rotate every 90 days
# Check last rotation date in password manager
```

## Monthly Review

### First Monday of Month - Comprehensive Review

#### 1. Usage Analysis

```bash
# Generate monthly usage report
terminus wp kanopi-2019.live -- db query "
SELECT
  COUNT(*) as total_chats,
  COUNT(DISTINCT session_id) as unique_sessions,
  AVG(response_time) as avg_response_ms
FROM wp_sk_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"

# Search query analysis
terminus wp kanopi-2019.live -- db query "
SELECT
  query,
  COUNT(*) as frequency,
  AVG(response_time) as avg_response_ms
FROM wp_sk_search_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
GROUP BY query
ORDER BY frequency DESC
LIMIT 20"
```

**Action**:
- Document trends
- Identify optimization opportunities
- Report metrics to stakeholders

#### 2. Cost Analysis

Review monthly costs for:

- **OpenAI API**: Embedding + Chat completions
- **Pinecone**: Vector storage + queries
- **Pantheon**: Hosting costs

**Budget Check**:
```bash
# OpenAI usage (via dashboard)
# https://platform.openai.com/usage

# Calculate estimated cost:
# - Embeddings: $0.00002/token (text-embedding-3-small)
# - Chat: $0.150/1M input tokens, $0.600/1M output tokens (gpt-4o-mini)

# Pinecone usage (via dashboard)
# https://app.pinecone.io/billing
```

#### 3. Log Cleanup

```bash
# Check log table sizes
terminus wp kanopi-2019.live -- db query "
SELECT
  table_name,
  ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
  table_rows
FROM information_schema.TABLES
WHERE table_schema = 'pantheon'
AND table_name LIKE 'semantic_knowledge_%'
ORDER BY (data_length + index_length) DESC"

# Clean old logs (90 days default)
terminus wp kanopi-2019.live -- db query "
DELETE FROM wp_sk_chat_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"

terminus wp kanopi-2019.live -- db query "
DELETE FROM wp_sk_search_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
```

#### 4. Plugin Updates

```bash
# Update plugin (if new version available)
terminus wp kanopi-2019.dev -- plugin update semantic-knowledge

# Test in dev environment
# Run smoke tests
# Check for errors

# If successful, deploy to staging
terminus env:deploy kanopi-2019.test

# Test in staging

# Deploy to production (during maintenance window)
terminus env:deploy kanopi-2019.live
```

#### 5. Documentation Review

- [ ] Review and update runbook
- [ ] Update incident response procedures
- [ ] Review and update deployment checklist
- [ ] Update FAQs based on support tickets

## Monitoring and Alerting

### Key Metrics

#### Application Metrics

| Metric | Threshold | Alert Level |
|--------|-----------|-------------|
| Error Rate | > 1% | Warning |
| Error Rate | > 5% | Critical |
| Response Time (p95) | > 3s | Warning |
| Response Time (p95) | > 5s | Critical |
| Search Availability | < 99% | Critical |
| Chatbot Availability | < 99% | Critical |

#### Infrastructure Metrics

| Metric | Threshold | Alert Level |
|--------|-----------|-------------|
| PHP Memory Usage | > 80% | Warning |
| PHP Memory Usage | > 95% | Critical |
| Disk Space | < 20% free | Warning |
| Disk Space | < 10% free | Critical |
| CPU Usage | > 80% sustained | Warning |
| Database Connections | > 80% | Warning |

#### External Service Metrics

| Metric | Threshold | Alert Level |
|--------|-----------|-------------|
| OpenAI API Errors | > 5% | Critical |
| Pinecone API Errors | > 5% | Critical |
| API Rate Limits | Approaching limit | Warning |

### Alert Configuration

#### New Relic Alerts (If Available)

```yaml
# Example alert configuration
alerts:
  - name: "Semantic Knowledge - High Error Rate"
    condition: "error_rate > 5%"
    duration: 5 minutes
    severity: critical

  - name: "Semantic Knowledge - Slow Response Time"
    condition: "response_time_p95 > 5s"
    duration: 5 minutes
    severity: warning

  - name: "Semantic Knowledge - API Failures"
    condition: "external_api_error_rate > 5%"
    duration: 5 minutes
    severity: critical
```

#### Pantheon Monitoring

Enable Pantheon's built-in monitoring:

```bash
# Configure uptime monitoring
terminus site:upstream:set kanopi-2019 --check-frequency=5m

# Enable New Relic (if available)
terminus new-relic:enable kanopi-2019.live
```

### Custom Monitoring Scripts

Create custom monitoring script to run via cron:

```php
<?php
/**
 * Semantic Knowledge Health Check
 *
 * Run via: wp eval-file health-check.php
 */

$logger = Semantic_Knowledge_Logger::instance();

// Check database tables
$tables = ['semantic_knowledge_chat_logs', 'semantic_knowledge_search_logs'];
foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$exists) {
        $logger->critical("Database table missing: {$table}");
    }
}

// Check API connectivity
$core = new Semantic_Knowledge_Core();
$openai = new Semantic_Knowledge_OpenAI($core, new Semantic_Knowledge_Secrets());

try {
    // Test OpenAI connection
    $test = $openai->create_embedding('test');
    $logger->info('OpenAI API: OK');
} catch (Exception $e) {
    $logger->error('OpenAI API: FAILED - ' . $e->getMessage());
}

// Check Pinecone
$pinecone = new Semantic_Knowledge_Pinecone($core, new Semantic_Knowledge_Secrets());
try {
    $stats = $pinecone->describe_index_stats();
    $logger->info('Pinecone API: OK');
} catch (Exception $e) {
    $logger->error('Pinecone API: FAILED - ' . $e->getMessage());
}

// Check recent errors
$stats = Semantic_Knowledge_Database::get_stats();
if ($stats['chat_logs_today'] == 0 && date('H') > 12) {
    $logger->warning('No chat logs today - possible issue');
}

WP_CLI::success('Health check complete');
```

Schedule via cron:

```bash
# Add to crontab
*/15 * * * * terminus wp kanopi-2019.live -- eval-file /path/to/health-check.php
```

## Log Management

### Log Types

#### 1. Application Logs (WordPress debug.log)

**Location**: `wp-content/debug.log`

**View Recent Logs**:
```bash
# Last 100 Semantic Knowledge log entries
terminus logs kanopi-2019.live --type=php-error | grep "Semantic_Knowledge_ASSISTANT" | tail -100
```

#### 2. Chat Logs (Database)

**Table**: `semantic_knowledge_chat_logs`

**Query Logs**:
```bash
# Recent chat interactions
terminus wp kanopi-2019.live -- db query "
SELECT
  id,
  LEFT(question, 50) as question,
  response_time,
  created_at
FROM wp_sk_chat_logs
ORDER BY created_at DESC
LIMIT 20"
```

#### 3. Search Logs (Database)

**Table**: `semantic_knowledge_search_logs`

**Query Logs**:
```bash
# Recent search queries
terminus wp kanopi-2019.live -- db query "
SELECT
  id,
  query,
  results_count,
  response_time,
  created_at
FROM wp_sk_search_logs
ORDER BY created_at DESC
LIMIT 20"
```

#### 4. Indexer Logs

**Location**: CircleCI job output

**View Logs**:
```bash
# View recent indexer runs
# Navigate to: https://circleci.com/gh/kanopi/kanopi-2019
# Select: ai-assistant-indexer workflow
```

### Log Retention Policy

| Log Type | Retention Period | Storage Location | Cleanup Method |
|----------|-----------------|------------------|----------------|
| WordPress debug.log | 30 days | File system | Log rotation |
| Chat logs | 90 days | Database | WP-Cron cleanup |
| Search logs | 90 days | Database | WP-Cron cleanup |
| PHP error logs | 7 days | Pantheon | Automatic |
| CircleCI logs | 30 days | CircleCI | Automatic |

### Log Analysis

#### Error Pattern Analysis

```bash
# Most common errors (last 24 hours)
terminus logs kanopi-2019.live --type=php-error --since="24 hours ago" | \
  grep "Semantic_Knowledge_ASSISTANT" | \
  awk '{print $NF}' | \
  sort | uniq -c | sort -nr | head -10
```

#### Performance Analysis

```bash
# Slow queries (> 3 seconds)
terminus wp kanopi-2019.live -- db query "
SELECT
  LEFT(question, 80) as question,
  response_time,
  created_at
FROM wp_sk_chat_logs
WHERE response_time > 3000
ORDER BY response_time DESC
LIMIT 20"
```

#### User Activity Analysis

```bash
# Most active users/sessions
terminus wp kanopi-2019.live -- db query "
SELECT
  session_id,
  COUNT(*) as interactions,
  AVG(response_time) as avg_response
FROM wp_sk_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY session_id
ORDER BY interactions DESC
LIMIT 10"
```

### Log Cleanup

#### Manual Cleanup

```bash
# Clean logs older than 90 days
terminus wp kanopi-2019.live -- db query "
DELETE FROM wp_sk_chat_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"

terminus wp kanopi-2019.live -- db query "
DELETE FROM wp_sk_search_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"

# Verify cleanup
terminus wp kanopi-2019.live -- db query "
SELECT
  'chat_logs' as table_name,
  COUNT(*) as remaining
FROM wp_sk_chat_logs
UNION ALL
SELECT
  'search_logs' as table_name,
  COUNT(*) as remaining
FROM wp_sk_search_logs"
```

#### Automated Cleanup

The plugin runs automatic cleanup daily via WP-Cron:

```php
// Hook: semantic_knowledge_cleanup_logs
// Frequency: Daily
// Retention: 90 days (configurable via settings)
```

Verify cron is running:

```bash
# Check scheduled events
terminus wp kanopi-2019.live -- cron event list | grep "semantic_knowledge_cleanup_logs"

# Manually trigger cleanup
terminus wp kanopi-2019.live -- cron event run semantic_knowledge_cleanup_logs
```

## Cache Management

### Cache Layers

#### 1. Object Cache (Redis)

**Type**: Persistent object cache
**Provider**: Pantheon Redis
**TTL**: 1 hour (3600 seconds)

**Cached Data**:
- Plugin settings
- Indexer configuration
- System check results

**Management**:
```bash
# Flush Redis cache
terminus redis kanopi-2019.live -- clear

# Verify cache statistics
terminus redis kanopi-2019.live -- info stats

# Check specific cache keys
terminus wp kanopi-2019.live -- cache get semantic_knowledge_settings_cache semantic_knowledge
```

#### 2. Page Cache (Varnish)

**Type**: Edge cache
**Provider**: Pantheon Varnish
**TTL**: 10 minutes (configurable)

**Management**:
```bash
# Clear Varnish cache
terminus env:clear-cache kanopi-2019.live

# Clear specific URLs
curl -X PURGE https://yoursite.com/path/to/page
```

#### 3. Transients

**Type**: WordPress transients
**Storage**: Database
**TTL**: Varies by transient

**Cached Data**:
- System check results (1 hour)
- API responses (configurable)

**Management**:
```bash
# List AI Assistant transients
terminus wp kanopi-2019.live -- transient list | grep "wp_ai"

# Delete specific transient
terminus wp kanopi-2019.live -- transient delete semantic_knowledge_system_check

# Delete all expired transients
terminus wp kanopi-2019.live -- transient delete --expired
```

### Cache Warming

After clearing caches, warm critical endpoints:

```bash
# Warm homepage
curl -I https://yoursite.com/

# Warm settings API
curl -I https://yoursite.com/wp-json/wp/v2/ai-indexer/settings

# Warm search page
curl -I https://yoursite.com/?s=test
```

### Cache Invalidation Strategy

The plugin automatically invalidates caches on:

- Settings update
- Plugin activation/deactivation
- Content index update

Manual invalidation when:

- Deploying new code
- Changing external API configuration
- Troubleshooting issues

## Database Maintenance

### Database Tables

The plugin creates and maintains these tables:

| Table | Purpose | Approximate Size |
|-------|---------|-----------------|
| `semantic_knowledge_chat_logs` | Chat interaction logs | 10-50 MB |
| `semantic_knowledge_search_logs` | Search query logs | 5-25 MB |

### Optimization Tasks

#### Weekly Optimization

```bash
# Optimize tables
terminus wp kanopi-2019.live -- db optimize --tables="wp_sk_chat_logs,wp_sk_search_logs"

# Check table statistics
terminus wp kanopi-2019.live -- db query "
SELECT
  table_name,
  ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
  table_rows,
  ROUND((data_free) / 1024 / 1024, 2) AS free_mb
FROM information_schema.TABLES
WHERE table_schema = 'pantheon'
AND table_name LIKE 'semantic_knowledge_%'"
```

#### Monthly Maintenance

```bash
# Analyze tables
terminus wp kanopi-2019.live -- db query "ANALYZE TABLE wp_sk_chat_logs, wp_sk_search_logs"

# Rebuild indexes
terminus wp kanopi-2019.live -- db query "ALTER TABLE wp_sk_chat_logs ENGINE=InnoDB"
terminus wp kanopi-2019.live -- db query "ALTER TABLE wp_sk_search_logs ENGINE=InnoDB"
```

### Database Backups

Pantheon automatically backs up databases daily. Verify backups exist:

```bash
# List database backups
terminus backup:list kanopi-2019.live --element=database

# Create manual backup before maintenance
terminus backup:create kanopi-2019.live --element=database
```

### Table Monitoring

Monitor table growth:

```bash
# Track table size over time
terminus wp kanopi-2019.live -- db query "
SELECT
  DATE(NOW()) as date,
  table_name,
  table_rows,
  ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = 'pantheon'
AND table_name LIKE 'semantic_knowledge_%'" > db-size-$(date +%Y-%m-%d).log
```

Alert if tables grow unexpectedly:
- Chat logs: > 100,000 rows per month
- Search logs: > 50,000 rows per month
- Table size: > 100 MB

## Performance Monitoring

### Response Time Monitoring

#### Measure Response Times

```bash
# Average response times (last 24 hours)
terminus wp kanopi-2019.live -- db query "
SELECT
  'Chat' as type,
  COUNT(*) as total,
  AVG(response_time) as avg_ms,
  MIN(response_time) as min_ms,
  MAX(response_time) as max_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY response_time) as p95_ms
FROM wp_sk_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
UNION ALL
SELECT
  'Search' as type,
  COUNT(*) as total,
  AVG(response_time) as avg_ms,
  MIN(response_time) as min_ms,
  MAX(response_time) as max_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY response_time) as p95_ms
FROM wp_sk_search_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
```

#### Performance Benchmarking

```bash
# Test search performance
time curl -X GET "https://yoursite.com/?s=test&ai_search=1"

# Test chatbot API performance
time curl -X POST "https://yoursite.com/wp-json/semantic-knowledge/v1/chat" \
  -H "Content-Type: application/json" \
  -d '{"message":"test question"}'
```

### Resource Usage

#### PHP Memory

```bash
# Check PHP memory limit
terminus wp kanopi-2019.live -- eval "echo WP_MEMORY_LIMIT;"

# Monitor memory usage
terminus logs kanopi-2019.live --type=php-error | grep "memory"
```

#### Database Queries

```bash
# Enable query logging temporarily
terminus wp kanopi-2019.live -- config set SAVEQUERIES true --type=constant

# View slow queries
terminus wp kanopi-2019.live -- db query "
SELECT * FROM mysql.slow_log
WHERE db = 'pantheon'
AND query_time > 1
ORDER BY start_time DESC
LIMIT 20"
```

### Optimization Actions

If performance degrades:

1. **Clear all caches**
2. **Optimize database tables**
3. **Review slow query log**
4. **Check external API response times**
5. **Review recent code changes**
6. **Scale resources if needed**

## API Key Management

### Key Types

| API | Purpose | Rotation Frequency | Storage |
|-----|---------|-------------------|---------|
| OpenAI API Key | Embeddings + Chat | 90 days | Pantheon Secrets |
| Pinecone API Key | Vector storage/search | 90 days | Pantheon Secrets |
| WP Application Password | Indexer authentication | 180 days | CircleCI Variables |

### Key Rotation Process

#### OpenAI API Key

```bash
# Generate new key in OpenAI dashboard
# https://platform.openai.com/api-keys

# Set in Pantheon Secrets
terminus secret:set kanopi-2019.live OPENAI_API_KEY "sk-..."

# Update in CircleCI (for indexer)
# Navigate to: Project Settings → Environment Variables
# Update: OPENAI_API_KEY

# Verify new key works
terminus wp kanopi-2019.live -- ai-indexer config

# Remove old key from OpenAI dashboard
```

#### Pinecone API Key

```bash
# Generate new key in Pinecone console
# https://app.pinecone.io/organizations/

# Set in Pantheon Secrets
terminus secret:set kanopi-2019.live PINECONE_API_KEY "..."

# Update in CircleCI
# Navigate to: Project Settings → Environment Variables
# Update: PINECONE_API_KEY

# Verify new key works
terminus wp kanopi-2019.live -- ai-indexer config

# Remove old key from Pinecone console
```

#### WordPress Application Password

```bash
# Generate new application password
terminus wp kanopi-2019.live -- user application-password create indexer-user indexer-circleci

# Update in CircleCI
# Navigate to: Project Settings → Environment Variables
# Update: WP_API_USERNAME and WP_API_PASSWORD

# Test authentication
curl -u "indexer-user:NEW_PASSWORD" \
  "https://yoursite.com/wp-json/wp/v2/types"

# Remove old application password
terminus wp kanopi-2019.live -- user application-password list indexer-user
terminus wp kanopi-2019.live -- user application-password delete indexer-user OLD_UUID
```

### Key Monitoring

Track key usage and expiration:

```bash
# Create key rotation reminder
# Add to calendar:
# - OpenAI key: Rotate every 90 days
# - Pinecone key: Rotate every 90 days
# - WP password: Rotate every 180 days

# Document last rotation date in password manager
```

## Backup and Recovery

### Automated Backups

Pantheon performs automatic daily backups:

```bash
# Verify daily backups exist
terminus backup:list kanopi-2019.live

# Expected: Daily backups for last 7 days
```

### Manual Backup Process

Before major changes:

```bash
# Create full backup (code + files + database)
terminus backup:create kanopi-2019.live --element=all --keep-for=30

# Verify backup created
terminus backup:list kanopi-2019.live

# Download backup (for off-site storage)
terminus backup:get kanopi-2019.live --element=database --to=backup-$(date +%Y-%m-%d).sql.gz
terminus backup:get kanopi-2019.live --element=files --to=backup-files-$(date +%Y-%m-%d).tar.gz
```

### Backup Verification

Monthly backup testing:

```bash
# Restore backup to dev environment
terminus backup:restore kanopi-2019.dev --element=database --yes

# Verify data integrity
terminus wp kanopi-2019.dev -- db check

# Test plugin functionality
terminus wp kanopi-2019.dev -- plugin list
terminus wp kanopi-2019.dev -- ai-indexer check
```

### Recovery Procedures

See [DISASTER-RECOVERY.md](DISASTER-RECOVERY.md) for complete recovery procedures.

## Routine Tasks Reference

### Quick Reference Table

| Task | Frequency | Estimated Time | Priority |
|------|-----------|---------------|----------|
| Morning health check | Daily | 5 min | High |
| Review error logs | Daily | 5 min | High |
| Check API status | Daily | 2 min | Medium |
| Clear cache (if needed) | As needed | 1 min | Medium |
| Full reindex | Weekly (Mon) | 10 min | Medium |
| Performance review | Weekly (Wed) | 15 min | Medium |
| Security audit | Weekly (Fri) | 10 min | High |
| Database optimization | Weekly | 5 min | Low |
| Usage analysis | Monthly | 30 min | Medium |
| Cost review | Monthly | 15 min | High |
| Log cleanup | Monthly | 5 min | Low |
| API key rotation | Every 90 days | 30 min | High |
| Backup verification | Monthly | 20 min | High |
| Documentation update | Monthly | 30 min | Medium |

### Command Shortcuts

Create shell aliases for common commands:

```bash
# Add to ~/.bashrc or ~/.zshrc
alias ai-health="terminus wp kanopi-2019.live -- ai-indexer check"
alias ai-logs="terminus logs kanopi-2019.live --type=php-error | grep Semantic_Knowledge_ASSISTANT"
alias ai-stats="terminus wp kanopi-2019.live -- db query 'SELECT COUNT(*) FROM wp_sk_chat_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'"
alias ai-reindex="terminus wp kanopi-2019.live -- ai-indexer index"
alias ai-cache-clear="terminus env:clear-cache kanopi-2019.live && terminus wp kanopi-2019.live -- cache flush"
```

## Troubleshooting Daily Checks

### Plugin Not Active

```bash
# Activate plugin
terminus wp kanopi-2019.live -- plugin activate semantic-knowledge

# If activation fails, check error log
terminus logs kanopi-2019.live --type=php-error | tail -50
```

### System Check Failures

```bash
# Run system check
terminus wp kanopi-2019.live -- ai-indexer check

# If Node.js missing:
# Contact Pantheon support or DevOps team

# If indexer missing:
# Redeploy code via CircleCI
```

### High Error Rate

```bash
# Check recent errors
terminus logs kanopi-2019.live --type=php-error --since="1 hour ago" | grep "Semantic_Knowledge_ASSISTANT"

# Common causes:
# - API keys expired/invalid
# - Pinecone index unavailable
# - Rate limits exceeded

# Resolution:
# 1. Verify API keys
# 2. Check external service status
# 3. Review error patterns
# 4. Escalate if unresolved
```

## Related Documentation

- [DEPLOYMENT.md](DEPLOYMENT.md) - Deployment procedures
- [INCIDENT-RESPONSE.md](INCIDENT-RESPONSE.md) - Incident response guide
- [DISASTER-RECOVERY.md](DISASTER-RECOVERY.md) - Disaster recovery plan
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Troubleshooting guide
- [MONITORING.md](MONITORING.md) - Monitoring and alerting

## Revision History

| Date | Version | Author | Changes |
|------|---------|--------|---------|
| 2026-01-28 | 1.0.0 | System | Initial operations runbook |
