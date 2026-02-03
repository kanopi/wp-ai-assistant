<?php
/**
 * Admin Notices for WP AI Assistant
 *
 * Handles admin notices for indexer installation status.
 *
 * @package WP_AI_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin notices handler
 */
class WP_AI_Admin_Notices {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'check_indexer_installed']);
        add_action('admin_init', [$this, 'handle_dismiss']);
        add_action('admin_notices', [$this, 'show_notices']);
        add_action('admin_post_wp_ai_install_indexer', [$this, 'handle_install_indexer']);
    }

    /**
     * Check if indexer is installed
     */
    public function check_indexer_installed() {
        $plugin_dir = dirname(dirname(__FILE__));
        $indexer_path = $plugin_dir . '/indexer/node_modules/@kanopi/wp-ai-indexer';

        if (!file_exists($indexer_path)) {
            // Only show if not dismissed
            $dismissed = get_option('wp_ai_indexer_notice_dismissed', false);
            if (!$dismissed) {
                set_transient('wp_ai_indexer_missing', true, HOUR_IN_SECONDS);
            }
        } else {
            // Clear any existing notices
            delete_transient('wp_ai_indexer_missing');
        }
    }

    /**
     * Handle notice dismissal
     */
    public function handle_dismiss() {
        if (isset($_GET['wp_ai_dismiss_indexer']) && current_user_can('manage_options')) {
            check_admin_referer('wp_ai_dismiss_indexer');
            update_option('wp_ai_indexer_notice_dismissed', true);
            delete_transient('wp_ai_indexer_missing');

            // Redirect back without query params
            wp_safe_redirect(remove_query_arg(['wp_ai_dismiss_indexer', '_wpnonce']));
            exit;
        }
    }

    /**
     * Show admin notices
     */
    public function show_notices() {
        // Indexer missing notice
        if (get_transient('wp_ai_indexer_missing')) {
            $this->indexer_missing_notice();
        }

        // Installation success
        if (isset($_GET['wp_ai_indexer_installed']) && $_GET['wp_ai_indexer_installed'] === 'success') {
            $this->indexer_success_notice();
        }

        // Installation error
        if (isset($_GET['wp_ai_indexer_error'])) {
            $this->indexer_error_notice($_GET['wp_ai_indexer_error']);
        }
    }

    /**
     * Show indexer missing notice
     */
    private function indexer_missing_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $install_url = admin_url('admin-post.php?action=wp_ai_install_indexer');
        $install_url = wp_nonce_url($install_url, 'wp_ai_install_indexer');
        $dismiss_url = add_query_arg('wp_ai_dismiss_indexer', '1');
        $dismiss_url = wp_nonce_url($dismiss_url, 'wp_ai_dismiss_indexer');

        ?>
        <div class="notice notice-warning is-dismissible">
            <h3>WP AI Assistant: Indexer Not Installed</h3>
            <p>
                The Node.js indexer package is required for AI-powered search and chatbot functionality.
            </p>
            <p>
                <a href="<?php echo esc_url($install_url); ?>" class="button button-primary">
                    Install Indexer Now
                </a>
                <a href="https://github.com/kanopi/wp-ai-assistant#installation" class="button" target="_blank">
                    Installation Guide
                </a>
                <a href="<?php echo esc_url($dismiss_url); ?>" class="button">
                    Dismiss
                </a>
            </p>
            <p>
                <strong>Requirements:</strong> Node.js 18+ must be installed on your server.
                <a href="https://nodejs.org/" target="_blank">Download Node.js</a>
            </p>
        </div>
        <?php
    }

    /**
     * Handle indexer installation via admin
     */
    public function handle_install_indexer() {
        check_admin_referer('wp_ai_install_indexer');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $plugin_dir = dirname(dirname(__FILE__));
        $indexer_dir = $plugin_dir . '/indexer';

        // Check Node.js
        exec('which node 2>&1', $output, $return_code);
        if ($return_code !== 0) {
            wp_redirect(admin_url('plugins.php?wp_ai_indexer_error=node_not_found'));
            exit;
        }

        // Run npm install
        $cmd = sprintf('cd %s && npm install 2>&1', escapeshellarg($indexer_dir));
        exec($cmd, $install_output, $return_code);

        if ($return_code === 0) {
            delete_transient('wp_ai_indexer_missing');
            delete_option('wp_ai_indexer_notice_dismissed');
            wp_redirect(admin_url('plugins.php?wp_ai_indexer_installed=success'));
        } else {
            $error_log = implode("\n", $install_output);
            error_log('WP AI Assistant indexer installation failed: ' . $error_log);
            wp_redirect(admin_url('plugins.php?wp_ai_indexer_error=install_failed'));
        }
        exit;
    }

    /**
     * Show success notice
     */
    private function indexer_success_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong>WP AI Assistant:</strong> Indexer installed successfully!
                You can now run <code>wp ai-indexer index</code> to index your content.
            </p>
        </div>
        <?php
    }

    /**
     * Show error notice
     */
    private function indexer_error_notice($error_code) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $message = 'Installation failed.';

        if ($error_code === 'node_not_found') {
            $message = 'Node.js not found. Please install Node.js 18+ on your server.';
        } elseif ($error_code === 'install_failed') {
            $message = 'Failed to install indexer. Try manually: <code>cd wp-content/plugins/wp-ai-assistant/indexer && npm install</code>';
        }

        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>WP AI Assistant:</strong> <?php echo wp_kses_post($message); ?></p>
        </div>
        <?php
    }
}
