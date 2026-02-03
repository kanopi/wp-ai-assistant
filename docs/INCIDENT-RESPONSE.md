# WP AI Assistant - Incident Response Guide

Comprehensive incident response procedures for the WP AI Assistant plugin.

## Table of Contents

- [Overview](#overview)
- [Incident Classification](#incident-classification)
- [On-Call Procedures](#on-call-procedures)
- [Incident Response Workflow](#incident-response-workflow)
- [Common Incidents](#common-incidents)
  - [API Outages](#api-outages)
  - [Performance Degradation](#performance-degradation)
  - [Security Incidents](#security-incidents)
  - [Data Integrity Issues](#data-integrity-issues)
- [Escalation Procedures](#escalation-procedures)
- [Post-Incident Review](#post-incident-review)
- [Communication Templates](#communication-templates)

## Overview

This guide provides procedures for responding to incidents affecting the WP AI Assistant plugin. All on-call engineers should familiarize themselves with these procedures.

### Incident Definition

An **incident** is any unplanned interruption or degradation of service that impacts users' ability to use the AI Assistant features (search or chatbot).

### Response Objectives

- **Detect**: Identify incidents quickly through monitoring and alerts
- **Respond**: Acknowledge and begin investigation within 15 minutes
- **Resolve**: Restore service as quickly as possible
- **Learn**: Conduct post-incident review to prevent recurrence

## Incident Classification

### Severity Levels

#### P1 - Critical

**Definition**: Complete service outage affecting all users

**Examples**:
- Plugin crashes causing site downtime
- All search or chatbot requests failing (100% error rate)
- Database corruption preventing any AI functionality
- Security breach or data exposure

**Response Time**: 15 minutes
**Resolution Target**: 1 hour
**Notification**: Immediate (phone/SMS)
**Escalation**: Automatic to senior engineer after 30 minutes

#### P2 - High

**Definition**: Major feature failure or severe performance degradation

**Examples**:
- Search or chatbot failing for > 25% of users
- Response times > 10 seconds
- Indexer completely failing
- External API outage (OpenAI, Pinecone) with no workaround

**Response Time**: 30 minutes
**Resolution Target**: 4 hours
**Notification**: Slack alert
**Escalation**: To senior engineer after 2 hours

#### P3 - Medium

**Definition**: Partial service degradation affecting some users

**Examples**:
- Intermittent errors (< 25% error rate)
- Slower than normal response times (3-5 seconds)
- Non-critical features unavailable
- Single environment affected (dev/test)

**Response Time**: 1 hour
**Resolution Target**: 8 hours (within business day)
**Notification**: Slack alert
**Escalation**: To senior engineer if unresolved by EOD

#### P4 - Low

**Definition**: Minor issues with minimal user impact

**Examples**:
- Cosmetic issues
- Non-blocking errors in logs
- Single user reports issue that cannot be reproduced
- Documentation gaps

**Response Time**: 4 hours
**Resolution Target**: Next sprint
**Notification**: Ticket creation
**Escalation**: Standard ticket workflow

### Priority Matrix

| Impact | Urgency | Severity |
|--------|---------|----------|
| All users | Complete failure | P1 |
| All users | Degraded | P2 |
| Some users | Complete failure | P2 |
| Some users | Degraded | P3 |
| Single user | Complete failure | P3 |
| Single user | Degraded | P4 |

## On-Call Procedures

### On-Call Responsibilities

**Primary On-Call Engineer**:
- Monitor alerts 24/7 during on-call rotation
- Respond to P1/P2 incidents within SLA
- Execute incident response procedures
- Document all actions in incident log
- Conduct post-incident review

**Secondary On-Call (Backup)**:
- Available if primary cannot respond
- Escalation point for complex issues
- Senior engineer guidance

### On-Call Rotation

- **Duration**: 1 week (Monday 9am - Monday 9am)
- **Handoff**: Monday morning with brief sync
- **Schedule**: Maintained in PagerDuty
- **Compensation**: Per company policy

### Response Checklist

When alert received:

1. **Acknowledge** - Acknowledge alert in PagerDuty (stops escalation)
2. **Assess** - Determine severity using classification guide
3. **Communicate** - Post in #incidents Slack channel
4. **Investigate** - Follow incident response workflow
5. **Resolve** - Apply fix and verify resolution
6. **Document** - Complete incident report

## Incident Response Workflow

### Phase 1: Detection (0-5 minutes)

#### Alert Received

```bash
# Acknowledge alert in PagerDuty
# Post initial notification in Slack

Incident Detected
-----------------
Severity: [P1/P2/P3/P4]
Description: [Brief description]
Time: [Timestamp]
On-call: [Your name]
Status: Investigating
```

#### Initial Assessment

```bash
# Quick health check
terminus site:info kanopi-2019

# Check site accessibility
curl -I https://yoursite.com

# Check plugin status
terminus wp kanopi-2019.live -- plugin list --status=active --name=wp-ai-assistant

# Check recent errors
terminus logs kanopi-2019.live --type=php-error --since="15 minutes ago" | grep "WP_AI_ASSISTANT"
```

### Phase 2: Investigation (5-30 minutes)

#### Gather Information

1. **Error Rate**
```bash
# Check error count
terminus logs kanopi-2019.live --type=php-error --since="1 hour ago" | grep "WP_AI_ASSISTANT" | wc -l
```

2. **Response Times**
```bash
# Check recent response times
terminus wp kanopi-2019.live -- db query "
SELECT
  AVG(response_time) as avg_ms,
  MAX(response_time) as max_ms,
  COUNT(*) as total
FROM wp_ai_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
```

3. **External Services**
```bash
# Check OpenAI status
curl https://status.openai.com/api/v2/status.json

# Check Pinecone status
curl https://status.pinecone.io/api/v2/status.json
```

4. **Recent Changes**
```bash
# Check recent deployments
terminus env:code-log kanopi-2019.live --limit=5

# Check CircleCI recent builds
circleci build list --limit=5
```

#### Identify Root Cause

Common issues by symptom:

| Symptom | Likely Cause | Check |
|---------|--------------|-------|
| All requests failing | Plugin error, API keys | Error logs, credentials |
| Slow responses | API latency, DB queries | Response times, query log |
| Intermittent errors | Rate limits, timeouts | API quotas, timeout settings |
| Site down | Server issue, fatal error | Pantheon status, PHP errors |

### Phase 3: Mitigation (30-60 minutes)

#### Immediate Actions

**For P1 Incidents** - Choose fastest mitigation:

##### Option 1: Disable Plugin (Fastest)

```bash
# Deactivate plugin to restore site
terminus wp kanopi-2019.live -- plugin deactivate wp-ai-assistant

# Verify site is accessible
curl -I https://yoursite.com

# Update status
echo "Plugin deactivated - site restored. Investigating root cause."
```

##### Option 2: Enable Maintenance Mode

```bash
# Put site in maintenance mode
terminus wp kanopi-2019.live -- maintenance-mode activate

# Fix issue in dev/staging
terminus wp kanopi-2019.dev -- plugin activate wp-ai-assistant
# Test fix...

# Deploy fix to production
terminus env:deploy kanopi-2019.live

# Disable maintenance mode
terminus wp kanopi-2019.live -- maintenance-mode deactivate
```

##### Option 3: Rollback Deployment

```bash
# Rollback to previous code version
terminus env:code-log kanopi-2019.live --limit=5
terminus env:deploy kanopi-2019.live --sync-content=false

# Clear caches
terminus env:clear-cache kanopi-2019.live

# Verify site restored
curl -I https://yoursite.com
```

**For P2 Incidents** - Targeted fixes:

```bash
# Clear all caches
terminus env:clear-cache kanopi-2019.live
terminus wp kanopi-2019.live -- cache flush

# Restart PHP processes
terminus env:wake kanopi-2019.live

# Optimize database
terminus wp kanopi-2019.live -- db optimize

# Verify improvement
curl -w "@curl-format.txt" https://yoursite.com
```

### Phase 4: Resolution (1-4 hours)

#### Deploy Permanent Fix

1. **Identify Fix**
   - Review error logs
   - Reproduce in dev environment
   - Develop and test fix

2. **Deploy Fix**
```bash
# Deploy fix to dev
git push origin feature/fix-incident-123

# Test in dev
terminus wp kanopi-2019.dev -- ai-indexer check

# Deploy to production (if verified)
git checkout main
git merge feature/fix-incident-123
git push origin main

# Monitor deployment
# Watch CircleCI: https://circleci.com/gh/kanopi/kanopi-2019
```

3. **Verify Resolution**
```bash
# Check error rate (should be < 1%)
terminus logs kanopi-2019.live --type=php-error --since="30 minutes ago" | grep "WP_AI_ASSISTANT" | wc -l

# Check response times
terminus wp kanopi-2019.live -- db query "
SELECT AVG(response_time) as avg_ms
FROM wp_ai_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"

# Test functionality
curl -X POST "https://yoursite.com/wp-json/wp-ai-assistant/v1/chat" \
  -H "Content-Type: application/json" \
  -d '{"message":"test"}'
```

### Phase 5: Recovery (Post-Resolution)

#### Post-Resolution Checklist

- [ ] Service fully restored to normal operation
- [ ] Error rate < 1%
- [ ] Response times within normal range
- [ ] All monitoring alerts cleared
- [ ] Stakeholders notified of resolution
- [ ] Incident report drafted
- [ ] Post-incident review scheduled

## Common Incidents

### API Outages

#### Incident: OpenAI API Down

**Symptoms**:
- All embedding/chat requests failing
- Error: "Connection refused" or timeout
- OpenAI status page shows outage

**Investigation**:
```bash
# Check OpenAI status
curl https://status.openai.com/api/v2/status.json | jq '.status'

# Test API directly
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer $OPENAI_API_KEY"

# Check recent errors
terminus logs kanopi-2019.live --type=php-error | grep "openai"
```

**Mitigation**:

**Option 1: Wait for Recovery** (if brief outage expected)
```bash
# Enable graceful degradation (if implemented)
# Users see: "AI features temporarily unavailable"

# Monitor status
while true; do
  curl -s https://status.openai.com/api/v2/status.json | jq '.status'
  sleep 60
done
```

**Option 2: Fallback to Basic Search** (if extended outage)
```bash
# Disable AI search module
terminus wp kanopi-2019.live -- option update wp_ai_assistant_settings '{
  "search_enabled": false,
  "search_replace_default": false
}' --format=json

# Users fall back to WordPress native search
```

**Option 3: Switch to Backup Provider** (if configured)
```bash
# Update to use backup LLM provider
# (Requires pre-configured fallback)
```

**Resolution**:
```bash
# Once OpenAI recovers, re-enable
terminus wp kanopi-2019.live -- option update wp_ai_assistant_settings '{
  "search_enabled": true
}' --format=json

# Verify functionality
curl https://yoursite.com/?s=test
```

#### Incident: Pinecone API Down

**Symptoms**:
- Vector queries failing
- No search results returned
- Error: "Failed to query Pinecone index"

**Investigation**:
```bash
# Check Pinecone status
curl https://status.pinecone.io/api/v2/status.json | jq '.status'

# Test index directly
curl "https://YOUR_INDEX-NAME.svc.YOUR_ENVIRONMENT.pinecone.io/describe_index_stats" \
  -H "Api-Key: $PINECONE_API_KEY"

# Check recent errors
terminus logs kanopi-2019.live --type=php-error | grep "pinecone"
```

**Mitigation**:

**Option 1: Disable AI Search**
```bash
# Temporarily disable AI-powered search
terminus wp kanopi-2019.live -- option update wp_ai_assistant_settings '{
  "search_enabled": false,
  "search_replace_default": false
}' --format=json
```

**Option 2: Use Cached Results** (if caching enabled)
```bash
# Extend cache TTL temporarily
terminus wp kanopi-2019.live -- option update wp_ai_assistant_settings '{
  "cache_ttl": 3600
}' --format=json
```

**Resolution**:
```bash
# Once Pinecone recovers, verify index
curl "https://YOUR_INDEX-NAME.svc.YOUR_ENVIRONMENT.pinecone.io/describe_index_stats" \
  -H "Api-Key: $PINECONE_API_KEY"

# Re-enable AI search
terminus wp kanopi-2019.live -- option update wp_ai_assistant_settings '{
  "search_enabled": true
}' --format=json
```

#### Incident: API Rate Limits Exceeded

**Symptoms**:
- Intermittent failures
- Error: "Rate limit exceeded"
- High request volume

**Investigation**:
```bash
# Check request volume
terminus wp kanopi-2019.live -- db query "
SELECT
  COUNT(*) as total_requests,
  COUNT(*) / 3600 as requests_per_second
FROM wp_ai_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"

# Check OpenAI usage
# Navigate to: https://platform.openai.com/usage

# Check Pinecone usage
# Navigate to: https://app.pinecone.io/
```

**Mitigation**:

**Option 1: Implement Rate Limiting**
```php
// Add to theme's functions.php or custom plugin
add_filter('wp_ai_chatbot_rate_limit', function($limit) {
    return 10; // Max 10 requests per minute per user
});
```

**Option 2: Enable Caching**
```bash
# Enable response caching
terminus wp kanopi-2019.live -- option update wp_ai_assistant_settings '{
  "cache_responses": true,
  "cache_ttl": 300
}' --format=json
```

**Option 3: Upgrade API Plan**
```bash
# Upgrade OpenAI plan
# Navigate to: https://platform.openai.com/account/billing

# Upgrade Pinecone plan
# Navigate to: https://app.pinecone.io/billing
```

**Resolution**:
```bash
# Monitor usage after changes
terminus wp kanopi-2019.live -- db query "
SELECT
  DATE(created_at) as date,
  COUNT(*) as requests,
  AVG(response_time) as avg_ms
FROM wp_ai_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY DATE(created_at)"
```

### Performance Degradation

#### Incident: Slow Response Times

**Symptoms**:
- Response times > 5 seconds
- User complaints about slowness
- Timeout errors

**Investigation**:
```bash
# Check response time trends
terminus wp kanopi-2019.live -- db query "
SELECT
  DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as hour,
  AVG(response_time) as avg_ms,
  MAX(response_time) as max_ms,
  COUNT(*) as total
FROM wp_ai_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY hour
ORDER BY hour DESC"

# Check slow queries
terminus wp kanopi-2019.live -- db query "
SELECT * FROM mysql.slow_log
WHERE db = 'pantheon'
ORDER BY start_time DESC
LIMIT 20"

# Check API latency
curl -w "@curl-format.txt" -o /dev/null -s \
  https://api.openai.com/v1/models \
  -H "Authorization: Bearer $OPENAI_API_KEY"
```

**Mitigation**:

**Option 1: Clear Caches**
```bash
# Clear all caches
terminus env:clear-cache kanopi-2019.live
terminus wp kanopi-2019.live -- cache flush
terminus redis kanopi-2019.live -- clear
```

**Option 2: Optimize Database**
```bash
# Optimize tables
terminus wp kanopi-2019.live -- db optimize

# Clean old logs
terminus wp kanopi-2019.live -- db query "
DELETE FROM wp_ai_chat_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
```

**Option 3: Reduce Top-K Results**
```bash
# Temporarily reduce number of results fetched
terminus wp kanopi-2019.live -- option update wp_ai_assistant_settings '{
  "chatbot_top_k": 3,
  "search_top_k": 5
}' --format=json
```

**Resolution**:
```bash
# Verify performance improvement
terminus wp kanopi-2019.live -- db query "
SELECT AVG(response_time) as avg_ms
FROM wp_ai_chat_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"

# Expected: < 2000ms
```

#### Incident: High CPU Usage

**Symptoms**:
- CPU at 100%
- Site sluggish
- PHP processes timing out

**Investigation**:
```bash
# Check PHP processes
terminus env:info kanopi-2019.live

# Check recent deployment
terminus env:code-log kanopi-2019.live --limit=5

# Check for infinite loops in logs
terminus logs kanopi-2019.live --type=php-error | grep "Maximum execution time"
```

**Mitigation**:

**Option 1: Restart PHP**
```bash
# Wake environment (restarts PHP processes)
terminus env:wake kanopi-2019.live

# Clear caches
terminus env:clear-cache kanopi-2019.live
```

**Option 2: Rollback Recent Changes**
```bash
# If recent deployment caused issue
terminus env:deploy kanopi-2019.live --sync-content=false
```

**Option 3: Disable Resource-Intensive Features**
```bash
# Disable indexer temporarily
terminus wp kanopi-2019.live -- option update wp_ai_assistant_settings '{
  "auto_index_on_save": false
}' --format=json
```

### Security Incidents

#### Incident: Suspicious API Access

**Symptoms**:
- Unusual API request patterns
- Requests from unexpected IPs
- High request volume from single source

**Investigation**:
```bash
# Check access logs
terminus logs kanopi-2019.live --type=nginx-access | grep "wp-json.*ai"

# Analyze request patterns
terminus logs kanopi-2019.live --type=nginx-access --since="1 hour ago" | \
  grep "wp-json.*ai" | \
  awk '{print $1}' | \
  sort | uniq -c | sort -nr | head -10

# Check for brute force attempts
terminus logs kanopi-2019.live --type=nginx-access | grep "401"
```

**Mitigation**:

**Option 1: Block Malicious IPs**
```bash
# Add IP to blocklist via Pantheon firewall
# Or use WordPress security plugin

# Example: Wordfence
terminus wp kanopi-2019.live -- wordfence block-ip 1.2.3.4
```

**Option 2: Implement Rate Limiting**
```php
// Add to theme's functions.php
add_filter('wp_ai_assistant_rate_limit', function($limit, $ip) {
    // More aggressive rate limiting
    return 5; // Max 5 requests per minute
}, 10, 2);
```

**Option 3: Require Authentication**
```bash
# Require authentication for API endpoints
# Update .htaccess or wp-config.php

# Force authentication for REST API
terminus wp kanopi-2019.live -- option update wp_ai_assistant_settings '{
  "require_authentication": true
}' --format=json
```

**Resolution**:
```bash
# Monitor for continued suspicious activity
terminus logs kanopi-2019.live --type=nginx-access --since="15 minutes ago" | \
  grep "wp-json.*ai" | wc -l

# Review and adjust security measures
```

#### Incident: Data Exposure

**Symptoms**:
- PII visible in logs
- Sensitive data in error messages
- Database credentials leaked

**Investigation**:
```bash
# Search logs for PII
terminus logs kanopi-2019.live --type=php-error | grep -E "email|password|api_key"

# Check debug settings
terminus wp kanopi-2019.live -- config get WP_DEBUG
terminus wp kanopi-2019.live -- config get WP_DEBUG_DISPLAY
```

**Immediate Actions**:

1. **Disable Debug Mode**
```bash
terminus wp kanopi-2019.live -- config set WP_DEBUG false --type=constant
terminus wp kanopi-2019.live -- config set WP_DEBUG_DISPLAY false --type=constant
```

2. **Rotate Exposed Credentials**
```bash
# Rotate API keys immediately
# See API Key Management in RUNBOOK.md

# Generate new OpenAI key
terminus secret:set kanopi-2019.live OPENAI_API_KEY "new-key"

# Generate new Pinecone key
terminus secret:set kanopi-2019.live PINECONE_API_KEY "new-key"
```

3. **Clean Logs**
```bash
# Remove sensitive data from logs
terminus wp kanopi-2019.live -- db query "
UPDATE wp_ai_chat_logs
SET question = '[REDACTED]', answer = '[REDACTED]'
WHERE question LIKE '%password%' OR answer LIKE '%api%key%'"
```

4. **Notify Security Team**
```bash
# Send notification
# Include: What was exposed, when, for how long, remediation steps
```

### Data Integrity Issues

#### Incident: Index Out of Sync

**Symptoms**:
- Search returns outdated results
- New content not appearing in search
- Deleted content still showing

**Investigation**:
```bash
# Check last indexer run
terminus logs kanopi-2019.live --type=nginx-access | grep "ai-indexer" | tail -5

# Check indexer logs
# Navigate to CircleCI: ai-assistant-indexer workflow

# Check Pinecone vector count
curl "https://YOUR_INDEX-NAME.svc.YOUR_ENVIRONMENT.pinecone.io/describe_index_stats" \
  -H "Api-Key: $PINECONE_API_KEY"

# Compare to WordPress post count
terminus wp kanopi-2019.live -- post list --post_type=post,page --format=count
```

**Mitigation**:

**Option 1: Incremental Reindex**
```bash
# Reindex recent changes only
terminus wp kanopi-2019.live -- ai-indexer index --since="7 days ago"
```

**Option 2: Full Reindex**
```bash
# Full reindex (takes longer)
terminus wp kanopi-2019.live -- ai-indexer index

# Monitor progress
# Check CircleCI logs
```

**Option 3: Clean and Reindex**
```bash
# Delete all vectors and reindex
terminus wp kanopi-2019.live -- ai-indexer delete-all --yes
terminus wp kanopi-2019.live -- ai-indexer index
```

**Resolution**:
```bash
# Verify index count matches
curl "https://YOUR_INDEX-NAME.svc.YOUR_ENVIRONMENT.pinecone.io/describe_index_stats" \
  -H "Api-Key: $PINECONE_API_KEY" | jq '.totalVectorCount'

terminus wp kanopi-2019.live -- post list --post_type=post,page --format=count

# Test search
curl "https://yoursite.com/?s=test"
```

#### Incident: Database Corruption

**Symptoms**:
- Database errors in logs
- Unable to save logs
- Missing data

**Investigation**:
```bash
# Check database status
terminus wp kanopi-2019.live -- db check

# Check table integrity
terminus wp kanopi-2019.live -- db query "CHECK TABLE wp_ai_chat_logs, wp_ai_search_logs"

# Check for corruption
terminus wp kanopi-2019.live -- db query "
SELECT table_name, engine
FROM information_schema.tables
WHERE table_schema = 'pantheon'
AND table_name LIKE 'wp_ai_%'"
```

**Mitigation**:

**Option 1: Repair Tables**
```bash
# Repair corrupted tables
terminus wp kanopi-2019.live -- db repair

# Verify repair
terminus wp kanopi-2019.live -- db check
```

**Option 2: Restore from Backup**
```bash
# List backups
terminus backup:list kanopi-2019.live --element=database

# Restore from latest backup
terminus backup:restore kanopi-2019.live --element=database --yes

# Verify restoration
terminus wp kanopi-2019.live -- db query "SELECT COUNT(*) FROM wp_ai_chat_logs"
```

**Resolution**:
```bash
# Verify tables are healthy
terminus wp kanopi-2019.live -- db query "
SHOW TABLE STATUS
WHERE Name LIKE 'wp_ai_%'"

# Test functionality
terminus wp kanopi-2019.live -- db query "
INSERT INTO wp_ai_chat_logs (question, answer, created_at)
VALUES ('test', 'test', NOW())"
```

## Escalation Procedures

### When to Escalate

Escalate to senior engineer when:

- P1 incident not resolved within 30 minutes
- P2 incident not resolved within 2 hours
- Root cause unclear after initial investigation
- Fix requires architectural changes
- Multiple systems affected
- Security incident with potential data breach

### Escalation Process

1. **Notify Senior Engineer**
```
Subject: [P1] WP AI Assistant - [Brief Description]

Incident: [Description]
Severity: P1
Started: [Timestamp]
Duration: 30 minutes
Actions Taken:
- [Action 1]
- [Action 2]
- [Action 3]

Current Status: [Status]
Need: [What you need help with]

Incident Log: [Link to incident document]
```

2. **Handoff Details**
- Current incident status
- Actions already taken
- What has been ruled out
- Next steps planned
- Access credentials needed

3. **Stay Available**
- Remain available to assist
- Provide context as needed
- Document handoff in incident log

### Contact Information

**Primary On-Call**: Check PagerDuty schedule
**Secondary On-Call**: Check PagerDuty schedule
**Senior Engineer**: [Contact info]
**DevOps Lead**: [Contact info]
**Security Team**: security@company.com
**Pantheon Support**: support@pantheon.io (for platform issues)

## Post-Incident Review

### Review Timeline

- **Within 24 hours**: Draft initial incident report
- **Within 48 hours**: Conduct PIR meeting with team
- **Within 1 week**: Complete action items and close incident

### Incident Report Template

```markdown
# Post-Incident Report: [Incident Title]

## Summary

**Incident ID**: INC-YYYY-MM-DD-NNN
**Severity**: P1/P2/P3/P4
**Duration**: [Start time] to [End time] ([Total duration])
**Impact**: [Number of users affected, % of requests]
**On-Call Engineer**: [Name]

## Timeline

| Time | Event |
|------|-------|
| 14:23 | Alert triggered |
| 14:25 | On-call engineer acknowledged |
| 14:30 | Root cause identified |
| 14:45 | Mitigation applied |
| 15:10 | Service restored |
| 15:30 | Permanent fix deployed |

## Root Cause

[Detailed explanation of what caused the incident]

## Impact

- Users affected: [Number/percentage]
- Duration: [Time]
- Revenue impact: [If applicable]
- Data loss: [If any]

## Resolution

[Explanation of how the incident was resolved]

## What Went Well

- Quick detection through monitoring
- Effective communication with team
- Successful rollback procedure

## What Could Be Improved

- Need better alerting for this scenario
- Documentation was outdated
- Unclear escalation path

## Action Items

| Action | Owner | Due Date | Status |
|--------|-------|----------|--------|
| Update monitoring alerts | DevOps | 2026-02-01 | Open |
| Improve documentation | Engineer | 2026-02-05 | Open |
| Add integration tests | QA | 2026-02-10 | Open |

## Lessons Learned

[Key takeaways and learnings from this incident]
```

### PIR Meeting Agenda

1. **Review Timeline** (10 min)
   - Walk through incident timeline
   - Clarify any questions

2. **Discuss Root Cause** (15 min)
   - Why did this happen?
   - Could we have prevented it?

3. **Evaluate Response** (15 min)
   - What went well?
   - What could be improved?
   - Were procedures followed?

4. **Action Items** (15 min)
   - What changes will prevent recurrence?
   - Who owns each action?
   - Set due dates

5. **Closing** (5 min)
   - Summary of next steps
   - Schedule follow-up

### Follow-Up Actions

- Implement monitoring improvements
- Update runbooks and procedures
- Conduct training if needed
- Share learnings with broader team
- Close incident ticket

## Communication Templates

### Incident Notification (Initial)

```
🚨 Incident Alert - WP AI Assistant

Severity: [P1/P2/P3/P4]
Status: Investigating
Started: [Timestamp]

Description:
[Brief description of the issue]

Impact:
[Who/what is affected]

Current Actions:
[What we're doing to resolve]

Next Update: 15 minutes

Incident Commander: [Name]
Slack: #incidents
```

### Status Update

```
📊 Incident Update - WP AI Assistant

Status: [Investigating/Mitigating/Resolved]
Duration: [Time since start]

Progress:
- [Update 1]
- [Update 2]

Next Steps:
- [Action 1]
- [Action 2]

ETA: [Estimated resolution time or "unknown"]
Next Update: [Time]
```

### Resolution Notification

```
✅ Incident Resolved - WP AI Assistant

Status: Resolved
Duration: [Total time]
Resolution: [Brief description of fix]

Impact:
[Summary of impact]

Root Cause:
[Brief explanation]

Follow-Up:
- Post-incident review scheduled for [date/time]
- Action items tracked in [ticket system]

Thanks to [team members] for quick response!
```

### Stakeholder Communication

```
Subject: [Status] AI Assistant Service Incident - [Date]

Dear [Stakeholder],

This email is to inform you about a service incident affecting the
WP AI Assistant plugin.

SUMMARY
-------
Severity: [P1/P2]
Status: [Investigating/Resolved]
Started: [Timestamp]
Impact: [Description of user impact]

DETAILS
-------
[More detailed explanation appropriate for stakeholder]

CURRENT STATUS
--------------
[What we're doing to resolve]

NEXT STEPS
----------
[What happens next]

We will provide updates every [frequency] until resolved.

For questions, please contact [contact info].

Thank you,
[Your name]
```

## Related Documentation

- [DEPLOYMENT.md](DEPLOYMENT.md) - Deployment procedures
- [RUNBOOK.md](RUNBOOK.md) - Daily operations and maintenance
- [DISASTER-RECOVERY.md](DISASTER-RECOVERY.md) - Disaster recovery plan
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Troubleshooting guide

## Revision History

| Date | Version | Author | Changes |
|------|---------|--------|---------|
| 2026-01-28 | 1.0.0 | System | Initial incident response guide |
