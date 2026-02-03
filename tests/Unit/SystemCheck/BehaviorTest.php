<?php
/**
 * Tests for WP_AI_System_Check behavioral logic
 */

namespace WP_AI_Tests\Unit\SystemCheck;

use WP_AI_Tests\Helpers\TestCase;
use WP_Mock;
use Mockery;

class BehaviorTest extends TestCase {
    /**
     * Test run_checks with cache enabled returns cached data
     */
    public function testRunChecksUsesCacheWhenEnabled() {
        $cachedData = [
            'node_available' => true,
            'node_version' => '22.9.0',
            'node_version_ok' => true,
            'indexer_available' => true,
            'indexer_version' => '1.0.0',
            'all_ok' => true,
        ];

        $this->mockGetTransient('wp_ai_assistant_system_check', $cachedData);

        $result = \WP_AI_System_Check::run_checks(true);

        $this->assertEquals($cachedData, $result);
        $this->assertArrayHasKey('node_available', $result);
        $this->assertArrayHasKey('node_version', $result);
        $this->assertArrayHasKey('all_ok', $result);
    }

    /**
     * Test run_checks with cache disabled returns valid structure
     */
    public function testRunChecksIgnoresCacheWhenDisabled() {
        // When cache is disabled (false), should perform fresh checks
        // Note: Cannot mock file_exists() - testing structure only

        // Mock set_transient since checks will save results
        $this->mockSetTransient('wp_ai_assistant_system_check', Mockery::any(), 3600);

        $result = \WP_AI_System_Check::run_checks(false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('node_available', $result);
        $this->assertArrayHasKey('indexer_available', $result);
        $this->assertArrayHasKey('all_ok', $result);
    }

    /**
     * Test run_checks handles cache miss and returns valid structure
     */
    public function testRunChecksHandlesCacheMiss() {
        // Cache miss returns false - should perform fresh checks
        // Note: Cannot mock file_exists() - testing structure only
        $this->mockGetTransient('wp_ai_assistant_system_check', false);
        $this->mockSetTransient('wp_ai_assistant_system_check', Mockery::any(), 3600);

        $result = \WP_AI_System_Check::run_checks(true);

        // Should run checks and return results
        $this->assertIsArray($result);
        $this->assertArrayHasKey('all_ok', $result);
    }

    /**
     * Test run_checks returns all_ok field
     */
    public function testRunChecksReturnsAllOkField() {
        // Note: Cannot mock file_exists() to control node availability
        // Testing that all_ok field exists and is boolean

        // Mock set_transient since checks will save results
        $this->mockSetTransient('wp_ai_assistant_system_check', Mockery::any(), 3600);

        $result = \WP_AI_System_Check::run_checks(false);

        $this->assertArrayHasKey('all_ok', $result);
        $this->assertIsBool($result['all_ok']);
    }

    /**
     * Test clear_cache calls delete_transient
     */
    public function testClearCacheDeletesTransient() {
        $this->mockDeleteTransient('wp_ai_assistant_system_check');

        \WP_AI_System_Check::clear_cache();

        // If we get here without errors, delete_transient was called
        $this->assertTrue(true);
    }

    /**
     * Test get_indexer_path returns string or null
     */
    public function testGetIndexerPathReturnType() {
        // Note: Cannot mock file_exists() to control which path is found
        // Testing that method returns string (path found) or null (not found)

        $result = \WP_AI_System_Check::get_indexer_path();

        $this->assertTrue(is_string($result) || is_null($result));

        // If path is found, it should be an absolute path
        if (is_string($result)) {
            $this->assertStringContainsString('wp-ai-indexer', $result);
        }
    }

    /**
     * Test get_indexer_path checks expected locations
     */
    public function testGetIndexerPathChecksExpectedLocations() {
        // Test that the method considers both monorepo and local paths
        // by checking the method exists and is static

        $reflection = new \ReflectionClass('WP_AI_System_Check');
        $method = $reflection->getMethod('get_indexer_path');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test show_admin_notice respects is_network_admin
     */
    public function testShowAdminNoticeRespectsNetworkAdmin() {
        WP_Mock::userFunction('is_network_admin')
            ->once()
            ->andReturn(true);

        // Should return early without checking other conditions
        \WP_AI_System_Check::show_admin_notice();

        $this->assertTrue(true);
    }

    /**
     * Test show_admin_notice respects user capabilities
     */
    public function testShowAdminNoticeRespectsCapabilities() {
        WP_Mock::userFunction('is_network_admin')
            ->once()
            ->andReturn(false);

        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(false);

        // Should return early without showing notice
        \WP_AI_System_Check::show_admin_notice();

        $this->assertTrue(true);
    }

    /**
     * Test show_admin_notice respects dismissed status
     */
    public function testShowAdminNoticeRespectsDismissed() {
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

        // Should return early when dismissed
        \WP_AI_System_Check::show_admin_notice();

        $this->assertTrue(true);
    }

    /**
     * Test show_admin_notice returns early when all checks pass
     */
    public function testShowAdminNoticeReturnsEarlyWhenAllOk() {
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
            ->andReturn(false);

        $this->mockGetTransient('wp_ai_assistant_system_check', [
            'node_available' => true,
            'node_version' => '22.9.0',
            'node_version_ok' => true,
            'indexer_available' => true,
            'indexer_version' => '1.0.0',
            'all_ok' => true,
        ]);

        // Should return early when all_ok is true (no notice needed)
        \WP_AI_System_Check::show_admin_notice();

        $this->assertTrue(true);
    }

    /**
     * Test cache TTL is 1 hour (3600 seconds)
     */
    public function testCacheTTLIsOneHour() {
        $this->assertEquals(3600, \WP_AI_System_Check::CACHE_TTL);
        $this->assertEquals(HOUR_IN_SECONDS, \WP_AI_System_Check::CACHE_TTL);
    }

    /**
     * Test minimum Node version is semver format
     */
    public function testMinNodeVersionIsSemver() {
        $version = \WP_AI_System_Check::MIN_NODE_VERSION;

        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
        $this->assertEquals('18.0.0', $version);
    }

    /**
     * Test version comparison with equal versions
     */
    public function testVersionComparisonEqual() {
        $this->assertTrue(version_compare('18.0.0', '18.0.0', '>='));
        $this->assertTrue(version_compare('18.0.0', '18.0.0', '<='));
        $this->assertTrue(version_compare('18.0.0', '18.0.0', '=='));
    }

    /**
     * Test version comparison with patch versions
     */
    public function testVersionComparisonPatchVersions() {
        $this->assertTrue(version_compare('18.0.1', '18.0.0', '>'));
        $this->assertTrue(version_compare('18.1.0', '18.0.9', '>'));
        $this->assertFalse(version_compare('17.9.9', '18.0.0', '>='));
    }

    /**
     * Test version comparison with major versions
     */
    public function testVersionComparisonMajorVersions() {
        $this->assertTrue(version_compare('20.0.0', '18.0.0', '>='));
        $this->assertTrue(version_compare('22.9.0', '18.0.0', '>='));
        $this->assertFalse(version_compare('16.20.0', '18.0.0', '>='));
    }
}
