# Local Development Guide

Complete guide for setting up and developing the WP AI Assistant plugin locally.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Running Tests](#running-tests)
- [Debugging Techniques](#debugging-techniques)
- [Common Issues](#common-issues)
- [Development Workflow](#development-workflow)
- [Database Management](#database-management)
- [Cache Management](#cache-management)

## Prerequisites

### Required Software

#### PHP 8.0+

**Check version**:
```bash
php --version
# Should show: PHP 8.0.0 or higher
```

**Install** (if needed):
- **macOS**: `brew install php@8.2`
- **Ubuntu**: `sudo apt install php8.2 php8.2-cli php8.2-mysql php8.2-mbstring php8.2-xml`
- **Windows**: Download from [php.net](https://www.php.net/downloads)

**Required PHP Extensions**:
- `curl` - HTTP requests to OpenAI and Pinecone
- `json` - JSON encoding/decoding
- `mbstring` - Multi-byte string functions
- `mysqli` or `pdo_mysql` - Database access

**Verify extensions**:
```bash
php -m | grep -E '(curl|json|mbstring|mysqli)'
```

#### Composer 2.x

**Check version**:
```bash
composer --version
# Should show: Composer version 2.x
```

**Install**:
```bash
# macOS
brew install composer

# Linux
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Windows
# Download from: https://getcomposer.org/download/
```

#### Node.js 18.x+

**Check version**:
```bash
node --version
# Should show: v18.0.0 or higher

npm --version
# Should show: 8.0.0 or higher
```

**Install**:
```bash
# Using nvm (recommended)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
nvm install 18
nvm use 18

# macOS (without nvm)
brew install node@18

# Ubuntu
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Windows
# Download from: https://nodejs.org/
```

#### WordPress 5.6+

**For local development, use**:
- **DDEV** (recommended) - Pre-configured WordPress environment
- **Local by Flywheel** - GUI-based WordPress development
- **Docker** - Manual WordPress + MySQL setup
- **XAMPP/MAMP** - Traditional LAMP/LEMP stack

### Recommended Software

#### DDEV (Recommended)

**Why DDEV?**
- Pre-configured WordPress environment
- Built-in WP-CLI support
- Easy database management
- Redis support for object caching
- Mailhog for email testing

**Install DDEV**:
```bash
# macOS
brew install ddev/ddev/ddev

# Linux
curl -fsSL https://apt.fury.io/drud/gpg.key | gpg --dearmor | sudo tee /etc/apt/trusted.gpg.d/ddev.gpg > /dev/null
echo "deb [signed-by=/etc/apt/trusted.gpg.d/ddev.gpg] https://apt.fury.io/drud/ * *" | sudo tee /etc/apt/sources.list.d/ddev.list
sudo apt update && sudo apt install -y ddev

# Windows
choco install ddev
```

**Verify installation**:
```bash
ddev version
```

#### Git

**Check version**:
```bash
git --version
```

**Install**:
```bash
# macOS
brew install git

# Ubuntu
sudo apt install git

# Windows
# Download from: https://git-scm.com/downloads
```

#### VS Code (Optional but Recommended)

**Recommended extensions**:
- PHP Intelephense
- WordPress Toolbox
- ESLint
- Prettier
- GitLens

**Install extensions**:
```bash
code --install-extension bmewburn.vscode-intelephense-client
code --install-extension wordpresstoolbox.wordpress-toolbox
code --install-extension dbaeumer.vscode-eslint
code --install-extension esbenp.prettier-vscode
```

## Installation

### Option 1: DDEV (Recommended)

#### 1. Clone or Navigate to Project

```bash
# If starting fresh
git clone https://github.com/kanopi/kanopi-2019.git
cd kanopi-2019

# If already cloned
cd path/to/kanopi-2019
```

#### 2. Start DDEV

```bash
# Initialize DDEV (if first time)
ddev config --project-type=wordpress

# Start environment
ddev start

# Install WordPress (if fresh install)
ddev wp core install \
  --url=https://kanopi-2019.ddev.site \
  --title="Dev Site" \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=admin@example.com
```

#### 3. Install Plugin Dependencies

```bash
# Install PHP dependencies
ddev composer install --working-dir=web/wp-content/plugins/wp-ai-assistant

# Build indexer package (monorepo)
ddev exec "cd packages/wp-ai-indexer && npm install && npm run build"

# Activate plugin
ddev wp plugin activate wp-ai-assistant
```

#### 4. Configure Environment Variables

Edit `.ddev/config.yaml`:

```yaml
name: kanopi-2019
type: wordpress
docroot: web
php_version: "8.2"
webserver_type: nginx-fpm
router_http_port: "80"
router_https_port: "443"
xdebug_enabled: false
additional_hostnames: []
additional_fqdns: []
database:
  type: mariadb
  version: "10.4"
use_dns_when_possible: true
composer_version: "2"
web_environment:
  - OPENAI_API_KEY=sk-your-key-here
  - PINECONE_API_KEY=your-key-here
  - WP_AI_INDEXER_KEY=your-secure-random-key-here
  - WP_AI_ENABLE_CSP=false  # Optional: Enable CSP for testing
```

**Generate secure indexer key**:
```bash
# macOS/Linux
openssl rand -hex 32

# Or use PHP
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

#### 5. Restart DDEV

```bash
ddev restart
```

#### 6. Configure Plugin Settings

```bash
# Via WP-CLI
ddev wp option update wp_ai_assistant_settings '
{
  "pinecone_index_host": "https://your-index-abc123.svc.pinecone.io",
  "pinecone_index_name": "your-index",
  "embedding_model": "text-embedding-3-small",
  "embedding_dimension": 1536,
  "chatbot_enabled": true,
  "search_enabled": true
}' --format=json

# Or via WordPress admin
# Navigate to: https://kanopi-2019.ddev.site/wp-admin
# Go to: Settings > AI Assistant
```

#### 7. Index Sample Content

```bash
# Check system status
ddev wp ai-indexer check

# Index all content
ddev wp ai-indexer index --debug

# Verify indexing
ddev wp ai-indexer config
```

#### 8. Verify Installation

Visit in browser:
- **WordPress Admin**: https://kanopi-2019.ddev.site/wp-admin
  - Username: `admin`
  - Password: `admin`
- **Frontend**: https://kanopi-2019.ddev.site

Test chatbot:
- Look for floating chat button
- Or add shortcode: `[ai_chatbot]`

Test search:
- Add shortcode: `[ai_search]`
- Or visit WordPress search

### Option 2: Standalone WordPress (Without DDEV)

#### 1. Navigate to Plugin Directory

```bash
cd path/to/wordpress/wp-content/plugins/wp-ai-assistant
```

#### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install indexer (local)
cd indexer
npm install
cd ..
```

#### 3. Activate Plugin

```bash
# Via WP-CLI
wp plugin activate wp-ai-assistant

# Or via WordPress admin
```

#### 4. Configure API Keys

**Option A: Environment variables** (recommended)

Add to `.env` file or shell:
```bash
export OPENAI_API_KEY="sk-your-key-here"
export PINECONE_API_KEY="your-key-here"
export WP_AI_INDEXER_KEY="your-secure-key-here"
```

**Option B: wp-config.php**

```php
// Add to wp-config.php (before "That's all, stop editing!")
define('OPENAI_API_KEY', 'sk-your-key-here');
define('PINECONE_API_KEY', 'your-key-here');
define('WP_AI_INDEXER_KEY', 'your-secure-key-here');
```

#### 5. Configure Plugin Settings

Same as DDEV Option 1, Step 6

#### 6. Index Content

```bash
# Check system
wp ai-indexer check

# Index content
wp ai-indexer index

# Or use global indexer (if installed globally)
npm install -g @kanopi/wp-ai-indexer
wp-ai-indexer index
```

## Configuration

### Plugin Settings

#### Via WordPress Admin

1. Navigate to **Settings > AI Assistant**

2. **General Settings**:
   - Pinecone Index Host: `https://your-index-abc123.svc.pinecone.io`
   - Pinecone Index Name: `your-index`
   - Embedding Model: `text-embedding-3-small`
   - Embedding Dimension: `1536`

3. **Chatbot Settings**:
   - Enable Chatbot: ✓
   - Model: `gpt-4o-mini`
   - Temperature: `0.2`
   - Top K: `5`
   - System Prompt: (customize as needed)

4. **Search Settings**:
   - Enable Search: ✓
   - Top K: `10`
   - Minimum Score: `0.5`
   - Enable Summary: ✓
   - Relevance Boosting: ✓

#### Via WP-CLI

```bash
# Get current settings
ddev wp option get wp_ai_assistant_settings --format=json | jq

# Update specific setting
ddev wp option patch update wp_ai_assistant_settings chatbot_enabled true

# Update multiple settings
ddev wp option update wp_ai_assistant_settings '
{
  "chatbot_enabled": true,
  "chatbot_model": "gpt-4o-mini",
  "chatbot_temperature": 0.2,
  "search_enabled": true,
  "search_top_k": 10
}' --format=json
```

### WordPress Debug Mode

Enable debugging in `wp-config.php`:

```php
// Enable debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Optional: Enable script debugging
define('SCRIPT_DEBUG', true);

// Optional: Save all database queries
define('SAVEQUERIES', true);
```

**View debug log**:
```bash
# DDEV
ddev logs

# Standalone
tail -f wp-content/debug.log
```

### Redis Object Cache (Optional but Recommended)

#### Enable Redis in DDEV

1. **Edit `.ddev/config.yaml`**:
```yaml
services:
  redis:
    type: redis
    version: "7"
```

2. **Restart DDEV**:
```bash
ddev restart
```

3. **Install Redis Object Cache plugin**:
```bash
ddev composer require wpackagist-plugin/redis-cache
ddev wp plugin activate redis-cache
ddev wp redis enable
```

4. **Verify Redis**:
```bash
ddev wp redis status
# Should show: "Status: Connected"
```

**Benefits**:
- 80% reduction in API calls (due to caching)
- Faster response times
- Realistic production environment

## Running Tests

### Setup Test Environment

#### 1. Install Test Dependencies

```bash
composer install
# Installs PHPUnit, Brain Monkey, etc.
```

#### 2. Configure Test Environment

Edit `phpunit.xml` (should already be configured):

```xml
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="WP_TESTS_DOMAIN" value="example.org"/>
        <env name="WP_TESTS_EMAIL" value="admin@example.org"/>
        <env name="WP_TESTS_TITLE" value="Test Site"/>
    </php>
</phpunit>
```

### Run Tests

```bash
# Run all tests
composer test

# Run unit tests only
composer test:unit

# Run integration tests only
composer test:integration

# Run specific test file
composer test tests/Unit/Core/CoreValidationTest.php

# Run specific test method
vendor/bin/phpunit --filter test_validate_chatbot_temperature

# Run with code coverage
composer test:coverage
# Opens HTML coverage report in browser
```

### Continuous Testing (Watch Mode)

Use `nodemon` to auto-run tests on file changes:

```bash
# Install nodemon globally
npm install -g nodemon

# Watch PHP files and run tests
nodemon --watch includes --watch tests --ext php --exec "composer test"
```

### Writing Tests

**Unit Test Example**:
```php
<?php
namespace WP_AI_Tests\Unit\Core;

use WP_AI_Core;
use WP_AI_Tests\Helpers\TestCase;

class MyFeatureTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
        // Setup mocks
    }

    public function test_my_feature() {
        $core = new WP_AI_Core();
        $result = $core->some_method();

        $this->assertTrue($result);
        $this->assertEquals('expected', $result['key']);
    }
}
```

**Run your new test**:
```bash
composer test tests/Unit/Core/MyFeatureTest.php
```

## Debugging Techniques

### PHP Debugging

#### Using var_dump / print_r

```php
// In plugin code
error_log('Debug: ' . print_r($data, true));

// View in log
tail -f wp-content/debug.log
```

#### Using WP_CLI wp shell

```bash
# Interactive PHP shell with WordPress loaded
ddev wp shell

# Test code
php> $core = new WP_AI_Core();
php> print_r($core->get_settings());
```

#### Using Xdebug (Advanced)

**Enable in DDEV**:

1. **Edit `.ddev/config.yaml`**:
```yaml
xdebug_enabled: true
```

2. **Restart DDEV**:
```bash
ddev restart
```

3. **Configure VS Code** (`.vscode/launch.json`):
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "${workspaceFolder}"
      }
    }
  ]
}
```

4. **Set breakpoints** in VS Code

5. **Start debugging** (F5)

6. **Visit site** in browser to trigger breakpoints

### JavaScript Debugging

#### Browser DevTools

```javascript
// Add breakpoints
debugger;

// Log to console
console.log('Debug:', data);
console.table(data); // For arrays/objects

// Network tab: View AJAX requests
// Console tab: View errors and logs
```

#### Debug Chatbot

```javascript
// In chatbot.js, add logging
console.log('Chatbot config:', settings);
console.log('API response:', data);

// Test in browser console
const nonce = wpAiAssistantChatbot.nonce;
fetch('/wp-json/ai-assistant/v1/chat', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': nonce
  },
  body: JSON.stringify({
    question: 'Test question',
    top_k: 5
  })
})
.then(r => r.json())
.then(console.log);
```

### API Debugging

#### Debug OpenAI Requests

```php
// In WP_AI_OpenAI class
error_log('OpenAI Request: ' . json_encode($request_body));
error_log('OpenAI Response: ' . wp_remote_retrieve_body($response));
```

#### Debug Pinecone Queries

```php
// In WP_AI_Pinecone class
error_log('Pinecone Query: ' . json_encode([
    'vector_length' => count($vector),
    'top_k' => $top_k,
    'filter' => $filter
]));
error_log('Pinecone Matches: ' . count($matches));
```

#### Test REST API Directly

```bash
# Get nonce
ddev wp eval "echo wp_create_nonce('wp_rest');"
# Copy nonce

# Test chat endpoint
curl -X POST \
  https://kanopi-2019.ddev.site/wp-json/ai-assistant/v1/chat \
  -H 'Content-Type: application/json' \
  -H 'X-WP-Nonce: YOUR_NONCE_HERE' \
  -d '{
    "question": "What services do you offer?",
    "top_k": 5
  }' | jq

