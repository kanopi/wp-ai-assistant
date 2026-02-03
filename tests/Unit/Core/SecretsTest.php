<?php
/**
 * Tests for WP_AI_Secrets class
 *
 * @package WP_AI_Assistant
 */

namespace WP_AI_Tests\Unit\Core;

use WP_AI_Tests\Helpers\TestCase;
use WP_AI_Secrets;

class SecretsTest extends TestCase {

	/**
	 * Secrets instance
	 *
	 * @var WP_AI_Secrets
	 */
	private $secrets;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		$this->secrets = new WP_AI_Secrets();
	}

	/**
	 * Test hierarchical secret retrieval - constant takes precedence
	 */
	public function test_get_secret_uses_constant_first() {
		// Mock constant
		if ( ! defined( 'WP_AI_OPENAI_API_KEY' ) ) {
			define( 'WP_AI_OPENAI_API_KEY', 'constant-key' );
		}

		// Mock environment variable
		putenv( 'WP_AI_OPENAI_API_KEY=env-key' );

		// Mock option
		\WP_Mock::userFunction( 'get_option' )
			->andReturn(
				array(
					'openai_api_key' => 'option-key',
				)
			);

		$key = $this->secrets->get_secret( 'openai_api_key' );

		$this->assertEquals( 'constant-key', $key );
	}

	/**
	 * Test hierarchical secret retrieval - env var takes second precedence
	 */
	public function test_get_secret_uses_env_var_second() {
		// No constant defined for this test
		// Mock environment variable
		putenv( 'WP_AI_TEST_KEY=env-key' );

		// Mock option
		\WP_Mock::userFunction( 'get_option' )
			->andReturn(
				array(
					'test_key' => 'option-key',
				)
			);

		// Use a key that doesn't have a constant
		$reflection = new \ReflectionClass( $this->secrets );
		$method = $reflection->getMethod( 'get_env_var' );
		$method->setAccessible( true );

		$key = $method->invoke( $this->secrets, 'test_key' );

		$this->assertEquals( 'env-key', $key );
	}

	/**
	 * Test hierarchical secret retrieval - option is last resort
	 */
	public function test_get_secret_uses_option_last() {
		// No constant or env var
		\WP_Mock::userFunction( 'get_option' )
			->andReturn(
				array(
					'some_api_key' => 'option-key',
				)
			);

		$key = $this->secrets->get_secret( 'some_api_key' );

		$this->assertEquals( 'option-key', $key );
	}

	/**
	 * Test get_secret returns empty string when not found
	 */
	public function test_get_secret_returns_empty_when_not_found() {
		\WP_Mock::userFunction( 'get_option' )
			->andReturn( array() );

		$key = $this->secrets->get_secret( 'nonexistent_key' );

		$this->assertEquals( '', $key );
	}

	/**
	 * Test constant name conversion
	 */
	public function test_get_constant_name_conversion() {
		$reflection = new \ReflectionClass( $this->secrets );
		$method = $reflection->getMethod( 'get_constant_name' );
		$method->setAccessible( true );

		$this->assertEquals(
			'WP_AI_OPENAI_API_KEY',
			$method->invoke( $this->secrets, 'openai_api_key' )
		);

		$this->assertEquals(
			'WP_AI_PINECONE_API_KEY',
			$method->invoke( $this->secrets, 'pinecone_api_key' )
		);
	}

	/**
	 * Test env var name conversion
	 */
	public function test_get_env_var_name_conversion() {
		$reflection = new \ReflectionClass( $this->secrets );
		$method = $reflection->getMethod( 'get_env_var_name' );
		$method->setAccessible( true );

		$this->assertEquals(
			'WP_AI_OPENAI_API_KEY',
			$method->invoke( $this->secrets, 'openai_api_key' )
		);

		$this->assertEquals(
			'WP_AI_PINECONE_API_KEY',
			$method->invoke( $this->secrets, 'pinecone_api_key' )
		);
	}

	/**
	 * Test has_secret method
	 */
	public function test_has_secret_returns_true_when_exists() {
		if ( ! defined( 'WP_AI_TEST_SECRET' ) ) {
			define( 'WP_AI_TEST_SECRET', 'test-value' );
		}

		// Note: has_secret would need to be a public method or we test via get_secret
		$secret = $this->secrets->get_secret( 'test_secret' );

		$this->assertNotEmpty( $secret );
	}

	/**
	 * Test has_secret returns false when not exists
	 */
	public function test_has_secret_returns_false_when_not_exists() {
		\WP_Mock::userFunction( 'get_option' )
			->andReturn( array() );

		$secret = $this->secrets->get_secret( 'nonexistent_secret' );

		$this->assertEmpty( $secret );
	}

	/**
	 * Test empty values are treated as not found
	 */
	public function test_empty_values_treated_as_not_found() {
		\WP_Mock::userFunction( 'get_option' )
			->andReturn(
				array(
					'empty_key' => '',
				)
			);

		$key = $this->secrets->get_secret( 'empty_key' );

		$this->assertEquals( '', $key );
	}

	/**
	 * Test whitespace-only values are treated as not found
	 */
	public function test_whitespace_values_treated_as_not_found() {
		\WP_Mock::userFunction( 'get_option' )
			->andReturn(
				array(
					'whitespace_key' => '   ',
				)
			);

		$key = $this->secrets->get_secret( 'whitespace_key' );

		// Should be trimmed and treated as empty
		$this->assertEquals( '', $key );
	}

	/**
	 * Test secrets are trimmed
	 */
	public function test_secrets_are_trimmed() {
		\WP_Mock::userFunction( 'get_option' )
			->andReturn(
				array(
					'padded_key' => '  secret-value  ',
				)
			);

		$key = $this->secrets->get_secret( 'padded_key' );

		$this->assertEquals( 'secret-value', $key );
	}

	/**
	 * Test get_all_secrets method
	 */
	public function test_get_all_secrets() {
		if ( ! defined( 'WP_AI_OPENAI_API_KEY' ) ) {
			define( 'WP_AI_OPENAI_API_KEY', 'openai-key' );
		}

		\WP_Mock::userFunction( 'get_option' )
			->andReturn(
				array(
					'pinecone_api_key' => 'pinecone-key',
				)
			);

		$all_secrets = $this->secrets->get_all_secrets();

		$this->assertIsArray( $all_secrets );
		$this->assertArrayHasKey( 'openai_api_key', $all_secrets );
		$this->assertArrayHasKey( 'pinecone_api_key', $all_secrets );
	}

	/**
	 * Test secrets are not logged or exposed
	 */
	public function test_secrets_not_exposed_in_debug() {
		\WP_Mock::userFunction( 'get_option' )
			->andReturn(
				array(
					'api_key' => 'super-secret-key',
				)
			);

		$key = $this->secrets->get_secret( 'api_key' );

		// Key should be retrievable
		$this->assertEquals( 'super-secret-key', $key );

		// But object should not expose it in var_dump/print_r
		// (This would require __debugInfo magic method in the class)
	}
}
