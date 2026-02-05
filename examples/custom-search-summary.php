<?php
/**
 * Example: Custom Search Summary Modifications
 *
 * This example shows how to customize the AI-generated search summaries.
 *
 * Use case: Add context, modify tone, inject custom information, or adjust summaries.
 *
 * Add this code to your theme's functions.php or a custom plugin.
 */

// Don't execute outside WordPress
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add site-specific context to summaries
 */
add_filter('semantic_knowledge_search_summary_context', 'my_add_site_context', 10, 4);

function my_add_site_context($context, $query, $results, $matches) {
    // Add company information
    $context .= "\n\n=== COMPANY INFORMATION ===\n";
    $context .= "Company: Acme Corporation\n";
    $context .= "Location: San Francisco, CA\n";
    $context .= "Hours: Monday-Friday 9am-5pm PST\n";
    $context .= "Phone: (555) 123-4567\n";
    $context .= "Email: info@acme.com\n";

    // Add relevant disclaimers
    if (stripos($query, 'price') !== false || stripos($query, 'cost') !== false) {
        $context .= "\n=== PRICING DISCLAIMER ===\n";
        $context .= "Prices shown are subject to change. Contact sales for current pricing.\n";
    }

    return $context;
}

/**
 * Adjust system prompt based on query type
 */
add_filter('semantic_knowledge_search_summary_system_prompt', 'my_adjust_summary_prompt', 10, 3);

function my_adjust_summary_prompt($prompt, $query, $results) {
    // For question queries, be more direct
    if (preg_match('/^(what|how|why|when|where|who|can|is|are|do|does)/i', $query)) {
        $prompt .= "\n\nIMPORTANT: This is a question. Provide a direct, concise answer in the first sentence.";
    }

    // For product queries, include a CTA
    if (stripos($query, 'product') !== false || stripos($query, 'buy') !== false) {
        $prompt .= "\n\nIMPORTANT: End your response with a call-to-action to view products or contact sales.";
    }

    // For comparison queries, be balanced
    if (stripos($query, 'vs') !== false || stripos($query, 'versus') !== false || stripos($query, 'compare') !== false) {
        $prompt .= "\n\nIMPORTANT: Provide an objective comparison. List pros and cons of each option.";
    }

    return $prompt;
}

/**
 * Add disclaimers or CTAs to summaries
 */
add_filter('semantic_knowledge_search_summary', 'my_add_summary_enhancements', 10, 2);

function my_add_summary_enhancements($summary, $query) {
    if (empty($summary)) {
        return $summary;
    }

    // Add disclaimer for medical/legal content
    if (preg_match('/\b(medical|health|legal|law|attorney|doctor)\b/i', $query)) {
        $summary .= '<p class="disclaimer"><small><strong>Disclaimer:</strong> This information is for general educational purposes only and should not be construed as medical or legal advice. Please consult a qualified professional.</small></p>';
    }

    // Add contact CTA for service queries
    if (preg_match('/\b(service|help|support|contact|hire)\b/i', $query)) {
        $summary .= '<p class="cta"><strong>Need help?</strong> <a href="/contact/" rel="noopener">Contact our team</a> to discuss your project.</p>';
    }

    // Add related resources
    if (count(explode(' ', $query)) > 3) {
        $summary .= '<p class="related"><small>For more information, check out our <a href="/documentation/" rel="noopener">documentation</a> or <a href="/faq/" rel="noopener">FAQ</a>.</small></p>';
    }

    return $summary;
}

/**
 * Disable summaries for specific query types
 */
add_filter('semantic_knowledge_search_summary_enabled', 'my_conditional_summary_generation', 10, 3);

function my_conditional_summary_generation($enabled, $query, $results) {
    // Disable for single-word queries
    if (str_word_count($query) === 1) {
        return false;
    }

    // Disable when we have no results
    if (empty($results)) {
        return false;
    }

    // Disable for very simple navigational queries
    $nav_queries = array('home', 'about', 'contact', 'blog', 'shop');
    if (in_array(strtolower($query), $nav_queries)) {
        return false;
    }

    return $enabled;
}

/**
 * Inject structured data into summaries
 */
add_filter('semantic_knowledge_search_summary', 'my_add_structured_data', 20, 2);

