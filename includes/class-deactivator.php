<?php
/**
 * Fired during plugin deactivation.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Vote_Deactivator
 */
class Content_Vote_Deactivator {

	/**
	 * Plugin deactivation handler.
	 * Data is preserved on deactivation; only removed on uninstall.
	 */
	public static function deactivate(): void {
		// Nothing to do on deactivation — data is kept intact.
	}
}
