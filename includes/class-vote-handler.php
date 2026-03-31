<?php
/**
 * Business logic for processing a vote submission.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Vote_Handler
 */
class Content_Vote_Handler {

	/**
	 * Maximum votes per visitor per hour (rate limiting).
	 */
	const RATE_LIMIT_MAX = 30;

	/**
	 * Rate limit window in seconds.
	 */
	const RATE_LIMIT_WINDOW = HOUR_IN_SECONDS;

	/**
	 * Processes a vote payload.
	 *
	 * @param array{section_id: string, page_url: string, vote_type: string|int} $payload Raw POST data.
	 *
	 * @return array{success: bool, message: string, up: int, down: int, user_vote: int}
	 */
	public static function handle( array $payload ): array {
		// --- Validate section_id --------------------------------------------------
		$section_id = sanitize_text_field( $payload['section_id'] ?? '' );
		if ( ! preg_match( '/^[a-zA-Z0-9_-]{1,100}$/', $section_id ) ) {
			return self::error( __( 'Invalid section identifier.', 'content-vote' ) );
		}

		// --- Validate vote_type (1, -1, or 0 for "remove vote") ------------------
		$vote_type = (int) ( $payload['vote_type'] ?? 999 );
		if ( ! in_array( $vote_type, array( 1, -1, 0 ), true ) ) {
			return self::error( __( 'Invalid vote type.', 'content-vote' ) );
		}

		// --- Validate & normalise page_url ----------------------------------------
		$raw_url  = sanitize_url( $payload['page_url'] ?? '' );
		$page_url = self::normalise_url( $raw_url );
		if ( empty( $page_url ) ) {
			return self::error( __( 'Invalid page URL.', 'content-vote' ) );
		}

		// Reject admin-area URLs — prevents votes from the Elementor editor preview.
		$admin_path = wp_parse_url( admin_url(), PHP_URL_PATH );
		$req_path   = wp_parse_url( $page_url, PHP_URL_PATH ) ?? '/';
		if ( $admin_path && str_starts_with( $req_path, $admin_path ) ) {
			return self::error( __( 'Voting is not available in the editor.', 'content-vote' ) );
		}

		// Reject URLs that point to external domains.
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$req_host  = wp_parse_url( $page_url, PHP_URL_HOST );
		if ( $home_host !== $req_host ) {
			return self::error( __( 'URL does not belong to this site.', 'content-vote' ) );
		}

		// --- Rate limiting --------------------------------------------------------
		$visitor_hash = Content_Vote_Visitor_Identity::get_hash();
		if ( ! self::check_rate_limit( $visitor_hash ) ) {
			return self::error( __( 'You are voting too quickly. Please wait before voting again.', 'content-vote' ) );
		}

		// --- Toggle / Deduplication -----------------------------------------------
		$existing = Content_Vote_Database::get_existing_vote( $visitor_hash, $section_id, $page_url );

		if ( null !== $existing ) {
			if ( 0 === $vote_type || $existing === $vote_type ) {
				// Remove vote: either explicit removal or clicking the same button again.
				Content_Vote_Database::delete_vote( $visitor_hash, $section_id, $page_url );
				$counts = Content_Vote_Database::get_counts( $section_id, $page_url );
				return array(
					'success'   => true,
					'message'   => '',
					'up'        => $counts['up'],
					'down'      => $counts['down'],
					'user_vote' => 0,
				);
			}
			// Different vote — change it.
			Content_Vote_Database::change_vote( $visitor_hash, $section_id, $page_url, $vote_type );
		} else {
			if ( 0 === $vote_type ) {
				// Nothing to remove.
				$counts = Content_Vote_Database::get_counts( $section_id, $page_url );
				return array(
					'success'   => true,
					'message'   => '',
					'up'        => $counts['up'],
					'down'      => $counts['down'],
					'user_vote' => 0,
				);
			}
			// New vote.
			if ( ! Content_Vote_Database::insert_vote( $visitor_hash, $section_id, $page_url, $vote_type ) ) {
				return self::error( __( 'Could not save your vote. Please try again.', 'content-vote' ) );
			}
			self::increment_rate_limit( $visitor_hash );
		}

		$counts = Content_Vote_Database::get_counts( $section_id, $page_url );

		return array(
			'success'   => true,
			'message'   => '',
			'up'        => $counts['up'],
			'down'      => $counts['down'],
			'user_vote' => $vote_type,
		);
	}

	/**
	 * Normalises a URL: strips query string, fragments; lowercases host; ensures trailing slash.
	 *
	 * @param string $url Raw URL.
	 *
	 * @return string
	 */
	private static function normalise_url( string $url ): string {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}
		return trailingslashit(
			strtolower( $parts['scheme'] ) . '://' .
			strtolower( $parts['host'] ) .
			( ! empty( $parts['path'] ) ? $parts['path'] : '/' )
		);
	}

	/**
	 * Checks whether the visitor is within the rate limit.
	 *
	 * @param string $visitor_hash Visitor fingerprint.
	 *
	 * @return bool True if under limit, false if over.
	 */
	private static function check_rate_limit( string $visitor_hash ): bool {
		$key   = 'cv_rl_' . substr( $visitor_hash, 0, 32 );
		$count = (int) get_transient( $key );
		return $count < self::RATE_LIMIT_MAX;
	}

	/**
	 * Increments the rate-limit counter.
	 *
	 * @param string $visitor_hash Visitor fingerprint.
	 */
	private static function increment_rate_limit( string $visitor_hash ): void {
		$key   = 'cv_rl_' . substr( $visitor_hash, 0, 32 );
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );
	}

	/**
	 * Returns a normalised error array.
	 *
	 * @param string $message Error message.
	 *
	 * @return array{success: false, message: string, up: int, down: int, user_vote: int}
	 */
	private static function error( string $message ): array {
		return array(
			'success'   => false,
			'message'   => $message,
			'up'        => 0,
			'down'      => 0,
			'user_vote' => 0,
		);
	}
}
