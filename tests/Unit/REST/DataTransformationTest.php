<?php
/**
 * Tests for WP_AI_Indexer_Settings_Controller data transformation logic
 */

namespace WP_AI_Tests\Unit\REST;

use WP_AI_Tests\Helpers\TestCase;
use WP_Mock;
use Mockery;

class DataTransformationTest extends TestCase {
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
     * Test post types parsing from comma-separated string
     */
    public function testPostTypesParsingFromString() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => 'post, page, custom-type',
            'post_types_exclude' => 'attachment, revision',
            'auto_discover' => true,
            'clean_deleted' => true,
            'embedding_model' => 'text-embedding-3-small',
            'embedding_dimension' => 1536,
            'chunk_size' => 1200,
            'chunk_overlap' => 200,
            'pinecone_index_host' => 'https://test-index.pinecone.io',
            'pinecone_index_name' => 'test-index',
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

        $this->assertIsArray($response);
        $this->assertEquals(['post', 'page', 'custom-type'], $response['post_types']);
        $this->assertEquals(['attachment', 'revision'], $response['post_types_exclude']);
    }

    /**
     * Test post types parsing from array
     */
    public function testPostTypesParsingFromArray() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => ['post', 'page'],
            'post_types_exclude' => ['attachment'],
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

        $this->assertEquals(['post', 'page'], $response['post_types']);
        $this->assertEquals(['attachment'], $response['post_types_exclude']);
    }

    /**
     * Test boolean coercion for auto_discover and clean_deleted
     */
    public function testBooleanCoercion() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => 'post',
            'auto_discover' => 1, // Truthy value
            'clean_deleted' => 0, // Falsy value
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

        $this->assertTrue($response['auto_discover']);
        $this->assertFalse($response['clean_deleted']);
        $this->assertIsBool($response['auto_discover']);
        $this->assertIsBool($response['clean_deleted']);
    }

    /**
     * Test integer coercion for dimensions and chunk sizes
     */
    public function testIntegerCoercion() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => 'post',
            'auto_discover' => true,
            'clean_deleted' => true,
            'embedding_model' => 'text-embedding-3-small',
            'embedding_dimension' => '1536', // String should be cast to int
            'chunk_size' => '1200',
            'chunk_overlap' => '200',
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

        $this->assertIsInt($response['embedding_dimension']);
        $this->assertIsInt($response['chunk_size']);
        $this->assertIsInt($response['chunk_overlap']);
        $this->assertEquals(1536, $response['embedding_dimension']);
        $this->assertEquals(1200, $response['chunk_size']);
        $this->assertEquals(200, $response['chunk_overlap']);
    }

    /**
     * Test domain extraction from home_url
     */
    public function testDomainExtraction() {
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

        // Test with various URL formats
        WP_Mock::userFunction('home_url')
            ->once()
            ->andReturn('https://subdomain.example.com:8080/path');

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

        $this->assertEquals('subdomain.example.com', $response['domain']);
    }

    /**
     * Test empty post types after filtering
     */
    public function testEmptyPostTypesAfterFiltering() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => '  ,  , ',  // Only whitespace and commas
            'post_types_exclude' => '',
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

        $this->assertIsArray($response['post_types']);
        $this->assertEmpty($response['post_types']);
    }

    /**
     * Test schema_version is always 1
     */
    public function testSchemaVersionConsistency() {
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

        $this->assertIsInt($response['schema_version']);
        $this->assertEquals(1, $response['schema_version']);
    }

    /**
     * Test all required fields are present in response
     */
    public function testAllRequiredFieldsPresent() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('wp_ai_assistant_settings', [
            'post_types' => 'post',
            'post_types_exclude' => 'attachment',
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

        $requiredFields = [
            'schema_version',
            'domain',
            'post_types',
            'post_types_exclude',
            'auto_discover',
            'clean_deleted',
            'embedding_model',
            'embedding_dimension',
            'chunk_size',
            'chunk_overlap',
            'pinecone_index_host',
            'pinecone_index_name',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $response, "Missing field: $field");
        }
    }

    /**
     * Test default values are merged when settings are empty
     */
    public function testDefaultValuesMerging() {
        $request = Mockery::mock('WP_REST_Request');

        // Empty settings, should use defaults
        $this->mockGetOption('wp_ai_assistant_settings', [
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

        // Check defaults are applied
        $this->assertEquals('text-embedding-3-small', $response['embedding_model']);
        $this->assertEquals(1536, $response['embedding_dimension']);
        $this->assertEquals(1200, $response['chunk_size']);
        $this->assertEquals(200, $response['chunk_overlap']);
    }
}
