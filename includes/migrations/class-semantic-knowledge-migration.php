<?php
/**
 * Migration class for Semantic Knowledge
 *
 * Handles automatic migration from old wp-ai-chatbot and wp-ai-search plugins
 * to the unified wp-ai-assistant plugin.
 *
 * @package Semantic_Knowledge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_AI_Migration
 *
 * Migrates settings from old plugins to the new unified plugin.
 */
class Semantic_Knowledge_Migration {

	/**
	 * Check if migration is needed and run it
	 *
	 * @return void
	 */
	public function maybe_migrate_from_old_plugins() {
		// Check if already migrated
		if ( get_option( 'semantic_knowledge_migrated' ) ) {
			return;
		}

		$has_old_chatbot = $this->is_old_chatbot_active();
		$has_old_search  = $this->is_old_search_active();

		// If neither old plugin exists, mark as clean install
		if ( ! $has_old_chatbot && ! $has_old_search ) {
			update_option( 'semantic_knowledge_migrated', 'clean_install' );
			return;
		}

		// Perform migration
		$new_settings = array();

		// Import chatbot settings
		if ( $has_old_chatbot ) {
			$new_settings = array_merge(
				$new_settings,
				$this->migrate_chatbot_settings()
			);
		}

		// Import search settings
		if ( $has_old_search ) {
			$new_settings = array_merge(
				$new_settings,
				$this->migrate_search_settings()
			);
		}

		// Add meta fields
		$new_settings['version']        = SEMANTIC_KNOWLEDGE_VERSION;
		$new_settings['schema_version'] = SEMANTIC_KNOWLEDGE_SCHEMA_VERSION;

		// Save merged settings
		update_option( WP_AI_Assistant::OPTION_KEY, $new_settings );

		// Deactivate old plugins
		$this->deactivate_old_plugins( $has_old_chatbot, $has_old_search );

		// Mark migration complete
		update_option( 'semantic_knowledge_migrated', current_time( 'mysql' ) );

		// Show admin notice
		add_action( 'admin_notices', array( $this, 'migration_success_notice' ) );
	}

	/**
	 * Check if old chatbot plugin is active
	 *
	 * @return bool
	 */
	private function is_old_chatbot_active() {
		return is_plugin_active( 'wp-ai-chatbot/wp-ai-chatbot.php' );
	}

	/**
	 * Check if old search plugin is active
	 *
	 * @return bool
	 */
	private function is_old_search_active() {
		return is_plugin_active( 'wp-ai-search/wp-ai-search.php' );
	}

	/**
	 * Migrate settings from old chatbot plugin
	 *
	 * @return array Migrated settings
	 */
	private function migrate_chatbot_settings() {
		$chatbot_settings = get_option( 'semantic_knowledge_chatbot_settings', array() );

		if ( empty( $chatbot_settings ) ) {
			return array( 'chatbot_enabled' => false );
		}

		$new_settings = array(
			// Enable chatbot module
			'chatbot_enabled' => true,

			// Chatbot-specific settings
			'chatbot_system_prompt'      => $chatbot_settings['system_prompt'] ?? '',
			'chatbot_model'              => $chatbot_settings['model'] ?? 'gpt-4o-mini',
			'chatbot_temperature'        => $chatbot_settings['temperature'] ?? 0.2,
			'chatbot_top_k'              => $chatbot_settings['pinecone_top_k'] ?? 5,
			'chatbot_floating_button'    => $chatbot_settings['enable_floating_button'] ?? true,
			'chatbot_intro_message'      => $chatbot_settings['intro_message'] ?? '',
			'chatbot_input_placeholder'  => $chatbot_settings['input_placeholder'] ?? 'Ask a question...',
		);

		// Import general settings from chatbot (if not already set)
		if ( ! empty( $chatbot_settings['pinecone_index_host'] ) ) {
			$new_settings['pinecone_index_host'] = $chatbot_settings['pinecone_index_host'];
		}
		if ( ! empty( $chatbot_settings['pinecone_index_name'] ) ) {
			$new_settings['pinecone_index_name'] = $chatbot_settings['pinecone_index_name'];
		}
		if ( ! empty( $chatbot_settings['embedding_model'] ) ) {
			$new_settings['embedding_model'] = $chatbot_settings['embedding_model'];
		}
		if ( ! empty( $chatbot_settings['embedding_dimension'] ) ) {
			$new_settings['embedding_dimension'] = $chatbot_settings['embedding_dimension'];
		}

		return $new_settings;
	}

