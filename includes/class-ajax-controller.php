<?php
/**
 * AJAX controller — registers and handles the vote submission endpoint.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Vote_Ajax_Controller
 */
class Content_Vote_Ajax_Controller {

	/**
	 * Registers WordPress AJAX hooks.
	 */
	public static function init(): void {
		add_action( 'wp_ajax_content_vote_submit', array( __CLASS__, 'handle_vote' ) );
		add_action( 'wp_ajax_nopriv_content_vote_submit', array( __CLASS__, 'handle_vote' ) );
	}

	/**
	 * Handles the AJAX vote request.
	 *
	 * Verifies nonce, sanitises input, delegates to Vote_Handler, returns JSON.
	 */
	public static function handle_vote(): void {
		// 1. Verify nonce — exits with -1 on failure.
		check_ajax_referer( 'content_vote_nonce', 'nonce' );

		// 2. Collect and sanitise raw POST data.
		$payload = array(
			'section_id' => isset( $_POST['section_id'] ) ? sanitize_text_field( wp_unslash( $_POST['section_id'] ) ) : '',
			'page_url'   => isset( $_POST['page_url'] ) ? sanitize_url( wp_unslash( $_POST['page_url'] ) ) : '',
			'vote_type'  => isset( $_POST['vote_type'] ) ? (int) $_POST['vote_type'] : 0,
		);

		// 3. Delegate to business logic.
		$result = Content_Vote_Handler::handle( $payload );

		// 4. Return structured JSON response.
		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result, 400 );
		}
	}
}
