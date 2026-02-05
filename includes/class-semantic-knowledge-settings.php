<?php
/**
 * Settings page for Semantic Knowledge
 * Unified admin page with tabs for General, Chatbot, Search, and Indexer settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Semantic_Knowledge_Settings {
	private $core;

	public function __construct( WP_AI_Core $core ) {
		$this->core = $core;
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register admin menu page
	 */
	public function register_admin_menu() {
		add_options_page(
			'AI Assistant',
			'AI Assistant',
			'manage_options',
			'semantic-knowledge',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		register_setting(
			'semantic_knowledge_assistant',
			WP_AI_Core::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize settings before saving
	 *
	 * @param array $input Raw input from form
	 * @return array Sanitized settings
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return $this->core->get_settings();
		}

		// Remove deprecated API keys for security (they should be in environment variables)
		$deprecated_keys = array( 'openai_api_key', 'pinecone_api_key' );
		$removed_keys = array();
		foreach ( $deprecated_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				unset( $input[ $key ] );
				$removed_keys[] = $key;
			}
		}

		// Notify user if keys were removed
		if ( ! empty( $removed_keys ) ) {
			add_settings_error(
				'semantic_knowledge_assistant',
				'deprecated_keys_removed',
				sprintf(
					'Deprecated API keys (%s) have been removed from database for security. Please ensure they are configured via environment variables.',
					implode( ', ', $removed_keys )
				),
				'warning'
			);
		}

		// Use core validation
		$validated = $this->core->validate_settings( $input );

		if ( is_wp_error( $validated ) ) {
			add_settings_error(
				'semantic_knowledge_assistant',
				'validation_error',
				$validated->get_error_message()
			);
			return $this->core->get_settings();
		}

		return $validated;
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->core->get_settings();
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="?page=wp-ai-assistant&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
					General
				</a>
				<a href="?page=wp-ai-assistant&tab=chatbot" class="nav-tab <?php echo $current_tab === 'chatbot' ? 'nav-tab-active' : ''; ?>">
					Chatbot
				</a>
				<a href="?page=wp-ai-assistant&tab=search" class="nav-tab <?php echo $current_tab === 'search' ? 'nav-tab-active' : ''; ?>">
					Search
				</a>
				<a href="?page=wp-ai-assistant&tab=indexer" class="nav-tab <?php echo $current_tab === 'indexer' ? 'nav-tab-active' : ''; ?>">
					Indexer
				</a>
			</h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'semantic_knowledge_assistant' );

				switch ( $current_tab ) {
					case 'chatbot':
						$this->render_chatbot_tab( $settings );
						break;
					case 'search':
						$this->render_search_tab( $settings );
						break;
					case 'indexer':
						$this->render_indexer_tab( $settings );
						break;
					case 'general':
					default:
						$this->render_general_tab( $settings );
						break;
				}

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render General tab
	 *
	 * @param array $settings Current settings
	 */
	private function render_general_tab( $settings ) {
		// Check if deprecated API keys exist in database
		$has_deprecated_keys = ! empty( $settings['openai_api_key'] ) || ! empty( $settings['pinecone_api_key'] );

		if ( $has_deprecated_keys ) : ?>
			<div class="notice notice-error">
				<p>
					<strong>⚠️ SECURITY WARNING:</strong> API keys found in database settings.
					This is insecure and deprecated. These keys are now being ignored by the plugin.
				</p>
				<p>
					<strong>Action Required:</strong>
				</p>
				<ol style="margin-left: 20px;">
					<li>Set your API keys via environment variables or secrets (see instructions below)</li>
					<li>Remove the old keys from the database by re-saving these settings</li>
					<li>Verify the plugin is working with environment-based keys</li>
				</ol>
				<p>
					For security reasons, API keys stored in the database will be permanently removed in a future version.
				</p>
			</div>
		<?php endif; ?>

		<div class="notice notice-info">
			<p>
				<strong>Security Note:</strong> API keys (OpenAI and Pinecone) must be configured via environment variables,
				PHP constants, or Pantheon secrets for security. They cannot be entered through this admin interface.
				<a href="https://github.com/kanopi/wp-ai-assistant#configuration" target="_blank" rel="noopener">View documentation</a>
			</p>
			<p>Required environment variables:</p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li><code>OPENAI_API_KEY</code> - Your OpenAI API key</li>
				<li><code>PINECONE_API_KEY</code> - Your Pinecone API key</li>
			</ul>
		</div>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="pinecone_index_host">Pinecone Index Host</label>
				</th>
				<td>
					<input type="url" id="pinecone_index_host" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[pinecone_index_host]" value="<?php echo esc_attr( $settings['pinecone_index_host'] ?? '' ); ?>" class="regular-text">
					<p class="description">Full URL (e.g., https://your-index.svc.region.pinecone.io)</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pinecone_index_name">Pinecone Index Name</label>
				</th>
				<td>
					<input type="text" id="pinecone_index_name" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[pinecone_index_name]" value="<?php echo esc_attr( $settings['pinecone_index_name'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="embedding_model">Embedding Model</label>
				</th>
				<td>
					<select id="embedding_model" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[embedding_model]">
						<option value="text-embedding-3-small" <?php selected( $settings['embedding_model'] ?? '', 'text-embedding-3-small' ); ?>>text-embedding-3-small</option>
						<option value="text-embedding-3-large" <?php selected( $settings['embedding_model'] ?? '', 'text-embedding-3-large' ); ?>>text-embedding-3-large</option>
						<option value="text-embedding-ada-002" <?php selected( $settings['embedding_model'] ?? '', 'text-embedding-ada-002' ); ?>>text-embedding-ada-002</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="embedding_dimension">Embedding Dimension</label>
				</th>
				<td>
					<input type="number" id="embedding_dimension" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[embedding_dimension]" value="<?php echo esc_attr( $settings['embedding_dimension'] ?? 1536 ); ?>" class="small-text">
					<p class="description">1536 for text-embedding-3-small, 3072 for text-embedding-3-large</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Current Domain</th>
				<td>
					<code><?php echo esc_html( $this->core->get_current_domain() ); ?></code>
					<p class="description">All queries will be filtered to this domain (Schema Version <?php echo esc_html( $this->core->get_schema_version() ); ?>)</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Configuration Status</th>
				<td>
					<?php if ( $this->core->is_configured() ) : ?>
						<span style="color: green;">✓ Configured</span>
					<?php else : ?>
						<span style="color: red;">✗ Not configured</span>
						<p class="description">Please configure Pinecone settings and ensure API keys are set via secrets.</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Chatbot tab
	 *
	 * @param array $settings Current settings
	 */
	private function render_chatbot_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="chatbot_enabled">Enable Chatbot</label>
				</th>
				<td>
					<input type="checkbox" id="chatbot_enabled" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[chatbot_enabled]" value="1" <?php checked( ! empty( $settings['chatbot_enabled'] ) ); ?>>
					<label for="chatbot_enabled">Enable chatbot functionality</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="chatbot_system_prompt">System Prompt</label>
				</th>
				<td>
					<textarea id="chatbot_system_prompt" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[chatbot_system_prompt]" rows="6" class="large-text"><?php echo esc_textarea( $settings['chatbot_system_prompt'] ?? '' ); ?></textarea>
					<p class="description">Instructions for the chatbot's behavior and personality</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="chatbot_model">OpenAI Model</label>
				</th>
				<td>
					<input type="text" id="chatbot_model" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[chatbot_model]" value="<?php echo esc_attr( $settings['chatbot_model'] ?? 'gpt-4o-mini' ); ?>" class="regular-text">
					<p class="description">Default: gpt-4o-mini</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="chatbot_temperature">Temperature</label>
				</th>
				<td>
					<input type="number" id="chatbot_temperature" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[chatbot_temperature]" value="<?php echo esc_attr( $settings['chatbot_temperature'] ?? 0.2 ); ?>" min="0" max="2" step="0.1" class="small-text">
					<p class="description">0.0 to 2.0 (lower = more focused, higher = more creative)</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="chatbot_top_k">Top K Results</label>
				</th>
				<td>
					<input type="number" id="chatbot_top_k" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[chatbot_top_k]" value="<?php echo esc_attr( $settings['chatbot_top_k'] ?? 5 ); ?>" min="1" max="20" class="small-text">
					<p class="description">Number of relevant chunks to retrieve from Pinecone</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="chatbot_floating_button">Floating Button</label>
				</th>
				<td>
					<input type="checkbox" id="chatbot_floating_button" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[chatbot_floating_button]" value="1" <?php checked( ! empty( $settings['chatbot_floating_button'] ) ); ?>>
					<label for="chatbot_floating_button">Enable floating chat button site-wide</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="chatbot_intro_message">Intro Message</label>
				</th>
				<td>
					<input type="text" id="chatbot_intro_message" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[chatbot_intro_message]" value="<?php echo esc_attr( $settings['chatbot_intro_message'] ?? '' ); ?>" class="large-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="chatbot_input_placeholder">Input Placeholder</label>
				</th>
				<td>
					<input type="text" id="chatbot_input_placeholder" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[chatbot_input_placeholder]" value="<?php echo esc_attr( $settings['chatbot_input_placeholder'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Search tab
	 *
	 * @param array $settings Current settings
	 */
	private function render_search_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="search_enabled">Enable Search</label>
				</th>
				<td>
					<input type="checkbox" id="search_enabled" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_enabled]" value="1" <?php checked( ! empty( $settings['search_enabled'] ) ); ?>>
					<label for="search_enabled">Enable AI-powered search functionality</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_top_k">Top K Results</label>
				</th>
				<td>
					<input type="number" id="search_top_k" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_top_k]" value="<?php echo esc_attr( $settings['search_top_k'] ?? 10 ); ?>" min="1" max="50" class="small-text">
					<p class="description">Number of results to return from Pinecone</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_min_score">Minimum Score</label>
				</th>
				<td>
					<input type="number" id="search_min_score" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_min_score]" value="<?php echo esc_attr( $settings['search_min_score'] ?? 0.5 ); ?>" min="0" max="1" step="0.1" class="small-text">
					<p class="description">Minimum similarity score (0.0 to 1.0)</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_replace_default">Replace Default Search</label>
				</th>
				<td>
					<input type="checkbox" id="search_replace_default" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_replace_default]" value="1" <?php checked( ! empty( $settings['search_replace_default'] ) ); ?>>
					<label for="search_replace_default">Replace WordPress default search with AI search</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_results_per_page">Results Per Page</label>
				</th>
				<td>
					<input type="number" id="search_results_per_page" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_results_per_page]" value="<?php echo esc_attr( $settings['search_results_per_page'] ?? 10 ); ?>" min="1" max="50" class="small-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_placeholder">Search Placeholder</label>
				</th>
				<td>
					<input type="text" id="search_placeholder" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_placeholder]" value="<?php echo esc_attr( $settings['search_placeholder'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_enable_summary">Enable AI Summary</label>
				</th>
				<td>
					<input type="checkbox" id="search_enable_summary" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_enable_summary]" value="1" <?php checked( ! empty( $settings['search_enable_summary'] ) ); ?>>
					<label for="search_enable_summary">Generate AI-powered summary at the top of search results (like Google)</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_system_prompt">Search System Prompt</label>
				</th>
				<td>
					<textarea id="search_system_prompt" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_system_prompt]" rows="15" class="large-text"><?php echo esc_textarea( $settings['search_system_prompt'] ?? '' ); ?></textarea>
					<p class="description">
						Customize how the AI analyzes and presents search results.
						<strong>Content Preferences:</strong> Edit the "CONTENT PREFERENCES" section
						to control which types of content are prioritized without writing code.
					</p>
					<details style="margin-top: 10px;">
						<summary style="cursor: pointer; color: #2271b1;">Examples of Content Preferences</summary>
						<div style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
							<p><strong>Platform-Specific Content:</strong></p>
							<code style="display: block; white-space: pre-wrap; margin: 5px 0; font-family: monospace; background: #fff; padding: 8px; border-radius: 3px;">- When users ask about WordPress, prioritize WordPress-related pages and resources
