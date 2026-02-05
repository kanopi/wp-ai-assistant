# Configuration Guide: Semantic Knowledge

Detailed configuration reference for administrators setting up and fine-tuning the Semantic Knowledge plugin.

## Table of Contents

- [Environment Variables](#environment-variables)
- [WordPress Admin Settings](#wordpress-admin-settings)
- [Chatbot Configuration](#chatbot-configuration)
- [Search Configuration](#search-configuration)
- [Advanced Settings](#advanced-settings)
- [Performance Tuning](#performance-tuning)
- [Security Settings](#security-settings)
- [Configuration Examples](#configuration-examples)

## Environment Variables

Environment variables provide secure configuration for API keys and sensitive settings.

### Required Variables

These variables MUST be configured for the plugin to function:

```bash
# OpenAI API Key (required)
OPENAI_API_KEY=sk-proj-your-key-here

# Pinecone API Key (required)
PINECONE_API_KEY=your-pinecone-key-here

# Indexer API Key for REST authentication (required for CI/CD)
Semantic_Knowledge_INDEXER_KEY=generate-a-secure-random-32-char-string
```

### Optional Variables

These variables provide additional security and configuration options:

```bash
# Trusted proxy IPs (for rate limiting behind load balancer)
# Only set if you're behind CDN/load balancer and need accurate client IPs
Semantic_Knowledge_TRUSTED_PROXIES=10.0.0.1,10.0.0.2

# Enable Content Security Policy headers (disabled by default for compatibility)
Semantic_Knowledge_ENABLE_CSP=true
```

### Configuration Methods

#### Method 1: .env File (DDEV, Local Development)

Create or edit `.ddev/config.yaml`:

```yaml
web_environment:
  - OPENAI_API_KEY=sk-proj-...
  - PINECONE_API_KEY=...
  - Semantic_Knowledge_INDEXER_KEY=...
  - Semantic_Knowledge_TRUSTED_PROXIES=10.0.0.1
```

Then restart DDEV:
```bash
ddev restart
```

#### Method 2: wp-config.php (PHP Constants)

Add to `wp-config.php` before "That's all, stop editing!":

```php
// Semantic Knowledge Configuration
define('OPENAI_API_KEY', 'sk-proj-...');
define('PINECONE_API_KEY', '...');
define('Semantic_Knowledge_INDEXER_KEY', '...');

// Optional: Trusted proxies for rate limiting
define('Semantic_Knowledge_TRUSTED_PROXIES', '10.0.0.1,10.0.0.2');

// Optional: Enable Content Security Policy
define('Semantic_Knowledge_ENABLE_CSP', true);
```

#### Method 3: Server Environment Variables

**Apache (.htaccess):**
```apache
SetEnv OPENAI_API_KEY "sk-proj-..."
SetEnv PINECONE_API_KEY "..."
SetEnv Semantic_Knowledge_INDEXER_KEY "..."
```

**Nginx (server config):**
```nginx
location / {
    fastcgi_param OPENAI_API_KEY "sk-proj-...";
    fastcgi_param PINECONE_API_KEY "...";
    fastcgi_param Semantic_Knowledge_INDEXER_KEY "...";
}
```

#### Method 4: Hosting Platform Configuration

**Pantheon:**
1. Use Pantheon Secrets (recommended)
2. Or add to `pantheon.yml`

**WP Engine:**
1. Use WP Engine Portal > Environment Variables
2. Available in User Portal > Sites > [Your Site] > Environment Variables

**Kinsta:**
1. MyKinsta > Sites > [Your Site] > Tools > Environment Variables
2. Add key-value pairs

### Security Best Practices

#### Generating Secure Keys

**Indexer API Key:**
```bash
# Linux/macOS
openssl rand -hex 32

# PHP
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# Result example:
a3f8c9d2e7b1f4a6c8d3e9f2b7a5c1d4e8f3a6b9c2d7e1f4a8b3c6d9e2f5a7c1
```

#### Storage Guidelines

**DO:**
- Store in environment variables
- Use hosting platform's secret management
- Add to `.env` file (if file is in `.gitignore`)
- Use wp-config.php with proper file permissions (600)

**DON'T:**
- Commit keys to version control
- Store in WordPress database
- Share keys in documentation or tickets
- Use same keys across environments (dev/staging/prod)

#### Key Rotation

Rotate API keys periodically:

1. Generate new keys in respective dashboards (OpenAI, Pinecone)
2. Update environment variables
3. Test functionality
4. Revoke old keys
5. Monitor for any issues

**Recommended:** Rotate every 90 days or after team member changes

### Verifying Configuration

Check if environment variables are properly configured:

```bash
# Via WP-CLI
wp eval 'echo "OpenAI: " . (getenv("OPENAI_API_KEY") ? "✓" : "✗") . "\n";'
wp eval 'echo "Pinecone: " . (getenv("PINECONE_API_KEY") ? "✓" : "✗") . "\n";'

# Or check in WordPress admin
# Settings > AI Assistant > General > Configuration Status
```

## WordPress Admin Settings

Configuration available through WordPress admin interface.

### Navigation

All settings are located at: **Settings > AI Assistant**

Four tabs:
1. **General** - Core configuration
2. **Chatbot** - Chatbot-specific settings
3. **Search** - Search-specific settings
4. **Indexer** - Content indexing configuration

### General Settings Tab

#### Pinecone Index Host

**Field Type:** URL input
**Required:** Yes
**Format:** `https://[index-name]-[project-id].svc.[region].pinecone.io`

**How to Find:**
1. Log in to Pinecone dashboard
2. Navigate to your index
3. Click "Connect" tab
4. Copy the "Host" value

**Example:**
```
https://my-wp-site-abc123.svc.gcp-starter.pinecone.io
```

**Common Errors:**
- Missing `https://` prefix
- Including API key in URL
- Using project URL instead of index URL

#### Pinecone Index Name

**Field Type:** Text input
**Required:** Yes
**Format:** Lowercase letters, numbers, hyphens

**How to Find:**
- Visible in Pinecone dashboard index list
- Usually matches what you named it during creation

**Example:**
```
my-website-content
wordpress-site-index
production-content-v2
```

**Best Practices:**
- Use descriptive names
- Include environment (prod, staging)
- Version your indexes if making breaking changes

#### Embedding Model

**Field Type:** Dropdown select
**Default:** `text-embedding-3-small`
**Options:**
- `text-embedding-3-small` - 1536 dimensions, $0.02/1M tokens
- `text-embedding-3-large` - 3072 dimensions, $0.13/1M tokens
- `text-embedding-ada-002` - 1536 dimensions, $0.10/1M tokens (legacy)

**Comparison:**

| Model | Quality | Speed | Cost | Recommendation |
|-------|---------|-------|------|----------------|
| text-embedding-3-small | Good | Fast | Low | **Recommended** |
| text-embedding-3-large | Excellent | Fast | Medium | High-quality needs |
| text-embedding-ada-002 | Good | Fast | Medium | Legacy only |

**Important:**
- Must match dimension configured in Pinecone
- Cannot change after initial indexing (requires re-index with new Pinecone index)

#### Embedding Dimension

**Field Type:** Number input
**Default:** 1536
**Options:**
- 1536 (for text-embedding-3-small and ada-002)
- 3072 (for text-embedding-3-large)

**Configuration Match:**

```
WordPress Setting          Pinecone Index Configuration
─────────────────────────────────────────────────────────
Model: text-embedding-3-small  →  Dimension: 1536
Model: text-embedding-3-large  →  Dimension: 3072
```

**Critical:** This must match your Pinecone index dimension exactly. Mismatch will cause indexing to fail.

#### Current Domain

**Display Only:** Shows the detected domain
**Purpose:** All content is tagged with this domain for multi-site filtering

**Example:**
```
example.com
```

**Note:** Used in Pinecone metadata to filter vectors by domain (supports multi-site WordPress or shared indexes)

#### Configuration Status

**Display Only:** Shows overall configuration status
**Indicators:**
- ✓ Configured (green) - All API keys found and Pinecone configured
- ✗ Not configured (red) - Missing API keys or Pinecone settings

**If showing "Not configured":**
1. Verify environment variables are set
2. Check Pinecone Host and Index Name fields
3. Save settings again
4. Refresh page

## Chatbot Configuration

Navigate to **Settings > AI Assistant > Chatbot**

### Enable Chatbot

**Field Type:** Checkbox
**Default:** Unchecked

Enable to activate chatbot functionality. Once enabled:
- REST API endpoint becomes active
- Chatbot assets load on frontend
- Floating button appears (if enabled)

**When to Enable:**
- After initial configuration complete
- After content is indexed
- After testing in development environment

### System Prompt

**Field Type:** Textarea (large)
**Character Limit:** None (but keep reasonable ~1000-2000 chars)
**Supports:** Plain text (sent to OpenAI as-is)

The system prompt defines the chatbot's behavior, personality, and response format.

#### Default Prompt Structure

The default prompt includes these sections:

1. **OUTPUT FORMAT** - Defines HTML formatting requirements
2. **PERSONA** - Chatbot personality and voice
3. **BOUNDARIES** - What chatbot will/won't answer
4. **CONTEXT USAGE** - How to use retrieved content

#### Customizing the Prompt

**Customize the PERSONA section:**

```
PERSONA
You speak with the voice of this website:
- [Edit these lines to match your brand]
- Friendly and professional
- Expert in [your industry/topic]
- Patient and helpful with beginners
```

**Examples:**

**Healthcare Organization:**
```
PERSONA
You are a knowledgeable healthcare assistant:
- Professional, compassionate, and clear
- Use plain language, avoid medical jargon
- When discussing medical topics, always remind users to consult healthcare providers
- Emphasize accuracy and patient safety
```

**E-commerce Store:**
```
PERSONA
You are a helpful shopping assistant:
- Enthusiastic about products but not pushy
- Help customers find what they need
- Provide accurate product information and comparisons
- Friendly and conversational
```

**Technical Documentation:**
```
PERSONA
You are a technical documentation assistant:
- Clear, precise, and accurate
- Provide step-by-step guidance
- Use technical terms when appropriate
- Include code examples when relevant
```

#### Advanced Prompt Customization

**Add custom instructions:**

```
CUSTOM INSTRUCTIONS
- Always mention the contact page when users ask about reaching us
- If discussing pricing, remind users about current promotions
- For technical questions, recommend relevant documentation sections
```

**Modify boundaries:**

```
BOUNDARIES
You ONLY answer questions using the provided context.
- If context doesn't contain the answer, say: "I don't have specific information about that, but you can contact our team at [contact page link]"
- Stay focused on [your specific topics]
- For [specific topics], redirect to [specific pages]
```

### OpenAI Model

**Field Type:** Text input
**Default:** `gpt-4o-mini`
**Valid Options:**
- `gpt-4o-mini` - Fastest, most cost-effective (~$0.15/1M input tokens)
- `gpt-4o` - Balanced quality and speed (~$2.50/1M input tokens)
- `gpt-4-turbo` - Previous gen, still good (~$10/1M input tokens)
- `gpt-4` - Legacy, slower (~$30/1M input tokens)

**Recommendations:**

| Use Case | Recommended Model | Why |
|----------|-------------------|-----|
| General website chat | gpt-4o-mini | Cost-effective, fast, good quality |
| Technical documentation | gpt-4o | Better reasoning for complex topics |
| High-stakes content | gpt-4o | Highest quality, worth the cost |
| High volume site | gpt-4o-mini | Cost management |

**Performance Comparison:**

```
Model          Speed       Quality     Cost/1M tokens
─────────────────────────────────────────────────────
gpt-4o-mini    Fastest     Good        $0.15
gpt-4o         Fast        Excellent   $2.50
gpt-4-turbo    Medium      Excellent   $10.00
gpt-4          Slow        Excellent   $30.00
```

### Temperature

**Field Type:** Number (range slider or input)
**Default:** 0.2
**Range:** 0.0 to 2.0 (step 0.1)

Controls response randomness and creativity.

**Temperature Scale:**

```
0.0 ─────────── 0.5 ─────────── 1.0 ─────────── 2.0
│               │               │               │
Deterministic   Balanced        Creative        Random
(same answer)   (slight var)    (varied)        (chaotic)
```

**Recommendations by Use Case:**

| Temperature | Best For | Response Style |
|-------------|----------|----------------|
| 0.0 - 0.2   | Facts, documentation, support | Consistent, focused, same answer |
| 0.3 - 0.5   | General chat, FAQs | Balanced, slight variety |
| 0.6 - 0.8   | Creative content, marketing | Varied, conversational |
| 0.9 - 2.0   | Brainstorming, creative writing | Unpredictable, not recommended |

**Recommended:** Start with 0.2 for factual content

### Top K Results

**Field Type:** Number input
**Default:** 5
**Range:** 1 to 20
**Recommended:** 3 to 10

Number of relevant content chunks retrieved from Pinecone to provide context to the AI.

**Impact:**

| Top K | Context Quality | Response Time | API Cost |
|-------|----------------|---------------|----------|
| 1-3   | Limited        | Fast (~2s)    | Low      |
| 5-7   | Good           | Normal (~3s)  | Medium   |
| 10-15 | Comprehensive  | Slower (~5s)  | High     |
| 15+   | Excessive      | Slow (>5s)    | Very High |

**Choosing the Right Value:**

- **Small sites (<100 pages):** 3-5 sufficient
- **Medium sites (100-1000 pages):** 5-7 recommended
- **Large sites (>1000 pages):** 7-10 for better coverage
- **Complex topics:** Higher (8-10) for more context

**Signs Top K is too low:**
- Chatbot says "I don't have that information" for content that exists
- Responses missing important details
- Poor source attribution

**Signs Top K is too high:**
- Slow response times
- Responses too verbose
- Conflicting information in responses

### Floating Button

**Field Type:** Checkbox
**Default:** Unchecked

When enabled, displays a floating chat button in the bottom-right corner of all pages.

**Behavior:**
- Fixed position, follows scroll
- Click to open chat popup
- Dismiss with X button or Escape key
- Remembers open/closed state (session)

**Styling:**
- Default styling included
- Customizable via CSS
- Respects theme colors (where possible)

**When to Enable:**
- For site-wide chat availability
- On support-focused sites
- For improving engagement
- After testing with shortcode

**When to Use Shortcode Instead:**
- For specific pages only
- When design requires custom placement
- For A/B testing
- When floating button conflicts with design

### Intro Message

**Field Type:** Text input
**Default:** "Hi! I can help you explore this website. Ask me a question to get started."
**Supports:** HTML tags (paragraphs, strong, links)

First message shown when chatbot opens.

**Best Practices:**
- Keep under 2-3 sentences
- Set expectations about what chatbot can do
- Use friendly, welcoming tone
- Include call-to-action

**Examples:**

**Support-Focused:**
```html
<p><strong>Hi there!</strong> I'm here to help answer your questions about our products and services.</p>
<p>What would you like to know?</p>
```

**Documentation Site:**
```html
<p>👋 <strong>Welcome to our docs!</strong></p>
<p>Ask me anything about our platform, APIs, or guides.</p>
```

**E-commerce:**
```html
<p>Hello! I can help you find products, answer questions about shipping, returns, and more.</p>
<p>What are you looking for today?</p>
```

### Input Placeholder

**Field Type:** Text input
**Default:** "Ask a question..."
**Character Limit:** ~50 characters (for UX)

Placeholder text shown in the chat input field.

**Examples:**
- "Ask a question..."
- "How can I help you?"
- "Type your message..."
- "Ask me anything..."

**Best Practices:**
- Keep short and clear
- Match your brand voice
- Avoid overpromising capabilities

## Search Configuration

Navigate to **Settings > AI Assistant > Search**

### Enable Search

**Field Type:** Checkbox
**Default:** Unchecked

Enables AI-powered semantic search functionality.

**When Enabled:**
- REST API endpoint becomes active
- Search shortcode works
- Can replace default WordPress search (optional)

### Top K Results

**Field Type:** Number input
**Default:** 10
**Range:** 1 to 50
**Recommended:** 5 to 20

Number of results to retrieve from Pinecone.

**Recommendations:**

| Site Size | Posts Indexed | Recommended Top K |
|-----------|---------------|-------------------|
| Small     | < 100         | 5-10              |
| Medium    | 100-1000      | 10-15             |
| Large     | 1000-10000    | 15-20             |
| Very Large| > 10000       | 20-30             |

**Note:** Higher values provide more results but increase:
- API costs (more Pinecone queries)
- Response time
- Result diversity (possibly less relevant items)

### Minimum Score

**Field Type:** Number (range)
**Default:** 0.5
**Range:** 0.0 to 1.0 (step 0.1)

Filters out results below this relevance score.

**Score Ranges:**

```
1.0 ────── 0.8 ────── 0.6 ────── 0.4 ────── 0.2 ────── 0.0
│          │          │          │          │          │
Perfect    Very       Relevant   Somewhat   Barely     Unrelated
match      relevant              related    related
```

**Recommendations:**

| Threshold | Results | Use Case |
|-----------|---------|----------|
| 0.7-1.0   | Few, very relevant | Precise matching, technical docs |
| 0.5-0.7   | Balanced | **Recommended** for most sites |
| 0.3-0.5   | Many, some loosely related | Broad discovery |
| < 0.3     | Many, poor quality | Not recommended |

**Tuning Tips:**
- **Too few results?** Lower threshold to 0.4-0.5
- **Poor quality results?** Raise threshold to 0.6-0.7
- **Start at 0.5** and adjust based on quality

### Replace Default Search

**Field Type:** Checkbox
**Default:** Unchecked

When enabled, WordPress default search is replaced with AI search.

**How It Works:**
1. Intercepts `is_search()` queries
2. Runs AI semantic search
3. Modifies WP_Query to return AI results
4. Preserves normal WordPress search template display

**Benefits:**
- No theme modifications needed
- Works with existing search.php template
- Seamless upgrade for users
- Can be toggled on/off easily

**Compatibility:**
- Works with standard WordPress themes
- Compatible with most search plugins (may need testing)
- Preserves pagination
- Maintains search term highlighting (if theme supports)

**When to Enable:**
- After thorough testing with shortcode
- On content-heavy sites where search is important
- When default search quality is poor

**When NOT to Enable:**
- If using specialized search plugin (Relevanssi, SearchWP)
- During initial testing phase
- If you need both standard and AI search options

### Results Per Page

**Field Type:** Number input
**Default:** 10
**Range:** 1 to 50

Number of results displayed per page when using AI search.

**Standard:** 10 results (matches WordPress default)
**Recommendations:**
- Blogs: 10-15
- Documentation: 15-20
- E-commerce: 12-16

### Search Placeholder

**Field Type:** Text input
**Default:** "Search with AI..."
**Character Limit:** ~50 characters

Placeholder text for search input field (when using shortcode).

**Examples:**
- "Search our site..."
- "What are you looking for?"
- "Search documentation..."
- "Find answers..."

### Enable AI Summary

**Field Type:** Checkbox
**Default:** Checked (enabled)

Generates AI-powered summary at top of search results (similar to Google AI Overviews).

**Summary Includes:**
- Direct answer to search query (1-2 sentences)
- Key points from top results (bulleted)
- Links to 2-4 most relevant pages
- Clear, scannable format

**Performance Impact:**
- Adds 1 OpenAI API call per search (~1-2 seconds)
- Cost: ~$0.05 per 1000 searches with gpt-4o-mini

**When to Enable:**
- For better user experience (highly recommended)
- On content-rich sites
- When users want quick answers

**When to Disable:**
- To reduce API costs
- For very simple content
- If response time is critical
- During high-traffic periods (cost control)

### Search System Prompt

**Field Type:** Textarea (large)
**Default:** Pre-configured prompt for search summaries

Controls how AI analyzes search results and generates summaries.

#### Key Sections

**1. OUTPUT FORMAT**
Defines HTML formatting (do not modify unless necessary)

**2. PRIMARY GOAL**
What the summary should accomplish

**3. GROUNDING & ACCURACY**
Critical: Ensures AI doesn't invent information

**4. RESPONSE STRUCTURE**
Three-part format: answer, key points, related results

**5. CONTENT PREFERENCES** ⭐ **CUSTOMIZE THIS**
Controls which content is prioritized

#### Customizing Content Preferences

This is the most powerful customization option. Edit the CONTENT PREFERENCES section to control search behavior **without writing code**.

**Format:**
```
CONTENT PREFERENCES

[Your preferences in plain English]

- Priority statements
- Filtering rules
- Content type preferences
- Audience targeting
```

**Examples:**

**Platform-Specific Content:**
```
CONTENT PREFERENCES

Platform Priority:
- When users ask about WordPress, prioritize WordPress-related pages and resources
- When users ask about Drupal, prioritize Drupal-related pages and resources
- If query mentions one platform, de-emphasize content about other platforms
```

**Custom Post Types:**
```
CONTENT PREFERENCES

Content Type Priority:
- For service-related queries, prioritize pages from the Services section
- When users ask about case studies or examples, emphasize case study pages
- Product questions should focus on product pages over blog posts
- Support queries should prioritize documentation over marketing content
```

**Content Freshness:**
```
CONTENT PREFERENCES

Freshness & Quality:
- Prefer recently updated content when available
- For technical questions, prioritize documentation over blog posts
- News queries should emphasize the most recent articles
- Evergreen guides take priority over dated announcements
```

**Industry-Specific (Healthcare):**
```
CONTENT PREFERENCES

Medical Content Priority:
- For clinical questions, prioritize peer-reviewed research and clinical guidelines
- For patient inquiries, emphasize patient education materials written in plain language
- Always highlight the most current clinical protocols
- De-emphasize marketing materials for medical/clinical queries
- Include relevant disclaimers about consulting healthcare providers
```

**Industry-Specific (Legal):**
```
CONTENT PREFERENCES

Legal Content Priority:
- For legal questions, prioritize practice area pages and case studies
- For general inquiries, emphasize overview and consultation information
- Always note that content is informational and not legal advice
- Prioritize jurisdiction-specific information when location is mentioned
```

**Industry-Specific (Education):**
```
CONTENT PREFERENCES

Educational Content Priority:
- For course inquiries, prioritize program pages and curriculum details
- For admissions questions, emphasize requirements and application processes
- For student support queries, highlight student resources and support services
- Prioritize current academic year information over archived content
```

**Industry-Specific (SaaS):**
```
CONTENT PREFERENCES

Product Documentation Priority:
- For feature questions, prioritize documentation and guides over blog posts
- For troubleshooting, emphasize support articles and FAQs
- For integration questions, prioritize API documentation and developer guides
- Include relevant pricing tier information when discussing features
```

### Advanced Relevance Boosting

Navigate to **Settings > AI Assistant > Search > Advanced Relevance Boosting**

Fine-tune algorithmic relevance scoring applied BEFORE AI summarization.

#### Enable Relevance Boosting

**Field Type:** Checkbox
**Default:** Checked (enabled)

When enabled, applies algorithmic boosts to re-rank results based on:
- URL slug matches
- Title matches
- Post type priority

**When to Disable:**
- If you prefer pure semantic ranking
- During troubleshooting
- If custom ranking logic conflicts

#### URL Slug Match Boost

**Field Type:** Number (decimal)
**Default:** 0.15
**Range:** 0.0 to 1.0

Boosts results when query words appear in the URL slug.

**Example:**
- Query: "wordpress migration"
- URL: `/services/wordpress-migration/`
- Boost applied: +0.15 to relevance score

**Tuning:**
- **0.10-0.15:** Moderate boost (recommended)
- **0.15-0.25:** Strong boost (if URLs are very descriptive)
- **0.0:** Disable URL boosting

#### Exact Title Match Boost

**Field Type:** Number (decimal)
**Default:** 0.12
**Range:** 0.0 to 1.0

Boosts results when page title exactly matches search query.

**Example:**
- Query: "Contact Us"
- Title: "Contact Us"
- Boost applied: +0.12

**Tuning:**
- **0.10-0.15:** Good for exact matches
- **0.15-0.20:** Strong boost for title priority
- **0.0:** Disable exact match boosting

#### Title All Words Boost

**Field Type:** Number (decimal)
**Default:** 0.08
**Range:** 0.0 to 1.0

Boosts when title contains ALL query words (but not necessarily exact match).

**Example:**
- Query: "web design services"
- Title: "Professional Web Design Services in Chicago"
- Boost applied: +0.08

**Tuning:**
- **0.05-0.10:** Moderate boost (recommended)
- **0.10-0.15:** Strong boost for comprehensive titles
- **0.0:** Disable

#### Page Post Type Boost

**Field Type:** Number (decimal)
**Default:** 0.05
**Range:** 0.0 to 1.0

Boosts WordPress "page" post type slightly over "post" type.

**Rationale:** Pages tend to be more authoritative/permanent content than blog posts.

**Tuning:**
- **0.03-0.07:** Slight preference for pages
- **0.0:** No post type preference
- **0.10+:** Strong preference (may over-prioritize pages)

**Note:** For custom post type boosts, use the `semantic_knowledge_search_relevance_config` filter (requires custom code).

## Advanced Settings

Settings requiring developer assistance or deeper technical knowledge.

### Custom Node.js Path

**Location:** Settings > AI Assistant > Indexer
**Field Type:** Text input
**Default:** Empty (auto-detect)

Specify custom path to Node.js executable if auto-detection fails.

**When to Configure:**
- Server has multiple Node.js versions
- Node.js installed in non-standard location
- Auto-detection fails
- Need specific Node.js version for indexer

**Finding Node.js Path:**
```bash
which node
# Output example: /usr/local/bin/node
```

**Examples:**
- `/usr/local/bin/node`
- `/opt/homebrew/bin/node`
- `/usr/bin/node`
- `~/.nvm/versions/node/v18.17.0/bin/node`

**Leave Empty If:**
- Auto-detection works
- Using standard Node.js installation
- No special requirements

### Trusted Proxy IPs

**Location:** Environment variable: `Semantic_Knowledge_TRUSTED_PROXIES`
**Type:** Comma-separated IP addresses
**Default:** None

Configure if your site is behind a load balancer, CDN, or proxy that sets `X-Forwarded-For` headers.

**Purpose:** Accurate IP detection for rate limiting

**Security:** Only IPs listed here are trusted to set X-Forwarded-For header (prevents IP spoofing).

**Configuration:**
```bash
# Single IP
Semantic_Knowledge_TRUSTED_PROXIES=10.0.0.1

# Multiple IPs
Semantic_Knowledge_TRUSTED_PROXIES=10.0.0.1,10.0.0.2,10.0.0.3
```

**When to Configure:**
- Behind Cloudflare (trusted automatically)
- Behind load balancer (AWS ELB, etc.)
- Behind reverse proxy (Nginx, Apache)
- Behind Varnish cache

**When NOT Needed:**
- Direct server connection
- No load balancer/CDN
- CDN doesn't set X-Forwarded-For

**Finding Proxy IPs:**
```bash
# Check server variables
wp eval 'echo $_SERVER["REMOTE_ADDR"];'

# If behind proxy, check forwarded header
wp eval 'echo $_SERVER["HTTP_X_FORWARDED_FOR"] ?? "not set";'
```

### Content Security Policy (CSP)

**Location:** Environment variable: `Semantic_Knowledge_ENABLE_CSP`
**Type:** Boolean (true/false)
**Default:** False (disabled for compatibility)

Enables Content Security Policy headers for enhanced security.

**Configuration:**
```bash
Semantic_Knowledge_ENABLE_CSP=true
```

**CSP Directives Added:**
```
Content-Security-Policy:
  default-src 'self';
  script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
  style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
  connect-src 'self' https://api.openai.com https://*.pinecone.io;
  img-src 'self' data: https:;
```

**When to Enable:**
- After testing in development
- On security-sensitive sites
- If theme/plugins are CSP-compatible

**When NOT to Enable:**
- Default (off for maximum compatibility)
- If theme uses inline scripts
- If other plugins conflict
- During initial setup/testing

**Customizing CSP:**

Developers can filter directives:
```php
add_filter('semantic_knowledge_csp_directives', function($directives) {
    // Add custom directive
    $directives[] = "font-src 'self' https://custom-cdn.com";
    return $directives;
});
```

### Rate Limiting

**Built-in:** 10 requests per minute per IP
**Configured via filters (developer)**

**Customizing Rate Limits:**

```php
// Change chatbot rate limit
add_filter('semantic_knowledge_chatbot_rate_limit', function($limit) {
    return 20; // 20 requests per window
});

add_filter('semantic_knowledge_chatbot_rate_window', function($window) {
    return 120; // 120 seconds (2 minutes)
});

// Change search rate limit
add_filter('semantic_knowledge_search_rate_limit', function($limit) {
    return 30;
});
```

**Recommendations:**
- **Low traffic:** 10-20 per minute
- **High traffic:** 30-50 per minute
- **Prevent abuse:** 5-10 per minute

## Performance Tuning

Optimize plugin performance for your environment.

### Caching Strategies

The plugin includes built-in caching for:
- Embeddings (1 hour TTL)
- Pinecone query results (15 minutes TTL)
- Indexer settings (5 minutes TTL)

#### Enable Object Caching

**Recommended:** Redis or Memcached for significant performance improvement

**Performance Impact:**
- 80% reduction in API call latency
- 60% reduction in database queries
- Faster repeat queries

**DDEV Setup:**

1. Add to `.ddev/config.yaml`:
```yaml
services:
  redis:
    type: redis
    version: "7"
```

2. Install Redis Object Cache plugin:
```bash
ddev restart
ddev composer require wpackagist-plugin/redis-cache
ddev wp plugin activate redis-cache
ddev wp redis enable
```

**Production Setup:**

Consult your hosting documentation:
- **Pantheon:** Redis included, enable via dashboard
- **WP Engine:** Redis available on select plans
- **Kinsta:** Redis included on all plans
- **Other:** Install Redis and WordPress Redis plugin

### Response Compression

Enable gzip or Brotli compression for API responses:

**Nginx:**
```nginx
gzip on;
gzip_types application/json;
gzip_min_length 1000;
```

**Apache (.htaccess):**
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE application/json
</IfModule>
```

**Performance Impact:**
- 60-80% reduction in bandwidth
- Faster response times
- Lower data transfer costs

### Database Optimization

For high-traffic sites:

```bash
# Optimize log tables monthly
wp db query "OPTIMIZE TABLE semantic_knowledge_chat_logs;"
wp db query "OPTIMIZE TABLE semantic_knowledge_search_logs;"
```

### CDN Integration

Use CDN for plugin assets:

**Cloudflare:**
1. Enable "Cache Everything" page rule
2. Add plugin URL patterns
3. Set appropriate cache TTLs

**Other CDNs:**
- Configure similar caching rules
- Cache `/wp-content/plugins/semantic-knowledge/assets/*`
- Set long cache TTLs (1 year for versioned assets)

### Model Selection for Performance

| Requirement | Recommended Model | Why |
|-------------|-------------------|-----|
| Fastest responses | gpt-4o-mini | 2-3x faster than gpt-4o |
| Lowest cost | gpt-4o-mini | 10x cheaper than gpt-4o |
| Best quality | gpt-4o | Highest accuracy |
| Balanced | gpt-4o-mini | **Recommended** starting point |

### Monitoring Performance

**Key Metrics:**

1. **Response Time**
   - Target: <3 seconds for chat
   - Target: <2 seconds for search
   - Measure in chat/search logs

2. **API Latency**
   - OpenAI: Check platform status
   - Pinecone: Monitor query times

3. **Cache Hit Rate**
   - Higher = better performance
   - Check via Redis INFO command

**Monitoring Tools:**
- Query Monitor plugin
- New Relic
- Application Performance Monitoring (APM)

## Security Settings

Security configuration and best practices.

### API Key Security

**✓ DO:**
- Store keys in environment variables
- Use separate keys per environment
- Rotate keys quarterly
- Limit key permissions to necessary scopes only

**✗ DON'T:**
- Store keys in database
- Commit keys to version control
- Share keys via email/chat
- Use production keys in development

### Input Validation

Built-in protections:
- Maximum query length (1000 characters)
- Nonce verification for all requests
- Sanitization of all user inputs
- Rate limiting per IP address

**No configuration needed** - security measures are always active.

### Rate Limiting Security

Configure rate limits to prevent abuse:

```php
// Stricter rate limiting for anonymous users
add_filter('semantic_knowledge_chatbot_rate_limit', function($limit) {
    return is_user_logged_in() ? 20 : 10;
});
```

### Content Security Policy

See [Advanced Settings > Content Security Policy](#content-security-policy-csp)

### Trusted Proxies

See [Advanced Settings > Trusted Proxy IPs](#trusted-proxy-ips)

**Security Risk:** Misconfigured trusted proxies can allow IP spoofing

**Best Practice:** Only add IPs you control/trust

### HTTPS Requirement

**Strongly Recommended:** Run plugin over HTTPS

**Why:**
- API keys transmitted in headers
- User queries contain potentially sensitive info
- Prevents man-in-the-middle attacks

**Check HTTPS:**
```bash
wp eval 'echo is_ssl() ? "HTTPS ✓" : "HTTP ✗";'
```

### Data Privacy

**What's Sent to External APIs:**

**OpenAI:**
- User queries (search terms, chat questions)
- Retrieved content chunks (for context)
- System prompts

**Pinecone:**
- Embedding vectors (anonymous)
- Metadata (page IDs, URLs, titles)
- Search vectors

**OpenAI Data Policy:**
- API data not used for training (as of 2024)
- 30-day retention for abuse monitoring
- See: https://openai.com/policies/api-data-usage-policies

**Pinecone Data Policy:**
- Vectors stored until deleted
- Metadata stored with vectors
- See: https://www.pinecone.io/privacy/

**Privacy Recommendations:**
1. Update Privacy Policy to mention AI features
2. Inform users queries sent to OpenAI/Pinecone
3. Don't index pages with PII/sensitive content
4. Configure reasonable log retention
5. Provide data deletion upon request (GDPR)

## Configuration Examples

Real-world configuration examples for common scenarios.

### Example 1: Small Business Website

**Profile:**
- 50 pages
- 200 visitors/day
- ~50 searches/day
- Budget: $5-10/month

**Configuration:**

```
General Settings:
  - Embedding Model: text-embedding-3-small
  - Embedding Dimension: 1536

Chatbot Settings:
  - Enable: Yes
  - Floating Button: Yes
  - Model: gpt-4o-mini
  - Temperature: 0.2
  - Top K: 3
  - Intro: "Hi! I can answer questions about our services and how we can help you."

Search Settings:
  - Enable: Yes
  - Replace Default: Yes
  - Top K: 5
  - Min Score: 0.5
  - Enable Summary: Yes

Indexer Settings:
  - Post Types: posts,pages,services
  - Chunk Size: 1000
```

### Example 2: Documentation Site

**Profile:**
- 500 documentation pages
- 2000 visitors/day
- ~400 searches/day
- Budget: $30-50/month

**Configuration:**

```
General Settings:
  - Embedding Model: text-embedding-3-small
  - Embedding Dimension: 1536

Chatbot Settings:
  - Enable: Yes
  - Floating Button: Yes
  - Model: gpt-4o-mini
  - Temperature: 0.1 (very focused)
  - Top K: 7
  - System Prompt: [Customized for technical docs]

Search Settings:
  - Enable: Yes
  - Replace Default: Yes
  - Top K: 15
  - Min Score: 0.6 (higher threshold)
  - Enable Summary: Yes
  - Search System Prompt: [Prioritize docs over blog]

Content Preferences:
  - Prioritize documentation pages
  - Prefer code examples and tutorials
  - De-emphasize announcement posts

Indexer Settings:
  - Post Types: docs,guides,api-reference
  - Chunk Size: 1200
  - Chunk Overlap: 200
```

### Example 3: E-commerce Store

**Profile:**
- 1000 products
- 5000 visitors/day
- ~1000 searches/day
- Budget: $50-100/month

**Configuration:**

```
General Settings:
  - Embedding Model: text-embedding-3-small
  - Embedding Dimension: 1536

Chatbot Settings:
  - Enable: Yes
  - Floating Button: Yes (support-focused)
  - Model: gpt-4o-mini
  - Temperature: 0.3
  - Top K: 5
  - Intro: "Hi! I can help you find products, answer questions about shipping, returns, and more. What are you looking for?"

Search Settings:
  - Enable: Yes
  - Replace Default: Yes
  - Top K: 12
  - Min Score: 0.5
  - Enable Summary: Yes
  - Page Boost: 0.05 (pages > products)

Content Preferences:
  - Prioritize product pages for product queries
  - Emphasize shipping/return policy for those questions
  - Highlight current promotions

Indexer Settings:
  - Post Types: products,pages,posts,faqs
  - Chunk Size: 1000 (shorter for products)
```

### Example 4: High-Traffic News Site

**Profile:**
- 5000+ articles
- 50000 visitors/day
- ~5000 searches/day
- Budget: $200-300/month

**Configuration:**

```
General Settings:
  - Embedding Model: text-embedding-3-small
  - Embedding Dimension: 1536

Chatbot Settings:
  - Enable: No (focus on search)
  - Floating Button: No

Search Settings:
  - Enable: Yes
  - Replace Default: Yes
  - Top K: 20
  - Min Score: 0.6
  - Enable Summary: Yes
  - Results Per Page: 15
  - Model: gpt-4o-mini (cost management)

Content Preferences:
  - Prioritize most recent articles for news queries
  - For evergreen topics, emphasize comprehensive guides
  - De-emphasize articles over 2 years old for time-sensitive topics

Performance:
  - Enable Redis caching
  - Enable response compression
  - CDN for assets
  - Rate limit: 30 requests/minute

Indexer Settings:
  - Post Types: posts,articles
  - Auto-discover: Yes
  - Clean Deleted: Yes
  - Chunk Size: 1500 (longer articles)
```

### Example 5: Multi-Site WordPress Network

**Profile:**
- 10 sites on network
- Shared Pinecone index
- Different AI needs per site

**Configuration:**

**Site 1 (Main Corporate):**
```
Chatbot: Enabled (professional tone)
Search: Enabled (comprehensive)
Content: All post types
```

**Site 2 (Blog):**
```
Chatbot: Enabled (conversational)
Search: Enabled (emphasis on recent)
Content: Posts only
```

**Site 3 (Support Docs):**
```
Chatbot: Enabled (technical, precise)
Search: Enabled (docs-focused)
Content: Docs custom post type
```

**Shared Settings:**
- Same Pinecone index (domain filtering keeps them separate)
- Same API keys
- Different system prompts per site
- Different content type priorities

---

**Last Updated:** January 2025
**Plugin Version:** 1.0.0

For implementation examples and code snippets, see:
- [CUSTOMIZATION.md](CUSTOMIZATION.md) - Content preference examples
- [HOOKS.md](HOOKS.md) - Filter and action reference
- [examples/](../examples/) - Code examples
