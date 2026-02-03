<?php
/**
 * Tests for WP_AI_Core validation methods
 *
 * @package WP_AI_Assistant
 */

namespace WP_AI_Tests\Unit\Core;

use WP_AI_Tests\Helpers\TestCase;
use WP_AI_Core;

class CoreValidationTest extends TestCase {

	/**
	 * Core instance
	 *
	 * @var WP_AI_Core
	 */
	private $core;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		$this->core = new WP_AI_Core();

		// Mock get_option to return empty settings
		\WP_Mock::userFunction( 'get_option' )
			->andReturn( array() );
	}

	/**
	 * Test validate_embedding_settings
	 */
	public function test_validate_embedding_settings() {
		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_embedding_settings' );
		$method->setAccessible( true );

		$settings = array(
			'embedding_model'     => 'text-embedding-3-small',
			'embedding_dimension' => 1536,
		);

		$validated = $method->invoke( $this->core, $settings );

		$this->assertEquals( 'text-embedding-3-small', $validated['embedding_model'] );
		$this->assertEquals( 1536, $validated['embedding_dimension'] );
	}

	/**
	 * Test validate_embedding_settings rejects invalid models
	 */
	public function test_validate_embedding_settings_rejects_invalid_model() {
		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_embedding_settings' );
		$method->setAccessible( true );

		$settings = array(
			'embedding_model' => 'invalid-model',
		);

		$validated = $method->invoke( $this->core, $settings );

		$this->assertArrayNotHasKey( 'embedding_model', $validated );
	}

	/**
	 * Test validate_chatbot_settings
	 */
	public function test_validate_chatbot_settings() {
		\WP_Mock::userFunction( 'sanitize_textarea_field' )
			->andReturnFirstArg();
		\WP_Mock::userFunction( 'sanitize_text_field' )
			->andReturnFirstArg();

		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_chatbot_settings' );
		$method->setAccessible( true );

		$settings = array(
			'chatbot_enabled'     => true,
			'chatbot_model'       => 'gpt-4o-mini',
			'chatbot_temperature' => 0.5,
			'chatbot_top_k'       => 5,
		);

		$validated = $method->invoke( $this->core, $settings );

		$this->assertTrue( $validated['chatbot_enabled'] );
		$this->assertEquals( 'gpt-4o-mini', $validated['chatbot_model'] );
		$this->assertEquals( 0.5, $validated['chatbot_temperature'] );
		$this->assertEquals( 5, $validated['chatbot_top_k'] );
	}

	/**
	 * Test validate_chatbot_settings clamps temperature
	 */
	public function test_validate_chatbot_settings_clamps_temperature() {
		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_chatbot_settings' );
		$method->setAccessible( true );

		// Test too high
		$settings = array( 'chatbot_temperature' => 5.0 );
		$validated = $method->invoke( $this->core, $settings );
		$this->assertEquals( 2.0, $validated['chatbot_temperature'] );

		// Test too low
		$settings = array( 'chatbot_temperature' => -1.0 );
		$validated = $method->invoke( $this->core, $settings );
		$this->assertEquals( 0.0, $validated['chatbot_temperature'] );
	}

	/**
	 * Test validate_search_settings
	 */
	public function test_validate_search_settings() {
		\WP_Mock::userFunction( 'sanitize_text_field' )
			->andReturnFirstArg();
		\WP_Mock::userFunction( 'sanitize_textarea_field' )
			->andReturnFirstArg();

		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_search_settings' );
		$method->setAccessible( true );

		$settings = array(
			'search_enabled'    => true,
			'search_top_k'      => 10,
			'search_min_score'  => 0.5,
			'search_placeholder' => 'Search...',
		);

		$validated = $method->invoke( $this->core, $settings );

		$this->assertTrue( $validated['search_enabled'] );
		$this->assertEquals( 10, $validated['search_top_k'] );
		$this->assertEquals( 0.5, $validated['search_min_score'] );
		$this->assertEquals( 'Search...', $validated['search_placeholder'] );
	}

	/**
	 * Test validate_search_settings clamps min_score
	 */
	public function test_validate_search_settings_clamps_min_score() {
		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_search_settings' );
		$method->setAccessible( true );

		// Test too high
		$settings = array( 'search_min_score' => 5.0 );
		$validated = $method->invoke( $this->core, $settings );
		$this->assertEquals( 1.0, $validated['search_min_score'] );

		// Test too low
		$settings = array( 'search_min_score' => -1.0 );
		$validated = $method->invoke( $this->core, $settings );
		$this->assertEquals( 0.0, $validated['search_min_score'] );
	}

	/**
	 * Test validate_relevance_settings
	 */
	public function test_validate_relevance_settings() {
		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_relevance_settings' );
		$method->setAccessible( true );

		$settings = array(
			'search_relevance_enabled'   => true,
			'search_url_boost'           => 0.15,
			'search_title_exact_boost'   => 0.12,
			'search_title_words_boost'   => 0.08,
			'search_page_boost'          => 0.05,
		);

		$validated = $method->invoke( $this->core, $settings );

		$this->assertTrue( $validated['search_relevance_enabled'] );
		$this->assertEquals( 0.15, $validated['search_url_boost'] );
		$this->assertEquals( 0.12, $validated['search_title_exact_boost'] );
		$this->assertEquals( 0.08, $validated['search_title_words_boost'] );
		$this->assertEquals( 0.05, $validated['search_page_boost'] );
	}

	/**
	 * Test validate_relevance_settings clamps boost values
	 */
	public function test_validate_relevance_settings_clamps_boost_values() {
		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_relevance_settings' );
		$method->setAccessible( true );

		$settings = array(
			'search_url_boost' => 5.0,
			'search_page_boost' => -1.0,
		);

		$validated = $method->invoke( $this->core, $settings );

		$this->assertEquals( 1.0, $validated['search_url_boost'] );
		$this->assertEquals( 0.0, $validated['search_page_boost'] );
	}

	/**
	 * Test validate_pinecone_settings
	 */
	public function test_validate_pinecone_settings() {
		\WP_Mock::userFunction( 'esc_url_raw' )
			->andReturnFirstArg();
		\WP_Mock::userFunction( 'sanitize_text_field' )
			->andReturnFirstArg();

		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_pinecone_settings' );
		$method->setAccessible( true );

		$settings = array(
			'pinecone_index_host' => 'https://example.pinecone.io',
			'pinecone_index_name' => 'my-index',
		);

		$validated = $method->invoke( $this->core, $settings );

		$this->assertEquals( 'https://example.pinecone.io', $validated['pinecone_index_host'] );
		$this->assertEquals( 'my-index', $validated['pinecone_index_name'] );
	}

	/**
	 * Test validate_indexer_settings
	 */
	public function test_validate_indexer_settings() {
		\WP_Mock::userFunction( 'sanitize_text_field' )
			->andReturnFirstArg();
		\WP_Mock::userFunction( 'sanitize_textarea_field' )
			->andReturnFirstArg();

		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_indexer_settings' );
		$method->setAccessible( true );

		$settings = array(
			'post_types'         => 'post,page',
			'post_types_exclude' => 'attachment,revision',
			'auto_discover'      => true,
			'clean_deleted'      => true,
			'chunk_size'         => 1200,
			'chunk_overlap'      => 200,
		);

		$validated = $method->invoke( $this->core, $settings );

		$this->assertEquals( 'post,page', $validated['post_types'] );
		$this->assertEquals( 'attachment,revision', $validated['post_types_exclude'] );
		$this->assertTrue( $validated['auto_discover'] );
		$this->assertTrue( $validated['clean_deleted'] );
		$this->assertEquals( 1200, $validated['chunk_size'] );
		$this->assertEquals( 200, $validated['chunk_overlap'] );
	}

	/**
	 * Test validate_indexer_settings clamps chunk_size
	 */
	public function test_validate_indexer_settings_clamps_chunk_size() {
		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_indexer_settings' );
		$method->setAccessible( true );

		// Test too small
		$settings = array( 'chunk_size' => 50 );
		$validated = $method->invoke( $this->core, $settings );
		$this->assertEquals( 100, $validated['chunk_size'] );

		// Test too large
		$settings = array( 'chunk_size' => 50000 );
		$validated = $method->invoke( $this->core, $settings );
		$this->assertEquals( 10000, $validated['chunk_size'] );
	}

	/**
	 * Test validate_indexer_settings clamps chunk_overlap
	 */
	public function test_validate_indexer_settings_clamps_chunk_overlap() {
		$reflection = new \ReflectionClass( $this->core );
		$method = $reflection->getMethod( 'validate_indexer_settings' );
		$method->setAccessible( true );

		// Test negative
		$settings = array( 'chunk_overlap' => -10 );
		$validated = $method->invoke( $this->core, $settings );
		$this->assertEquals( 0, $validated['chunk_overlap'] );

		// Test too large
		$settings = array( 'chunk_overlap' => 5000 );
		$validated = $method->invoke( $this->core, $settings );
		$this->assertEquals( 1000, $validated['chunk_overlap'] );
	}

	/**
	 * Test validate_settings integrates all validators
	 */
	public function test_validate_settings_integrates_all_validators() {
		\WP_Mock::userFunction( 'sanitize_text_field' )
			->andReturnFirstArg();
		\WP_Mock::userFunction( 'sanitize_textarea_field' )
			->andReturnFirstArg();
		\WP_Mock::userFunction( 'esc_url_raw' )
			->andReturnFirstArg();

		$settings = array(
			'embedding_model'    => 'text-embedding-3-small',
			'chatbot_enabled'    => true,
			'search_enabled'     => true,
			'pinecone_index_name' => 'test-index',
			'chunk_size'         => 1200,
		);

		$validated = $this->core->validate_settings( $settings );

		// Should contain values from all validators
		$this->assertEquals( 'text-embedding-3-small', $validated['embedding_model'] );
		$this->assertTrue( $validated['chatbot_enabled'] );
		$this->assertTrue( $validated['search_enabled'] );
		$this->assertEquals( 'test-index', $validated['pinecone_index_name'] );
		$this->assertEquals( 1200, $validated['chunk_size'] );
	}

	/**
	 * Test is_configured method
	 */
	public function test_is_configured() {
		\WP_Mock::userFunction( 'get_option' )
			->andReturn(
				array(
					'pinecone_index_host' => 'https://example.pinecone.io',
					'pinecone_index_name' => 'my-index',
				)
			);

		$core = new WP_AI_Core();
		$this->assertTrue( $core->is_configured() );
	}

	/**
	 * Test is_configured returns false when missing Pinecone config
	 */
	public function test_is_configured_returns_false_when_missing_config() {
		\WP_Mock::userFunction( 'get_option' )
			->andReturn( array() );

		$core = new WP_AI_Core();
		$this->assertFalse( $core->is_configured() );
	}
}
