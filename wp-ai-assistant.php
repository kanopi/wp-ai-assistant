<?php
/**
 * Plugin Name: WP AI Assistant
 * Description: AI-powered chatbot and semantic search using OpenAI and Pinecone with RAG architecture
 * Version: 1.0.0
 * Author: Kanopi Studios
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_AI_ASSISTANT_VERSION', '1.0.0' );
define( 'WP_AI_ASSISTANT_SCHEMA_VERSION', 1 );
define( 'WP_AI_ASSISTANT_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_AI_ASSISTANT_URL', plugin_dir_url( __FILE__ ) );

// Load dependencies
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-logger.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-cache.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-database.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-assets.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-core.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-secrets.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-openai.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-pinecone.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-settings.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-indexer-controller.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-system-check.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/migrations/class-wp-ai-migration.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/modules/class-wp-ai-chatbot-module.php';
require_once WP_AI_ASSISTANT_DIR . 'includes/modules/class-wp-ai-search-module.php';

// Load WP-CLI commands if WP-CLI is available
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WP_AI_ASSISTANT_DIR . 'includes/class-wp-ai-cli.php';
}

class WP_AI_Assistant {
	const VERSION = WP_AI_ASSISTANT_VERSION;
	const SCHEMA_VERSION = WP_AI_ASSISTANT_SCHEMA_VERSION;
	const OPTION_KEY = 'wp_ai_assistant_settings';

	private static $instance = null;
	private $core;
	private $secrets;
	private $openai;
	private $pinecone;
	private $settings;
	private $chatbot_module;
	private $search_module;
	private $indexer_controller;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_core();
		$this->init_modules();
		$this->register_hooks();

		// Initialize asset optimization
		WP_AI_Assets::init();
	}

	private function init_core() {
		$this->core = new WP_AI_Core();
		$this->secrets = new WP_AI_Secrets();
		$this->openai = new WP_AI_OpenAI( $this->core, $this->secrets );
		$this->pinecone = new WP_AI_Pinecone( $this->core, $this->secrets );
		$this->settings = new WP_AI_Settings( $this->core );
		$this->indexer_controller = new WP_AI_Indexer_Settings_Controller();
	}

	private function init_modules() {
		$settings = $this->core->get_settings();

		if ( ! empty( $settings['chatbot_enabled'] ) ) {
			$this->chatbot_module = new WP_AI_Chatbot_Module(
				$this->core,
				$this->openai,
				$this->pinecone
			);
		}

		if ( ! empty( $settings['search_enabled'] ) ) {
			$this->search_module = new WP_AI_Search_Module(
				$this->core,
				$this->openai,
				$this->pinecone
			);
		}
	}

	private function register_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		add_action( 'rest_api_init', array( $this->indexer_controller, 'register_routes' ) );

		// Security headers
		add_action( 'send_headers', array( $this, 'add_security_headers' ) );

		// Cache invalidation on settings update
		add_action( 'update_option_' . self::OPTION_KEY, array( 'WP_AI_Indexer_Settings_Controller', 'clear_cache' ) );

		// System checks
		add_action( 'admin_notices', array( 'WP_AI_System_Check', 'show_admin_notice' ) );
		add_action( 'wp_ajax_wp_ai_assistant_dismiss_notice', array( 'WP_AI_System_Check', 'ajax_dismiss_notice' ) );
		add_action( 'wp_ajax_wp_ai_assistant_recheck', array( 'WP_AI_System_Check', 'ajax_recheck' ) );

		// Cron jobs
		add_action( 'wp_ai_assistant_cleanup_logs', array( $this, 'cleanup_old_logs' ) );
	}

	/**
	 * Add security headers to HTTP responses
	 * Implements Content Security Policy and other security headers
	 *
	 * Note: CSP is disabled by default for compatibility with WordPress themes and plugins.
	 * Enable it by defining WP_AI_ENABLE_CSP constant or using the wp_ai_assistant_enable_csp filter.
	 */
	public function add_security_headers() {
		// Only add headers on frontend and REST API requests
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		// Check if CSP should be enabled (disabled by default for compatibility)
		$enable_csp = defined( 'WP_AI_ENABLE_CSP' ) && WP_AI_ENABLE_CSP;
		$enable_csp = apply_filters( 'wp_ai_assistant_enable_csp', $enable_csp );

		if ( $enable_csp ) {
			// Generate CSP nonce if not already set
			if ( ! defined( 'WP_AI_CSP_NONCE' ) ) {
				$nonce = base64_encode( wp_generate_password( 32, false ) );
				define( 'WP_AI_CSP_NONCE', $nonce );
			}

			// Content Security Policy
			// Compatible directives that work with most WordPress themes
			$csp_directives = array(
				"default-src 'self'",
				"script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://js.hs-scripts.com https://js.hsforms.net",
				"style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
				"img-src 'self' data: https:",
				"connect-src 'self' https://api.openai.com https://*.pinecone.io",
				"font-src 'self' data: https://fonts.gstatic.com",
				"frame-ancestors 'self'",
				"base-uri 'self'",
				"form-action 'self'",
			);

			/**
			 * Filter CSP directives for WP AI Assistant
			 *
			 * @param array $csp_directives Array of CSP directives
			 */
			$csp_directives = apply_filters( 'wp_ai_assistant_csp_directives', $csp_directives );

			// Only set CSP if not already set
			if ( ! headers_sent() ) {
				$csp_header = implode( '; ', $csp_directives );
				header( "Content-Security-Policy: {$csp_header}", false );
			}
		}

		// Always add basic security headers (these are safe and widely compatible)
		if ( ! headers_sent() ) {
			header( 'X-Content-Type-Options: nosniff', false );
			header( 'X-Frame-Options: SAMEORIGIN', false );
			header( 'Referrer-Policy: strict-origin-when-cross-origin', false );
		}
	}

	/**
	 * Get CSP nonce for inline scripts/styles
	 *
	 * @return string CSP nonce
	 */
	public function get_csp_nonce() {
		if ( defined( 'WP_AI_CSP_NONCE' ) ) {
			return WP_AI_CSP_NONCE;
		}
		return '';
	}

	public function activate() {
		// Initialize database tables
		WP_AI_Database::init();

		// Set default settings if none exist
		if ( ! get_option( self::OPTION_KEY ) ) {
			$this->core->set_default_settings();
		}

		// Create minified assets (in production)
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			WP_AI_Assets::create_minified_assets();
		}

		// Schedule log cleanup cron job (daily)
		if ( ! wp_next_scheduled( 'wp_ai_assistant_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'wp_ai_assistant_cleanup_logs' );
		}

		// Clear system check cache on activation
		WP_AI_System_Check::clear_cache();

		// Run checks and store results
		$status = WP_AI_System_Check::run_checks( false );

		// Initialize data retention policies
		WP_AI_Migration::init_retention_policies();

		// Warm the indexer settings cache
		WP_AI_Indexer_Settings_Controller::clear_cache();
	}

	/**
	 * Plugin deactivation hook
	 */
	public function deactivate() {
		// Unschedule cleanup tasks
		WP_AI_Migration::unschedule_cleanup();

		// Unschedule log cleanup
		wp_clear_scheduled_hook( 'wp_ai_assistant_cleanup_logs' );
	}

	/**
	 * Clean up old logs (cron job handler)
	 * Removes logs older than the configured retention period
	 */
	public function cleanup_old_logs() {
		$settings = $this->core->get_settings();
		$days_to_keep = isset( $settings['log_retention_days'] ) ? (int) $settings['log_retention_days'] : 90;

		$result = WP_AI_Database::cleanup_old_logs( $days_to_keep );

		$this->core->log( 'Log cleanup completed: ' . json_encode( $result ) );
	}

	public function get_core() {
		return $this->core;
	}

	public function get_openai() {
		return $this->openai;
	}

	public function get_pinecone() {
		return $this->pinecone;
	}
}

