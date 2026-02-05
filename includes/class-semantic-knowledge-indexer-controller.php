<?php
/**
 * Indexer Settings REST API Controller for Semantic Knowledge
 *
 * Provides endpoint for indexer configuration:
 * GET /wp-json/semantic-knowledge/v1/indexer-settings
 *
 * This controller is specifically designed for the unified Semantic Knowledge plugin
 * and includes domain filtering support.
 *
 * @package Semantic_Knowledge
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Semantic_Knowledge_Indexer_Settings_Controller extends WP_REST_Controller {

	/**
	 * Schema version - increment when breaking changes are made
	 *
	 * Version 1: Includes domain field for multi-site filtering
	 */
	const SCHEMA_VERSION = 1;

	/**
	 * Shared option key for unified plugin settings
	 */
	const OPTION_KEY = 'semantic_knowledge_settings';

	/**
	 * Cache key for REST API response
	 */
	const CACHE_KEY = 'semantic_knowledge_indexer_settings_response';

	/**
	 * Cache duration in seconds (1 hour)
	 */
	const CACHE_TTL = 3600;

	/**
	 * REST API namespace
	 */
	const NAMESPACE = 'semantic-knowledge/v1';

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/indexer-settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'get_settings_permissions_check' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Permission check for getting settings
	 *
	 * Requires authentication via:
	 * 1. X-WP-Indexer-Key header with valid indexer key
	 * 2. WordPress authentication (logged in user with manage_options capability)
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if allowed to access, WP_Error otherwise
	 */
	public function get_settings_permissions_check( $request ) {
		// Option 1: Check for indexer API key in header
		$indexer_key = $request->get_header( 'X-WP-Indexer-Key' );

		if ( ! empty( $indexer_key ) ) {
			$valid_key = $this->get_indexer_api_key();

			if ( empty( $valid_key ) ) {
				return new WP_Error(
					'semantic_knowledge_indexer_key_not_configured',
					'Indexer API key is not configured. Please set WP_AI_INDEXER_KEY environment variable.',
					array( 'status' => 500 )
				);
			}

			// Use hash_equals to prevent timing attacks
			if ( hash_equals( $valid_key, $indexer_key ) ) {
				return true;
			}

			return new WP_Error(
				'semantic_knowledge_indexer_invalid_key',
				'Invalid indexer API key.',
				array( 'status' => 403 )
			);
		}

		// Option 2: Check for WordPress authentication
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'semantic_knowledge_indexer_unauthorized',
			'Authentication required. Provide X-WP-Indexer-Key header or log in as administrator.',
			array( 'status' => 401 )
		);
	}

	/**
	 * Get the indexer API key from environment or secrets
	 *
	 * @return string|null API key or null if not configured
	 */
	private function get_indexer_api_key() {
		// Check environment variable
		$env_key = getenv( 'WP_AI_INDEXER_KEY' );
		if ( ! empty( $env_key ) ) {
			return $env_key;
		}

		// Check PHP constant
		if ( defined( 'WP_AI_INDEXER_KEY' ) ) {
			return WP_AI_INDEXER_KEY;
		}

		// Check Pantheon secrets if available
		if ( function_exists( 'pantheon_get_secret' ) ) {
			$pantheon_key = pantheon_get_secret( 'WP_AI_INDEXER_KEY' );
			if ( ! empty( $pantheon_key ) ) {
				return $pantheon_key;
			}
		}

		return null;
	}

	/**
	 * Get indexer settings with caching
	 *
	 * Returns the configuration needed by @kanopi/wp-ai-indexer to index content.
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response with settings or error
	 */
	public function get_settings( $request ) {
		// Try to get from cache first
		$cached_response = get_transient( self::CACHE_KEY );

		if ( false !== $cached_response && is_array( $cached_response ) ) {
			return rest_ensure_response( $cached_response );
		}

		// Cache miss - load and prepare settings
		$settings = $this->load_settings();

		// Get domain for filtering
		$domain = parse_url( home_url(), PHP_URL_HOST );

		// Validate and prepare response
		$response = $this->prepare_settings_response( $settings, $domain );

		// Return error if validation failed
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Store in cache
		set_transient( self::CACHE_KEY, $response, self::CACHE_TTL );

		return rest_ensure_response( $response );
	}

	/**
	 * Clear settings cache
	 *
	 * Call this method when settings are updated to invalidate the cache.
	 * This should be hooked into the settings save action.
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Load settings from WordPress options
	 *
	 * @return array Settings array with defaults
	 */
	private function load_settings() {
		// Get stored settings
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		// Merge with defaults
		$defaults = $this->get_default_settings();
		return wp_parse_args( $stored, $defaults );
	}

	/**
	 * Get default settings
	 *
	 * @return array Default settings
	 */
	private function get_default_settings() {
		return array(
			'post_types'          => 'posts,pages',
			'post_types_exclude'  => 'attachment,revision,nav_menu_item,customize_changeset,custom_css,oembed_cache,user_request,wp_block,wp_template,wp_template_part,wp_navigation,media,menu-items,blocks,templates,template-parts,global-styles,navigation,font-families,wpcf7_contact_form',
			'auto_discover'       => true,
			'clean_deleted'       => true,
			'embedding_model'     => 'text-embedding-3-small',
			'embedding_dimension' => 1536,
			'chunk_size'          => 1200,
			'chunk_overlap'       => 200,
			'pinecone_index_host' => '',
			'pinecone_index_name' => '',
		);
	}

	/**
	 * Prepare settings response
	 *
	 * Validates and formats settings for the REST API response.
	 *
	 * @param array $settings Raw settings array
	 * @param string $domain Current WordPress domain
	 * @return array|WP_Error Prepared settings or error
	 */
	private function prepare_settings_response( $settings, $domain ) {
		// Parse post types from comma-separated string
		$post_types = array();
		if ( isset( $settings['post_types'] ) ) {
			if ( is_array( $settings['post_types'] ) ) {
				$post_types = $settings['post_types'];
			} else {
				$post_types = array_filter(
					array_map( 'trim', explode( ',', $settings['post_types'] ) )
				);
			}
		}

		// Parse excluded post types
		$post_types_exclude = array();
		if ( isset( $settings['post_types_exclude'] ) ) {
			if ( is_array( $settings['post_types_exclude'] ) ) {
				$post_types_exclude = $settings['post_types_exclude'];
			} else {
				$post_types_exclude = array_filter(
					array_map( 'trim', explode( ',', $settings['post_types_exclude'] ) )
				);
			}
		}

		// Get Pinecone configuration (may come from settings or secrets)
		$pinecone_index_host = $this->get_pinecone_config( 'host', $settings );
		$pinecone_index_name = $this->get_pinecone_config( 'name', $settings );

		// Validate required Pinecone configuration
		if ( empty( $pinecone_index_host ) || empty( $pinecone_index_name ) ) {
			return new WP_Error(
				'semantic_knowledge_missing_config',
				'Pinecone configuration is incomplete. Please configure Pinecone index host and name.',
				array( 'status' => 500 )
			);
		}

		// Prepare response (includes domain)
		return array(
			'schema_version'      => self::SCHEMA_VERSION,
			'domain'              => $domain,
			'post_types'          => $post_types,
			'post_types_exclude'  => $post_types_exclude,
			'auto_discover'       => ! empty( $settings['auto_discover'] ),
			'clean_deleted'       => ! empty( $settings['clean_deleted'] ),
			'embedding_model'     => $settings['embedding_model'],
			'embedding_dimension' => (int) $settings['embedding_dimension'],
			'chunk_size'          => (int) $settings['chunk_size'],
			'chunk_overlap'       => (int) $settings['chunk_overlap'],
			'pinecone_index_host' => $pinecone_index_host,
			'pinecone_index_name' => $pinecone_index_name,
		);
	}

	/**
	 * Get Pinecone configuration
	 *
	 * Checks unified plugin settings first, then falls back to environment/secrets.
	 *
	 * @param string $key Config key ('host' or 'name')
	 * @param array $settings Indexer settings array
	 * @return string Pinecone configuration value
	 */
	private function get_pinecone_config( $key, $settings ) {
		// Map keys to settings keys
		$setting_keys = array(
			'host' => 'pinecone_index_host',
			'name' => 'pinecone_index_name',
		);

		if ( ! isset( $setting_keys[ $key ] ) ) {
			return '';
		}

		$setting_key = $setting_keys[ $key ];

		// Check indexer settings first
		if ( ! empty( $settings[ $setting_key ] ) ) {
			return trim( $settings[ $setting_key ] );
		}

		// Fall back to unified plugin settings
		$plugin_settings = get_option( 'semantic_knowledge_settings', array() );
		if ( ! empty( $plugin_settings[ $setting_key ] ) ) {
			return trim( $plugin_settings[ $setting_key ] );
		}

		// Fall back to environment variables
		$env_keys = array(
			'host' => 'PINECONE_INDEX_HOST',
			'name' => 'PINECONE_INDEX_NAME',
		);

		$env_value = getenv( $env_keys[ $key ] );
		if ( ! empty( $env_value ) ) {
			return trim( $env_value );
		}

		// Fall back to defined constants
		$const_keys = array(
			'host' => 'PINECONE_INDEX_HOST',
			'name' => 'PINECONE_INDEX_NAME',
		);

		if ( defined( $const_keys[ $key ] ) ) {
			$const_value = constant( $const_keys[ $key ] );
			if ( ! empty( $const_value ) ) {
				return trim( $const_value );
			}
		}

		return '';
	}

	/**
	 * Get settings schema
	 *
	 * Defines the structure of the settings object for REST API documentation.
	 *
	 * @return array Schema definition
	 */
	public function get_settings_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'wp-ai-assistant-indexer-settings',
			'type'       => 'object',
			'properties' => array(
				'schema_version'      => array(
					'description' => 'Schema version for compatibility checking',
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'domain'              => array(
					'description' => 'WordPress site domain for filtering',
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'post_types'          => array(
					'description' => 'Post types to index',
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
					),
					'context'     => array( 'view' ),
				),
				'post_types_exclude'  => array(
					'description' => 'Post types to exclude from indexing',
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
					),
					'context'     => array( 'view' ),
				),
				'auto_discover'       => array(
					'description' => 'Automatically discover REST-visible post types',
					'type'        => 'boolean',
					'context'     => array( 'view' ),
				),
				'clean_deleted'       => array(
					'description' => 'Remove vectors for deleted content',
					'type'        => 'boolean',
					'context'     => array( 'view' ),
				),
				'embedding_model'     => array(
					'description' => 'OpenAI embedding model to use',
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'embedding_dimension' => array(
					'description' => 'Embedding dimension (must match Pinecone index)',
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'chunk_size'          => array(
					'description' => 'Content chunk size in characters',
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'chunk_overlap'       => array(
					'description' => 'Chunk overlap in characters',
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'pinecone_index_host' => array(
					'description' => 'Pinecone index host URL',
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'pinecone_index_name' => array(
					'description' => 'Pinecone index name',
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
			),
		);
	}
}