- When users ask about Drupal, prioritize Drupal-related pages and resources
- If query mentions one platform, de-emphasize content about other platforms</code>

							<p style="margin-top: 15px;"><strong>Custom Post Types:</strong></p>
							<code style="display: block; white-space: pre-wrap; margin: 5px 0; font-family: monospace; background: #fff; padding: 8px; border-radius: 3px;">- For service-related queries, prioritize pages from the Services section
- When users ask about case studies or examples, emphasize case study pages
- Product questions should focus on product pages over blog posts</code>

							<p style="margin-top: 15px;"><strong>Content Freshness:</strong></p>
							<code style="display: block; white-space: pre-wrap; margin: 5px 0; font-family: monospace; background: #fff; padding: 8px; border-radius: 3px;">- Prefer recently updated content when available
- For technical questions, prioritize documentation over blog posts
- News queries should emphasize the most recent articles</code>
						</div>
					</details>
				</td>
			</tr>
		</table>

		<h3>Advanced Relevance Boosting</h3>
		<p>Fine-tune how search results are ranked using algorithmic boosting. These are simple, fast algorithmic adjustments applied before AI summarization.</p>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="search_relevance_enabled">Enable Relevance Boosting</label>
				</th>
				<td>
					<input type="checkbox" id="search_relevance_enabled" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_relevance_enabled]" value="1" <?php checked( ! empty( $settings['search_relevance_enabled'] ) ); ?>>
					<label for="search_relevance_enabled">Apply relevance boosting to improve result ranking</label>
					<p class="description">Boosts results based on URL matches, title matches, and post types. For content preferences (like WordPress vs Drupal), use the System Prompt above.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_url_boost">URL Slug Match Boost</label>
				</th>
				<td>
					<input type="number" id="search_url_boost" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_url_boost]" value="<?php echo esc_attr( $settings['search_url_boost'] ?? 0.15 ); ?>" min="0" max="1" step="0.01" class="small-text">
					<p class="description">Boost for results where query words appear in the URL slug (0.0-1.0, default: 0.15)</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_title_exact_boost">Exact Title Match Boost</label>
				</th>
				<td>
					<input type="number" id="search_title_exact_boost" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_title_exact_boost]" value="<?php echo esc_attr( $settings['search_title_exact_boost'] ?? 0.12 ); ?>" min="0" max="1" step="0.01" class="small-text">
					<p class="description">Boost when page title exactly matches the search query (0.0-1.0, default: 0.12)</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_title_words_boost">Title All Words Boost</label>
				</th>
				<td>
					<input type="number" id="search_title_words_boost" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_title_words_boost]" value="<?php echo esc_attr( $settings['search_title_words_boost'] ?? 0.08 ); ?>" min="0" max="1" step="0.01" class="small-text">
					<p class="description">Boost when page title contains all query words (0.0-1.0, default: 0.08)</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="search_page_boost">Page Post Type Boost</label>
				</th>
				<td>
					<input type="number" id="search_page_boost" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[search_page_boost]" value="<?php echo esc_attr( $settings['search_page_boost'] ?? 0.05 ); ?>" min="0" max="1" step="0.01" class="small-text">
					<p class="description">Boost for WordPress pages (typically more authoritative) (0.0-1.0, default: 0.05)</p>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<p class="description">
						<strong>Note:</strong> For advanced customizations like custom post type boosts or category-based filtering,
						use the <code>semantic_knowledge_search_relevance_config</code> filter in your theme or custom plugin.
						See the plugin documentation for examples.
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Indexer tab
	 *
	 * @param array $settings Current settings
	 */
	private function render_indexer_tab( $settings ) {
		?>
		<h3>Content Indexing</h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="post_types">Post Types to Index</label>
				</th>
				<td>
					<input type="text" id="post_types" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[post_types]" value="<?php echo esc_attr( $settings['post_types'] ?? 'posts,pages' ); ?>" class="large-text">
					<p class="description">Comma-separated list (e.g., posts,pages,staff,case-study,testimonials,services)</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="post_types_exclude">Post Types to Exclude</label>
				</th>
				<td>
					<textarea id="post_types_exclude" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[post_types_exclude]" rows="3" class="large-text"><?php echo esc_textarea( $settings['post_types_exclude'] ?? 'attachment,revision,nav_menu_item,customize_changeset,custom_css,oembed_cache,user_request,wp_block,wp_template,wp_template_part,wp_navigation,media,menu-items,blocks,templates,template-parts,global-styles,navigation,font-families,wpcf7_contact_form' ); ?></textarea>
					<p class="description">Comma-separated list of post types to exclude from indexing</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="auto_discover">Auto-discover Post Types</label>
				</th>
				<td>
					<input type="checkbox" id="auto_discover" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[auto_discover]" value="1" <?php checked( ! empty( $settings['auto_discover'] ) ); ?>>
					<label for="auto_discover">Automatically discover and index REST-visible post types</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="clean_deleted">Clean Deleted Content</label>
				</th>
				<td>
					<input type="checkbox" id="clean_deleted" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[clean_deleted]" value="1" <?php checked( ! empty( $settings['clean_deleted'] ) ); ?>>
					<label for="clean_deleted">Remove vectors for deleted posts during indexing</label>
				</td>
			</tr>
		</table>

		<h3>Chunking Configuration</h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="chunk_size">Chunk Size</label>
				</th>
				<td>
					<input type="number" id="chunk_size" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[chunk_size]" value="<?php echo esc_attr( $settings['chunk_size'] ?? 1200 ); ?>" min="100" max="10000" class="small-text">
					<p class="description">Number of characters per chunk (100-10000). Default: 1200</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="chunk_overlap">Chunk Overlap</label>
				</th>
				<td>
					<input type="number" id="chunk_overlap" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[chunk_overlap]" value="<?php echo esc_attr( $settings['chunk_overlap'] ?? 200 ); ?>" min="0" max="1000" class="small-text">
					<p class="description">Number of overlapping characters between chunks (0-1000). Default: 200</p>
				</td>
			</tr>
		</table>

		<h3>Node.js Configuration</h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="indexer_node_path">Custom Node.js Path</label>
				</th>
				<td>
					<input type="text" id="indexer_node_path" name="<?php echo esc_attr( WP_AI_Core::OPTION_KEY ); ?>[indexer_node_path]" value="<?php echo esc_attr( $settings['indexer_node_path'] ?? '' ); ?>" class="large-text" placeholder="/usr/local/bin/node">
					<p class="description">
						Optional: Specify a custom path to the Node.js executable. Leave empty to use auto-detection.
						The plugin will check this path first, then fall back to <code>which node</code> and standard system paths.
					</p>
				</td>
			</tr>
		</table>

		<h3>System Information</h3>
		<table class="form-table">
			<tr>
				<th scope="row">Current Domain</th>
				<td>
					<code><?php echo esc_html( $this->core->get_current_domain() ); ?></code>
					<p class="description">All indexed content will include this domain in metadata</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Schema Version</th>
				<td>
					<code><?php echo esc_html( $this->core->get_schema_version() ); ?></code>
					<p class="description">Current schema version with domain filtering support</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Indexer Settings Endpoint</th>
				<td>
					<code><?php echo esc_html( rest_url( 'semantic-knowledge/v1/indexer-settings' ) ); ?></code>
					<p class="description">The <code>@kanopi/wp-ai-indexer</code> package fetches configuration from this endpoint</p>
				</td>
			</tr>
		</table>
		<?php
	}
}
