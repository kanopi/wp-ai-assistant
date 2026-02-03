<?php
/**
 * Tests for WP_AI_Migration data retention features
 *
 * @package WP_AI_Assistant
 */

namespace WP_AI_Tests\Unit\Migration;

use WP_AI_Tests\Helpers\TestCase;
use WP_AI_Migration;
use WP_AI_Logger;

class RetentionTest extends TestCase {

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * Test cleanup_old_conversation_logs removes old conversations
	 */
	public function test_cleanup_old_conversation_logs() {
		// Mock old conversations
		$old_post_ids = array( 1, 2, 3 );

		\WP_Mock::userFunction( 'apply_filters' )
			->with( 'wp_ai_conversation_retention_days', 90 )
			->andReturn( 90 );

		\WP_Mock::userFunction( 'gmdate' )
			->andReturn( '2026-01-28 12:00:00' );

		\WP_Mock::userFunction( 'get_posts' )
			->andReturn( $old_post_ids );

		\WP_Mock::userFunction( 'wp_delete_post' )
			->times( 3 )
			->andReturn( true );

		$deleted = WP_AI_Migration::cleanup_old_conversation_logs( 90 );

		$this->assertEquals( 3, $deleted );
	}

	/**
	 * Test cleanup_old_conversation_logs with custom retention period
	 */
	public function test_cleanup_old_conversation_logs_custom_period() {
		\WP_Mock::userFunction( 'gmdate' )
			->andReturn( '2026-01-28 12:00:00' );

		\WP_Mock::userFunction( 'get_posts' )
			->andReturn( array() );

		$deleted = WP_AI_Migration::cleanup_old_conversation_logs( 30 );

		// Even with no posts, should complete without error
		$this->assertEquals( 0, $deleted );
	}

