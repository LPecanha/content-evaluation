<?php
/**
 * Fired when the plugin is uninstalled (deleted).
 *
 * @package ContentVote
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table = $wpdb->prefix . 'content_votes';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // nosemgrep: sql-injection — table name is prefixed, not user input.

delete_option( 'content_vote_version' );
delete_option( 'content_vote_trusted_proxies' );

// Remove cached transients.
delete_transient( 'cv_voted_pages_titles' );

// Remove all rate-limit transients (they share the cv_rl_ prefix).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_cv_rl_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_cv_rl_' ) . '%'
	)
);
