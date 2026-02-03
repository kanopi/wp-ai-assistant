# WP AI Assistant - CircleCI Integration

This document covers WP AI Assistant plugin-specific CircleCI setup. For comprehensive script documentation and integration examples, see [`.circleci/INTEGRATION.md`](../../../../.circleci/INTEGRATION.md).

## Quick Links

- **[Full Integration Guide](../../../../.circleci/INTEGRATION.md)** - Complete CircleCI script reference
- **[Script Reference](../../../../.circleci/INTEGRATION.md#script-reference)** - Detailed script documentation
- **[Troubleshooting](../../../../.circleci/INTEGRATION.md#troubleshooting)** - Common issues and solutions

## Overview

The WP AI Assistant plugin integrates with CircleCI to automatically index WordPress content after deployment to Pantheon environments. The indexer runs externally via Node.js and communicates with WordPress through the REST API.

### Script Organization

All plugin-specific CI/CD scripts are located in `.circleci/` within this plugin directory:

- `run-indexer.sh` - Execute AI Assistant indexer with metrics
- `test-plugin.sh` - Run WordPress plugin PHPUnit tests
- `security-audit-composer.sh` - Composer security audits
- `notify-slack.sh` - Send Slack notifications
- `health-check.sh` - Post-deployment health checks
- `rollback.sh` - Automated rollback functionality
- `README.md` - Script documentation

This organization allows the plugin to be distributed independently with its complete CI/CD workflow included.

### How It Works

1. **Deploy** - Code is pushed and deployed to Pantheon multidev
2. **Trigger** - CircleCI TEST stage pipeline is triggered
3. **Index** - Indexer fetches content via WordPress REST API
4. **Store** - Content embeddings are stored in Pinecone
5. **Notify** - Slack notification sent with results

### Environment Mapping

- `main` branch → `dev` environment
- Feature branches → Corresponding multidev (e.g., `feature/search` → multidev)
- PR branches → PR-based multidev (e.g., `pr-809`)

## Plugin Setup

### 1. WordPress Configuration

In WordPress admin for each environment:

1. Go to **Settings → AI Assistant**
2. Configure:
   - **Pinecone Index Host** (e.g., `your-index-123.pinecone.io`)
   - **Pinecone Index Name** (e.g., `wp-content`)
   - **Post Types** to index (posts, pages, custom post types)
   - **Chunk Size** (default: 1000 characters)
   - **Embedding Model** (default: text-embedding-3-small)
3. **Activate the plugin** if not already active

### 2. Create WordPress Application Password

Application Passwords are required for external REST API authentication:

1. Log into WordPress admin
2. Go to **Users → Your Profile**
3. Scroll to **Application Passwords** section
4. Enter name: "CircleCI Indexer"
5. Click **Add New Application Password**
6. Copy the generated password (format: `xxxx xxxx xxxx xxxx xxxx xxxx`)
7. Add to CircleCI context as `WP_API_PASSWORD`

**Important**: Use the Application Password exactly as shown (with or without spaces, depending on your implementation). Do NOT use your regular WordPress password.

### 3. CircleCI Environment Variables

Add these to your CircleCI context (e.g., `kanopi-code`):

**Required Variables:**
```bash
# WordPress API Authentication
WP_API_USERNAME=your-wp-username
WP_API_PASSWORD=xxxx xxxx xxxx xxxx xxxx xxxx  # Application Password

# AI Service Keys
OPENAI_API_KEY=sk-proj-...
PINECONE_API_KEY=pcsk_...

# Pantheon
TERMINUS_TOKEN=your-machine-token
TERMINUS_SITE=your-site-name
```

**Optional Variables:**
```bash
# Override WordPress settings if needed
PINECONE_INDEX_HOST=your-index-123.pinecone.io
PINECONE_INDEX_NAME=your-index-name

# Force full reindex
FORCE_FULL_REINDEX=true

# Slack Notifications
SLACK_WEBHOOK=https://hooks.slack.com/services/...
SLACK_CHANNEL=C12345678
```

For detailed environment variable setup, see [INTEGRATION.md - Environment Variable Setup](../../../../.circleci/INTEGRATION.md#environment-variable-setup).

## CircleCI Configuration

### Minimal Setup

Add to `.circleci/config.yml`:

```yaml
version: 2.1

jobs:
  run-indexer:
    docker:
      - image: quay.io/pantheon-public/build-tools-ci:8.x-php8.2
    steps:
      - checkout
      - run:
          name: Run AI Assistant Indexer
          command: |
            web/wp-content/plugins/wp-ai-assistant/.circleci/run-indexer.sh \
              --site-id "${TERMINUS_SITE}" \
              --indexer-path "packages/wp-ai-indexer"
          no_output_timeout: 30m

workflows:
  ai-indexer:
    jobs:
      - run-indexer:
          context: your-context-name
          filters:
            branches:
              only: /.*/
```

### With Slack Notifications

```yaml
jobs:
  run-indexer:
    docker:
      - image: quay.io/pantheon-public/build-tools-ci:8.x-php8.2
    steps:
      - checkout
      - run:
          name: Run Indexer
          command: web/wp-content/plugins/wp-ai-assistant/.circleci/run-indexer.sh
          no_output_timeout: 30m
      - run:
          name: Notify Success
          when: on_success
          command: |
            if [[ "${INDEXER_SUCCESS}" != "skipped" ]]; then
              web/wp-content/plugins/wp-ai-assistant/.circleci/notify-slack.sh \
                --type indexer-success \
                --channel "${SLACK_CHANNEL}" \
                --url "${ENV_URL}" \
                --posts "${POSTS_INDEXED}" \
                --duration "${INDEXER_DURATION}" \
                --errors "${INDEXER_ERRORS}"
            fi
      - run:
          name: Notify Failure
          when: on_fail
          command: |
            web/wp-content/plugins/wp-ai-assistant/.circleci/notify-slack.sh \
              --type indexer-error \
              --channel "${SLACK_CHANNEL}" \
              --url "${ENV_URL}"
```

For complete CircleCI job examples, see [INTEGRATION.md - CircleCI Job Examples](../../../../.circleci/INTEGRATION.md#circleci-job-examples).

## Shared Indexer Package

WP AI Assistant uses the shared `@kanopi/wp-ai-indexer` Node.js package:

- **Package Location**: `packages/wp-ai-indexer/`
- **Settings Endpoint**: `/wp-json/ai-assistant/v1/indexer-settings`
- **Repository**: Internal monorepo package

The indexer:
1. Fetches configuration from WordPress REST API
2. Retrieves content to index
3. Generates embeddings via OpenAI
4. Stores vectors in Pinecone with metadata
5. Supports multi-environment filtering via domain metadata

## Manual Indexer Execution

### Via WP-CLI (on Pantheon)

```bash
# SSH into environment
terminus ssh your-site.dev

# Run indexer
wp ai-indexer index
```

### Via Node Package (locally or DDEV)

```bash
# Set environment variables
export WP_API_BASE="https://your-site.ddev.site"
export WP_API_USERNAME="your_username"
export WP_API_PASSWORD="your application password"
export OPENAI_API_KEY="sk-..."
export PINECONE_API_KEY="pcsk_..."

# Run indexer
cd packages/wp-ai-indexer
npx wp-ai-indexer index
```

### In DDEV

```bash
# With environment variables in .ddev/config.yaml
ddev exec "cd packages/wp-ai-indexer && npx wp-ai-indexer index"
```

**Note**: For DDEV with self-signed certificates, you may need `NODE_TLS_REJECT_UNAUTHORIZED=0`.

## Content Change Detection

The indexer automatically detects when indexing is needed based on:

1. **File Changes**: Changes to theme or plugin PHP files
2. **Plugin Changes**: Updates to WP AI Assistant plugin itself
3. **Main Branch**: Always runs on main branch deployments
4. **Weekly Schedule**: Full reindex every Monday on main branch
5. **Force Flag**: `FORCE_FULL_REINDEX=true` environment variable

To force indexing regardless of changes:

```bash
web/wp-content/plugins/wp-ai-assistant/.circleci/run-indexer.sh --force
```

Or set environment variable:

```yaml
environment:
  FORCE_FULL_REINDEX: "true"
```

## Migration Guide

### From Inline Config to Scripts

If you have inline bash in `.circleci/config.yml`, migrate to scripts:

**Before (inline bash):**
```yaml
- run:
    name: Run Indexer
    command: |
      curl -fsSL https://deb.nodesource.com/setup_22.x | sudo bash -
      sudo apt-get install -y nodejs
      cd packages/wp-ai-indexer
      npm ci
      terminus auth:login --machine-token=${TERMINUS_TOKEN}
      # ... 50+ lines of inline bash ...
```

**After (using scripts):**
```yaml
- run:
    name: Run Indexer
    command: web/wp-content/plugins/wp-ai-assistant/.circleci/run-indexer.sh
    no_output_timeout: 30m
```

**Benefits:**
- Reusable across projects
- Easier to test locally
- Better error handling
- Clear documentation
- Version controlled separately

### Script Versions

Track which script version you're using:

```yaml
# In .circleci/config.yml, add comment
# WP AI Assistant Scripts: v1.0.0
# Last updated: 2024-01-29
```

See [INTEGRATION.md - Version History](../../../../.circleci/INTEGRATION.md#version-history) for script changelog.

## Troubleshooting

### Common Issues

#### Authentication Failed (401)

**Cause**: Invalid Application Password

**Solution**:
1. Generate new Application Password in WordPress
2. Update `WP_API_PASSWORD` in CircleCI context
3. Test manually:
   ```bash
   curl -u "username:app-password" \
     "https://dev-site.pantheonsite.io/wp-json/wp/v2/types"
   ```

#### Indexer Skipped

**Cause**: No content changes detected

**Solution**:
```bash
# Force run
web/wp-content/plugins/wp-ai-assistant/.circleci/run-indexer.sh --force

# Or skip detection
web/wp-content/plugins/wp-ai-assistant/.circleci/run-indexer.sh --skip-change-detection
```

#### Plugin Not Activated

**Cause**: Plugin deactivated in environment

**Solution**:
```bash
# Activate via Terminus
terminus wp site.env -- plugin activate wp-ai-assistant

# Or via WP-CLI
wp plugin activate wp-ai-assistant
```

#### Missing Environment Variables

**Cause**: Variables not set in CircleCI context

**Solution**:
1. Go to CircleCI → Organization Settings → Contexts
2. Open your context (e.g., `kanopi-code`)
3. Verify all required variables exist
4. Check variable names match exactly

For complete troubleshooting guide, see [INTEGRATION.md - Troubleshooting](../../../../.circleci/INTEGRATION.md#troubleshooting).

## Monitoring

### CircleCI Dashboard

1. Go to project pipeline
2. Find the TEST stage
3. Click on indexer workflow
4. View job logs

### Key Log Indicators

Success indicators:
```
✓ OPENAI_API_KEY is set
✓ PINECONE_API_KEY is set
✓ Authentication successful
✓ Content changes detected
✓ Posts indexed: 150
✓ Duration: 5m 32s
✓ Indexer completed successfully
```

Failure indicators:
```
✗ Authentication failed (401 Unauthorized)
✗ OPENAI_API_KEY is not set
✗ Indexer failed after 2m 15s
```

### Slack Notifications

If configured, you'll receive:
- **Success**: Posts indexed, duration, error count
- **Failure**: Error message with troubleshooting tips
- **Skipped**: No notification (no changes detected)

## Best Practices

1. **Use Application Passwords** - Never use regular WordPress passwords
2. **Store Secrets in Context** - Not in config.yml
3. **Monitor First Run** - Watch logs on new environments
4. **Test Locally First** - Verify indexer works before relying on CI
5. **Regular Full Reindex** - Let weekly schedule run on Mondays
6. **Check Logs** - Review indexer output for warnings
7. **Update Scripts** - Keep CircleCI scripts up to date

## Security Considerations

- Application Passwords can be revoked individually
- API keys stored in CircleCI contexts (encrypted)
- Secrets never committed to version control
- Indexer runs externally (not on Pantheon servers)
- REST API authentication required
- Rate limiting applies to OpenAI/Pinecone APIs

## Additional Resources

- **[Full Integration Guide](../../../../.circleci/INTEGRATION.md)** - Complete documentation
- **[run-indexer.sh Reference](../../../../.circleci/INTEGRATION.md#run-indexersh)** - Script options
- **[Environment Variables](../../../../.circleci/INTEGRATION.md#environment-variable-setup)** - Setup guide
- **[Script Customization](../../../../.circleci/INTEGRATION.md#customization-guide)** - Modify for your needs

## Support

For issues or questions:

1. Check script help: `web/wp-content/plugins/wp-ai-assistant/.circleci/run-indexer.sh --help`
2. Use dry-run mode: `web/wp-content/plugins/wp-ai-assistant/.circleci/run-indexer.sh --dry-run`
3. Review [INTEGRATION.md troubleshooting](../../../../.circleci/INTEGRATION.md#troubleshooting)
4. Check CircleCI job logs for specific errors
5. Verify environment variables are accessible

## Script Changelog

### v1.0.0 (2024-01-29)
- Initial release with modular scripts
- Extracted from inline CircleCI configuration
- Added comprehensive error handling
- Added dry-run mode
- Added content change detection
- Added metrics capture and reporting
- Added Slack notification support
