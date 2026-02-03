<?php
/**
 * Core functionality for WP AI Assistant
 * Handles settings management, configuration validation, and domain detection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AI_Core {
	const OPTION_KEY = 'wp_ai_assistant_settings';
	const CACHE_KEY = 'wp_ai_assistant_settings_cache';
	const CACHE_GROUP = 'wp_ai_assistant';
	const CACHE_TTL = 3600; // 1 hour

	private $settings;

	public function __construct() {
		$this->load_settings();
	}

	/**
	 * Load settings from database with object caching
	 */
	private function load_settings() {
		// Try to get from object cache first
		$cached = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );

		if ( false !== $cached ) {
			$this->settings = $cached;
			return;
		}

		// Cache miss - load from database
		$this->settings = get_option( self::OPTION_KEY, array() );

		// Ensure defaults are set
		if ( empty( $this->settings ) ) {
			$this->settings = $this->get_default_settings();
		}

		// Store in cache
		wp_cache_set( self::CACHE_KEY, $this->settings, self::CACHE_GROUP, self::CACHE_TTL );
	}

	/**
	 * Get all settings
	 *
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get a specific setting
	 *
	 * @param string $key Setting key
	 * @param mixed $default Default value if not set
	 * @return mixed
	 */
	public function get_setting( $key, $default = '' ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
	}

	/**
	 * Save settings to database and clear cache
	 *
	 * @param array $settings Settings array
	 * @return bool
	 */
	public function save_settings( $settings ) {
		$this->settings = $settings;
		$result = update_option( self::OPTION_KEY, $settings );

		// Clear object cache on save
		if ( $result ) {
			wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
			// Immediately update cache with new settings
			wp_cache_set( self::CACHE_KEY, $settings, self::CACHE_GROUP, self::CACHE_TTL );

			// Clear indexer settings REST API cache
			if ( class_exists( 'WP_AI_Indexer_Settings_Controller' ) ) {
				WP_AI_Indexer_Settings_Controller::clear_cache();
			}
		}

		return $result;
	}

	/**
	 * Set default settings
	 */
	public function set_default_settings() {
		$defaults = $this->get_default_settings();
		$this->save_settings( $defaults );
	}

	/**
	 * Get default settings schema
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return array(
			// General Settings (required for both modules)
			'openai_api_key'       => '',
			'pinecone_api_key'     => '',
			'pinecone_index_host'  => '',
			'pinecone_index_name'  => '',
			'embedding_model'      => 'text-embedding-3-small',
			'embedding_dimension'  => 1536,

			// Chatbot Settings
			'chatbot_enabled'      => true,
			'chatbot_system_prompt' => 'You are a helpful assistant. Use the provided context to answer questions accurately and concisely.',
			'chatbot_model'        => 'gpt-4o-mini',
			'chatbot_temperature'  => 0.2,
			'chatbot_top_k'        => 5,
			'chatbot_floating_button' => true,
			'chatbot_intro_message' => 'Hi! How can I help you today?',
			'chatbot_input_placeholder' => 'Ask a question...',

			// Search Settings
			'search_enabled'       => false,
			'search_top_k'         => 10,
			'search_min_score'     => 0.5,
			'search_replace_default' => true,
			'search_results_per_page' => 10,
			'search_placeholder'   => 'Search with AI...',
			'search_enable_summary' => true,
			'search_relevance_enabled' => true,
			'search_url_boost'     => 0.15,
			'search_title_exact_boost' => 0.12,
			'search_title_words_boost' => 0.08,
			'search_page_boost'    => 0.05,
			'search_system_prompt' => "You are an AI search assistant. Your role is to help users quickly understand what the search results say and decide which results are worth clicking.\n\nYou do NOT invent answers. You summarize, synthesize, and point to relevant results.\n\nOUTPUT FORMAT (REQUIRED)\n- Format ALL responses using HTML tags:\n  - Use <p> for paragraphs\n  - Use <strong> for emphasis\n  - Use <ul> and <li> for bullet lists\n  - Use <ol> and <li> for numbered lists\n  - Use <a href=\"...\" rel=\"noopener\" target=\"_blank\">Descriptive title</a> for links\n  - Use <br> for line breaks where helpful\n- Do NOT use Markdown\n- Do NOT wrap the entire response in a single <p>\n- Do NOT use headings (<h1>-<h6>)\n\nPRIMARY GOAL\nHelp the user:\n1) Understand the key takeaway from the search results, and\n2) Discover related or supporting results they may want to explore next.\n\nGROUNDING & ACCURACY (VERY IMPORTANT)\n- Use ONLY the provided search results as your source of truth\n- Do NOT add information that is not explicitly supported by the results\n- Do NOT infer intent, outcomes, or conclusions beyond what is stated\n- If the results do not clearly answer the question:\n  - Say so plainly\n  - Explain what the results do cover instead\n\nRESPONSE STRUCTURE (REQUIRED)\n1) <strong>Direct answer or summary</strong>\n   - 1–2 sentences that clearly state what the search results collectively show\n   - If there is no single answer, summarize the common themes or differences\n\n2) <strong>Key points from the results</strong>\n   - Use a bullet list to highlight specific facts, steps, definitions, or findings\n   - Each bullet should reflect something concrete from the results\n\n3) <strong>Related or useful results</strong>\n   - Provide 2–4 links the user may want to explore next\n   - Briefly explain what each link covers and why it's relevant\n   - Always link using the URL from the search results\n\nLINK USAGE RULES\n- Include links inline where they add clarity or credibility\n- Use descriptive link text that reflects the page's content\n- Do NOT repeat the same link multiple times unless necessary\n- Do NOT invent or assume URLs\n\nCONTENT GUIDELINES\n- Keep the response concise and scannable\n- Aim for 100–180 words total unless the question clearly requires more\n- Avoid filler phrases like:\n  - \"Based on the search results…\"\n  - \"This article discusses…\"\n- Focus on clarity over completeness\n\nTONE\n- Neutral, helpful, and informative\n- Confident but not authoritative\n- No marketing language, hype, or opinionated framing\n\nCONTENT PREFERENCES\nThis section controls how search results are prioritized, summarized, and presented. These preferences are set by an administrator and must be followed for every response.\n\nYou MUST apply these preferences when analyzing search results. Do not explain or restate them in your response.\n\nPreferences may include (but are not limited to):\n\n- Content types to prioritize (for example: guides, documentation, blog posts, case studies, FAQs)\n- Content types to de-emphasize or exclude (for example: marketing pages, announcements, outdated posts)\n- Preferred freshness (for example: newest results first, evergreen content preferred, or no preference)\n- Preferred depth:\n  - High-level summaries\n  - Step-by-step explanations\n  - Technical or advanced detail\n- Preferred tone:\n  - Neutral and factual\n  - Conversational\n  - Instructional\n- Preferred audiences (for example: beginners, practitioners, decision-makers)\n\nIf preferences conflict:\n- Prioritize clarity and relevance to the user's query\n- Prefer explicit administrator preferences over inferred intent\n\nIf preferences limit available results:\n- Work only with the remaining eligible content\n- If no strong results remain, say so clearly and summarize what is available instead\n\nCLOSING BEHAVIOR\nEnd with a light, optional nudge that helps the user continue, such as:\n- \"If you want more detail, the links above go deeper.\"\n- \"Let me know if you'd like help comparing these results.\"",

			// Indexer Settings
			'post_types'           => 'posts,pages',
			'post_types_exclude'   => 'attachment,revision,nav_menu_item,customize_changeset,custom_css,oembed_cache,user_request,wp_block,wp_template,wp_template_part,wp_navigation,media,menu-items,blocks,templates,template-parts,global-styles,navigation,font-families,wpcf7_contact_form',
			'auto_discover'        => true,
			'clean_deleted'        => true,
			'chunk_size'           => 1200,
			'chunk_overlap'        => 200,
			'indexer_node_path'    => '',

			// Meta
			'schema_version'       => WP_AI_ASSISTANT_SCHEMA_VERSION,
			'version'              => WP_AI_ASSISTANT_VERSION,
		);
	}

	/**
	 * Get current domain from WordPress home URL
	 *
	 * @return string
	 */
	public function get_current_domain() {
		$domain = parse_url( home_url(), PHP_URL_HOST );
		return $domain ? $domain : '';
	}

	/**
	 * Check if plugin is properly configured
	 *
	 * @return bool
	 */
	public function is_configured() {
		// Check for required Pinecone configuration
		$pinecone_host = $this->get_setting( 'pinecone_index_host' );
		$pinecone_name = $this->get_setting( 'pinecone_index_name' );

		if ( empty( $pinecone_host ) || empty( $pinecone_name ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate settings before saving
	 *
	 * @param array $settings Settings to validate
	 * @return array|WP_Error Validated settings or error
	 */
	public function validate_settings( $settings ) {
		$validated = array();

		// Validate each category of settings
		$validated = array_merge( $validated, $this->validate_embedding_settings( $settings ) );
		$validated = array_merge( $validated, $this->validate_chatbot_settings( $settings ) );
		$validated = array_merge( $validated, $this->validate_search_settings( $settings ) );
		$validated = array_merge( $validated, $this->validate_relevance_settings( $settings ) );
		$validated = array_merge( $validated, $this->validate_pinecone_settings( $settings ) );
		$validated = array_merge( $validated, $this->validate_indexer_settings( $settings ) );

		// Merge with existing settings to preserve other values
		$validated = array_merge( $this->settings, $validated );

		// Update version and schema
		$validated['version'] = WP_AI_ASSISTANT_VERSION;
		$validated['schema_version'] = WP_AI_ASSISTANT_SCHEMA_VERSION;

		return $validated;
	}

	/**
	 * Validate embedding settings
	 *
	 * @param array $settings Settings to validate
	 * @return array Validated embedding settings
	 */
	private function validate_embedding_settings( $settings ) {
		$validated = array();

		// Validate embedding model
		$valid_models = array( 'text-embedding-3-small', 'text-embedding-3-large', 'text-embedding-ada-002' );
		if ( ! empty( $settings['embedding_model'] ) && in_array( $settings['embedding_model'], $valid_models, true ) ) {
			$validated['embedding_model'] = $settings['embedding_model'];
		}

		// Validate embedding dimension
		if ( isset( $settings['embedding_dimension'] ) ) {
			$dimension = absint( $settings['embedding_dimension'] );
			if ( $dimension > 0 ) {
				$validated['embedding_dimension'] = $dimension;
			}
		}

		return $validated;
	}

	/**
	 * Validate chatbot settings
	 *
	 * @param array $settings Settings to validate
	 * @return array Validated chatbot settings
	 */
	private function validate_chatbot_settings( $settings ) {
		$validated = array();

		if ( isset( $settings['chatbot_enabled'] ) ) {
			$validated['chatbot_enabled'] = (bool) $settings['chatbot_enabled'];
		}

		if ( ! empty( $settings['chatbot_system_prompt'] ) ) {
			$validated['chatbot_system_prompt'] = sanitize_textarea_field( $settings['chatbot_system_prompt'] );
		}

		if ( ! empty( $settings['chatbot_model'] ) ) {
			$validated['chatbot_model'] = sanitize_text_field( $settings['chatbot_model'] );
		}

		if ( isset( $settings['chatbot_temperature'] ) ) {
			$temp = floatval( $settings['chatbot_temperature'] );
			$validated['chatbot_temperature'] = max( 0, min( 2, $temp ) );
		}

		if ( isset( $settings['chatbot_top_k'] ) ) {
			$validated['chatbot_top_k'] = absint( $settings['chatbot_top_k'] );
		}

		if ( isset( $settings['chatbot_floating_button'] ) ) {
			$validated['chatbot_floating_button'] = (bool) $settings['chatbot_floating_button'];
		}

		if ( isset( $settings['chatbot_intro_message'] ) ) {
			$validated['chatbot_intro_message'] = sanitize_text_field( $settings['chatbot_intro_message'] );
		}

		if ( isset( $settings['chatbot_input_placeholder'] ) ) {
			$validated['chatbot_input_placeholder'] = sanitize_text_field( $settings['chatbot_input_placeholder'] );
		}

		return $validated;
	}

	/**
	 * Validate search settings
	 *
	 * @param array $settings Settings to validate
	 * @return array Validated search settings
	 */
	private function validate_search_settings( $settings ) {
		$validated = array();

		if ( isset( $settings['search_enabled'] ) ) {
			$validated['search_enabled'] = (bool) $settings['search_enabled'];
		}

		if ( isset( $settings['search_top_k'] ) ) {
			$validated['search_top_k'] = absint( $settings['search_top_k'] );
		}

		if ( isset( $settings['search_min_score'] ) ) {
			$score = floatval( $settings['search_min_score'] );
			$validated['search_min_score'] = max( 0, min( 1, $score ) );
		}

		if ( isset( $settings['search_replace_default'] ) ) {
			$validated['search_replace_default'] = (bool) $settings['search_replace_default'];
		}

		if ( isset( $settings['search_results_per_page'] ) ) {
			$validated['search_results_per_page'] = absint( $settings['search_results_per_page'] );
		}

		if ( isset( $settings['search_placeholder'] ) ) {
			$validated['search_placeholder'] = sanitize_text_field( $settings['search_placeholder'] );
		}

		if ( isset( $settings['search_enable_summary'] ) ) {
			$validated['search_enable_summary'] = (bool) $settings['search_enable_summary'];
		}

		if ( ! empty( $settings['search_system_prompt'] ) ) {
			$validated['search_system_prompt'] = sanitize_textarea_field( $settings['search_system_prompt'] );
		}

		return $validated;
	}

	/**
	 * Validate relevance boosting settings
	 *
	 * @param array $settings Settings to validate
	 * @return array Validated relevance settings
	 */
	private function validate_relevance_settings( $settings ) {
		$validated = array();

		if ( isset( $settings['search_relevance_enabled'] ) ) {
			$validated['search_relevance_enabled'] = (bool) $settings['search_relevance_enabled'];
		}

		$boost_fields = array(
			'search_url_boost',
			'search_title_exact_boost',
			'search_title_words_boost',
			'search_page_boost',
		);

		foreach ( $boost_fields as $field ) {
			if ( isset( $settings[ $field ] ) ) {
				$boost = floatval( $settings[ $field ] );
				$validated[ $field ] = max( 0, min( 1, $boost ) );
			}
		}

		return $validated;
	}

	/**
	 * Validate Pinecone settings
	 *
	 * @param array $settings Settings to validate
	 * @return array Validated Pinecone settings
	 */
	private function validate_pinecone_settings( $settings ) {
		$validated = array();

		if ( ! empty( $settings['pinecone_index_host'] ) ) {
			$validated['pinecone_index_host'] = esc_url_raw( $settings['pinecone_index_host'] );
		}

		if ( ! empty( $settings['pinecone_index_name'] ) ) {
			$validated['pinecone_index_name'] = sanitize_text_field( $settings['pinecone_index_name'] );
		}

		return $validated;
	}

	/**
	 * Validate indexer settings
	 *
	 * @param array $settings Settings to validate
	 * @return array Validated indexer settings
	 */
	private function validate_indexer_settings( $settings ) {
		$validated = array();

		if ( isset( $settings['post_types'] ) ) {
			$validated['post_types'] = sanitize_text_field( $settings['post_types'] );
		}

		if ( isset( $settings['post_types_exclude'] ) ) {
			$validated['post_types_exclude'] = sanitize_textarea_field( $settings['post_types_exclude'] );
		}

		if ( isset( $settings['auto_discover'] ) ) {
			$validated['auto_discover'] = (bool) $settings['auto_discover'];
		}

		if ( isset( $settings['clean_deleted'] ) ) {
			$validated['clean_deleted'] = (bool) $settings['clean_deleted'];
		}

		if ( isset( $settings['chunk_size'] ) ) {
			$chunk_size = absint( $settings['chunk_size'] );
			// Validate range: 100-10000
			$validated['chunk_size'] = max( 100, min( 10000, $chunk_size ) );
		}

		if ( isset( $settings['chunk_overlap'] ) ) {
			$chunk_overlap = absint( $settings['chunk_overlap'] );
			// Validate range: 0-1000
			$validated['chunk_overlap'] = max( 0, min( 1000, $chunk_overlap ) );
		}

		if ( isset( $settings['indexer_node_path'] ) ) {
			$node_path = sanitize_text_field( $settings['indexer_node_path'] );
			// Validate path exists if not empty
			if ( ! empty( $node_path ) && ! file_exists( $node_path ) ) {
				// Path doesn't exist - don't save it
				$node_path = '';
			}
			$validated['indexer_node_path'] = $node_path;
		}

		return $validated;
	}

	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	public function get_version() {
		return WP_AI_ASSISTANT_VERSION;
	}

	/**
	 * Get schema version
	 *
	 * @return int
	 */
	public function get_schema_version() {
		return WP_AI_ASSISTANT_SCHEMA_VERSION;
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
}
