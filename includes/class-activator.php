<?php
/**
 * Fired during plugin activation.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Vote_Activator
 */
class Content_Vote_Activator {

	/**
	 * Plugin activation handler.
	 */
	public static function activate(): void {
		require_once CONTENT_VOTE_PATH . 'includes/class-database.php';
		Content_Vote_Database::create_table();
		update_option( 'content_vote_version', CONTENT_VOTE_VERSION );
	}
}
