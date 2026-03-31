<?php
/**
 * Integrates the plugin with Elementor.
 *
 * Checks that Elementor is active and loaded before registering
 * the custom widget category and widget class.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Vote_Elementor_Integration
 */
class Content_Vote_Elementor_Integration {

	/**
	 * Registers Elementor hooks.
	 *
	 * elementor/loaded fires before plugins_loaded completes, so we cannot
	 * nest our hooks inside it — they would never be called. Instead we hook
	 * elementor/widgets/register and elementor/elements/categories_registered
	 * directly here; both fire after plugins_loaded, so the timing is correct.
	 * We guard with a class_exists check so nothing blows up if Elementor is absent.
	 */
	public static function init(): void {
		if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
			// Elementor is not installed — register a notice and bail.
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-warning"><p>' .
						esc_html__( 'Content Vote requires Elementor to be installed and active.', 'content-vote' ) .
						'</p></div>';
				}
			);
			return;
		}

		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widget' ) );
	}

	/**
	 * Registers the "Content Vote" widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 */
	public static function register_category( $elements_manager ): void {
		$elements_manager->add_category(
			'content-vote-widgets',
			array(
				'title' => esc_html__( 'Content Vote', 'content-vote' ),
				'icon'  => 'fa fa-thumbs-up',
			)
		);
	}

	/**
	 * Registers the Content Vote widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public static function register_widget( $widgets_manager ): void {
		require_once CONTENT_VOTE_PATH . 'elementor/widgets/class-widget-content-vote.php';
		$widgets_manager->register( new Content_Vote_Widget() );
	}
}
