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
