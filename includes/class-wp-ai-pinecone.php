<?php
/**
 * Pinecone vector database integration for WP AI Assistant
 * Handles vector queries with domain filtering
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_AI_Pinecone {
	private $core;
	private $secrets;
	private $api_key;
	private $index_host;
	private $index_name;

	public function __construct( WP_AI_Core $core, WP_AI_Secrets $secrets ) {
		$this->core = $core;
		$this->secrets = $secrets;
		$this->api_key = $this->secrets->get_secret_or_setting( 'PINECONE_API_KEY', 'pinecone_api_key' );
		$this->index_host = untrailingslashit( $this->core->get_setting( 'pinecone_index_host' ) );
		$this->index_name = $this->core->get_setting( 'pinecone_index_name' );
	}

	/**
	 * Query Pinecone for similar vectors with domain filter
	 *
	 * This method automatically applies domain filtering to ensure
	 * results only come from the current WordPress site.
	 *
	 * @param array $vector Embedding vector
	 * @param int $top_k Number of results to return
	 * @return array|WP_Error Array of matches or error
	 */
	public function query_with_domain_filter( $vector, $top_k ) {
		$domain = $this->core->get_current_domain();

		return $this->query(
			$vector,
			$top_k,
			array(
				'domain' => array( '$eq' => $domain ),
			)
		);
	}

	/**
	 * Query Pinecone for similar vectors
	 *
	 * @param array $vector Embedding vector
	 * @param int $top_k Number of results to return
	 * @param array $filter Optional Pinecone filter
	 * @return array|WP_Error Array of matches or error
	 */
	public function query( $vector, $top_k, $filter = array() ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error(
				'wp_ai_assistant_missing_key',
				'Pinecone API key is not configured.',
				array( 'status' => 500 )
			);
		}

		if ( empty( $this->index_host ) ) {
			return new WP_Error(
				'wp_ai_assistant_missing_config',
				'Pinecone index host is not configured.',
				array( 'status' => 500 )
			);
		}

		$request_body = array(
			'topK'            => $top_k,
			'includeMetadata' => true,
			'vector'          => $vector,
		);

		// Add filter if provided
		if ( ! empty( $filter ) ) {
			$request_body['filter'] = $filter;
		}

		$request = wp_remote_post(
			trailingslashit( $this->index_host ) . 'query',
			array(
				'headers' => array(
					'Api-Key'      => $this->api_key,
					'Content-Type' => 'application/json',
				),
				'timeout' => 20,
				'body'    => wp_json_encode( $request_body ),
			)
		);

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$code = wp_remote_retrieve_response_code( $request );

		if ( 200 !== $code ) {
			$error_message = $this->extract_error_message( $request );
			return new WP_Error(
				'wp_ai_assistant_pinecone_error',
				'Pinecone query failed: ' . $error_message,
				array( 'status' => $code )
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );

		return ! empty( $body['matches'] ) ? $body['matches'] : array();
	}

	/**
	 * Format Pinecone matches for display
	 *
	 * @param array $matches Pinecone matches array
	 * @return array Formatted matches
	 */
	public function format_matches( $matches ) {
		if ( empty( $matches ) ) {
			return array();
		}

		$formatted = array();

		foreach ( $matches as $match ) {
			if ( empty( $match['metadata'] ) ) {
				continue;
			}

			$metadata = $match['metadata'];

			$formatted[] = array(
				'id'       => $match['id'] ?? '',
				'score'    => $match['score'] ?? 0,
				'title'    => $metadata['title'] ?? '',
				'url'      => $metadata['url'] ?? '',
				'chunk'    => $metadata['chunk'] ?? '',
				'post_id'  => $metadata['post_id'] ?? 0,
				'post_type' => $metadata['post_type'] ?? '',
				'domain'   => $metadata['domain'] ?? '',
			);
		}

		return $formatted;
	}

	/**
	 * Extract error message from API response
	 *
	 * @param array|WP_Error $response API response
	 * @return string Error message
	 */
	private function extract_error_message( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['error']['message'] ) ) {
			return $body['error']['message'];
		}

		if ( ! empty( $body['message'] ) ) {
			return $body['message'];
		}

		return 'Unknown error';
	}

	/**
	 * Check if Pinecone is configured
	 *
	 * @return bool
	 */
	public function is_configured() {
		return ! empty( $this->api_key ) && ! empty( $this->index_host ) && ! empty( $this->index_name );
	}

	/**
	 * Get index statistics (for debugging)
	 *
	 * @return array|WP_Error Index stats or error
	 */
	public function get_index_stats() {
		if ( empty( $this->api_key ) || empty( $this->index_host ) ) {
			return new WP_Error(
				'wp_ai_assistant_missing_config',
				'Pinecone is not fully configured.',
				array( 'status' => 500 )
			);
		}

		$request = wp_remote_post(
			trailingslashit( $this->index_host ) . 'describe_index_stats',
			array(
				'headers' => array(
					'Api-Key'      => $this->api_key,
					'Content-Type' => 'application/json',
				),
				'timeout' => 10,
				'body'    => wp_json_encode( array() ),
			)
		);

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$code = wp_remote_retrieve_response_code( $request );

		if ( 200 !== $code ) {
			return new WP_Error(
				'wp_ai_assistant_pinecone_error',
				'Failed to get index stats.',
				array( 'status' => $code )
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );

		return $body;
	}
}
