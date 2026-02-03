<?php
/**
 * PHPUnit bootstrap file for WP AI Assistant plugin tests
 */

// Composer autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load WP_Mock
WP_Mock::bootstrap();

// Define test constants
define('WP_AI_PLUGIN_DIR', dirname(__DIR__));
define('WP_AI_PLUGIN_FILE', WP_AI_PLUGIN_DIR . '/wp-ai-assistant.php');
define('WP_AI_PLUGIN_BASENAME', 'wp-ai-assistant/wp-ai-assistant.php');

// Mock WordPress constants if not defined
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

// Mock WP_REST_Controller if not available
if (!class_exists('WP_REST_Controller')) {
    class WP_REST_Controller {
        public function register_routes() {}
    }
}

// Mock WP_REST_Server if not available
if (!class_exists('WP_REST_Server')) {
    class WP_REST_Server {
        const READABLE = 'GET';
    }
}

// Mock WP_Error if not available
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code;
        private $message;
        private $data;

        public function __construct($code, $message, $data = []) {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code() {
            return $this->code;
        }

        public function get_error_message() {
            return $this->message;
        }
    }
}

// Mock is_wp_error function
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

// Mock WP_CLI if not available
if (!class_exists('WP_CLI')) {
    class WP_CLI {
        public static function add_command($name, $callable, $args = []) {}
        public static function success($message) {}
        public static function error($message, $exit = true) {}
        public static function warning($message) {}
        public static function log($message) {}
        public static function line($message = '') {}
        public static function colorize($string) { return $string; }
        public static function get_config($key = null) { return null; }
    }
}

// Define WP_AI_ASSISTANT_DIR if not defined
if (!defined('WP_AI_ASSISTANT_DIR')) {
    define('WP_AI_ASSISTANT_DIR', WP_AI_PLUGIN_DIR . '/');
}

// Load plugin classes
require_once WP_AI_PLUGIN_DIR . '/includes/class-wp-ai-indexer-controller.php';
require_once WP_AI_PLUGIN_DIR . '/includes/class-wp-ai-system-check.php';
require_once WP_AI_PLUGIN_DIR . '/includes/class-wp-ai-cli.php';
