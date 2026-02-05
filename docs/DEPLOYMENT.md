# Semantic Knowledge - Deployment Guide

Complete deployment procedures and best practices for the Semantic Knowledge plugin across all environments.

## Table of Contents

- [Overview](#overview)
- [Pre-Deployment Checklist](#pre-deployment-checklist)
- [Deployment Process](#deployment-process)
  - [Development Environment](#development-environment)
  - [Staging Environment](#staging-environment)
  - [Production Environment](#production-environment)
- [CircleCI Automated Deployment](#circleci-automated-deployment)
- [Post-Deployment Verification](#post-deployment-verification)
- [Rollback Procedures](#rollback-procedures)
- [Zero-Downtime Deployment](#zero-downtime-deployment)
- [Troubleshooting](#troubleshooting)

## Overview

The Semantic Knowledge plugin is deployed via CircleCI to Pantheon hosting environments. The deployment pipeline includes:

- Automated testing (PHPUnit, Jest)
- Security audits (npm audit, composer audit)
- Code quality checks (PHPCS)
- Theme asset compilation
- AI indexer execution
- Visual regression testing (BackstopJS)
- Accessibility testing (Pa11y)
- Performance testing (Lighthouse)

### Deployment Targets

- **Development (dev)**: Main branch → `dev` environment
- **Multidev**: Feature branches → `pr-{number}` multidev environments
- **Production**: Manual promotion from dev/test to `test` and `live`

## Pre-Deployment Checklist

### Before Every Deployment

#### 1. Code Review

- [ ] All pull requests reviewed and approved
- [ ] No merge conflicts
- [ ] All CI checks passing (tests, linting, security)
- [ ] Version numbers updated if needed

#### 2. Configuration

- [ ] Environment variables set in CircleCI:
  - `OPENAI_API_KEY` - OpenAI API key
  - `PINECONE_API_KEY` - Pinecone API key
  - `WP_API_USERNAME` - WordPress application password username
  - `WP_API_PASSWORD` - WordPress application password
  - `TERMINUS_TOKEN` - Pantheon Terminus authentication token
  - `ACF_USERNAME` / `ACF_PROD_URL` - Advanced Custom Fields credentials
  - `YOAST_TOKEN` - Yoast SEO credentials

- [ ] Pantheon Secrets configured (for production):
  ```bash
  # Set Pantheon Secrets
  terminus secret:set kanopi-2019.live OPENAI_API_KEY "sk-..."
  terminus secret:set kanopi-2019.live PINECONE_API_KEY "..."
  ```

#### 3. Dependencies

- [ ] Composer dependencies up to date: `composer update` (if applicable)
- [ ] npm dependencies up to date: `npm update` (if applicable)
- [ ] No known security vulnerabilities: `composer audit`, `npm audit`

#### 4. Database

- [ ] Database backups exist (automatic on Pantheon)
- [ ] No pending migrations (handled by plugin activation)
- [ ] Schema version compatible: `Semantic_Knowledge_ASSISTANT_SCHEMA_VERSION`

#### 5. Plugin Readiness

- [ ] Node.js indexer package built: `cd packages/wp-ai-indexer && npm run build`
- [ ] System requirements met: `wp sk-indexer check`
- [ ] Plugin activated in target environment
- [ ] Settings configured in WordPress admin

#### 6. Content Indexing

- [ ] Pinecone index exists and is configured
- [ ] Index host matches environment: `pinecone_index_host`
- [ ] Content types configured: `post_types` setting
- [ ] Indexer will run automatically after deployment

## Deployment Process

### Development Environment

Automatic deployment to Pantheon dev environment occurs on every push to `main` branch.

#### Step 1: Merge to Main

```bash
# From your feature branch
git checkout main
git pull origin main
git merge feature/your-feature
git push origin main
```

#### Step 2: CircleCI Pipeline

The following workflow executes automatically:

1. **Build Stage** (parallel execution):
   - Run PHPUnit tests (`test-wp-plugin`)
   - Run Jest tests (`test-indexer`)
   - Run npm security audit (`security-audit-npm`)
   - Run composer security audit (`security-audit-composer`)
   - Run PHPCS linting (`phpcs`)
   - Compile theme assets (`compile-theme`)

2. **Deploy Stage** (after build succeeds):
   - Deploy to Pantheon (`pantheon-deploy` job)
   - Install Composer dependencies
   - Push code to Pantheon Git repository
   - Clear Pantheon caches
   - Post Slack notification with deployment URL

3. **Test Stage** (after deploy succeeds):
   - Run BackstopJS visual regression tests (PRs only)
   - Run Pa11y accessibility tests (PRs only)
   - Run Lighthouse performance tests (PRs only)
   - Run AI indexer (`run-indexer` job)

#### Step 3: Monitor CircleCI

```bash
# View CircleCI build status
open https://circleci.com/gh/kanopi/kanopi-2019

# Or use CircleCI CLI
circleci run follow
```

#### Step 4: Verify Deployment

See [Post-Deployment Verification](#post-deployment-verification) section.

### Staging Environment

For testing before production, create a multidev or use the `test` environment:

#### Option 1: Multidev (Feature Branches)

CircleCI automatically creates multidev environments for pull requests:

```bash
# Create PR from feature branch
git checkout -b feature/new-feature
git push origin feature/new-feature
# Open pull request on GitHub
```

Multidev URL: `https://pr-{number}-kanopi-2019.pantheonsite.io`

#### Option 2: Test Environment (Manual)

```bash
# Deploy dev to test
terminus env:deploy kanopi-2019.test --note="Deploy AI Assistant v1.0.0"

# Activate plugin
terminus wp kanopi-2019.test -- plugin activate semantic-knowledge

# Verify system requirements
terminus wp kanopi-2019.test -- ai-indexer check

# Run indexer
terminus wp kanopi-2019.test -- ai-indexer index
```

### Production Environment

Production deployment requires manual promotion from test environment.

#### Step 1: Pre-Production Testing

Complete all testing in the test environment:

- [ ] All features working as expected
- [ ] Search functionality verified
- [ ] Chatbot functionality verified
- [ ] Performance acceptable (Lighthouse scores)
- [ ] Accessibility compliant (Pa11y)
- [ ] No console errors

#### Step 2: Production Deployment

```bash
# Set production to read-only mode (optional)
terminus wp kanopi-2019.live -- maintenance-mode activate

# Backup database (automatic on Pantheon, but verify)
terminus backup:create kanopi-2019.live --element=all

# Deploy test to live
terminus env:deploy kanopi-2019.live \
  --note="Deploy Semantic Knowledge v1.0.0 - [Ticket-123]" \
  --sync-content=false

# Clear all caches
terminus env:clear-cache kanopi-2019.live

# Activate plugin (if not already active)
terminus wp kanopi-2019.live -- plugin activate semantic-knowledge

# Verify system requirements
terminus wp kanopi-2019.live -- ai-indexer check

# Run full content index
terminus wp kanopi-2019.live -- ai-indexer index

# Disable maintenance mode
terminus wp kanopi-2019.live -- maintenance-mode deactivate
```

#### Step 3: Post-Production Verification

See [Post-Deployment Verification](#post-deployment-verification) section.

## CircleCI Automated Deployment

### Pipeline Overview

The CircleCI configuration (`.circleci/config.yml`) defines the deployment workflow:

#### Workflows

1. **test-wp-ai-indexer** - Runs PHPUnit and Jest tests on all branches
2. **phpcs** - Code quality checks, triggers deploy workflow
3. **build-deploy** - Compiles assets and deploys to Pantheon
4. **backstopjs** - Visual regression testing (PRs only)
5. **pa11y** - Accessibility testing (PRs only)
6. **lighthouse** - Performance testing (PRs only)
7. **ai-assistant-indexer** - Runs content indexer after deployment

#### Key Jobs

##### test-wp-plugin

Runs PHPUnit tests for WordPress plugin:

```yaml
test-wp-plugin:
  docker:
    - image: cimg/php:8.2
  steps:
    - checkout
    - restore_cache
    - run: composer install
    - save_cache
    - run: vendor/bin/phpunit
```

##### test-indexer

Runs Jest tests for Node.js indexer:

```yaml
test-indexer:
  docker:
    - image: cimg/node:22.9
  steps:
    - checkout
    - restore_cache
    - run: npm ci
    - save_cache
    - run: npm run test:ci
```

##### pantheon-deploy

Deploys code to Pantheon:

```yaml
pantheon-deploy:
  steps:
    - checkout
    - attach_workspace  # Theme assets
    - run: composer install
    - run: ./.circleci/scripts/pantheon/dev-multidev
    - slack/notify  # Success notification
```

##### run-indexer

Runs AI content indexer:

```yaml
run-indexer:
  steps:
    - checkout
    - run: Install Node.js
    - restore_cache
    - run: npm ci (indexer)
    - run: Authenticate Terminus
    - run: Activate plugin
    - run: Detect content changes
    - run: Run indexer with metrics
    - slack/notify  # Success/failure notification
```

### Indexer Smart Execution

The indexer runs conditionally to avoid unnecessary processing:

#### Triggers

- **Always**: Main branch deployments
- **Conditional**: Content-related file changes detected
- **Weekly**: Full reindex every Monday
- **Manual**: `FORCE_FULL_REINDEX=true` environment variable

#### Content Change Detection

```bash
# Checks for changes in:
- web/wp-content/themes/**/*.php
- web/wp-content/plugins/**/*.php
- web/wp-content/plugins/semantic-knowledge/**

# If no changes detected, indexer is skipped
```

### Slack Notifications

CircleCI posts notifications to Slack on:

- Successful deployment (with URL)
- Indexer success (with metrics)
- Indexer failure (with error details)

Channel: Configured via `SLACK_CHANNEL` variable

## Post-Deployment Verification

### Automated Health Checks

CircleCI performs these checks automatically, but manual verification is recommended for production:

#### 1. Plugin Activation

```bash
# Verify plugin is active
terminus wp kanopi-2019.{env} -- plugin list --status=active --name=semantic-knowledge

# Expected output:
# name               status version
# semantic-knowledge    active 1.0.0
```

#### 2. System Requirements

```bash
# Check Node.js and indexer availability
terminus wp kanopi-2019.{env} -- ai-indexer check

# Expected output:
# ✓ Node.js: 22.9.0
# ✓ Indexer: 1.0.0
# Success: All requirements met!
```

#### 3. Configuration Validation

```bash
# Verify settings
terminus wp kanopi-2019.{env} -- ai-indexer config

# Expected output shows:
# - OpenAI API key configured
# - Pinecone API key configured
# - Index host configured
# - Post types configured
```

#### 4. Database Tables

```bash
# Verify database tables exist
terminus wp kanopi-2019.{env} -- db query \
  "SHOW TABLES LIKE 'semantic_knowledge_%'"

# Expected tables:
# wp_sk_chat_logs
# wp_sk_search_logs
```

#### 5. Indexer Status

```bash
# Check indexer logs
terminus wp kanopi-2019.{env} -- ai-indexer config

# Verify last successful index time
# Check number of vectors in Pinecone index
```

### Manual Smoke Tests

#### Search Functionality

1. Navigate to site homepage
2. Use search form (if search module enabled)
3. Enter test query: "test"
4. Verify AI-powered results appear
5. Check for AI summary (if enabled)
6. Verify result relevance and ranking

#### Chatbot Functionality

1. Navigate to any page
2. Open chatbot (floating button or embedded)
3. Send test message: "What is this website about?"
4. Verify AI response appears
5. Check response quality and relevance
6. Verify source citations appear

#### Performance Checks

```bash
# Check page load time
curl -w "@curl-format.txt" -o /dev/null -s https://yoursite.com

# Check API response times
curl -w "@curl-format.txt" -o /dev/null -s \
  https://yoursite.com/wp-json/wp/v2/ai-indexer/settings
```

#### Error Log Review

```bash
# Check for PHP errors
terminus wp kanopi-2019.{env} -- tail -n 100 wp-content/debug.log | grep "Semantic_Knowledge_ASSISTANT"

# Check for fatal errors
terminus logs kanopi-2019.{env} --type=php-error
```

### Monitoring Dashboards

#### Pantheon Dashboard

- Site status: https://dashboard.pantheon.io/sites/{site-id}
- Uptime monitoring
- Performance metrics
- Error logs

#### Application Performance

- New Relic (if configured)
- Response time metrics
- Error rate tracking
- Database query performance

## Rollback Procedures

### When to Rollback

- **Critical bugs** - Plugin causes site errors or crashes
- **Performance issues** - Significant slowdown or timeouts
- **Data loss** - Content or settings corrupted
- **API failures** - OpenAI or Pinecone unavailable
- **Security issues** - Vulnerability discovered

### Emergency Rollback (Quick)

#### Option 1: Deactivate Plugin

Fastest method - immediately disables plugin:

```bash
# Via Terminus
terminus wp kanopi-2019.{env} -- plugin deactivate semantic-knowledge

# Via WordPress admin
# Navigate to: Plugins → Deactivate Semantic Knowledge
```

This removes all plugin functionality but preserves:
- Database tables
- Settings
- Log data

#### Option 2: Pantheon Code Rollback

Revert to previous code deployment:

```bash
# List recent deployments
terminus env:code-log kanopi-2019.{env}

# Rollback to previous commit
terminus env:deploy kanopi-2019.{env} \
  --sync-content=false \
  --note="Emergency rollback - reverting to {commit-hash}"

# Clear caches
terminus env:clear-cache kanopi-2019.{env}
```

### Full Rollback (Complete)

For complete rollback including database changes:

#### Step 1: Restore Code

```bash
# Rollback code to previous version
terminus env:deploy kanopi-2019.{env} \
  --sync-content=false \
  --note="Full rollback to pre-deployment state"
```

#### Step 2: Restore Database

```bash
# List available backups
terminus backup:list kanopi-2019.{env}

# Restore database from backup
terminus backup:restore kanopi-2019.{env} \
  --element=database \
  --yes
```

#### Step 3: Verify Rollback

```bash
# Check plugin version
terminus wp kanopi-2019.{env} -- plugin list --name=semantic-knowledge

# Verify site functionality
curl -I https://{env}-kanopi-2019.pantheonsite.io

# Check for errors
terminus logs kanopi-2019.{env} --type=php-error
```

### Post-Rollback Actions

1. **Notify Team**: Alert stakeholders about rollback
2. **Document Cause**: Record reason for rollback
3. **Create Fix**: Develop and test fix in development
4. **Plan Redeployment**: Schedule fix deployment

### Rollback Testing

Periodically test rollback procedures in development:

```bash
# Create backup
terminus backup:create kanopi-2019.dev --element=all

# Deploy new version
git push origin main

# Test rollback
terminus backup:restore kanopi-2019.dev --element=code --yes

# Verify restoration
terminus wp kanopi-2019.dev -- plugin list
```

## Zero-Downtime Deployment

### Strategy

Pantheon provides zero-downtime deployments through:

1. **Code deployment** - Code pushed to app servers without restart
2. **Redis cache** - Object cache maintains availability during deployment
3. **Varnish cache** - Edge cache serves cached pages during deployment

### Best Practices

#### 1. Backwards-Compatible Changes

Always ensure new code is compatible with old database schema:

```php
// Good: Check for column existence
if ( $wpdb->get_var( "SHOW COLUMNS FROM {$table} LIKE 'new_column'" ) ) {
    // Use new column
}

// Bad: Assume column exists
$wpdb->query( "SELECT new_column FROM {$table}" );
```

#### 2. Database Migrations

Run migrations during off-peak hours:

```bash
# Schedule for low-traffic time
terminus wp kanopi-2019.{env} -- cron event schedule wp_ai_migration 02:00
```

#### 3. Feature Flags

Use feature flags for gradual rollout:

```php
// Enable new feature for admins only
if ( current_user_can( 'manage_options' ) || get_option( 'semantic_knowledge_new_feature_enabled' ) ) {
    // New feature code
}
```

#### 4. Cache Warming

Warm caches after deployment:

```bash
# Clear caches
terminus env:clear-cache kanopi-2019.{env}

# Warm critical pages
curl https://yoursite.com/
curl https://yoursite.com/search/
curl https://yoursite.com/wp-json/wp/v2/ai-indexer/settings
```

### Deployment Windows

#### Production

- **Preferred**: Tuesday-Thursday, 9am-3pm EST
- **Avoid**: Friday afternoons, weekends, holidays
- **Maintenance**: Schedule during off-peak hours

#### Emergency Deployments

For critical security fixes or outages:

1. Follow emergency change process
2. Notify team via Slack
3. Document in incident log
4. Deploy immediately
5. Monitor closely for 30 minutes

## Troubleshooting

### Common Deployment Issues

#### Build Failures

**Symptom**: CircleCI build fails

**Causes**:
- Test failures
- Linting errors
- Security vulnerabilities
- Composer/npm dependency conflicts

**Resolution**:
```bash
# Run tests locally
cd web/wp-content/plugins/semantic-knowledge
vendor/bin/phpunit

# Check linting
composer run phpcs

# Check security
composer audit
npm audit

# Fix and push
git add .
git commit -m "Fix build errors"
git push
```

#### Deployment Timeouts

**Symptom**: CircleCI job times out during deployment

**Causes**:
- Large file uploads
- Slow Composer installs
- Network issues

**Resolution**:
```yaml
# Increase timeout in .circleci/config.yml
- run:
    name: Deploy to Pantheon
    command: ./.circleci/scripts/pantheon/dev-multidev
    no_output_timeout: 30m  # Increase from default
```

#### Indexer Failures

**Symptom**: AI indexer fails during deployment

**Causes**:
- Missing API keys
- Pinecone index not configured
- Node.js not available
- WordPress not responding

**Resolution**:
```bash
# Check environment variables
terminus env:info kanopi-2019.{env}

# Verify plugin activation
terminus wp kanopi-2019.{env} -- plugin activate semantic-knowledge

# Test manually
terminus wp kanopi-2019.{env} -- ai-indexer check
terminus wp kanopi-2019.{env} -- ai-indexer index --debug
```

#### Cache Issues

**Symptom**: Changes not visible after deployment

**Causes**:
- Varnish cache not cleared
- Redis cache stale
- Browser cache

**Resolution**:
```bash
# Clear all Pantheon caches
terminus env:clear-cache kanopi-2019.{env}

# Clear WordPress object cache
terminus wp kanopi-2019.{env} -- cache flush

# Test with cache bypass
curl -H "Cache-Control: no-cache" https://yoursite.com
```

### Emergency Contacts

- **Primary On-Call**: Check PagerDuty schedule
- **Secondary On-Call**: Backup engineer
- **Pantheon Support**: support@pantheon.io (for platform issues)
- **Slack Channel**: #deployments

## Related Documentation

- [RUNBOOK.md](RUNBOOK.md) - Daily operations and maintenance tasks
- [INCIDENT-RESPONSE.md](INCIDENT-RESPONSE.md) - Incident response procedures
- [DISASTER-RECOVERY.md](DISASTER-RECOVERY.md) - Disaster recovery plan
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - General troubleshooting guide
- [CIRCLECI.md](../CIRCLECI.md) - CircleCI configuration details

## Revision History

| Date | Version | Author | Changes |
|------|---------|--------|---------|
| 2026-01-28 | 1.0.0 | System | Initial deployment guide |