function my_add_structured_data($summary, $query) {
    if (empty($summary)) {
        return $summary;
    }

    // Add FAQ schema for question queries
    if (preg_match('/^(what|how|why|when|where|who)/i', $query)) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array(
                array(
                    '@type' => 'Question',
                    'name' => $query,
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => wp_strip_all_tags($summary),
                    ),
                ),
            ),
        );

        $summary .= '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }

    return $summary;
}

/**
 * Add inline images or media to summaries
 */
add_filter('semantic_knowledge_search_summary_context', 'my_add_media_context', 10, 4);

function my_add_media_context($context, $query, $results, $matches) {
    // Find relevant featured images
    $images = array();

    foreach ($results as $result) {
        if (!empty($result['post_id'])) {
            $thumbnail_id = get_post_thumbnail_id($result['post_id']);
            if ($thumbnail_id) {
                $image_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
                $images[$result['post_id']] = $image_url;
            }
        }
    }

    if (!empty($images)) {
        $context .= "\n\n=== AVAILABLE IMAGES ===\n";
        foreach ($images as $post_id => $image_url) {
            $post_title = get_the_title($post_id);
            $context .= "- $post_title: $image_url\n";
        }
        $context .= "\nYou may reference these images in your response if relevant.\n";
    }

    return $context;
}

/**
 * Translate summaries based on user language
 */
add_filter('semantic_knowledge_search_summary', 'my_translate_summary', 30, 2);

function my_translate_summary($summary, $query) {
    if (empty($summary)) {
        return $summary;
    }

    // Detect user language (example using WPML or Polylang)
    $user_lang = function_exists('pll_current_language') ? pll_current_language() : 'en';

    // If not English, add translation instruction
    if ($user_lang !== 'en') {
        // This would require the AI to generate in the target language
        // You'd need to modify the system prompt earlier in the pipeline
    }

    return $summary;
}

/**
 * Add personalization based on user history
 */
add_filter('semantic_knowledge_search_summary_context', 'my_personalize_context', 10, 4);

function my_personalize_context($context, $query, $results, $matches) {
    // Get user's previous searches (requires tracking)
    $user_id = get_current_user_id();

    if ($user_id) {
        $recent_searches = get_user_meta($user_id, 'recent_ai_searches', true) ?: array();
        $recent_searches = array_slice($recent_searches, -5); // Last 5 searches

        if (!empty($recent_searches)) {
            $context .= "\n\n=== USER CONTEXT ===\n";
            $context .= "User's recent searches: " . implode(', ', $recent_searches) . "\n";
            $context .= "Consider this context when generating the response.\n";
        }

        // Store current search
        $recent_searches[] = $query;
        $recent_searches = array_slice($recent_searches, -10); // Keep last 10
        update_user_meta($user_id, 'recent_ai_searches', $recent_searches);
    }

    return $context;
}

/**
 * Format summaries for accessibility
 */
add_filter('semantic_knowledge_search_summary', 'my_enhance_accessibility', 40, 2);

function my_enhance_accessibility($summary, $query) {
    if (empty($summary)) {
        return $summary;
    }

    // Wrap summary in accessible container
    $summary = '<div role="region" aria-label="AI-generated search summary" class="ai-search-summary">' . $summary . '</div>';

    // Add skip link
    $summary = '<a href="#ai-summary-end" class="skip-link screen-reader-text">Skip AI summary</a>' . $summary . '<span id="ai-summary-end"></span>';

    return $summary;
}

/**
 * Provide fallback content when AI summary fails
 */
add_filter('semantic_knowledge_search_summary', 'my_fallback_summary', 50, 2);

function my_fallback_summary($summary, $query) {
    // If AI failed to generate a summary, provide a fallback
    if (empty($summary)) {
        $summary = '<p>We found several results for <strong>"' . esc_html($query) . '"</strong>. Browse the results below to find what you\'re looking for.</p>';

        // Add helpful suggestions
        $summary .= '<p class="search-tips"><small>';
        $summary .= 'Tip: Try refining your search with more specific terms or <a href="/search-help/">view our search guide</a>.';
        $summary .= '</small></p>';
    }

    return $summary;
}
