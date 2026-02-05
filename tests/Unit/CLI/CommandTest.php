<?php
/**
 * Tests for Semantic_Knowledge_CLI_Command class
 */

namespace Semantic_Knowledge_Tests\Unit\CLI;

use Semantic_Knowledge_Tests\Helpers\TestCase;
use WP_Mock;
use Mockery;

class CommandTest extends TestCase {
    /**
     * Test check command with structure (can't easily mock static methods)
     */
    public function testCheckCommandStructure() {
        // Test that check command exists and has correct structure
        $reflection = new \ReflectionClass('Semantic_Knowledge_CLI_Command');
        $checkMethod = $reflection->getMethod('check');

        $this->assertTrue($checkMethod->isPublic());
        $this->assertEquals(2, $checkMethod->getNumberOfParameters());
        $this->assertNotEmpty($checkMethod->getDocComment());

        $this->assertTrue(true);
    }

    /**
     * Test command class exists
     */
    public function testCommandClassExists() {
        $this->assertTrue(class_exists('Semantic_Knowledge_CLI_Command'));
    }

    /**
     * Test command has required methods
     */
    public function testCommandHasRequiredMethods() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_CLI_Command');

        $this->assertTrue($reflection->hasMethod('index'));
        $this->assertTrue($reflection->hasMethod('clean'));
        $this->assertTrue($reflection->hasMethod('delete_all'));
        $this->assertTrue($reflection->hasMethod('config'));
        $this->assertTrue($reflection->hasMethod('check'));
    }

    /**
     * Test command methods are public
     */
    public function testCommandMethodsArePublic() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_CLI_Command');

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
        $this->assertEquals(2, $reflection->getMethod('index')->getNumberOfParameters());
        $this->assertEquals(2, $reflection->getMethod('clean')->getNumberOfParameters());
        $this->assertEquals(2, $reflection->getMethod('delete_all')->getNumberOfParameters());
        $this->assertEquals(2, $reflection->getMethod('config')->getNumberOfParameters());
        $this->assertEquals(2, $reflection->getMethod('check')->getNumberOfParameters());
    }

    /**
     * Test command has private helper methods
     */
    public function testCommandHasPrivateHelperMethods() {
        $reflection = new \ReflectionClass('Semantic_Knowledge_CLI_Command');

        $this->assertTrue($reflection->hasMethod('check_indexer_available'));
        $this->assertTrue($reflection->hasMethod('run_command'));

        $this->assertTrue($reflection->getMethod('check_indexer_available')->isPrivate());
        $this->assertTrue($reflection->getMethod('run_command')->isPrivate());
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
     * Test config command can be instantiated and called
     */
    public function testConfigCommandInstantiation() {
        $command = new \Semantic_Knowledge_CLI_Command();
        $this->assertInstanceOf('Semantic_Knowledge_CLI_Command', $command);
    }
}
