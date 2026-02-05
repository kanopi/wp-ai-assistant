<?php
/**
 * Tests for Semantic_Knowledge_Logger class
 *
 * @package Semantic_Knowledge_Assistant
 */

namespace Semantic_Knowledge_Tests\Unit\Logger;

use Semantic_Knowledge_Tests\Helpers\TestCase;
use Semantic_Knowledge_Logger;

class LoggerTest extends TestCase {

	/**
	 * Logger instance
	 *
	 * @var Semantic_Knowledge_Logger
	 */
	private $logger;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		$this->logger = Semantic_Knowledge_Logger::instance();
	}

	/**
	 * Test logger singleton instance
	 */
	public function test_singleton_instance() {
		$instance1 = Semantic_Knowledge_Logger::instance();
		$instance2 = Semantic_Knowledge_Logger::instance();

		$this->assertSame( $instance1, $instance2, 'Logger should return same instance' );
	}

	/**
	 * Test PII masking in strings
	 */
	public function test_masks_email_addresses() {
		$reflection = new \ReflectionClass( $this->logger );
		$method = $reflection->getMethod( 'mask_pii_in_string' );
		$method->setAccessible( true );

		$text = 'Contact us at test@example.com for help';
		$masked = $method->invoke( $this->logger, $text );

		$this->assertStringContainsString( '[EMAIL_REDACTED]', $masked );
		$this->assertStringNotContainsString( 'test@example.com', $masked );
	}

	/**
	 * Test masking IP addresses
	 */
	public function test_masks_ip_addresses() {
		$reflection = new \ReflectionClass( $this->logger );
		$method = $reflection->getMethod( 'mask_pii_in_string' );
		$method->setAccessible( true );

		$text = 'Request from 192.168.1.1 was blocked';
		$masked = $method->invoke( $this->logger, $text );

		$this->assertStringContainsString( '[IP_REDACTED]', $masked );
		$this->assertStringNotContainsString( '192.168.1.1', $masked );
	}

	/**
	 * Test masking phone numbers
	 */
	public function test_masks_phone_numbers() {
		$reflection = new \ReflectionClass( $this->logger );
		$method = $reflection->getMethod( 'mask_pii_in_string' );
		$method->setAccessible( true );

		$text = 'Call me at 555-123-4567';
		$masked = $method->invoke( $this->logger, $text );

		$this->assertStringContainsString( '[PHONE_REDACTED]', $masked );
		$this->assertStringNotContainsString( '555-123-4567', $masked );
	}

	/**
	 * Test masking credit card numbers
	 */
	public function test_masks_credit_cards() {
		$reflection = new \ReflectionClass( $this->logger );
		$method = $reflection->getMethod( 'mask_pii_in_string' );
		$method->setAccessible( true );

		$text = 'Card number: 4532-1234-5678-9010';
		$masked = $method->invoke( $this->logger, $text );

		$this->assertStringContainsString( '[CARD_REDACTED]', $masked );
		$this->assertStringNotContainsString( '4532-1234-5678-9010', $masked );
	}

	/**
	 * Test masking PII fields in arrays
	 */
	public function test_masks_pii_fields_in_arrays() {
		$reflection = new \ReflectionClass( $this->logger );
		$method = $reflection->getMethod( 'mask_pii_in_array' );
		$method->setAccessible( true );

		$data = array(
			'name'       => 'John Doe',
			'email'      => 'john@example.com',
			'ip_address' => '192.168.1.1',
			'password'   => 'secret123',
			'public_data' => 'This is safe',
		);

		$masked = $method->invoke( $this->logger, $data );

		$this->assertEquals( '[REDACTED]', $masked['email'] );
		$this->assertEquals( '[REDACTED]', $masked['ip_address'] );
		$this->assertEquals( '[REDACTED]', $masked['password'] );
		$this->assertEquals( 'This is safe', $masked['public_data'] );
	}

	/**
	 * Test log level hierarchy
	 */
	public function test_log_level_hierarchy() {
		$reflection = new \ReflectionClass( $this->logger );
		$method = $reflection->getMethod( 'should_log' );
		$method->setAccessible( true );

		// Set minimum level to WARNING via reflection
		$prop = $reflection->getProperty( 'min_level' );
		$prop->setAccessible( true );
		$prop->setValue( $this->logger, Semantic_Knowledge_Logger::WARNING );

		// Should log WARNING and above
		$this->assertTrue( $method->invoke( $this->logger, Semantic_Knowledge_Logger::EMERGENCY ) );
		$this->assertTrue( $method->invoke( $this->logger, Semantic_Knowledge_Logger::ERROR ) );
		$this->assertTrue( $method->invoke( $this->logger, Semantic_Knowledge_Logger::WARNING ) );

		// Should NOT log below WARNING
		$this->assertFalse( $method->invoke( $this->logger, Semantic_Knowledge_Logger::NOTICE ) );
		$this->assertFalse( $method->invoke( $this->logger, Semantic_Knowledge_Logger::INFO ) );
		$this->assertFalse( $method->invoke( $this->logger, Semantic_Knowledge_Logger::DEBUG ) );
	}

	/**
	 * Test log entry formatting
	 */
	public function test_format_log_entry() {
		$reflection = new \ReflectionClass( $this->logger );
		$method = $reflection->getMethod( 'format_log_entry' );
		$method->setAccessible( true );

		$message = 'Test message';
		$context = array( 'key' => 'value' );

		$formatted = $method->invoke( $this->logger, Semantic_Knowledge_Logger::ERROR, $message, $context );

		$this->assertStringContainsString( 'Semantic_Knowledge_ASSISTANT.ERROR', $formatted );
		$this->assertStringContainsString( 'Test message', $formatted );
		$this->assertStringContainsString( '"key":"value"', $formatted );
	}

	/**
	 * Test logging methods exist
	 */
	public function test_logging_methods_exist() {
		$this->assertTrue( method_exists( $this->logger, 'emergency' ) );
		$this->assertTrue( method_exists( $this->logger, 'alert' ) );
		$this->assertTrue( method_exists( $this->logger, 'critical' ) );
		$this->assertTrue( method_exists( $this->logger, 'error' ) );
		$this->assertTrue( method_exists( $this->logger, 'warning' ) );
		$this->assertTrue( method_exists( $this->logger, 'notice' ) );
		$this->assertTrue( method_exists( $this->logger, 'info' ) );
		$this->assertTrue( method_exists( $this->logger, 'debug' ) );
	}

	/**
	 * Test nested array PII masking
	 */
	public function test_masks_nested_pii_in_arrays() {
		$reflection = new \ReflectionClass( $this->logger );
		$method = $reflection->getMethod( 'mask_pii_in_array' );
		$method->setAccessible( true );

		$data = array(
			'user' => array(
				'name'  => 'John Doe',
				'email' => 'john@example.com',
				'meta'  => array(
					'phone' => '555-1234',
					'city'  => 'New York',
				),
			),
		);

		$masked = $method->invoke( $this->logger, $data );

		$this->assertEquals( '[REDACTED]', $masked['user']['email'] );
		$this->assertEquals( '[REDACTED]', $masked['user']['meta']['phone'] );
	}

	/**
	 * Test PII masking can be filtered
	 */
	public function test_pii_masking_filters() {
		\WP_Mock::onFilter( 'wp_ai_mask_pii_string' )
			->with( 'test@example.com' )
			->reply( '[CUSTOM_REDACTED]' );

		$reflection = new \ReflectionClass( $this->logger );
		$method = $reflection->getMethod( 'mask_pii_in_string' );
		$method->setAccessible( true );

		$masked = $method->invoke( $this->logger, 'test@example.com' );

		$this->assertEquals( '[CUSTOM_REDACTED]', $masked );
	}
}
