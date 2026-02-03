# WP AI Assistant

AI-powered chatbot and semantic search for WordPress using RAG (Retrieval-Augmented Generation).

## Features

- 🤖 AI-powered chatbot with conversation history
- 🔍 Semantic search using vector embeddings
- 🚀 Built on OpenAI and Pinecone
- 🎨 Customizable UI components
- ♿ WCAG 2.1 Level AA accessible
- 🔧 Easy configuration via WordPress admin

## Requirements

- PHP 8.0+
- Node.js 18+
- WordPress 6.0+
- Composer (for Composer installation)
- OpenAI API key
- Pinecone API key

## Installation

### Method 1: Composer (Recommended - Fully Automated)

```bash
# Install plugin
composer require kanopi/wp-ai-assistant

# Activate plugin
wp plugin activate wp-ai-assistant

# Indexer installs automatically during composer install! ✅
```

**Why this works:**
- Plugin's `composer.json` has `post-install-cmd` hook
- Automatically installs indexer into `indexer/node_modules/`
- No manual steps required

### Method 2: WordPress.org

1. Download from WordPress.org (pending approval)
2. Upload to `/wp-content/plugins/` or install via WordPress admin
3. Activate the plugin
4. Install indexer:
   ```bash
   wp ai-assistant install-indexer
   ```
   Or click **"Install Indexer"** button in WordPress admin

### Method 3: Manual Installation

```bash
# Clone repository
cd wp-content/plugins
git clone https://github.com/kanopi/wp-ai-assistant.git
cd wp-ai-assistant

# Install PHP dependencies
composer install

# Install Node.js indexer
cd indexer
npm install
```

## Configuration

### 1. API Keys

Add to `wp-config.php`:
```php
define('OPENAI_API_KEY', 'sk-...');
define('PINECONE_API_KEY', 'pcsk_...');
```

Or set via WP-CLI:
```bash
wp config set OPENAI_API_KEY "sk-..." --type=constant
wp config set PINECONE_API_KEY "pcsk_..." --type=constant
```

### 2. Plugin Settings

1. Go to **Settings → AI Assistant** in WordPress admin
2. Configure:
   - Pinecone Index Host (e.g., `your-index-123.pinecone.io`)
   - Pinecone Index Name (e.g., `wp-content`)
   - Post Types to index
   - Chunk Size (default: 1000 characters)
   - Embedding Model (default: text-embedding-3-small)

### 3. Index Content

```bash
# Index all content
wp ai-indexer index

# Check system requirements
wp ai-indexer check

# View configuration
wp ai-indexer config
```

## Usage

### Chatbot

Add to any page or post using shortcode:
```
[wp_ai_chatbot]
```

Or via block editor: Add the **AI Chatbot** block

### Search

Replace default WordPress search:
```php
// In your theme's functions.php
add_filter('wp_ai_assistant_enable_search', '__return_true');
```

Or use the search widget in **Appearance → Widgets**

## Development

### Running Tests

```bash
# PHP tests
composer test

# Node.js indexer tests
cd indexer/node_modules/@kanopi/wp-ai-indexer
npm test
```

### Code Quality

```bash
# PHP linting
composer phpcs

# JavaScript linting
npm run lint
```

## Troubleshooting

### Indexer Not Found

**Problem:** Plugin shows "Indexer not installed" notice

**Solution:**
```bash
# Check Node.js
node --version  # Should be 18+

# Install indexer
wp ai-assistant install-indexer

# Or manually
cd wp-content/plugins/wp-ai-assistant/indexer
npm install
```

### Authentication Errors

**Problem:** 401 Unauthorized errors when indexing

**Solution:**
- Verify API keys are set correctly
- Check Pinecone index exists and is active
- Ensure OpenAI API key has sufficient credits

### No Search Results

**Problem:** Search returns no results

**Solution:**
```bash
# Re-index content
wp ai-indexer delete-all
wp ai-indexer index

# Check index status
wp ai-indexer config
```

## Architecture

Built on a modular architecture:

- **WP_AI_Core**: Settings and configuration management
- **WP_AI_OpenAI**: OpenAI API integration
- **WP_AI_Pinecone**: Pinecone vector database integration
- **WP_AI_Chatbot_Module**: Chatbot functionality
- **WP_AI_Search_Module**: Search functionality
- **@kanopi/wp-ai-indexer**: Node.js indexing package (separate npm package)

## Documentation

- [Installation Guide](https://github.com/kanopi/wp-ai-assistant/wiki/Installation)
- [Configuration](https://github.com/kanopi/wp-ai-assistant/wiki/Configuration)
- [API Documentation](https://github.com/kanopi/wp-ai-assistant/wiki/API)
- [Troubleshooting](https://github.com/kanopi/wp-ai-assistant/wiki/Troubleshooting)
- [Indexer Package](https://github.com/kanopi/wp-ai-indexer)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md)

## License

MIT License - see [LICENSE](LICENSE)

## Support

- [GitHub Issues](https://github.com/kanopi/wp-ai-assistant/issues)
- [Documentation](https://github.com/kanopi/wp-ai-assistant/wiki)