# Test search endpoint
curl -X POST \
  https://kanopi-2019.ddev.site/wp-json/ai-assistant/v1/search \
  -H 'Content-Type: application/json' \
  -H 'X-WP-Nonce: YOUR_NONCE_HERE' \
  -d '{
    "query": "WordPress development",
    "top_k": 10
  }' | jq
```

## Common Issues

### Issue: "Indexer not found"

**Symptoms**:
```
Error: Indexer package not found.
```

**Solutions**:

1. **Verify Node.js installed**:
```bash
node --version
# Should show v18.0.0+
```

2. **Install indexer package**:
```bash
# DDEV (monorepo)
ddev exec "cd packages/wp-ai-indexer && npm install && npm run build"

# Standalone (local)
cd indexer && npm install

# Or global
npm install -g @kanopi/wp-ai-indexer
```

3. **Verify installation**:
```bash
ddev wp ai-indexer check
```

### Issue: "Invalid API key"

**Symptoms**:
```
Error: OpenAI API key is not configured.
```

**Solutions**:

1. **Check environment variables**:
```bash
# DDEV
ddev exec env | grep OPENAI_API_KEY

# Standalone
echo $OPENAI_API_KEY
```

2. **Verify API key format**:
- OpenAI: Should start with `sk-`
- Pinecone: Should be a long string

3. **Restart environment**:
```bash
ddev restart
```

4. **Test API key manually**:
```bash
# Test OpenAI
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer $OPENAI_API_KEY"

