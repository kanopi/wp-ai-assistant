<?php
/**
 * WP AI Assistant Database Manager
 *
 * Handles custom database tables for improved performance
 * Uses custom tables instead of wp_posts for chat/search logs
 *
 * @package WP_AI_Assistant
 */

class WP_AI_Database {
	/**
	 * Database version for migrations
	 *
	 * @var string
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Option key for storing database version
	 *
	 * @var string
	 */
	const DB_VERSION_KEY = 'wp_ai_assistant_db_version';

	/**
	 * Initialize database tables if needed
	 */
	public static function init() {
		$installed_version = get_option( self::DB_VERSION_KEY, '0' );

		if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
			self::create_tables();
			update_option( self::DB_VERSION_KEY, self::DB_VERSION );
		}
	}

	/**
	 * Create custom database tables
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Chat logs table
		$chat_logs_table = $wpdb->prefix . 'ai_chat_logs';
		$chat_logs_sql = "CREATE TABLE IF NOT EXISTS {$chat_logs_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			question text NOT NULL,
			answer longtext NOT NULL,
			sources longtext DEFAULT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			session_id varchar(64) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			response_time int UNSIGNED DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY session_id (session_id),
			KEY created_at (created_at)
		) $charset_collate;";

		// Search logs table
		$search_logs_table = $wpdb->prefix . 'ai_search_logs';
		$search_logs_sql = "CREATE TABLE IF NOT EXISTS {$search_logs_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			query text NOT NULL,
			results_count int UNSIGNED DEFAULT 0,
			results longtext DEFAULT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			session_id varchar(64) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			response_time int UNSIGNED DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY session_id (session_id),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $chat_logs_sql );
		dbDelta( $search_logs_sql );
	}

	/**
	 * Log a chat interaction
	 *
	 * @param string $question User question
	 * @param string $answer AI answer
	 * @param array $sources Source documents
	 * @param int $response_time Response time in milliseconds
	 * @return int|false Insert ID on success, false on failure
	 */
	public static function log_chat( $question, $answer, $sources = array(), $response_time = null ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ai_chat_logs';

		$data = array(
			'question'      => wp_kses_post( $question ),
			'answer'        => wp_kses_post( $answer ),
			'sources'       => wp_json_encode( $sources ),
			'user_id'       => get_current_user_id(),
			'session_id'    => self::get_session_id(),
			'ip_address'    => self::get_client_ip(),
			'user_agent'    => self::get_user_agent(),
			'response_time' => $response_time,
			'created_at'    => current_time( 'mysql' ),
		);

		$format = array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s' );

		$result = $wpdb->insert( $table, $data, $format );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Log a search query
	 *
	 * @param string $query Search query
	 * @param array $results Search results
	 * @param int $response_time Response time in milliseconds
	 * @return int|false Insert ID on success, false on failure
	 */
	public static function log_search( $query, $results = array(), $response_time = null ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ai_search_logs';

		$data = array(
			'query'          => wp_kses_post( $query ),
			'results_count'  => count( $results ),
			'results'        => wp_json_encode( $results ),
			'user_id'        => get_current_user_id(),
			'session_id'     => self::get_session_id(),
			'ip_address'     => self::get_client_ip(),
			'user_agent'     => self::get_user_agent(),
			'response_time'  => $response_time,
			'created_at'     => current_time( 'mysql' ),
		);

		$format = array( '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s' );

		$result = $wpdb->insert( $table, $data, $format );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get chat logs with pagination
	 *
	 * @param int $page Page number (1-indexed)
	 * @param int $per_page Items per page
	 * @param array $args Optional query arguments
	 * @return array Array of log entries
	 */
	public static function get_chat_logs( $page = 1, $per_page = 20, $args = array() ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ai_chat_logs';
		$offset = ( $page - 1 ) * $per_page;

		$where = array( '1=1' );
		$where_values = array();

		// Filter by user ID
		if ( ! empty( $args['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$where_values[] = $args['user_id'];
		}

		// Filter by date range
		if ( ! empty( $args['start_date'] ) ) {
			$where[] = 'created_at >= %s';
			$where_values[] = $args['start_date'];
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where[] = 'created_at <= %s';
			$where_values[] = $args['end_date'];
		}

		// Build query
		$where_clause = implode( ' AND ', $where );

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				array_merge( $where_values, array( $per_page, $offset ) )
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			);
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		// Decode JSON fields
		foreach ( $results as &$result ) {
			$result['sources'] = json_decode( $result['sources'], true );
		}

		return $results;
	}

	/**
	 * Get search logs with pagination
	 *
	 * @param int $page Page number (1-indexed)
	 * @param int $per_page Items per page
	 * @param array $args Optional query arguments
	 * @return array Array of log entries
	 */
	public static function get_search_logs( $page = 1, $per_page = 20, $args = array() ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ai_search_logs';
		$offset = ( $page - 1 ) * $per_page;

		$where = array( '1=1' );
		$where_values = array();

		// Filter by user ID
		if ( ! empty( $args['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$where_values[] = $args['user_id'];
		}

		// Filter by date range
		if ( ! empty( $args['start_date'] ) ) {
			$where[] = 'created_at >= %s';
			$where_values[] = $args['start_date'];
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where[] = 'created_at <= %s';
			$where_values[] = $args['end_date'];
		}

		// Build query
		$where_clause = implode( ' AND ', $where );

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				array_merge( $where_values, array( $per_page, $offset ) )
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			);
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		// Decode JSON fields
		foreach ( $results as &$result ) {
			$result['results'] = json_decode( $result['results'], true );
		}

		return $results;
	}

	/**
	 * Get total count of chat logs
	 *
	 * @param array $args Optional query arguments
	 * @return int Total count
	 */
	public static function get_chat_logs_count( $args = array() ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ai_chat_logs';

		$where = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$where_values[] = $args['user_id'];
		}

		if ( ! empty( $args['start_date'] ) ) {
			$where[] = 'created_at >= %s';
			$where_values[] = $args['start_date'];
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where[] = 'created_at <= %s';
			$where_values[] = $args['end_date'];
		}

		$where_clause = implode( ' AND ', $where );

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE {$where_clause}",
				$where_values
			);
		} else {
			$query = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
		}

		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Get total count of search logs
	 *
	 * @param array $args Optional query arguments
	 * @return int Total count
	 */
	public static function get_search_logs_count( $args = array() ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ai_search_logs';

		$where = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$where_values[] = $args['user_id'];
		}

		if ( ! empty( $args['start_date'] ) ) {
			$where[] = 'created_at >= %s';
			$where_values[] = $args['start_date'];
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where[] = 'created_at <= %s';
			$where_values[] = $args['end_date'];
		}

		$where_clause = implode( ' AND ', $where );

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE {$where_clause}",
				$where_values
			);
		} else {
			$query = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
		}

		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Delete old logs (cleanup)
	 *
	 * @param int $days_to_keep Number of days to keep logs
	 * @return array Array with counts of deleted rows
	 */
	public static function cleanup_old_logs( $days_to_keep = 90 ) {
		global $wpdb;

		$chat_table = $wpdb->prefix . 'ai_chat_logs';
		$search_table = $wpdb->prefix . 'ai_search_logs';

		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days_to_keep} days" ) );

		// Delete old chat logs
		$chat_deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$chat_table} WHERE created_at < %s",
				$cutoff_date
			)
		);

		// Delete old search logs
		$search_deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$search_table} WHERE created_at < %s",
				$cutoff_date
			)
		);

		return array(
			'chat_logs_deleted'   => $chat_deleted,
			'search_logs_deleted' => $search_deleted,
		);
	}

	/**
	 * Get session ID for current user
	 *
	 * @return string Session ID
	 */
	private static function get_session_id() {
		if ( ! session_id() ) {
			return md5( wp_get_session_token() );
		}
		return session_id();
	}

	/**
	 * Get client IP address
	 *
	 * @return string IP address
	 */
	private static function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * Get user agent string
	 *
	 * @return string User agent
	 */
	private static function get_user_agent() {
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 );
		}
		return '';
	}

	/**
	 * Get database statistics
	 *
	 * @return array Statistics array
	 */
	public static function get_stats() {
		global $wpdb;

		$chat_table = $wpdb->prefix . 'ai_chat_logs';
		$search_table = $wpdb->prefix . 'ai_search_logs';

		$stats = array(
			'total_chat_logs'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$chat_table}" ),
			'total_search_logs' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$search_table}" ),
			'chat_logs_today'   => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$chat_table} WHERE created_at >= %s",
					date( 'Y-m-d 00:00:00' )
				)
			),
			'search_logs_today' => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$search_table} WHERE created_at >= %s",
					date( 'Y-m-d 00:00:00' )
				)
			),
		);

		return $stats;
	}
}
