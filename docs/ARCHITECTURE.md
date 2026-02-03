# WP AI Assistant Architecture

Complete system architecture and design documentation for the WP AI Assistant plugin.

## Table of Contents

- [Overview](#overview)
- [System Architecture](#system-architecture)
- [Component Breakdown](#component-breakdown)
- [Data Flow](#data-flow)
- [External Dependencies](#external-dependencies)
- [Security Architecture](#security-architecture)
- [Caching Strategy](#caching-strategy)
- [Performance Considerations](#performance-considerations)
- [Database Schema](#database-schema)
- [Integration Points](#integration-points)

## Overview

WP AI Assistant is a WordPress plugin that provides AI-powered chatbot and semantic search capabilities using Retrieval-Augmented Generation (RAG) architecture. The system combines OpenAI's language models with Pinecone vector database to deliver context-aware responses grounded in your WordPress content.

### Key Features

- **AI Chatbot** - Conversational interface using Deep Chat library
- **Semantic Search** - Vector-based search with relevance boosting
- **RAG Architecture** - Combines retrieval with generation for accurate responses
- **Hybrid Implementation** - PHP backend with Node.js indexer
- **WordPress Integration** - Native WP hooks, REST API, shortcodes

### Technology Stack

- **Backend**: PHP 8.0+ (WordPress plugin architecture)
- **Frontend**: JavaScript (ES6+), Deep Chat library
- **Indexer**: Node.js 18+ (TypeScript)
- **APIs**: OpenAI (embeddings & chat), Pinecone (vector database)
- **Testing**: PHPUnit 9.5, Brain Monkey
- **Standards**: WordPress Coding Standards, PSR-12

## System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Frontend                        │
│  ┌──────────────┐                    ┌──────────────┐       │
│  │   Chatbot    │                    │    Search    │       │
│  │  (Deep Chat) │                    │   Interface  │       │
│  └──────┬───────┘                    └──────┬───────┘       │
│         │                                   │               │
│         └───────────────┬───────────────────┘               │
│                         │                                   │
│                         ↓                                   │
│            ┌────────────────────────┐                       │
│            │   WordPress REST API   │                       │
│            │  (Nonce Protection)    │                       │
│            └────────────┬───────────┘                       │
└─────────────────────────┼───────────────────────────────────┘
                          │
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                   WP AI Assistant Core                       │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │  WP_AI_Core │  │ WP_AI_Secrets│  │ WP_AI_Cache  │       │
│  │  (Settings) │  │  (API Keys)  │  │  (Redis/DB)  │       │
│  └─────────────┘  └──────────────┘  └──────────────┘       │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │              Module Layer                           │    │
│  │  ┌──────────────────┐  ┌──────────────────┐        │    │
│  │  │  Chatbot Module  │  │   Search Module  │        │    │
│  │  └──────────────────┘  └──────────────────┘        │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │            Integration Layer                         │    │
│  │  ┌───────────────┐         ┌───────────────┐       │    │
│  │  │  WP_AI_OpenAI │         │ WP_AI_Pinecone│       │    │
│  │  │  (Embeddings  │         │  (Vector      │       │    │
│  │  │   & Chat)     │         │   Queries)    │       │    │
│  │  └───────┬───────┘         └───────┬───────┘       │    │
│  └──────────┼─────────────────────────┼───────────────┘    │
└─────────────┼─────────────────────────┼─────────────────────┘
              │                         │
              ↓                         ↓
┌─────────────────────────┐  ┌──────────────────────┐
│   OpenAI API            │  │   Pinecone API       │
│  - text-embedding-3     │  │  - Vector Storage    │
│  - gpt-4o-mini          │  │  - Similarity Search │
└─────────────────────────┘  └──────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│               Indexing Pipeline (Node.js)                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  REST API → Fetch Posts → Chunk Text → Create        │   │
│  │  Embeddings → Store in Pinecone (with metadata)      │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Component Layers

#### Presentation Layer
- **Chatbot UI** - Deep Chat web component with custom styling
- **Search Interface** - Form-based search with AJAX results
- **WordPress Admin** - Settings pages, chat logs, search logs

#### Application Layer
- **REST API Controllers** - Handle chatbot and search requests
- **Module System** - Chatbot and Search modules with independent lifecycles
- **Template Functions** - Helper functions for theme integration

#### Service Layer
- **OpenAI Integration** - Embedding creation and chat completions
- **Pinecone Integration** - Vector queries with domain filtering
- **Cache Service** - Multi-tier caching (Redis/Memcached/Transients)
- **Logger** - Structured logging with retention policies

#### Data Layer
- **WordPress Database** - Settings, logs, custom tables
- **Object Cache** - Redis/Memcached for hot data
- **Pinecone** - Vector embeddings and metadata

## Component Breakdown

### PHP Classes

#### Core Components

**WP_AI_Core** (`includes/class-wp-ai-core.php`)
- Settings management with validation
- Configuration caching (1-hour TTL)
- Domain detection for multi-site filtering
- Default settings schema

**WP_AI_Secrets** (`includes/class-wp-ai-secrets.php`)
- Secure API key retrieval
- Multi-source resolution: Environment vars → PHP constants → Pantheon Secrets
- Never stores secrets in database

**WP_AI_Cache** (`includes/class-wp-ai-cache.php`)
- Unified caching API
- Automatic Redis/Memcached detection
- Embedding cache (1-hour TTL)
- Query results cache (15-minute TTL)
- Cache versioning for invalidation

**WP_AI_Database** (`includes/class-wp-ai-database.php`)
- Custom tables for chat/search logs
- Optimized indexes for performance
- Retention policy enforcement
- Migration system

**WP_AI_Logger** (`includes/class-wp-ai-logger.php`)
- Structured logging
- Log rotation
- Retention policies (default: 90 days)
- WP_DEBUG integration

**WP_AI_Assets** (`includes/class-wp-ai-assets.php`)
- Asset optimization (minification)
- Lazy loading support
- CSP nonce injection

#### API Integration

**WP_AI_OpenAI** (`includes/class-wp-ai-openai.php`)
- Embedding creation (text-embedding-3-small/large)
- Chat completions (gpt-4o-mini)
- Error handling and retry logic
- Request/response logging

**WP_AI_Pinecone** (`includes/class-wp-ai-pinecone.php`)
- Vector similarity queries
- Domain-based filtering (multi-site safe)
- Metadata enrichment
- Index statistics

#### Feature Modules

**WP_AI_Chatbot_Module** (`includes/modules/class-wp-ai-chatbot-module.php`)
- REST API endpoint: `/ai-assistant/v1/chat`
- Deep Chat integration
- Conversation logging
- Rate limiting (10 req/min per IP)
- CSRF protection (nonce validation)
- Source citation

**WP_AI_Search_Module** (`includes/modules/class-wp-ai-search-module.php`)
- REST API endpoint: `/ai-assistant/v1/search`
- Semantic search
- Relevance boosting (URL, title, post type)
- AI-generated summaries
- WordPress search integration
- Rate limiting (10 req/min per IP)

#### Supporting Classes

**WP_AI_Settings** (`includes/class-wp-ai-settings.php`)
- Admin settings page
- Settings validation
- Default value management

**WP_AI_Indexer_Controller** (`includes/class-wp-ai-indexer-controller.php`)
- REST API for indexer: `/ai-assistant/v1/indexer/settings`
- Authenticated with WP_AI_INDEXER_KEY
- Settings export for Node.js indexer
- Response caching (5-minute TTL)

**WP_AI_System_Check** (`includes/class-wp-ai-system-check.php`)
- Environment validation
- Node.js detection
- Indexer package verification
- Admin notices

**WP_AI_Migration** (`includes/migrations/class-wp-ai-migration.php`)
- Database schema updates
- Data retention policies
- Cleanup cron jobs

**WP_AI_CLI** (`includes/class-wp-ai-cli.php`)
- WP-CLI commands wrapper
- Executes Node.js indexer
- Streams output to console
- Environment variable passing

### JavaScript Modules

**chatbot.js** (`assets/js/chatbot.js`)
- Deep Chat lazy loading
- Popup/inline modes
- Focus management
- Screen reader announcements
- Keyboard navigation (Tab, Escape)

**search.js** (`assets/js/search.js`)
- AJAX search requests
- Results rendering
- Error handling
- Accessibility (ARIA live regions)

### Node.js Indexer

**@kanopi/wp-ai-indexer** (separate npm package)
- Fetches WordPress content via REST API
- Chunks text (default: 1200 chars, 200 overlap)
- Creates embeddings via OpenAI
- Stores vectors in Pinecone with metadata
- Cleans deleted posts
- Progress tracking

## Data Flow

### Chatbot Query Flow

```
1. User submits question
   ↓
2. JavaScript sends POST to /ai-assistant/v1/chat
   ↓
3. WP_AI_Chatbot_Module::handle_chat_query()
   - Validates nonce (CSRF protection)
   - Checks rate limits
   - Sanitizes input
   ↓
4. Create embedding (with cache check)
   - Check WP_AI_Cache::get_embedding($question)
   - If miss: WP_AI_OpenAI::create_embedding($question)
   - Cache result (1 hour)
   ↓
5. Query Pinecone (with cache check)
   - Check WP_AI_Cache::get_query_results($embedding, $top_k)
   - If miss: WP_AI_Pinecone::query_with_domain_filter($embedding, $top_k)
   - Cache result (15 minutes)
   ↓
6. Build context from top matches
   - Extract chunks and metadata
   - Format as context string
   ↓
7. Generate answer
   - WP_AI_OpenAI::chat_completion($question, $context)
   - Apply system prompt
   - Temperature: 0.2 (focused responses)
   ↓
8. Log interaction
   - WP_AI_Database::log_chat($question, $answer, $sources)
   ↓
9. Return JSON response
   {
     "answer": "...",
     "sources": [{"title": "...", "url": "..."}]
   }
```

### Search Query Flow

```
1. User submits search query
   ↓
2. JavaScript sends POST to /ai-assistant/v1/search
   ↓
3. WP_AI_Search_Module::handle_search_query()
   - Validates nonce
   - Checks rate limits
   - Sanitizes input
   ↓
4. Create embedding (with cache check)
   - Same as chatbot flow
   ↓
5. Query Pinecone (with cache check)
   - Same as chatbot flow
   ↓
6. Filter by minimum score (default: 0.5)
   ↓
7. Apply relevance boosting
   - URL slug match boost (+0.15)
   - Exact title match boost (+0.12)
   - All-words title boost (+0.08)
   - Post type boost (page: +0.05)
   - Custom filters (via wp_ai_search_relevance_config)
   ↓
8. Re-rank results by boosted scores
   ↓
9. Generate AI summary (if enabled)
   - Build context from top 5 results
   - WP_AI_OpenAI::chat_completion() with search prompt
   - Temperature: 0.3
   ↓
10. Log search
    - WP_AI_Database::log_search($query, $results)
    ↓
11. Return JSON response
    {
      "query": "...",
      "summary": "...",
      "results": [...],
      "total": N
    }
```

### Indexing Flow

```
1. Trigger: wp ai-indexer index (WP-CLI)
   ↓
2. WP_AI_CLI::run_command('index')
   - Detects indexer location (monorepo → local → global)
   - Passes environment variables
   - Executes: npx wp-ai-indexer index
   ↓
3. Node.js Indexer starts
   ↓
4. Fetch settings from WordPress
   - GET /ai-assistant/v1/indexer/settings
   - Authenticate with WP_AI_INDEXER_KEY
   - Retrieve: post types, chunk size, API keys, etc.
   ↓
5. Fetch WordPress posts
   - GET /wp-json/wp/v2/{post_type}
   - Paginated requests (100 per page)
   - Filter by published status
   ↓
6. Process each post
   - Extract title, content, URL
   - Chunk text (default: 1200 chars, 200 overlap)
   ↓
7. Create embeddings
   - Batch requests to OpenAI
   - Model: text-embedding-3-small
   - Dimensions: 1536
   ↓
8. Store in Pinecone
   - Upsert vectors with metadata:
     {
       id: "post-{id}-chunk-{n}",
       values: [...embedding...],
       metadata: {
         post_id, title, url, chunk,
         post_type, domain, indexed_at
       }
     }
   ↓
9. Complete
   - Report statistics
   - Return exit code
```

## External Dependencies

### OpenAI API

**Purpose**: Embeddings and chat completions

**Endpoints Used**:
- `POST https://api.openai.com/v1/embeddings`
- `POST https://api.openai.com/v1/chat/completions`

**Models**:
- `text-embedding-3-small` (default, 1536 dimensions)
- `text-embedding-3-large` (3072 dimensions, optional)
- `text-embedding-ada-002` (legacy, 1536 dimensions)
- `gpt-4o-mini` (default chat model)

**Rate Limits**: Tier-based (see OpenAI dashboard)

**Cost Considerations**:
- Embeddings: ~$0.02 per 1M tokens
- Chat: ~$0.15 per 1M input tokens, ~$0.60 per 1M output tokens
- Caching reduces API calls by ~80%

### Pinecone Vector Database

**Purpose**: Vector storage and similarity search

**Endpoints Used**:
- `POST https://{index-host}/query`
- `POST https://{index-host}/describe_index_stats`

**Index Requirements**:
- Dimensions: Match embedding model (1536 or 3072)
- Metric: Cosine similarity
- Pod type: Serverless recommended
- Metadata filtering: Required

**Rate Limits**: Plan-based

**Cost Considerations**:
- Serverless: Pay per read/write operations
- Pods: Fixed monthly cost
- Storage: Per GB

### Deep Chat Library

**Purpose**: Chatbot UI component

**Source**: `https://cdn.jsdelivr.net/npm/deep-chat@2.3.0/dist/deepChat.bundle.js`

**Version**: 2.3.0 (pinned)

**Loading**: Lazy loaded on first interaction

**Features Used**:
- Custom message handler
- HTML responses
- Custom styling
- Input configuration

**Accessibility**: Limited (third-party component) - enhanced via custom wrappers

### WordPress Dependencies

**Minimum WordPress**: 5.6+

**APIs Used**:
- REST API (endpoints, nonce validation)
- Settings API
- Transients API
- Options API
- Shortcode API
- WP-CLI

**Hooks**: 30+ filters and actions (see HOOKS.md)

## Security Architecture

### Authentication & Authorization

#### REST API Endpoints

**Chatbot & Search** (`/ai-assistant/v1/chat`, `/ai-assistant/v1/search`):
- **Method**: Nonce validation (`wp_rest` nonce)
- **Transport**: X-WP-Nonce header or nonce parameter
- **Permission**: Public access (with rate limiting)
- **CSRF Protection**: WordPress nonces

**Indexer Settings** (`/ai-assistant/v1/indexer/settings`):
- **Method**: Shared secret key
- **Transport**: X-Indexer-Key header or indexer_key parameter
- **Permission**: Restricted (indexer only)
- **Key Storage**: Environment variable or PHP constant

### API Key Management

**Storage Strategy**: Never in database
1. Environment variables (preferred)
2. PHP constants in wp-config.php
3. Pantheon Secrets (hosting integration)

**Retrieval**: `WP_AI_Secrets::get_secret_or_setting()`

**Keys Required**:
- `OPENAI_API_KEY` - OpenAI API access
- `PINECONE_API_KEY` - Pinecone API access
- `WP_AI_INDEXER_KEY` - Indexer authentication (32+ chars)

### Input Validation & Sanitization

**All User Inputs**:
- Maximum length validation (1000 chars)
- Sanitization (`sanitize_text_field`, `sanitize_textarea_field`)
- XSS protection (escaping on output)

**REST API Parameters**:
- Type validation
- Required field checks
- Custom validation callbacks

**Settings**:
- Comprehensive validation in `WP_AI_Core::validate_settings()`
- Range checks (temperatures, scores, etc.)
- URL validation
- Path existence checks

### Rate Limiting

**Implementation**: Transient-based per-IP tracking

**Default Limits**:
- 10 requests per 60 seconds per IP
- Configurable via filters:
  - `wp_ai_chatbot_rate_limit`
  - `wp_ai_chatbot_rate_window`
  - `wp_ai_search_rate_limit`
  - `wp_ai_search_rate_window`

**IP Detection**:
- Secure proxy header handling
- Trusted proxy validation (`WP_AI_TRUSTED_PROXIES`)
- Prevents header spoofing
- Fallback to REMOTE_ADDR

**Response**: 429 Too Many Requests with Retry-After suggestion

### Content Security Policy (CSP)

**Status**: Optional (disabled by default for compatibility)

**Enable**: Define `WP_AI_ENABLE_CSP` constant or use `wp_ai_assistant_enable_csp` filter

**Directives**:
```
default-src 'self';
script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
img-src 'self' data: https:;
connect-src 'self' https://api.openai.com https://*.pinecone.io;
font-src 'self' data: https://fonts.gstatic.com;
frame-ancestors 'self';
```

**Customization**: `wp_ai_assistant_csp_directives` filter

**Nonces**: Generated per-request for inline scripts

### Data Privacy

**User Data Collection**:
- Chat questions and responses (logged)
- Search queries and results (logged)
- IP addresses (rate limiting, temporary)
- No personal information required

**Data Retention**:
- Logs: 90 days (configurable)
- Cache: 15 minutes to 1 hour
- Transients: Auto-expire

**GDPR Compliance**:
- Minimal data collection
- Configurable retention
- No cookies or tracking
- User can be anonymized

**Third-Party Data Sharing**:
- OpenAI: Questions and context (see OpenAI privacy policy)
- Pinecone: Embeddings and metadata (see Pinecone privacy policy)

## Caching Strategy

### Multi-Tier Caching

#### Tier 1: Object Cache (Redis/Memcached)

**Usage**: Automatic if `wp_using_ext_object_cache()` returns true

**Data Cached**:
- Plugin settings (1 hour TTL)
- Embeddings (1 hour TTL - warm cache)
- Query results (15 minutes TTL - hot cache)
- Indexer settings API response (5 minutes TTL)

**Benefits**:
- 80% reduction in API calls
- Sub-millisecond retrieval
- Shared across web servers

**Setup**: See PERFORMANCE.md

#### Tier 2: WordPress Transients

**Usage**: Fallback when object cache unavailable

**Data Cached**: Same as Tier 1

**Storage**: Database (`wp_options` table)

**Limitations**:
- Slower than object cache
- Database queries required
- Not shared across servers

#### Tier 3: HTTP Caching

**Not implemented** (due to personalization and real-time requirements)

### Cache Keys

**Structure**: `wp_ai_cache_{hash}`

**Hashing**: MD5 for consistent key generation

**Examples**:
- Settings: `wp_ai_assistant_settings_cache`
- Embedding: `wp_ai_cache_embedding_{md5($text)}`
- Query: `wp_ai_cache_query_{md5($embedding)}_k{$top_k}`

### Cache Invalidation

**Automatic Invalidation**:
- Settings change → Clear all caches
- Plugin update → Clear system check cache

**Manual Invalidation**:
- `WP_AI_Cache::flush_all()` - Clear all plugin caches
- `WP_AI_Cache::delete($key)` - Clear specific key

**Version-Based Invalidation**:
- Cache version incremented on settings change
- Old caches become stale automatically

### Performance Impact

**With Object Cache**:
- Embedding retrieval: <1ms (vs 200-500ms API call)
- Query results: <1ms (vs 300-800ms API call)
- Settings: <1ms (vs 5-10ms DB query)

**Without Object Cache**:
- Embedding retrieval: ~10ms (vs 200-500ms API call)
- Query results: ~10ms (vs 300-800ms API call)
- Settings: ~5ms (vs first load)

**Recommendation**: Always use Redis or Memcached in production

## Performance Considerations

### Response Time Targets

**Chatbot**:
- Cold cache: 1.5-2.5 seconds
- Warm cache (embedding cached): 800ms-1.5s
- Hot cache (full cache): 200-400ms

**Search**:
- Cold cache: 1.2-2.0 seconds
- Warm cache: 600ms-1.2s
- Hot cache: 150-300ms

### Optimization Strategies

#### 1. Caching (Highest Impact)

**Object Cache**: 80% latency reduction

**Setup**:
```yaml
# .ddev/config.yaml
services:
  redis:
    type: redis
    version: "7"
```

```bash
composer require wpackagist-plugin/redis-cache
wp plugin activate redis-cache
wp redis enable
```

#### 2. Response Compression

**Gzip/Brotli**: 60-80% bandwidth reduction

**Headers**:
```php
add_filter('wp_ai_assistant_enable_compression', '__return_true');
```

#### 3. Asset Optimization

**Minification**: Automatic in production (when `WP_DEBUG` is false)

**Lazy Loading**: Deep Chat loaded only on interaction

**Deferred Scripts**: All JavaScript uses `defer` strategy

#### 4. Database Optimization

**Custom Tables**: Dedicated tables for logs (not post meta)

**Indexes**:
```sql
INDEX (created_at DESC)  -- Fast log retrieval
INDEX (question(100))    -- Search in logs
```

**Batch Operations**: Bulk inserts for logs

#### 5. API Request Optimization

**Batching**: Indexer processes posts in batches

**Parallel Requests**: Multiple embeddings created concurrently

**Retry Logic**: Exponential backoff for failures

**Timeout Tuning**:
- OpenAI: 30 seconds
- Pinecone: 20 seconds

### Scalability

**Concurrent Users**:
- Rate limit: 10 requests/min per IP
- Object cache: Handles thousands of concurrent reads
- Pinecone: Auto-scales

**Content Volume**:
- Tested: Up to 10,000 posts
- Chunking: Handles large posts (split at 1200 chars)
- Index size: ~1KB per chunk (1536-dimension embedding)

**Multi-Site**:
- Domain filtering ensures site isolation
- Shared Pinecone index (filtered by domain metadata)
- Independent caches per site

### Monitoring

**Metrics to Track**:
- Response times (chat, search)
- Cache hit rates
- API error rates
- Rate limit hits

**Logging**:
- Response times logged per query
- API errors logged with details
- Cache performance logged (WP_DEBUG mode)

**WordPress Debug**:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Database Schema

### Custom Tables

#### `{prefix}_ai_chat_logs`

```sql
CREATE TABLE wp_ai_chat_logs (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  question text NOT NULL,
  answer longtext NOT NULL,
  sources longtext DEFAULT NULL,
  response_time int(11) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY created_at (created_at DESC),
  KEY question (question(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Purpose**: Log chatbot interactions

**Retention**: 90 days (configurable)

#### `{prefix}_ai_search_logs`

```sql
CREATE TABLE wp_ai_search_logs (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  query text NOT NULL,
  results longtext DEFAULT NULL,
  result_count int(11) DEFAULT 0,
  response_time int(11) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY created_at (created_at DESC),
  KEY query (query(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Purpose**: Log search queries

**Retention**: 90 days (configurable)

### WordPress Options

**`wp_ai_assistant_settings`** - Main plugin settings (cached)

**`wp_ai_cache_version`** - Cache invalidation version

**`wp_ai_assistant_system_check`** - Transient for system check results

**`wp_ai_assistant_schema_version`** - Database schema version

### Transients (Caching)

**Prefix**: `_transient_wp_ai_cache_*`

**TTL**: 900-3600 seconds

**Auto-cleanup**: WordPress cron

## Integration Points

### WordPress Hooks

**30+ filters and actions** - See [HOOKS.md](HOOKS.md) for complete reference

**Key Filters**:
- `wp_ai_chatbot_answer` - Modify chatbot responses
- `wp_ai_search_results` - Modify search results
- `wp_ai_search_relevance_config` - Customize relevance boosting

**Key Actions**:
- `wp_ai_chatbot_query_start` - Track chatbot usage
- `wp_ai_search_query_end` - Analytics integration

### REST API

**Endpoints**:
- `POST /ai-assistant/v1/chat` - Chatbot queries
- `POST /ai-assistant/v1/search` - Search queries
- `GET /ai-assistant/v1/indexer/settings` - Indexer configuration

### Shortcodes

**`[ai_chatbot]`** - Embed chatbot (inline or popup)

**`[ai_search]`** - Embed search form

### Template Functions

**`wp_ai_get_search_summary()`** - Retrieve AI summary in search.php

**`wp_ai_is_search()`** - Check if query is AI search

**`wp_ai_the_search_summary()`** - Display formatted summary

### WP-CLI

**`wp ai-indexer index`** - Index content

**`wp ai-indexer clean`** - Clean deleted posts

**`wp ai-indexer delete-all`** - Delete all vectors

**`wp ai-indexer check`** - Verify system requirements

**`wp ai-indexer config`** - Show configuration

### JavaScript Events

**Deep Chat Events**:
- Response received
- Error occurred
- Input submitted

**Custom Events**: None (uses direct function calls)

---

## Further Reading

- [API Documentation](API.md) - Complete API reference
- [Hooks Reference](HOOKS.md) - Filters and actions
- [Performance Guide](PERFORMANCE.md) - Optimization strategies
- [Customization Guide](CUSTOMIZATION.md) - Extending functionality