	/**
	 * Migrate settings from old search plugin
	 *
	 * @return array Migrated settings
	 */
	private function migrate_search_settings() {
		$search_settings = get_option( 'semantic_knowledge_search_settings', array() );

		if ( empty( $search_settings ) ) {
			return array( 'search_enabled' => false );
		}

		$new_settings = array(
			// Enable search module
			'search_enabled' => true,

			// Search-specific settings
			'search_top_k'              => $search_settings['top_k'] ?? 10,
			'search_min_score'          => $search_settings['min_score_threshold'] ?? 0.5,
			'search_replace_default'    => $search_settings['replace_default_search'] ?? true,
			'search_results_per_page'   => $search_settings['results_per_page'] ?? 10,
			'search_placeholder'        => $search_settings['search_placeholder'] ?? 'Search with AI...',
		);

		// Import general settings from search (if not already set from chatbot)
		if ( ! empty( $search_settings['pinecone_index_host'] ) ) {
			$new_settings['pinecone_index_host'] = $search_settings['pinecone_index_host'];
		}
		if ( ! empty( $search_settings['pinecone_index_name'] ) ) {
			$new_settings['pinecone_index_name'] = $search_settings['pinecone_index_name'];
		}
		if ( ! empty( $search_settings['embedding_model'] ) ) {
			$new_settings['embedding_model'] = $search_settings['embedding_model'];
		}
		if ( ! empty( $search_settings['embedding_dimension'] ) ) {
			$new_settings['embedding_dimension'] = $search_settings['embedding_dimension'];
		}

		return $new_settings;
	}

	/**
	 * Deactivate old plugins
	 *
	 * @param bool $has_chatbot Whether chatbot plugin was active.
	 * @param bool $has_search Whether search plugin was active.
	 * @return void
	 */
	private function deactivate_old_plugins( $has_chatbot, $has_search ) {
		$plugins_to_deactivate = array();

		if ( $has_chatbot ) {
			$plugins_to_deactivate[] = 'wp-ai-chatbot/wp-ai-chatbot.php';
		}

		if ( $has_search ) {
			$plugins_to_deactivate[] = 'wp-ai-search/wp-ai-search.php';
		}

		if ( ! empty( $plugins_to_deactivate ) ) {
			deactivate_plugins( $plugins_to_deactivate );
		}
	}

