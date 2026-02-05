<?php
/**
 * OpenAI API integration for Semantic Knowledge
 * Handles embeddings and chat completions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Semantic_Knowledge_OpenAI {
	private $core;
	private $secrets;
	private $api_key;

	public function __construct( WP_AI_Core $core, WP_AI_Secrets $secrets ) {
		$this->core = $core;
		$this->secrets = $secrets;
		$this->api_key = $this->secrets->get_secret_or_setting( 'OPENAI_API_KEY', 'openai_api_key' );
	}

	/**
	 * Create embedding vector using OpenAI API
	 *
	 * @param string $text Text to embed
	 * @return array|WP_Error Embedding vector or error
	 */
	public function create_embedding( $text ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error(
				'semantic_knowledge_missing_key',
				'OpenAI API key is not configured.',
				array( 'status' => 500 )
			);
		}

		$model = $this->core->get_setting( 'embedding_model', 'text-embedding-3-small' );
		$dimension = $this->core->get_setting( 'embedding_dimension', 1536 );

		$request_body = array(
			'model' => $model,
			'input' => $text,
		);

		// Only add dimensions parameter if it's greater than 0
		if ( $dimension > 0 ) {
			$request_body['dimensions'] = $dimension;
		}

		$request = wp_remote_post(
			'https://api.openai.com/v1/embeddings',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
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
				'semantic_knowledge_openai_error',
				'Unable to create embedding: ' . $error_message,
				array( 'status' => $code )
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( empty( $body['data'][0]['embedding'] ) ) {
			return new WP_Error(
				'semantic_knowledge_openai_error',
				'Embedding missing from response.',
				array( 'status' => 500 )
			);
		}

		return $body['data'][0]['embedding'];
	}

	/**
	 * Get chat completion from OpenAI
	 *
	 * @param string $question User's question
	 * @param string $context Context from Pinecone matches
	 * @param array $options Optional parameters (model, temperature, system_prompt)
	 * @return string|WP_Error Answer text or error
	 */
	public function chat_completion( $question, $context, $options = array() ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error(
				'semantic_knowledge_missing_key',
				'OpenAI API key is not configured.',
				array( 'status' => 500 )
			);
		}

		$defaults = array(
			'model'         => $this->core->get_setting( 'chatbot_model', 'gpt-4o-mini' ),
			'temperature'   => (float) $this->core->get_setting( 'chatbot_temperature', 0.2 ),
			'system_prompt' => $this->core->get_setting( 'chatbot_system_prompt', $this->get_default_system_prompt() ),
		);

		$options = array_merge( $defaults, $options );

		$messages = array(
			array(
				'role'    => 'system',
				'content' => $options['system_prompt'],
			),
			array(
				'role'    => 'system',
				'content' => 'Context: ' . $context,
			),
			array(
				'role'    => 'user',
				'content' => $question,
			),
		);

		$request = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
				'body'    => wp_json_encode(
					array(
						'model'       => $options['model'],
						'messages'    => $messages,
						'temperature' => $options['temperature'],
					)
				),
			)
		);

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$code = wp_remote_retrieve_response_code( $request );

		if ( 200 !== $code ) {
			$error_message = $this->extract_error_message( $request );
			return new WP_Error(
				'semantic_knowledge_openai_error',
				'Chat completion failed: ' . $error_message,
				array( 'status' => $code )
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( empty( $body['choices'][0]['message']['content'] ) ) {
			return new WP_Error(
				'semantic_knowledge_openai_error',
				'No answer returned.',
				array( 'status' => 500 )
			);
		}

		return $body['choices'][0]['message']['content'];
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

		return 'Unknown error';
	}

	/**
	 * Get default system prompt
	 *
	 * @return string
	 */
	private function get_default_system_prompt() {
		return 'You are a helpful assistant. Use the provided context to answer questions accurately and concisely.';
	}

	/**
	 * Check if OpenAI is configured
	 *
	 * @return bool
	 */
	public function is_configured() {
		return ! empty( $this->api_key );
	}
}
