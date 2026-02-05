<?php
/**
 * WP-CLI Commands for Semantic Knowledge
 *
 * Provides WordPress-native CLI interface to the Node.js indexer package.
 *
 * @package Semantic_Knowledge
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_CLI')) {
    return;
}

/**
 * Manage AI indexing operations
 */
class Semantic_Knowledge_CLI_Command {

    /**
     * Index all WordPress content
     *
     * ## OPTIONS
     *
     * [--debug]
     * : Enable debug logging
     *
     * [--since=<date>]
     * : Only index posts modified since this date (ISO format)
     *
     * ## EXAMPLES
     *
     *     # Index all content
     *     wp sk-indexer index
     *
     *     # Index with debug output
     *     wp sk-indexer index --debug
     *
     * @when after_wp_load
     */
    public function index($args, $assoc_args) {
        // Build command arguments
        $cmd = 'index';

        if (isset($assoc_args['debug'])) {
            $cmd .= ' --debug';
        }

        if (isset($assoc_args['since'])) {
            $cmd .= ' --since ' . escapeshellarg($assoc_args['since']);
        }

        // Run indexer and stream output
        $status = $this->run_command($cmd);

        if ($status !== 0) {
            WP_CLI::error('Indexing failed with exit code ' . $status);
        }
    }

    /**
     * Clean deleted posts from index
     *
     * ## OPTIONS
     *
     * [--debug]
     * : Enable debug logging
     *
     * ## EXAMPLES
     *
     *     # Clean deleted posts
     *     wp sk-indexer clean
     *
     * @when after_wp_load
     */
    public function clean($args, $assoc_args) {
        $cmd = 'clean';

        if (isset($assoc_args['debug'])) {
            $cmd .= ' --debug';
        }

        $status = $this->run_command($cmd);

        if ($status !== 0) {
            WP_CLI::error('Cleaning failed with exit code ' . $status);
        }
    }

    /**
     * Delete all vectors for the current domain
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Skip confirmation prompt
     *
     * [--debug]
     * : Enable debug logging
     *
     * ## EXAMPLES
     *
     *     # Delete all (with confirmation)
     *     wp sk-indexer delete-all
     *
     *     # Delete all (skip confirmation)
     *     wp sk-indexer delete-all --yes
     *
     * @when after_wp_load
     */
    public function delete_all($args, $assoc_args) {
        $cmd = 'delete-all';

        if (isset($assoc_args['yes'])) {
            $cmd .= ' --yes';
        }

        if (isset($assoc_args['debug'])) {
            $cmd .= ' --debug';
        }

        $status = $this->run_command($cmd);

        if ($status !== 0) {
            WP_CLI::error('Deletion failed with exit code ' . $status);
        }
    }

    /**
     * Show current indexer configuration
     *
     * ## EXAMPLES
     *
     *     # Show configuration
     *     wp sk-indexer config
     *
     * @when after_wp_load
     */
    public function config($args, $assoc_args) {
        $this->run_command('config');
    }

    /**
     * Check system requirements
     *
     * Verifies that Node.js and the indexer package are installed and meet
     * minimum version requirements.
     *
     * ## EXAMPLES
     *
     *     # Check system requirements
     *     wp sk-indexer check
     *
     * @when after_wp_load
     */
    public function check($args, $assoc_args) {
        $status = WP_AI_System_Check::run_checks(false);

        WP_CLI::line('🔍 Checking system requirements...');
        WP_CLI::line('');

        // Node.js
        if ($status['node_available']) {
            WP_CLI::line(sprintf(
                '✓ Node.js: %s',
                $status['node_version']
            ));

            if (!$status['node_version_ok']) {
                WP_CLI::warning(sprintf(
                    'Node.js version %s or higher is required (found: %s)',
                    WP_AI_System_Check::MIN_NODE_VERSION,
                    $status['node_version']
                ));
            }
        } else {
            WP_CLI::line('✗ Node.js: Not found');
            WP_CLI::warning('Install Node.js 18+ from https://nodejs.org/');
        }

        // Indexer package
        if ($status['indexer_available']) {
            WP_CLI::line(sprintf(
                '✓ Indexer: %s',
                $status['indexer_version'] ?: 'installed'
            ));
        } else {
            WP_CLI::line('✗ Indexer: Not found');
            WP_CLI::warning('For DDEV: ddev exec "cd packages/wp-sk-indexer && npm install && npm run build"');
            WP_CLI::warning('For Local: cd packages/wp-sk-indexer && npm install && npm run build');
            WP_CLI::warning('For CI/CD: npm install -g @kanopi/wp-sk-indexer');
        }

        WP_CLI::line('');

        if ($status['all_ok']) {
            WP_CLI::success('All requirements met!');
        } else {
            WP_CLI::error('Some requirements are not met. See warnings above.');
        }
    }

    /**
     * Check if indexer package is available and return path
     *
     * @return string Path to indexer
     * @throws WP_CLI\ExitException
     */
    private function check_indexer_available() {
        $indexer_path = WP_AI_System_Check::get_indexer_path();

        if (!$indexer_path) {
            WP_CLI::error(
                "Node.js indexer not found.\n\n" .
                "For DDEV:\n" .
                "  ddev exec \"cd packages/wp-sk-indexer && npm install && npm run build\"\n\n" .
                "For Local (non-DDEV):\n" .
                "  cd packages/wp-sk-indexer && npm install && npm run build\n\n" .
                "For CI/CD (global installation):\n" .
                "  npm install -g @kanopi/wp-sk-indexer\n\n" .
                "Documentation: https://github.com/kanopi/wp-sk-indexer"
            );
        }

        return $indexer_path;
    }

