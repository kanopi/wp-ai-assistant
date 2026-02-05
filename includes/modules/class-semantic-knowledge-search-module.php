<?php
/**
 * WP AI Search Module
 * Provides AI-powered semantic search functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Semantic_Knowledge_Search_Module {
	const ROUTE_NAMESPACE = 'semantic-knowledge/v1';
	const ROUTE_SEARCH = '/search';
	const POST_TYPE = 'ai_search_log';

	private $core;
	private $openai;
	private $pinecone;

	/**
	 * Constructor
	 *
	 * @param WP_AI_Core $core Core functionality
	 * @param WP_AI_OpenAI $openai OpenAI integration
	 * @param WP_AI_Pinecone $pinecone Pinecone integration
	 */
	public function __construct( WP_AI_Core $core, WP_AI_OpenAI $openai, WP_AI_Pinecone $pinecone ) {
		$this->core = $core;
		$this->openai = $openai;
		$this->pinecone = $pinecone;

		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'init', array( $this, 'register_search_log_post_type' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_filter( 'script_loader_tag', array( $this, 'add_csp_nonce_to_script' ), 10, 3 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'set_search_log_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_search_log_column' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_search_log_meta_boxes' ) );
		add_action( 'admin_head', array( $this, 'hide_search_log_editor' ) );
		add_shortcode( 'ai_search', array( $this, 'render_shortcode' ) );

		// Optionally intercept default WordPress search
		if ( (bool) $this->core->get_setting( 'search_replace_default', false ) ) {
			add_action( 'pre_get_posts', array( $this, 'intercept_search_query' ) );
		}
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			self::ROUTE_SEARCH,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_search_query' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'query' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_query' ),
					),
					'top_k' => array(
						'required' => false,
						'type'     => 'integer',
					),
					'nonce' => array(
						'required' => false,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * Check permission for REST API access
	 * Validates nonce for CSRF protection
	 *
	 * @param WP_REST_Request $request REST request object
	 * @return bool|WP_Error True if permission granted, WP_Error otherwise
	 */
	public function check_permission( $request ) {
		// Verify nonce for CSRF protection
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) ) {
			$nonce = $request->get_param( 'nonce' );
		}

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'semantic_knowledge_invalid_nonce',
				'Invalid security token. Please refresh the page and try again.',
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Validate search query input
	 *
	 * @param string $value Query text
	 * @param WP_REST_Request $request REST request object
	 * @param string $param Parameter name
	 * @return bool|WP_Error
	 */
	public function validate_query( $value, $request, $param ) {
		// Check maximum length (1000 characters)
		if ( strlen( $value ) > 1000 ) {
			return new WP_Error(
				'semantic_knowledge_query_too_long',
				'Search query must be 1000 characters or less.',
				array( 'status' => 400 )
			);
		}

		// Check minimum length
		if ( strlen( trim( $value ) ) === 0 ) {
			return new WP_Error(
				'semantic_knowledge_query_empty',
				'Search query cannot be empty.',
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Check rate limiting for API requests
	 * Limits requests to 10 per minute per IP address
	 *
	 * @param WP_REST_Request $request REST request object
	 * @return bool|WP_Error True if within limits, WP_Error if exceeded
	 */
	private function check_rate_limit( $request ) {
		// Get client IP address
		$ip_address = $this->get_client_ip();

		// Allow filtering of rate limit settings
		$rate_limit = apply_filters( 'semantic_knowledge_search_rate_limit', 10 );
		$rate_window = apply_filters( 'semantic_knowledge_search_rate_window', 60 ); // seconds

		// Create transient key
		$transient_key = 'semantic_knowledge_search_rl_' . wp_hash( $ip_address );

		// Get current request count
		$requests = get_transient( $transient_key );

		if ( false === $requests ) {
			// First request in this window
			set_transient( $transient_key, 1, $rate_window );
			return true;
		}

		// Check if limit exceeded
		if ( $requests >= $rate_limit ) {
			return new WP_Error(
				'semantic_knowledge_rate_limit_exceeded',
				sprintf(
					'Rate limit exceeded. Please wait before making another request. Limit: %d requests per %d seconds.',
					$rate_limit,
					$rate_window
				),
				array( 'status' => 429 )
			);
		}

		// Increment request count
		set_transient( $transient_key, $requests + 1, $rate_window );

		return true;
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address
	 */
	private function get_client_ip() {
		$ip_address = '';

		// Check for proxy headers (in order of preference)
		$headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip_address = $_SERVER[ $header ];

				// Handle comma-separated list (X-Forwarded-For)
				if ( strpos( $ip_address, ',' ) !== false ) {
					$ips = explode( ',', $ip_address );
					$ip_address = trim( $ips[0] );
				}

				break;
			}
		}

		// Validate IP address
		if ( filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
			return $ip_address;
		}

		// Fallback
		return 'unknown';
	}

	/**
	 * Register search log custom post type
	 */
	public function register_search_log_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => 'Search Logs',
					'singular_name' => 'Search Log',
					'menu_name'     => 'AI Search Logs',
					'all_items'     => 'All Search Logs',
					'view_item'     => 'View Search Log',
					'search_items'  => 'Search Logs',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-search',
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Register and enqueue frontend assets
	 */
	public function register_assets() {
		$handle = 'semantic-knowledge-search';

		// Register search styles
		wp_register_style(
			$handle,
			SEMANTIC_KNOWLEDGE_URL . 'assets/css/search.css',
			array(),
			SEMANTIC_KNOWLEDGE_VERSION
		);

		// Register search script
		wp_register_script(
			$handle,
			SEMANTIC_KNOWLEDGE_URL . 'assets/js/search.js',
			array( 'jquery' ),
			SEMANTIC_KNOWLEDGE_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}

	/**
	 * Add CSP nonce to search script tags for security
	 *
	 * @param string $tag Script tag HTML
	 * @param string $handle Script handle
	 * @param string $src Script source URL
	 * @return string Modified script tag
	 */
	public function add_csp_nonce_to_script( $tag, $handle, $src ) {
		// Add CSP nonce to our search script for security
		if ( 'semantic-knowledge-search' === $handle ) {
			$nonce = $this->core->get_csp_nonce();
			if ( ! empty( $nonce ) && strpos( $tag, 'nonce=' ) === false ) {
				$tag = str_replace( '<script ', '<script nonce="' . esc_attr( $nonce ) . '" ', $tag );
			}
		}

		return $tag;
	}

	/**
	 * Handle search query REST API request
	 *
	 * @param WP_REST_Request $request REST request object
	 * @return WP_REST_Response|WP_Error Response with results or error
	 */
	public function handle_search_query( WP_REST_Request $request ) {
		// Check rate limiting
		$rate_limit_check = $this->check_rate_limit( $request );
		if ( is_wp_error( $rate_limit_check ) ) {
			return $rate_limit_check;
		}

		if ( ! $this->is_configured() ) {
			return new WP_Error(
				'semantic_knowledge_not_configured',
				'Search API keys are missing.',
				array( 'status' => 500 )
			);
		}

		$query = sanitize_text_field( $request->get_param( 'query' ) );
		$top_k = (int) $request->get_param( 'top_k' );

		if ( empty( $query ) ) {
			return new WP_Error(
				'semantic_knowledge_empty_query',
				'Please provide a search query.',
				array( 'status' => 400 )
			);
		}

		/**
		 * Fires at the start of a search query.
		 *
		 * @param string $query Search query text
		 * @param WP_REST_Request $request REST request object
		 */
		do_action( 'semantic_knowledge_search_query_start', $query, $request );

		/**
		 * Filter the search query text before processing.
		 *
		 * @param string $query Search query text
		 * @param WP_REST_Request $request REST request object
		 * @return string Modified query text
		 */
		$query = apply_filters( 'semantic_knowledge_search_query_text', $query, $request );

		if ( $top_k <= 0 ) {
			$top_k = (int) $this->core->get_setting( 'search_top_k', 10 );
		}

		/**
		 * Filter the number of results to retrieve from Pinecone.
		 *
		 * @param int $top_k Number of results
		 * @param string $query Search query text
		 * @return int Modified top_k value
		 */
		$top_k = apply_filters( 'semantic_knowledge_search_top_k', $top_k, $query );

		/**
		 * Fires before creating the search embedding.
		 *
		 * @param string $query Search query text
		 */
		do_action( 'semantic_knowledge_search_before_embedding', $query );

		// Step 1: Create embedding (with caching)
		$cached_embedding = WP_AI_Cache::get_embedding( $query );
		if ( false !== $cached_embedding ) {
			$embedding = $cached_embedding;
			$this->core->log( 'Using cached embedding for search query' );
		} else {
			$embedding = $this->openai->create_embedding( $query );
			if ( is_wp_error( $embedding ) ) {
				return $embedding;
			}

			// Cache the embedding for future use (1 hour TTL)
			WP_AI_Cache::set_embedding( $query, $embedding, WP_AI_Cache::WARM_CACHE_TTL );
		}

		// Step 2: Query Pinecone with domain filter (with caching)
		$cached_matches = WP_AI_Cache::get_query_results( $embedding, $top_k );
		if ( false !== $cached_matches ) {
			$matches = $cached_matches;
			$this->core->log( 'Using cached search results' );
		} else {
			$matches = $this->pinecone->query_with_domain_filter( $embedding, $top_k );
			if ( is_wp_error( $matches ) ) {
				return $matches;
			}

			// Cache the query results (15 minutes TTL for hot cache)
			WP_AI_Cache::set_query_results( $embedding, $top_k, $matches, WP_AI_Cache::DEFAULT_TTL );
		}

		/**
		 * Fires after querying Pinecone for results.
		 *
		 * @param array $matches Raw Pinecone matches
		 * @param string $query Search query text
		 */
		do_action( 'semantic_knowledge_search_after_pinecone_query', $matches, $query );

		// Debug: Log domain filter and results
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$current_domain = $this->core->get_current_domain();
			error_log( 'WP AI Search - Domain filter: ' . $current_domain );
			error_log( 'WP AI Search - Raw matches count: ' . count( $matches ) );
			foreach ( $matches as $i => $match ) {
				$match_domain = $match['metadata']['domain'] ?? 'NO_DOMAIN';
				$match_title = $match['metadata']['title'] ?? 'NO_TITLE';
				error_log( sprintf( 'WP AI Search - Match #%d: %s (domain: %s)', $i, $match_title, $match_domain ) );
			}
		}

		// Step 3: Filter by minimum score if configured
		$min_score = (float) $this->core->get_setting( 'search_min_score', 0.5 );

		/**
		 * Filter the minimum score threshold.
		 *
		 * @param float $min_score Minimum score threshold
		 * @param string $query Search query text
		 * @return float Modified threshold
		 */
		$min_score = apply_filters( 'semantic_knowledge_search_min_score', $min_score, $query );

		$matches = $this->filter_by_score( $matches, $min_score );

		// Step 4: Apply relevance boosting to re-rank results
		$matches = $this->apply_relevance_boosting( $matches, $query );

		// Step 5: Format results
		$results = $this->format_search_results( $matches );

		/**
		 * Filter the complete results array.
		 *
		 * @param array $results Formatted search results
		 * @param string $query Search query text
		 * @param array $matches Raw Pinecone matches
		 * @return array Modified results
		 */
		$results = apply_filters( 'semantic_knowledge_search_results', $results, $query, $matches );

		// Step 6: Generate AI summary from results
		$summary = $this->generate_search_summary( $query, $results, $matches );

		/**
		 * Fires before logging the search query.
		 *
		 * @param string $query Search query text
		 * @param array $results Formatted results
		 */
		do_action( 'semantic_knowledge_search_before_log', $query, $results );

		// Step 7: Log search
		$this->log_search_query( $query, $results );

		// Step 8: Return response
		$response = array(
			'query'   => $query,
			'summary' => $summary,
			'results' => $results,
			'total'   => count( $results ),
		);

		/**
		 * Fires at the end of a search query.
		 *
		 * @param array $response Complete response array
		 * @param string $query Search query text
		 */
		do_action( 'semantic_knowledge_search_query_end', $response, $query );

		return rest_ensure_response( $response );
	}

	/**
	 * Intercept WordPress search query to use AI search
	 *
	 * @param WP_Query $query Query object
	 */
	public function intercept_search_query( $query ) {
		// Only intercept main search queries on frontend
		if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
			$search_term = $query->get( 's' );

			if ( ! empty( $search_term ) ) {
				// Store original search term for display
				set_query_var( 'ai_search_query', $search_term );

				// Get AI search results (with caching)
				$cached_embedding = WP_AI_Cache::get_embedding( $search_term );
				if ( false !== $cached_embedding ) {
					$embedding = $cached_embedding;
				} else {
					$embedding = $this->openai->create_embedding( $search_term );
					if ( ! is_wp_error( $embedding ) ) {
						WP_AI_Cache::set_embedding( $search_term, $embedding, WP_AI_Cache::WARM_CACHE_TTL );
					}
				}

				if ( ! is_wp_error( $embedding ) ) {
					$top_k = (int) $this->core->get_setting( 'search_top_k', 10 );

					// Check cache for query results
					$cached_matches = WP_AI_Cache::get_query_results( $embedding, $top_k );
					if ( false !== $cached_matches ) {
						$matches = $cached_matches;
					} else {
						$matches = $this->pinecone->query_with_domain_filter( $embedding, $top_k );
						if ( ! is_wp_error( $matches ) ) {
							WP_AI_Cache::set_query_results( $embedding, $top_k, $matches, WP_AI_Cache::DEFAULT_TTL );
						}
					}

					if ( ! is_wp_error( $matches ) && ! empty( $matches ) ) {
						// Filter matches by minimum score
						$min_score = (float) $this->core->get_setting( 'search_min_score', 0.5 );
						$filtered_matches = $this->filter_by_score( $matches, $min_score );

						// Apply relevance boosting to re-rank results
						$filtered_matches = $this->apply_relevance_boosting( $filtered_matches, $search_term );

						if ( ! empty( $filtered_matches ) ) {
							// Format results for logging and summary
							$results = $this->format_search_results( $filtered_matches );

							// Generate AI summary from results
							$summary = $this->generate_search_summary( $search_term, $results, $filtered_matches );

							// Store summary as query var for theme access
							if ( ! empty( $summary ) ) {
								set_query_var( 'ai_search_summary', $summary );
								$query->set( 'ai_search_summary', $summary );
							}

							// Extract post IDs from filtered matches
							$post_ids = array();
							foreach ( $filtered_matches as $match ) {
								$post_id = $match['metadata']['post_id'] ?? 0;
								if ( $post_id > 0 ) {
									$post_ids[] = $post_id;
								}
							}

							if ( ! empty( $post_ids ) ) {
								// Modify query to use AI results
								$query->set( 'post__in', $post_ids );
								$query->set( 'orderby', 'post__in' );
								$query->set( 's', '' ); // Clear default search
								$query->set( 'ai_search_active', true );

								// Log the search with formatted results
								$this->log_search_query( $search_term, $results );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Render search shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string Rendered shortcode HTML
	 */
	public function render_shortcode( $atts ) {
		if ( ! $this->is_configured() ) {
			return '<p>The AI search is not configured yet. Please add the required API keys.</p>';
		}

		$atts = shortcode_atts(
			array(
				'placeholder' => $this->core->get_setting( 'search_placeholder', 'Search with AI...' ),
				'button'      => 'Search',
			),
			$atts,
			'ai_search'
		);

		$handle = 'semantic-knowledge-search';
		wp_enqueue_style( $handle );
		wp_enqueue_script( $handle );

		wp_localize_script(
			$handle,
			'wpAiAssistantSearch',
			array(
				'endpoint' => rest_url( self::ROUTE_NAMESPACE . self::ROUTE_SEARCH ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'topK'     => (int) $this->core->get_setting( 'search_top_k', 10 ),
			)
		);

		ob_start();
		?>
		<div class="wp-ai-search" role="search" aria-label="AI-powered site search">
			<form class="wp-ai-search__form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label for="wp-ai-search-input" class="wp-ai-search__label">
					<?php echo esc_html( $atts['placeholder'] ); ?>
					<span aria-label="required">*</span>
				</label>
				<input
					type="search"
					id="wp-ai-search-input"
					class="wp-ai-search__input"
					name="s"
					placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
					required
					aria-required="true"
					aria-describedby="wp-ai-search-input-hint"
					autocomplete="off"
				/>
				<span id="wp-ai-search-input-hint" class="sr-only">
					Search field is required
				</span>
				<button type="submit" class="wp-ai-search__button">
					<?php echo esc_html( $atts['button'] ); ?>
				</button>
			</form>
			<section
				class="wp-ai-search__results"
				style="display: none;"
				role="region"
				aria-label="Search results">
				<h2 class="wp-ai-search__results-title">Search Results</h2>
				<div
					class="wp-ai-search__results-list"
					aria-live="polite"
					aria-atomic="true"
					aria-relevant="additions removals">
				</div>
			</section>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Filter matches by minimum score
	 *
	 * @param array $matches Pinecone matches
	 * @param float $min_score Minimum score threshold
	 * @return array Filtered matches
	 */
	private function filter_by_score( $matches, $min_score ) {
		if ( $min_score <= 0 ) {
			return $matches;
		}

		return array_filter( $matches, function( $match ) use ( $min_score ) {
			$score = $match['score'] ?? 0;
			return $score >= $min_score;
		} );
	}

	/**
	 * Get relevance boosting configuration
	 *
	 * Returns the configuration for relevance boosting, including default values
	 * and settings-based overrides. Can be filtered via semantic_knowledge_search_relevance_config.
	 *
	 * @param string $query User's search query
	 * @return array Configuration array
	 */
	private function get_relevance_config( $query ) {
		// Default configuration with simple algorithmic boosts
		$default_config = array(
			'enabled' => true,
			'url_slug_match' => array(
				'enabled' => true,
				'boost' => 0.15,
				'min_word_length' => 3,
			),
			'title_exact_match' => array(
				'enabled' => true,
				'boost' => 0.12,
			),
			'title_all_words' => array(
				'enabled' => true,
				'boost' => 0.08,
				'min_word_length' => 2,
			),
			'post_type_boosts' => array(
				'page' => 0.05,
			),
			'custom_rules' => array(),
		);

		// Get settings-based overrides
		$settings_enabled = (bool) $this->core->get_setting( 'search_relevance_enabled', true );
		$default_config['enabled'] = $settings_enabled;

		$settings_url_boost = (float) $this->core->get_setting( 'search_url_boost', 0.15 );
		if ( $settings_url_boost !== 0.15 ) {
			$default_config['url_slug_match']['boost'] = $settings_url_boost;
		}

		$settings_title_exact_boost = (float) $this->core->get_setting( 'search_title_exact_boost', 0.12 );
		if ( $settings_title_exact_boost !== 0.12 ) {
			$default_config['title_exact_match']['boost'] = $settings_title_exact_boost;
		}

		$settings_title_words_boost = (float) $this->core->get_setting( 'search_title_words_boost', 0.08 );
		if ( $settings_title_words_boost !== 0.08 ) {
			$default_config['title_all_words']['boost'] = $settings_title_words_boost;
		}

		$settings_page_boost = (float) $this->core->get_setting( 'search_page_boost', 0.05 );
		if ( $settings_page_boost !== 0.05 ) {
			$default_config['post_type_boosts']['page'] = $settings_page_boost;
		}

		/**
		 * Filter the relevance boosting configuration.
		 *
		 * Use this filter to customize relevance scoring algorithms, add custom post type boosts,
		 * or implement custom boosting rules.
		 *
		 * @param array $config Default configuration
		 * @param string $query User's search query
		 * @return array Modified configuration
		 */
		return apply_filters( 'semantic_knowledge_search_relevance_config', $default_config, $query );
	}

	/**
	 * Apply relevance boosting to re-rank search results
	 *
	 * This improves search quality by boosting results that are more likely to be relevant:
	 * - Exact URL/slug matches
	 * - Title keyword matches
	 * - Post type priority
	 * - Custom boosting rules (via filters)
	 *
	 * Content preferences (like WordPress vs Drupal prioritization) should be handled
	 * via the AI system prompt instead of hardcoded logic here.
	 *
	 * @param array $matches Pinecone matches
	 * @param string $query User's search query
	 * @return array Re-ranked matches
	 */
	private function apply_relevance_boosting( $matches, $query ) {
		// Get configuration (includes filters)
		$config = $this->get_relevance_config( $query );

		// Check if boosting is enabled
		if ( ! $config['enabled'] ) {
			return $matches;
		}

		/**
		 * Fires before relevance boosting begins.
		 *
		 * @param array $matches Raw matches before boosting
		 * @param string $query User's search query
		 * @param array $config Boosting configuration
		 */
		do_action( 'semantic_knowledge_search_before_boost', $matches, $query, $config );

		/**
		 * Filter raw matches before boosting calculations.
		 *
		 * @param array $matches Raw Pinecone matches
		 * @param string $query User's search query
		 * @return array Filtered matches
		 */
		$matches = apply_filters( 'semantic_knowledge_search_raw_matches', $matches, $query );

		// Normalize query for comparison
		$query_lower = strtolower( trim( $query ) );
		$query_words = preg_split( '/\s+/', $query_lower );

		foreach ( $matches as $index => $match ) {
			$boost = 0;
			$base_score = $match['score'] ?? 0;

			// Extract metadata
			$url = strtolower( $match['metadata']['url'] ?? '' );
			$title = strtolower( $match['metadata']['title'] ?? '' );
			$post_type = $match['metadata']['post_type'] ?? '';

			// 1. URL slug exact match boost
			if ( $config['url_slug_match']['enabled'] ) {
				$url_boost = $this->calculate_url_boost( $url, $query_words, $config['url_slug_match'] );
				/**
				 * Filter URL slug match boost value.
				 *
				 * @param float $url_boost Calculated URL boost
				 * @param array $match Current match
				 * @param string $query User's search query
				 * @return float Modified boost value
				 */
				$url_boost = apply_filters( 'semantic_knowledge_search_url_boost', $url_boost, $match, $query );
				$boost += $url_boost;
			}

			// 2. Exact title match boost
			if ( $config['title_exact_match']['enabled'] && $title === $query_lower ) {
				$title_exact_boost = $config['title_exact_match']['boost'];
				/**
				 * Filter exact title match boost value.
				 *
				 * @param float $title_exact_boost Configured boost value
				 * @param array $match Current match
				 * @param string $query User's search query
				 * @return float Modified boost value
				 */
				$title_exact_boost = apply_filters( 'semantic_knowledge_search_title_exact_boost', $title_exact_boost, $match, $query );
				$boost += $title_exact_boost;
			}

			// 3. Title contains all query words boost
			if ( $config['title_all_words']['enabled'] ) {
				$title_words_boost = $this->calculate_title_words_boost( $title, $query_words, $config['title_all_words'] );
				/**
				 * Filter all-words title boost value.
				 *
				 * @param float $title_words_boost Calculated boost value
				 * @param array $match Current match
				 * @param string $query User's search query
				 * @return float Modified boost value
				 */
				$title_words_boost = apply_filters( 'semantic_knowledge_search_title_words_boost', $title_words_boost, $match, $query );
				$boost += $title_words_boost;
			}

			// 4. Post type priority boost
			if ( ! empty( $config['post_type_boosts'][ $post_type ] ) ) {
				$post_type_boost = (float) $config['post_type_boosts'][ $post_type ];
				/**
				 * Filter post type boost value.
				 *
				 * @param float $post_type_boost Configured boost value
				 * @param string $post_type Post type
				 * @param array $match Current match
				 * @param string $query User's search query
				 * @return float Modified boost value
				 */
				$post_type_boost = apply_filters( 'semantic_knowledge_search_post_type_boost', $post_type_boost, $post_type, $match, $query );
				$boost += $post_type_boost;
			}

			// 5. Apply custom boosting rules
			if ( ! empty( $config['custom_rules'] ) ) {
				foreach ( $config['custom_rules'] as $rule_name => $rule ) {
					$custom_boost = $this->apply_custom_rule( $rule, $match, $query );
					/**
					 * Filter custom rule boost value.
					 *
					 * Dynamic hook name includes the rule name.
					 *
					 * @param float $custom_boost Calculated boost value
					 * @param array $rule Rule configuration
					 * @param array $match Current match
					 * @param string $query User's search query
					 * @return float Modified boost value
					 */
					$custom_boost = apply_filters( "semantic_knowledge_search_custom_boost_{$rule_name}", $custom_boost, $rule, $match, $query );
					$boost += $custom_boost;
				}
			}

			// Calculate final score
			$final_score = max( 0, min( 1.0, $base_score + $boost ) );

			/**
			 * Filter individual match score after boosting.
			 *
			 * @param float $final_score Calculated final score
			 * @param float $base_score Original score
			 * @param float $boost Total boost applied
			 * @param array $match Current match
			 * @param string $query User's search query
			 * @return float Modified final score
			 */
			$final_score = apply_filters( 'semantic_knowledge_search_match_score', $final_score, $base_score, $boost, $match, $query );

			// Store scores in match
			$matches[ $index ]['score'] = $final_score;
			$matches[ $index ]['_original_score'] = $base_score;
			$matches[ $index ]['_boost'] = $boost;
		}

		// Re-sort by boosted scores
		usort( $matches, function( $a, $b ) {
			$score_a = $a['score'] ?? 0;
			$score_b = $b['score'] ?? 0;
			return $score_b <=> $score_a; // Descending order
		} );

		/**
		 * Filter all matches after boosting and sorting.
		 *
		 * @param array $matches Boosted and sorted matches
		 * @param string $query User's search query
		 * @param array $config Boosting configuration
		 * @return array Modified matches
		 */
		$matches = apply_filters( 'semantic_knowledge_search_boosted_matches', $matches, $query, $config );

		/**
		 * Fires after relevance boosting is complete.
		 *
		 * @param array $matches Boosted and sorted matches
		 * @param string $query User's search query
		 * @param array $config Boosting configuration
		 */
		do_action( 'semantic_knowledge_search_after_boost', $matches, $query, $config );

		return $matches;
	}

	/**
	 * Calculate URL slug matching boost
	 *
	 * Checks if any query words appear in the URL slug and returns appropriate boost.
	 *
	 * @param string $url Lowercase URL
	 * @param array $query_words Query words (lowercase)
	 * @param array $config URL boost configuration
	 * @return float Boost value
	 */
	private function calculate_url_boost( $url, $query_words, $config ) {
		$boost = 0;
		$min_length = $config['min_word_length'] ?? 3;
		$boost_value = $config['boost'] ?? 0.15;

		foreach ( $query_words as $word ) {
			if ( strlen( $word ) > $min_length && strpos( $url, '/' . $word . '/' ) !== false ) {
				$boost += $boost_value;
			}
		}

		return $boost;
	}

	/**
	 * Calculate title all-words boost
	 *
	 * Checks if all query words appear in the title and returns appropriate boost.
	 *
	 * @param string $title Lowercase title
	 * @param array $query_words Query words (lowercase)
	 * @param array $config Title words boost configuration
	 * @return float Boost value
	 */
	private function calculate_title_words_boost( $title, $query_words, $config ) {
		if ( empty( $query_words ) ) {
			return 0;
		}

		$min_length = $config['min_word_length'] ?? 2;
		$boost_value = $config['boost'] ?? 0.08;

		$all_words_in_title = true;
		foreach ( $query_words as $word ) {
			if ( strlen( $word ) > $min_length && strpos( $title, $word ) === false ) {
				$all_words_in_title = false;
				break;
			}
		}

		return $all_words_in_title ? $boost_value : 0;
	}

	/**
	 * Apply custom boosting rule
	 *
	 * Applies a custom rule configuration to determine additional boost.
	 * Supports 'match' pattern for URL/title matching.
	 *
	 * @param array $rule Rule configuration
	 * @param array $match Current match
	 * @param string $query User's search query
	 * @return float Boost value
	 */
	private function apply_custom_rule( $rule, $match, $query ) {
		if ( empty( $rule['boost'] ) ) {
			return 0;
		}

		$boost = 0;

		// Check for URL pattern match
		if ( ! empty( $rule['match'] ) ) {
			$url = strtolower( $match['metadata']['url'] ?? '' );
			$pattern = strtolower( $rule['match'] );

			if ( strpos( $url, $pattern ) !== false ) {
				$boost = (float) $rule['boost'];
			}
		}

		return $boost;
	}

	/**
	 * Format search results for response
	 *
	 * @param array $matches Pinecone matches
	 * @return array Formatted results
	 */
	private function format_search_results( $matches ) {
		$results = array();
		$seen_post_ids = array();

		foreach ( $matches as $match ) {
			$post_id = $match['metadata']['post_id'] ?? 0;
			$title = $match['metadata']['title'] ?? 'Unknown';
			$url = $match['metadata']['url'] ?? '';
			$excerpt = $match['metadata']['chunk'] ?? '';
			$score = $match['score'] ?? 0;

			// Deduplicate by post ID
			if ( $post_id > 0 && ! in_array( $post_id, $seen_post_ids, true ) ) {
				$result = array(
					'post_id' => $post_id,
					'title'   => $title,
					'url'     => $url,
					'excerpt' => wp_trim_words( $excerpt, 30 ),
					'score'   => $score,
				);

				/**
				 * Filter individual search result formatting.
				 *
				 * @param array $result Formatted result
				 * @param array $match Raw Pinecone match
				 * @return array Modified result
				 */
				$result = apply_filters( 'semantic_knowledge_search_result_format', $result, $match );

				$results[] = $result;
				$seen_post_ids[] = $post_id;
			}
		}

		return $results;
	}

	/**
	 * Generate AI summary from search results
	 *
	 * @param string $query User's search query
	 * @param array $results Formatted search results
	 * @param array $matches Raw Pinecone matches with full chunks
	 * @return string|null AI-generated summary or null if disabled/error
	 */
	private function generate_search_summary( $query, $results, $matches ) {
		$enabled = (bool) $this->core->get_setting( 'search_enable_summary', true );

		/**
		 * Filter whether AI summary generation is enabled.
		 *
		 * @param bool $enabled Whether summary is enabled
		 * @param string $query Search query text
		 * @param array $results Formatted results
		 * @return bool Modified enabled status
		 */
		$enabled = apply_filters( 'semantic_knowledge_search_summary_enabled', $enabled, $query, $results );

		// Check if AI summary is enabled
		if ( ! $enabled ) {
			return null;
		}

		// Need at least one result to generate a summary
		if ( empty( $results ) ) {
			return null;
		}

		// Build context from top 5 matches (use full chunks, not trimmed excerpts)
		$context_chunks = array();
		$available_urls = array();
		$count = 0;

		foreach ( $matches as $match ) {
			if ( $count >= 5 ) {
				break;
			}

			$chunk = $match['metadata']['chunk'] ?? '';
			$title = $match['metadata']['title'] ?? '';
			$url = $match['metadata']['url'] ?? '';

			if ( ! empty( $chunk ) ) {
				// Include URL in context for AI reference
				$context_chunks[] = "From \"$title\" ($url):\n$chunk";

				// Build list of available URLs
				if ( ! empty( $url ) && ! empty( $title ) ) {
					$available_urls[] = array(
						'title' => $title,
						'url'   => $url,
					);
				}

				$count++;
			}
		}

		if ( empty( $context_chunks ) ) {
			return null;
		}

		$context = implode( "\n\n", $context_chunks );

		// Add available URLs section for AI to use in inline links
		if ( ! empty( $available_urls ) ) {
			$context .= "\n\n---\nAVAILABLE PAGES TO LINK:\n";
			foreach ( $available_urls as $link ) {
				$context .= "- \"{$link['title']}\": {$link['url']}\n";
			}
			$context .= "\nUse these URLs when creating inline links in your response.";
		}

		/**
		 * Filter the context passed to AI for summary generation.
		 *
		 * @param string $context Context string
		 * @param string $query Search query text
		 * @param array $results Formatted results
		 * @param array $matches Raw Pinecone matches
		 * @return string Modified context
		 */
		$context = apply_filters( 'semantic_knowledge_search_summary_context', $context, $query, $results, $matches );

		// Get search-specific system prompt
		$system_prompt = $this->core->get_setting(
			'search_system_prompt',
			$this->get_default_search_system_prompt()
		);

		/**
		 * Filter the system prompt for search summary generation.
		 *
		 * @param string $system_prompt System prompt
		 * @param string $query Search query text
		 * @param array $results Formatted results
		 * @return string Modified system prompt
		 */
		$system_prompt = apply_filters( 'semantic_knowledge_search_summary_system_prompt', $system_prompt, $query, $results );

		// Generate summary using chat completion
		$summary = $this->openai->chat_completion(
			$query,
			$context,
			array(
				'system_prompt' => $system_prompt,
				'temperature'   => 0.3, // Lower temperature for more focused summaries
			)
		);

		// Return null on error instead of error object
		if ( is_wp_error( $summary ) ) {
			return null;
		}

		return $summary;
	}

	/**
	 * Get fallback system prompt for search summaries if none is set
	 *
	 * This should rarely be used as the default settings include a prompt.
	 *
	 * @return string
	 */
	private function get_default_search_system_prompt() {
		return "You are an AI search assistant. Your role is to help users quickly understand what the search results say and decide which results are worth clicking.\n\nYou do NOT invent answers. You summarize, synthesize, and point to relevant results.\n\nOUTPUT FORMAT (REQUIRED)\n- Format ALL responses using HTML tags:\n  - Use <p> for paragraphs\n  - Use <strong> for emphasis\n  - Use <ul> and <li> for bullet lists\n  - Use <ol> and <li> for numbered lists\n  - Use <a href=\"...\" rel=\"noopener\" target=\"_blank\">Descriptive title</a> for links\n  - Use <br> for line breaks where helpful\n- Do NOT use Markdown\n- Do NOT wrap the entire response in a single <p>\n- Do NOT use headings (<h1>-<h6>)\n\nPRIMARY GOAL\nHelp the user:\n1) Understand the key takeaway from the search results, and\n2) Discover related or supporting results they may want to explore next.\n\nGROUNDING & ACCURACY (VERY IMPORTANT)\n- Use ONLY the provided search results as your source of truth\n- Do NOT add information that is not explicitly supported by the results\n- Do NOT infer intent, outcomes, or conclusions beyond what is stated\n- If the results do not clearly answer the question:\n  - Say so plainly\n  - Explain what the results do cover instead\n\nRESPONSE STRUCTURE (REQUIRED)\n1) <strong>Direct answer or summary</strong>\n   - 1–2 sentences that clearly state what the search results collectively show\n   - If there is no single answer, summarize the common themes or differences\n\n2) <strong>Key points from the results</strong>\n   - Use a bullet list to highlight specific facts, steps, definitions, or findings\n   - Each bullet should reflect something concrete from the results\n\n3) <strong>Related or useful results</strong>\n   - Provide 2–4 links the user may want to explore next\n   - Briefly explain what each link covers and why it's relevant\n   - Always link using the URL from the search results\n\nLINK USAGE RULES\n- Include links inline where they add clarity or credibility\n- Use descriptive link text that reflects the page's content\n- Do NOT repeat the same link multiple times unless necessary\n- Do NOT invent or assume URLs\n\nCONTENT GUIDELINES\n- Keep the response concise and scannable\n- Aim for 100–180 words total unless the question clearly requires more\n- Avoid filler phrases like:\n  - \"Based on the search results…\"\n  - \"This article discusses…\"\n- Focus on clarity over completeness\n\nTONE\n- Neutral, helpful, and informative\n- Confident but not authoritative\n- No marketing language, hype, or opinionated framing\n\nCONTENT PREFERENCES\nThis section controls how search results are prioritized, summarized, and presented. These preferences are set by an administrator and must be followed for every response.\n\nYou MUST apply these preferences when analyzing search results. Do not explain or restate them in your response.\n\nPreferences may include (but are not limited to):\n\n- Content types to prioritize (for example: guides, documentation, blog posts, case studies, FAQs)\n- Content types to de-emphasize or exclude (for example: marketing pages, announcements, outdated posts)\n- Preferred freshness (for example: newest results first, evergreen content preferred, or no preference)\n- Preferred depth:\n  - High-level summaries\n  - Step-by-step explanations\n  - Technical or advanced detail\n- Preferred tone:\n  - Neutral and factual\n  - Conversational\n  - Instructional\n- Preferred audiences (for example: beginners, practitioners, decision-makers)\n\nIf preferences conflict:\n- Prioritize clarity and relevance to the user's query\n- Prefer explicit administrator preferences over inferred intent\n\nIf preferences limit available results:\n- Work only with the remaining eligible content\n- If no strong results remain, say so clearly and summarize what is available instead\n\nCLOSING BEHAVIOR\nEnd with a light, optional nudge that helps the user continue, such as:\n- \"If you want more detail, the links above go deeper.\"\n- \"Let me know if you'd like help comparing these results.\"";
	}

	/**
	 * Log search query to database
	 *
	 * @param string $query Search query
	 * @param array $results Search results
	 * @param int $response_time Response time in milliseconds (optional)
	 */
	private function log_search_query( $query, $results, $response_time = null ) {
		// Use optimized database table for logs
		WP_AI_Database::log_search( $query, $results, $response_time );
	}

	/**
	 * Check if search is configured with required API keys
	 *
	 * @return bool
	 */
	private function is_configured() {
		return $this->core->is_configured();
	}

	// ============================================================================
	// Public Helper Methods for Themes
	// ============================================================================

	/**
	 * Get AI-generated search summary for current query
	 *
	 * Use this in your theme's search.php template to display the AI summary:
	 *
	 * if ( function_exists( 'semantic_knowledge_get_search_summary' ) ) {
	 *     $summary = wp_ai_get_search_summary();
	 *     if ( $summary ) {
	 *         echo '<div class="ai-search-summary">' . wp_kses_post( $summary ) . '</div>';
	 *     }
	 * }
	 *
	 * @param WP_Query|null $query Optional query object. Uses global $wp_query if not provided.
	 * @return string|null AI summary or null if not available
	 */
	public static function get_search_summary( $query = null ) {
		if ( null === $query ) {
			global $wp_query;
			$query = $wp_query;
		}

		if ( ! $query || ! $query->is_search() ) {
			return null;
		}

		// Try to get from query var first
		$summary = get_query_var( 'ai_search_summary', '' );

		// Fallback to query object property
		if ( empty( $summary ) ) {
			$summary = $query->get( 'ai_search_summary' );
		}

		// Allow themes to filter the summary HTML
		if ( ! empty( $summary ) ) {
			$summary = apply_filters( 'semantic_knowledge_search_summary', $summary, $query );
		}

		return $summary;
	}

	/**
	 * Check if current query is an AI-powered search
	 *
	 * @param WP_Query|null $query Optional query object
	 * @return bool
	 */
	public static function is_ai_search( $query = null ) {
		if ( null === $query ) {
			global $wp_query;
			$query = $wp_query;
		}

		if ( ! $query || ! $query->is_search() ) {
			return false;
		}

		return (bool) $query->get( 'ai_search_active' );
	}

	// ============================================================================
	// Admin Interface for Search Logs
	// ============================================================================

	/**
	 * Set custom columns for search log list table
	 *
	 * @param array $columns Default columns
	 * @return array Modified columns
	 */
	public function set_search_log_columns( $columns ) {
		return array(
			'cb'           => $columns['cb'],
			'title'        => 'Search Query',
			'date'         => 'Date',
			'result_count' => 'Results Found',
		);
	}

	/**
	 * Render custom column content
	 *
	 * @param string $column Column name
	 * @param int $post_id Post ID
	 */
	public function render_search_log_column( $column, $post_id ) {
		if ( 'result_count' === $column ) {
			$count = get_post_meta( $post_id, '_search_count', true );
			echo esc_html( $count ? $count : '0' );
		}
	}

	/**
	 * Add meta boxes for search log details
	 */
	public function add_search_log_meta_boxes() {
		add_meta_box(
			'semantic_knowledge_search_log_details',
			'Search Results',
			array( $this, 'render_search_log_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render search log meta box content
	 *
	 * @param WP_Post $post Current post object
	 */
	public function render_search_log_meta_box( $post ) {
		$results = get_post_meta( $post->ID, '_search_results', true );

		?>
		<div style="margin: 15px 0;">
			<h3>Search Query</h3>
			<p><strong><?php echo esc_html( $post->post_title ); ?></strong></p>

			<?php if ( $results && is_array( $results ) && count( $results ) > 0 ) : ?>
				<h3>Results (<?php echo count( $results ); ?>)</h3>
				<table class="widefat" style="margin-top: 10px;">
					<thead>
						<tr>
							<th>Title</th>
							<th>URL</th>
							<th>Score</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $results as $result ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $result['title'] ); ?></strong></td>
								<td>
									<a href="<?php echo esc_url( $result['url'] ); ?>" target="_blank" rel="noopener">
										<?php echo esc_html( $result['url'] ); ?>
									</a>
								</td>
								<td><?php echo esc_html( number_format( $result['score'], 3 ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><em>No results found.</em></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Hide default editor for search log post type
	 */
	public function hide_search_log_editor() {
		global $post_type;
		if ( self::POST_TYPE === $post_type ) {
			?>
			<style>
				#postdivrich { display: none; }
				#edit-slug-box { display: none; }
			</style>
			<?php
		}
	}
}