// Initialize plugin
WP_AI_Assistant::instance();

// ============================================================================
// Template Functions for Themes
// ============================================================================

/**
 * Get AI-generated search summary for current query
 *
 * Use this in your theme's search.php template to display the AI summary:
 *
 * Example usage:
 * <?php
 * if ( function_exists( 'wp_ai_get_search_summary' ) ) {
 *     $summary = wp_ai_get_search_summary();
 *     if ( $summary ) {
 *         echo '<div class="ai-search-summary">';
 *         echo '<h2>AI Summary</h2>';
 *         echo wp_kses_post( $summary );
 *         echo '</div>';
 *     }
 * }
 * ?>
 *
 * @param WP_Query|null $query Optional query object. Uses global $wp_query if not provided.
 * @return string|null AI summary or null if not available
 */
function wp_ai_get_search_summary( $query = null ) {
	return WP_AI_Search_Module::get_search_summary( $query );
}

/**
 * Check if current query is an AI-powered search
 *
 * @param WP_Query|null $query Optional query object
 * @return bool
 */
function wp_ai_is_search( $query = null ) {
	return WP_AI_Search_Module::is_ai_search( $query );
}

/**
 * Display AI search summary with default styling
 *
 * @param array $args Optional display arguments
 */
function wp_ai_the_search_summary( $args = array() ) {
	$defaults = array(
		'before'         => '<div class="ai-search-summary">',
		'after'          => '</div>',
		'title'          => '<h2 class="ai-search-summary__title">AI Summary</h2>',
		'content_before' => '<div class="ai-search-summary__content">',
		'content_after'  => '</div>',
		'show_badge'     => true,
		'badge_text'     => 'AI-Generated',
	);

	$args = wp_parse_args( $args, $defaults );

	$summary = wp_ai_get_search_summary();

	if ( empty( $summary ) ) {
		return;
	}

	echo $args['before'];

	if ( ! empty( $args['title'] ) ) {
		echo $args['title'];
	}

	echo $args['content_before'];
	echo wp_kses_post( $summary );
	echo $args['content_after'];

	if ( $args['show_badge'] ) {
		echo '<div class="ai-search-summary__badge">';
		echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="vertical-align: middle; margin-right: 4px;"><path d="M8 0L10 6L16 6L11 10L13 16L8 12L3 16L5 10L0 6L6 6Z"/></svg>';
		echo esc_html( $args['badge_text'] );
		echo '</div>';
	}

	echo $args['after'];
}
