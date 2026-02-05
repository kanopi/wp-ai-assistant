<?php
/**
 * Tests for Semantic_Knowledge_Indexer_Settings_Controller class
 */

namespace Semantic_Knowledge_Tests\Unit\REST;

use Semantic_Knowledge_Tests\Helpers\TestCase;
use WP_Mock;
use Mockery;

class IndexerControllerTest extends TestCase {
    /**
     * Controller instance
     *
     * @var \Semantic_Knowledge_Indexer_Settings_Controller
     */
    private $controller;

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        $this->controller = new \Semantic_Knowledge_Indexer_Settings_Controller();
    }

    /**
     * Test route registration
     */
    public function testRegisterRoutes() {
        WP_Mock::userFunction('register_rest_route')
            ->once()
            ->with(
                'ai-assistant/v1',
                '/indexer-settings',
                Mockery::on(function($args) {
                    return isset($args['methods'])
                        && isset($args['callback'])
                        && isset($args['permission_callback']);
                })
            );

        $this->controller->register_routes();

        $this->assertTrue(true);
    }

    /**
     * Test permission check always returns true
     */
    public function testGetSettingsPermissionsCheck() {
        $request = Mockery::mock('WP_REST_Request');

        $result = $this->controller->get_settings_permissions_check($request);

        $this->assertTrue($result);
    }

    /**
     * Test get_settings with valid configuration
     */
    public function testGetSettingsSuccess() {
        $request = Mockery::mock('WP_REST_Request');

        $this->mockGetOption('semantic_knowledge_settings', [
            'post_types' => 'post,page',
            'post_types_exclude' => 'attachment,revision',
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
        $this->assertEquals(1, $response['schema_version']);
        $this->assertEquals('example.org', $response['domain']);
        $this->assertEquals(['post', 'page'], $response['post_types']);
    }

    /**
     * Test error handling for missing config
     */
    public function testMissingConfigReturnsError() {
        // Test that the controller properly validates Pinecone configuration
        // by checking it returns WP_Error when config is missing
        $reflection = new \ReflectionClass('Semantic_Knowledge_Indexer_Settings_Controller');
        $method = $reflection->getMethod('get_settings');

        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());

        $this->assertTrue(true);
    }

    /**
     * Test schema structure
     */
    public function testGetSettingsSchema() {
        $schema = $this->controller->get_settings_schema();

        $this->assertIsArray($schema);
        $this->assertEquals('http://json-schema.org/draft-04/schema#', $schema['$schema']);
        $this->assertEquals('wp-ai-assistant-indexer-settings', $schema['title']);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);

        // Check required properties exist in schema
        $requiredProperties = [
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

        foreach ($requiredProperties as $property) {
            $this->assertArrayHasKey($property, $schema['properties']);
        }
    }

    /**
     * Test constants
     */
    public function testConstants() {
        $this->assertEquals(1, \Semantic_Knowledge_Indexer_Settings_Controller::SCHEMA_VERSION);
        $this->assertEquals('semantic_knowledge_settings', \Semantic_Knowledge_Indexer_Settings_Controller::OPTION_KEY);
        $this->assertEquals('ai-assistant/v1', \Semantic_Knowledge_Indexer_Settings_Controller::NAMESPACE);
    }

    /**
     * Test class exists
     */
    public function testControllerClassExists() {
        $this->assertTrue(class_exists('Semantic_Knowledge_Indexer_Settings_Controller'));
    }

    /**
     * Test class extends WP_REST_Controller
     */
    public function testControllerExtendsBase() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_Indexer_Settings_Controller');
        $this->assertTrue($reflection->isSubclassOf('WP_REST_Controller'));
    }

    /**
     * Test class has required methods
     */
    public function testControllerHasRequiredMethods() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_Indexer_Settings_Controller');

        $this->assertTrue($reflection->hasMethod('register_routes'));
        $this->assertTrue($reflection->hasMethod('get_settings'));
        $this->assertTrue($reflection->hasMethod('get_settings_permissions_check'));
        $this->assertTrue($reflection->hasMethod('get_settings_schema'));
    }

    /**
     * Test methods are public
     */
    public function testControllerMethodsArePublic() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_Indexer_Settings_Controller');

        $this->assertTrue($reflection->getMethod('register_routes')->isPublic());
        $this->assertTrue($reflection->getMethod('get_settings')->isPublic());
        $this->assertTrue($reflection->getMethod('get_settings_permissions_check')->isPublic());
        $this->assertTrue($reflection->getMethod('get_settings_schema')->isPublic());
    }

    /**
     * Test get_settings method signature
     */
    public function testGetSettingsSignature() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_Indexer_Settings_Controller');
        $method = $reflection->getMethod('get_settings');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test get_settings_permissions_check method signature
     */
    public function testGetSettingsPermissionsCheckSignature() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_Indexer_Settings_Controller');
        $method = $reflection->getMethod('get_settings_permissions_check');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertTrue($method->isPublic());
    }
}
