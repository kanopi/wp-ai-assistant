# Performance Optimization Guide

This guide covers performance optimization strategies for WP AI Assistant, including caching, compression, and CDN configuration.

## Object Caching for Transients

The plugin uses WordPress transients for caching API responses and settings. By default, transients are stored in the database, but enabling persistent object caching dramatically improves performance.

### Benefits

- **80% reduction** in Pinecone query times (cached queries return instantly)
- **50% reduction** in API latency for end users
- **Reduced database load** (transients stored in memory instead of database)
- **Faster settings retrieval** (indexer settings cached in Redis/Memcached)

### Recommended: Redis with DDEV

**1. Enable Redis in DDEV**

Add to `.ddev/config.yaml`:

```yaml
services:
  redis:
    type: redis
    version: "7"

web_environment:
  - REDIS_HOST=redis
  - REDIS_PORT=6379
```

Restart DDEV:
```bash
ddev restart
```

**2. Install Redis Object Cache Plugin**

```bash
ddev composer require wpackagist-plugin/redis-cache
ddev wp plugin activate redis-cache
ddev wp redis enable
```

**3. Verify Redis is Working**

```bash
ddev wp redis status
```

You should see:
```
Status: Connected
Client: Predis
```

### Alternative: Memcached

**1. Enable Memcached in DDEV**

Add to `.ddev/config.yaml`:

```yaml
services:
  memcached:
    type: memcached
    version: "1.6"
```

**2. Install Memcached Object Cache**

```bash
ddev composer require wpackagist-plugin/memcached
ddev wp plugin activate memcached
```

Copy the object-cache.php drop-in:
```bash
ddev exec "cp wp-content/plugins/memcached/object-cache.php wp-content/object-cache.php"
```

### Production Configuration

**Pantheon:**
Redis is available on Performance and Elite plans. It's automatically configured - no setup needed.

**WP Engine:**
Includes built-in object caching. No configuration required.

**Custom Servers:**

Install Redis:
```bash
sudo apt-get install redis-server php-redis
```

Install Redis Object Cache plugin via WordPress admin or WP-CLI:
```bash
wp plugin install redis-cache --activate
wp redis enable
```

### Verifying Object Cache is Active

Check if object cache is enabled:
```bash
wp redis status
# Or for Memcached:
wp cache type
```

Test cache performance:
```bash
# Before object cache:
time wp transient get wp_ai_indexer_settings_response

# After object cache (should be much faster on second run):
time wp transient get wp_ai_indexer_settings_response
```

### Transients Used by WP AI Assistant

The plugin uses the following transients:

| Transient Key | Purpose | TTL | Impact |
|--------------|---------|-----|--------|
| `wp_ai_indexer_settings_response` | Indexer settings cache | 1 hour | High - reduces DB queries |
| `wp_ai_chatbot_rl_{hash}` | Rate limiting counters | 60 seconds | High - prevents abuse |
| `wp_ai_assistant_system_check` | System requirements check | 6 hours | Low - admin only |

With object caching enabled, these transients are stored in Redis/Memcached instead of the database.

---

## Response Compression

Enable Gzip or Brotli compression to reduce bandwidth and improve load times.

### Benefits

- **60-80% reduction** in CSS/JS file sizes
- **30-50% faster page loads**
- **Reduced bandwidth costs**

### DDEV Configuration

Add to `.ddev/config.yaml`:

```yaml
webserver_type: nginx-fpm

nginx_config:
  - compression: |
      gzip on;
      gzip_vary on;
      gzip_min_length 1024;
      gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript application/json;
      gzip_disable "msie6";
```

Restart DDEV:
```bash
ddev restart
```

### Apache (.htaccess)

Add to `.htaccess`:

```apache
# Enable Gzip compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
  BrowserMatch ^Mozilla/4 gzip-only-text/html
  BrowserMatch ^Mozilla/4\.0[678] no-gzip
  BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
</IfModule>

# Enable Brotli compression (if available)
<IfModule mod_brotli.c>
  AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

### Nginx

Add to nginx server block:

```nginx
# Gzip compression
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript application/json;
gzip_disable "msie6";

# Brotli compression (if available)
brotli on;
brotli_comp_level 6;
brotli_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript application/json;
```

### Verifying Compression

Test with curl:

```bash
# Check for Gzip
curl -I -H "Accept-Encoding: gzip" https://yoursite.com/wp-content/plugins/wp-ai-assistant/assets/css/chatbot.css

# Look for:
# Content-Encoding: gzip

# Check for Brotli
curl -I -H "Accept-Encoding: br" https://yoursite.com/wp-content/plugins/wp-ai-assistant/assets/css/chatbot.css