	/**
	 * Display migration success notice
	 *
	 * @return void
	 */
	public function migration_success_notice() {
		$migrated_plugins = array();

		if ( get_option( 'semantic_knowledge_chatbot_settings' ) ) {
			$migrated_plugins[] = 'WP AI Chatbot';
		}
		if ( get_option( 'semantic_knowledge_search_settings' ) ) {
			$migrated_plugins[] = 'WP AI Search';
		}

		if ( empty( $migrated_plugins ) ) {
			return;
		}

		$plugins_list = implode( ' and ', $migrated_plugins );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong>Semantic Knowledge:</strong>
				Successfully migrated settings from <?php echo esc_html( $plugins_list ); ?>.
				Old plugins have been deactivated.
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-ai-assistant' ) ); ?>">
					Review settings
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Check if a migration was performed
	 *
	 * @return bool
	 */
	public function was_migrated() {
		$migrated = get_option( 'semantic_knowledge_migrated', false );
		return $migrated && $migrated !== 'clean_install';
	}

	/**
	 * Get migration timestamp
	 *
	 * @return string|false
	 */
	public function get_migration_time() {
		$migrated = get_option( 'semantic_knowledge_migrated', false );
		if ( $migrated && $migrated !== 'clean_install' ) {
			return $migrated;
		}
		return false;
	}

	/**
	 * Archive old plugin settings (for rollback purposes)
	 *
	 * Creates backup copies of old plugin settings before migration.
	 *
	 * @return void
	 */
	public function archive_old_settings() {
		$current_time = current_time( 'mysql' );

		$chatbot_settings = get_option( 'semantic_knowledge_chatbot_settings' );
		if ( $chatbot_settings ) {
			update_option( 'semantic_knowledge_chatbot_settings_backup', $chatbot_settings );
			update_option( 'semantic_knowledge_chatbot_settings_backup_time', $current_time );
		}

		$search_settings = get_option( 'semantic_knowledge_search_settings' );
		if ( $search_settings ) {
			update_option( 'semantic_knowledge_search_settings_backup', $search_settings );
			update_option( 'semantic_knowledge_search_settings_backup_time', $current_time );
		}
	}

	/**
	 * Restore old plugin settings (for rollback)
	 *
	 * @return bool Success status
	 */
	public function rollback_migration() {
		$success = true;

		// Restore chatbot settings
		$chatbot_backup = get_option( 'semantic_knowledge_chatbot_settings_backup' );
		if ( $chatbot_backup ) {
			update_option( 'semantic_knowledge_chatbot_settings', $chatbot_backup );
		}

		// Restore search settings
		$search_backup = get_option( 'semantic_knowledge_search_settings_backup' );
		if ( $search_backup ) {
			update_option( 'semantic_knowledge_search_settings', $search_backup );
		}

		// Remove migration flag
		delete_option( 'semantic_knowledge_migrated' );

		// Remove new settings
		delete_option( WP_AI_Assistant::OPTION_KEY );

		return $success;
	}

	/**
	 * Initialize data retention policies
	 *
	 * Sets up WP-Cron jobs for automatic cleanup of old data.
	 *
	 * @return void
	 */
	public static function init_retention_policies() {
		// Schedule daily cleanup if not already scheduled
		if ( ! wp_next_scheduled( 'semantic_knowledge_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'semantic_knowledge_daily_cleanup' );
		}

		// Register cleanup hooks
		add_action( 'semantic_knowledge_daily_cleanup', array( __CLASS__, 'run_daily_cleanup' ) );
	}

	/**
	 * Run daily cleanup tasks
	 *
	 * @return void
	 */
	public static function run_daily_cleanup() {
		$logger = WP_AI_Logger::instance();
		$logger->info( 'Starting daily cleanup tasks' );

		try {
			// Clean up old conversation logs
			self::cleanup_old_conversation_logs();

			// Clean up old settings backups
			self::cleanup_old_settings_backups();

			// Clean up expired transients
			self::cleanup_expired_transients();

			$logger->info( 'Daily cleanup tasks completed successfully' );
		} catch ( Exception $e ) {
			$logger->error( 'Daily cleanup failed', array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Clean up old conversation logs
	 *
	 * Removes conversation logs older than the configured retention period.
	 *
	 * @param int $days Number of days to retain (default: 90)
	 * @return int Number of deleted items
	 */
	public static function cleanup_old_conversation_logs( $days = null ) {
		global $wpdb;

		// Get retention period from settings or use default
		if ( null === $days ) {
			$days = apply_filters( 'semantic_knowledge_conversation_retention_days', 90 );
		}

		$logger = WP_AI_Logger::instance();

		// Calculate cutoff date
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// Delete old conversation posts
		$deleted = 0;
		$post_type = 'semantic_knowledge_conversation';

		// Get old conversation posts
		$old_posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'date_query'     => array(
					array(
						'before'    => $cutoff_date,
						'inclusive' => false,
					),
				),
				'fields'         => 'ids',
			)
		);

		// Delete each post
		foreach ( $old_posts as $post_id ) {
			if ( wp_delete_post( $post_id, true ) ) {
				$deleted++;
			}
		}

		if ( $deleted > 0 ) {
			$logger->info(
				"Cleaned up {$deleted} conversation logs older than {$days} days",
				array(
					'deleted_count' => $deleted,
					'retention_days' => $days,
					'cutoff_date'   => $cutoff_date,
				)
			);
		}

		return $deleted;
	}

	/**
	 * Clean up old settings backups
	 *
	 * Removes settings backups older than the configured retention period.
	 *
	 * @param int $days Number of days to retain backups (default: 30)
	 * @return bool Success status
	 */
	public static function cleanup_old_settings_backups( $days = null ) {
		// Get retention period from settings or use default
		if ( null === $days ) {
			$days = apply_filters( 'semantic_knowledge_backup_retention_days', 30 );
		}

		$logger = WP_AI_Logger::instance();

		// Check if backups exist
		$chatbot_backup_time = get_option( 'semantic_knowledge_chatbot_settings_backup_time' );
		$search_backup_time = get_option( 'semantic_knowledge_search_settings_backup_time' );

		$cutoff_timestamp = strtotime( "-{$days} days" );
		$deleted = 0;

		// Clean up chatbot backup if old
		if ( $chatbot_backup_time && strtotime( $chatbot_backup_time ) < $cutoff_timestamp ) {
			delete_option( 'semantic_knowledge_chatbot_settings_backup' );
			delete_option( 'semantic_knowledge_chatbot_settings_backup_time' );
			$deleted++;
		}

		// Clean up search backup if old
		if ( $search_backup_time && strtotime( $search_backup_time ) < $cutoff_timestamp ) {
			delete_option( 'semantic_knowledge_search_settings_backup' );
			delete_option( 'semantic_knowledge_search_settings_backup_time' );
			$deleted++;
		}

		if ( $deleted > 0 ) {
			$logger->info(
				"Cleaned up {$deleted} settings backups older than {$days} days",
				array(
					'deleted_count'  => $deleted,
					'retention_days' => $days,
				)
			);
		}

		return true;
	}

	/**
	 * Clean up expired transients
	 *
	 * Removes expired transients related to Semantic Knowledge.
	 *
	 * @return int Number of deleted transients
	 */
	public static function cleanup_expired_transients() {
		global $wpdb;

		$logger = WP_AI_Logger::instance();

		// Clean up rate limiting transients (these expire automatically, but we can force cleanup)
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				WHERE option_name LIKE %s
				AND option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_wp_ai_' ) . '%',
				'%'
			)
		);

		// Clean up corresponding transient values
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				WHERE option_name LIKE %s
				AND option_name NOT LIKE %s",
				$wpdb->esc_like( '_transient_wp_ai_' ) . '%',
				'%_timeout_%'
			)
		);

