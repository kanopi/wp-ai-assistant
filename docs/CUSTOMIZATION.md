# Customizing Semantic Knowledge Content Preferences

This guide explains how to customize the Semantic Knowledge plugin to prioritize specific types of content for your website **without writing any code**.

## Overview

The Semantic Knowledge uses AI to understand and present search results. Instead of hardcoding business logic in PHP, you can simply tell the AI what to prioritize in natural language by editing the **Search System Prompt** in the plugin settings.

## Key Innovation

**Before (Old Approach):**
- Hardcoded PHP logic with regex patterns and boost values
- Required code changes for each customization
- Complex platform detection (WordPress vs Drupal)
- Developer-only modifications

**After (New Approach):**
- Natural language instructions in system prompt
- No code changes needed
- AI understands context and intent
- Anyone can customize via Settings UI

## How It Works

1. Go to **Settings > AI Assistant > Search**
2. Scroll to **Search System Prompt**
3. Find the **CONTENT PREFERENCES** section
4. Add your preferences in plain English
5. Save changes

The AI will automatically understand and apply your preferences when generating search summaries.

---

## Common Customization Scenarios

### 1. Platform-Specific Content

**Use Case:** Your site covers both WordPress and Drupal. You want platform-specific queries to prioritize relevant content.

**Configuration:**
```
CONTENT PREFERENCES

Platform-Specific Content:
- When users ask about WordPress, prioritize WordPress-related pages and resources
- When users ask about Drupal, prioritize Drupal-related pages and resources
- If a query specifically mentions one platform, strongly emphasize content about that platform and de-emphasize content about other platforms
```

**Example Queries:**
- "How do I update WordPress?" → AI emphasizes WordPress update guides
- "Drupal migration best practices" → AI prioritizes Drupal migration content
- "CMS comparison" → AI provides balanced content from both platforms

---

### 2. Custom Post Type Emphasis

**Use Case:** You have a Services custom post type and want service-related queries to highlight your offerings.

**Configuration:**
```
CONTENT PREFERENCES

Service Pages:
- For queries about services, capabilities, or "what we do", prioritize content from the Services section
- Service-related questions should highlight our service offerings prominently
- When users ask "what services do you offer", emphasize the services overview page
```

**Example Queries:**
- "What services do you provide?" → AI highlights services overview and individual service pages
- "Web development services" → AI prioritizes service pages over blog posts

---

### 3. Content Freshness and Recency

**Use Case:** You want to prioritize recent content for news and updates, but favor evergreen documentation for technical questions.

**Configuration:**
```
CONTENT PREFERENCES

Content Freshness:
- For news, announcements, and "latest" queries, emphasize the most recent articles
- For technical documentation and how-to questions, prioritize comprehensive guides over blog posts
- When users ask "what's new", focus exclusively on content from the last 30 days
- Tutorial and documentation pages are more authoritative than blog posts for technical questions
```

**Example Queries:**
- "Latest WordPress updates" → AI shows recent news articles
- "How to configure SSL" → AI prioritizes technical documentation

---

### 4. Industry-Specific Customization

**Use Case:** You're a healthcare provider and want to emphasize patient resources over internal documentation.

**Configuration:**
```
CONTENT PREFERENCES

Healthcare Content:
- For patient-facing queries, prioritize patient resources, FAQs, and appointment information
- For medical condition queries, emphasize treatment information and specialist pages
- Administrative and insurance questions should highlight our billing and insurance guides
- "How do I schedule" queries should prominently feature appointment scheduling information
```

---

### 5. Product Hierarchy

**Use Case:** You have multiple product lines and want to emphasize flagship products.

**Configuration:**
```
CONTENT PREFERENCES

Product Priority:
- For general product queries, emphasize our flagship Product A over other offerings
- Product comparison queries should lead with our recommended solutions
- When users ask about features, prioritize Product A and Product B pages
- Legacy products (Product C, Product D) should be mentioned but not emphasized unless specifically requested
```

---

### 6. Geographic Targeting

**Use Case:** You serve multiple regions with location-specific content.

**Configuration:**
```
CONTENT PREFERENCES

Geographic Priority:
- For queries mentioning "US" or "United States", prioritize US-specific content and regulations
- UK and EU queries should emphasize GDPR compliance and EU-specific information
- Location-agnostic queries should provide balanced international coverage
- Service availability queries should clarify geographic restrictions
```

---

### 7. Seasonal Content

**Use Case:** You want to adjust priorities based on time-sensitive campaigns or seasons.

**Configuration:**
```
CONTENT PREFERENCES

Seasonal Focus (Update Seasonally):
- Current season: Q1 2024 - Tax season
- Tax-related queries should prioritize our 2024 tax preparation services
- Year-end planning queries are less relevant until Q4
- Summer programs and services are not currently active - mention but don't emphasize
```

---

### 8. Audience Segmentation

**Use Case:** You serve both beginners and advanced users with different content needs.

