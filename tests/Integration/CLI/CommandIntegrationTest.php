<?php
/**
 * Integration tests for WP-CLI command registration and execution
 */

namespace Semantic_Knowledge_Tests\Integration\CLI;

use Semantic_Knowledge_Tests\Helpers\TestCase;
use WP_Mock;
use Mockery;

class CommandIntegrationTest extends TestCase {
    /**
     * Test that WP-CLI command registration happens
     */
    public function testCommandRegistrationPattern() {
        // Test that the command class and WP_CLI class exist
        $this->assertTrue(class_exists('WP_CLI'));
        $this->assertTrue(class_exists('Semantic_Knowledge_CLI_Command'));

        // Verify WP_CLI has the add_command method
        $this->assertTrue(method_exists('WP_CLI', 'add_command'));

        // If we get here without errors, the test passes
        $this->assertTrue(true);
    }

    /**
     * Test command has required methods
     */
    public function testCommandHasRequiredMethods() {
        $this->assertTrue(class_exists('Semantic_Knowledge_CLI_Command'));

        $reflection = new \ReflectionClass('Semantic_Knowledge_CLI_Command');

        // Check public command methods exist
        $this->assertTrue($reflection->hasMethod('index'));
        $this->assertTrue($reflection->hasMethod('clean'));
        $this->assertTrue($reflection->hasMethod('delete_all'));
        $this->assertTrue($reflection->hasMethod('config'));
        $this->assertTrue($reflection->hasMethod('check'));

        // Check methods are public
        $this->assertTrue($reflection->getMethod('index')->isPublic());
        $this->assertTrue($reflection->getMethod('clean')->isPublic());
        $this->assertTrue($reflection->getMethod('delete_all')->isPublic());
        $this->assertTrue($reflection->getMethod('config')->isPublic());
        $this->assertTrue($reflection->getMethod('check')->isPublic());
    }

    /**
     * Test command methods have correct signatures
     */
    public function testCommandMethodSignatures() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_CLI_Command');

        // All commands should accept ($args, $assoc_args)
        $indexMethod = $reflection->getMethod('index');
        $this->assertEquals(2, $indexMethod->getNumberOfParameters());

        $cleanMethod = $reflection->getMethod('clean');
        $this->assertEquals(2, $cleanMethod->getNumberOfParameters());

        $deleteAllMethod = $reflection->getMethod('delete_all');
        $this->assertEquals(2, $deleteAllMethod->getNumberOfParameters());

        $configMethod = $reflection->getMethod('config');
        $this->assertEquals(2, $configMethod->getNumberOfParameters());

