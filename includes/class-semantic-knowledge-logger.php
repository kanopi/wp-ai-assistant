<?php
/**
 * WP AI Logger
 *
 * Centralized logging system with PII masking and configurable log levels.
 *
 * @package Semantic_Knowledge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger class for Semantic Knowledge
 */
class Semantic_Knowledge_Logger {

	/**
	 * Log levels
	 */
	const EMERGENCY = 'emergency'; // System is unusable
	const ALERT     = 'alert';     // Action must be taken immediately
	const CRITICAL  = 'critical';  // Critical conditions
	const ERROR     = 'error';     // Error conditions
	const WARNING   = 'warning';   // Warning conditions
	const NOTICE    = 'notice';    // Normal but significant condition
	const INFO      = 'info';      // Informational messages
	const DEBUG     = 'debug';     // Debug-level messages

	/**
	 * Singleton instance
	 *
	 * @var WP_AI_Logger
	 */
	private static $instance = null;

	/**
	 * Whether to mask PII in logs
	 *
	 * @var bool
	 */
	private $mask_pii = true;

	/**
	 * Minimum log level
	 *
	 * @var string
	 */
	private $min_level = self::WARNING;

	/**
	 * Log level hierarchy (for comparison)
	 *
	 * @var array
	 */
	private $level_hierarchy = array(
		self::EMERGENCY => 8,
		self::ALERT     => 7,
		self::CRITICAL  => 6,
		self::ERROR     => 5,
		self::WARNING   => 4,
		self::NOTICE    => 3,
		self::INFO      => 2,
		self::DEBUG     => 1,
	);

	/**
	 * Get singleton instance
	 *
	 * @return WP_AI_Logger
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Set minimum log level from settings or constant
		if ( defined( 'WP_AI_LOG_LEVEL' ) ) {
			$this->min_level = WP_AI_LOG_LEVEL;
		}

		// Enable/disable PII masking
		if ( defined( 'WP_AI_MASK_PII' ) ) {
			$this->mask_pii = (bool) WP_AI_MASK_PII;
		}
	}

	/**
	 * Emergency level log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function emergency( $message, $context = array() ) {
		$this->log( self::EMERGENCY, $message, $context );
	}

	/**
	 * Alert level log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function alert( $message, $context = array() ) {
		$this->log( self::ALERT, $message, $context );
	}

	/**
	 * Critical level log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function critical( $message, $context = array() ) {
		$this->log( self::CRITICAL, $message, $context );
	}

	/**
	 * Error level log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function error( $message, $context = array() ) {
		$this->log( self::ERROR, $message, $context );
	}

	/**
	 * Warning level log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function warning( $message, $context = array() ) {
		$this->log( self::WARNING, $message, $context );
	}

	/**
	 * Notice level log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function notice( $message, $context = array() ) {
		$this->log( self::NOTICE, $message, $context );
	}

	/**
	 * Info level log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function info( $message, $context = array() ) {
		$this->log( self::INFO, $message, $context );
	}

	/**
	 * Debug level log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function debug( $message, $context = array() ) {
		$this->log( self::DEBUG, $message, $context );
	}

	/**
	 * Main log method
	 *
	 * @param string $level   Log level
	 * @param string $message Log message
	 * @param array  $context Additional context
	 */
	public function log( $level, $message, $context = array() ) {
		// Check if log level is enabled
		if ( ! $this->should_log( $level ) ) {
			return;
		}

		// Mask PII if enabled
		if ( $this->mask_pii ) {
			$message = $this->mask_pii_in_string( $message );
			$context = $this->mask_pii_in_array( $context );
		}

		// Prepare log entry
		$log_entry = $this->format_log_entry( $level, $message, $context );

		// Write to WordPress debug log if enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( $log_entry );
		}

