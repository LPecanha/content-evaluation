<?php
/**
 * Builds an anonymised visitor fingerprint from IP + User-Agent.
 *
 * The raw IP is never stored; only a salted SHA-256 hash is persisted,
 * which satisfies GDPR's data minimisation principle.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Vote_Visitor_Identity
 */
class Content_Vote_Visitor_Identity {

	/**
	 * Returns the visitor's anonymised hash.
	 *
	 * Respects X-Forwarded-For only when the request comes from a known
	 * trusted proxy (configured via the plugin option), to prevent spoofing.
	 *
	 * @return string 64-character hex SHA-256 hash.
	 */
	public static function get_hash(): string {
		$ip = self::resolve_ip();
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return hash( 'sha256', $ip . '|' . $ua . '|' . CONTENT_VOTE_SALT );
	}

	/**
	 * Resolves the real client IP, optionally trusting a reverse proxy.
	 *
	 * @return string
	 */
	private static function resolve_ip(): string {
		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$trusted     = get_option( 'content_vote_trusted_proxies', array() );

		if ( ! empty( $trusted ) && in_array( $remote_addr, (array) $trusted, true ) ) {
			// REMOTE_ADDR is a trusted proxy — read the real IP from the forwarded header.
			if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$forwarded = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
				// Take the first (leftmost) IP in the chain — that is the original client.
				$parts = array_map( 'trim', explode( ',', $forwarded ) );
				if ( filter_var( $parts[0], FILTER_VALIDATE_IP ) ) {
					return $parts[0];
				}
			}
		}

		return $remote_addr;
	}
}
