<?php
/**
 * Example: Search Analytics Tracking
 *
 * This example shows how to track AI search usage and send data to analytics platforms.
 *
 * Use case: Track search queries, results, and user engagement for analytics and optimization.
 *
 * Add this code to your theme's functions.php or a custom plugin.
 */

// Don't execute outside WordPress
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Track search query start
 */
add_action('wp_ai_search_query_start', 'my_track_search_start', 10, 2);

function my_track_search_start($query, $request) {
    // Track to Google Analytics (GA4)
    // This assumes you have gtag() available in your frontend
    // For server-side tracking, use Google Analytics Measurement Protocol

    // Log search query
    error_log(sprintf(
        'AI Search: Query="%s", User IP=%s, User Agent=%s',
        $query,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ));

    // Track to custom database table (optional)
    global $wpdb;
    $table = $wpdb->prefix . 'ai_search_analytics';

    // Create table if it doesn't exist (run once on plugin activation)
    // $wpdb->query("CREATE TABLE IF NOT EXISTS $table (
    //     id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    //     query TEXT NOT NULL,
    //     timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    //     user_ip VARCHAR(45),
    //     user_agent TEXT,
    //     results_count INT DEFAULT 0
    // )");

    // Store query
    // $wpdb->insert($table, array(
    //     'query' => $query,
    //     'user_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    //     'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    // ));
}

/**
 * Track search results
 */
add_action('wp_ai_search_query_end', 'my_track_search_results', 10, 2);

function my_track_search_results($response, $query) {
    $result_count = $response['total'] ?? 0;
    $has_summary = !empty($response['summary']);

    // Send to Google Analytics via Measurement Protocol
    // You'll need to set up GA4 and get your Measurement ID and API Secret
    $ga_measurement_id = 'G-XXXXXXXXXX'; // Your GA4 Measurement ID
    $ga_api_secret = 'your-api-secret';  // Your API secret

    if (!empty($ga_measurement_id) && !empty($ga_api_secret)) {
        $data = array(
            'client_id' => wp_hash($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'events' => array(
                array(
                    'name' => 'ai_search',
                    'params' => array(
                        'search_term' => $query,
                        'results_count' => $result_count,
                        'has_summary' => $has_summary,
                        'session_id' => session_id() ?: 'none',
                    ),
                ),
            ),
        );

        wp_remote_post(
            "https://www.google-analytics.com/mp/collect?measurement_id={$ga_measurement_id}&api_secret={$ga_api_secret}",
            array(
                'body' => json_encode($data),
                'headers' => array('Content-Type' => 'application/json'),
                'blocking' => false, // Non-blocking for performance
            )
        );
    }

    // Track zero results
    if ($result_count === 0) {
        // Log zero-result queries for optimization
        error_log("AI Search: Zero results for query: $query");

        // Track to custom analytics
        do_action('my_track_event', 'ai_search_zero_results', array(
            'query' => $query,
        ));
    }

    // Track popular queries (cache for performance)
    $popular_queries = get_transient('ai_search_popular_queries') ?: array();
    if (!isset($popular_queries[$query])) {
        $popular_queries[$query] = 0;
    }
    $popular_queries[$query]++;
    set_transient('ai_search_popular_queries', $popular_queries, WEEK_IN_SECONDS);
}

/**
 * Track search performance metrics
 */
add_action('wp_ai_search_after_pinecone_query', 'my_track_search_performance', 10, 2);

function my_track_search_performance($matches, $query) {
    $match_count = count($matches);

    // Track to custom monitoring (e.g., New Relic, DataDog)
    if (function_exists('newrelic_custom_metric')) {
        newrelic_custom_metric('Custom/AISearch/MatchCount', $match_count);
    }

    // Track average relevance score
    $total_score = 0;
    foreach ($matches as $match) {
        $total_score += $match['score'] ?? 0;
    }
    $avg_score = $match_count > 0 ? $total_score / $match_count : 0;

    if (function_exists('newrelic_custom_metric')) {
        newrelic_custom_metric('Custom/AISearch/AvgRelevance', $avg_score);
    }
}

/**
 * Generate analytics dashboard widget for wp-admin
 */
add_action('wp_dashboard_setup', 'my_ai_search_dashboard_widget');

function my_ai_search_dashboard_widget() {
    wp_add_dashboard_widget(
        'ai_search_analytics',
        'AI Search Analytics',
        'my_render_ai_search_dashboard'
    );
}

function my_render_ai_search_dashboard() {
    // Get popular queries
    $popular_queries = get_transient('ai_search_popular_queries') ?: array();
    arsort($popular_queries);
    $top_queries = array_slice($popular_queries, 0, 10, true);

    echo '<h3>Top Search Queries (This Week)</h3>';
    if (!empty($top_queries)) {
        echo '<ol>';
        foreach ($top_queries as $query => $count) {
            echo '<li>' . esc_html($query) . ' <strong>(' . $count . ' searches)</strong></li>';
        }
        echo '</ol>';
    } else {
        echo '<p>No search data available yet.</p>';
    }

    // Get recent searches from custom post type
    $recent_searches = get_posts(array(
        'post_type' => 'ai_search_log',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    if (!empty($recent_searches)) {
        echo '<h3>Recent Searches</h3>';
        echo '<ul>';
        foreach ($recent_searches as $search) {
            $result_count = get_post_meta($search->ID, '_search_count', true);
            echo '<li>';
            echo esc_html($search->post_title);
            echo ' - ' . $result_count . ' results';
            echo ' <small>(' . human_time_diff(get_post_time('U', false, $search->ID)) . ' ago)</small>';
            echo '</li>';
        }
        echo '</ul>';
    }
}

/**
 * Export analytics data via WP-CLI or admin endpoint
 */
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ai-search analytics', 'my_ai_search_analytics_cli');
}

function my_ai_search_analytics_cli($args, $assoc_args) {
    $days = $assoc_args['days'] ?? 7;

    WP_CLI::line('AI Search Analytics Report');
    WP_CLI::line('==========================');

    // Get all searches from the last X days
    $searches = get_posts(array(
        'post_type' => 'ai_search_log',
        'posts_per_page' => -1,
        'date_query' => array(
            array(
                'after' => "$days days ago",
            ),
        ),
    ));

    $total_searches = count($searches);
    $zero_results = 0;
    $query_counts = array();

    foreach ($searches as $search) {
        $query = $search->post_title;
        $result_count = get_post_meta($search->ID, '_search_count', true);

        if ($result_count == 0) {
            $zero_results++;
        }

        if (!isset($query_counts[$query])) {
            $query_counts[$query] = 0;
        }
        $query_counts[$query]++;
    }

    WP_CLI::line("Total Searches: $total_searches");
    WP_CLI::line("Zero Result Queries: $zero_results (" . round(($zero_results / max($total_searches, 1)) * 100, 1) . "%)");

    WP_CLI::line("\nTop 20 Queries:");
    arsort($query_counts);
    $top_20 = array_slice($query_counts, 0, 20, true);

    foreach ($top_20 as $query => $count) {
        WP_CLI::line("  - $query ($count times)");
    }
}

/**
 * Track chatbot interactions
 */
add_action('wp_ai_chatbot_query_end', 'my_track_chatbot', 10, 2);

function my_track_chatbot($response, $question) {
    // Track chatbot usage
    error_log("AI Chatbot: Question=\"$question\"");

    // Send to analytics
    // Similar to search tracking above
}
