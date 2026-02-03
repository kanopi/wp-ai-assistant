# WP AI Assistant CircleCI Scripts

CI/CD workflow scripts for the WP AI Assistant WordPress plugin.

## Scripts

### run-indexer.sh
Orchestrates the complete AI indexer workflow including:
- Node.js setup
- Terminus authentication
- WordPress plugin activation
- Environment variable validation
- Content change detection
- Indexer execution with metrics

Usage:
```bash
./run-indexer.sh --site-id SITE_ID --indexer-path PATH [OPTIONS]
```

Options:
- `--site-id SITE_ID` - Terminus site ID
- `--indexer-path PATH` - Path to indexer package
- `--branch BRANCH` - Git branch (auto-detected)
- `--env-url URL` - Environment URL (auto-detected)
- `--force` - Force full reindex
- `--skip-change-detection` - Always run indexer
- `--dry-run` - Show what would be done

### test-plugin.sh
Runs PHPUnit tests for WordPress plugins with Composer dependency management.

Usage:
```bash
./test-plugin.sh [--acf-auth] [--yoast-auth] [--phpunit-options "OPTIONS"]
```

### security-audit-composer.sh
Runs Composer security audits with support for private repositories.

Usage:
```bash
./security-audit-composer.sh [--acf-auth] [--yoast-auth] [--fail-on-vulnerabilities]
```

### notify-slack.sh
Sends formatted Slack notifications for deployment and indexer events.

Usage:
```bash
./notify-slack.sh --type TYPE --channel CHANNEL --url URL [OPTIONS]
```

Types:
- `deployment` - Deployment success notifications
- `indexer-success` - Indexer completion with metrics
- `indexer-error` - Indexer failure with troubleshooting

### health-check.sh
Runs post-deployment health checks on Pantheon environments.

Usage:
```bash
./health-check.sh [OPTIONS]
```

### rollback.sh
Handles automated rollback on Pantheon environments.

Usage:
```bash
./rollback.sh [OPTIONS]
```

## Integration

For complete integration examples and environment variable setup, see:
- `CIRCLECI.md` in this plugin directory
- `.circleci/INTEGRATION.md` in the project root
