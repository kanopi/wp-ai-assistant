<?php
/**
 * Tests for WP_AI_System_Check class
 */

namespace WP_AI_Tests\Unit\SystemCheck;

use WP_AI_Tests\Helpers\TestCase;
use WP_Mock;
use Mockery;

class SystemCheckTest extends TestCase {
    /**
     * Test run_checks returns cached results
     */
    public function testRunChecksWithCache() {
        $cached_status = [
            'node_available' => true,
            'node_version' => '22.9.0',
            'node_version_ok' => true,
            'indexer_available' => true,
            'indexer_version' => '1.0.0',
            'all_ok' => true,
        ];

        $this->mockGetTransient('wp_ai_assistant_system_check', $cached_status);

        $result = \WP_AI_System_Check::run_checks(true);

        $this->assertEquals($cached_status, $result);
    }

    /**
     * Test version comparison logic
     */
    public function testVersionComparison() {
        // Test that version 22.9.0 >= 18.0.0
        $this->assertTrue(version_compare('22.9.0', '18.0.0', '>='));

        // Test that version 16.0.0 < 18.0.0
        $this->assertFalse(version_compare('16.0.0', '18.0.0', '>='));

        // Test that version 18.0.0 >= 18.0.0
        $this->assertTrue(version_compare('18.0.0', '18.0.0', '>='));
    }

    /**
     * Test clear_cache
     */
    public function testClearCache() {
        $this->mockDeleteTransient('wp_ai_assistant_system_check');

        \WP_AI_System_Check::clear_cache();

        $this->assertTrue(true);
    }

    /**
     * Test get_indexer_path method exists and signature
     */
    public function testGetIndexerPathMethodExists() {
        $reflection = new \ReflectionClass('WP_AI_System_Check');
        $method = $reflection->getMethod('get_indexer_path');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertEquals(0, $method->getNumberOfParameters());

        $this->assertTrue(true);
    }

    /**
     * Test ajax methods exist
     */
    public function testAjaxMethodsExist() {
        $reflection = new \ReflectionClass('WP_AI_System_Check');

        $this->assertTrue($reflection->hasMethod('ajax_dismiss_notice'));
        $this->assertTrue($reflection->hasMethod('ajax_recheck'));

        $this->assertTrue($reflection->getMethod('ajax_dismiss_notice')->isPublic());
        $this->assertTrue($reflection->getMethod('ajax_recheck')->isPublic());
        $this->assertTrue($reflection->getMethod('ajax_dismiss_notice')->isStatic());
        $this->assertTrue($reflection->getMethod('ajax_recheck')->isStatic());

        $this->assertTrue(true);
    }

    /**
     * Test show_admin_notice returns early for network admin
     */
    public function testShowAdminNoticeNetworkAdmin() {
        WP_Mock::userFunction('is_network_admin')
            ->once()
            ->andReturn(true);

        \WP_AI_System_Check::show_admin_notice();

        $this->assertTrue(true);
    }

    /**
     * Test show_admin_notice returns early for non-admin users
     */
    public function testShowAdminNoticeNonAdmin() {
        WP_Mock::userFunction('is_network_admin')
            ->once()
            ->andReturn(false);

        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(false);

        \WP_AI_System_Check::show_admin_notice();

        $this->assertTrue(true);
    }

    /**
     * Test show_admin_notice returns early if dismissed
     */
    public function testShowAdminNoticeDismissed() {
        WP_Mock::userFunction('is_network_admin')
            ->once()
            ->andReturn(false);

        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);

        WP_Mock::userFunction('get_current_user_id')
            ->once()
            ->andReturn(1);

        WP_Mock::userFunction('get_user_meta')
            ->once()
            ->with(1, 'wp_ai_assistant_system_notice_dismissed', true)
            ->andReturn(true);

        \WP_AI_System_Check::show_admin_notice();

        $this->assertTrue(true);
    }

    /**
     * Test constant values
     */
    public function testConstants() {
        $this->assertEquals('wp_ai_assistant_system_check', \WP_AI_System_Check::CACHE_KEY);
        $this->assertEquals(3600, \WP_AI_System_Check::CACHE_TTL);
        $this->assertEquals('18.0.0', \WP_AI_System_Check::MIN_NODE_VERSION);
    }

    /**
     * Test class exists
     */
    public function testSystemCheckClassExists() {
        $this->assertTrue(class_exists('WP_AI_System_Check'));
    }

    /**
     * Test class has required static methods
     */
    public function testSystemCheckHasRequiredMethods() {
        $reflection = new \ReflectionClass('WP_AI_System_Check');

        $this->assertTrue($reflection->hasMethod('run_checks'));
        $this->assertTrue($reflection->hasMethod('get_indexer_path'));
        $this->assertTrue($reflection->hasMethod('clear_cache'));
        $this->assertTrue($reflection->hasMethod('show_admin_notice'));
        $this->assertTrue($reflection->hasMethod('ajax_dismiss_notice'));
        $this->assertTrue($reflection->hasMethod('ajax_recheck'));
    }

    /**
     * Test methods are static
     */
    public function testSystemCheckMethodsAreStatic() {
        $reflection = new \ReflectionClass('WP_AI_System_Check');

        $this->assertTrue($reflection->getMethod('run_checks')->isStatic());
        $this->assertTrue($reflection->getMethod('get_indexer_path')->isStatic());
        $this->assertTrue($reflection->getMethod('clear_cache')->isStatic());
        $this->assertTrue($reflection->getMethod('show_admin_notice')->isStatic());
        $this->assertTrue($reflection->getMethod('ajax_dismiss_notice')->isStatic());
        $this->assertTrue($reflection->getMethod('ajax_recheck')->isStatic());
    }

    /**
     * Test run_checks method signature
     */
    public function testRunChecksSignature() {
        $reflection = new \ReflectionClass('WP_AI_System_Check');
        $method = $reflection->getMethod('run_checks');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test get_indexer_path method signature
     */
    public function testGetIndexerPathSignature() {
        $reflection = new \ReflectionClass('WP_AI_System_Check');
        $method = $reflection->getMethod('get_indexer_path');

        $this->assertEquals(0, $method->getNumberOfParameters());
        $this->assertTrue($method->isPublic());
    }
}
