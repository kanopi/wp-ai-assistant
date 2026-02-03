<?php
/**
 * Example: Performance Optimization
 *
 * This example shows how to implement caching and performance optimizations for AI search.
 *
 * Use case: Reduce API calls to OpenAI/Pinecone and improve response times.
 *
 * Add this code to your theme's functions.php or a custom plugin.
 */

// Don't execute outside WordPress
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache search results for repeat queries
 */
add_action('wp_ai_search_query_end', 'my_cache_search_results', 10, 2);

function my_cache_search_results($response, $query) {
    // Generate cache key from query
    $cache_key = 'ai_search_' . md5(strtolower(trim($query)));

    // Cache for 1 hour
    set_transient($cache_key, $response, HOUR_IN_SECONDS);

    // Also store in object cache if available
    if (function_exists('wp_cache_set')) {
        wp_cache_set($cache_key, $response, 'ai_search', HOUR_IN_SECONDS);
    }
}

/**
 * Return cached results for repeat queries
 */
add_filter('wp_ai_search_query_text', 'my_check_search_cache', 5, 2);

function my_check_search_cache($query, $request) {
    $cache_key = 'ai_search_' . md5(strtolower(trim($query)));

    // Check transient cache
    $cached_response = get_transient($cache_key);

    // Check object cache as fallback
    if ($cached_response === false && function_exists('wp_cache_get')) {
        $cached_response = wp_cache_get($cache_key, 'ai_search');
    }

    // If we have cached results, return them immediately
    if ($cached_response !== false) {
        // Send cached response and exit
        wp_send_json($cached_response);
        exit;
    }

    return $query;
}

/**
 * Cache embeddings for common queries
 */
add_action('wp_ai_search_before_embedding', 'my_cache_embedding_start');

function my_cache_embedding_start($query) {
    // Store query in global for later use
    $GLOBALS['ai_search_current_query'] = $query;
}

/**
 * Implement request coalescing for concurrent identical queries
 */
add_filter('wp_ai_search_query_text', 'my_request_coalescing', 3, 2);

function my_request_coalescing($query, $request) {
    $lock_key = 'ai_search_lock_' . md5(strtolower(trim($query)));

    // Check if another request is already processing this query
    $lock = get_transient($lock_key);

    if ($lock) {
        // Wait for the other request to complete (max 10 seconds)
        $wait_time = 0;
        $cache_key = 'ai_search_' . md5(strtolower(trim($query)));

        while ($wait_time < 10) {
            sleep(1);
            $wait_time++;

            $result = get_transient($cache_key);
            if ($result !== false) {
                // Result is ready, return it
                wp_send_json($result);
                exit;
            }
        }
    } else {
        // Set lock for this query
        set_transient($lock_key, true, 30); // 30 second lock
    }

    return $query;
}

/**
 * Release lock after query completes
 */
add_action('wp_ai_search_query_end', 'my_release_query_lock', 999, 2);

function my_release_query_lock($response, $query) {
    $lock_key = 'ai_search_lock_' . md5(strtolower(trim($query)));
    delete_transient($lock_key);
}

/**
 * Optimize match processing by limiting expensive operations
 */
add_filter('wp_ai_search_raw_matches', 'my_optimize_match_processing', 10, 2);

function my_optimize_match_processing($matches, $query) {
    // Limit to top 20 matches for processing (reduces boosting overhead)
    if (count($matches) > 20) {
        $matches = array_slice($matches, 0, 20);
    }

    return $matches;
}

/**
 * Lazy load post metadata to reduce database queries
 */
add_filter('wp_ai_search_result_format', 'my_lazy_load_metadata', 10, 2);

function my_lazy_load_metadata($result, $match) {
    // Don't fetch additional metadata unless needed
    // Store post_id for later use instead of fetching now
    $result['_post_id'] = $result['post_id'];

    return $result;
}

/**
 * Implement result pagination to reduce payload size
 */
add_filter('wp_ai_search_results', 'my_paginate_results', 10, 3);

