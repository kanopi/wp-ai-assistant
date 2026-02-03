<?php
/**
 * System Check for WP AI Assistant
 *
 * Checks for required dependencies:
 * - Node.js >= 18.0.0
 * - @kanopi/wp-ai-indexer npm package
 *
 * @package WP_AI_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AI_System_Check {

    /**
     * Transient key for caching check results
     */
    const CACHE_KEY = 'wp_ai_assistant_system_check';

    /**
     * Cache TTL (1 hour)
     */
    const CACHE_TTL = 3600;

    /**
     * Minimum Node.js version
     */
    const MIN_NODE_VERSION = '18.0.0';

    /**
     * Run all system checks
     *
     * @param bool $use_cache Whether to use cached results
     * @return array System status
     */
    public static function run_checks($use_cache = true) {
        // Check cache first
        if ($use_cache) {
            $cached = get_transient(self::CACHE_KEY);
            if ($cached !== false) {
                return $cached;
            }
        }

        $status = array(
            'node_available' => self::check_node_available(),
            'node_version' => self::get_node_version(),
            'node_version_ok' => false,
            'indexer_available' => self::check_indexer_available(),
            'indexer_version' => self::get_indexer_version(),
            'all_ok' => false,
        );

        // Check Node version
        if ($status['node_available'] && $status['node_version']) {
            $status['node_version_ok'] = version_compare(
                $status['node_version'],
                self::MIN_NODE_VERSION,
                '>='
            );
        }

        // Overall status
        $status['all_ok'] = $status['node_available']
            && $status['node_version_ok']
            && $status['indexer_available'];

        // Cache results
        set_transient(self::CACHE_KEY, $status, self::CACHE_TTL);

        return $status;
    }

    /**
     * Check if Node.js is available
     */
    private static function check_node_available() {
        exec('which node 2>&1', $output, $return_code);
        return $return_code === 0;
    }

    /**
     * Get Node.js version
     */
    private static function get_node_version() {
        exec('node --version 2>&1', $output, $return_code);
        if ($return_code === 0 && !empty($output[0])) {
            // Strip leading 'v' from version string (e.g., "v18.0.0" -> "18.0.0")
            return ltrim($output[0], 'v');
        }
        return null;
    }

    /**
     * Check if indexer package is available (local or global)
     */
    private static function check_indexer_available() {
        // Check monorepo location first (packages/wp-ai-indexer)
        $monorepo_path = ABSPATH . '../packages/wp-ai-indexer/dist';
        if (file_exists($monorepo_path)) {
            return true;
        }

        // Check plugin's local installation (for standalone usage)
        $local_path = WP_AI_ASSISTANT_DIR . 'indexer/node_modules/@kanopi/wp-ai-indexer';
        if (file_exists($local_path)) {
            return true;
        }

        // Check global installation (for CircleCI)
        exec('which wp-ai-indexer 2>&1', $output, $return_code);
        if ($return_code === 0) {
            return true;
        }

        // Also check via npm list globally
        exec('npm list -g @kanopi/wp-ai-indexer --depth=0 2>&1', $output, $return_code);
        return $return_code === 0;
    }

    /**
     * Get indexer package version
     */
    private static function get_indexer_version() {
        // Check monorepo version first
        $monorepo_package_json = ABSPATH . '../packages/wp-ai-indexer/package.json';
        if (file_exists($monorepo_package_json)) {
            $package_data = json_decode(file_get_contents($monorepo_package_json), true);
            if (isset($package_data['version'])) {
                return $package_data['version'];
            }
        }

        // Check plugin's local version
        $local_package_json = WP_AI_ASSISTANT_DIR . 'indexer/node_modules/@kanopi/wp-ai-indexer/package.json';
        if (file_exists($local_package_json)) {
            $package_data = json_decode(file_get_contents($local_package_json), true);
            if (isset($package_data['version'])) {
                return $package_data['version'];
            }
        }

        // Check global version
        exec('npm list -g @kanopi/wp-ai-indexer --depth=0 2>&1', $output);
        foreach ($output as $line) {
            if (preg_match('/@kanopi\/wp-ai-indexer@([\d.]+)/', $line, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Get path to indexer executable
     *
     * @return string|null Path to indexer or null if not found
     */
    public static function get_indexer_path() {
        // Check monorepo installation first (for local development)
        $monorepo_bin = ABSPATH . '../packages/wp-ai-indexer/bin/wp-ai-indexer.js';
        if (file_exists($monorepo_bin)) {
            return $monorepo_bin;
        }

        // Check plugin's local installation
        $local_bin = WP_AI_ASSISTANT_DIR . 'indexer/node_modules/.bin/wp-ai-indexer';
        if (file_exists($local_bin)) {
            return $local_bin;
        }

        // Check global installation (for CircleCI)
        exec('which wp-ai-indexer 2>&1', $output, $return_code);
        if ($return_code === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        return null;
    }

    /**
     * Clear cached check results
     */
    public static function clear_cache() {
        delete_transient(self::CACHE_KEY);
    }

    /**
     * Display admin notice if checks fail
     */
    public static function show_admin_notice() {
        // Don't show on network admin or if user can't manage options
        if (is_network_admin() || !current_user_can('manage_options')) {
            return;
        }

        // Check if notice is dismissed
        $dismissed = get_user_meta(get_current_user_id(), 'wp_ai_assistant_system_notice_dismissed', true);
        if ($dismissed) {
            return;
        }

        $status = self::run_checks();

        if ($status['all_ok']) {
            return; // Everything OK
        }

        // Build notice message
        $message = '<strong>WP AI Assistant</strong> requires additional setup:';
        $steps = array();

        if (!$status['node_available']) {
            $steps[] = 'Install Node.js 18+ (<a href="https://nodejs.org/" target="_blank">Download</a>)';
        } elseif (!$status['node_version_ok']) {
            $steps[] = sprintf(
                'Update Node.js to version %s or higher (currently: %s)',
                self::MIN_NODE_VERSION,
                $status['node_version']
            );
        }

        if (!$status['indexer_available']) {
            $steps[] = 'Install the indexer package:<br>' .
                '<strong>For DDEV:</strong> <code>ddev exec "cd packages/wp-ai-indexer && npm install && npm run build"</code><br>' .
                '<strong>For Local (non-DDEV):</strong> <code>cd packages/wp-ai-indexer && npm install && npm run build</code><br>' .
                '<strong>For CI/CD:</strong> <code>npm install -g @kanopi/wp-ai-indexer</code>';
        }

        if (empty($steps)) {
            return;
        }

        ?>
        <div class="notice notice-warning is-dismissible wp-ai-assistant-notice" data-notice="system-check">
            <p><?php echo $message; ?></p>
            <ol style="margin-left: 20px;">
                <?php foreach ($steps as $step): ?>
                    <li><?php echo $step; ?></li>
                <?php endforeach; ?>
            </ol>
            <p>
                <a href="https://github.com/kanopi/wp-ai-indexer#installation" target="_blank">View Documentation</a>
                | <a href="#" class="wp-ai-assistant-recheck">Re-check</a>
            </p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Handle notice dismissal
            $(document).on('click', '.wp-ai-assistant-notice .notice-dismiss', function() {
                $.post(ajaxurl, {
                    action: 'wp_ai_assistant_dismiss_notice',
                    nonce: '<?php echo wp_create_nonce('wp_ai_assistant_dismiss'); ?>'
                });
            });

            // Handle re-check
            $(document).on('click', '.wp-ai-assistant-recheck', function(e) {
                e.preventDefault();
                $.post(ajaxurl, {
                    action: 'wp_ai_assistant_recheck',
                    nonce: '<?php echo wp_create_nonce('wp_ai_assistant_recheck'); ?>'
                }, function(response) {
                    location.reload();
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Handle AJAX notice dismissal
     */
    public static function ajax_dismiss_notice() {
        check_ajax_referer('wp_ai_assistant_dismiss', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        update_user_meta(
            get_current_user_id(),
            'wp_ai_assistant_system_notice_dismissed',
            true
        );

        wp_send_json_success();
    }

    /**
     * Handle AJAX re-check
     */
    public static function ajax_recheck() {
        check_ajax_referer('wp_ai_assistant_recheck', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Clear cache and re-run checks
        self::clear_cache();
        $status = self::run_checks(false);

        // Clear dismissal if issues resolved
        if ($status['all_ok']) {
            delete_user_meta(
                get_current_user_id(),
                'wp_ai_assistant_system_notice_dismissed'
            );
        }

        wp_send_json_success($status);
    }
}