**Configuration:**
```
CONTENT PREFERENCES

Audience Targeting:
- For queries starting with "how do I" or "what is", prioritize beginner-friendly guides and introductory content
- Advanced queries mentioning technical terms should emphasize advanced documentation and API references
- Enterprise and "at scale" queries should highlight enterprise features and case studies
- Pricing queries from SMBs should focus on starter and business plans
```

---

## Migration Guide for Previous Hardcoded Logic

If you previously had hardcoded business logic in the plugin (like the Kanopi-specific WordPress/Drupal detection), here's how to migrate:

### Original Hardcoded Logic (Removed)

The plugin previously had this hardcoded in PHP:
```php
// Detect platform-specific queries
if (preg_match('/\bwordpress\b/i', $query)) {
    // Boost WordPress pages +0.15
    // Penalize Drupal pages -0.25
}
```

### New Approach (No Code)

Add to the System Prompt:
```
CONTENT PREFERENCES

Platform-Specific Content:
- When users ask about WordPress, prioritize WordPress-related pages and case studies
- When users ask about Drupal, prioritize Drupal-related pages and case studies
- If a query specifically mentions one platform, strongly emphasize content about that platform and de-emphasize content about other platforms
```

### Why This Is Better

1. **More Intelligent**: AI understands context and intent, not just keywords
2. **Easier to Maintain**: Edit settings instead of modifying code
3. **More Flexible**: Handles variations like "WP", "WordPress", "Wordpress" automatically
4. **Better UX**: Non-developers can customize preferences
5. **Safer**: No risk of code errors or breaking changes

---

## Advanced: Combining AI Preferences with PHP Filters

For very specific algorithmic boosts (like exact URL matches), you can combine system prompt preferences with PHP filters.

**When to use System Prompt (Recommended):**
- Content type preferences
- Topical prioritization
- Contextual understanding
- Natural language conditions

**When to use PHP Filters (Advanced):**
- Mathematical scoring adjustments
- Database-driven rules
- Complex conditional logic
- Performance-critical calculations

**Example Combination:**

System Prompt:
```
CONTENT PREFERENCES

Services:
- For service-related queries, prioritize our Services section
```

PHP Filter (in `functions.php` or custom plugin):
```php
// Add extra algorithmic boost for services post type
add_filter('semantic_knowledge_search_relevance_config', function($config, $query) {
    $config['post_type_boosts']['services'] = 0.07;
    return $config;
}, 10, 2);
```

However, **start with the System Prompt first** - it's usually sufficient.

---

## Tips and Best Practices

### 1. Be Specific

**❌ Bad:**
```
Prioritize important content
```

**✅ Good:**
```
For pricing queries, prioritize our Pricing page and comparison guides
For support queries, prioritize FAQ and documentation pages
```

### 2. Use Natural Language

**❌ Bad:**
```
IF query CONTAINS "pricing" THEN boost pricing pages
```

**✅ Good:**
```
When users ask about pricing or costs, emphasize our pricing page and ROI calculator
```

### 3. Handle Edge Cases

**❌ Incomplete:**
```
Prioritize WordPress content
```

**✅ Complete:**
```
When users ask about WordPress, prioritize WordPress-related pages
If the query doesn't mention a specific platform, provide balanced content
If both platforms are mentioned, compare them fairly
```

### 4. Test Your Changes

After updating preferences:
1. Try various search queries
2. Check if results match expectations
3. Adjust wording if needed
4. The AI interprets instructions, so experiment with phrasing

### 5. Document Changes

Keep a log of your customizations:
```
CONTENT PREFERENCES

[Updated 2024-01-15]
- Added seasonal focus for Q1 tax season
- Emphasized beginner content for "how do I" queries

[Updated 2023-12-01]
- Removed holiday promotion references
- Updated product hierarchy for new Product B launch
```

---

## Troubleshooting

### AI Not Following Preferences

**Check:**
1. Is the preference clearly stated?
2. Does the search query match your condition?
3. Is there conflicting guidance in the prompt?
4. Are results semantically similar (AI might be correct)?

**Try:**
- Rephrase your preference more explicitly
- Add examples of what you want
- Remove contradictory instructions

### Preferences Too Aggressive

**Problem:** AI completely ignores relevant content

**Solution:**
```
❌ Too Strong:
ONLY show WordPress content for WordPress queries

✅ Better:
When users ask about WordPress, prioritize WordPress-related pages but include relevant general content when applicable
```

### Need More Control

**Solution:** Use PHP filters for specific algorithmic adjustments

See [HOOKS.md](HOOKS.md) for filter documentation and [examples/](../examples/) for code samples.

---

## Additional Resources

- [HOOKS.md](HOOKS.md) - Complete filter and action reference
- [examples/](../examples/) - PHP code examples for advanced customization
- [README.md](../README.md) - Plugin overview and getting started

---

## Support

For questions or issues with customization:
1. Review this guide and examples
2. Check [HOOKS.md](HOOKS.md) for advanced options
3. Test with different query phrasings
4. Submit issues on GitHub (if open source)