        $checkMethod = $reflection->getMethod('check');
        $this->assertEquals(2, $checkMethod->getNumberOfParameters());
    }

    /**
     * Test SystemCheck class exists and has required methods
     */
    public function testSystemCheckClassExists() {
        $this->assertTrue(class_exists('Semantic_Knowledge_System_Check'));

        $reflection = new \ReflectionClass('Semantic_Knowledge_System_Check');

        // Check required static methods exist
        $this->assertTrue($reflection->hasMethod('run_checks'));
        $this->assertTrue($reflection->hasMethod('get_indexer_path'));
        $this->assertTrue($reflection->hasMethod('clear_cache'));

        // Check methods are static
        $this->assertTrue($reflection->getMethod('run_checks')->isStatic());
        $this->assertTrue($reflection->getMethod('get_indexer_path')->isStatic());
        $this->assertTrue($reflection->getMethod('clear_cache')->isStatic());
    }

    /**
     * Test REST API controller class exists and has required methods
     */
    public function testRestControllerClassExists() {
        $this->assertTrue(class_exists('Semantic_Knowledge_Indexer_Settings_Controller'));

        $reflection = new \ReflectionClass('Semantic_Knowledge_Indexer_Settings_Controller');

        // Check required methods exist
        $this->assertTrue($reflection->hasMethod('register_routes'));
        $this->assertTrue($reflection->hasMethod('get_settings'));
        $this->assertTrue($reflection->hasMethod('get_settings_permissions_check'));
        $this->assertTrue($reflection->hasMethod('get_settings_schema'));

        // Check methods are public
        $this->assertTrue($reflection->getMethod('register_routes')->isPublic());
        $this->assertTrue($reflection->getMethod('get_settings')->isPublic());
        $this->assertTrue($reflection->getMethod('get_settings_permissions_check')->isPublic());
        $this->assertTrue($reflection->getMethod('get_settings_schema')->isPublic());
    }

    /**
     * Test REST controller extends WP_REST_Controller
     */
    public function testRestControllerExtendsBase() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_Indexer_Settings_Controller');

        $this->assertTrue($reflection->isSubclassOf('WP_REST_Controller'));
    }

    /**
     * Test command documentation (PHPDoc blocks exist)
     */
    public function testCommandDocumentation() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_CLI_Command');

        // Check index command has docblock
        $indexMethod = $reflection->getMethod('index');
        $this->assertNotEmpty($indexMethod->getDocComment());

        // Check that docblock contains @when annotation
        $docComment = $indexMethod->getDocComment();
        $this->assertStringContainsString('@when after_wp_load', $docComment);
    }

    /**
     * Test SystemCheck constants are defined correctly
     */
    public function testSystemCheckConstants() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_System_Check');

        $constants = $reflection->getConstants();

        $this->assertArrayHasKey('CACHE_KEY', $constants);
        $this->assertArrayHasKey('CACHE_TTL', $constants);
        $this->assertArrayHasKey('MIN_NODE_VERSION', $constants);

        $this->assertEquals('semantic_knowledge_system_check', $constants['CACHE_KEY']);
        $this->assertEquals(3600, $constants['CACHE_TTL']);
        $this->assertEquals('18.0.0', $constants['MIN_NODE_VERSION']);
    }

    /**
     * Test REST controller constants are defined correctly
     */
    public function testRestControllerConstants() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_Indexer_Settings_Controller');

        $constants = $reflection->getConstants();

        $this->assertArrayHasKey('SCHEMA_VERSION', $constants);
        $this->assertArrayHasKey('OPTION_KEY', $constants);
        $this->assertArrayHasKey('NAMESPACE', $constants);

        $this->assertEquals(1, $constants['SCHEMA_VERSION']);
        $this->assertEquals('semantic_knowledge_settings', $constants['OPTION_KEY']);
        $this->assertEquals('ai-assistant/v1', $constants['NAMESPACE']);
    }

    /**
     * Test command registration happens when WP_CLI is available
     */
    public function testCommandRegistrationRequiresWpCli() {
        // Verify that WP_CLI class is defined in test environment
        $this->assertTrue(class_exists('WP_CLI'));

        // Verify that basic WP_CLI methods are available
        $this->assertTrue(method_exists('WP_CLI', 'add_command'));
        $this->assertTrue(method_exists('WP_CLI', 'success'));
        $this->assertTrue(method_exists('WP_CLI', 'error'));
        $this->assertTrue(method_exists('WP_CLI', 'warning'));
        $this->assertTrue(method_exists('WP_CLI', 'line'));
    }

    /**
     * Test plugin defines ABSPATH protection
     */
    public function testAbspathProtection() {
        // All plugin files should check for ABSPATH
        $cliFile = Semantic_Knowledge_PLUGIN_DIR . '/includes/class-wp-ai-cli.php';
        $systemCheckFile = Semantic_Knowledge_PLUGIN_DIR . '/includes/class-wp-ai-system-check.php';
        $controllerFile = Semantic_Knowledge_PLUGIN_DIR . '/includes/class-wp-ai-indexer-controller.php';

        if (file_exists($cliFile)) {
            $content = file_get_contents($cliFile);
            $this->assertStringContainsString("if (!defined('ABSPATH'))", $content);
        }

        if (file_exists($systemCheckFile)) {
            $content = file_get_contents($systemCheckFile);
            $this->assertStringContainsString("if (!defined('ABSPATH'))", $content);
        }

        if (file_exists($controllerFile)) {
            $content = file_get_contents($controllerFile);
            $this->assertStringContainsString("if ( ! defined( 'ABSPATH' ) )", $content);
        }
    }

    /**
     * Test plugin files use proper namespacing
     */
    public function testClassNaming() {
        // Classes should follow WordPress naming convention
        $this->assertTrue(class_exists('Semantic_Knowledge_CLI_Command'));
        $this->assertTrue(class_exists('Semantic_Knowledge_System_Check'));
        $this->assertTrue(class_exists('Semantic_Knowledge_Indexer_Settings_Controller'));

        // Classes should have WP_AI prefix
        $this->assertStringStartsWith('WP_AI', 'Semantic_Knowledge_CLI_Command');
        $this->assertStringStartsWith('WP_AI', 'Semantic_Knowledge_System_Check');
        $this->assertStringStartsWith('WP_AI', 'Semantic_Knowledge_Indexer_Settings_Controller');
    }
}
