<?php
/**
 * Secrets management for Semantic Knowledge
 * Hierarchical secret retrieval: Constants → Env → Pantheon → File
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Semantic_Knowledge_Secrets {
	private $secrets_cache = array();
	private $secrets_file_path;

	public function __construct() {
		$upload_dir = wp_upload_dir();
		$this->secrets_file_path = $upload_dir['basedir'] . '/private/secrets.json';
	}

	/**
	 * Get a secret value using hierarchical retrieval
	 *
	 * Priority order:
	 * 1. PHP constants
	 * 2. Environment variables
	 * 3. Pantheon Secrets
	 * 4. Private secrets file
	 *
	 * @param string $key Secret key
	 * @return string|null Secret value or null if not found
	 */
	public function get_secret( $key ) {
		// Check cache first
		if ( isset( $this->secrets_cache[ $key ] ) ) {
			return $this->secrets_cache[ $key ];
		}

		$value = null;

		// 1. Check PHP constants
		if ( defined( $key ) ) {
			$value = constant( $key );
		}

		// 2. Check environment variables
		if ( null === $value ) {
			$env_value = getenv( $key );
			if ( false !== $env_value && '' !== $env_value ) {
				$value = $env_value;
			}
		}

		// 3. Check Pantheon Secrets (if function exists)
		if ( null === $value && function_exists( 'pantheon_get_secret' ) ) {
			$pantheon_value = pantheon_get_secret( $key );
			if ( false !== $pantheon_value && '' !== $pantheon_value ) {
				$value = $pantheon_value;
			}
		}

		// 4. Check private secrets file
		if ( null === $value ) {
			$value = $this->get_secret_from_file( $key );
		}

		// Cache the result (even if null)
		$this->secrets_cache[ $key ] = $value;

		return $value;
	}

	/**
	 * Get secret from private file or fall back to setting
	 *
	 * @deprecated Fallback to settings is deprecated for security reasons.
	 *             API keys should only be stored in environment variables or secrets.
	 *
	 * @param string $key Secret key
	 * @param string $setting_key Setting key to fall back to
	 * @return string
	 */
	public function get_secret_or_setting( $key, $setting_key ) {
		$secret = $this->get_secret( $key );

		if ( null !== $secret && '' !== $secret ) {
			return $secret;
		}

		// Check if setting exists (deprecated behavior)
		$settings = get_option( 'semantic_knowledge_settings', array() );
		$setting_value = isset( $settings[ $setting_key ] ) ? $settings[ $setting_key ] : '';

		// Log deprecation warning if setting is being used
		if ( ! empty( $setting_value ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'Semantic Knowledge: Storing API key "%s" in database settings is deprecated and insecure. ' .
					'Please use environment variables, PHP constants, or the secrets file instead. ' .
					'This fallback will be removed in a future version.',
					$setting_key
				)
			);
		}

		// For critical API keys, do not fall back to settings (security hardening)
		$critical_keys = array( 'openai_api_key', 'pinecone_api_key' );
		if ( in_array( $setting_key, $critical_keys, true ) ) {
			// Do not return database-stored API keys for security
			return '';
		}

		return $setting_value;
	}

	/**
	 * Read secret from private JSON file
	 *
	 * @param string $key Secret key
	 * @return string|null
	 */
	private function get_secret_from_file( $key ) {
		if ( ! file_exists( $this->secrets_file_path ) ) {
			return null;
		}

		$contents = file_get_contents( $this->secrets_file_path );
		if ( false === $contents ) {
			return null;
		}

		$secrets = json_decode( $contents, true );
		if ( ! is_array( $secrets ) ) {
			return null;
		}

		return isset( $secrets[ $key ] ) ? $secrets[ $key ] : null;
	}

	/**
	 * Check if a secret exists
	 *
	 * @param string $key Secret key
	 * @return bool
	 */
	public function has_secret( $key ) {
		$value = $this->get_secret( $key );
		return null !== $value && '' !== $value;
	}

	/**
	 * Clear secrets cache
	 */
	public function clear_cache() {
		$this->secrets_cache = array();
	}

	/**
	 * Get all available secret sources for debugging
	 *
	 * @param string $key Secret key
	 * @return array
	 */
	public function debug_secret_sources( $key ) {
		$sources = array(
			'constant' => defined( $key ),
			'environment' => false !== getenv( $key ),
			'pantheon' => function_exists( 'pantheon_get_secret' ) && false !== pantheon_get_secret( $key ),
			'file' => null !== $this->get_secret_from_file( $key ),
		);

		return $sources;
	}
}