# Should return list of models
```

### Issue: Cache not working

**Symptoms**:
- Slow response times
- Many API calls
- Cache stats show "Database Transients"

**Solutions**:

1. **Check if object cache is available**:
```bash
ddev wp eval "echo wp_using_ext_object_cache() ? 'Yes' : 'No';"
```

2. **Enable Redis** (see Configuration section)

3. **Clear cache**:
```bash
ddev wp cache flush
```

4. **Verify Redis connection**:
```bash
ddev wp redis status
```

### Issue: Tests failing

**Symptoms**:
```
PHPUnit errors or failures
```

**Solutions**:

1. **Update dependencies**:
```bash
composer install
```

2. **Check PHP version**:
```bash
php --version
# Should be 8.0+
```

3. **Run specific failing test with verbose output**:
```bash
vendor/bin/phpunit --verbose tests/Unit/Core/CoreValidationTest.php
```

4. **Check for missing WordPress functions**:
- Ensure Brain Monkey is properly set up in test bootstrap
- Check `tests/bootstrap.php`

### Issue: Permission errors

**Symptoms**:
```
Permission denied when installing or running commands
```

**Solutions**:

1. **Fix file permissions**:
```bash
# DDEV
ddev exec chmod -R 755 web/wp-content/plugins/wp-ai-assistant

