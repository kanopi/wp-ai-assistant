# WP AI Assistant

AI-powered chatbot and semantic search for WordPress using RAG (Retrieval-Augmented Generation) with OpenAI and Pinecone.

## Requirements

- WordPress 5.6+
- PHP 8.0+
- Node.js 18+ ([Download](https://nodejs.org/))
- [@kanopi/wp-ai-indexer](https://www.npmjs.com/package/@kanopi/wp-ai-indexer) npm package
- OpenAI API key
- Pinecone API key

## Installation

This plugin is part of a monorepo with a shared indexer package at `packages/wp-ai-indexer`.

### For Monorepo/Local Development (DDEV)

1. **Install and activate the plugin:**
   - Plugin is already in `wp-content/plugins/wp-ai-assistant/`
   - Activate via WordPress admin or WP-CLI: `ddev wp plugin activate wp-ai-assistant`

2. **Build the indexer package:**
   ```bash
   # From project root
   ddev exec "cd packages/wp-ai-indexer && npm install && npm run build"
   ```

3. **Verify installation:**
   ```bash
   ddev wp ai-indexer check
   ```

### For CI/CD Environments

CircleCI installs the indexer from the monorepo `packages/wp-ai-indexer` directory:

```bash
# In CircleCI config (already configured)
cd packages/wp-ai-indexer
npm install
npx wp-ai-indexer index
```

### For Standalone Plugin Usage

If using this plugin outside the monorepo, install the indexer globally:

```bash
# Install indexer globally
npm install -g @kanopi/wp-ai-indexer

# Verify
wp ai-indexer check
```

**Note**: The WP-CLI commands automatically detect the indexer location:
1. First checks `packages/wp-ai-indexer` (monorepo)
2. Then checks plugin's `indexer/` subdirectory (standalone)
3. Finally checks global installation (CI/CD)

## Configuration

### Required Environment Variables

**Security Note:** All API keys and secrets must be configured via environment variables, PHP constants, or Pantheon secrets. They cannot be set through the WordPress admin interface for security reasons.

Required variables:
```bash
# OpenAI API key (required)
export OPENAI_API_KEY="sk-..."

# Pinecone API key (required)
export PINECONE_API_KEY="..."

# Indexer API key for REST endpoint authentication (required for CI/CD)
export WP_AI_INDEXER_KEY="generate-a-secure-random-string"

# Trusted proxy IPs (optional, for rate limiting security)
# Only set if behind load balancer/CDN that sets X-Forwarded-For header
export WP_AI_TRUSTED_PROXIES="10.0.0.1,10.0.0.2"
```

**For DDEV:**
Add to `.ddev/config.yaml`:
```yaml
web_environment:
  - OPENAI_API_KEY=sk-...
  - PINECONE_API_KEY=...
  - WP_AI_INDEXER_KEY=your-secure-key
```

**For Pantheon:**
Use Pantheon Secrets or environment variables in `pantheon.yml`.

**For CircleCI:**
Add to CircleCI project environment variables or use contexts.

**Generating a secure indexer key:**
```bash
# Linux/macOS
openssl rand -hex 32

# Or use PHP
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### WordPress Admin Settings

1. Navigate to **Settings > AI Assistant** in WordPress admin
2. Configure Pinecone settings:
   - Pinecone Index Host
   - Pinecone Index Name
   - Embedding Model (default: text-embedding-3-small)
   - Embedding Dimension (default: 1536)
3. Enable desired features:
   - AI Chatbot
   - AI Search

## Usage

### Indexing Content

The plugin relies on the Node.js indexer package to create and manage vectors in Pinecone.

**Authentication:**
The indexer REST endpoint requires authentication. The indexer automatically authenticates using the `WP_AI_INDEXER_KEY` environment variable when fetching settings from WordPress.

**Using WP-CLI (Recommended):**

```bash
# Index all content
wp ai-indexer index

# Index with debug output
wp ai-indexer index --debug

# Clean deleted posts
wp ai-indexer clean

# Delete all vectors and re-index
wp ai-indexer delete-all
wp ai-indexer index

# Check system requirements
wp ai-indexer check

# Show configuration
wp ai-indexer config
```

**Using npx directly:**

```bash
# Set the indexer key
export WP_AI_INDEXER_KEY="your-secure-key"

# Index all content
npx wp-ai-indexer index

# Other commands
npx wp-ai-indexer clean
npx wp-ai-indexer delete-all
npx wp-ai-indexer config
```

**DDEV:**

```bash
# Using WP-CLI wrapper (recommended - uses environment from .ddev/config.yaml)
ddev wp ai-indexer index

# Or using npx directly
ddev exec "cd /var/www/html/web/wp-content/plugins/wp-ai-assistant/indexer && npx wp-ai-indexer index"
```

**CI/CD (CircleCI, GitHub Actions, etc.):**

The indexer requires the `WP_AI_INDEXER_KEY` environment variable to authenticate with the WordPress REST API:

```bash
# Set environment variables
export OPENAI_API_KEY="sk-..."
export PINECONE_API_KEY="..."
export WP_AI_INDEXER_KEY="your-secure-key"

# Run indexer
npx wp-ai-indexer index
```

### Using the Chatbot

Once content is indexed, the chatbot will appear on your site (if enabled). Users can ask questions and receive AI-generated responses based on your WordPress content.

### Using AI Search

AI-powered search enhances the default WordPress search with semantic understanding, providing more relevant results based on meaning rather than exact keyword matches.

## Performance Optimization

For production deployments, enable object caching and response compression for optimal performance:

- **Object Caching (Redis/Memcached):** Reduces API latency by 80%
- **Response Compression (Gzip/Brotli):** Reduces bandwidth by 60-80%
- **CDN Integration:** Improves global load times

See [PERFORMANCE.md](docs/PERFORMANCE.md) for detailed configuration instructions.

**Quick Setup for DDEV:**

Enable Redis in `.ddev/config.yaml`:
```yaml
services:
  redis:
    type: redis
    version: "7"
```

Then install Redis Object Cache plugin:
```bash
ddev restart
ddev composer require wpackagist-plugin/redis-cache
ddev wp plugin activate redis-cache
ddev wp redis enable
```

## Troubleshooting

### "Indexer not found"

If you see an error that the indexer is not found:

1. **Check if Node.js is installed:**
   ```bash
   node --version
   # Should show v18.0.0 or higher
   ```

2. **Install the indexer package:**

   **For DDEV/Local (recommended):**
   ```bash
   cd wp-content/plugins/wp-ai-assistant/indexer
   npm install
   ```

   **For CI/CD/Global:**
   ```bash
   npm install -g @kanopi/wp-ai-indexer
   ```

3. **Check installation:**
   ```bash
   wp ai-indexer check
   ```

### Admin Notice Won't Disappear

If the admin notice keeps appearing after installing the indexer:

1. Click the "Re-check" link in the notice
2. Or clear the cache manually:
   ```bash
   wp transient delete wp_ai_assistant_system_check
   ```

### Permission Errors During npm Install

If you get permission errors with global installation:

```bash
# Option 1: Use local installation instead (recommended)
cd wp-content/plugins/wp-ai-assistant/indexer
npm install
# No sudo needed!

# Option 2: Use nvm for global installation (recommended for CI/CD)
# Install nvm: https://github.com/nvm-sh/nvm
nvm install 18
nvm use 18
npm install -g @kanopi/wp-ai-indexer

# Option 3: Use sudo (not recommended for security)
sudo npm install -g @kanopi/wp-ai-indexer

# Option 4: Change npm global directory
# See: https://docs.npmjs.com/resolving-eacces-permissions-errors
```

### Local vs Global Installation

**When to use local installation:**
- ✅ DDEV or other local development environments
- ✅ When you don't want to use sudo
- ✅ When working on multiple projects with different versions

**When to use global installation:**
- ✅ CI/CD pipelines (CircleCI, GitHub Actions, etc.)
- ✅ Production servers running indexer as a service
- ✅ When you want `wp-ai-indexer` available everywhere

**How the plugin chooses:**
1. Checks for local installation in `indexer/node_modules/.bin/wp-ai-indexer`
2. Falls back to global installation if local not found
3. Shows error if neither found

## Development

### Requirements

- Node.js 18+
- Composer
- WP-CLI

### Setup

```bash
# Clone repository
git clone https://github.com/kanopi/wp-ai-assistant.git
cd wp-ai-assistant

# Install dependencies
composer install

# The indexer will be installed locally automatically
```

### Running Tests

```bash
# Check system requirements
wp ai-indexer check

# Test indexing
wp ai-indexer index --debug
```

## Features

### AI Chatbot
- Conversational interface for users
- Context-aware responses based on your content
- Customizable appearance and behavior
- Fully keyboard accessible with screen reader support

### AI Search
- Semantic search understanding
- More relevant results
- Works with existing WordPress search
- WCAG 2.1 Level AA compliant interface

### RAG Architecture
- Retrieval-Augmented Generation
- Combines content retrieval with AI generation
- Accurate, content-grounded responses

### Accessibility Features
- **Keyboard Navigation** - All features accessible via keyboard
- **Screen Reader Compatible** - Tested with NVDA, JAWS, and VoiceOver
- **WCAG 2.1 Level AA** - Striving for full compliance (74% current)
- **Focus Management** - Clear focus indicators and logical tab order
- **Reduced Motion Support** - Respects user motion preferences
- **High Contrast** - All text meets WCAG color contrast requirements

See [ACCESSIBILITY.md](ACCESSIBILITY.md) for complete accessibility statement.

## Architecture

This plugin uses a hybrid architecture:
- **WordPress Plugin** (PHP): Handles WordPress integration, settings, and UI
- **Node.js Indexer** (JavaScript): Fast vector processing and indexing
- **OpenAI API**: Embeddings and chat completion
- **Pinecone**: Vector database for semantic search

## Extending the Plugin

The WP AI Assistant plugin is designed to be highly extensible and customizable. You can tailor the search behavior, content prioritization, and AI responses to match your specific needs.

### Customization Approaches

#### 1. **Content Preferences (No Code Required)** ✨ **Recommended**

The easiest way to customize the plugin is by editing the **Search System Prompt** in Settings > AI Assistant > Search.

Simply tell the AI what to prioritize in natural language:

```
CONTENT PREFERENCES

Platform-Specific Content:
- When users ask about WordPress, prioritize WordPress-related pages
- When users ask about Drupal, prioritize Drupal-related pages

Service Pages:
- For service-related queries, emphasize our Services section
```

**Learn more:**
- [CUSTOMIZATION.md](docs/CUSTOMIZATION.md) - Complete guide with examples for different industries
- [examples/content-preferences-examples.md](examples/content-preferences-examples.md) - Ready-to-use examples

#### 2. **Settings-Based Configuration**

Fine-tune algorithmic relevance boosting in Settings > AI Assistant > Search > Advanced Relevance Boosting:

- Enable/disable relevance boosting
- Adjust URL slug match boost (0.0-1.0)
- Adjust exact title match boost (0.0-1.0)
- Adjust all-words title boost (0.0-1.0)
- Adjust page post type boost (0.0-1.0)

#### 3. **Filters and Actions (Advanced)**

For advanced customizations, the plugin provides 30+ filters and actions:

```php
// Add custom post type boosts
add_filter('wp_ai_search_relevance_config', function($config, $query) {
    $config['post_type_boosts']['services'] = 0.07;
    return $config;
}, 10, 2);

// Track search analytics
add_action('wp_ai_search_query_end', function($response, $query) {
    // Send to analytics platform
}, 10, 2);

// Customize AI summaries
add_filter('wp_ai_search_summary', function($summary, $query) {
    $summary .= '<p class="cta">Need help? <a href="/contact/">Contact us</a></p>';
    return $summary;
}, 10, 2);
```

**Learn more:**
- [HOOKS.md](docs/HOOKS.md) - Complete filter and action reference
- [examples/](examples/) - Practical code examples

### Quick Start: Common Customizations

#### Customize Content Priorities

**Recommended: Use System Prompt**
1. Go to Settings > AI Assistant > Search
2. Scroll to "Search System Prompt"
3. Edit the "CONTENT PREFERENCES" section
4. Add your preferences in plain English
5. Save changes

See [CUSTOMIZATION.md](docs/CUSTOMIZATION.md) for examples.

#### Add Custom Post Type Boosting

**PHP Filter (functions.php or custom plugin):**
```php
add_filter('wp_ai_search_relevance_config', function($config, $query) {
    $config['post_type_boosts']['case_study'] = 0.08;
    return $config;
}, 10, 2);
```

See [examples/custom-post-type-boost.php](examples/custom-post-type-boost.php) for more examples.

#### Track Search Analytics

```php
add_action('wp_ai_search_query_end', function($response, $query) {
    error_log('Search: ' . $query . ' (' . $response['total'] . ' results)');
}, 10, 2);
```

See [examples/analytics-tracking.php](examples/analytics-tracking.php) for complete implementation.

#### Implement Caching

```php
add_action('wp_ai_search_query_end', function($response, $query) {
    $cache_key = 'ai_search_' . md5($query);
    set_transient($cache_key, $response, HOUR_IN_SECONDS);
}, 10, 2);
```

See [examples/performance-optimization.php](examples/performance-optimization.php) for complete implementation.

### Available Hooks

The plugin provides extensive hooks for customization:

**Search Filters:**
- `wp_ai_search_query_text` - Modify query before processing
- `wp_ai_search_relevance_config` - Configure relevance boosting
- `wp_ai_search_results` - Filter search results
- `wp_ai_search_summary` - Customize AI summaries
- `wp_ai_search_summary_system_prompt` - Modify AI prompt
- And 20+ more...

**Search Actions:**
- `wp_ai_search_query_start` - Track search start
- `wp_ai_search_query_end` - Track search completion
- `wp_ai_search_before_log` - Intercept logging
- And 10+ more...

**Chatbot Filters:**
- `wp_ai_chatbot_question` - Modify user question
- `wp_ai_chatbot_context` - Customize context
- `wp_ai_chatbot_answer` - Filter AI response
- And 10+ more...

See [HOOKS.md](docs/HOOKS.md) for the complete reference.

### Documentation

- **[CUSTOMIZATION.md](docs/CUSTOMIZATION.md)** - Guide to customizing content preferences (no code)
- **[HOOKS.md](docs/HOOKS.md)** - Complete filter and action reference
- **[examples/](examples/)** - Practical code examples:
  - `content-preferences-examples.md` - Ready-to-use system prompt examples
  - `custom-post-type-boost.php` - Custom post type boosting
  - `analytics-tracking.php` - Search analytics and tracking
  - `performance-optimization.php` - Caching and performance
  - `custom-search-summary.php` - AI summary customization

### Best Practices

1. **Start with System Prompt** - For content preferences, use the system prompt instead of code
2. **Use Settings First** - Adjust built-in settings before writing custom filters
3. **Test Changes** - Try various queries after making customizations
4. **Monitor Performance** - Track response times when adding custom logic
5. **Document Customizations** - Keep notes about what you've changed and why

## Keyboard Shortcuts

The plugin is fully keyboard accessible. Use these shortcuts for efficient navigation:

| Shortcut | Action |
|----------|--------|
| `Tab` | Move focus forward through interactive elements |
| `Shift + Tab` | Move focus backward through interactive elements |
| `Enter` | Activate buttons, submit forms, or follow links |
| `Space` | Activate buttons |
| `Escape` | Close chatbot popup (when using floating button) |

### Chatbot Navigation

1. **Tab** to the chatbot button (floating or shortcode)
2. Press **Enter** or **Space** to open the chat
3. Use **Tab** to navigate between chat input, send button, and close button
4. Press **Escape** to close the popup and return focus to the button

### Search Navigation

1. **Tab** to the search input field
2. Type your query
3. Press **Enter** to submit (or **Tab** to button and press **Enter**)
4. Use **Tab** to navigate through search results
5. Press **Enter** on a focused result to view the page

All interactive elements display a visible focus indicator (blue outline) when focused.

## Accessibility

The WP AI Assistant plugin is committed to providing an accessible experience for all users.

### Compliance Status

- **WCAG 2.1 Level AA:** 74% compliant (actively working toward full compliance)
- **Keyboard Accessible:** All features fully operable via keyboard
- **Screen Reader Compatible:** Tested with NVDA, JAWS, and VoiceOver
- **High Contrast:** All text and interactive elements meet WCAG AA color contrast requirements

### Accessibility Features

- Semantic HTML structure with proper headings and landmarks
- ARIA labels and live regions for screen reader announcements
- Visible focus indicators on all interactive elements
- Keyboard navigation with logical tab order
- Focus management in popups and modals
- Reduced motion support for users with motion sensitivities
- Minimum 44x44px touch targets on mobile devices

### Known Limitations

The chatbot interface uses the Deep Chat library (third-party component). While we have implemented accessibility enhancements around this component, some limitations may exist within the component itself. We are actively working to improve accessibility and welcome feedback.

### Report Accessibility Issues

If you encounter an accessibility barrier, please report it:

- **GitHub Issues:** [Report accessibility issues](https://github.com/kanopi/wp-ai-assistant/issues)
- **Email:** accessibility@kanopi.com

See [ACCESSIBILITY.md](ACCESSIBILITY.md) for our complete accessibility statement and [docs/accessibility-testing-guide.md](docs/accessibility-testing-guide.md) for developer testing guidelines.

## Support

For issues, questions, or contributions:
- [GitHub Issues](https://github.com/kanopi/wp-ai-assistant/issues)
- [Documentation](https://github.com/kanopi/wp-ai-indexer#readme)
- [Accessibility Issues](https://github.com/kanopi/wp-ai-assistant/issues) (use "Accessibility Issue" label)

## License

MIT License - see LICENSE file for details

## Credits

Developed by [Kanopi Studios](https://kanopi.com)
