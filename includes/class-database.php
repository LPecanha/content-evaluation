<?php
/**
 * Database layer for Content Vote.
 *
 * All queries use $wpdb->prepare() — never raw string interpolation of user data.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Vote_Database
 */
class Content_Vote_Database {

	const TABLE_NAME = 'content_votes';

	/**
	 * Returns the full prefixed table name.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Creates (or updates) the plugin table using dbDelta.
	 */
	public static function create_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::table();

		$sql = "CREATE TABLE {$table} (
			id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			page_url      VARCHAR(500)        NOT NULL,
			section_id    VARCHAR(100)        NOT NULL,
			vote_type     TINYINT(1)          NOT NULL,
			visitor_hash  VARCHAR(64)         NOT NULL,
			voted_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY   uq_visitor_section  (visitor_hash, section_id, page_url(191)),
			KEY          idx_section_page    (section_id, page_url(191)),
			KEY          idx_voted_at        (voted_at),
			KEY          idx_page_url        (page_url(191))
		) ENGINE=InnoDB {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Checks whether a visitor has already voted on a specific section.
	 *
	 * @param string $visitor_hash Hashed visitor fingerprint.
	 * @param string $section_id   Elementor section CSS ID.
	 * @param string $page_url     Normalised page URL.
	 *
	 * @return int|null  1 or -1 if voted, null if not.
	 */
	public static function get_existing_vote( string $visitor_hash, string $section_id, string $page_url ): ?int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT vote_type FROM %i WHERE visitor_hash = %s AND section_id = %s AND page_url = %s LIMIT 1',
				self::table(),
				$visitor_hash,
				$section_id,
				$page_url
			)
		);

		return null !== $result ? (int) $result : null;
	}

	/**
	 * Inserts a new vote record.
	 *
	 * @param string $visitor_hash Hashed visitor fingerprint.
	 * @param string $section_id   Elementor section CSS ID.
	 * @param string $page_url     Normalised page URL.
	 * @param int    $vote_type    1 (up) or -1 (down).
	 *
	 * @return bool
	 */
	public static function insert_vote( string $visitor_hash, string $section_id, string $page_url, int $vote_type ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			self::table(),
			array(
				'visitor_hash' => $visitor_hash,
				'section_id'   => $section_id,
				'page_url'     => $page_url,
				'vote_type'    => $vote_type,
				'voted_at'     => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%d', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Deletes a visitor's vote (toggle-off scenario).
	 *
	 * @param string $visitor_hash
	 * @param string $section_id
	 * @param string $page_url
	 *
	 * @return bool
	 */
	public static function delete_vote( string $visitor_hash, string $section_id, string $page_url ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			self::table(),
			array(
				'visitor_hash' => $visitor_hash,
				'section_id'   => $section_id,
				'page_url'     => $page_url,
			),
			array( '%s', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Updates an existing vote (vote-change scenario).
	 *
	 * @param string $visitor_hash Hashed visitor fingerprint.
	 * @param string $section_id   Elementor section CSS ID.
	 * @param string $page_url     Normalised page URL.
	 * @param int    $new_type     New vote type (1 or -1).
	 *
	 * @return bool
	 */
	public static function change_vote( string $visitor_hash, string $section_id, string $page_url, int $new_type ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			self::table(),
			array(
				'vote_type' => $new_type,
				'voted_at'  => current_time( 'mysql', true ),
			),
			array(
				'visitor_hash' => $visitor_hash,
				'section_id'   => $section_id,
				'page_url'     => $page_url,
			),
			array( '%d', '%s' ),
			array( '%s', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Returns up and down counts for a specific section.
	 *
	 * @param string $section_id Elementor section CSS ID.
	 * @param string $page_url   Normalised page URL.
	 *
	 * @return array{up: int, down: int}
	 */
	public static function get_counts( string $section_id, string $page_url ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT vote_type, COUNT(*) as total FROM %i WHERE section_id = %s AND page_url = %s GROUP BY vote_type',
				self::table(),
				$section_id,
				$page_url
			),
			ARRAY_A
		);

		$counts = array( 'up' => 0, 'down' => 0 );
		if ( $rows ) {
			foreach ( $rows as $row ) {
				if ( 1 === (int) $row['vote_type'] ) {
					$counts['up'] = (int) $row['total'];
				} elseif ( -1 === (int) $row['vote_type'] ) {
					$counts['down'] = (int) $row['total'];
				}
			}
		}

		return $counts;
	}

	/**
	 * Returns aggregated report data grouped by page_url + section_id.
	 *
	 * @param array{
	 *   page_url?: string,
	 *   date_from?: string,
	 *   date_to?: string,
	 *   per_page?: int,
	 *   offset?: int,
	 *   orderby?: string,
	 *   order?: string,
	 * } $filters
	 *
	 * @return array{rows: list<array>, total: int}
	 */
	public static function get_report( array $filters ): array {
		global $wpdb;

		$table    = self::table();
		$where    = array( '1=1' );
		$params   = array();

		// Filter: specific page URL.
		if ( ! empty( $filters['page_url'] ) ) {
			$where[]  = 'page_url = %s';
			$params[] = sanitize_url( $filters['page_url'] );
		}

		// Filter: month (YYYY-MM format — takes priority over date_from/date_to).
		if ( ! empty( $filters['month'] ) ) {
			$month    = sanitize_text_field( $filters['month'] );
			$where[]  = "DATE_FORMAT(voted_at, '%%Y-%%m') = %s";
			$params[] = $month;
		} elseif ( ! empty( $filters['date_from'] ) || ! empty( $filters['date_to'] ) ) {
			if ( ! empty( $filters['date_from'] ) ) {
				$where[]  = 'voted_at >= %s';
				$params[] = sanitize_text_field( $filters['date_from'] ) . ' 00:00:00';
			}
			if ( ! empty( $filters['date_to'] ) ) {
				$where[]  = 'voted_at <= %s';
				$params[] = sanitize_text_field( $filters['date_to'] ) . ' 23:59:59';
			}
		}

		$where_clause = implode( ' AND ', $where );

		// Allowed orderby columns to prevent SQL injection.
		$allowed_orderby = array( 'page_url', 'section_id', 'total_up', 'total_down', 'total_votes', 'last_vote' );
		$orderby         = in_array( $filters['orderby'] ?? '', $allowed_orderby, true ) ? $filters['orderby'] : 'total_votes';
		$order           = 'ASC' === strtoupper( $filters['order'] ?? '' ) ? 'ASC' : 'DESC';

		$per_page = max( 1, min( 100, (int) ( $filters['per_page'] ?? 25 ) ) );
		$offset   = max( 0, (int) ( $filters['offset'] ?? 0 ) );

		// Build aggregate query.
		$base_query = "SELECT
				page_url,
				section_id,
				SUM( CASE WHEN vote_type = 1 THEN 1 ELSE 0 END )  AS total_up,
				SUM( CASE WHEN vote_type = -1 THEN 1 ELSE 0 END ) AS total_down,
				COUNT(*)                                            AS total_votes,
				MAX(voted_at)                                       AS last_vote
			FROM {$table}
			WHERE {$where_clause}
			GROUP BY page_url, section_id";

		// Total count for pagination.
		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM ({$base_query}) AS sub", ...$params ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM ({$base_query}) AS sub" );
		}

		// Paginated rows.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$paginated_query = "{$base_query} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$row_params      = array_merge( $params, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( $paginated_query, ...$row_params ), ARRAY_A );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'rows'  => $rows ?: array(),
			'total' => $total,
		);
	}

	/**
	 * Returns a distinct list of page URLs that have at least one vote.
	 *
	 * @return list<string>
	 */
	public static function get_voted_pages(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare( 'SELECT DISTINCT page_url FROM %i ORDER BY page_url ASC', self::table() )
		);

		return $rows ?: array();
	}

	/**
	 * Returns voted pages with their WordPress post titles.
	 *
	 * Uses url_to_postid() to resolve each URL to a post. Falls back to the
	 * raw URL when no matching post is found (e.g. custom routes).
	 *
	 * @return list<array{url: string, title: string}>
	 */
	public static function get_voted_pages_with_titles(): array {
		$urls   = self::get_voted_pages();
		$result = array();

		foreach ( $urls as $url ) {
			$post_id = url_to_postid( $url );
			$title   = $post_id ? get_the_title( $post_id ) : $url;
			$result[] = array(
				'url'   => $url,
				'title' => $title ?: $url,
			);
		}

		// Sort alphabetically by title.
		usort( $result, fn( $a, $b ) => strcmp( $a['title'], $b['title'] ) );

		return $result;
	}

	/**
	 * Returns the distinct months (YYYY-MM) that have votes, for the month filter.
	 *
	 * @return list<string>
	 */
	public static function get_voted_months(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT DATE_FORMAT(voted_at, '%%Y-%%m') AS month FROM %i ORDER BY month DESC",
				self::table()
			)
		);

		return $rows ?: array();
	}
}