# Standalone
chmod -R 755 wp-content/plugins/wp-ai-assistant
```

2. **Don't use sudo with npm**:
```bash
# BAD
sudo npm install

# GOOD
npm install

# If permission issues, use nvm
nvm install 18
nvm use 18
npm install
```

## Development Workflow

### Daily Workflow

```bash
# 1. Start environment
ddev start

# 2. Pull latest changes
git pull origin main

# 3. Update dependencies (if composer.lock or package.json changed)
ddev composer install --working-dir=web/wp-content/plugins/wp-ai-assistant

# 4. Make changes to code

# 5. Run linter
composer phpcs

# Auto-fix style issues
composer phpcbf

# 6. Run tests
composer test

# 7. Test manually in browser

# 8. Commit changes
git add .
git commit -m "feat: add new feature"

# 9. Push to GitHub
git push origin feature/my-feature

# 10. Stop environment (optional)
ddev stop
```

### Feature Development Workflow

```bash
# 1. Create feature branch
git checkout -b feature/add-relevance-boost

# 2. Make changes

# 3. Add tests
# Create tests/Unit/MyFeatureTest.php

# 4. Run tests
composer test

# 5. Update documentation
# Edit docs/API.md, README.md, etc.

# 6. Commit and push
git add .
git commit -m "feat(search): add custom relevance boosting"
git push origin feature/add-relevance-boost