		// Allow custom log handlers
		do_action( 'semantic_knowledge_log', $level, $message, $context );
		do_action( "semantic_knowledge_log_{$level}", $message, $context );
	}

	/**
	 * Check if a message should be logged based on level
	 *
	 * @param string $level Log level
	 * @return bool
	 */
	private function should_log( $level ) {
		if ( ! isset( $this->level_hierarchy[ $level ] ) || ! isset( $this->level_hierarchy[ $this->min_level ] ) ) {
			return false;
		}

		return $this->level_hierarchy[ $level ] >= $this->level_hierarchy[ $this->min_level ];
	}

	/**
	 * Format log entry
	 *
	 * @param string $level   Log level
	 * @param string $message Log message
	 * @param array  $context Additional context
	 * @return string
	 */
	private function format_log_entry( $level, $message, $context ) {
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$level_upper = strtoupper( $level );

		$log_entry = "[{$timestamp}] WP_AI_ASSISTANT.{$level_upper}: {$message}";

		if ( ! empty( $context ) ) {
			$log_entry .= ' | Context: ' . wp_json_encode( $context, JSON_UNESCAPED_UNICODE );
		}

		return $log_entry;
	}

	/**
	 * Mask PII in a string
	 *
	 * @param string $text Text to mask
	 * @return string
	 */
	private function mask_pii_in_string( $text ) {
		// Email addresses
		$text = preg_replace(
			'/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',
			'[EMAIL_REDACTED]',
			$text
		);

		// IP addresses (IPv4 and IPv6)
		$text = preg_replace(
			'/\b(?:\d{1,3}\.){3}\d{1,3}\b/',
			'[IP_REDACTED]',
			$text
		);
		$text = preg_replace(
			'/\b(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}\b/',
			'[IP_REDACTED]',
			$text
		);

		// Credit card numbers (basic pattern)
		$text = preg_replace(
			'/\b(?:\d{4}[-\s]?){3}\d{4}\b/',
			'[CARD_REDACTED]',
			$text
		);

		// Social Security Numbers (US format)
		$text = preg_replace(
			'/\b\d{3}-\d{2}-\d{4}\b/',
			'[SSN_REDACTED]',
			$text
		);

		// Phone numbers (various formats)
		$text = preg_replace(
			'/\b(?:\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}\b/',
			'[PHONE_REDACTED]',
			$text
		);

		return apply_filters( 'semantic_knowledge_mask_pii_string', $text );
	}

	/**
	 * Mask PII in an array
	 *
	 * @param array $data Data to mask
	 * @return array
	 */
	private function mask_pii_in_array( $data ) {
		// PII field names to mask
		$pii_fields = array(
			'email',
			'user_email',
			'email_address',
			'ip',
			'ip_address',
			'phone',
			'phone_number',
			'ssn',
			'credit_card',
			'card_number',
			'password',
			'api_key',
			'secret',
			'token',
			'address',
			'street',
			'city',
			'zip',
			'postal_code',
			'user_login',
			'username',
		);

		foreach ( $data as $key => $value ) {
			$key_lower = strtolower( $key );

			// Check if key is a PII field
			foreach ( $pii_fields as $pii_field ) {
				if ( false !== strpos( $key_lower, $pii_field ) ) {
					$data[ $key ] = '[REDACTED]';
					continue 2;
				}
			}

			// Recursively mask arrays
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->mask_pii_in_array( $value );
			} elseif ( is_string( $value ) ) {
				$data[ $key ] = $this->mask_pii_in_string( $value );
			}
		}

		return apply_filters( 'semantic_knowledge_mask_pii_array', $data );
	}

	/**
	 * Get recent logs from WordPress debug log
	 *
	 * @param int    $limit Number of log entries to retrieve
	 * @param string $level Filter by log level (optional)
	 * @return array
	 */
	public function get_recent_logs( $limit = 100, $level = null ) {
		$logs = array();

		// Check if debug log file exists
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return $logs;
		}

		$log_file = WP_CONTENT_DIR . '/debug.log';
		if ( ! file_exists( $log_file ) || ! is_readable( $log_file ) ) {
			return $logs;
		}

		// Read last N lines from log file
		$lines = $this->tail_file( $log_file, $limit * 2 ); // Get more lines to account for filtering

		// Parse and filter log entries
		foreach ( $lines as $line ) {
			if ( false === strpos( $line, 'WP_AI_ASSISTANT' ) ) {
				continue;
			}

			// Parse log entry
			preg_match( '/\[(.*?)\] WP_AI_ASSISTANT\.(.*?):\s(.*)/', $line, $matches );
			if ( ! empty( $matches ) ) {
				$entry_level = strtolower( $matches[2] );

				// Filter by level if specified
				if ( $level && $entry_level !== strtolower( $level ) ) {
					continue;
				}

				$logs[] = array(
					'timestamp' => $matches[1],
					'level'     => $entry_level,
					'message'   => $matches[3],
				);

				// Stop when we have enough entries
				if ( count( $logs ) >= $limit ) {
					break;
				}
			}
		}

		return $logs;
	}

	/**
	 * Read last N lines from a file
	 *
	 * @param string $file File path
	 * @param int    $lines Number of lines to read
	 * @return array
	 */
	private function tail_file( $file, $lines = 100 ) {
		$handle = fopen( $file, 'r' );
		if ( ! $handle ) {
			return array();
		}

		$line_buffer = array();
		$position = -1;

		fseek( $handle, $position, SEEK_END );
		$char = fgetc( $handle );

		while ( count( $line_buffer ) < $lines ) {
			// Move back one character
			if ( fseek( $handle, $position--, SEEK_END ) === -1 ) {
				break;
			}

			$char = fgetc( $handle );

			if ( "\n" === $char ) {
				$line_buffer[] = strrev( $current_line );
				$current_line = '';
			} else {
				$current_line .= $char;
			}
		}

		fclose( $handle );

		return array_reverse( $line_buffer );
	}

	/**
	 * Clear old log entries (implement via WP-Cron)
	 *
	 * @param int $days Number of days to keep logs
	 */
	public function cleanup_old_logs( $days = 30 ) {
		// This would be implemented based on the log storage method
		// For file-based logs, could rotate/truncate the debug.log
		// For database-based logs, delete old entries
		do_action( 'semantic_knowledge_cleanup_logs', $days );
	}
}
