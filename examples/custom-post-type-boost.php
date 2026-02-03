<?php
/**
 * Example: Custom Post Type Boosting
 *
 * This example shows how to add custom post type boosts to improve search result relevance.
 *
 * Use case: You have custom post types (services, case studies, team members)
 * and want them to rank higher for relevant queries.
 *
 * Add this code to your theme's functions.php or a custom plugin.
 */

// Don't execute outside WordPress
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add custom post type boosts to relevance configuration
 */
add_filter('wp_ai_search_relevance_config', 'my_custom_post_type_boosts', 10, 2);

function my_custom_post_type_boosts($config, $query) {
    // Add static boosts for important custom post types
    $config['post_type_boosts']['services'] = 0.07;      // Services CPT gets +0.07
    $config['post_type_boosts']['case_study'] = 0.06;    // Case studies get +0.06
    $config['post_type_boosts']['team'] = 0.04;          // Team members get +0.04

    // Query-specific boosting
    // Boost services CPT even more when query contains "service" keyword
    if (preg_match('/\bservices?\b/i', $query)) {
        $config['post_type_boosts']['services'] = 0.12; // Increased boost
    }

    // Boost case studies for "example", "case study", or "success" queries
    if (preg_match('/\b(example|case study|success|portfolio)\b/i', $query)) {
        $config['post_type_boosts']['case_study'] = 0.10;
    }

    // Boost team members for "who", "team", "staff", or "about" queries
    if (preg_match('/\b(who|team|staff|about|contact)\b/i', $query)) {
        $config['post_type_boosts']['team'] = 0.08;
    }

    return $config;
}

/**
 * Alternative: More granular control with post_type_boost filter
 */
add_filter('wp_ai_search_post_type_boost', 'my_granular_post_type_boost', 10, 4);

function my_granular_post_type_boost($boost, $post_type, $match, $query) {
    // Boost based on post metadata
    if ($post_type === 'case_study') {
        $post_id = $match['metadata']['post_id'] ?? 0;

        if ($post_id) {
            // Boost featured case studies
            if (get_post_meta($post_id, 'featured', true)) {
                $boost += 0.05;
            }

            // Boost case studies from specific industries matching the query
            $industry = get_post_meta($post_id, 'industry', true);
            if (!empty($industry) && stripos($query, $industry) !== false) {
                $boost += 0.08;
            }

            // Boost recent case studies
            $post_date = get_post_time('U', false, $post_id);
            $age_days = (time() - $post_date) / DAY_IN_SECONDS;

            if ($age_days < 90) {
                $boost += 0.03; // Recent case study
            }
        }
    }

    return $boost;
}

/**
 * Example: Custom rule for specific URL patterns
 */
add_filter('wp_ai_search_relevance_config', 'my_custom_url_rules', 10, 2);

function my_custom_url_rules($config, $query) {
    // Boost /services/ pages for service-related queries
    if (preg_match('/\bservice(s)?\b/i', $query)) {
        $config['custom_rules']['services_url'] = array(
            'match' => '/services/',
            'boost' => 0.1,
        );
    }

    // Boost /about/ pages for company information queries
    if (preg_match('/\b(about|company|who|team)\b/i', $query)) {
        $config['custom_rules']['about_url'] = array(
            'match' => '/about/',
            'boost' => 0.09,
        );
    }

    // Boost /contact/ pages for contact queries
    if (preg_match('/\b(contact|reach|call|email|phone)\b/i', $query)) {
        $config['custom_rules']['contact_url'] = array(
            'match' => '/contact/',
            'boost' => 0.15,
        );
    }

    return $config;
}

/**
 * Example: Taxonomy-based boosting
 */
add_filter('wp_ai_search_match_score', 'my_taxonomy_based_boost', 10, 5);

function my_taxonomy_based_boost($final_score, $base_score, $boost, $match, $query) {
    $post_id = $match['metadata']['post_id'] ?? 0;

    if ($post_id) {
        // Get post categories
        $categories = wp_get_post_categories($post_id);

        // Boost posts in "featured" category
        $featured_cat = get_category_by_slug('featured');
        if ($featured_cat && in_array($featured_cat->term_id, $categories)) {
            $final_score += 0.05;
        }

        // Boost posts that match query-related categories
        foreach ($categories as $cat_id) {
            $cat = get_category($cat_id);
            if ($cat && stripos($query, $cat->name) !== false) {
                $final_score += 0.06;
                break;
            }
        }

        // Cap final score at 1.0
        $final_score = min($final_score, 1.0);
    }

    return $final_score;
}

/**
 * Example: Dynamic boosting based on post popularity
 */
add_filter('wp_ai_search_match_score', 'my_popularity_boost', 20, 5);

function my_popularity_boost($final_score, $base_score, $boost, $match, $query) {
    $post_id = $match['metadata']['post_id'] ?? 0;

    if ($post_id) {
        // Boost based on view count (assumes you track this)
        $view_count = get_post_meta($post_id, 'view_count', true);

        if ($view_count) {
            if ($view_count > 1000) {
                $final_score += 0.04; // Very popular
            } elseif ($view_count > 500) {
                $final_score += 0.02; // Popular
            }
        }

        // Cap final score at 1.0
        $final_score = min($final_score, 1.0);
    }

    return $final_score;
}
