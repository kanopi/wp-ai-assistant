# WP AI Assistant - Hooks Reference

This document provides a comprehensive reference for all filters and actions available in the WP AI Assistant plugin.

## Table of Contents

- [Search Module Hooks](#search-module-hooks)
  - [Query Processing](#query-processing)
  - [Relevance Boosting](#relevance-boosting)
  - [Result Formatting](#result-formatting)
  - [Summary Generation](#summary-generation)
- [Chatbot Module Hooks](#chatbot-module-hooks)
- [Indexer Hooks](#indexer-hooks)
- [Best Practices](#best-practices)

---

## Search Module Hooks

### Query Processing

#### `wp_ai_search_query_start`
**Type:** Action
**Description:** Fires at the start of a search query, before any processing begins.

**Parameters:**
- `$query` (string) - The search query text
- `$request` (WP_REST_Request) - The REST API request object

**Example:**
```php
add_action('wp_ai_search_query_start', function($query, $request) {
    error_log('AI search started: ' . $query);
}, 10, 2);
```

---

#### `wp_ai_search_query_text`
**Type:** Filter
**Description:** Filter the search query text before processing.

**Parameters:**
- `$query` (string) - The search query text
- `$request` (WP_REST_Request) - The REST API request object

**Returns:** (string) Modified query text

**Example:**
```php
// Auto-correct common misspellings
add_filter('wp_ai_search_query_text', function($query, $request) {
    $replacements = array(
        'wordpres' => 'wordpress',
        'drupel' => 'drupal',
    );
    return str_ireplace(array_keys($replacements), array_values($replacements), $query);
}, 10, 2);
```

---

#### `wp_ai_search_top_k`
**Type:** Filter
**Description:** Filter the number of results to retrieve from Pinecone.

**Parameters:**
- `$top_k` (int) - Number of results to retrieve
- `$query` (string) - The search query text

**Returns:** (int) Modified top_k value

**Example:**
```php
// Retrieve more results for complex queries
add_filter('wp_ai_search_top_k', function($top_k, $query) {
    if (str_word_count($query) > 5) {
        return 15; // More results for complex queries
    }
    return $top_k;
}, 10, 2);
```

---

#### `wp_ai_search_before_embedding`
**Type:** Action
**Description:** Fires before creating the search embedding.

**Parameters:**
- `$query` (string) - The search query text

**Example:**
```php
add_action('wp_ai_search_before_embedding', function($query) {
    // Log or track searches
    do_action('my_custom_analytics_track', 'ai_search', $query);
});
```

---

#### `wp_ai_search_after_pinecone_query`
**Type:** Action
**Description:** Fires after querying Pinecone for results.

**Parameters:**
- `$matches` (array) - Raw Pinecone matches
- `$query` (string) - The search query text

**Example:**
```php
add_action('wp_ai_search_after_pinecone_query', function($matches, $query) {
    error_log(sprintf('Found %d matches for: %s', count($matches), $query));
}, 10, 2);
```

---

#### `wp_ai_search_min_score`
**Type:** Filter
**Description:** Filter the minimum score threshold for search results.

**Parameters:**
- `$min_score` (float) - Minimum score threshold (0.0-1.0)
- `$query` (string) - The search query text

**Returns:** (float) Modified threshold

**Example:**
```php
// Lower threshold for short queries
add_filter('wp_ai_search_min_score', function($min_score, $query) {
    if (strlen($query) < 10) {
        return 0.4; // Lower threshold for short queries
    }
    return $min_score;
}, 10, 2);
```

---

#### `wp_ai_search_results`
**Type:** Filter
**Description:** Filter the complete results array before returning to the client.

**Parameters:**
- `$results` (array) - Formatted search results
- `$query` (string) - The search query text
- `$matches` (array) - Raw Pinecone matches

**Returns:** (array) Modified results

**Example:**
```php
// Add custom metadata to each result
add_filter('wp_ai_search_results', function($results, $query, $matches) {
    foreach ($results as &$result) {
        $post = get_post($result['post_id']);
        if ($post) {
            $result['author_name'] = get_the_author_meta('display_name', $post->post_author);
            $result['publish_date'] = get_the_date('Y-m-d', $post);
        }
    }
    return $results;
}, 10, 3);
```

---

#### `wp_ai_search_before_log`
**Type:** Action
**Description:** Fires before logging the search query.

**Parameters:**
- `$query` (string) - The search query text
- `$results` (array) - Formatted search results

**Example:**
```php
add_action('wp_ai_search_before_log', function($query, $results) {
    // Send to external analytics
    wp_remote_post('https://analytics.example.com/track', array(
        'body' => array(
            'event' => 'ai_search',
            'query' => $query,
            'result_count' => count($results),
        ),
    ));
}, 10, 2);
```

---

#### `wp_ai_search_query_end`
**Type:** Action
**Description:** Fires at the end of a search query, after all processing is complete.

**Parameters:**
- `$response` (array) - Complete response array (query, summary, results, total)
- `$query` (string) - The search query text

**Example:**
```php
add_action('wp_ai_search_query_end', function($response, $query) {
    // Cache results for repeat queries
    wp_cache_set('ai_search_' . md5($query), $response, 'ai_search', HOUR_IN_SECONDS);
}, 10, 2);
```

---

### Relevance Boosting

#### `wp_ai_search_relevance_config`
**Type:** Filter
**Description:** Filter the complete relevance boosting configuration. This is the primary hook for customizing relevance scoring.

**Parameters:**
- `$config` (array) - Default configuration
  - `enabled` (bool) - Whether boosting is enabled
  - `url_slug_match` (array) - URL slug boost config
  - `title_exact_match` (array) - Exact title boost config
  - `title_all_words` (array) - All-words title boost config
  - `post_type_boosts` (array) - Post type boost values
  - `custom_rules` (array) - Custom boosting rules
- `$query` (string) - The search query text

**Returns:** (array) Modified configuration

**Example:**
```php
// Add custom post type boosts
add_filter('wp_ai_search_relevance_config', function($config, $query) {
    // Add boost for 'services' custom post type
    $config['post_type_boosts']['services'] = 0.07;
    $config['post_type_boosts']['case_study'] = 0.06;

    // Add custom rule for services URL pattern
    if (preg_match('/\bservices?\b/i', $query)) {
        $config['custom_rules']['services_url'] = array(
            'match' => '/services/',
            'boost' => 0.1,
        );
    }

    return $config;
}, 10, 2);
```

---

#### `wp_ai_search_before_boost`
**Type:** Action
**Description:** Fires before relevance boosting calculations begin.

**Parameters:**
- `$matches` (array) - Raw matches before boosting
- `$query` (string) - The search query text
- `$config` (array) - Boosting configuration

**Example:**
```php
add_action('wp_ai_search_before_boost', function($matches, $query, $config) {
    error_log(sprintf('Boosting %d results for query: %s', count($matches), $query));
}, 10, 3);
```

---

#### `wp_ai_search_raw_matches`
**Type:** Filter
**Description:** Filter raw matches before boosting calculations.

**Parameters:**
- `$matches` (array) - Raw Pinecone matches
- `$query` (string) - The search query text

**Returns:** (array) Modified matches

**Example:**
```php
// Filter out draft posts from results
add_filter('wp_ai_search_raw_matches', function($matches, $query) {
    return array_filter($matches, function($match) {
        $post_id = $match['metadata']['post_id'] ?? 0;
        if ($post_id) {
            return get_post_status($post_id) === 'publish';
        }
        return true;
    });
}, 10, 2);
```

---

#### `wp_ai_search_url_boost`
**Type:** Filter
**Description:** Filter the URL slug match boost value for a specific result.

**Parameters:**
- `$url_boost` (float) - Calculated URL boost
- `$match` (array) - Current match being processed
- `$query` (string) - The search query text

**Returns:** (float) Modified boost value

**Example:**
```php
// Double the boost for exact slug matches
add_filter('wp_ai_search_url_boost', function($url_boost, $match, $query) {
    $url = strtolower($match['metadata']['url'] ?? '');
    $query_slug = sanitize_title($query);

    if (strpos($url, '/' . $query_slug . '/') !== false) {
        return $url_boost * 2;
    }

    return $url_boost;
}, 10, 3);
```

---

#### `wp_ai_search_title_exact_boost`
**Type:** Filter
**Description:** Filter the exact title match boost value.

**Parameters:**
- `$title_exact_boost` (float) - Configured boost value
- `$match` (array) - Current match being processed
- `$query` (string) - The search query text

**Returns:** (float) Modified boost value

---

#### `wp_ai_search_title_words_boost`
**Type:** Filter
**Description:** Filter the all-words title match boost value.

**Parameters:**
- `$title_words_boost` (float) - Calculated boost value
- `$match` (array) - Current match being processed
- `$query` (string) - The search query text

**Returns:** (float) Modified boost value

---

#### `wp_ai_search_post_type_boost`
**Type:** Filter
**Description:** Filter the post type boost value for a specific result.

**Parameters:**
- `$post_type_boost` (float) - Configured boost value
- `$post_type` (string) - The post type
- `$match` (array) - Current match being processed
- `$query` (string) - The search query text

**Returns:** (float) Modified boost value

**Example:**
```php
// Boost recent posts more than old posts
add_filter('wp_ai_search_post_type_boost', function($boost, $post_type, $match, $query) {
    if ($post_type === 'post') {
        $post_id = $match['metadata']['post_id'] ?? 0;
        if ($post_id) {
            $post_date = get_post_time('U', false, $post_id);
            $age_days = (time() - $post_date) / DAY_IN_SECONDS;

            // Higher boost for posts less than 30 days old
            if ($age_days < 30) {
                return $boost + 0.05;
            }
        }
    }
    return $boost;
}, 10, 4);
```

---

#### `wp_ai_search_custom_boost_{$rule_name}`
**Type:** Filter (Dynamic)
**Description:** Filter a custom rule boost value. The hook name includes the rule name.

**Parameters:**
- `$custom_boost` (float) - Calculated boost value
- `$rule` (array) - Rule configuration
- `$match` (array) - Current match being processed
- `$query` (string) - The search query text

**Returns:** (float) Modified boost value

**Example:**
```php
// Modify the 'services_url' custom rule boost
add_filter('wp_ai_search_custom_boost_services_url', function($boost, $rule, $match, $query) {
    // Increase boost if query contains "what" (indicating a question)
    if (stripos($query, 'what') === 0) {
        return $boost + 0.05;
    }
    return $boost;
}, 10, 4);
```

---

#### `wp_ai_search_match_score`
**Type:** Filter
**Description:** Filter the final score for an individual match after all boosts are applied.

**Parameters:**
- `$final_score` (float) - Calculated final score (0.0-1.0)
- `$base_score` (float) - Original Pinecone similarity score
- `$boost` (float) - Total boost applied
- `$match` (array) - Current match being processed
- `$query` (string) - The search query text

**Returns:** (float) Modified final score

**Example:**
```php
// Cap maximum score at 0.95
add_filter('wp_ai_search_match_score', function($final_score, $base_score, $boost, $match, $query) {
    return min($final_score, 0.95);
}, 10, 5);
```

---

#### `wp_ai_search_boosted_matches`
**Type:** Filter
**Description:** Filter all matches after boosting and sorting is complete.

**Parameters:**
- `$matches` (array) - Boosted and sorted matches
- `$query` (string) - The search query text
- `$config` (array) - Boosting configuration used

**Returns:** (array) Modified matches

**Example:**
```php
// Limit to top 10 matches regardless of settings
add_filter('wp_ai_search_boosted_matches', function($matches, $query, $config) {
    return array_slice($matches, 0, 10);
}, 10, 3);
```

---

#### `wp_ai_search_after_boost`
**Type:** Action
**Description:** Fires after relevance boosting is complete.

**Parameters:**
- `$matches` (array) - Boosted and sorted matches
- `$query` (string) - The search query text
- `$config` (array) - Boosting configuration used

**Example:**
```php
add_action('wp_ai_search_after_boost', function($matches, $query, $config) {
    // Log top result after boosting
    if (!empty($matches[0])) {
        $top_result = $matches[0];
        error_log(sprintf(
            'Top result: %s (score: %.3f, boost: %.3f)',
            $top_result['metadata']['title'] ?? 'Unknown',
            $top_result['score'] ?? 0,
            $top_result['_boost'] ?? 0
        ));
    }
}, 10, 3);
```

---

### Result Formatting

#### `wp_ai_search_result_format`
**Type:** Filter
**Description:** Filter individual search result formatting.

**Parameters:**
- `$result` (array) - Formatted result
  - `post_id` (int) - WordPress post ID
  - `title` (string) - Post title
  - `url` (string) - Post URL
  - `excerpt` (string) - Trimmed excerpt
  - `score` (float) - Relevance score
- `$match` (array) - Raw Pinecone match

**Returns:** (array) Modified result

**Example:**
```php
// Add thumbnail to results
add_filter('wp_ai_search_result_format', function($result, $match) {
    if ($result['post_id']) {
        $thumbnail_id = get_post_thumbnail_id($result['post_id']);
        if ($thumbnail_id) {
            $result['thumbnail_url'] = wp_get_attachment_image_url($thumbnail_id, 'medium');
        }
    }
    return $result;
}, 10, 2);
```

---

### Summary Generation

#### `wp_ai_search_summary_enabled`
**Type:** Filter
**Description:** Filter whether AI summary generation is enabled for a specific query.

**Parameters:**
- `$enabled` (bool) - Whether summary is enabled
- `$query` (string) - The search query text
- `$results` (array) - Formatted search results

**Returns:** (bool) Modified enabled status

**Example:**
```php
// Disable summaries for very short queries
add_filter('wp_ai_search_summary_enabled', function($enabled, $query, $results) {
    if (strlen($query) < 5) {
        return false;
    }
    return $enabled;
}, 10, 3);
```

---

#### `wp_ai_search_summary_context`
**Type:** Filter
**Description:** Filter the context string passed to the AI for summary generation.

**Parameters:**
- `$context` (string) - Context string built from top matches
- `$query` (string) - The search query text
- `$results` (array) - Formatted search results
- `$matches` (array) - Raw Pinecone matches

**Returns:** (string) Modified context

**Example:**
```php
// Add site-specific context
add_filter('wp_ai_search_summary_context', function($context, $query, $results, $matches) {
    $context .= "\n\nSITE CONTEXT:\n";
    $context .= "This is a technology consulting company specializing in WordPress and Drupal.\n";
    return $context;
}, 10, 4);
```

---

#### `wp_ai_search_summary_system_prompt`
**Type:** Filter
**Description:** Filter the system prompt for search summary generation.

**Parameters:**
- `$system_prompt` (string) - System prompt from settings
- `$query` (string) - The search query text
- `$results` (array) - Formatted search results

**Returns:** (string) Modified system prompt

**Example:**
```php
// Adjust tone for question-based queries
add_filter('wp_ai_search_summary_system_prompt', function($prompt, $query, $results) {
    if (preg_match('/^(what|how|why|when|where|who)/i', $query)) {
        $prompt .= "\n\nThis is a question. Provide a direct, concise answer.";
    }
    return $prompt;
}, 10, 3);
```

---

#### `wp_ai_search_summary`
**Type:** Filter
**Description:** Filter the final AI-generated summary before returning to the client.

**Parameters:**
- `$summary` (string) - AI-generated summary (HTML)
- `$query` (WP_Query) - Query object (when used via theme integration)

**Returns:** (string) Modified summary

**Example:**
```php
// Add a disclaimer to AI summaries
add_filter('wp_ai_search_summary', function($summary, $query) {
    if (!empty($summary)) {
        $summary .= '<p class="ai-disclaimer"><small>This summary was generated by AI. Please verify important information.</small></p>';
    }
    return $summary;
}, 10, 2);
```

---

## Chatbot Module Hooks

### `wp_ai_chatbot_query_start`
**Type:** Action
**Description:** Fires at the start of a chatbot query.

**Parameters:**
- `$question` (string) - User's question
- `$request` (WP_REST_Request) - REST API request object

**Example:**
```php
add_action('wp_ai_chatbot_query_start', function($question, $request) {
    error_log('Chatbot question: ' . $question);
}, 10, 2);
```

---

### `wp_ai_chatbot_question`
**Type:** Filter
**Description:** Filter the chatbot question before processing.

**Parameters:**
- `$question` (string) - User's question
- `$request` (WP_REST_Request) - REST API request object

**Returns:** (string) Modified question

---

### `wp_ai_chatbot_top_k`
**Type:** Filter
**Description:** Filter the number of context results to retrieve.

**Parameters:**
- `$top_k` (int) - Number of results
- `$question` (string) - User's question

**Returns:** (int) Modified top_k value

---

### `wp_ai_chatbot_matches`
**Type:** Filter
**Description:** Filter Pinecone matches for chatbot context.

**Parameters:**
- `$matches` (array) - Pinecone matches
- `$question` (string) - User's question

**Returns:** (array) Modified matches

---

### `wp_ai_chatbot_context`
**Type:** Filter
**Description:** Filter the context string passed to the chatbot.

**Parameters:**
- `$context` (string) - Context string built from matches
- `$matches` (array) - Pinecone matches
- `$question` (string) - User's question

**Returns:** (string) Modified context

**Example:**
```php
// Add company information to context
add_filter('wp_ai_chatbot_context', function($context, $matches, $question) {
    $context .= "\n\nCOMPANY INFO: We're open Mon-Fri 9am-5pm EST. Call 555-0100 for urgent support.";
    return $context;
}, 10, 3);
```

---

### `wp_ai_chatbot_model`
**Type:** Filter
**Description:** Filter the OpenAI model for chatbot responses.

**Parameters:**
- `$model` (string) - Model identifier (e.g., 'gpt-4o-mini')
- `$question` (string) - User's question

**Returns:** (string) Modified model identifier

---

### `wp_ai_chatbot_temperature`
**Type:** Filter
**Description:** Filter the temperature parameter for chatbot responses.

**Parameters:**
- `$temperature` (float) - Temperature value (0.0-2.0)
- `$question` (string) - User's question

**Returns:** (float) Modified temperature

---

### `wp_ai_chatbot_system_prompt`
**Type:** Filter
**Description:** Filter the system prompt for chatbot responses.

**Parameters:**
- `$system_prompt` (string) - System prompt text
- `$question` (string) - User's question
- `$context` (string) - Context string

**Returns:** (string) Modified system prompt

---

### `wp_ai_chatbot_answer`
**Type:** Filter
**Description:** Filter the chatbot answer before returning.

**Parameters:**
- `$answer` (string) - Generated answer
- `$question` (string) - User's question
- `$context` (string) - Context used

**Returns:** (string) Modified answer

**Example:**
```php
// Add contact CTA to certain answers
add_filter('wp_ai_chatbot_answer', function($answer, $question, $context) {
    if (stripos($question, 'pricing') !== false || stripos($question, 'cost') !== false) {
        $answer .= "\n\nFor a detailed quote, please contact our sales team.";
    }
    return $answer;
}, 10, 3);
```

---

### `wp_ai_chatbot_sources`
**Type:** Filter
**Description:** Filter the chatbot sources before returning.

**Parameters:**
- `$sources` (array) - Formatted sources
- `$matches` (array) - Raw Pinecone matches
- `$question` (string) - User's question

**Returns:** (array) Modified sources

---

### `wp_ai_chatbot_before_log`
**Type:** Action
**Description:** Fires before logging the chatbot interaction.

**Parameters:**
- `$question` (string) - User's question
- `$answer` (string) - Generated answer
- `$sources` (array) - Source references

---

### `wp_ai_chatbot_query_end`
**Type:** Action
**Description:** Fires at the end of a chatbot query.

**Parameters:**
- `$response` (array) - Complete response array (answer, sources)
- `$question` (string) - User's question

---

## Indexer Hooks

### `wp_ai_indexer_node_path`
**Type:** Filter
**Description:** Filter the Node.js executable path used by the indexer.

**Parameters:**
- `$node_path` (string|null) - Path to Node.js or null to use default detection

**Returns:** (string|null) Modified path or null

**Example:**
```php
// Force use of specific Node.js version
add_filter('wp_ai_indexer_node_path', function($node_path) {
    return '/usr/local/bin/node';
});
```

---

## Best Practices

### Performance

1. **Cache Aggressively**: Use transients or object caching for expensive operations
2. **Limit Data Processing**: Filter data early in the pipeline to reduce processing
3. **Avoid External Calls**: Minimize HTTP requests in high-frequency hooks
4. **Use Appropriate Priorities**: Lower priority (higher number) for non-critical modifications

### Debugging

```php
// Enable debug logging for AI search
add_action('wp_ai_search_query_start', function($query) {
    error_log('=== AI Search Debug ===');
    error_log('Query: ' . $query);
}, 1);

add_action('wp_ai_search_after_boost', function($matches, $query, $config) {
    error_log('Boosted Results:');
    foreach ($matches as $i => $match) {
        error_log(sprintf(
            '#%d: %s (score: %.3f, boost: %.3f)',
            $i + 1,
            $match['metadata']['title'] ?? 'Unknown',
            $match['score'] ?? 0,
            $match['_boost'] ?? 0
        ));
    }
    error_log('=== End Debug ===');
}, 999, 3);
```

### Security

1. **Sanitize User Input**: Always sanitize data from filters before using
2. **Validate Permissions**: Check user capabilities when appropriate
3. **Escape Output**: Use `esc_html()`, `esc_url()`, etc. for output
4. **Limit Data Exposure**: Don't expose sensitive information in results

### Code Organization

```php
// Organize hooks in a class
class My_AI_Search_Customizations {
    public function __construct() {
        add_filter('wp_ai_search_relevance_config', array($this, 'add_custom_post_type_boosts'), 10, 2);
        add_filter('wp_ai_search_results', array($this, 'add_custom_metadata'), 10, 3);
        add_action('wp_ai_search_before_log', array($this, 'track_analytics'), 10, 2);
    }

    public function add_custom_post_type_boosts($config, $query) {
        $config['post_type_boosts']['case_study'] = 0.08;
        return $config;
    }

    public function add_custom_metadata($results, $query, $matches) {
        // Implementation
        return $results;
    }

    public function track_analytics($query, $results) {
        // Implementation
    }
}

new My_AI_Search_Customizations();
```

---

## Additional Resources

- [CUSTOMIZATION.md](CUSTOMIZATION.md) - Guide to customizing content preferences
- [examples/](../examples/) - Practical code examples
- [README.md](../README.md) - Plugin overview and getting started
