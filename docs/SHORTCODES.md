# Shortcode Reference Guide

Complete reference for WP AI Assistant shortcodes with examples and styling tips.

## Table of Contents

- [AI Chatbot Shortcode](#ai-chatbot-shortcode)
- [AI Search Shortcode](#ai-search-shortcode)
- [Styling Shortcodes](#styling-shortcodes)
- [Advanced Use Cases](#advanced-use-cases)
- [Troubleshooting](#troubleshooting)

## AI Chatbot Shortcode

The `[ai_chatbot]` shortcode displays the AI chatbot interface on specific pages.

### Basic Usage

#### Inline Mode (Default)

Displays chat interface directly on the page:

```
[ai_chatbot]
```

or explicitly:

```
[ai_chatbot mode="inline"]
```

**Best for:**
- Support pages
- FAQ sections
- Dedicated chat pages
- Contact forms

#### Popup Mode

Displays a button that opens chat in a popup:

```
[ai_chatbot mode="popup"]
```

With custom button text:

```
[ai_chatbot mode="popup" button="Chat with Us"]
```

**Best for:**
- Product pages
- Sidebar placement
- Minimal space requirements
- Multiple chat entry points

### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `mode` | string | `inline` | Display mode: `inline` or `popup` |
| `button` | string | `Chat with AI` | Button text for popup mode |

### Examples

#### Example 1: Support Page

**Inline chat on support page:**

```
<h1>Customer Support</h1>

<p>Have a question? Ask our AI assistant for instant help!</p>

[ai_chatbot mode="inline"]

<p>Or <a href="/contact/">contact us directly</a> for personalized assistance.</p>
```

**Result:** Chat interface embedded directly in page content.

#### Example 2: Product Page

**Popup button on product pages:**

```
<div class="product-info">
    <h2>Product Name</h2>
    <p>Product description...</p>

    <div class="product-support">
        [ai_chatbot mode="popup" button="Ask About This Product"]
    </div>
</div>
```

**Result:** Button that opens chat popup when clicked.

#### Example 3: FAQ Section

**Inline chat after FAQ list:**

```
<h2>Frequently Asked Questions</h2>

<details>
    <summary>How do I...?</summary>
    <p>Answer here...</p>
</details>

<!-- More FAQs -->

<h3>Can't Find What You're Looking For?</h3>
<p>Ask our AI assistant:</p>

[ai_chatbot]
```

#### Example 4: Sidebar Widget

**Popup button in sidebar (if theme supports shortcodes in widgets):**

```
[ai_chatbot mode="popup" button="Need Help?"]
```

Add via:
1. Appearance > Widgets
2. Add "Shortcode" widget to sidebar
3. Paste shortcode

#### Example 5: Contact Form Alternative

**Chat as primary contact method:**

```
<h1>Get in Touch</h1>

<p>The fastest way to get answers is through our AI assistant:</p>

[ai_chatbot mode="inline"]

<hr>

<h2>Prefer Email?</h2>
[contact-form-7 id="123"]
```

### Shortcode Placement Tips

#### Works In:

- Posts and pages (Gutenberg blocks or classic editor)
- Custom post types
- WooCommerce product descriptions
- Page builders (Elementor, Beaver Builder, etc.)
- Widgets (if theme supports shortcodes)
- Template files (via `do_shortcode()`)

#### Placement Recommendations:

**Top of Page:**
```
[ai_chatbot mode="inline"]

<h1>Welcome!</h1>
<p>Page content...</p>
```
Good for: Immediate access to help

**After Introduction:**
```
<h1>Services</h1>
<p>We offer comprehensive services...</p>

[ai_chatbot mode="popup" button="Ask About Our Services"]

<h2>What We Offer</h2>
```
Good for: Contextual help after setting context

**Bottom of Page:**
```
<div class="page-content">
    <!-- Main content -->
</div>

<div class="need-help">
    <h3>Questions?</h3>
    [ai_chatbot]
</div>
```
Good for: After users read content

**Floating Sidebar:**
```
<div class="content-wrapper">
    <main class="main-content">
        <!-- Content -->
    </main>

    <aside class="sidebar">
        <div class="chat-widget">
            <h4>Need Help?</h4>
            [ai_chatbot mode="popup" button="Ask AI"]
        </div>
    </aside>
</div>
```
Good for: Always visible, not intrusive

## AI Search Shortcode

The `[ai_search]` shortcode displays an AI-powered search form.

### Basic Usage

**Default search form:**

```
[ai_search]
```

**With custom placeholder:**

```
[ai_search placeholder="Search our documentation..."]
```

**With custom button text:**

```
[ai_search button="Find Answers"]
```

**Fully customized:**

```
[ai_search placeholder="What are you looking for?" button="Search Now"]
```

### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `placeholder` | string | Setting value or "Search with AI..." | Input placeholder text |
| `button` | string | `Search` | Submit button text |

**Note:** If no placeholder specified, uses value from Settings > AI Assistant > Search > Search Placeholder.

### Examples

#### Example 1: Documentation Search

**Top of documentation section:**

```
<div class="docs-search">
    <h2>Documentation</h2>
    <p>Find answers in our comprehensive guides:</p>

    [ai_search placeholder="Search documentation..." button="Search Docs"]
</div>
```

#### Example 2: Knowledge Base

**Prominent search on knowledge base:**

```
<div class="kb-hero">
    <h1>Knowledge Base</h1>
    <p class="subtitle">Search thousands of articles</p>

    [ai_search placeholder="How can we help you?" button="Get Answers"]
</div>

<div class="popular-topics">
    <h2>Popular Topics</h2>
    <!-- Topic links -->
</div>
```

#### Example 3: Site Search Page

**Dedicated search page (search.php alternative):**

```
<h1>Search Our Site</h1>

[ai_search placeholder="Enter your search query..." button="Search"]

<div class="search-tips">
    <h3>Search Tips:</h3>
    <ul>
        <li>Use natural language questions</li>
        <li>Try different keyword combinations</li>
        <li>AI understands meaning, not just exact words</li>
    </ul>
</div>
```

#### Example 4: Product Finder

**E-commerce product search:**

```
<div class="product-finder">
    <h2>Find Your Perfect Product</h2>
    <p>Describe what you're looking for:</p>

    [ai_search placeholder="e.g., wireless headphones for running" button="Find Products"]
</div>
```

#### Example 5: FAQ Alternative

**Search-first FAQ page:**

```
<h1>Frequently Asked Questions</h1>

<p>Search for specific answers:</p>
[ai_search placeholder="Ask a question..." button="Search FAQs"]

<hr>

<h2>Popular Questions</h2>
<div class="faq-list">
    <!-- FAQ items -->
</div>
```

#### Example 6: Support Portal

**Multi-option support page:**

```
<h1>Support Center</h1>

<div class="support-options">
    <div class="option">
        <h3>Search Articles</h3>
        [ai_search placeholder="Search support articles..."]
    </div>

    <div class="option">
        <h3>Ask AI</h3>
        [ai_chatbot mode="popup" button="Chat with Support"]
    </div>

    <div class="option">
        <h3>Contact Us</h3>
        <a href="/contact/" class="button">Email Support</a>
    </div>
</div>
```

### Search Results Display

When using the `[ai_search]` shortcode, results appear dynamically below the form.

**Result Components:**

1. **AI Summary** (if enabled)
   - Direct answer to query
   - Key points from results
   - Links to relevant pages

2. **Search Results**
   - Page title
   - Excerpt
   - Read more link
   - Relevance score (hidden by default)

3. **No Results Message**
   - Shows if no matching content found
   - Suggests alternative queries

**Customizing Results Display:**

See [Styling Shortcodes](#styling-shortcodes) section for CSS customization.

## Styling Shortcodes

Customize the appearance of chatbot and search shortcodes with CSS.

### AI Chatbot Styles

#### Default CSS Classes

```css
/* Chatbot container */
.wp-ai-chatbot {
    /* Container styles */
}

/* Popup mode button */
.wp-ai-chatbot-button {
    /* Button styles */
}

/* Chat popup overlay */
.wp-ai-chatbot-popup {
    /* Popup styles */
}

/* Chat messages */
.wp-ai-chatbot__message {
    /* Message styles */
}

.wp-ai-chatbot__message--user {
    /* User message styles */
}

.wp-ai-chatbot__message--assistant {
    /* AI response styles */
}
```

#### Styling Examples

**Example 1: Custom Button Colors**

```css
/* Match brand colors */
.wp-ai-chatbot-button {
    background: #0066cc;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 25px;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba(0, 102, 204, 0.3);
}

.wp-ai-chatbot-button:hover {
    background: #0052a3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.4);
}
```

**Example 2: Custom Popup Size**

```css
.wp-ai-chatbot-popup {
    max-width: 450px;
    max-height: 600px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}
```

**Example 3: Message Bubbles**

```css
.wp-ai-chatbot__message--user {
    background: #0066cc;
    color: white;
    border-radius: 18px 18px 4px 18px;
    padding: 10px 16px;
    margin-left: auto;
    max-width: 70%;
}

.wp-ai-chatbot__message--assistant {
    background: #f0f0f0;
    color: #333;
    border-radius: 18px 18px 18px 4px;
    padding: 10px 16px;
    margin-right: auto;
    max-width: 70%;
}
```

**Example 4: Full-Width Inline Chat**

```css
.wp-ai-chatbot[data-popup="false"] {
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}
```

### AI Search Styles

#### Default CSS Classes

```css
/* Search container */
.wp-ai-search {
    /* Container styles */
}

/* Search form */
.wp-ai-search__form {
    /* Form styles */
}

/* Search input */
.wp-ai-search__input {
    /* Input field styles */
}

/* Search button */
.wp-ai-search__button {
    /* Button styles */
}

/* Results container */
.wp-ai-search__results {
    /* Results section styles */
}

/* AI summary */
.wp-ai-search__summary {
    /* Summary box styles */
}

/* Individual result */
.wp-ai-search__result {
    /* Result item styles */
}
```

#### Styling Examples

**Example 1: Hero Search Box**

```css
.wp-ai-search {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
}

.wp-ai-search__form {
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    gap: 10px;
}

.wp-ai-search__input {
    flex: 1;
    padding: 16px 24px;
    font-size: 18px;
    border: none;
    border-radius: 50px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.wp-ai-search__button {
    padding: 16px 32px;
    background: white;
    color: #667eea;
    border: none;
    border-radius: 50px;
    font-weight: bold;
    cursor: pointer;
}
```

**Example 2: Minimal Search**

```css
.wp-ai-search__form {
    display: flex;
    border: 2px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.wp-ai-search__input {
    flex: 1;
    padding: 12px;
    border: none;
    font-size: 16px;
}

.wp-ai-search__button {
    padding: 12px 24px;
    background: #0066cc;
    color: white;
    border: none;
    cursor: pointer;
}
```

**Example 3: Card-Style Results**

```css
.wp-ai-search__results {
    margin-top: 30px;
}

.wp-ai-search__result {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: box-shadow 0.2s;
}

.wp-ai-search__result:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.wp-ai-search__result-title {
    font-size: 20px;
    font-weight: bold;
    color: #0066cc;
    margin-bottom: 8px;
}

.wp-ai-search__result-excerpt {
    color: #666;
    line-height: 1.6;
}
```

**Example 4: Highlighted AI Summary**

```css
.wp-ai-search__summary {
    background: #f8f9fa;
    border-left: 4px solid #0066cc;
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 4px;
}

.wp-ai-search__summary h3 {
    color: #0066cc;
    margin-top: 0;
}

.wp-ai-search__summary a {
    color: #0066cc;
    text-decoration: none;
    border-bottom: 1px solid #0066cc;
}
```

### Responsive Design

Make shortcodes mobile-friendly:

```css
/* Chatbot - Mobile */
@media (max-width: 768px) {
    .wp-ai-chatbot-popup {
        width: 100%;
        max-width: none;
        height: 100vh;
        border-radius: 0;
    }

    .wp-ai-chatbot-button {
        width: 100%;
        text-align: center;
    }
}

/* Search - Mobile */
@media (max-width: 768px) {
    .wp-ai-search__form {
        flex-direction: column;
    }

    .wp-ai-search__input,
    .wp-ai-search__button {
        width: 100%;
    }

    .wp-ai-search__button {
        margin-top: 10px;
    }
}
```

### Dark Mode

Support for dark mode:

```css
/* Dark mode chatbot */
@media (prefers-color-scheme: dark) {
    .wp-ai-chatbot {
        background: #1a1a1a;
        color: #ffffff;
    }

    .wp-ai-chatbot__message--assistant {
        background: #2a2a2a;
        color: #ffffff;
    }
}

/* Dark mode search */
@media (prefers-color-scheme: dark) {
    .wp-ai-search__input {
        background: #2a2a2a;
        color: #ffffff;
        border-color: #444;
    }

    .wp-ai-search__result {
        background: #1a1a1a;
        border-color: #444;
        color: #ffffff;
    }
}
```

## Advanced Use Cases

Complex implementations using shortcodes with custom code.

### Use Case 1: Conditional Chatbot Display

Show chatbot only on specific pages:

**In page template:**

```php
<?php
// Show chatbot only on product pages
if ( is_singular('product') ) {
    echo do_shortcode('[ai_chatbot mode="popup" button="Ask About This Product"]');
}

// Show chatbot only for logged-out users
if ( ! is_user_logged_in() ) {
    echo do_shortcode('[ai_chatbot mode="inline"]');
}
?>
```

### Use Case 2: Dynamic Button Text

Change button text based on context:

```php
<?php
$page_type = get_post_type();
$button_text = 'Chat with AI';

if ( $page_type === 'product' ) {
    $button_text = 'Ask About This Product';
} elseif ( $page_type === 'docs' ) {
    $button_text = 'Get Help with Docs';
} elseif ( is_page('support') ) {
    $button_text = 'Contact Support';
}

echo do_shortcode('[ai_chatbot mode="popup" button="' . esc_attr($button_text) . '"]');
?>
```

### Use Case 3: Search with Custom Results Handler

Display search results in custom format:

```php
<?php
// Add custom search handler
add_action('wp_footer', function() {
    ?>
    <script>
    // Listen for search results
    document.addEventListener('wp_ai_search_results', function(e) {
        const results = e.detail.results;
        const summary = e.detail.summary;

        // Custom results display
        console.log('Search results:', results);

        // Send to analytics
        gtag('event', 'ai_search', {
            'search_term': e.detail.query,
            'num_results': results.length
        });
    });
    </script>
    <?php
});
?>

<!-- Page content -->
<div class="custom-search">
    <h1>Search Our Site</h1>
    [ai_search]

    <div id="custom-results">
        <!-- Custom results will be inserted here via JavaScript -->
    </div>
</div>
```

### Use Case 4: Tabbed Interface

Chatbot and search in tabs:

```html
<div class="help-tabs">
    <ul class="tabs-nav">
        <li><a href="#tab-chat">Chat</a></li>
        <li><a href="#tab-search">Search</a></li>
    </ul>

    <div id="tab-chat" class="tab-content">
        <h3>Ask AI Assistant</h3>
        [ai_chatbot mode="inline"]
    </div>

    <div id="tab-search" class="tab-content">
        <h3>Search Knowledge Base</h3>
        [ai_search placeholder="Search articles..."]
    </div>
</div>

<script>
// Simple tab switching
jQuery('.tabs-nav a').on('click', function(e) {
    e.preventDefault();
    jQuery('.tab-content').hide();
    jQuery(jQuery(this).attr('href')).show();
});
jQuery('.tab-content:first').show();
</script>
```

### Use Case 5: Multilingual Shortcodes

Different placeholders per language (with WPML/Polylang):

```php
<?php
$placeholder = 'Search with AI...';
$button = 'Search';

if ( function_exists('pll_current_language') ) {
    $lang = pll_current_language();

    if ( $lang === 'es' ) {
        $placeholder = 'Buscar con IA...';
        $button = 'Buscar';
    } elseif ( $lang === 'fr' ) {
        $placeholder = 'Rechercher avec IA...';
        $button = 'Rechercher';
    }
}

echo do_shortcode('[ai_search placeholder="' . esc_attr($placeholder) . '" button="' . esc_attr($button) . '"]');
?>
```

### Use Case 6: A/B Testing

Test different chatbot placements:

```php
<?php
// Simple A/B test
$variant = isset($_COOKIE['ab_test']) ? $_COOKIE['ab_test'] : (rand(0,1) ? 'a' : 'b');
setcookie('ab_test', $variant, time() + 30*24*60*60, '/');

if ( $variant === 'a' ) {
    // Variant A: Popup button
    echo do_shortcode('[ai_chatbot mode="popup" button="Need Help?"]');
} else {
    // Variant B: Inline chat
    echo '<div class="help-section">';
    echo '<h3>Have Questions?</h3>';
    echo do_shortcode('[ai_chatbot mode="inline"]');
    echo '</div>';
}
?>
```

### Use Case 7: Widget Area Shortcode

Add to theme's widget area programmatically:

```php
<?php
// In functions.php
add_action('widgets_init', function() {
    register_sidebar(array(
        'name'          => 'AI Assistant Sidebar',
        'id'            => 'ai-assistant',
        'before_widget' => '<div class="ai-widget">',
        'after_widget'  => '</div>',
    ));
});

// Add shortcode to widget area
add_action('dynamic_sidebar_after', function($index) {
    if ( $index === 'ai-assistant' ) {
        echo do_shortcode('[ai_chatbot mode="popup" button="Ask AI"]');
    }
});
?>
```

## Troubleshooting

Common issues when using shortcodes.

### Shortcode Displays as Plain Text

**Problem:** `[ai_chatbot]` appears literally on page

**Causes:**
1. Plugin not activated
2. Typo in shortcode name
3. Visual editor adding extra characters

**Solutions:**
1. Verify plugin is active: Plugins > Installed Plugins
2. Check spelling: `ai_chatbot` not `ai-chatbot`
3. Switch to Text/Code editor, remove extra spaces

### Chatbot Not Appearing

**Problem:** Shortcode processed but chatbot doesn't show

**Checklist:**
1. Chatbot enabled: Settings > AI Assistant > Chatbot > Enable
2. API keys configured (check Configuration Status)
3. Content indexed (ask developer)
4. JavaScript errors (check browser console F12)
5. Theme/plugin conflict (disable other plugins temporarily)

### Search Form Not Working

**Problem:** Search form appears but doesn't function

**Checklist:**
1. Search enabled: Settings > AI Assistant > Search > Enable
2. JavaScript loaded (check page source for `wp-ai-assistant-search.js`)
3. REST API accessible (check /wp-json/ endpoint)
4. Browser console errors (F12 > Console)

### Styling Not Applied

**Problem:** Custom CSS not affecting shortcodes

**Solutions:**
1. Use browser inspector (F12) to find correct CSS classes
2. Check CSS specificity (may need `!important` or more specific selector)
3. Clear cache (browser, plugin, CDN)
4. Verify CSS file is enqueued after plugin styles

### Multiple Shortcodes Conflict

**Problem:** Using multiple `[ai_chatbot]` shortcodes causes issues

**Solution:** Only use ONE chatbot shortcode per page
- Multiple search shortcodes OK
- Multiple chatbot shortcodes = conflicts
- Use floating button for site-wide access

### Shortcode in Widget Not Working

**Problem:** Shortcode in widget doesn't process

**Causes:**
1. Theme doesn't support shortcodes in widgets
2. Widget not in active sidebar

**Solutions:**
1. Add to functions.php:
```php
add_filter('widget_text', 'do_shortcode');
```
2. Use "Shortcode" widget if available
3. Use "Custom HTML" widget with `do_shortcode()`:
```php
<?php echo do_shortcode('[ai_chatbot mode="popup"]'); ?>
```

### Parameters Not Working

**Problem:** Shortcode parameters ignored

**Common mistakes:**
```
❌ [ai_chatbot mode=popup]           (missing quotes)
❌ [ai_chatbot mode="popup "]        (extra space)
❌ [ai_chatbot mode='popup']         (wrong quotes - use double)
❌ [ai_chatbot button=Ask me]        (missing quotes around multi-word)

✓ [ai_chatbot mode="popup"]
✓ [ai_chatbot button="Ask me"]
```

### Accessibility Issues

**Problem:** Keyboard navigation not working

**Solutions:**
1. Clear browser cache
2. Check for JavaScript errors
3. Test in different browser
4. Ensure no custom CSS interfering with focus

**Report:** If issues persist, report as accessibility bug

---

## Quick Reference

### Chatbot Shortcode

```
[ai_chatbot]
[ai_chatbot mode="inline"]
[ai_chatbot mode="popup"]
[ai_chatbot mode="popup" button="Custom Text"]
```

### Search Shortcode

```
[ai_search]
[ai_search placeholder="Custom placeholder..."]
[ai_search button="Custom Button"]
[ai_search placeholder="Search..." button="Find"]
```

### CSS Classes

**Chatbot:**
- `.wp-ai-chatbot`
- `.wp-ai-chatbot-button`
- `.wp-ai-chatbot-popup`

**Search:**
- `.wp-ai-search`
- `.wp-ai-search__form`
- `.wp-ai-search__input`
- `.wp-ai-search__button`
- `.wp-ai-search__results`

---

**Related Documentation:**
- [User Guide](USER-GUIDE.md) - Complete usage guide
- [Configuration](CONFIGURATION.md) - Settings reference
- [FAQ](FAQ.md) - Common questions

**Last Updated:** January 2025
**Plugin Version:** 1.0.0
