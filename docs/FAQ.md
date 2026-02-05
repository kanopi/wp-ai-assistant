# Frequently Asked Questions

Common questions and answers for Semantic Knowledge users.

## Table of Contents

- [General Questions](#general-questions)
- [Setup and Configuration](#setup-and-configuration)
- [Troubleshooting](#troubleshooting)
- [Performance and Costs](#performance-and-costs)
- [Privacy and Security](#privacy-and-security)
- [Accessibility](#accessibility)
- [Customization](#customization)
- [Common Issues](#common-issues)

## General Questions

### What is Semantic Knowledge?

Semantic Knowledge is a WordPress plugin that adds AI-powered features to your website:
- **AI Chatbot** - Answers visitor questions using your website content
- **AI Search** - Semantic search that understands meaning, not just keywords

The plugin uses advanced AI technology (OpenAI and Pinecone) to provide intelligent, context-aware responses based on your actual content.

### How does it work?

1. **Indexing** - Your website content is processed and stored as "embeddings" (mathematical representations) in a vector database (Pinecone)
2. **User Query** - When someone asks a question or searches, their query is converted to an embedding
3. **Retrieval** - The system finds the most relevant content chunks from your site
4. **AI Generation** - OpenAI generates a natural response using the retrieved content as context
5. **Response** - User gets an answer with source links for verification

This is called RAG (Retrieval-Augmented Generation) architecture.

### Do I need technical knowledge to use it?

**Basic Usage:** No - once configured by a developer, administrators can manage settings through the WordPress admin interface.

**Initial Setup:** Yes - requires a developer to:
- Install Node.js indexer package
- Configure API keys in environment variables
- Run initial content indexing

**Day-to-Day Management:** No - administrators can:
- Enable/disable features
- Adjust settings
- Monitor logs
- Update content preferences

### What are the requirements?

**Technical Requirements:**
- WordPress 5.6+
- PHP 8.0+
- Node.js 18+ (for indexing)
- HTTPS (recommended)

**External Services:**
- OpenAI account and API key
- Pinecone account and index
- Billing enabled for both services (pay-as-you-go)

### How much does it cost?

**Plugin:** Free and open source

**External API Costs (estimates):**

Small site (1,000 pages, 500 searches/month): **$5-10/month**
- OpenAI: ~$3-5
- Pinecone: Free tier or ~$2

Medium site (5,000 pages, 5,000 searches/month): **$20-40/month**
- OpenAI: ~$15-25
- Pinecone: ~$5-15

Large site (20,000 pages, 20,000 searches/month): **$100-200/month**
- OpenAI: ~$70-150
- Pinecone: ~$30-50

Actual costs vary based on:
- Models used (gpt-4o-mini vs gpt-4o)
- Search volume
- Content size
- Features enabled (AI summaries add cost)

### Can I try it before committing?

Yes! Both OpenAI and Pinecone offer:
- **Free trials** with credits
- **Pinecone free tier** (1 index, 100k vectors)
- **Pay-as-you-go** pricing (no monthly minimum)

Start small:
1. Test on staging site
2. Index limited content (100-500 pages)
3. Monitor costs for a week
4. Scale up if satisfied

### Is my data secure?

Yes, the plugin follows security best practices:

**API Keys:**
- Never stored in database
- Only in environment variables/secrets
- Transmitted over HTTPS only

**User Data:**
- Queries are NOT logged with personal information
- No IP addresses stored (unless custom code added)
- Rate limiting prevents abuse

**External APIs:**
- OpenAI doesn't use API data for training (as of 2024)
- Data encrypted in transit (HTTPS/TLS)
- 30-day retention for abuse monitoring only

See [Privacy and Security](#privacy-and-security) section for details.

### Will it work with my theme?

Yes! The plugin is designed to work with any WordPress theme:

**Chatbot:**
- Floating button: Works with any theme
- Shortcode: Integrates into any page/post

**Search:**
- Shortcode: Works anywhere
- Replace default search: Uses your theme's existing search.php template

**Styling:**
- Default styles included
- Customizable via CSS
- Respects theme design where possible

### Do I need to re-index after adding new content?

**Short answer:** Not for regular publishing.

**Automatic:** The plugin does NOT automatically re-index when you publish new content.

**Manual re-indexing needed:**
- After publishing many new pages (10+)
- After major content updates
- After deleting old content
- After changing indexed post types

**How to re-index:**
Ask your developer to run:
```bash
wp sk-indexer index
```

**Recommendation:** Re-index weekly or monthly depending on publishing frequency.

### Can I use it on multiple sites?

**Yes**, but each site needs:
- Its own plugin installation
- Its own API keys (or shared keys)
- Its own Pinecone index (or shared with domain filtering)

**Shared Index Option:**
Multiple sites can share one Pinecone index. The plugin automatically filters by domain, so content doesn't mix between sites.

**Cost Implications:**
- Shared API keys = combined usage limits
- Shared Pinecone index = one subscription
- More efficient for multi-site networks

## Setup and Configuration

### How do I get API keys?

**OpenAI API Key:**
1. Visit https://platform.openai.com/
2. Sign up or log in
3. Navigate to **API Keys** section
4. Click **Create new secret key**
5. Copy the key (starts with `sk-proj-...`)
6. Add billing information
7. Provide key to your developer

**Pinecone API Key:**
1. Visit https://www.pinecone.io/
2. Sign up or log in
3. Click **API Keys** in sidebar
4. Copy your API key
5. Create an index (if you haven't already)
6. Provide key and index details to developer

### Where do I enter API keys?

**You DON'T enter them in WordPress admin.**

For security, API keys must be configured by your developer as environment variables or server constants.

**Developer adds them to:**
- `.env` file (local development)
- Server environment variables
- Hosting platform settings (Pantheon, WP Engine, etc.)
- `wp-config.php` (as PHP constants)

See [Configuration Guide](CONFIGURATION.md#environment-variables) for details.

### How do I create a Pinecone index?

**Ask your developer** to create the index with these settings:

1. Log in to Pinecone dashboard
2. Click **Create Index**
3. Configure:
   - **Name:** `my-website-content` (or your choice)
   - **Dimensions:** `1536` (for text-embedding-3-small) or `3072` (for text-embedding-3-large)
   - **Metric:** `cosine` (recommended)
   - **Region:** Choose closest to your server
4. Click **Create Index**
5. Note the **Index Host URL**
6. Add Host and Name to WordPress settings

**Important:** Dimension must match the embedding model you choose.

### What post types should I index?

**Recommended starting point:**
```
posts,pages
```

**Common additions:**
- Custom post types: `staff,services,case-studies,testimonials`
- Documentation: `docs,guides,faqs`
- E-commerce: `products`

**What to EXCLUDE:**
- System types (automatically excluded): attachments, revisions, nav menus
- Private/draft content (automatically excluded)
- Admin-only content
- Pages with forms only (no useful content)

**Configure in:** Settings > AI Assistant > Indexer > Post Types to Index

### How long does indexing take?

Depends on content volume:

- **100 pages:** 2-5 minutes
- **500 pages:** 5-15 minutes
- **1,000 pages:** 10-20 minutes
- **5,000 pages:** 45-90 minutes
- **10,000 pages:** 1-2 hours

**Factors affecting speed:**
- Content length
- Chunk size
- Network speed
- OpenAI API rate limits
- Server resources

### Can I test before going live?

**Absolutely recommended!**

**Testing checklist:**
1. Install on staging/development site first
2. Configure with test API keys (separate from production)
3. Index small subset of content (50-100 pages)
4. Test chatbot with various questions
5. Test search with different queries
6. Monitor API costs for 1 week
7. Adjust settings based on results
8. Deploy to production when satisfied

### What if I don't have a developer?

**Options:**

1. **Hire a developer** - Find WordPress developer familiar with:
   - Command line / WP-CLI
   - Node.js
   - Environment variables
   - Server configuration

2. **Use managed hosting with support** - Some hosts (Pantheon, WP Engine) can assist with environment setup

3. **Contact plugin developers** - Kanopi Studios may offer setup services

**Non-negotiable technical steps:**
- Installing Node.js indexer
- Configuring environment variables
- Running indexer commands

These REQUIRE technical knowledge.

## Troubleshooting

### Chatbot button doesn't appear

**Checklist:**

1. **Is chatbot enabled?**
   - Settings > AI Assistant > Chatbot
   - "Enable Chatbot" checkbox checked
   - "Floating Button" checkbox checked

2. **Are API keys configured?**
   - Check "Configuration Status" on General tab
   - Should show green "✓ Configured"

3. **Is content indexed?**
   - Ask developer: `wp sk-indexer check`
   - Should show indexer is installed

4. **Clear cache:**
   - Browser cache (Ctrl+Shift+R / Cmd+Shift+R)
   - Site cache (if using caching plugin)
   - CDN cache (if applicable)

5. **Check JavaScript console:**
   - Press F12, go to Console tab
   - Look for errors
   - Share errors with developer

### Search returns no results

**Possible causes:**

1. **Content not indexed**
   - Solution: Ask developer to run indexing

2. **Minimum score too high**
   - Settings > AI Assistant > Search > Minimum Score
   - Try lowering to 0.4-0.5

3. **Wrong post types indexed**
   - Settings > AI Assistant > Indexer > Post Types to Index
   - Verify your content type is included
   - Re-index if you add post types

4. **Query too specific/obscure**
   - Try simpler, broader queries
   - Use keywords from your content

5. **Content is draft/private**
   - Only published content is indexed
   - Check post status

### Responses are inaccurate or off-topic

**Solutions:**

1. **Increase Top K (retrieve more context)**
   - Settings > AI Assistant > Chatbot/Search
   - Try 7-10 instead of 5

2. **Lower temperature (more focused)**
   - Settings > AI Assistant > Chatbot > Temperature
   - Try 0.1-0.2 for factual content

3. **Improve source content**
   - Make content clearer and more detailed
   - Use proper headings and structure
   - Re-index after improvements

4. **Update system prompt**
   - Settings > AI Assistant > Chatbot > System Prompt
   - Adjust boundaries and context usage instructions

5. **Re-index content**
   - Ensures latest content is used

### "Invalid security token" error

**Cause:** WordPress security nonce expired

**Solution:**
1. Refresh the page (F5)
2. Try the action again
3. If error persists, clear cookies for your site
4. Check server time is correct (nonce validation is time-based)

### Slow response times

**Normal times:**
- Chatbot: 2-5 seconds
- Search: 1-3 seconds
- Search with summary: 3-6 seconds

**If slower, try:**

1. **Reduce Top K**
   - Fewer content chunks = faster
   - Try 3-5 for chatbot, 5-7 for search

2. **Use faster model**
   - Switch to gpt-4o-mini
   - Settings > AI Assistant > Chatbot > OpenAI Model

3. **Enable caching**
   - Ask developer to install Redis
   - Can reduce latency by 80%

4. **Check API status**
   - OpenAI status: https://status.openai.com/
   - Pinecone status: https://status.pinecone.io/

### Rate limit errors

**Message:** "Rate limit exceeded. Please wait..."

**Cause:** Too many requests from same IP

**Default limit:** 10 requests per minute per IP

**Solutions:**

1. **Wait 60 seconds** and try again

2. **If legitimate high usage:**
   - Ask developer to increase rate limits
   - Requires custom filter code

3. **If abuse:**
   - Rate limiting is working correctly
   - Consider stricter limits

### Content not being found by chatbot

**Troubleshooting steps:**

1. **Verify content is indexed:**
   ```bash
   # Developer runs
   wp sk-indexer config
   ```
   Check if post type is included

2. **Check post status:**
   - Content must be published (not draft/private)
   - Must be public (not password protected)

3. **Check indexer exclusions:**
   - Settings > AI Assistant > Indexer > Post Types to Exclude
   - Verify your content type isn't excluded

4. **Test direct search:**
   - Try searching for exact title
   - If found in search but not chat, it's a retrieval issue

5. **Increase Top K:**
   - More context chunks = better chance of finding content

6. **Re-index:**
   - Maybe content wasn't indexed properly

## Performance and Costs

### How can I reduce API costs?

**Top cost-saving strategies:**

1. **Use gpt-4o-mini instead of gpt-4o**
   - 10x cheaper
   - Still good quality
   - Settings > AI Assistant > Chatbot > OpenAI Model

2. **Reduce Top K values**
   - Fewer vectors = fewer API calls
   - Try 3-5 for chatbot, 5-10 for search

3. **Disable AI search summaries**
   - Saves 1 API call per search
   - Settings > AI Assistant > Search > Enable AI Summary
   - Uncheck to disable

4. **Enable caching (ask developer)**
   - Redis/Memcached caching
   - Prevents duplicate API calls
   - Can reduce costs by 50-70%

5. **Implement rate limiting**
   - Default: 10 requests/minute
   - Prevents abuse and runaway costs

6. **Use Pinecone free tier**
   - 1 index, 100,000 vectors
   - Sufficient for small-medium sites

7. **Monitor usage regularly**
   - Check OpenAI dashboard weekly
   - Set usage alerts in OpenAI platform
   - Adjust settings if costs too high

### Why are my costs higher than expected?

**Common causes:**

1. **Using expensive model**
   - gpt-4o costs 10x more than gpt-4o-mini
   - Check: Settings > AI Assistant > Chatbot > OpenAI Model

2. **High Top K values**
   - Top K of 15-20 = many Pinecone queries
   - Check: Settings > AI Assistant > Chatbot/Search

3. **AI summaries enabled**
   - Adds 1 chat completion per search
   - Check: Settings > AI Assistant > Search > Enable AI Summary

4. **High search volume**
   - More users = more API calls
   - Check search logs for usage

5. **No caching**
   - Duplicate queries hit API repeatedly
   - Solution: Enable Redis caching

6. **Large content chunks**
   - Larger chunks = more tokens = higher cost
   - Check: Settings > AI Assistant > Indexer > Chunk Size

7. **Frequent re-indexing**
   - Each index run costs money
   - Only re-index when necessary

### How can I improve response speed?

**Performance optimization strategies:**

1. **Enable object caching (Redis/Memcached)**
   - **Impact:** 80% faster repeat queries
   - Requires server setup (ask developer)

2. **Use faster model**
   - gpt-4o-mini is 2-3x faster than gpt-4o
   - Settings > AI Assistant > Chatbot > OpenAI Model

3. **Reduce Top K**
   - Fewer vectors = faster retrieval
   - Balance: quality vs speed

4. **Enable response compression**
   - Gzip/Brotli compression
   - Reduces bandwidth, speeds up transmission
   - Requires server config

5. **Use CDN**
   - Serve plugin assets from CDN
   - Cloudflare, Fastly, etc.

6. **Optimize server resources**
   - Adequate PHP memory (256MB+)
   - Fast database (SSD, proper indexes)
   - Good network connection

### How do I monitor API usage and costs?

**OpenAI Usage:**
1. Visit https://platform.openai.com/usage
2. View usage by:
   - Date range
   - Model (embeddings vs chat)
   - Cost breakdown
3. Set up usage alerts (if available)

**Pinecone Usage:**
1. Log in to Pinecone dashboard
2. Check:
   - Query volume
   - Storage used
   - Current plan limits

**WordPress Logs:**
1. **AI Chat Logs** - Count chat interactions
2. **AI Search Logs** - Count searches
3. Estimate:
   - Chat: ~$0.001 per interaction (gpt-4o-mini)
   - Search: ~$0.0005 per search (without summary)
   - Search: ~$0.001 per search (with summary)

**Recommendation:** Check weekly, especially first month.

### What happens if I exceed my API limits?

**OpenAI:**
- Requests fail with error message
- Users see "Chatbot API keys are missing" error
- No charges beyond your limit

**Solutions:**
1. Increase usage limits in OpenAI dashboard
2. Upgrade to higher tier
3. Wait for limit to reset (often monthly)

**Pinecone:**
- Free tier: 100,000 vectors max
- Paid: Based on plan
- Exceeding limits prevents new indexing

**Prevention:**
- Monitor usage regularly
- Set alerts when approaching limits
- Budget for expected traffic

## Privacy and Security

### What data is sent to external services?

**OpenAI receives:**
- User queries (search terms, chat questions)
- Content chunks for context
- System prompts

**Pinecone stores:**
- Embedding vectors (mathematical representations, not readable text)
- Metadata: page IDs, URLs, titles, post types
- Domain information

**What is NOT sent:**
- User personal information (by default)
- User IP addresses (by default)
- Authentication cookies
- Form submissions
- Private/draft content

### Is user data logged?

**What IS logged:**
- Chat questions
- Chat answers
- Search queries
- Search results
- Timestamps

**What is NOT logged:**
- User IP addresses (by default)
- User names/emails
- Personal information
- Session data

**Log retention:**
- Default: 90 days
- Automatically cleaned up
- Can be manually deleted

**Purpose:**
- Analytics (what users are asking)
- Quality improvement
- Content gap identification

### Is the plugin GDPR compliant?

**Plugin design is privacy-friendly:**
- No personal data collected by default
- Anonymous logging
- Configurable retention
- Data stored in WordPress database (same residency as your site)

**For GDPR compliance, you should:**

1. **Update Privacy Policy** to mention:
   - AI features use OpenAI and Pinecone
   - Queries are sent to external services
   - No personal data collected
   - Link to OpenAI and Pinecone privacy policies

2. **Data Processing Agreements:**
   - OpenAI has DPA available
   - Pinecone has DPA available
   - Review and sign as needed

3. **User Rights:**
   - Provide data export (WordPress standard)
   - Provide data deletion on request
   - Clear logs on user deletion

4. **Consent (if required):**
   - Some regions require explicit consent for AI features
   - Consider cookie/consent banner mentioning AI features

**Not legal advice** - consult with legal professional for your specific situation.

### Can I prevent specific content from being indexed?

**Yes, several ways:**

1. **Exclude by post type:**
   - Settings > AI Assistant > Indexer > Post Types to Exclude
   - Add post type to exclusion list

2. **Make content private/draft:**
   - Only published, public content is indexed
   - Set post to private or draft status

3. **Use custom filter (developer):**
   ```php
   add_filter('semantic_knowledge_indexer_should_index', function($should_index, $post) {
       // Skip posts in specific category
       if (has_category('private-category', $post)) {
           return false;
       }
       return $should_index;
   }, 10, 2);
   ```

4. **Remove from index (developer):**
   ```bash
   # Delete specific post from index
   wp sk-indexer delete-post <post-id>
   ```

### Are API keys secure?

**Yes, if configured properly:**

**Security measures:**
- Never stored in database
- Only in environment variables or server config
- Transmitted over HTTPS only
- Not visible in WordPress admin
- Not accessible via REST API

**Your responsibilities:**
- Don't commit keys to version control
- Don't share keys via email/chat
- Rotate keys periodically (quarterly)
- Use separate keys per environment
- Restrict key permissions to necessary scopes

**Developer responsibilities:**
- Proper environment variable configuration
- Secure file permissions (600 for config files)
- HTTPS/SSL enabled
- Server hardening

### What happens to my data if I deactivate the plugin?

**On Deactivation:**
- Plugin features stop working
- Chatbot disappears
- Search reverts to default WordPress
- Logs remain in database
- Pinecone vectors remain in index

**On Uninstall:**
- WordPress tables remain (by design)
- Logs remain in database
- Pinecone vectors remain in index

**To fully remove data:**

1. **Clear WordPress logs** (ask developer):
   ```bash
   wp post delete $(wp post list --post_type=ai_chat_log --format=ids) --force
   wp post delete $(wp post list --post_type=ai_search_log --format=ids) --force
   ```

2. **Clear Pinecone index**:
   - Option A: Delete index from Pinecone dashboard
   - Option B: Run `wp sk-indexer delete-all --yes` (before uninstalling)

3. **Uninstall plugin:**
   - Plugins > Installed Plugins
   - Deactivate then Delete

## Accessibility

### Is the plugin accessible?

**Yes!** The plugin is designed with accessibility in mind:

**Compliance Status:**
- WCAG 2.1 Level AA: 74% compliant (actively improving)
- Keyboard accessible: 100%
- Screen reader compatible: Yes

**Accessibility Features:**
- Full keyboard navigation (Tab, Enter, Escape)
- ARIA labels and live regions
- Focus indicators (visible outline)
- Semantic HTML structure
- Screen reader tested (NVDA, JAWS, VoiceOver)
- Reduced motion support
- High color contrast (WCAG AA)

See [ACCESSIBILITY.md](../ACCESSIBILITY.md) for complete statement.

### Can I use it with keyboard only?

**Yes!** All features are fully keyboard accessible.

**Chatbot Navigation:**
1. Tab to chatbot button
2. Press Enter or Space to open
3. Tab to input field and type question
4. Tab to send button and press Enter
5. Press Escape to close popup

**Search Navigation:**
1. Tab to search input
2. Type query
3. Press Enter or Tab to button and press Enter
4. Tab through results
5. Press Enter on a result to visit page

**Keyboard Shortcuts:**
- `Tab` - Move forward
- `Shift+Tab` - Move backward
- `Enter` - Activate
- `Space` - Activate buttons
- `Escape` - Close chatbot popup

### Does it work with screen readers?

**Yes!** Tested with:
- NVDA (Windows)
- JAWS (Windows)
- VoiceOver (Mac/iOS)

**Screen Reader Features:**
- Descriptive labels for all inputs
- ARIA live regions announce results
- Status updates announced
- Clear landmark navigation
- Proper heading structure

**Known Limitation:**
The chatbot uses a third-party component (Deep Chat) which may have some minor screen reader limitations. We're actively working to improve this.

### How do I report accessibility issues?

**We take accessibility seriously!**

**To report an issue:**
1. Visit https://github.com/kanopi/semantic-knowledge/issues
2. Click "New Issue"
3. Add label "Accessibility Issue"
4. Describe:
   - What you were trying to do
   - What happened (or didn't happen)
   - Your assistive technology (screen reader, etc.)
   - Browser and version

**Or email:** accessibility@kanopi.com

**We aim to respond within 48 hours.**

### Can I customize accessibility features?

**Yes, developers can:**

1. **Customize ARIA labels:**
   ```php
   add_filter('semantic_knowledge_chatbot_aria_label', function($label) {
       return 'Custom accessible label';
   });
   ```

2. **Adjust focus management:**
   ```javascript
   // Custom focus behavior
   ```

3. **Add skip links:**
   ```html
   <a href="#ai-chatbot" class="skip-link">Skip to AI Chat</a>
   ```

4. **Override default styles:**
   ```css
   /* Enhance focus indicators */
   .sk-chatbot:focus {
       outline: 3px solid blue;
   }
   ```

## Customization

### Can I customize the chatbot appearance?

**Yes!** Several ways:

**1. Via CSS (theme stylesheet):**
```css
/* Chatbot button */
.sk-chatbot-button {
    background: your-brand-color;
    border-radius: 50%;
}

/* Chat popup */
.sk-chatbot-popup {
    max-width: 400px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
```

**2. Via system prompt (personality):**
- Settings > AI Assistant > Chatbot > System Prompt
- Edit PERSONA section to match brand voice

**3. Via intro message:**
- Settings > AI Assistant > Chatbot > Intro Message
- Customize welcome message

**4. Via developer filters:**
```php
// Customize chatbot config
add_filter('semantic_knowledge_chatbot_config', function($config) {
    $config['theme'] = 'dark';
    return $config;
});
```

### Can I change the chatbot personality?

**Yes!** Edit the system prompt:

1. Navigate to Settings > AI Assistant > Chatbot
2. Scroll to "System Prompt"
3. Find the "PERSONA" section
4. Edit to match your brand voice

**Examples:**

**Professional/Corporate:**
```
PERSONA
You speak with professional expertise:
- Clear, accurate, and concise
- Knowledgeable but approachable
- Focus on providing value
```

**Friendly/Casual:**
```
PERSONA
You're a friendly helper:
- Conversational and warm
- Helpful and patient
- Use simple language
```

**Technical/Expert:**
```
PERSONA
You're a technical expert:
- Precise and detailed
- Use proper terminology
- Provide code examples when relevant
```

### Can I prioritize specific content types?

**Yes!** Two methods:

**1. System Prompt (no code required) - RECOMMENDED**

Edit Search System Prompt > CONTENT PREFERENCES:

```
CONTENT PREFERENCES

- For service inquiries, prioritize Services pages
- For support questions, emphasize Documentation
- For product questions, prioritize Product pages over blog posts
```

**2. Relevance Boosting (admin settings):**

Settings > AI Assistant > Search > Advanced Relevance Boosting:
- Adjust URL, title, and post type boosts

**3. Custom Filters (developer):**

```php
add_filter('semantic_knowledge_search_relevance_config', function($config, $query) {
    // Boost custom post types
    $config['post_type_boosts']['services'] = 0.08;
    $config['post_type_boosts']['case-study'] = 0.06;
    return $config;
}, 10, 2);
```

See [CUSTOMIZATION.md](CUSTOMIZATION.md) for detailed examples.

### Can I add custom fields to indexing?

**Yes, developers can:**

```php
add_filter('semantic_knowledge_indexer_post_data', function($data, $post) {
    // Add custom field to indexed content
    $custom_value = get_post_meta($post->ID, 'custom_field', true);
    if ($custom_value) {
        $data['content'] .= "\n\n" . $custom_value;
    }
    return $data;
}, 10, 2);
```

After adding filter, re-index content.

### Can I customize search results display?

**Yes, several ways:**

**1. Filter results (developer):**
```php
add_filter('semantic_knowledge_search_results', function($results, $query, $matches) {
    // Customize results array
    foreach ($results as &$result) {
        // Add featured image
        $result['thumbnail'] = get_the_post_thumbnail_url($result['post_id']);
    }
    return $results;
}, 10, 3);
```

**2. Customize AI summary (developer):**
```php
add_filter('semantic_knowledge_search_summary', function($summary, $query) {
    // Add custom formatting or CTA
    $summary .= '<p class="cta">Need help? <a href="/contact/">Contact us</a></p>';
    return $summary;
}, 10, 2);
```

**3. Override CSS:**
```css
.sk-search__results {
    /* Custom styles */
}
```

### Can I track analytics?

**Yes, developers can:**

**Google Analytics 4:**
```php
add_action('semantic_knowledge_chatbot_query_end', function($response, $question) {
    // Track chat interactions
    ?>
    <script>
    gtag('event', 'ai_chat', {
        'event_category': 'engagement',
        'event_label': 'Chat Query'
    });
    </script>
    <?php
}, 10, 2);

add_action('semantic_knowledge_search_query_end', function($response, $query) {
    // Track searches
    ?>
    <script>
    gtag('event', 'ai_search', {
        'event_category': 'engagement',
        'event_label': 'Search Query',
        'value': <?php echo $response['total']; ?>
    });
    </script>
    <?php
}, 10, 2);
```

**Custom Analytics:**
```php
add_action('semantic_knowledge_chatbot_query_end', function($response, $question) {
    // Send to custom analytics service
    wp_remote_post('https://your-analytics.com/track', [
        'body' => [
            'event' => 'ai_chat',
            'question' => $question,
            'sources_count' => count($response['sources'])
        ]
    ]);
}, 10, 2);
```

## Common Issues

### "Node.js indexer not found"

**Cause:** Indexer package not installed

**Solution (ask developer):**

**For DDEV/Local:**
```bash
cd packages/wp-ai-indexer
npm install && npm run build
```

**For Production/Global:**
```bash
npm install -g @kanopi/wp-ai-indexer
```

**Verify:**
```bash
wp sk-indexer check
```

### "Configuration Status: Not configured"

**Causes:**

1. API keys not set (most common)
2. Pinecone settings empty/incorrect
3. Settings not saved

**Solutions:**

1. **Check environment variables set:**
   - Ask developer to verify keys are configured

2. **Fill in Pinecone settings:**
   - Settings > AI Assistant > General
   - Enter Pinecone Index Host
   - Enter Pinecone Index Name
   - Click Save Changes

3. **Save settings again:**
   - Sometimes just re-saving fixes it

4. **Refresh page:**
   - Clear browser cache
   - Reload page

### Chatbot says "I don't have that information" for existing content

**Possible causes:**

1. **Content not indexed:**
   - Solution: Re-index content

2. **Top K too low:**
   - Increase Top K to 7-10
   - Settings > AI Assistant > Chatbot > Top K Results

3. **Content in wrong post type:**
   - Check if post type is indexed
   - Settings > AI Assistant > Indexer > Post Types to Index

4. **Query too specific:**
   - Try broader, simpler questions

5. **Content quality:**
   - Improve content clarity
   - Add more details
   - Use clear headings
   - Re-index after improvements

### Search results don't match content

**Troubleshooting:**

1. **Check minimum score:**
   - Lower to 0.4-0.5
   - Settings > AI Assistant > Search > Minimum Score

2. **Increase Top K:**
   - More results considered
   - Settings > AI Assistant > Search > Top K

3. **Verify post type indexed:**
   - Settings > AI Assistant > Indexer > Post Types to Index

4. **Check relevance boosting:**
   - Settings > AI Assistant > Search > Advanced Relevance Boosting
   - Adjust boosts if over/under-prioritizing

5. **Review content preferences:**
   - Settings > AI Assistant > Search > Search System Prompt
   - CONTENT PREFERENCES section may be filtering results

### High API costs suddenly

**Investigation steps:**

1. **Check OpenAI usage dashboard:**
   - Look for spike in usage
   - Identify which model/endpoint

2. **Check WordPress logs:**
   - AI Chat Logs - count recent interactions
   - AI Search Logs - count recent searches
   - Look for unusual patterns

3. **Check for abuse:**
   - Many requests from same IP?
   - Automated/bot traffic?

4. **Review settings:**
   - Did Top K increase?
   - Did model change to gpt-4o?
   - Are AI summaries enabled?

5. **Enable stricter rate limiting:**
   - Ask developer to reduce limits temporarily

### Can't delete old logs

**Manual deletion:**

1. Navigate to AI Chat Logs or AI Search Logs
2. Check boxes next to entries
3. Bulk Actions > Move to Trash
4. Apply

**Database cleanup (ask developer):**
```bash
# Delete logs older than 30 days
wp db query "DELETE FROM semantic_knowledge_chat_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);"
wp db query "DELETE FROM semantic_knowledge_search_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);"
```

### Plugin conflicts with another plugin

**Symptoms:**
- JavaScript errors
- Features not loading
- White screen of death

**Troubleshooting:**

1. **Disable other plugins one by one:**
   - Find conflicting plugin
   - Report to both plugin developers

2. **Check for JavaScript conflicts:**
   - Open browser console (F12)
   - Look for errors
   - Note which scripts are conflicting

3. **Check for REST API conflicts:**
   - Some plugins block REST API
   - Semantic Knowledge requires REST API access

**Common conflicts:**
- Security plugins blocking REST API
- Caching plugins serving stale assets
- Page builders interfering with shortcodes

**Resolution:**
- Whitelist REST API endpoints in security plugins
- Exclude plugin assets from cache
- Contact plugin developers for support

---

## Still Have Questions?

**Documentation:**
- [User Guide](USER-GUIDE.md) - Complete usage guide
- [Configuration Guide](CONFIGURATION.md) - Detailed settings reference
- [Shortcodes Guide](SHORTCODES.md) - Shortcode examples

**Support:**
- [GitHub Issues](https://github.com/kanopi/semantic-knowledge/issues) - Bug reports and feature requests
- [Developer Documentation](https://github.com/kanopi/wp-ai-indexer) - Technical docs
- Developer contact - For custom development and support

**Last Updated:** January 2025
**Plugin Version:** 1.0.0
