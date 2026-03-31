<?php
/**
 * Plugin Name:       Content Evaluation
 * Plugin URI:        https://github.com/LPecanha/content-evaluation
 * Description:       Entenda o que realmente ressoa com sua audiência. O Content Evaluation adiciona um widget ao Elementor que permite aos visitantes avaliarem cada seção do seu conteúdo — com ícones, emojis ou thumbs — e consolida tudo em um painel de relatórios para que você tome decisões baseadas em dados reais.
 * Version:           1.1.0
 * Author:            Lucas Peçanha
 * Author URI:        https://lucaspecanha.com.br
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       content-vote
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Elementor tested up to: 3.25
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CONTENT_VOTE_VERSION', '1.1.0' );
define( 'CONTENT_VOTE_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONTENT_VOTE_URL', plugin_dir_url( __FILE__ ) );
define( 'CONTENT_VOTE_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'CONTENT_VOTE_SALT' ) ) {
	define( 'CONTENT_VOTE_SALT', hash( 'sha256', ( defined( 'AUTH_SALT' ) ? AUTH_SALT : 'cv-fallback' ) . 'content_vote_v1' ) );
}

// Activation / Deactivation / Uninstall.
// These hooks fire before plugins_loaded, so we require the classes inline.
register_activation_hook(
	__FILE__,
	function () {
		require_once CONTENT_VOTE_PATH . 'includes/class-database.php';
		require_once CONTENT_VOTE_PATH . 'includes/class-activator.php';
		Content_Vote_Activator::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		require_once CONTENT_VOTE_PATH . 'includes/class-deactivator.php';
		Content_Vote_Deactivator::deactivate();
	}
);

/**
 * Minimum version checks before loading the plugin.
 *
 * @return bool
 */
function content_vote_meets_requirements() {
	if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>' .
					esc_html__( 'Content Vote requires PHP 8.1 or higher.', 'content-vote' ) .
					'</p></div>';
			}
		);
		return false;
	}

	if ( version_compare( get_bloginfo( 'version' ), '6.4', '<' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>' .
					esc_html__( 'Content Vote requires WordPress 6.4 or higher.', 'content-vote' ) .
					'</p></div>';
			}
		);
		return false;
	}

	return true;
}

/**
 * Load all plugin classes and bootstrap.
 */
function content_vote_init() {
	if ( ! content_vote_meets_requirements() ) {
		return;
	}

	require_once CONTENT_VOTE_PATH . 'includes/class-activator.php';
	require_once CONTENT_VOTE_PATH . 'includes/class-deactivator.php';
	require_once CONTENT_VOTE_PATH . 'includes/class-database.php';
	require_once CONTENT_VOTE_PATH . 'includes/class-visitor-identity.php';
	require_once CONTENT_VOTE_PATH . 'includes/class-vote-handler.php';
	require_once CONTENT_VOTE_PATH . 'includes/class-ajax-controller.php';
	require_once CONTENT_VOTE_PATH . 'admin/class-admin.php';
	require_once CONTENT_VOTE_PATH . 'public/class-public.php';
	require_once CONTENT_VOTE_PATH . 'elementor/class-elementor-integration.php';

	Content_Vote_Ajax_Controller::init();
	Content_Vote_Admin::init();
	Content_Vote_Public::init();
	Content_Vote_Elementor_Integration::init();
}
add_action( 'plugins_loaded', 'content_vote_init' );
