# User Guide: Semantic Knowledge Plugin

A comprehensive guide for site administrators and content editors using the Semantic Knowledge plugin.

## Table of Contents

- [Introduction](#introduction)
- [Getting Started](#getting-started)
- [Installation and Activation](#installation-and-activation)
- [Initial Configuration](#initial-configuration)
- [Using the AI Chatbot](#using-the-ai-chatbot)
- [Using AI Search](#using-ai-search)
- [Managing Logs](#managing-logs)
- [Settings Reference](#settings-reference)
- [Best Practices](#best-practices)
- [Common Tasks](#common-tasks)
- [Troubleshooting](#troubleshooting)

## Introduction

### What is Semantic Knowledge?

Semantic Knowledge is a WordPress plugin that provides AI-powered features for your website:

- **AI Chatbot** - An intelligent chatbot that answers visitor questions using your website's content
- **AI Search** - Semantic search that understands meaning, not just keywords
- **RAG Architecture** - Retrieval-Augmented Generation ensures responses are grounded in your actual content

### How Does It Work?

The plugin uses advanced AI technology:

1. **Content Indexing** - Your website content is processed and stored in a vector database (Pinecone)
2. **User Query** - When a visitor asks a question or searches, the AI finds relevant content
3. **AI Response** - OpenAI generates a natural, helpful response based on your content
4. **Source Attribution** - Responses include links to source pages for verification

### Key Benefits

- **Improved User Experience** - Visitors find answers faster
- **Reduced Support Burden** - Common questions answered automatically
- **Better Content Discovery** - Semantic search finds relevant content even with different wording
- **Always Up-to-Date** - Responses based on your current content

### Who Should Use This Guide?

This guide is for:
- Website administrators configuring the plugin
- Content managers understanding how it works
- Site editors who need to monitor usage
- Anyone responsible for website user experience

## Getting Started

### Prerequisites

Before you begin, ensure you have:

#### Required Accounts and Keys

1. **OpenAI API Account**
   - Sign up at https://platform.openai.com/
   - Create an API key from the API Keys section
   - Add billing information (plugin uses pay-as-you-go pricing)

2. **Pinecone Account**
   - Sign up at https://www.pinecone.io/
   - Create a free or paid index
   - Note your API key and index details

3. **WordPress Administrator Access**
   - You need admin-level access to configure the plugin
   - Access to server environment variables (or work with your developer)

#### Technical Requirements

- WordPress 5.6 or higher
- PHP 8.0 or higher
- Node.js 18+ (for indexing content)
- HTTPS enabled (recommended for API security)

### Cost Considerations

The plugin uses external APIs with usage-based pricing:

**OpenAI Costs (as of 2024):**
- Embeddings: ~$0.02 per 1,000 pages indexed
- Chat completions: ~$0.10 per 1,000 questions answered
- Search summaries: ~$0.05 per 1,000 searches

**Pinecone Costs:**
- Free tier: 1 index, 100,000 vectors
- Paid plans start at $70/month for higher limits

**Estimated Monthly Costs:**
- Small site (1,000 pages, 500 searches/month): $5-10/month
- Medium site (5,000 pages, 5,000 searches/month): $20-40/month
- Large site (20,000 pages, 20,000 searches/month): $100-200/month

### Quick Start Checklist

- [ ] Obtain OpenAI API key
- [ ] Create Pinecone index
- [ ] Install and activate plugin
- [ ] Configure environment variables (work with developer)
- [ ] Complete WordPress admin settings
- [ ] Index your content
- [ ] Test chatbot and search
- [ ] Monitor usage and costs

## Installation and Activation

### Step 1: Install the Plugin

The plugin may be installed via:

#### Manual Upload

1. Download the plugin ZIP file
2. Navigate to **Plugins > Add New** in WordPress admin
3. Click **Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Click **Activate Plugin**

#### FTP Upload

1. Extract the plugin ZIP file
2. Upload the `semantic-knowledge` folder to `/wp-content/plugins/`
3. Navigate to **Plugins** in WordPress admin
4. Find "Semantic Knowledge" and click **Activate**

#### Via Composer (for developers)

```bash
composer require kanopi/semantic-knowledge
```

### Step 2: Install Node.js Indexer

The plugin requires a Node.js package for indexing content.

**Contact your developer** to install the indexer:

```bash
# For local/DDEV environments
cd packages/wp-ai-indexer
npm install && npm run build

# For production environments
npm install -g @kanopi/wp-ai-indexer
```

You can verify installation from WordPress:
- Navigate to **Settings > AI Assistant**
- Look for system status indicators
- A green checkmark means the indexer is installed correctly

### Step 3: Verify Installation

After activation, check that everything is working:

1. Navigate to **Settings > AI Assistant**
2. You should see the plugin settings page with four tabs:
   - General
   - Chatbot
   - Search
   - Indexer
3. Check the "Configuration Status" - it will show "Not configured" until you complete setup

## Initial Configuration

### Understanding API Keys and Security

**Important Security Note:** For security reasons, API keys cannot be entered through the WordPress admin interface. They must be configured via environment variables or server constants.

**Work with your developer** to configure these required keys:

```bash
# Required environment variables
OPENAI_API_KEY=sk-your-openai-key
PINECONE_API_KEY=your-pinecone-key
Semantic_Knowledge_INDEXER_KEY=your-secure-random-string
```

Your developer can add these to:
- Environment variables (.env file)
- Server configuration
- Hosting platform settings (Pantheon, WP Engine, etc.)
- wp-config.php (as PHP constants)

### Step 1: Configure General Settings

Navigate to **Settings > AI Assistant > General**

#### Pinecone Configuration

1. **Pinecone Index Host**
   - Enter the full URL of your Pinecone index
   - Format: `https://your-index-name.svc.region.pinecone.io`
   - Find this in your Pinecone dashboard under "Index URL"

2. **Pinecone Index Name**
   - Enter your index name (e.g., `my-website-content`)
   - Must match the index you created in Pinecone

3. **Embedding Model**
   - Recommended: `text-embedding-3-small` (default, most cost-effective)
   - Alternative: `text-embedding-3-large` (higher quality, higher cost)
   - Legacy: `text-embedding-ada-002` (older model)

4. **Embedding Dimension**
   - `1536` for text-embedding-3-small (default)
   - `3072` for text-embedding-3-large
   - Must match your Pinecone index dimension

#### Verification

After entering settings, check the **Configuration Status**:
- Green checkmark = Configured correctly
- Red X = Missing or incorrect settings

Click **Save Changes** to save your configuration.

### Step 2: Enable Features

Choose which features to enable:

#### Enable Chatbot

Navigate to **Settings > AI Assistant > Chatbot**

1. Check **Enable Chatbot** checkbox
2. Configure chatbot settings (see Chatbot Settings section)
3. Save changes

#### Enable Search

Navigate to **Settings > AI Assistant > Search**

1. Check **Enable Search** checkbox
2. Configure search settings (see Search Settings section)
3. Save changes

### Step 3: Index Your Content

Before the AI features work, you must index your website content.

**Ask your developer** to run the indexing command:

```bash
# Via WP-CLI
wp sk-indexer index

# Or with debug output
wp sk-indexer index --debug
```

Indexing time depends on your content:
- 100 pages: ~2-5 minutes
- 1,000 pages: ~10-20 minutes
- 10,000 pages: ~1-2 hours

You'll see output showing progress:
```
Fetching settings from WordPress...
Indexing posts...
Processing: 150/500 posts
Uploading vectors to Pinecone...
✓ Indexing complete!
```

### Step 4: Test the Features

After indexing completes:

#### Test the Chatbot

1. Visit your website's homepage
2. Look for the floating chat button (if enabled)
3. Click to open the chat
4. Ask a question about your website content
5. Verify you get a relevant response with source links

#### Test Search

1. Use the search form on your site
2. Try a search query related to your content
3. Check if you see an AI-generated summary (if enabled)
4. Verify search results are relevant

## Using the AI Chatbot

### Two Display Modes

The chatbot can be displayed in two ways:

#### 1. Floating Button (Site-Wide)

**Enable in Settings:**
1. Navigate to **Settings > AI Assistant > Chatbot**
2. Check **Floating Button** checkbox
3. Save changes

The floating button appears on all pages:
- Fixed position in the bottom-right corner
- Clicks to open chat popup
- Users can close with X button or Escape key

**Best for:**
- Making chat available everywhere
- Encouraging visitor engagement
- Providing constant support access

#### 2. Shortcode (Specific Pages)

Add chatbot to specific pages using the `[ai_chatbot]` shortcode.

**Inline Mode:**
```
[ai_chatbot mode="inline"]
```
Displays chat interface directly on the page.

**Popup Mode:**
```
[ai_chatbot mode="popup" button="Chat with Us"]
```
Displays a button that opens chat in a popup.

**Best for:**
- Support pages
- Contact forms
- Product pages
- FAQ sections

### Chatbot Features

#### Natural Conversation

The chatbot understands natural language:
- "What services do you offer?"
- "How do I contact support?"
- "Tell me about your pricing"

Responses are conversational and helpful, using your actual website content.

#### Source Attribution

Every response includes source links:
- Shows which pages the information came from
- Includes relevance scores (0.0-1.0)
- Allows users to verify information

#### Context Awareness

The chatbot:
- Uses your site's content as its knowledge base
- Stays on topic (your website)
- Says "I don't have that information" if content isn't found
- Never invents or guesses information

#### Accessibility Features

The chatbot is fully accessible:
- **Keyboard Navigation** - Tab, Enter, and Escape keys work
- **Screen Readers** - ARIA labels and announcements
- **Focus Management** - Clear focus indicators
- **High Contrast** - WCAG AA color contrast

### Managing Chat Logs

View chat history in WordPress admin.

#### Accessing Logs

1. Navigate to **AI Chat Logs** in the WordPress admin menu
2. You'll see a list of all chat interactions

#### Log Information

Each log entry shows:
- **Question** - What the user asked
- **Date** - When the interaction occurred
- **Answer Preview** - First few words of the AI response

#### Viewing Full Details

Click on any log entry to see:
- Full question text
- Complete AI response
- Source pages used
- Relevance scores

#### Log Retention

Chat logs are stored for 90 days by default (configurable). Old logs are automatically deleted via daily cron job.

## Using AI Search

### Search Display Modes

#### Replace Default WordPress Search

Enable in **Settings > AI Assistant > Search**:
1. Check **Replace Default Search** checkbox
2. Save changes

This automatically upgrades your existing search forms to use AI-powered search.

**Benefits:**
- No theme modifications needed
- Works with any search form
- Seamless upgrade

#### Use Search Shortcode

Add search to specific pages using `[ai_search]` shortcode:

```
[ai_search placeholder="Search our site..." button="Search"]
```

**Best for:**
- Custom search pages
- Landing pages
- Sidebar widgets (if theme supports shortcodes in widgets)

### AI Search Features

#### Semantic Understanding

AI search understands meaning, not just keywords:

**Traditional Keyword Search:**
- Query: "wordpress development"
- Finds: Only pages with exact words "wordpress" and "development"

**AI Semantic Search:**
- Query: "wordpress development"
- Finds: Pages about WordPress, WP plugins, theme customization, CMS development, even if those exact words aren't used

#### AI-Generated Summary

When enabled, search results show an AI summary at the top (like Google's AI Overviews):

**The summary includes:**
- Direct answer to the search query
- Key points from the results
- Links to 2-4 most relevant pages
- Clear, scannable format

**Configuration:**
1. Navigate to **Settings > AI Assistant > Search**
2. Check **Enable AI Summary**
3. Save changes

#### Relevance Boosting

Search intelligently ranks results:
- **Exact matches** - Pages with query words in title or URL rank higher
- **Post type priority** - Pages rank slightly higher than posts (configurable)
- **Content quality** - More comprehensive content ranks better
- **Freshness** - Recently updated content can be prioritized (via system prompt)

### Search System Prompt

The search system prompt controls how AI analyzes and presents results.

#### Editing Content Preferences

1. Navigate to **Settings > AI Assistant > Search**
2. Scroll to **Search System Prompt**
3. Find the "CONTENT PREFERENCES" section
4. Add your preferences in plain English

**Example - Prioritize Documentation:**
```
CONTENT PREFERENCES

Content Type Priority:
- For technical questions, prioritize documentation pages over blog posts
- For examples and tutorials, emphasize guide and how-to content
- De-emphasize marketing pages unless specifically relevant
```

**Example - Platform-Specific:**
```
CONTENT PREFERENCES

Platform Priority:
- When users ask about WordPress, prioritize WordPress-related pages
- When users ask about Drupal, prioritize Drupal-related pages
- If query mentions one platform, de-emphasize content about other platforms
```

**Example - Industry-Specific (Healthcare):**
```
CONTENT PREFERENCES

Content Priority:
- For medical information queries, prioritize clinical documentation
- For patient questions, emphasize patient education materials
- Always highlight the most current clinical guidelines
- De-emphasize general marketing content for clinical questions
```

See [CONFIGURATION.md](CONFIGURATION.md) for more examples.

### Managing Search Logs

View search history in WordPress admin.

#### Accessing Logs

1. Navigate to **AI Search Logs** in the WordPress admin menu
2. You'll see a list of all search queries

#### Log Information

Each log entry shows:
- **Search Query** - What the user searched for
- **Date** - When the search occurred
- **Results Found** - Number of results returned

#### Viewing Full Details

Click on any log entry to see:
- The full search query
- All results with titles and URLs
- Relevance scores for each result

#### Analyzing Search Data

Use search logs to:
- Identify popular topics
- Find content gaps
- Improve content strategy
- Monitor what users are looking for

## Managing Logs

### Log Storage

Both chat and search interactions are logged in WordPress database tables (not custom post types for optimal performance).

### Log Retention

**Default:** Logs are kept for 90 days

**Configure retention period:**
Logs are automatically cleaned up via daily WordPress cron job. While there's no UI setting for retention, developers can adjust the `log_retention_days` setting.

### Privacy Considerations

#### What is Logged

**Chat Logs:**
- User question
- AI response
- Source pages used
- Timestamp

**Search Logs:**
- Search query
- Results returned
- Timestamp

#### What is NOT Logged

- User IP addresses (unless added via custom code)
- User identification
- Personal information
- Form data or messages

#### GDPR Compliance

The plugin is designed with privacy in mind:
- No personal data collected by default
- Logs are anonymized
- Retention limits prevent long-term storage
- Data is stored in WordPress database (same data residency as your WP site)

**For GDPR compliance:**
- Update your Privacy Policy to mention AI features
- Inform users that queries are sent to OpenAI (data processing agreement)
- Provide option to request log deletion (via standard WordPress data export/deletion)

### Clearing Logs

#### Manual Deletion

Delete individual logs:
1. Navigate to **AI Chat Logs** or **AI Search Logs**
2. Hover over a log entry
3. Click **Trash**

#### Bulk Deletion

1. Navigate to logs
2. Check boxes next to multiple entries
3. Select "Move to Trash" from bulk actions dropdown
4. Click Apply

#### Complete Log Cleanup

**Ask your developer** to run:

```bash
# Clear all chat logs
wp post delete $(wp post list --post_type=ai_chat_log --format=ids) --force

# Clear all search logs
wp post delete $(wp post list --post_type=ai_search_log --format=ids) --force
```

## Settings Reference

Comprehensive reference for all plugin settings.

### General Settings

Navigate to **Settings > AI Assistant > General**

#### Pinecone Index Host
- **Type:** URL
- **Required:** Yes
- **Format:** `https://your-index.svc.region.pinecone.io`
- **Description:** Full URL of your Pinecone index
- **Find it:** Pinecone dashboard > Index details > Index URL

#### Pinecone Index Name
- **Type:** Text
- **Required:** Yes
- **Description:** Name of your Pinecone index
- **Example:** `my-website-content`

#### Embedding Model
- **Type:** Dropdown
- **Default:** `text-embedding-3-small`
- **Options:**
  - `text-embedding-3-small` - Most cost-effective, good quality
  - `text-embedding-3-large` - Higher quality, 2x cost
  - `text-embedding-ada-002` - Legacy model
- **Description:** OpenAI model used to create embeddings

#### Embedding Dimension
- **Type:** Number
- **Default:** `1536`
- **Options:**
  - `1536` for text-embedding-3-small
  - `3072` for text-embedding-3-large
- **Description:** Vector dimension; must match Pinecone index
- **Note:** Cannot be changed after index creation

### Chatbot Settings

Navigate to **Settings > AI Assistant > Chatbot**

#### Enable Chatbot
- **Type:** Checkbox
- **Default:** Unchecked
- **Description:** Enable chatbot functionality site-wide

#### System Prompt
- **Type:** Textarea
- **Default:** Pre-configured prompt defining chatbot behavior
- **Description:** Instructions for the chatbot's behavior and personality
- **Tips:**
  - Keep the output format requirements (HTML tags)
  - Customize the persona section to match your brand voice
  - Adjust boundaries to stay on topic

#### OpenAI Model
- **Type:** Text
- **Default:** `gpt-4o-mini`
- **Options:**
  - `gpt-4o-mini` - Fastest, most cost-effective
  - `gpt-4o` - Higher quality, slower
  - `gpt-4-turbo` - Previous generation
- **Description:** OpenAI chat completion model

#### Temperature
- **Type:** Number (0.0-2.0)
- **Default:** `0.2`
- **Range:** 0.0 to 2.0, step 0.1
- **Description:** Response creativity/randomness
  - `0.0-0.3` - Focused, consistent (recommended for factual info)
  - `0.4-0.7` - Balanced
  - `0.8-2.0` - Creative, varied

#### Top K Results
- **Type:** Number
- **Default:** `5`
- **Range:** 1 to 20
- **Description:** Number of relevant content chunks to retrieve
- **Impact:** More chunks = better context, but higher cost and slower

#### Floating Button
- **Type:** Checkbox
- **Default:** Unchecked
- **Description:** Display floating chat button site-wide

#### Intro Message
- **Type:** Text
- **Default:** "Hi! I can help you explore this website. Ask me a question to get started."
- **Supports:** HTML tags
- **Description:** First message shown when chat opens

#### Input Placeholder
- **Type:** Text
- **Default:** "Ask a question..."
- **Description:** Placeholder text in chat input field

### Search Settings

Navigate to **Settings > AI Assistant > Search**

#### Enable Search
- **Type:** Checkbox
- **Default:** Unchecked
- **Description:** Enable AI-powered search functionality

#### Top K Results
- **Type:** Number
- **Default:** `10`
- **Range:** 1 to 50
- **Description:** Number of results to return from Pinecone

#### Minimum Score
- **Type:** Number (0.0-1.0)
- **Default:** `0.5`
- **Range:** 0.0 to 1.0, step 0.1
- **Description:** Minimum relevance score (filter out low-quality results)
- **Tips:**
  - `0.7-1.0` - Only very relevant results (may be too strict)
  - `0.5-0.7` - Good balance (recommended)
  - `0.0-0.5` - Include less relevant results

#### Replace Default Search
- **Type:** Checkbox
- **Default:** Unchecked
- **Description:** Replace WordPress default search with AI search

#### Results Per Page
- **Type:** Number
- **Default:** `10`
- **Range:** 1 to 50
- **Description:** Number of results per page

#### Search Placeholder
- **Type:** Text
- **Default:** "Search with AI..."
- **Description:** Placeholder text in search input

#### Enable AI Summary
- **Type:** Checkbox
- **Default:** Checked
- **Description:** Generate AI summary at top of search results (like Google AI Overviews)

#### Search System Prompt
- **Type:** Textarea
- **Default:** Pre-configured prompt for search summaries
- **Description:** Controls how AI analyzes and presents search results
- **Key Section:** CONTENT PREFERENCES (edit to customize behavior)

#### Advanced Relevance Boosting

**Enable Relevance Boosting**
- **Type:** Checkbox
- **Default:** Checked
- **Description:** Apply algorithmic relevance boosting to improve ranking

**URL Slug Match Boost**
- **Type:** Number (0.0-1.0)
- **Default:** `0.15`
- **Description:** Boost when query words appear in URL slug

**Exact Title Match Boost**
- **Type:** Number (0.0-1.0)
- **Default:** `0.12`
- **Description:** Boost when page title exactly matches query

**Title All Words Boost**
- **Type:** Number (0.0-1.0)
- **Default:** `0.08`
- **Description:** Boost when title contains all query words

**Page Post Type Boost**
- **Type:** Number (0.0-1.0)
- **Default:** `0.05`
- **Description:** Boost for WordPress pages (typically more authoritative)

### Indexer Settings

Navigate to **Settings > AI Assistant > Indexer**

#### Post Types to Index
- **Type:** Text (comma-separated)
- **Default:** `posts,pages`
- **Description:** Post types to include in index
- **Examples:**
  - `posts,pages,staff,case-study`
  - `posts,pages,products`

#### Post Types to Exclude
- **Type:** Textarea (comma-separated)
- **Default:** System post types (attachments, revisions, etc.)
- **Description:** Post types to exclude from indexing
- **Note:** System types are excluded by default

#### Auto-discover Post Types
- **Type:** Checkbox
- **Default:** Unchecked
- **Description:** Automatically discover and index REST-visible post types

#### Clean Deleted Content
- **Type:** Checkbox
- **Default:** Unchecked
- **Description:** Remove vectors for deleted posts during indexing

#### Chunk Size
- **Type:** Number
- **Default:** `1200`
- **Range:** 100 to 10000 characters
- **Description:** Size of content chunks for indexing
- **Tips:**
  - Smaller (500-800) - More precise, more vectors, higher cost
  - Medium (1000-1500) - Balanced (recommended)
  - Larger (2000+) - Less precise, fewer vectors, lower cost

#### Chunk Overlap
- **Type:** Number
- **Default:** `200`
- **Range:** 0 to 1000 characters
- **Description:** Overlapping characters between chunks
- **Purpose:** Prevents context loss at chunk boundaries

#### Custom Node.js Path
- **Type:** Text
- **Default:** Empty (auto-detect)
- **Description:** Custom path to Node.js executable
- **Example:** `/usr/local/bin/node`
- **When to use:** If auto-detection fails or need specific version

## Best Practices

### Content Strategy

#### Write Clear, Informative Content

AI responses are only as good as your content:

**Good:**
- Clear headings and structure
- Complete sentences
- Specific information
- Examples and details

**Avoid:**
- Keyword stuffing
- Fragmented content
- Vague descriptions
- Missing context

#### Organize Content Logically

- Use proper heading hierarchy (H1 → H2 → H3)
- Group related information together
- Link between related pages
- Avoid duplicate content

#### Keep Content Updated

- Review and update regularly
- Remove outdated information
- Re-index after major content changes
- Monitor search logs to identify content gaps

### Configuration Tips

#### Start Conservative, Then Adjust

**Initial Settings:**
- Top K: 5-10
- Temperature: 0.2-0.3
- Minimum Score: 0.5-0.6
- Enable AI Summary: Yes

Monitor performance and adjust based on:
- Response quality
- Response speed
- User feedback
- Costs

#### Test Before Enabling Site-Wide

1. Test with shortcode on a hidden page
2. Try various question types
3. Check response quality
4. Verify source attribution
5. Then enable floating button site-wide

#### Balance Quality and Cost

**Higher Quality (Higher Cost):**
- Use gpt-4o model
- Higher Top K (10-15)
- Lower temperature (0.1-0.2)

**Lower Cost (Good Quality):**
- Use gpt-4o-mini model
- Lower Top K (3-5)
- Temperature (0.2-0.3)

### Monitoring and Maintenance

#### Regular Tasks

**Weekly:**
- Check chat and search logs
- Identify common questions
- Look for unanswered or poorly answered queries

**Monthly:**
- Review API usage and costs
- Analyze search trends
- Update content based on gaps identified
- Re-index if significant content changes

**Quarterly:**
- Review and update system prompts
- Adjust relevance boosting settings
- Consider model upgrades
- Evaluate ROI

#### Performance Monitoring

**Watch for:**
- Slow response times (>3-5 seconds)
- High API costs
- Low-quality responses
- Unanswered questions

**Optimize by:**
- Reducing Top K
- Using faster models
- Enabling object caching
- Updating content for clarity

#### Cost Management

**Monitor via:**
- OpenAI usage dashboard
- Pinecone usage dashboard
- Search and chat log counts

**Control costs:**
- Set OpenAI usage limits
- Use gpt-4o-mini instead of gpt-4o
- Reduce Top K values
- Implement caching (ask developer)
- Set rate limits (configured by developer)

## Common Tasks

### How to Enable the Chatbot

1. Navigate to **Settings > AI Assistant > Chatbot**
2. Check **Enable Chatbot**
3. Optionally check **Floating Button** for site-wide display
4. Click **Save Changes**
5. Visit your website to see the chatbot

### How to Add Search to a Page

1. Edit the page where you want search
2. Add the shortcode: `[ai_search]`
3. Optionally customize: `[ai_search placeholder="Search our docs..." button="Search"]`
4. Click **Update** to save the page
5. View the page to see the search form

### How to Customize Chatbot Personality

1. Navigate to **Settings > AI Assistant > Chatbot**
2. Scroll to **System Prompt**
3. Find the "PERSONA" section
4. Edit the personality description:
   ```
   PERSONA
   You speak with the voice of this website:
   - [Your brand personality here]
   - [Your tone preferences here]
   ```
5. Click **Save Changes**
6. Test by asking questions

### How to Prioritize Specific Content Types

1. Navigate to **Settings > AI Assistant > Search**
2. Scroll to **Search System Prompt**
3. Find the "CONTENT PREFERENCES" section
4. Add your preferences in plain English:
   ```
   CONTENT PREFERENCES

   - For service inquiries, prioritize pages from the Services section
   - For pricing questions, emphasize pricing and plans pages
   - For support questions, prioritize documentation over blog posts
   ```
5. Click **Save Changes**
6. Test with relevant searches

### How to Re-Index Content

**After making major content changes**, re-index to update AI knowledge:

**Ask your developer to run:**
```bash
# Re-index all content
wp sk-indexer index

# Or delete and re-index everything
wp sk-indexer delete-all --yes
wp sk-indexer index
```

**When to re-index:**
- After publishing many new pages
- After major content updates
- After deleting old content
- After changing post types to index
- Monthly maintenance (optional)

### How to Test AI Features

#### Test the Chatbot

1. Open your website in a new incognito/private browser window
2. Look for the floating chat button
3. Click to open chat
4. Ask specific questions about your content:
   - "What services do you offer?"
   - "How do I contact support?"
   - "Tell me about [your specific topic]"
5. Verify:
   - Responses are accurate and helpful
   - Source links work and are relevant
   - Chatbot stays on topic

#### Test Search

1. Use your site's search form
2. Try various query types:
   - Single keywords
   - Multiple keywords
   - Questions ("how to...")
   - Semantic queries (meaning-based)
3. Check:
   - AI summary appears (if enabled)
   - Results are relevant
   - Ranking makes sense
   - Results cover the topic

### How to Monitor Usage and Costs

#### Check OpenAI Usage

1. Visit https://platform.openai.com/usage
2. Log in to your OpenAI account
3. View usage by:
   - Model (embeddings vs. chat)
   - Date range
   - Cost breakdown
4. Set up usage alerts if available

#### Check Pinecone Usage

1. Log in to Pinecone dashboard
2. Navigate to your index
3. Check:
   - Total vectors stored
   - Query volume
   - Current plan usage

#### Review WordPress Logs

1. Navigate to **AI Chat Logs** and **AI Search Logs**
2. Count recent interactions
3. Calculate approximate costs:
   - Embeddings: Count new queries
   - Chat completions: Count chat interactions
   - Search summaries: Count searches (if summaries enabled)

### How to Disable Features Temporarily

#### Disable Chatbot

1. Navigate to **Settings > AI Assistant > Chatbot**
2. Uncheck **Enable Chatbot**
3. Click **Save Changes**

Chatbot will no longer appear on your site.

#### Disable Search

1. Navigate to **Settings > AI Assistant > Search**
2. Uncheck **Enable Search**
3. Uncheck **Replace Default Search** (if enabled)
4. Click **Save Changes**

Search will revert to standard WordPress search.

#### Re-enable Later

Simply check the boxes again and save to re-enable. No re-indexing needed.

## Troubleshooting

### Chatbot Not Appearing

**Check:**
1. Is chatbot enabled? **Settings > AI Assistant > Chatbot > Enable Chatbot**
2. Is floating button enabled? (If using floating button mode)
3. Are API keys configured? Check "Configuration Status" on General tab
4. Is content indexed? Ask developer to verify
5. Check browser console for JavaScript errors (F12 → Console tab)

**Solutions:**
- Save settings again
- Clear browser cache
- Try different browser
- Ask developer to check server logs

### Search Not Working

**Check:**
1. Is search enabled? **Settings > AI Assistant > Search > Enable Search**
2. Are API keys configured?
3. Is content indexed?
4. Try a simple search query first

**If using Replace Default Search:**
- Ensure box is checked
- Try disabling/re-enabling
- Clear site cache if using caching plugin

### Poor Quality Responses

**Possible Causes:**

1. **Content Not Indexed**
   - Solution: Re-index content

2. **Top K Too Low**
   - Solution: Increase Top K to 7-10
   - Location: **Settings > AI Assistant > [Chatbot/Search]**

3. **Poor Source Content**
   - Solution: Improve content quality, clarity, and completeness

4. **System Prompt Needs Tuning**
   - Solution: Adjust system prompt to guide AI better

### "Invalid Security Token" Error

**Cause:** Security nonce expired or invalid

**Solution:**
1. Refresh the page
2. Try again
3. If error persists, clear browser cookies for your site

### Slow Response Times

**Normal Response Times:**
- Chatbot: 2-5 seconds
- Search: 1-3 seconds
- Search with summary: 3-6 seconds

**If Slower:**

1. **Reduce Top K**
   - Lower value = faster responses
   - Try 3-5 for chatbot, 5-7 for search

2. **Use Faster Model**
   - Switch to gpt-4o-mini (faster, cheaper)

3. **Enable Caching**
   - Ask developer to enable Redis object caching
   - Can reduce latency by 80%

4. **Check API Status**
   - Visit https://status.openai.com/
   - Check Pinecone status

### High API Costs

**Reduce Costs:**

1. **Switch Models**
   - Use gpt-4o-mini instead of gpt-4o (5-10x cheaper)

2. **Reduce Top K**
   - Fewer vectors retrieved = lower embedding lookups

3. **Disable AI Summaries**
   - Disable in **Settings > AI Assistant > Search**
   - Saves 1 API call per search

4. **Implement Caching**
   - Ask developer to add Redis caching
   - Reduces redundant API calls

5. **Set Rate Limits**
   - Ask developer to implement stricter rate limiting
   - Prevents abuse

### Content Not Being Found

**Check:**

1. **Is Content Indexed?**
   - Ask developer: `wp sk-indexer config`
   - Verify post types being indexed

2. **Is Post Type Included?**
   - **Settings > AI Assistant > Indexer > Post Types to Index**
   - Add missing post types (comma-separated)

3. **Is Content Published?**
   - Only published posts are indexed
   - Check post status

4. **Minimum Score Too High?**
   - Try lowering minimum score to 0.4-0.5
   - **Settings > AI Assistant > Search > Minimum Score**

**Solution:** Re-index content after fixing configuration

### Chatbot Gives Wrong Information

**Causes and Solutions:**

1. **Outdated Content**
   - Update the source page
   - Re-index content

2. **Content Ambiguous**
   - Improve content clarity
   - Add more details
   - Re-index

3. **Temperature Too High**
   - Reduce temperature to 0.1-0.2
   - **Settings > AI Assistant > Chatbot > Temperature**

4. **Wrong Sources Retrieved**
   - Check "Sources Used" in chat logs
   - Adjust relevance boosting
   - Improve content structure

### Accessibility Issues

**Keyboard Navigation Not Working:**
- Clear browser cache
- Test in different browser
- Check for JavaScript errors (F12 console)
- Report to developer with specifics

**Screen Reader Problems:**
- Test with NVDA, JAWS, or VoiceOver
- Report specific issues with reproduction steps
- Include screen reader name and version

**Report Issues:**
- GitHub: https://github.com/kanopi/semantic-knowledge/issues
- Label as "Accessibility Issue"

### Need More Help?

**Resources:**
- [Configuration Guide](CONFIGURATION.md) - Detailed configuration reference
- [FAQ](FAQ.md) - Frequently asked questions
- [Shortcode Guide](SHORTCODES.md) - Shortcode usage examples
- [GitHub Issues](https://github.com/kanopi/semantic-knowledge/issues) - Report bugs

**Contact Developer:**
- For server/environment issues
- For indexing problems
- For API configuration
- For custom development

**Check System Status:**
```bash
wp sk-indexer check
```

This verifies Node.js, indexer installation, and system requirements.

---

**Last Updated:** January 2025
**Plugin Version:** 1.0.0
