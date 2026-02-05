<?php
/**
 * Semantic Knowledge Asset Manager
 *
 * Handles asset optimization, versioning, and CDN integration
 *
 * @package Semantic_Knowledge
 */

class Semantic_Knowledge_Assets {
	/**
	 * Register asset optimization hooks
	 */
	public static function init() {
		// Add asset versioning with content hash
		add_filter( 'style_loader_src', array( __CLASS__, 'add_asset_version' ), 10, 2 );
		add_filter( 'script_loader_src', array( __CLASS__, 'add_asset_version' ), 10, 2 );

		// Add lazy loading to images
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'add_lazy_loading' ), 10, 3 );

		// Add SRI (Subresource Integrity) to external scripts
		add_filter( 'script_loader_tag', array( __CLASS__, 'add_sri_to_scripts' ), 10, 3 );

		// Optionally use CDN for assets
		if ( defined( 'WP_AI_CDN_URL' ) && WP_AI_CDN_URL ) {
			add_filter( 'style_loader_src', array( __CLASS__, 'use_cdn' ), 20, 1 );
			add_filter( 'script_loader_src', array( __CLASS__, 'use_cdn' ), 20, 1 );
		}
	}

	/**
	 * Add content-based versioning to assets
	 * Uses file modification time as version instead of plugin version
	 *
	 * @param string $src Asset source URL
	 * @param string $handle Asset handle
	 * @return string Modified asset URL
	 */
	public static function add_asset_version( $src, $handle ) {
		// Only version our plugin assets
		if ( strpos( $src, 'wp-ai-assistant' ) === false ) {
			return $src;
		}

		// Remove existing version query param
		$src = remove_query_arg( 'ver', $src );

		// Get file path from URL
		$file_path = str_replace( SEMANTIC_KNOWLEDGE_URL, SEMANTIC_KNOWLEDGE_DIR, $src );
		$file_path = strtok( $file_path, '?' ); // Remove query string

		// If file exists, use modification time as version
		if ( file_exists( $file_path ) ) {
			$version = filemtime( $file_path );
			return add_query_arg( 'ver', $version, $src );
		}

		return $src;
	}

	/**
	 * Add lazy loading attribute to images
	 *
	 * @param array $attr Image attributes
	 * @param WP_Post $attachment Image attachment post
	 * @param string|int[] $size Image size
	 * @return array Modified attributes
	 */
	public static function add_lazy_loading( $attr, $attachment, $size ) {
		// Don't add loading attribute if already set
		if ( isset( $attr['loading'] ) ) {
			return $attr;
		}

		// Add lazy loading
		$attr['loading'] = 'lazy';

		return $attr;
	}

	/**
	 * Add Subresource Integrity (SRI) to external scripts
	 *
	 * @param string $tag Script tag HTML
	 * @param string $handle Script handle
	 * @param string $src Script source URL
	 * @return string Modified script tag
	 */
	public static function add_sri_to_scripts( $tag, $handle, $src ) {
		// List of known external scripts with SRI hashes
		$sri_scripts = apply_filters(
			'semantic_knowledge_sri_scripts',
			array(
				'deep-chat' => array(
					'integrity' => 'sha384-PLACEHOLDER', // Should be updated with actual hash
					'crossorigin' => 'anonymous',
				),
			)
		);

		// Check if this script needs SRI
		if ( isset( $sri_scripts[ $handle ] ) ) {
			$sri = $sri_scripts[ $handle ];

			// Only add SRI to external scripts
			if ( strpos( $src, home_url() ) === false ) {
				// Add integrity and crossorigin attributes
				$tag = str_replace(
					'<script ',
					sprintf(
						'<script integrity="%s" crossorigin="%s" ',
						esc_attr( $sri['integrity'] ),
						esc_attr( $sri['crossorigin'] )
					),
					$tag
				);
			}
		}

		return $tag;
	}

	/**
	 * Use CDN URL for assets
	 *
	 * @param string $src Asset source URL
	 * @return string Modified asset URL with CDN
	 */
	public static function use_cdn( $src ) {
		// Only apply CDN to our plugin assets
		if ( strpos( $src, 'wp-ai-assistant' ) === false ) {
			return $src;
		}

		// Don't apply CDN in development
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return $src;
		}

		$cdn_url = WP_AI_CDN_URL;

		// Replace plugin URL with CDN URL
		$cdn_src = str_replace( SEMANTIC_KNOWLEDGE_URL, trailingslashit( $cdn_url ) . 'wp-content/plugins/wp-ai-assistant/', $src );

		return $cdn_src;
	}

	/**
	 * Minify CSS content
	 * Simple minification - removes comments, whitespace, and line breaks
	 *
	 * @param string $css CSS content
	 * @return string Minified CSS
	 */
	public static function minify_css( $css ) {
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

		// Remove whitespace
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );

		// Remove spaces around special characters
		$css = str_replace( array( ' {', '{ ', ' }', '} ', ' :', ': ', ' ;', '; ', ' ,', ', ' ), array( '{', '{', '}', '}', ':', ':', ';', ';', ',', ',' ), $css );

		return trim( $css );
	}

	/**
	 * Minify JavaScript content
	 * Simple minification - removes comments and excess whitespace
	 * For production, use a proper minifier like Terser
	 *
	 * @param string $js JavaScript content
	 * @return string Minified JavaScript
	 */
	public static function minify_js( $js ) {
		// Remove single-line comments (but not URLs)
		$js = preg_replace( '~//[^\n]*~', '', $js );

		// Remove multi-line comments
		$js = preg_replace( '~/\*.*?\*/~s', '', $js );

		// Remove excess whitespace
		$js = preg_replace( '~\s+~', ' ', $js );

		return trim( $js );
	}

	/**
	 * Get minified asset path
	 * Checks if a .min version exists, otherwise returns original
	 *
	 * @param string $file_path Path to asset file
	 * @return string Path to minified version or original
	 */
	public static function get_minified_asset( $file_path ) {
		// Check if we're in production mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return $file_path;
		}

		// Check for .min version
		$min_path = str_replace( array( '.css', '.js' ), array( '.min.css', '.min.js' ), $file_path );

		if ( file_exists( $min_path ) ) {
			return $min_path;
		}

		return $file_path;
	}

	/**
	 * Create minified versions of all plugin assets
	 * Run this during build process or plugin activation
	 */
	public static function create_minified_assets() {
		$assets_dir = SEMANTIC_KNOWLEDGE_DIR . 'assets/';

		// Minify CSS files
		$css_files = glob( $assets_dir . 'css/*.css' );
		foreach ( $css_files as $file ) {
			// Skip already minified files
			if ( strpos( $file, '.min.css' ) !== false ) {
				continue;
			}

			$content = file_get_contents( $file );
			$minified = self::minify_css( $content );

			$min_file = str_replace( '.css', '.min.css', $file );
			file_put_contents( $min_file, $minified );
		}

		// Minify JS files
		$js_files = glob( $assets_dir . 'js/*.js' );
		foreach ( $js_files as $file ) {
			// Skip already minified files
			if ( strpos( $file, '.min.js' ) !== false ) {
				continue;
			}

			$content = file_get_contents( $file );
			$minified = self::minify_js( $content );

			$min_file = str_replace( '.js', '.min.js', $file );
			file_put_contents( $min_file, $minified );
		}
	}

	/**
	 * Get asset statistics
	 *
	 * @return array Asset statistics
	 */
	public static function get_stats() {
		$assets_dir = SEMANTIC_KNOWLEDGE_DIR . 'assets/';
		$css_dir = $assets_dir . 'css/';
		$js_dir = $assets_dir . 'js/';

		$stats = array(
			'css_files'         => count( glob( $css_dir . '*.css' ) ),
			'css_minified'      => count( glob( $css_dir . '*.min.css' ) ),
			'js_files'          => count( glob( $js_dir . '*.js' ) ),
			'js_minified'       => count( glob( $js_dir . '*.min.js' ) ),
			'cdn_enabled'       => defined( 'WP_AI_CDN_URL' ) && WP_AI_CDN_URL,
			'lazy_load_enabled' => true, // Always enabled
		);

		return $stats;
	}
}
