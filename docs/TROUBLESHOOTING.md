# Troubleshooting Guide

Comprehensive troubleshooting guide for developers working with the Semantic Knowledge plugin.

## Table of Contents

- [Common Errors](#common-errors)
- [Debugging Tips](#debugging-tips)
- [Log File Locations](#log-file-locations)
- [Performance Issues](#performance-issues)
- [API Connection Issues](#api-connection-issues)
- [Indexing Issues](#indexing-issues)
- [Cache Issues](#cache-issues)
- [Security & Rate Limiting](#security--rate-limiting)
- [Database Issues](#database-issues)
- [Frontend Issues](#frontend-issues)

## Common Errors

### "Indexer package not found"

**Error Message**:
```
Error: Indexer package not found. Please install Node.js 18+ and run 'npm install -g @kanopi/wp-ai-indexer' or install locally in the plugin's indexer directory.
```

**Cause**: Node.js not installed or indexer package not installed.

**Solutions**:

1. **Verify Node.js installation**:
```bash
node --version
# Should output: v18.0.0 or higher
```

If not installed:
```bash
# Using nvm (recommended)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
nvm install 18
nvm use 18

# macOS with Homebrew
brew install node@18

# Ubuntu
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

2. **Install indexer package**:

**For DDEV (monorepo)**:
```bash
ddev exec "cd packages/wp-ai-indexer && npm install && npm run build"
```

**For standalone (local installation)**:
```bash
cd wp-content/plugins/semantic-knowledge/indexer
npm install
```

**For global installation** (CI/CD):
```bash
npm install -g @kanopi/wp-ai-indexer
```

3. **Verify installation**:
```bash
wp sk-indexer check
# Should output: ✓ Node.js found (v18.x.x)
#                ✓ Indexer package found
```

**Still not working?**
- Clear system check cache: `wp transient delete semantic_knowledge_system_check`
- Check file permissions: `ls -la indexer/`
- Try reinstalling: `rm -rf indexer/node_modules && npm install`

---

### "OpenAI API key is not configured"

**Error Message**:
```
Error: OpenAI API key is not configured.
```

**Cause**: `OPENAI_API_KEY` environment variable or constant not set.

**Solutions**:

1. **Check current configuration**:
```bash
# DDEV
ddev exec env | grep OPENAI_API_KEY

# Standalone
echo $OPENAI_API_KEY

# WordPress (via WP-CLI)
wp eval "echo getenv('OPENAI_API_KEY') ? 'Set' : 'Not set';"
```

2. **Set API key**:

**Option A: Environment variable (DDEV)**:

Edit `.ddev/config.yaml`:
```yaml
web_environment:
  - OPENAI_API_KEY=sk-proj-your-key-here
  - PINECONE_API_KEY=your-key-here
  - Semantic_Knowledge_INDEXER_KEY=your-secure-key-here
```

Then restart:
```bash
ddev restart
```

**Option B: wp-config.php constant**:
```php
// Add before "That's all, stop editing!"
define('OPENAI_API_KEY', 'sk-proj-your-key-here');
define('PINECONE_API_KEY', 'your-key-here');
define('Semantic_Knowledge_INDEXER_KEY', 'your-secure-key-here');
```

**Option C: Environment variable (standalone)**:
```bash
# .env file
export OPENAI_API_KEY="sk-proj-your-key-here"
export PINECONE_API_KEY="your-key-here"
export Semantic_Knowledge_INDEXER_KEY="your-secure-key-here"

# Load variables
source .env
```

3. **Verify API key format**:
- Should start with `sk-proj-` or `sk-` (older keys)
- Typical length: 48-51 characters
- No spaces or special characters

4. **Test API key**:
```bash
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer $OPENAI_API_KEY"
```

Should return list of available models, not an error.

---

### "Invalid security token"

**Error Message**:
```json
{
  "code": "semantic_knowledge_invalid_nonce",
  "message": "Invalid security token. Please refresh the page and try again."
}
```

**Cause**: WordPress nonce expired or invalid.

**Solutions**:

1. **Refresh the page** - Nonces expire after 12-24 hours

2. **Check nonce generation**:
```php
// Verify nonce is created correctly
$nonce = wp_create_nonce('wp_rest');
echo "Nonce: $nonce";
```

3. **Verify nonce in JavaScript**:
```javascript
console.log('Nonce:', wpAiAssistantChatbot.nonce);
// Should output a random string, not undefined
```

4. **Check for caching issues**:
- Disable page caching for pages with chatbot/search
- Exclude from CDN caching if using Cloudflare/etc.

5. **Verify AJAX URL**:
```javascript
console.log('Endpoint:', wpAiAssistantChatbot.endpoint);
// Should be: https://yoursite.com/wp-json/semantic-knowledge/v1/chat
```

---

### "Rate limit exceeded"

**Error Message**:
```json
{
  "code": "semantic_knowledge_rate_limit_exceeded",
  "message": "Rate limit exceeded. Please wait before making another request. Limit: 10 requests per 60 seconds."
}
```

**Cause**: Too many requests from same IP address.

**Solutions**:

1. **Wait 60 seconds** - Default rate limit window

2. **Increase rate limits for development**:
```php
// In functions.php or custom plugin
add_filter('semantic_knowledge_chatbot_rate_limit', function($limit) {
    return WP_DEBUG ? 100 : 10; // Higher limit in dev
});

add_filter('semantic_knowledge_chatbot_rate_window', function($window) {
    return WP_DEBUG ? 10 : 60; // Shorter window in dev
});
```

3. **Clear rate limit transients**:
```bash
wp transient delete --all
# Or specifically
wp transient delete wp_ai_chatbot_rl_{hash}
```

4. **Check IP detection**:
```bash
wp eval "
\$request = new WP_REST_Request();
\$chatbot = new Semantic_Knowledge_Chatbot_Module(new Semantic_Knowledge_Core(), new Semantic_Knowledge_OpenAI(...), new Semantic_Knowledge_Pinecone(...));
echo 'Detected IP: ' . \$chatbot->get_client_ip();
"
```

5. **Disable rate limiting temporarily**:
```php
// Return true to bypass rate limit check
add_filter('semantic_knowledge_chatbot_rate_limit', '__return_false');
add_filter('semantic_knowledge_search_rate_limit', '__return_false');
```

---

### "Pinecone query failed"

**Error Message**:
```
Error: Pinecone query failed: Index not found
```

**Cause**: Pinecone index not created, incorrect host, or API key invalid.

**Solutions**:

1. **Verify Pinecone configuration**:
```bash
wp option get semantic_knowledge_settings --format=json | jq '{
  pinecone_index_host,
  pinecone_index_name
}'
```

2. **Check index exists in Pinecone**:
- Log into [Pinecone Console](https://app.pinecone.io)
- Verify index name matches plugin settings
- Check index dimensions (should be 1536 for text-embedding-3-small)

3. **Test Pinecone connection**:
```bash
# Get index stats
wp eval "
\$core = new Semantic_Knowledge_Core();
\$secrets = new Semantic_Knowledge_Secrets();
\$pinecone = new Semantic_Knowledge_Pinecone(\$core, \$secrets);
print_r(\$pinecone->get_index_stats());
"
```

4. **Verify API key**:
```bash
echo $PINECONE_API_KEY
# Should be a long alphanumeric string
```

5. **Check index host format**:
```
Correct:   https://index-abc123-xyz789.svc.pinecone.io
Wrong:     index-abc123-xyz789.svc.pinecone.io (missing https://)
Wrong:     https://index-abc123-xyz789.svc.pinecone.io/ (trailing slash)
```

The plugin automatically removes trailing slashes, but verify the host is correct.

---

## Debugging Tips

### Enable Debug Logging

1. **Enable WordPress debug mode** in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

2. **View logs**:
```bash
# DDEV
ddev logs

# Standalone
tail -f wp-content/debug.log

# Filter for plugin logs
tail -f wp-content/debug.log | grep "WP AI"
```

### Debug Specific Components

#### Debug Chatbot Queries

```php
// Add to chatbot module or via filter
add_action('semantic_knowledge_chatbot_query_start', function($question) {
    error_log("CHATBOT QUERY START: $question");
});

add_action('semantic_knowledge_chatbot_query_end', function($response, $question) {
    error_log("CHATBOT QUERY END: " . json_encode([
        'question' => $question,
        'answer_length' => strlen($response['answer']),
        'sources_count' => count($response['sources'])
    ]));
}, 10, 2);
```

#### Debug Search Queries

```php
add_action('semantic_knowledge_search_query_start', function($query) {
    error_log("SEARCH QUERY START: $query");
});

add_action('semantic_knowledge_search_query_end', function($response, $query) {
    error_log("SEARCH QUERY END: " . json_encode([
        'query' => $query,
        'total_results' => $response['total'],
        'has_summary' => !empty($response['summary'])
    ]));
}, 10, 2);
```

#### Debug API Calls

**OpenAI requests**:
```php
// In Semantic_Knowledge_OpenAI class, temporarily add
error_log('OpenAI Request: ' . json_encode([
    'model' => $model,
    'text_length' => strlen($text),
    'endpoint' => 'embeddings'
]));

error_log('OpenAI Response Code: ' . wp_remote_retrieve_response_code($response));
```

**Pinecone queries**:
```php
// In Semantic_Knowledge_Pinecone class, temporarily add
error_log('Pinecone Query: ' . json_encode([
    'vector_length' => count($vector),
    'top_k' => $top_k,
    'has_filter' => !empty($filter),
    'filter' => $filter
]));

error_log('Pinecone Matches: ' . count($matches));
```

### Use WP-CLI for Testing

```bash
# Test chatbot flow
wp eval "
\$core = new Semantic_Knowledge_Core();
\$secrets = new Semantic_Knowledge_Secrets();
\$openai = new Semantic_Knowledge_OpenAI(\$core, \$secrets);
\$pinecone = new Semantic_Knowledge_Pinecone(\$core, \$secrets);

// Create embedding
\$embedding = \$openai->create_embedding('What services do you offer?');
if (is_wp_error(\$embedding)) {
    echo 'Embedding error: ' . \$embedding->get_error_message() . PHP_EOL;
    exit(1);
}
echo 'Embedding created: ' . count(\$embedding) . ' dimensions' . PHP_EOL;

// Query Pinecone
\$matches = \$pinecone->query_with_domain_filter(\$embedding, 5);
if (is_wp_error(\$matches)) {
    echo 'Pinecone error: ' . \$matches->get_error_message() . PHP_EOL;
    exit(1);
}
echo 'Found ' . count(\$matches) . ' matches' . PHP_EOL;
print_r(\$matches);
"
```

### Monitor Performance

```php
// Add timing to any function
$start = microtime(true);

// ... your code ...

$end = microtime(true);
$duration = ($end - $start) * 1000; // Convert to milliseconds
error_log("Function took {$duration}ms");
```

---

## Log File Locations

### WordPress Debug Log
```
Location: wp-content/debug.log
View: tail -f wp-content/debug.log
```

### Plugin Logs (Database)
```sql
-- Chat logs
SELECT * FROM wp_sk_chat_logs ORDER BY created_at DESC LIMIT 10;

-- Search logs
SELECT * FROM wp_sk_search_logs ORDER BY created_at DESC LIMIT 10;

-- Via WP-CLI
wp db query "SELECT * FROM wp_sk_chat_logs ORDER BY created_at DESC LIMIT 10"
```

### Web Server Logs (DDEV)
```bash
# PHP errors
ddev logs

# Nginx access log
ddev logs -s web

# Nginx error log
ddev logs -f web
```

### Indexer Logs
```bash
# Run indexer with debug flag
wp sk-indexer index --debug

# Outputs detailed progress and errors to stdout
```

---

## Performance Issues

### Slow Response Times

**Symptoms**: Chatbot or search takes >5 seconds to respond.

**Diagnosis**:

1. **Check if caching is working**:
```bash
wp eval "print_r(Semantic_Knowledge_Cache::get_stats());"
```

Expected output:
```
Array
(
    [using_object_cache] => 1  // Should be 1 (true)
    [cache_type] => Redis/Memcached
    ...
)
```

If `using_object_cache` is 0:
- Object cache not available
- Install Redis (see LOCAL-DEVELOPMENT.md)

2. **Monitor cache hit rate**:
```php
// Add temporarily to track cache performance
add_action('semantic_knowledge_chatbot_query_start', function() {
    global $wp_ai_cache_hits, $wp_ai_cache_misses;
    $wp_ai_cache_hits = 0;
    $wp_ai_cache_misses = 0;
});

// In Semantic_Knowledge_Cache::get()
if ($value !== false) {
    $GLOBALS['semantic_knowledge_cache_hits']++;
} else {
    $GLOBALS['semantic_knowledge_cache_misses']++;
}

add_action('semantic_knowledge_chatbot_query_end', function() {
    error_log("Cache hits: {$GLOBALS['semantic_knowledge_cache_hits']}, misses: {$GLOBALS['semantic_knowledge_cache_misses']}");
});
```

3. **Check API response times**:
```php
// Time OpenAI calls
$start = microtime(true);
$embedding = $openai->create_embedding($text);
$duration = (microtime(true) - $start) * 1000;
error_log("OpenAI embedding: {$duration}ms");

// Time Pinecone calls
$start = microtime(true);
$matches = $pinecone->query_with_domain_filter($embedding, $top_k);
$duration = (microtime(true) - $start) * 1000;
error_log("Pinecone query: {$duration}ms");
```

**Solutions**:

1. **Enable object caching** (Redis/Memcached)
   - See [PERFORMANCE.md](PERFORMANCE.md)
   - 80% latency reduction

2. **Reduce top_k** - Fewer results = faster queries
```php
// In settings
'chatbot_top_k' => 3,  // Instead of 5
'search_top_k' => 5,   // Instead of 10
```

3. **Enable response compression**:
```php
// In wp-config.php
define('Semantic_Knowledge_ENABLE_COMPRESSION', true);
```

4. **Optimize chunk size** - Larger chunks = fewer API calls
```php
// In settings
'chunk_size' => 1500,     // Instead of 1200
'chunk_overlap' => 150,   // Instead of 200
```

---

### High API Costs

**Symptoms**: Unexpectedly high OpenAI or Pinecone bills.

**Diagnosis**:

1. **Check query volume**:
```sql
-- Chat queries per day
SELECT DATE(created_at) as date, COUNT(*) as queries
FROM wp_sk_chat_logs
GROUP BY DATE(created_at)
ORDER BY date DESC
LIMIT 30;

-- Search queries per day
SELECT DATE(created_at) as date, COUNT(*) as queries
FROM wp_sk_search_logs
GROUP BY DATE(created_at)
ORDER BY date DESC
LIMIT 30;
```

2. **Check cache hit rate** (see above)

3. **Monitor API usage**:
- OpenAI: [platform.openai.com/usage](https://platform.openai.com/usage)
- Pinecone: [app.pinecone.io](https://app.pinecone.io) → Usage

**Solutions**:

1. **Enable caching** - Reduce API calls by 80%

2. **Increase cache TTL**:
```php
// Increase embedding cache from 1 hour to 24 hours
add_filter('semantic_knowledge_embedding_cache_ttl', function() {
    return DAY_IN_SECONDS;
});

// Increase query cache from 15 minutes to 1 hour
add_filter('semantic_knowledge_query_cache_ttl', function() {
    return HOUR_IN_SECONDS;
});
```

3. **Implement request throttling**:
```php
// Lower rate limits
add_filter('semantic_knowledge_chatbot_rate_limit', function() {
    return 5; // Instead of 10
});
```

4. **Use cheaper models**:
```php
// Use text-embedding-3-small instead of 3-large
'embedding_model' => 'text-embedding-3-small',
'embedding_dimension' => 1536,

// Use gpt-4o-mini instead of gpt-4
'chatbot_model' => 'gpt-4o-mini',
```

---

## API Connection Issues

### OpenAI Connection Failed

**Error**: `cURL error: Could not resolve host: api.openai.com`

**Cause**: DNS resolution failure, firewall blocking, or network issue.

**Solutions**:

1. **Test connectivity**:
```bash
curl -I https://api.openai.com
# Should return: HTTP/2 200
```

2. **Check DNS resolution**:
```bash
nslookup api.openai.com
# Should return IP addresses
```

3. **Check firewall rules**:
```bash
# Allow outbound HTTPS to OpenAI
sudo ufw allow out https
```

4. **Test with PHP**:
```bash
wp eval "
\$response = wp_remote_get('https://api.openai.com/v1/models', [
    'headers' => ['Authorization' => 'Bearer ' . getenv('OPENAI_API_KEY')]
]);
echo wp_remote_retrieve_response_code(\$response);
"
# Should output: 200
```

5. **Check for proxy requirements**:
```php
// If behind corporate proxy
add_filter('http_request_args', function($args) {
    $args['proxy'] = 'http://proxy.company.com:8080';
    return $args;
});
```

---

### Pinecone Connection Failed

**Error**: `cURL error: Could not resolve host: {index}.svc.pinecone.io`

**Solutions**: Similar to OpenAI (above), plus:

1. **Verify index exists**:
   - Log into Pinecone console
   - Check index is in "Ready" state

2. **Check index host URL format**:
```bash
wp option get semantic_knowledge_settings --format=json | jq '.pinecone_index_host'
# Should be: "https://index-abc123.svc.pinecone.io"
# NOT: "index-abc123.svc.pinecone.io" (missing https://)
```

3. **Test connection**:
```bash
curl -X POST "https://your-index.svc.pinecone.io/query" \
  -H "Api-Key: $PINECONE_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "topK": 5,
    "includeMetadata": true,
    "vector": [0.1, 0.2, ...]
  }'
```

---

## Indexing Issues

### No Content Indexed

**Symptoms**: `wp sk-indexer index` reports 0 posts indexed.

**Diagnosis**:

1. **Check if posts exist**:
```bash
wp post list --post_type=post,page --post_status=publish
```

2. **Check post types configuration**:
```bash
wp option get semantic_knowledge_settings --format=json | jq '{
  post_types,
  post_types_exclude,
  auto_discover
}'
```

3. **Run with debug flag**:
```bash
wp sk-indexer index --debug
```

**Solutions**:

1. **Enable auto-discovery**:
```bash
wp option patch update semantic_knowledge_settings auto_discover true
```

2. **Manually specify post types**:
```bash
wp option patch update semantic_knowledge_settings post_types "post,page,custom_type"
```

3. **Check excluded post types**:
```bash
# View current exclusions
wp option get semantic_knowledge_settings --format=json | jq '.post_types_exclude'

# Remove unwanted exclusions
wp option patch update semantic_knowledge_settings post_types_exclude "attachment,revision"
```

---

### Indexing Hangs or Times Out

**Symptoms**: Indexer starts but never completes, or times out.

**Solutions**:

1. **Increase timeout**:
```php
// In wp-config.php
define('WP_TIMEOUT', 300); // 5 minutes
```

2. **Index in batches**:
```bash
# Index recent posts only
wp sk-indexer index --since=2024-01-01

# Clean and re-index incrementally
wp sk-indexer clean
wp sk-indexer index
```

3. **Check Node.js memory**:
```bash
# Increase Node.js heap size
NODE_OPTIONS=--max-old-space-size=4096 wp sk-indexer index
```

4. **Check for large posts**:
```sql
-- Find posts with huge content
SELECT ID, post_title, LENGTH(post_content) as content_length
FROM wp_posts
WHERE post_status = 'publish'
ORDER BY content_length DESC
LIMIT 10;
```

If posts are very large, reduce chunk size:
```bash
wp option patch update semantic_knowledge_settings chunk_size 800
```

---

## Cache Issues

### Stale Cache Data

**Symptoms**: Changes to content not reflected in search/chatbot responses.

**Solutions**:

1. **Clear all caches**:
```bash
# WordPress cache
wp cache flush

# Redis cache
wp redis clear

# Plugin cache
wp eval "Semantic_Knowledge_Cache::flush_all();"

# Transients
wp transient delete --all
```

2. **Re-index content**:
```bash
wp sk-indexer clean
wp sk-indexer index
```

3. **Disable caching temporarily for testing**:
```php
// In wp-config.php
define('Semantic_Knowledge_DISABLE_CACHE', true);
```

---

### Cache Not Persisting

**Symptoms**: Cache stats show no hits, data not persisting between requests.

**Diagnosis**:

1. **Check object cache status**:
```bash
wp eval "echo wp_using_ext_object_cache() ? 'Yes' : 'No';"
```

2. **Test Redis connection** (if using Redis):
```bash
wp redis status
# Should show: "Status: Connected"
```

**Solutions**:

1. **Install object cache plugin**:
```bash
composer require wpackagist-plugin/redis-cache
wp plugin activate redis-cache
wp redis enable
```

2. **Verify Redis is running**:
```bash
# DDEV
ddev describe | grep redis

# Standalone
redis-cli ping
# Should output: PONG
```

3. **Check cache configuration**:
```php
// In wp-content/object-cache.php (if exists)
// Verify Redis host and port are correct
```

---

## Security & Rate Limiting

### Bypassing Security for Development

**Temporarily disable security features for testing**:

```php
// In wp-config.php or functions.php

// Disable rate limiting
add_filter('semantic_knowledge_chatbot_rate_limit', '__return_false');
add_filter('semantic_knowledge_search_rate_limit', '__return_false');

// Disable nonce validation (NOT recommended for production)
add_filter('rest_authentication_errors', function($result) {
    if (is_wp_error($result)) {
        return null; // Allow unauthenticated requests
    }
    return $result;
});

// Allow all CORS requests (development only)
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        return $value;
    });
});
```

**⚠️ WARNING**: Remove these for production!

---

## Database Issues

### Tables Not Created

**Symptoms**: Chat/search logs not saving, errors about missing tables.

**Solutions**:

1. **Manually create tables**:
```bash
wp eval "Semantic_Knowledge_Database::init();"
```

2. **Verify tables exist**:
```bash
wp db query "SHOW TABLES LIKE 'semantic_knowledge_%';"
# Should show: wp_sk_chat_logs, wp_sk_search_logs
```

3. **Re-activate plugin**:
```bash
wp plugin deactivate semantic-knowledge
wp plugin activate semantic-knowledge
```

---

### Database Queries Too Slow

**Symptoms**: Slow admin pages when viewing logs.

**Solutions**:

1. **Add missing indexes** (should exist by default):
```sql
ALTER TABLE wp_sk_chat_logs ADD INDEX created_at (created_at DESC);
ALTER TABLE wp_sk_chat_logs ADD INDEX question (question(100));

ALTER TABLE wp_sk_search_logs ADD INDEX created_at (created_at DESC);
ALTER TABLE wp_sk_search_logs ADD INDEX query (query(100));
```

2. **Clean old logs**:
```bash
# Delete logs older than 90 days
wp eval "Semantic_Knowledge_Database::cleanup_old_logs(90);"
```

3. **Optimize tables**:
```bash
wp db query "OPTIMIZE TABLE wp_sk_chat_logs, wp_sk_search_logs;"
```

---

## Frontend Issues

### Chatbot Not Appearing

**Symptoms**: Floating button or chatbot shortcode not visible.

**Diagnosis**:

1. **Check if JavaScript loaded**:
```javascript
// In browser console
console.log(window.wpAiAssistantChatbot);
// Should output: {endpoint: "...", nonce: "...", ...}
```

2. **Check for JavaScript errors**:
- Open browser DevTools (F12)
- Check Console tab for errors

3. **Check if Deep Chat loaded**:
```javascript
console.log(customElements.get('deep-chat'));
// Should output: [object Object] or function
```

**Solutions**:

1. **Verify chatbot is enabled**:
```bash
wp option get semantic_knowledge_settings --format=json | jq '.chatbot_enabled'
# Should output: true
```

2. **Check floating button setting**:
```bash
wp option get semantic_knowledge_settings --format=json | jq '.chatbot_floating_button'
# Should output: true (if using floating button)
```

3. **Clear caches**:
```bash
wp cache flush
# Also clear browser cache (Ctrl+Shift+R)
```

4. **Check for JavaScript conflicts**:
- Disable other plugins temporarily
- Switch to default WordPress theme
- Test if chatbot appears

5. **Verify plugin is configured**:
```bash
wp eval "
\$core = new Semantic_Knowledge_Core();
echo \$core->is_configured() ? 'Configured' : 'Not configured';
"
```

---

### Search Not Working

**Symptoms**: Search form submits but no results appear.

**Solutions**:

1. **Check browser console** for JavaScript errors

2. **Test REST API directly**:
```bash
# Get nonce
NONCE=$(wp eval "echo wp_create_nonce('wp_rest');")

# Test search endpoint
curl -X POST "https://yoursite.com/wp-json/semantic-knowledge/v1/search" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: $NONCE" \
  -d '{"query": "test", "top_k": 10}'
```

3. **Verify content is indexed**:
```bash
wp sk-indexer config
# Check: vector_count should be > 0
```

4. **Check search is enabled**:
```bash
wp option get semantic_knowledge_settings --format=json | jq '.search_enabled'
# Should output: true
```

---

## Getting Help

If none of these solutions work:

1. **Enable debug mode** and collect logs
2. **Note exact error messages** and steps to reproduce
3. **Check system information**:
```bash
wp sk-indexer check
wp core version
php --version
```

4. **Open GitHub issue** with:
   - Error messages
   - Debug logs
   - Steps to reproduce
   - System information

5. **Or email**: hello@kanopi.com with "Semantic Knowledge" in subject

---

## Additional Resources

- [Architecture Documentation](ARCHITECTURE.md) - System design
- [API Documentation](API.md) - Complete API reference
- [Local Development Guide](LOCAL-DEVELOPMENT.md) - Setup instructions
- [Performance Guide](PERFORMANCE.md) - Optimization tips
- [GitHub Issues](https://github.com/kanopi/semantic-knowledge/issues) - Report bugs