# 7. Create Pull Request on GitHub

# 8. Address review feedback

# 9. Merge when approved
```

## Database Management

### View Database

```bash
# DDEV - Access database via CLI
ddev mysql

# Run queries
mysql> USE db;
mysql> SELECT * FROM wp_options WHERE option_name = 'wp_ai_assistant_settings'\G

# Or use GUI (phpMyAdmin)
ddev launch -p
# Navigate to Database tab
```

### Export Database

```bash
# Export entire database
ddev export-db --file=backup.sql.gz

# Export specific table
ddev mysql -e "SELECT * FROM wp_ai_chat_logs" > chat_logs.sql
```

### Import Database

```bash
# Import database dump
ddev import-db --src=backup.sql.gz
```

### Reset Database

```bash
# Drop all tables and reinstall WordPress
ddev wp db reset --yes
ddev wp core install \
  --url=https://kanopi-2019.ddev.site \
  --title="Dev Site" \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=admin@example.com
```

### Query Plugin Tables

```bash
# View chat logs
ddev wp db query "SELECT * FROM wp_ai_chat_logs ORDER BY created_at DESC LIMIT 10"

# View search logs
ddev wp db query "SELECT * FROM wp_ai_search_logs ORDER BY created_at DESC LIMIT 10"

# Count logs
ddev wp db query "SELECT COUNT(*) FROM wp_ai_chat_logs"
```

## Cache Management

### Clear All Caches

```bash
# WordPress object cache
ddev wp cache flush

# Redis cache (if using Redis)
ddev wp redis clear

# Plugin-specific cache
ddev wp eval "WP_AI_Cache::flush_all();"

# Transients
ddev wp transient delete --all

# Rewrite rules
ddev wp rewrite flush
```

### View Cache Stats

```bash
# Plugin cache stats
ddev wp eval "print_r(WP_AI_Cache::get_stats());"

# Redis stats (if using Redis)
ddev wp redis status
```

### Monitor Cache Hit Rate

```php
// Add to wp-config.php (temporarily)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// In plugin code, cache operations will be logged
// View logs
tail -f wp-content/debug.log | grep "cache"
```

---

## Quick Reference

### Useful Commands

```bash
# DDEV
ddev start              # Start environment
ddev stop               # Stop environment
ddev restart            # Restart environment
ddev wp                 # Run WP-CLI commands
ddev composer           # Run Composer
ddev logs               # View logs
ddev ssh                # SSH into container
ddev describe           # Show environment info

# Plugin
composer test           # Run tests
composer phpcs          # Check code style
composer phpcbf         # Fix code style
ddev wp ai-indexer index --debug  # Index content

# Git
git status              # Check status
git add .               # Stage changes
git commit -m "msg"     # Commit
git push                # Push to remote
```

### File Locations

```
Plugin root:      web/wp-content/plugins/wp-ai-assistant/
Settings:         wp_options table (wp_ai_assistant_settings)
Logs:             wp_ai_chat_logs, wp_ai_search_logs tables
Debug log:        wp-content/debug.log
Tests:            tests/
Documentation:    docs/
Indexer:          indexer/ (local) or packages/wp-ai-indexer/ (monorepo)
```

---

## Next Steps

- Read [ARCHITECTURE.md](ARCHITECTURE.md) to understand system design
- Review [API.md](API.md) for API reference
- Check [CONTRIBUTING.md](../CONTRIBUTING.md) for contribution guidelines
- See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for advanced debugging

Happy developing! 🚀