	/**
	 * Test cleanup_old_settings_backups
	 */
	public function test_cleanup_old_settings_backups() {
		$old_time = gmdate( 'Y-m-d H:i:s', strtotime( '-60 days' ) );

		\WP_Mock::userFunction( 'apply_filters' )
			->with( 'wp_ai_backup_retention_days', 30 )
			->andReturn( 30 );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'wp_ai_chatbot_settings_backup_time' )
			->andReturn( $old_time );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'wp_ai_search_settings_backup_time' )
			->andReturn( $old_time );

		\WP_Mock::userFunction( 'delete_option' )
			->times( 4 ) // 2 backups + 2 timestamps
			->andReturn( true );

		$result = WP_AI_Migration::cleanup_old_settings_backups( 30 );

		$this->assertTrue( $result );
	}

	/**
	 * Test cleanup doesn't remove recent backups
	 */
	public function test_cleanup_preserves_recent_backups() {
		$recent_time = gmdate( 'Y-m-d H:i:s', strtotime( '-10 days' ) );

		\WP_Mock::userFunction( 'apply_filters' )
			->with( 'wp_ai_backup_retention_days', 30 )
			->andReturn( 30 );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'wp_ai_chatbot_settings_backup_time' )
			->andReturn( $recent_time );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'wp_ai_search_settings_backup_time' )
			->andReturn( $recent_time );

		// Should NOT call delete_option for recent backups
		\WP_Mock::userFunction( 'delete_option' )
			->never();

		$result = WP_AI_Migration::cleanup_old_settings_backups( 30 );

		$this->assertTrue( $result );
	}

	/**
	 * Test get_retention_stats
	 */
	public function test_get_retention_stats() {
		global $wpdb;
		$wpdb = \Mockery::mock( '\WPDB' );

		// Mock conversation count
		$count_obj = new \stdClass();
		$count_obj->publish = 100;

		\WP_Mock::userFunction( 'wp_count_posts' )
			->with( 'wp_ai_conversation' )
			->andReturn( $count_obj );

		// Mock oldest conversation
		$oldest_post = new \stdClass();
		$oldest_post->post_date = '2025-10-01 12:00:00';

		\WP_Mock::userFunction( 'get_posts' )
			->andReturn( array( 123 ) );

		\WP_Mock::userFunction( 'get_post' )
			->with( 123 )
			->andReturn( $oldest_post );

		// Mock backups
		\WP_Mock::userFunction( 'get_option' )
			->with( 'wp_ai_chatbot_settings_backup' )
			->andReturn( array( 'data' => 'test' ) );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'wp_ai_search_settings_backup' )
			->andReturn( false );

		// Mock transient count
		$wpdb->shouldReceive( 'get_var' )
			->andReturn( 15 );

		$wpdb->shouldReceive( 'prepare' )
			->andReturn( 'SELECT COUNT(*) FROM wp_options WHERE option_name LIKE \'_transient_wp_ai_%\'' );

		\WP_Mock::userFunction( 'apply_filters' )
			->with( 'wp_ai_conversation_retention_days', 90 )
			->andReturn( 90 );

		\WP_Mock::userFunction( 'apply_filters' )
			->with( 'wp_ai_backup_retention_days', 30 )
			->andReturn( 30 );

		$stats = WP_AI_Migration::get_retention_stats();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'conversations', $stats );
		$this->assertArrayHasKey( 'backups', $stats );
		$this->assertArrayHasKey( 'transients', $stats );
		$this->assertArrayHasKey( 'policies', $stats );

		$this->assertEquals( 100, $stats['conversations']['total'] );
		$this->assertEquals( 15, $stats['transients'] );
		$this->assertEquals( 90, $stats['policies']['conversation_retention_days'] );
		$this->assertEquals( 30, $stats['policies']['backup_retention_days'] );
	}

	/**
	 * Test manual_cleanup executes all cleanup tasks
	 */
	public function test_manual_cleanup() {
		\WP_Mock::userFunction( 'apply_filters' )
			->andReturn( 90 );

		\WP_Mock::userFunction( 'gmdate' )
			->andReturn( '2026-01-28 12:00:00' );

		\WP_Mock::userFunction( 'get_posts' )
			->andReturn( array( 1, 2 ) );

		\WP_Mock::userFunction( 'wp_delete_post' )
			->andReturn( true );

		\WP_Mock::userFunction( 'get_option' )
			->andReturn( false );

		global $wpdb;
		$wpdb = \Mockery::mock( '\WPDB' );
		$wpdb->shouldReceive( 'query' )->andReturn( 5 );
		$wpdb->shouldReceive( 'prepare' )->andReturn( 'query' );
		$wpdb->options = 'wp_options';

		$results = WP_AI_Migration::manual_cleanup();

		$this->assertIsArray( $results );
		$this->assertArrayHasKey( 'conversations_deleted', $results );
		$this->assertArrayHasKey( 'backups_deleted', $results );
		$this->assertArrayHasKey( 'transients_deleted', $results );
	}

	/**
	 * Test init_retention_policies schedules cron job
	 */
	public function test_init_retention_policies() {
		\WP_Mock::userFunction( 'wp_next_scheduled' )
			->with( 'wp_ai_daily_cleanup' )
			->andReturn( false );

		\WP_Mock::userFunction( 'wp_schedule_event' )
			->once()
			->andReturn( true );

		\WP_Mock::expectActionAdded( 'wp_ai_daily_cleanup', array( 'WP_AI_Migration', 'run_daily_cleanup' ) );

		WP_AI_Migration::init_retention_policies();
	}

	/**
	 * Test unschedule_cleanup removes cron job
	 */
	public function test_unschedule_cleanup() {
		$timestamp = time();

		\WP_Mock::userFunction( 'wp_next_scheduled' )
			->with( 'wp_ai_daily_cleanup' )
			->andReturn( $timestamp );

		\WP_Mock::userFunction( 'wp_unschedule_event' )
			->with( $timestamp, 'wp_ai_daily_cleanup' )
			->once()
			->andReturn( true );

		WP_AI_Migration::unschedule_cleanup();
	}

	/**
	 * Test run_daily_cleanup executes all tasks
	 */
	public function test_run_daily_cleanup() {
		\WP_Mock::userFunction( 'apply_filters' )
			->andReturn( 90 );

		\WP_Mock::userFunction( 'gmdate' )
			->andReturn( '2026-01-28 12:00:00' );

		\WP_Mock::userFunction( 'get_posts' )
			->andReturn( array() );

		\WP_Mock::userFunction( 'get_option' )
			->andReturn( false );

		global $wpdb;
		$wpdb = \Mockery::mock( '\WPDB' );
		$wpdb->shouldReceive( 'query' )->andReturn( 0 );
		$wpdb->shouldReceive( 'prepare' )->andReturn( 'query' );
		$wpdb->options = 'wp_options';

		// Should complete without throwing exception
		WP_AI_Migration::run_daily_cleanup();

		$this->assertTrue( true );
	}

	/**
	 * Test cleanup respects filter for retention period
	 */
	public function test_cleanup_respects_retention_filter() {
		\WP_Mock::userFunction( 'apply_filters' )
			->with( 'wp_ai_conversation_retention_days', 90 )
			->andReturn( 180 ); // Override to 180 days

		\WP_Mock::userFunction( 'gmdate' )
			->andReturn( '2026-01-28 12:00:00' );

		\WP_Mock::userFunction( 'get_posts' )
			->andReturn( array() );

		// Should use the filtered value (180 days)
		$deleted = WP_AI_Migration::cleanup_old_conversation_logs();

		$this->assertEquals( 0, $deleted );
	}
}
