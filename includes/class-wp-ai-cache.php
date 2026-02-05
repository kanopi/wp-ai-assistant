<?php
/**
 * WP AI Assistant Cache Helper
 *
 * Provides caching functionality for embeddings and query results
 * Uses WordPress transients with Redis/Memcached object cache support
 *
 * @package WP_AI_Assistant
 */

class WP_AI_Cache {
	/**
	 * Cache key prefix
	 *
	 * @var string
	 */
	const CACHE_PREFIX = 'wp_ai_cache_';

	/**
	 * Default cache TTL (15 minutes for hot cache)
	 *
	 * @var int
	 */
	const DEFAULT_TTL = 900;

	/**
	 * Warm cache TTL (1 hour)
	 *
	 * @var int
	 */
	const WARM_CACHE_TTL = 3600;

	/**
	 * Get a value from cache
	 *
	 * @param string $key Cache key
	 * @return mixed|false Cached value or false if not found
	 */
	public static function get( $key ) {
		$cache_key = self::CACHE_PREFIX . wp_hash( $key );

		// Try to get from object cache first (Redis/Memcached)
		if ( wp_using_ext_object_cache() ) {
			return wp_cache_get( $cache_key, 'wp_ai_assistant' );
		}

		// Fall back to transients
		return get_transient( $cache_key );
	}

	/**
	 * Set a value in cache
	 *
	 * @param string $key Cache key
	 * @param mixed $value Value to cache
	 * @param int $ttl Time to live in seconds (default: 15 minutes)
	 * @return bool True on success, false on failure
	 */
	public static function set( $key, $value, $ttl = self::DEFAULT_TTL ) {
		$cache_key = self::CACHE_PREFIX . wp_hash( $key );

		// Use object cache if available (Redis/Memcached)
		if ( wp_using_ext_object_cache() ) {
			return wp_cache_set( $cache_key, $value, 'wp_ai_assistant', $ttl );
		}

		// Fall back to transients
		return set_transient( $cache_key, $value, $ttl );
	}

	/**
	 * Delete a value from cache
	 *
	 * @param string $key Cache key
	 * @return bool True on success, false on failure
	 */
	public static function delete( $key ) {
		$cache_key = self::CACHE_PREFIX . wp_hash( $key );

		// Delete from object cache if available
		if ( wp_using_ext_object_cache() ) {
			return wp_cache_delete( $cache_key, 'wp_ai_assistant' );
		}

		// Delete transient
		return delete_transient( $cache_key );
	}

	/**
	 * Flush all cache entries with our prefix
	 *
	 * @return bool True on success, false on failure
	 */
	public static function flush_all() {
		global $wpdb;

		// If using object cache, we can't easily flush by prefix
		// The consumer should handle cache invalidation on settings changes
		if ( wp_using_ext_object_cache() ) {
			// Increment cache version to effectively invalidate all caches
			$version = (int) get_option( 'wp_ai_cache_version', 0 );
			update_option( 'wp_ai_cache_version', $version + 1 );
			return true;
		}

		// For transients, delete from database
		$prefix = '_transient_' . self::CACHE_PREFIX;
		$timeout_prefix = '_transient_timeout_' . self::CACHE_PREFIX;

		// Delete transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( $prefix ) . '%',
				$wpdb->esc_like( $timeout_prefix ) . '%'
			)
		);

		return true;
	}

	/**
	 * Get cache key for embedding
	 *
	 * @param string $text Text to create embedding for
	 * @return string Cache key
	 */
	public static function get_embedding_cache_key( $text ) {
		return 'embedding_' . wp_hash( $text );
	}

	/**
	 * Get cache key for query results
	 *
	 * @param array $embedding Embedding vector
	 * @param int $top_k Number of results
	 * @return string Cache key
	 */
	public static function get_query_cache_key( $embedding, $top_k ) {
		// Create a hash of the embedding vector
		$embedding_hash = wp_hash( json_encode( $embedding ) );
		return 'query_' . $embedding_hash . '_k' . $top_k;
	}

	/**
	 * Get cached embedding
	 *
	 * @param string $text Text to get embedding for
	 * @return array|false Cached embedding or false if not found
	 */
	public static function get_embedding( $text ) {
		$key = self::get_embedding_cache_key( $text );
		return self::get( $key );
	}

	/**
	 * Cache an embedding
	 *
	 * @param string $text Text that was embedded
	 * @param array $embedding Embedding vector
	 * @param int $ttl Time to live (default: 1 hour)
	 * @return bool True on success, false on failure
	 */
	public static function set_embedding( $text, $embedding, $ttl = self::WARM_CACHE_TTL ) {
		$key = self::get_embedding_cache_key( $text );
		return self::set( $key, $embedding, $ttl );
	}

	/**
	 * Get cached query results
	 *
	 * @param array $embedding Embedding vector
	 * @param int $top_k Number of results
	 * @return array|false Cached results or false if not found
	 */
	public static function get_query_results( $embedding, $top_k ) {
		$key = self::get_query_cache_key( $embedding, $top_k );
		return self::get( $key );
	}

	/**
	 * Cache query results
	 *
	 * @param array $embedding Embedding vector
	 * @param int $top_k Number of results
	 * @param array $results Query results
	 * @param int $ttl Time to live (default: 15 minutes)
	 * @return bool True on success, false on failure
	 */
	public static function set_query_results( $embedding, $top_k, $results, $ttl = self::DEFAULT_TTL ) {
		$key = self::get_query_cache_key( $embedding, $top_k );
		return self::set( $key, $results, $ttl );
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache statistics
	 */
	public static function get_stats() {
		$using_object_cache = wp_using_ext_object_cache();

		return array(
			'using_object_cache' => $using_object_cache,
			'cache_type' => $using_object_cache ? 'Redis/Memcached' : 'Database Transients',
			'cache_prefix' => self::CACHE_PREFIX,
			'default_ttl' => self::DEFAULT_TTL,
			'warm_cache_ttl' => self::WARM_CACHE_TTL,
		);
	}
}