function my_paginate_results($results, $query, $matches) {
    // Get pagination parameters from request
    $page = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
    $per_page = isset($_REQUEST['per_page']) ? min(50, max(1, intval($_REQUEST['per_page']))) : 10;

    // Calculate offset
    $offset = ($page - 1) * $per_page;

    // Return paginated slice
    $paginated = array_slice($results, $offset, $per_page);

    // Store pagination info in response (requires modifying return format)
    // This is just an example - you'd need to adjust the actual response format
    add_filter('rest_prepare_ai_search_results', function($response) use ($results, $page, $per_page) {
        $response['pagination'] = array(
            'total' => count($results),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil(count($results) / $per_page),
        );
        return $response;
    });

    return $paginated;
}

/**
 * Implement selective summary generation
 */
add_filter('wp_ai_search_summary_enabled', 'my_selective_summary_generation', 10, 3);

function my_selective_summary_generation($enabled, $query, $results) {
    // Disable summary for very common queries (use cache instead)
    $common_queries = array('home', 'about', 'contact', 'services');

    if (in_array(strtolower($query), $common_queries)) {
        // Use a pre-written cached summary
        $summary_key = 'ai_search_summary_' . strtolower($query);
        $cached_summary = get_option($summary_key);

        if ($cached_summary) {
            // Inject cached summary (this is simplified - you'd need proper injection)
            add_filter('wp_ai_search_summary', function() use ($cached_summary) {
                return $cached_summary;
            });
        }

        return false; // Disable AI generation
    }

    // Disable for very long queries (they take longer to process)
    if (strlen($query) > 200) {
        return false;
    }

    return $enabled;
}

/**
 * Use object caching for expensive operations
 */
add_filter('wp_ai_search_relevance_config', 'my_cache_relevance_config', 10, 2);

function my_cache_relevance_config($config, $query) {
    // Cache configuration for identical queries
    $config_key = 'ai_search_config_' . md5($query);

    if (function_exists('wp_cache_get')) {
        $cached_config = wp_cache_get($config_key, 'ai_search');

        if ($cached_config !== false) {
            return $cached_config;
        }

        // Store config in cache after filtering
        wp_cache_set($config_key, $config, 'ai_search', 5 * MINUTE_IN_SECONDS);
    }

    return $config;
}

/**
 * Prefetch and warm cache for popular queries
 */
function my_warm_search_cache() {
    // Get popular queries
    $popular_queries = get_transient('ai_search_popular_queries') ?: array();
    arsort($popular_queries);
    $top_queries = array_slice($popular_queries, 0, 10, true);

    foreach ($top_queries as $query => $count) {
        $cache_key = 'ai_search_' . md5(strtolower(trim($query)));

        // Check if cache is expired
        if (get_transient($cache_key) === false) {
            // Cache is expired, trigger a refresh in background
            // This would require setting up a background job system
            error_log("Cache warming needed for: $query");
        }
    }
}

// Schedule cache warming twice daily
if (!wp_next_scheduled('ai_search_warm_cache')) {
    wp_schedule_event(time(), 'twicedaily', 'ai_search_warm_cache');
}
add_action('ai_search_warm_cache', 'my_warm_search_cache');

/**
 * Monitor performance metrics
 */
add_action('wp_ai_search_query_end', 'my_monitor_performance', 10, 2);

function my_monitor_performance($response, $query) {
    // Track query execution time
    $execution_time = microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));

    if ($execution_time > 5) {
        // Log slow queries
        error_log(sprintf(
            'Slow AI search query: "%s" took %.2f seconds',
            $query,
            $execution_time
        ));
    }

    // Store performance metrics
    $metrics = get_transient('ai_search_performance_metrics') ?: array();
    $metrics[] = array(
        'query' => $query,
        'time' => $execution_time,
        'results' => $response['total'] ?? 0,
        'timestamp' => time(),
    );

    // Keep last 100 metrics
    $metrics = array_slice($metrics, -100);
    set_transient('ai_search_performance_metrics', $metrics, WEEK_IN_SECONDS);
}

/**
 * Clear caches when content is updated
 */
add_action('save_post', 'my_clear_search_cache_on_update', 10, 3);

function my_clear_search_cache_on_update($post_id, $post, $update) {
    if ($update && $post->post_status === 'publish') {
        // Clear all search caches when content is updated
        global $wpdb;

        // Delete all ai_search transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_ai_search_%'
            OR option_name LIKE '_transient_timeout_ai_search_%'"
        );

        // Clear object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush_group('ai_search');
        }
    }
}