    /**
     * Find Node.js executable
     *
     * Checks multiple sources in priority order:
     * 1. Filter hook (for programmatic override)
     * 2. Plugin setting (custom path from UI)
     * 3. Standard system paths
     * 4. which node command
     * 5. Assume 'node' is in PATH
     *
     * @return string Path to Node.js executable
     */
    private function find_node_executable() {
        /**
         * Filter the Node.js executable path.
         *
         * @param string|null $node_path Path to Node.js or null to use default detection
         * @return string|null Modified path or null
         */
        $node_path = apply_filters('semantic_knowledge_indexer_node_path', null);

        if (!empty($node_path) && file_exists($node_path)) {
            return $node_path;
        }

        // Check plugin setting
        $settings = get_option(WP_AI_Core::OPTION_KEY, array());
        $setting_path = $settings['indexer_node_path'] ?? '';

        if (!empty($setting_path) && file_exists($setting_path)) {
            return $setting_path;
        }

        // Check standard system paths
        $standard_paths = array(
            '/usr/bin/node',
            '/usr/local/bin/node',
            '/opt/homebrew/bin/node',
        );

        foreach ($standard_paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try which node command
        exec('which node 2>/dev/null', $output, $return_code);
        if ($return_code === 0 && !empty($output[0])) {
            $detected_path = trim($output[0]);
            if (file_exists($detected_path)) {
                return $detected_path;
            }
        }

        // Last resort: assume 'node' is in PATH
        return 'node';
    }

    /**
     * Run a shell command and display output
     *
     * @param string $cmd Command to run (without indexer path)
     * @return int Exit code
     */
    private function run_command($cmd) {
        // Get indexer path (local or global)
        $indexer_path = $this->check_indexer_available();

        // Find node executable
        $node_path = $this->find_node_executable();

        // Build command - use stdbuf with absolute paths
        if (substr($indexer_path, -3) === '.js') {
            $full_cmd = sprintf(
                '/usr/bin/stdbuf -oL -eL %s %s %s',
                $node_path,
                escapeshellarg($indexer_path),
                $cmd
            );
        } else {
            $full_cmd = sprintf(
                '/usr/bin/stdbuf -oL -eL %s %s',
                escapeshellarg($indexer_path),
                $cmd
            );
        }

        // Disable output buffering for real-time streaming
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        // Use passthru() for real-time output streaming
        $return_code = 0;
        passthru($full_cmd, $return_code);

        return $return_code;
    }
}

    /**
     * Install the Node.js indexer package
     *
     * ## EXAMPLES
     *
     *     wp semantic-knowledge install-indexer
     *
     * @when after_wp_load
     */
    public function install_indexer() {
        WP_CLI::line('Installing @kanopi/wp-sk-indexer...');

        $plugin_dir = dirname(dirname(__FILE__));
        $indexer_dir = $plugin_dir . '/indexer';

        // Check if Node.js is installed
        exec('which node 2>&1', $output, $return_code);
        if ($return_code !== 0) {
            WP_CLI::error('Node.js not found. Please install Node.js 18+ first: https://nodejs.org/');
            return;
        }

        // Get Node.js version
        exec('node --version 2>&1', $version_output);
        $node_version = trim($version_output[0]);
        WP_CLI::log("Found Node.js: {$node_version}");

        // Check directory exists
        if (!is_dir($indexer_dir)) {
            WP_CLI::error("Indexer directory not found: {$indexer_dir}");
            return;
        }

        // Run npm install
        WP_CLI::log("Running: cd {$indexer_dir} && npm install");
        $cmd = sprintf('cd %s && npm install 2>&1', escapeshellarg($indexer_dir));
        exec($cmd, $install_output, $return_code);

        // Show output
        foreach ($install_output as $line) {
            WP_CLI::log($line);
        }

        if ($return_code === 0) {
            WP_CLI::success('Indexer installed successfully!');
            WP_CLI::line('You can now run: wp sk-indexer index');
        } else {
            WP_CLI::error('Failed to install indexer. Try manually: cd indexer && npm install');
        }
    }

    /**
     * Check indexer installation status
     *
     * ## EXAMPLES
     *
     *     wp semantic-knowledge check-indexer
     *
     * @when after_wp_load
     */
    public function check_indexer() {
        $plugin_dir = dirname(dirname(__FILE__));
        $indexer_path = $plugin_dir . '/indexer/node_modules/@kanopi/wp-sk-indexer';

        if (file_exists($indexer_path)) {
            WP_CLI::success("Indexer is installed at: {$indexer_path}");

            // Get version
            $package_json_path = $indexer_path . '/package.json';
            if (file_exists($package_json_path)) {
                $package_json = file_get_contents($package_json_path);
                $package = json_decode($package_json, true);
                if (isset($package['version'])) {
                    WP_CLI::log("Version: {$package['version']}");
                }
            }
        } else {
            WP_CLI::warning('Indexer is not installed.');
            WP_CLI::line('Install it with: wp semantic-knowledge install-indexer');
        }
    }
}

// Register commands
WP_CLI::add_command('sk-indexer', 'WP_AI_CLI_Command');
WP_CLI::add_command('ai-assistant install-indexer', ['WP_AI_CLI_Command', 'install_indexer']);
WP_CLI::add_command('ai-assistant check-indexer', ['WP_AI_CLI_Command', 'check_indexer']);
