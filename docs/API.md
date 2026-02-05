# Semantic Knowledge API Documentation

Complete API reference for the Semantic Knowledge plugin including REST endpoints, PHP classes, JavaScript APIs, and WordPress hooks.

## Table of Contents

- [REST API](#rest-api)
- [PHP Class API](#php-class-api)
- [JavaScript API](#javascript-api)
- [WordPress Hooks](#wordpress-hooks)
- [Template Functions](#template-functions)
- [WP-CLI Commands](#wp-cli-commands)

## REST API

### Authentication

All REST API endpoints use WordPress nonce validation for CSRF protection.

#### For Public Endpoints (Chat, Search)

**Header Method** (Recommended):
```http
POST /wp-json/semantic-knowledge/v1/chat
X-WP-Nonce: {nonce}
Content-Type: application/json
```

**Parameter Method**:
```http
POST /wp-json/semantic-knowledge/v1/chat
Content-Type: application/json

{
  "question": "...",
  "nonce": "{nonce}"
}
```

**Generate Nonce**:
```php
$nonce = wp_create_nonce('wp_rest');
```

```javascript
// Available in localized script
const nonce = wpAiAssistantChatbot.nonce;
```

#### For Indexer Endpoint

**Header Method**:
```http
GET /wp-json/semantic-knowledge/v1/indexer/settings
X-Indexer-Key: {secret_key}
```

**Parameter Method**:
```http
GET /wp-json/semantic-knowledge/v1/indexer/settings?indexer_key={secret_key}
```

**Secret Key**: Set via `Semantic_Knowledge_INDEXER_KEY` environment variable

### Endpoints

#### POST /semantic-knowledge/v1/chat

Submit a question to the AI chatbot.

**Request:**
```http
POST /wp-json/semantic-knowledge/v1/chat
X-WP-Nonce: abc123
Content-Type: application/json

{
  "question": "What services do you offer?",
  "top_k": 5
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `question` | string | Yes | User's question (max 1000 chars) |
| `top_k` | integer | No | Number of context results (default: 5) |
| `nonce` | string | No | WordPress nonce (if not in header) |

**Response (Success - 200):**
```json
{
  "answer": "<p><strong>We offer</strong> web development...</p>",
  "sources": [
    {
      "title": "Services",
      "url": "https://example.com/services",
      "score": 0.87
    }
  ]
}
```

**Response (Error - 400):**
```json
{
  "code": "semantic_knowledge_question_too_long",
  "message": "Question must be 1000 characters or less.",
  "data": {
    "status": 400
  }
}
```

**Response (Error - 403):**
```json
{
  "code": "semantic_knowledge_invalid_nonce",
  "message": "Invalid security token. Please refresh the page and try again.",
  "data": {
    "status": 403
  }
}
```

**Response (Error - 429):**
```json
{
  "code": "semantic_knowledge_rate_limit_exceeded",
  "message": "Rate limit exceeded. Please wait before making another request. Limit: 10 requests per 60 seconds.",
  "data": {
    "status": 429
  }
}
```

**Response (Error - 500):**
```json
{
  "code": "semantic_knowledge_not_configured",
  "message": "Chatbot API keys are missing.",
  "data": {
    "status": 500
  }
}
```

**Rate Limits:**
- 10 requests per 60 seconds per IP address
- Configurable via `semantic_knowledge_chatbot_rate_limit` and `semantic_knowledge_chatbot_rate_window` filters

#### POST /semantic-knowledge/v1/search

Perform an AI-powered semantic search.

**Request:**
```http
POST /wp-json/semantic-knowledge/v1/search
X-WP-Nonce: abc123
Content-Type: application/json

{
  "query": "WordPress development",
  "top_k": 10
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `query` | string | Yes | Search query (max 1000 chars) |
| `top_k` | integer | No | Number of results (default: 10) |
| `nonce` | string | No | WordPress nonce (if not in header) |

**Response (Success - 200):**
```json
{
  "query": "WordPress development",
  "summary": "<p><strong>WordPress development services include...</strong></p>",
  "results": [
    {
      "post_id": 123,
      "title": "WordPress Development Services",
      "url": "https://example.com/wordpress-dev",
      "excerpt": "We specialize in custom WordPress...",
      "score": 0.92
    }
  ],
  "total": 5
}
```

**Error Responses**: Same structure as chat endpoint

**Rate Limits**: Same as chat endpoint

#### GET /semantic-knowledge/v1/indexer/settings

Retrieve plugin settings for the Node.js indexer. Authenticated endpoint.

**Request:**
```http
GET /wp-json/semantic-knowledge/v1/indexer/settings
X-Indexer-Key: your-secure-key-here
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `indexer_key` | string | No | Indexer authentication key (if not in header) |

**Response (Success - 200):**
```json
{
  "wordpress_url": "https://example.com",
  "openai_api_key": "sk-...",
  "pinecone_api_key": "...",
  "pinecone_index_host": "https://index-abc.pinecone.io",
  "pinecone_index_name": "my-index",
  "embedding_model": "text-embedding-3-small",
  "embedding_dimension": 1536,
  "post_types": ["posts", "pages"],
  "post_types_exclude": ["attachment", "revision"],
  "auto_discover": true,
  "clean_deleted": true,
  "chunk_size": 1200,
  "chunk_overlap": 200
}
```

**Response (Error - 403):**
```json
{
  "code": "wp_ai_indexer_invalid_key",
  "message": "Invalid indexer key.",
  "data": {
    "status": 403
  }
}
```

**Caching**: Response cached for 5 minutes

## PHP Class API

### Core Classes

#### Semantic_Knowledge_Core

Main settings and configuration manager.

**Instantiation:**
```php
$core = new Semantic_Knowledge_Core();
```

**Methods:**

##### `get_settings(): array`

Get all plugin settings.

```php
$settings = $core->get_settings();
// Returns: ['chatbot_enabled' => true, 'search_enabled' => false, ...]
```

##### `get_setting( string $key, mixed $default = '' ): mixed`

Get a specific setting.

```php
$model = $core->get_setting('chatbot_model', 'gpt-4o-mini');
// Returns: 'gpt-4o-mini' or configured value
```

##### `save_settings( array $settings ): bool`

Save settings to database and clear cache.

```php
$settings['chatbot_enabled'] = true;
$success = $core->save_settings($settings);
// Returns: true on success
```

##### `validate_settings( array $settings ): array|WP_Error`

Validate settings before saving.

```php
$validated = $core->validate_settings($input);
if (is_wp_error($validated)) {
    echo $validated->get_error_message();
}
```

##### `get_current_domain(): string`

Get current WordPress domain for multi-site filtering.

```php
$domain = $core->get_current_domain();
// Returns: 'example.com'
```

##### `is_configured(): bool`

Check if plugin is properly configured.

```php
if ($core->is_configured()) {
    // Plugin ready to use
}
```

#### Semantic_Knowledge_OpenAI

OpenAI API integration for embeddings and chat.

**Instantiation:**
```php
$core = new Semantic_Knowledge_Core();
$secrets = new Semantic_Knowledge_Secrets();
$openai = new Semantic_Knowledge_OpenAI($core, $secrets);
```

**Methods:**

##### `create_embedding( string $text ): array|WP_Error`

Create embedding vector for text.

```php
$embedding = $openai->create_embedding('Hello world');
if (is_wp_error($embedding)) {
    echo $embedding->get_error_message();
} else {
    // $embedding is array of 1536 floats
    echo count($embedding); // 1536
}
```

**Returns**: Array of floats (embedding vector) or WP_Error

**Errors**:
- `semantic_knowledge_missing_key` - API key not configured
- `semantic_knowledge_openai_error` - API request failed

##### `chat_completion( string $question, string $context, array $options = [] ): string|WP_Error`

Generate chat completion with context.

```php
$answer = $openai->chat_completion(
    'What services do you offer?',
    'Context: We offer web development, design, and hosting.',
    [
        'model' => 'gpt-4o-mini',
        'temperature' => 0.2,
        'system_prompt' => 'You are a helpful assistant.'
    ]
);

if (is_wp_error($answer)) {
    echo $answer->get_error_message();
} else {
    echo $answer; // "We offer web development, design, and hosting services."
}
```

**Parameters**:
- `$question` - User's question
- `$context` - Context from vector search
- `$options` - Optional configuration:
  - `model` - OpenAI model (default: from settings)
  - `temperature` - Randomness 0.0-2.0 (default: from settings)
  - `system_prompt` - System instructions (default: from settings)

**Returns**: Answer string or WP_Error

##### `is_configured(): bool`

Check if OpenAI is configured with API key.

```php
if ($openai->is_configured()) {
    // API key is set
}
```

#### Semantic_Knowledge_Pinecone

Pinecone vector database integration.

**Instantiation:**
```php
$core = new Semantic_Knowledge_Core();
$secrets = new Semantic_Knowledge_Secrets();
$pinecone = new Semantic_Knowledge_Pinecone($core, $secrets);
```

**Methods:**

##### `query_with_domain_filter( array $vector, int $top_k ): array|WP_Error`

Query Pinecone with automatic domain filtering (recommended).

```php
$embedding = [/* 1536 floats */];
$matches = $pinecone->query_with_domain_filter($embedding, 5);

if (is_wp_error($matches)) {
    echo $matches->get_error_message();
} else {
    foreach ($matches as $match) {
        echo $match['metadata']['title'];
        echo $match['score'];
    }
}
```

**Returns**: Array of matches or WP_Error

**Match Structure**:
```php
[
    [
        'id' => 'post-123-chunk-0',
        'score' => 0.87,
        'metadata' => [
            'post_id' => 123,
            'title' => 'Page Title',
            'url' => 'https://example.com/page',
            'chunk' => 'Page content...',
            'post_type' => 'page',
            'domain' => 'example.com'
        ]
    ]
]
```

##### `query( array $vector, int $top_k, array $filter = [] ): array|WP_Error`

Query Pinecone with custom filter.

```php
$matches = $pinecone->query($embedding, 10, [
    'post_type' => ['$eq' => 'page']
]);
```

##### `format_matches( array $matches ): array`

Format raw Pinecone matches for display.

```php
$formatted = $pinecone->format_matches($matches);
// Returns simplified array with id, score, title, url, chunk, post_id, post_type, domain
```

##### `get_index_stats(): array|WP_Error`

Get Pinecone index statistics (for debugging).

```php
$stats = $pinecone->get_index_stats();
print_r($stats);
```

##### `is_configured(): bool`

Check if Pinecone is fully configured.

```php
if ($pinecone->is_configured()) {
    // API key and index configured
}
```

#### Semantic_Knowledge_Cache

Unified caching layer with Redis/Memcached support.

**All methods are static.**

**Methods:**

##### `get( string $key ): mixed|false`

Retrieve value from cache.

```php
$value = Semantic_Knowledge_Cache::get('my_key');
if ($value !== false) {
    // Cache hit
}
```

##### `set( string $key, mixed $value, int $ttl = 900 ): bool`

Store value in cache.

```php
$success = Semantic_Knowledge_Cache::set('my_key', $data, 3600);
// Cache for 1 hour
```

**Default TTL**: 900 seconds (15 minutes)

##### `delete( string $key ): bool`

Delete value from cache.

```php
Semantic_Knowledge_Cache::delete('my_key');
```

##### `flush_all(): bool`

Flush all plugin caches.

```php
Semantic_Knowledge_Cache::flush_all();
```

##### `get_embedding( string $text ): array|false`

Get cached embedding for text.

```php
$embedding = Semantic_Knowledge_Cache::get_embedding('Hello world');
if ($embedding === false) {
    // Cache miss - create embedding
}
```

##### `set_embedding( string $text, array $embedding, int $ttl = 3600 ): bool`

Cache an embedding.

```php
Semantic_Knowledge_Cache::set_embedding($text, $embedding, 3600);
// Cache for 1 hour
```

##### `get_query_results( array $embedding, int $top_k ): array|false`

Get cached query results.

```php
$results = Semantic_Knowledge_Cache::get_query_results($embedding, 5);
```

##### `set_query_results( array $embedding, int $top_k, array $results, int $ttl = 900 ): bool`

Cache query results.

```php
Semantic_Knowledge_Cache::set_query_results($embedding, 5, $results, 900);
// Cache for 15 minutes
```

##### `get_stats(): array`

Get cache statistics.

```php
$stats = Semantic_Knowledge_Cache::get_stats();
print_r($stats);
// ['using_object_cache' => true, 'cache_type' => 'Redis/Memcached', ...]
```

**Constants**:
- `Semantic_Knowledge_Cache::DEFAULT_TTL` - 900 seconds (hot cache)
- `Semantic_Knowledge_Cache::WARM_CACHE_TTL` - 3600 seconds (warm cache)

### Module Classes

#### Semantic_Knowledge_Chatbot_Module

Chatbot functionality and REST API.

**Not typically instantiated directly** (handled by main plugin class).

**Public Methods:**

##### `handle_chat_query( WP_REST_Request $request ): WP_REST_Response|WP_Error`

Handle chatbot REST API request.

**Used internally by REST API.**

##### `render_shortcode( array $atts ): string`

Render chatbot shortcode.

```php
echo do_shortcode('[ai_chatbot mode="popup" button="Chat with us"]');
```

**Shortcode Attributes**:
- `mode` - 'inline' or 'popup' (default: 'inline')
- `button` - Button text for popup mode (default: 'Chat with AI')

#### Semantic_Knowledge_Search_Module

Search functionality and REST API.

**Public Methods:**

##### `handle_search_query( WP_REST_Request $request ): WP_REST_Response|WP_Error`

Handle search REST API request.

**Used internally by REST API.**

##### `render_shortcode( array $atts ): string`

Render search shortcode.

```php
echo do_shortcode('[ai_search placeholder="Search our site..." button="Search"]');
```

**Shortcode Attributes**:
- `placeholder` - Input placeholder text
- `button` - Submit button text

##### `get_search_summary( WP_Query|null $query = null ): string|null` (static)

Get AI summary for search query (theme integration).

```php
$summary = Semantic_Knowledge_Search_Module::get_search_summary();
if ($summary) {
    echo '<div class="ai-summary">' . wp_kses_post($summary) . '</div>';
}
```

##### `is_ai_search( WP_Query|null $query = null ): bool` (static)

Check if query is AI-powered search.

```php
if (Semantic_Knowledge_Search_Module::is_ai_search()) {
    // Show AI indicator
}
```

## JavaScript API

### Chatbot (`chatbot.js`)

The chatbot JavaScript automatically initializes when included.

**Configuration** (via `wp_localize_script`):
```php
wp_localize_script('semantic-knowledge-chatbot', 'wpAiAssistantChatbot', [
    'endpoint' => rest_url('semantic-knowledge/v1/chat'),
    'nonce' => wp_create_nonce('wp_rest'),
    'topK' => 5,
    'enableFloatingButton' => true,
    'introMessage' => '<p>Hi! How can I help?</p>',
    'inputPlaceholder' => 'Ask a question...',
    'deepChatUrl' => 'https://cdn.jsdelivr.net/npm/deep-chat@2.3.0/dist/deepChat.bundle.js'
]);
```

**Features**:
- Lazy loads Deep Chat library on first interaction
- Automatic popup creation for floating button
- Focus management and keyboard navigation
- Screen reader announcements

**No public API** - fully self-contained

### Search (`search.js`)

The search JavaScript automatically initializes when included.

**Configuration** (via `wp_localize_script`):
```php
wp_localize_script('semantic-knowledge-search', 'wpAiAssistantSearch', [
    'endpoint' => rest_url('semantic-knowledge/v1/search'),
    'nonce' => wp_create_nonce('wp_rest'),
    'topK' => 10
]);
```

**Features**:
- AJAX search form submission
- Results rendering with accessibility
- Error handling and recovery
- XSS protection via HTML escaping

**No public API** - fully self-contained

## WordPress Hooks

See [HOOKS.md](HOOKS.md) for complete filter and action reference.

### Most Used Filters

#### `semantic_knowledge_chatbot_answer`

Modify chatbot answer before returning.

```php
add_filter('semantic_knowledge_chatbot_answer', function($answer, $question, $context) {
    // Add call-to-action
    $answer .= '<p><a href="/contact">Contact us for more info</a></p>';
    return $answer;
}, 10, 3);
```

#### `semantic_knowledge_search_results`

Modify search results.

```php
add_filter('semantic_knowledge_search_results', function($results, $query, $matches) {
    // Boost certain post types
    usort($results, function($a, $b) {
        // Your custom sorting
    });
    return $results;
}, 10, 3);
```

#### `semantic_knowledge_search_relevance_config`

Customize relevance boosting algorithm.

```php
add_filter('semantic_knowledge_search_relevance_config', function($config, $query) {
    // Add custom post type boost
    $config['post_type_boosts']['case_study'] = 0.10;

    // Add custom rule
    $config['custom_rules']['industry_boost'] = [
        'match' => '/healthcare/',
        'boost' => 0.15
    ];

    return $config;
}, 10, 2);
```

### Most Used Actions

#### `semantic_knowledge_search_query_end`

Track search queries for analytics.

```php
add_action('semantic_knowledge_search_query_end', function($response, $query) {
    // Send to analytics
    if (function_exists('ga_send_event')) {
        ga_send_event('AI Search', $query, $response['total']);
    }
}, 10, 2);
```

#### `semantic_knowledge_chatbot_before_log`

Intercept before logging chat.

```php
add_action('semantic_knowledge_chatbot_before_log', function($question, $answer, $sources) {
    // Custom logging
    error_log("Chat: $question");
}, 10, 3);
```

## Template Functions

These functions are available globally for theme integration.

### `semantic_knowledge_get_search_summary( WP_Query|null $query = null ): string|null`

Get AI-generated summary for search results.

**Usage in `search.php`:**
```php
<?php if (have_posts()) : ?>
    <?php
    $summary = semantic_knowledge_get_search_summary();
    if ($summary) :
    ?>
        <div class="ai-search-summary">
            <h2>AI Summary</h2>
            <?php echo wp_kses_post($summary); ?>
        </div>
    <?php endif; ?>

    <?php while (have_posts()) : the_post(); ?>
        <!-- Regular search results -->
    <?php endwhile; ?>
<?php endif; ?>
```

**Returns**: HTML summary or null if not available

### `semantic_knowledge_is_search( WP_Query|null $query = null ): bool`

Check if current query is AI-powered search.

**Usage:**
```php
<?php if (semantic_knowledge_is_search()) : ?>
    <div class="ai-badge">AI-Powered Results</div>
<?php endif; ?>
```

### `semantic_knowledge_the_search_summary( array $args = [] ): void`

Display formatted AI summary with wrapper HTML.

**Usage:**
```php
<?php
semantic_knowledge_the_search_summary([
    'before' => '<div class="ai-summary">',
    'after' => '</div>',
    'title' => '<h2>AI Summary</h2>',
    'show_badge' => true,
    'badge_text' => 'AI-Generated'
]);
?>
```

**Parameters**:
- `before` - HTML before summary (default: `<div class="ai-search-summary">`)
- `after` - HTML after summary (default: `</div>`)
- `title` - Title HTML (default: `<h2>AI Summary</h2>`)
- `content_before` - HTML before content (default: `<div class="ai-search-summary__content">`)
- `content_after` - HTML after content (default: `</div>`)
- `show_badge` - Show AI badge (default: true)
- `badge_text` - Badge text (default: 'AI-Generated')

## WP-CLI Commands

### `wp sk-indexer index`

Index all WordPress content to Pinecone.

**Usage:**
```bash
wp sk-indexer index
wp sk-indexer index --debug
wp sk-indexer index --since=2024-01-01
```

**Options**:
- `--debug` - Enable debug logging
- `--since=<date>` - Only index posts modified since date (ISO format)

**Example Output**:
```
Indexing WordPress content...
✓ Fetched settings from WordPress
✓ Found 150 posts to index
✓ Indexed 150 posts (450 chunks)
✓ Created 450 embeddings
✓ Stored 450 vectors in Pinecone
Success: Indexing complete
```

### `wp sk-indexer clean`

Remove deleted posts from Pinecone index.

**Usage:**
```bash
wp sk-indexer clean
wp sk-indexer clean --debug
```

**Options**:
- `--debug` - Enable debug logging

### `wp sk-indexer delete-all`

Delete all vectors for current domain from Pinecone.

**Usage:**
```bash
wp sk-indexer delete-all
wp sk-indexer delete-all --yes
```

**Options**:
- `--yes` - Skip confirmation prompt

**Warning**: This is destructive and cannot be undone.

### `wp sk-indexer check`

Verify system requirements and configuration.

**Usage:**
```bash
wp sk-indexer check
```

**Checks**:
- Node.js installed (v18+)
- Indexer package installed
- API keys configured
- Pinecone index accessible

### `wp sk-indexer config`

Display current configuration.

**Usage:**
```bash
wp sk-indexer config
```

**Output**:
```
WordPress URL: https://example.com
Embedding Model: text-embedding-3-small
Embedding Dimension: 1536
Pinecone Index: my-index
Post Types: posts, pages
Chunk Size: 1200
Chunk Overlap: 200
```

---

## Usage Examples

### Complete Chatbot Integration

```php
// In your theme's functions.php
add_action('wp_footer', function() {
    if (is_front_page()) {
        echo do_shortcode('[ai_chatbot mode="popup" button="Need help?"]');
    }
});

// Customize chatbot response
add_filter('semantic_knowledge_chatbot_answer', function($answer, $question, $context) {
    // Add signature
    $answer .= '<p><em>- Your Friendly AI Assistant</em></p>';
    return $answer;
}, 10, 3);

// Track chatbot usage
add_action('semantic_knowledge_chatbot_query_end', function($response, $question) {
    update_option('chatbot_query_count', get_option('chatbot_query_count', 0) + 1);
}, 10, 2);
```

### Complete Search Integration

```php
// In search.php template
<?php get_header(); ?>

<div class="search-results">
    <h1>Search Results for: <?php echo get_search_query(); ?></h1>

    <?php
    // Display AI summary if available
    $summary = semantic_knowledge_get_search_summary();
    if ($summary) :
    ?>
        <div class="ai-summary" role="region" aria-label="AI-generated summary">
            <h2>Quick Summary</h2>
            <?php echo wp_kses_post($summary); ?>
        </div>
    <?php endif; ?>

    <?php if (have_posts()) : ?>
        <div class="search-results-list">
            <?php while (have_posts()) : the_post(); ?>
                <article>
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php the_excerpt(); ?>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <p>No results found.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
```

### Custom Relevance Boosting

```php
// Boost industry-specific content
add_filter('semantic_knowledge_search_relevance_config', function($config, $query) {
    $query_lower = strtolower($query);

    // Boost healthcare content for health queries
    if (strpos($query_lower, 'health') !== false ||
        strpos($query_lower, 'medical') !== false) {
        $config['custom_rules']['healthcare_boost'] = [
            'match' => '/healthcare/',
            'boost' => 0.20
        ];
    }

    // Boost case studies
    $config['post_type_boosts']['case_study'] = 0.10;

    return $config;
}, 10, 2);
```

### Analytics Integration

```php
// Track all AI interactions
add_action('semantic_knowledge_chatbot_query_end', 'track_ai_analytics', 10, 2);
add_action('semantic_knowledge_search_query_end', 'track_ai_analytics', 10, 2);

function track_ai_analytics($response, $query) {
    // Send to Google Analytics
    if (function_exists('gtag')) {
        gtag('event', 'ai_interaction', [
            'event_category' => 'AI Assistant',
            'event_label' => $query,
            'value' => isset($response['total']) ? $response['total'] : 1
        ]);
    }

    // Or custom analytics
    $analytics = get_option('ai_analytics', []);
    $analytics[] = [
        'query' => $query,
        'timestamp' => time(),
        'results' => isset($response['total']) ? $response['total'] : 1
    ];
    update_option('ai_analytics', array_slice($analytics, -1000)); // Keep last 1000
}
```

---

For complete hooks reference, see [HOOKS.md](HOOKS.md).

For customization examples, see [CUSTOMIZATION.md](CUSTOMIZATION.md).