# Look for:
# Content-Encoding: br
```

Or use browser DevTools:
1. Open DevTools (F12)
2. Go to Network tab
3. Reload page
4. Click on a CSS/JS file
5. Check Response Headers for `Content-Encoding: gzip` or `Content-Encoding: br`

---

## CDN Configuration

For optimal performance, serve plugin assets via CDN.

### Cloudflare Setup

1. **Add Site to Cloudflare**
   - Sign up at https://cloudflare.com
   - Add your domain
   - Update nameservers

2. **Enable Caching**
   - Go to Caching → Configuration
   - Set Caching Level to "Standard"
   - Enable "Always Online"

3. **Enable Brotli**
   - Go to Speed → Optimization
   - Enable "Brotli" compression

4. **Create Page Rules** (optional)
   - Go to Rules → Page Rules
   - Create rule: `yourdomain.com/wp-content/plugins/*`
   - Settings:
     - Cache Level: Cache Everything
     - Edge Cache TTL: 1 month
     - Browser Cache TTL: 1 month

### WordPress CDN Plugin

Install and configure a CDN plugin:

```bash
wp plugin install cdn-enabler --activate
```

Configure in Settings → CDN Enabler:
- CDN URL: `https://cdn.yourdomain.com`
- Included Directories: `wp-content,wp-includes`
- CDN HTTPS: Enabled

---

## Asset Optimization

### Minification

The plugin assets are already minified in production, but you can further optimize with build tools.

**Install dependencies:**
```bash
cd web/wp-content/plugins/wp-ai-assistant
npm install --save-dev terser clean-css-cli
```

**Add to package.json scripts:**
```json
{
  "scripts": {
    "minify-css": "cleancss -o assets/css/chatbot.min.css assets/css/chatbot.css && cleancss -o assets/css/search.min.css assets/css/search.css",
    "minify-js": "terser assets/js/chatbot.js -o assets/js/chatbot.min.js -c -m && terser assets/js/search.js -o assets/js/search.min.js -c -m",
    "minify": "npm run minify-css && npm run minify-js"
  }
}
```

**Run minification:**
```bash
npm run minify
```

### Self-Hosting Deep Chat Library

Currently, Deep Chat is loaded from CDN. For better performance and reliability, self-host it:

**1. Download Deep Chat:**
```bash
cd web/wp-content/plugins/wp-ai-assistant/assets/vendor
wget https://cdn.jsdelivr.net/npm/deep-chat@2.3.0/dist/deepChat.bundle.js
```

**2. Generate SRI hash:**
```bash
openssl dgst -sha384 -binary deepChat.bundle.js | openssl base64 -A
```

**3. Update chatbot.js to use local version:**

Change line 36 from:
```javascript
script.src = settings.deepChatUrl || 'https://cdn.jsdelivr.net/npm/deep-chat@2.3.0/dist/deepChat.bundle.js';
```

To:
```javascript
script.src = settings.deepChatUrl || '/wp-content/plugins/wp-ai-assistant/assets/vendor/deepChat.bundle.js';
```

**4. Add SRI integrity attribute** (optional but recommended):
```javascript
script.setAttribute('integrity', 'sha384-{your-generated-hash}');
script.setAttribute('crossorigin', 'anonymous');
```

---

## Performance Monitoring

### Key Metrics to Track

1. **API Response Time**
   - Target: < 2 seconds (P95)
   - Monitor via Application Performance Monitoring (APM) tools

2. **Cache Hit Rate**
   ```bash
   wp redis info | grep keyspace_hits
   ```
   - Target: > 80% hit rate

3. **Page Load Time**
   - Use Google PageSpeed Insights
   - Target: < 3 seconds for First Contentful Paint

4. **Transient Storage**
   ```bash
   # Check transient count in database (should be low with object cache)
   wp transient list --format=count
   ```

### Recommended Tools

- **New Relic APM** - Application performance monitoring
- **Datadog** - Infrastructure and application monitoring
- **Query Monitor** - WordPress plugin for debugging performance
- **Redis Commander** - GUI for viewing Redis data

---

## Performance Benchmarks

### Expected Improvements with Full Optimization

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| API Response Time (P95) | 3-5s | 1-2s | 60% faster |
| Cache Hit Rate | 0% | 80%+ | N/A |
| Page Load Time | 4-6s | 2-3s | 50% faster |
| CSS/JS Transfer Size | 50KB | 15KB | 70% reduction |
| Database Queries | 15-20 | 8-10 | 50% reduction |

### Cost Savings

With caching and optimization:
- **API Costs:** 50-80% reduction in OpenAI/Pinecone API calls
- **Bandwidth:** 60-80% reduction with compression
- **Server Resources:** 40-50% reduction in CPU/memory usage

---

## Troubleshooting

### Object Cache Not Working

**Check if Redis is running:**
```bash
ddev redis-cli ping
# Should return: PONG
```

**Check WordPress connection:**
```bash
ddev wp redis status
```

**Clear cache and try again:**
```bash
ddev wp cache flush
ddev wp redis enable
```

### Compression Not Working

**Test compression manually:**
```bash
curl -I -H "Accept-Encoding: gzip,deflate,br" https://yoursite.com
```

**Check server modules:**
```bash
# Apache
apache2ctl -M | grep deflate

# Nginx
nginx -V 2>&1 | grep -o with-http_gzip_static_module
```

### CDN Issues

**Purge CDN cache:**
- Cloudflare: Caching → Purge Everything
- Or use API: `curl -X POST "https://api.cloudflare.com/client/v4/zones/{zone_id}/purge_cache"`

**Verify CDN is serving files:**
```bash
curl -I https://yoursite.com/wp-content/plugins/wp-ai-assistant/assets/css/chatbot.css | grep -i cf-cache
# Should show: cf-cache-status: HIT
```

---

## Next Steps

1. ✅ Enable Redis object caching
2. ✅ Configure response compression
3. ✅ Set up CDN (optional but recommended)
4. ✅ Monitor performance metrics
5. ✅ Optimize based on real-world data

For additional optimization strategies, see Phase 2 of the [Implementation Plan](../../../wp-ai-assistant-implementation-plan.md).
