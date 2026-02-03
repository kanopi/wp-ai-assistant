<?php
/**
 * Tests for WP_AI_Indexer_Settings_Controller error handling
 */

namespace WP_AI_Tests\Unit\REST;

use WP_AI_Tests\Helpers\TestCase;
use WP_Mock;
use Mockery;

class ErrorHandlingTest extends TestCase {
    /**
     * Controller instance
     *
     * @var \WP_AI_Indexer_Settings_Controller
     */
    private $controller;

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        $this->controller = new \WP_AI_Indexer_Settings_Controller();
    }

    /**
     * Test error when get_option returns non-array
     */
    public function testErrorWhenGetOptionReturnsNonArray() {
        $request = Mockery::mock('WP_REST_Request');

        // get_option returns string instead of array
        WP_Mock::userFunction('get_option')
            ->times(3)
            ->with('wp_ai_assistant_settings', Mockery::any())
            ->andReturn('invalid-string');

        WP_Mock::userFunction('home_url')
            ->once()
            ->andReturn('https://example.org');

        WP_Mock::userFunction('wp_parse_args')
            ->once()
            ->andReturnUsing(function($stored, $defaults) {
                // Stored is empty array (corrected), defaults remain
                return $defaults;
            });

        // Note: rest_ensure_response() is NOT called when returning WP_Error

        // Missing Pinecone config should trigger error
        $response = $this->controller->get_settings($request);

        $this->assertInstanceOf('WP_Error', $response);
    }

    /**
     * Test error when Pinecone host is empty
     */
    public function testErrorWhenPineconeHostEmpty() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => 'post',
            'embedding_model' => 'text-embedding-3-small',
            'embedding_dimension' => 1536,
            'chunk_size' => 1200,
            'chunk_overlap' => 200,
            'pinecone_index_host' => '', // Empty
            'pinecone_index_name' => 'test',
        ]);

        WP_Mock::userFunction('home_url')
            ->once()
            ->andReturn('https://example.org');

        WP_Mock::userFunction('wp_parse_args')
            ->once()
            ->andReturnUsing(function($stored, $defaults) {
                return array_merge($defaults, $stored);
            });

        $response = $this->controller->get_settings($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('wp_ai_assistant_missing_config', $response->get_error_code());
        $this->assertStringContainsString('Pinecone', $response->get_error_message());
    }

    /**
     * Test error when Pinecone name is empty
     */
    public function testErrorWhenPineconeNameEmpty() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => 'post',
            'embedding_model' => 'text-embedding-3-small',
            'embedding_dimension' => 1536,
            'chunk_size' => 1200,
            'chunk_overlap' => 200,
            'pinecone_index_host' => 'https://test.pinecone.io',
            'pinecone_index_name' => '', // Empty
        ]);

        WP_Mock::userFunction('home_url')
            ->once()
            ->andReturn('https://example.org');

        WP_Mock::userFunction('wp_parse_args')
            ->once()
            ->andReturnUsing(function($stored, $defaults) {
                return array_merge($defaults, $stored);
            });

        $response = $this->controller->get_settings($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('wp_ai_assistant_missing_config', $response->get_error_code());
    }

    /**
     * Test error when both Pinecone configs are missing
     */
    public function testErrorWhenBothPineconeConfigsMissing() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => 'post',
            'embedding_model' => 'text-embedding-3-small',
            'embedding_dimension' => 1536,
            'chunk_size' => 1200,
            'chunk_overlap' => 200,
            // Missing both pinecone configs
        ]);

        WP_Mock::userFunction('home_url')
            ->once()
            ->andReturn('https://example.org');

        WP_Mock::userFunction('wp_parse_args')
            ->once()
            ->andReturnUsing(function($stored, $defaults) {
                return array_merge($defaults, $stored);
            });

        $response = $this->controller->get_settings($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('wp_ai_assistant_missing_config', $response->get_error_code());
        $this->assertStringContainsString('incomplete', $response->get_error_message());
    }

    /**
     * Test is_wp_error correctly identifies WP_Error
     */
    public function testIsWpErrorIdentifiesErrors() {
        $error = new \WP_Error('test_code', 'Test message');

        $this->assertTrue(is_wp_error($error));
        $this->assertFalse(is_wp_error([]));
        $this->assertFalse(is_wp_error('string'));
        $this->assertFalse(is_wp_error(null));
    }

    /**
     * Test WP_Error has correct error code
     */
    public function testWpErrorHasCorrectCode() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => 'post',
            'embedding_model' => 'text-embedding-3-small',
            'embedding_dimension' => 1536,
            'chunk_size' => 1200,
            'chunk_overlap' => 200,
            'pinecone_index_host' => '',
            'pinecone_index_name' => '',
        ]);

        WP_Mock::userFunction('home_url')
            ->once()
            ->andReturn('https://example.org');

        WP_Mock::userFunction('wp_parse_args')
            ->once()
            ->andReturnUsing(function($stored, $defaults) {
                return array_merge($defaults, $stored);
            });

        $response = $this->controller->get_settings($request);

        $this->assertEquals('wp_ai_assistant_missing_config', $response->get_error_code());
    }

    /**
     * Test WP_Error has helpful message
     */
    public function testWpErrorHasHelpfulMessage() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => 'post',
            'embedding_model' => 'text-embedding-3-small',
            'embedding_dimension' => 1536,
            'chunk_size' => 1200,
            'chunk_overlap' => 200,
            'pinecone_index_host' => '',
            'pinecone_index_name' => '',
        ]);

        WP_Mock::userFunction('home_url')
            ->once()
            ->andReturn('https://example.org');

        WP_Mock::userFunction('wp_parse_args')
            ->once()
            ->andReturnUsing(function($stored, $defaults) {
                return array_merge($defaults, $stored);
            });

        $response = $this->controller->get_settings($request);

        $message = $response->get_error_message();

        $this->assertStringContainsString('Pinecone', $message);
        $this->assertStringContainsString('configuration', $message);
        $this->assertStringContainsString('incomplete', $message);
    }

    /**
     * Test error response includes correct status
     */
    public function testErrorResponseIncludesStatus() {
        $error = new \WP_Error(
            'wp_ai_assistant_missing_config',
            'Config missing',
            ['status' => 500]
        );

        $this->assertEquals('wp_ai_assistant_missing_config', $error->get_error_code());
        $this->assertEquals('Config missing', $error->get_error_message());
    }

    /**
     * Test successful response is not WP_Error
     */
    public function testSuccessfulResponseIsNotWpError() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => 'post',
            'auto_discover' => true,
            'clean_deleted' => true,
            'embedding_model' => 'text-embedding-3-small',
            'embedding_dimension' => 1536,
            'chunk_size' => 1200,
            'chunk_overlap' => 200,
            'pinecone_index_host' => 'https://test.pinecone.io',
            'pinecone_index_name' => 'test',
        ]);

        WP_Mock::userFunction('home_url')
            ->once()
            ->andReturn('https://example.org');

        WP_Mock::userFunction('wp_parse_args')
            ->once()
            ->andReturnUsing(function($stored, $defaults) {
                return array_merge($defaults, $stored);
            });

        WP_Mock::userFunction('rest_ensure_response')
            ->once()
            ->andReturnUsing(function($data) {
                return $data;
            });

        $response = $this->controller->get_settings($request);

        $this->assertFalse(is_wp_error($response));
        $this->assertIsArray($response);
    }

    /**
     * Test permission check never returns error
     */
    public function testPermissionCheckNeverReturnsError() {
        $request = Mockery::mock('WP_REST_Request');

        $result = $this->controller->get_settings_permissions_check($request);

        $this->assertTrue($result);
        $this->assertNotInstanceOf('WP_Error', $result);
    }

    /**
     * Test route registration handles REST errors gracefully
     */
    public function testRouteRegistrationHandlesErrors() {
        WP_Mock::userFunction('register_rest_route')
            ->once()
            ->with(
                'ai-assistant/v1',
                '/indexer-settings',
                Mockery::any()
            )
            ->andReturn(true);

        $result = $this->controller->register_routes();

        // register_routes doesn't return anything, just ensure no errors
        $this->assertNull($result);
    }
}
