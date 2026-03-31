<?php
/**
 * Frontend hooks: asset enqueueing.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Vote_Public
 */
class Content_Vote_Public {

	/**
	 * Registers WordPress frontend hooks.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueues the public-facing stylesheet and script.
	 *
	 * Scripts are loaded only when the Elementor widget is present on the page.
	 * This avoids unnecessary HTTP requests on pages without the widget.
	 */
	public static function enqueue_assets(): void {
		// Register styles/scripts unconditionally so the widget's get_style_depends
		// and get_script_depends can reference them; Elementor will enqueue on demand.
		wp_register_style(
			'content-vote-public',
			CONTENT_VOTE_URL . 'assets/css/content-vote-public.css',
			array(),
			CONTENT_VOTE_VERSION
		);

		wp_register_script(
			'content-vote-public',
			CONTENT_VOTE_URL . 'assets/js/content-vote-public.js',
			array(),
			CONTENT_VOTE_VERSION,
			array( 'strategy' => 'defer' )
		);

		wp_localize_script(
			'content-vote-public',
			'ContentVote',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'content_vote_nonce' ),
				'i18n'    => array(
					'error'   => __( 'An error occurred. Please try again.', 'content-vote' ),
					'thanks'  => __( 'Thank you for your feedback!', 'content-vote' ),
				),
			)
		);
	}
}
