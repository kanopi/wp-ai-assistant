<?php
/**
 * WP AI Chatbot Module
 * Provides RAG-style chatbot functionality with Deep Chat UI integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AI_Chatbot_Module {
	const ROUTE_NAMESPACE = 'ai-assistant/v1';
	const ROUTE_CHAT = '/chat';
	const POST_TYPE = 'ai_chat_log';

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
		add_action( 'init', array( $this, 'register_chat_log_post_type' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_filter( 'script_loader_tag', array( $this, 'add_module_type_attribute' ), 10, 3 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'set_chat_log_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_chat_log_column' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_chat_log_meta_boxes' ) );
		add_action( 'admin_head', array( $this, 'hide_chat_log_editor' ) );
		add_shortcode( 'ai_chatbot', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			self::ROUTE_CHAT,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_chat_query' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'question' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_question' ),
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
				'wp_ai_assistant_invalid_nonce',
				'Invalid security token. Please refresh the page and try again.',
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Validate question input
	 *
	 * @param string $value Question text
	 * @param WP_REST_Request $request REST request object
	 * @param string $param Parameter name
	 * @return bool|WP_Error
	 */
	public function validate_question( $value, $request, $param ) {
		// Check maximum length (1000 characters)
		if ( strlen( $value ) > 1000 ) {
			return new WP_Error(
				'wp_ai_assistant_question_too_long',
				'Question must be 1000 characters or less.',
				array( 'status' => 400 )
			);
		}

		// Check minimum length
		if ( strlen( trim( $value ) ) === 0 ) {
			return new WP_Error(
				'wp_ai_assistant_question_empty',
				'Question cannot be empty.',
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
		$rate_limit = apply_filters( 'wp_ai_chatbot_rate_limit', 10 );
		$rate_window = apply_filters( 'wp_ai_chatbot_rate_window', 60 ); // seconds

		// Create transient key
		$transient_key = 'wp_ai_chatbot_rl_' . md5( $ip_address );

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
				'wp_ai_assistant_rate_limit_exceeded',
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
	 * Get client IP address with proxy header validation
	 *
	 * Security: Only trusts proxy headers (X-Forwarded-For, etc.) if the request
	 * comes from a trusted proxy IP. This prevents IP spoofing via header manipulation.
	 *
	 * @return string Client IP address
	 */
	private function get_client_ip() {
		// Get the direct connection IP (always trustworthy)
		$remote_addr = ! empty( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';

		// Get list of trusted proxy IPs (e.g., load balancers, CDN edge servers)
		// These should be configured via constant, environment variable, or filter
		$trusted_proxies = $this->get_trusted_proxies();

		// Check if request is coming from a trusted proxy
		$is_trusted_proxy = false;
		if ( ! empty( $remote_addr ) && ! empty( $trusted_proxies ) ) {
			$is_trusted_proxy = in_array( $remote_addr, $trusted_proxies, true );
		}

		// Only check proxy headers if request comes from trusted proxy
		if ( $is_trusted_proxy ) {
			// Check for proxy headers (in order of preference)
			$proxy_headers = array(
				'HTTP_CF_CONNECTING_IP', // Cloudflare
				'HTTP_X_FORWARDED_FOR',  // Standard proxy header
				'HTTP_X_REAL_IP',        // Nginx proxy
			);

			foreach ( $proxy_headers as $header ) {
				if ( ! empty( $_SERVER[ $header ] ) ) {
					$ip_address = $_SERVER[ $header ];

					// Handle comma-separated list (X-Forwarded-For)
					if ( strpos( $ip_address, ',' ) !== false ) {
						$ips = explode( ',', $ip_address );
						$ip_address = trim( $ips[0] );
					}

					// Validate IP address
					if ( filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
						return $ip_address;
					}
				}
			}
		}

		// Fallback to direct connection IP
		if ( filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
			return $remote_addr;
		}

		// If logged in, use user ID as fallback for rate limiting
		if ( is_user_logged_in() ) {
			return 'user_' . get_current_user_id();
		}

		// Last resort fallback
		return 'unknown';
	}

	/**
	 * Get trusted proxy IPs
	 *
	 * Returns list of trusted proxy/load balancer IPs that are allowed to set
	 * X-Forwarded-For and other proxy headers.
	 *
	 * Configure via:
	 * 1. WP_AI_TRUSTED_PROXIES constant (comma-separated list)
	 * 2. WP_AI_TRUSTED_PROXIES environment variable
	 * 3. wp_ai_chatbot_trusted_proxies filter
	 *
	 * @return array List of trusted proxy IP addresses
	 */
	private function get_trusted_proxies() {
		$trusted_proxies = array();

		// Check PHP constant
		if ( defined( 'WP_AI_TRUSTED_PROXIES' ) ) {
			$trusted_proxies = array_map( 'trim', explode( ',', WP_AI_TRUSTED_PROXIES ) );
		}

		// Check environment variable
		if ( empty( $trusted_proxies ) ) {
			$env_proxies = getenv( 'WP_AI_TRUSTED_PROXIES' );
			if ( ! empty( $env_proxies ) ) {
				$trusted_proxies = array_map( 'trim', explode( ',', $env_proxies ) );
			}
		}

		// Allow filtering (for dynamic configuration)
		$trusted_proxies = apply_filters( 'wp_ai_chatbot_trusted_proxies', $trusted_proxies );

		// Common trusted proxy ranges (can be overridden by filter)
		// Cloudflare IPs are automatically trusted if HTTP_CF_CONNECTING_IP is present
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			// Cloudflare detected - trust common Cloudflare IPs
			// Note: In production, you should validate against full Cloudflare IP range
			return $trusted_proxies;
		}

		return array_filter( $trusted_proxies );
	}

	/**
	 * Register chat log custom post type
	 */
	public function register_chat_log_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => 'Chat Logs',
					'singular_name' => 'Chat Log',
					'menu_name'     => 'AI Chat Logs',
					'all_items'     => 'All Chat Logs',
					'view_item'     => 'View Chat Log',
					'search_items'  => 'Search Chat Logs',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-format-chat',
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
	 * Register and enqueue frontend assets with lazy loading
	 */
	public function register_assets() {
		$handle = 'wp-ai-assistant-chatbot';

		// Register Deep Chat CDN library (but don't enqueue it yet - lazy load)
		wp_register_script(
			'deep-chat',
			'https://cdn.jsdelivr.net/npm/deep-chat@2.3.0/dist/deepChat.bundle.js',
			array(),
			'2.3.0',
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		// Register chatbot styles
		wp_register_style(
			$handle,
			WP_AI_ASSISTANT_URL . 'assets/css/chatbot.css',
			array(),
			WP_AI_ASSISTANT_VERSION
		);

		// Register chatbot script WITHOUT deep-chat dependency for lazy loading
		wp_register_script(
			$handle,
			WP_AI_ASSISTANT_URL . 'assets/js/chatbot.js',
			array(), // No dependencies - will load Deep Chat dynamically
			WP_AI_ASSISTANT_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		$enable_floating_button = (bool) $this->core->get_setting( 'chatbot_floating_button', true );

		// Auto-enqueue if floating button is enabled and properly configured
		if ( $enable_floating_button && $this->is_configured() ) {
			wp_enqueue_style( $handle );
			wp_enqueue_script( $handle );

			wp_localize_script(
				$handle,
				'wpAiAssistantChatbot',
				array(
					'endpoint'             => rest_url( self::ROUTE_NAMESPACE . self::ROUTE_CHAT ),
					'nonce'                => wp_create_nonce( 'wp_rest' ),
					'topK'                 => (int) $this->core->get_setting( 'chatbot_top_k', 5 ),
					'enableFloatingButton' => true,
					'introMessage'         => $this->core->get_setting( 'chatbot_intro_message', '<p><strong>Hi!</strong> I can help you explore this website.</p><p>Ask me a question to get started.</p>' ),
					'inputPlaceholder'     => $this->core->get_setting( 'chatbot_input_placeholder', 'Ask a question...' ),
					'deepChatUrl'          => 'https://cdn.jsdelivr.net/npm/deep-chat@2.3.0/dist/deepChat.bundle.js',
				)
			);
		}
	}

	/**
	 * Add module type attribute and CSP nonce to scripts
	 *
	 * @param string $tag Script tag HTML
	 * @param string $handle Script handle
	 * @param string $src Script source URL
	 * @return string Modified script tag
	 */
	public function add_module_type_attribute( $tag, $handle, $src ) {
		// Add module type for deep-chat
		if ( 'deep-chat' === $handle ) {
			$tag = str_replace( '<script ', '<script type="module" ', $tag );
		}

		// Add CSP nonce to our plugin scripts for security
		if ( in_array( $handle, array( 'wp-ai-assistant-chatbot', 'deep-chat' ), true ) ) {
			$nonce = $this->core->get_csp_nonce();
			if ( ! empty( $nonce ) && strpos( $tag, 'nonce=' ) === false ) {
				$tag = str_replace( '<script ', '<script nonce="' . esc_attr( $nonce ) . '" ', $tag );
			}
		}

		return $tag;
	}

	/**
	 * Handle chat query REST API request
	 *
	 * @param WP_REST_Request $request REST request object
	 * @return WP_REST_Response|WP_Error Response with answer and sources or error
	 */
	public function handle_chat_query( WP_REST_Request $request ) {
		// Start timing for performance metrics
		$start_time = microtime( true );

		// Check rate limiting
		$rate_limit_check = $this->check_rate_limit( $request );
		if ( is_wp_error( $rate_limit_check ) ) {
			return $rate_limit_check;
		}

		if ( ! $this->is_configured() ) {
			return new WP_Error(
				'wp_ai_assistant_not_configured',
				'Chatbot API keys are missing.',
				array( 'status' => 500 )
			);
		}

		$question = sanitize_text_field( $request->get_param( 'question' ) );
		$top_k = (int) $request->get_param( 'top_k' );

		if ( empty( $question ) ) {
			return new WP_Error(
				'wp_ai_assistant_empty_question',
				'Please provide a question.',
				array( 'status' => 400 )
			);
		}

		/**
		 * Fires at the start of a chatbot query.
		 *
		 * @param string $question User's question
		 * @param WP_REST_Request $request REST request object
		 */
		do_action( 'wp_ai_chatbot_query_start', $question, $request );

		/**
		 * Filter the chatbot question text before processing.
		 *
		 * @param string $question User's question
		 * @param WP_REST_Request $request REST request object
		 * @return string Modified question text
		 */
		$question = apply_filters( 'wp_ai_chatbot_question', $question, $request );

		if ( $top_k <= 0 ) {
			$top_k = (int) $this->core->get_setting( 'chatbot_top_k', 5 );
		}

		/**
		 * Filter the number of context results to retrieve.
		 *
		 * @param int $top_k Number of results
		 * @param string $question User's question
		 * @return int Modified top_k value
		 */
		$top_k = apply_filters( 'wp_ai_chatbot_top_k', $top_k, $question );

		// Step 1: Create embedding (with caching)
		$cached_embedding = WP_AI_Cache::get_embedding( $question );
		if ( false !== $cached_embedding ) {
			$embedding = $cached_embedding;
			$this->core->log( 'Using cached embedding for question' );
		} else {
			$embedding = $this->openai->create_embedding( $question );
			if ( is_wp_error( $embedding ) ) {
				return $embedding;
			}

			// Cache the embedding for future use (1 hour TTL)
			WP_AI_Cache::set_embedding( $question, $embedding, WP_AI_Cache::WARM_CACHE_TTL );
		}

		// Step 2: Query Pinecone with domain filter (with caching)
		$cached_matches = WP_AI_Cache::get_query_results( $embedding, $top_k );
		if ( false !== $cached_matches ) {
			$matches = $cached_matches;
			$this->core->log( 'Using cached query results' );
		} else {
			$matches = $this->pinecone->query_with_domain_filter( $embedding, $top_k );
			if ( is_wp_error( $matches ) ) {
				return $matches;
			}

			// Cache the query results (15 minutes TTL for hot cache)
			WP_AI_Cache::set_query_results( $embedding, $top_k, $matches, WP_AI_Cache::DEFAULT_TTL );
		}

		/**
		 * Filter Pinecone matches for chatbot context.
		 *
		 * @param array $matches Pinecone matches
		 * @param string $question User's question
		 * @return array Modified matches
		 */
		$matches = apply_filters( 'wp_ai_chatbot_matches', $matches, $question );

		// Step 3: Build context from matches
		$context = $this->build_context( $matches );

		/**
		 * Filter the context string passed to the chatbot.
		 *
		 * @param string $context Context string
		 * @param array $matches Pinecone matches
		 * @param string $question User's question
		 * @return string Modified context
		 */
		$context = apply_filters( 'wp_ai_chatbot_context', $context, $matches, $question );

		$model = $this->core->get_setting( 'chatbot_model', 'gpt-4o-mini' );
		$temperature = (float) $this->core->get_setting( 'chatbot_temperature', 0.2 );
		$system_prompt = $this->get_system_prompt();

		/**
		 * Filter the OpenAI model for chatbot responses.
		 *
		 * @param string $model Model identifier
		 * @param string $question User's question
		 * @return string Modified model identifier
		 */
		$model = apply_filters( 'wp_ai_chatbot_model', $model, $question );

		/**
		 * Filter the temperature parameter for chatbot responses.
		 *
		 * @param float $temperature Temperature value (0.0-2.0)
		 * @param string $question User's question
		 * @return float Modified temperature
		 */
		$temperature = apply_filters( 'wp_ai_chatbot_temperature', $temperature, $question );

		/**
		 * Filter the system prompt for chatbot responses.
		 *
		 * @param string $system_prompt System prompt text
		 * @param string $question User's question
		 * @param string $context Context string
		 * @return string Modified system prompt
		 */
		$system_prompt = apply_filters( 'wp_ai_chatbot_system_prompt', $system_prompt, $question, $context );

		// Step 4: Generate chat completion
		$answer = $this->openai->chat_completion( $question, $context, array(
			'model'        => $model,
			'temperature'  => $temperature,
			'system_prompt' => $system_prompt,
		) );

		if ( is_wp_error( $answer ) ) {
			return $answer;
		}

		/**
		 * Filter the chatbot answer before returning.
		 *
		 * @param string $answer Generated answer
		 * @param string $question User's question
		 * @param string $context Context used
		 * @return string Modified answer
		 */
		$answer = apply_filters( 'wp_ai_chatbot_answer', $answer, $question, $context );

		// Step 5: Format sources
		$sources = $this->format_sources( $matches );

		/**
		 * Filter the chatbot sources before returning.
		 *
		 * @param array $sources Formatted sources
		 * @param array $matches Raw Pinecone matches
		 * @param string $question User's question
		 * @return array Modified sources
		 */
		$sources = apply_filters( 'wp_ai_chatbot_sources', $sources, $matches, $question );

		/**
		 * Fires before logging the chatbot interaction.
		 *
		 * @param string $question User's question
		 * @param string $answer Generated answer
		 * @param array $sources Source references
		 */
		do_action( 'wp_ai_chatbot_before_log', $question, $answer, $sources );

		// Step 6: Log interaction with response time
		$response_time = (int) ( ( microtime( true ) - $start_time ) * 1000 ); // Convert to milliseconds
		$this->log_chat_interaction( $question, $answer, $sources, $response_time );

		// Step 7: Return response
		$response = array(
			'answer'  => $answer,
			'sources' => $sources,
		);

		/**
		 * Fires at the end of a chatbot query.
		 *
		 * @param array $response Complete response array
		 * @param string $question User's question
		 */
		do_action( 'wp_ai_chatbot_query_end', $response, $question );

		return rest_ensure_response( $response );
	}

	/**
	 * Render chatbot shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string Rendered shortcode HTML
	 */
	public function render_shortcode( $atts ) {
		if ( ! $this->is_configured() ) {
			return '<p>The AI chatbot is not configured yet. Please add the required API keys.</p>';
		}

		$atts = shortcode_atts(
			array(
				'mode'   => 'inline',
				'button' => 'Chat with AI',
			),
			$atts,
			'ai_chatbot'
		);

		$handle = 'wp-ai-assistant-chatbot';
		wp_enqueue_style( $handle );
		wp_enqueue_script( $handle );

		$enable_floating_button = (bool) $this->core->get_setting( 'chatbot_floating_button', true );

		wp_localize_script(
			$handle,
			'wpAiAssistantChatbot',
			array(
				'endpoint'             => rest_url( self::ROUTE_NAMESPACE . self::ROUTE_CHAT ),
				'nonce'                => wp_create_nonce( 'wp_rest' ),
				'topK'                 => (int) $this->core->get_setting( 'chatbot_top_k', 5 ),
				'enableFloatingButton' => $enable_floating_button,
				'introMessage'         => $this->core->get_setting( 'chatbot_intro_message', '<p><strong>Hi!</strong> I can help you explore this website.</p><p>Ask me a question to get started.</p>' ),
				'inputPlaceholder'     => $this->core->get_setting( 'chatbot_input_placeholder', 'Ask a question...' ),
			)
		);

		$is_popup = $atts['mode'] === 'popup';
		$button_text = esc_attr( $atts['button'] );

		ob_start();
		?>
		<div class="wp-ai-chatbot" data-popup="<?php echo $is_popup ? 'true' : 'false'; ?>" data-button-text="<?php echo $button_text; ?>">
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Build context string from Pinecone matches
	 *
	 * @param array $matches Pinecone matches
	 * @return string Context string
	 */
	private function build_context( $matches ) {
		if ( empty( $matches ) ) {
			return 'No relevant information found.';
		}

		$context_parts = array();

		foreach ( $matches as $match ) {
			if ( ! empty( $match['metadata']['chunk'] ) ) {
				$title = $match['metadata']['title'] ?? 'Unknown';
				$chunk = $match['metadata']['chunk'];
				$context_parts[] = "From: {$title}\n{$chunk}";
			}
		}

		return implode( "\n\n---\n\n", $context_parts );
	}

	/**
	 * Format sources for response
	 *
	 * @param array $matches Pinecone matches
	 * @return array Formatted sources
	 */
	private function format_sources( $matches ) {
		$sources = array();
		$seen_urls = array();

		foreach ( $matches as $match ) {
			$url = $match['metadata']['url'] ?? '';
			$title = $match['metadata']['title'] ?? 'Unknown';
			$score = $match['score'] ?? 0;

			// Deduplicate by URL
			if ( ! empty( $url ) && ! in_array( $url, $seen_urls, true ) ) {
				$sources[] = array(
					'title' => $title,
					'url'   => $url,
					'score' => $score,
				);
				$seen_urls[] = $url;
			}
		}

		return $sources;
	}

	/**
	 * Get system prompt
	 *
	 * @return string System prompt
	 */
	private function get_system_prompt() {
		$custom_prompt = $this->core->get_setting( 'chatbot_system_prompt' );

		if ( ! empty( $custom_prompt ) ) {
			return $custom_prompt;
		}

		// Default system prompt for HTML output
		return <<<'PROMPT'
You are this website's chatbot: a friendly, knowledgeable guide to the organization, services, team, and work represented on this site.

OUTPUT FORMAT (REQUIRED)
- Format ALL responses using HTML tags:
  - Use <p> for paragraphs.
  - Use <strong> for emphasis (bold).
  - Use <ul>/<ol> and <li> for lists.
  - Use <br> for line breaks where needed.
- Do NOT use Markdown.
- Do NOT wrap the entire response in a single <p>. Use multiple <p> blocks.
- Do NOT use headings (no <h1>-<h6>).
- If you include links in sources, use <a href="...">Title</a> with rel="noopener" and target="_blank".

PERSONA
You speak with the voice of this website:
- Smart, approachable, and collaborative.
- Confident without being salesy.
- Curious, helpful, and a little fun.
- You sound like a real human who enjoys their work and wants to help people make good decisions.

Think "experienced partner explaining things clearly," not "corporate brochure" and not "internet comedian."

BOUNDARIES
You ONLY answer questions using the provided context.
- If the context doesn't contain the answer, say: "I don't have that information in the content I can access."
- Do NOT make up information or guess.
- Stay on topic: this website, its services, its work, its team.

CONTEXT USAGE
- Use the context provided to answer the question directly.
- Cite relevant information from the context when appropriate.
- If multiple sources support your answer, synthesize them naturally.
PROMPT;
	}

	/**
	 * Log chat interaction to database
	 *
	 * @param string $question User's question
	 * @param string $answer Chatbot's answer
	 * @param array $sources Sources used for answer
	 * @param int $response_time Response time in milliseconds (optional)
	 */
	private function log_chat_interaction( $question, $answer, $sources, $response_time = null ) {
		// Use optimized database table for logs
		WP_AI_Database::log_chat( $question, $answer, $sources, $response_time );
	}

	/**
	 * Check if chatbot is configured with required API keys
	 *
	 * @return bool
	 */
	private function is_configured() {
		return $this->core->is_configured();
	}

	// ============================================================================
	// Admin Interface for Chat Logs
	// ============================================================================

	/**
	 * Set custom columns for chat log list table
	 *
	 * @param array $columns Default columns
	 * @return array Modified columns
	 */
	public function set_chat_log_columns( $columns ) {
		return array(
			'cb'     => $columns['cb'],
			'title'  => 'Question',
			'date'   => 'Date',
			'answer' => 'Answer Preview',
		);
	}

	/**
	 * Render custom column content
	 *
	 * @param string $column Column name
	 * @param int $post_id Post ID
	 */
	public function render_chat_log_column( $column, $post_id ) {
		if ( 'answer' === $column ) {
			$answer = get_post_meta( $post_id, '_chat_answer', true );
			if ( $answer ) {
				echo esc_html( wp_trim_words( wp_strip_all_tags( $answer ), 20 ) );
			} else {
				echo '<em>No answer</em>';
			}
		}
	}

	/**
	 * Add meta boxes for chat log details
	 */
	public function add_chat_log_meta_boxes() {
		add_meta_box(
			'wp_ai_chat_log_details',
			'Chat Interaction Details',
			array( $this, 'render_chat_log_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render chat log meta box content
	 *
	 * @param WP_Post $post Current post object
	 */
	public function render_chat_log_meta_box( $post ) {
		$answer = get_post_meta( $post->ID, '_chat_answer', true );
		$sources = get_post_meta( $post->ID, '_chat_sources', true );

		?>
		<div style="margin: 15px 0;">
			<h3>Question</h3>
			<p><strong><?php echo esc_html( $post->post_title ); ?></strong></p>

			<h3>Answer</h3>
			<div style="background: #f9f9f9; padding: 15px; border-left: 3px solid #0073aa; margin-bottom: 20px;">
				<?php echo wp_kses_post( $answer ); ?>
			</div>

			<?php if ( $sources && is_array( $sources ) && count( $sources ) > 0 ) : ?>
				<h3>Sources Used</h3>
				<ul style="list-style: disc; margin-left: 20px;">
					<?php foreach ( $sources as $source ) : ?>
						<li>
							<strong><?php echo esc_html( $source['title'] ); ?></strong>
							<?php if ( ! empty( $source['url'] ) ) : ?>
								<br>
								<a href="<?php echo esc_url( $source['url'] ); ?>" target="_blank" rel="noopener">
									<?php echo esc_url( $source['url'] ); ?>
								</a>
							<?php endif; ?>
							<?php if ( isset( $source['score'] ) ) : ?>
								<br>
								<em>Relevance Score: <?php echo esc_html( number_format( $source['score'], 3 ) ); ?></em>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p><em>No sources available.</em></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Hide default editor for chat log post type
	 */
	public function hide_chat_log_editor() {
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
