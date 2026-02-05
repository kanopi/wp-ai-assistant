<?php
/**
 * Base test case class for all tests
 */

namespace Semantic_Knowledge_Tests\Helpers;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use WP_Mock;
use Mockery;

abstract class TestCase extends PHPUnitTestCase {
    /**
     * Set up test environment before each test
     */
    protected function setUp(): void {
        parent::setUp();
        WP_Mock::setUp();
    }

    /**
     * Tear down test environment after each test
     */
    protected function tearDown(): void {
        WP_Mock::tearDown();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Mock WordPress get_option function
     *
     * @param string $option Option name
     * @param mixed $return Return value
     * @return void
     */
    protected function mockGetOption($option, $return) {
        WP_Mock::userFunction('get_option')
            ->with($option, Mockery::any())
            ->andReturn($return);
    }

    /**
     * Mock WordPress update_option function
     *
     * @param string $option Option name
     * @param mixed $value Option value
     * @return void
     */
    protected function mockUpdateOption($option, $value) {
        WP_Mock::userFunction('update_option')
            ->with($option, $value)
            ->andReturn(true);
    }

    /**
     * Mock WordPress delete_transient function
     *
     * @param string $transient Transient name
     * @return void
     */
    protected function mockDeleteTransient($transient) {
        WP_Mock::userFunction('delete_transient')
            ->with($transient)
            ->andReturn(true);
    }

    /**
     * Mock WordPress get_transient function
     *
     * @param string $transient Transient name
     * @param mixed $return Return value
     * @return void
     */
    protected function mockGetTransient($transient, $return = false) {
        WP_Mock::userFunction('get_transient')
            ->with($transient)
            ->andReturn($return);
    }

    /**
     * Mock WordPress set_transient function
     *
     * @param string $transient Transient name
     * @param mixed $value Transient value
     * @param int $expiration Expiration time
     * @return void
     */
    protected function mockSetTransient($transient, $value, $expiration = 0) {
        WP_Mock::userFunction('set_transient')
            ->with($transient, $value, $expiration)
            ->andReturn(true);
    }

    /**
     * Mock esc_html function
     *
     * @return void
     */
    protected function mockEscHtml() {
        WP_Mock::userFunction('esc_html')
            ->andReturnUsing(function ($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            });
    }

    /**
     * Mock esc_url function
     *
     * @return void
     */
    protected function mockEscUrl() {
        WP_Mock::userFunction('esc_url')
            ->andReturnUsing(function ($url) {
                return filter_var($url, FILTER_SANITIZE_URL);
            });
    }

    /**
     * Mock sanitize_text_field function
     *
     * @return void
     */
    protected function mockSanitizeTextField() {
        WP_Mock::userFunction('sanitize_text_field')
            ->andReturnUsing(function ($text) {
                return strip_tags($text);
            });
    }

    /**
     * Mock wp_json_encode function
     *
     * @return void
     */
    protected function mockWpJsonEncode() {
        WP_Mock::userFunction('wp_json_encode')
            ->andReturnUsing(function ($data) {
                return json_encode($data);
            });
    }

    /**
     * Create a Mockery mock object with specified methods
     *
     * @param string $class Class name
     * @param array $methods Methods to mock
     * @return Mockery\MockInterface
     */
    protected function createMockeryMock($class, array $methods = []) {
        $mock = Mockery::mock($class);

        foreach ($methods as $method => $return) {
            $mock->shouldReceive($method)->andReturn($return);
        }

        return $mock;
    }

    /**
     * Assert that a string contains a substring
     *
     * @param string $needle Substring to search for
     * @param string $haystack String to search in
     * @param string $message Failure message
     * @return void
     */
    protected function assertStringContains($needle, $haystack, $message = '') {
        $this->assertStringContainsString($needle, $haystack, $message);
    }
}