		if ( $deleted > 0 ) {
			$logger->debug( "Cleaned up {$deleted} expired transients" );
		}

		return $deleted;
	}

	/**
	 * Get data retention statistics
	 *
	 * Returns information about stored data and retention policies.
	 *
	 * @return array Statistics array
	 */
	public static function get_retention_stats() {
		global $wpdb;

		// Count conversation logs
		$conversation_count = wp_count_posts( 'semantic_knowledge_conversation' );
		$total_conversations = $conversation_count ? $conversation_count->publish : 0;

		// Get oldest conversation
		$oldest_conversation = get_posts(
			array(
				'post_type'      => 'semantic_knowledge_conversation',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		$oldest_date = null;
		if ( ! empty( $oldest_conversation ) ) {
			$oldest_post = get_post( $oldest_conversation[0] );
			$oldest_date = $oldest_post->post_date;
		}

		// Count settings backups
		$backups_exist = array(
			'chatbot' => (bool) get_option( 'semantic_knowledge_chatbot_settings_backup' ),
			'search'  => (bool) get_option( 'semantic_knowledge_search_settings_backup' ),
		);

		// Count transients
		$transient_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options}
				WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_wp_ai_' ) . '%'
			)
		);

		return array(
			'conversations' => array(
				'total'       => $total_conversations,
				'oldest_date' => $oldest_date,
			),
			'backups'       => $backups_exist,
			'transients'    => (int) $transient_count,
			'policies'      => array(
				'conversation_retention_days' => apply_filters( 'semantic_knowledge_conversation_retention_days', 90 ),
				'backup_retention_days'       => apply_filters( 'semantic_knowledge_backup_retention_days', 30 ),
			),
		);
	}

	/**
	 * Manually trigger cleanup (for admin actions)
	 *
	 * @return array Cleanup results
	 */
	public static function manual_cleanup() {
		$results = array(
			'conversations_deleted' => self::cleanup_old_conversation_logs(),
			'backups_deleted'       => self::cleanup_old_settings_backups() ? 1 : 0,
			'transients_deleted'    => self::cleanup_expired_transients(),
		);

		$logger = WP_AI_Logger::instance();
		$logger->info( 'Manual cleanup completed', $results );

		return $results;
	}

	/**
	 * Unschedule cleanup tasks (for deactivation)
	 *
	 * @return void
	 */
	public static function unschedule_cleanup() {
		$timestamp = wp_next_scheduled( 'semantic_knowledge_daily_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'semantic_knowledge_daily_cleanup' );
		}
	}
}
