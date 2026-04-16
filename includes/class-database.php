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

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT vote_type FROM {$table} WHERE visitor_hash = %s AND section_id = %s AND page_url = %s LIMIT 1",
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

		if ( false !== $result ) {
			self::invalidate_voted_pages_cache();
		}

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

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT vote_type, COUNT(*) as total FROM {$table} WHERE section_id = %s AND page_url = %s GROUP BY vote_type",
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
	 * Builds a reusable WHERE clause and params array from filter options.
	 *
	 * @param array $filters
	 * @return array{0: string, 1: array}  [$where_clause, $params]
	 */
	private static function build_where( array $filters ): array {
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $filters['page_url'] ) ) {
			$where[]  = 'page_url = %s';
			$params[] = sanitize_url( $filters['page_url'] );
		}

		if ( ! empty( $filters['month'] ) ) {
			$where[]  = "DATE_FORMAT(voted_at, '%%Y-%%m') = %s";
			$params[] = sanitize_text_field( $filters['month'] );
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

		return array( implode( ' AND ', $where ), $params );
	}

	/**
	 * Returns page-level vote aggregates (one row per page), paginated.
	 * Used for the grouped admin report.
	 *
	 * @param array  $filters
	 * @param int    $per_page
	 * @param int    $offset
	 * @param string $orderby  Allowed: page_url | total_up | total_down | total_votes | section_count | last_vote
	 * @param string $order    ASC | DESC
	 *
	 * @return list<array>
	 */
	public static function get_pages_summary( array $filters, int $per_page, int $offset, string $orderby, string $order ): array {
		global $wpdb;

		$table = self::table();
		[ $where_clause, $params ] = self::build_where( $filters );

		$allowed  = array( 'page_url', 'total_up', 'total_down', 'total_votes', 'section_count', 'last_vote' );
		$orderby  = in_array( $orderby, $allowed, true ) ? $orderby : 'total_votes';
		$order    = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = "SELECT
				page_url,
				SUM( CASE WHEN vote_type = 1 THEN 1 ELSE 0 END )  AS total_up,
				SUM( CASE WHEN vote_type = -1 THEN 1 ELSE 0 END ) AS total_down,
				COUNT(*)                                            AS total_votes,
				COUNT( DISTINCT section_id )                        AS section_count,
				MAX( voted_at )                                     AS last_vote
			FROM {$table}
			WHERE {$where_clause}
			GROUP BY page_url
			ORDER BY {$orderby} {$order}
			LIMIT %d OFFSET %d";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$all_params = array_merge( $params, array( $per_page, $offset ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $query, ...$all_params ), ARRAY_A );

		return $rows ?: array();
	}

	/**
	 * Returns the total number of distinct pages that have votes, respecting filters.
	 *
	 * @param array $filters
	 * @return int
	 */
	public static function get_pages_count( array $filters ): int {
		global $wpdb;

		$table = self::table();
		[ $where_clause, $params ] = self::build_where( $filters );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = "SELECT COUNT( DISTINCT page_url ) FROM {$table} WHERE {$where_clause}";

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( $query, ...$params ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Returns section-level details for a list of page URLs, respecting filters.
	 * Results are ordered page_url ASC, total_votes DESC.
	 *
	 * @param list<string> $page_urls
	 * @param array        $filters
	 *
	 * @return list<array>
	 */
	public static function get_sections_by_page_urls( array $page_urls, array $filters ): array {
		global $wpdb;

		if ( empty( $page_urls ) ) {
			return array();
		}

		$table = self::table();
		[ $where_clause, $params ] = self::build_where( $filters );

		$placeholders = implode( ', ', array_fill( 0, count( $page_urls ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = "SELECT
				page_url,
				section_id,
				SUM( CASE WHEN vote_type = 1 THEN 1 ELSE 0 END )  AS total_up,
				SUM( CASE WHEN vote_type = -1 THEN 1 ELSE 0 END ) AS total_down,
				COUNT(*)                                            AS total_votes,
				MAX( voted_at )                                     AS last_vote
			FROM {$table}
			WHERE {$where_clause} AND page_url IN ({$placeholders})
			GROUP BY page_url, section_id
			ORDER BY page_url ASC, total_votes DESC";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$all_params = array_merge( $params, $page_urls );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $query, ...$all_params ), ARRAY_A );

		return $rows ?: array();
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

		$table                   = self::table();
		[ $where_clause, $params ] = self::build_where( $filters );

		// Allowed orderby columns to prevent SQL injection.
		$allowed_orderby = array( 'page_url', 'section_id', 'total_up', 'total_down', 'total_votes', 'last_vote' );
		$orderby         = in_array( $filters['orderby'] ?? '', $allowed_orderby, true ) ? $filters['orderby'] : 'total_votes';
		$order           = 'ASC' === strtoupper( $filters['order'] ?? '' ) ? 'ASC' : 'DESC';

		$per_page = max( 1, min( 10000, (int) ( $filters['per_page'] ?? 25 ) ) );
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

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_col( "SELECT DISTINCT page_url FROM {$table} ORDER BY page_url ASC" );

		return $rows ?: array();
	}

	/**
	 * Cache key for the voted-pages-with-titles result.
	 */
	const VOTED_PAGES_CACHE_KEY = 'cv_voted_pages_titles';

	/**
	 * Returns voted pages with their WordPress post titles.
	 *
	 * Results are cached in a transient to avoid running one url_to_postid()
	 * query per URL on every admin page load. Cache is invalidated whenever
	 * a new vote is inserted on a previously-unseen URL.
	 *
	 * @return list<array{url: string, title: string}>
	 */
	public static function get_voted_pages_with_titles(): array {
		$cached = get_transient( self::VOTED_PAGES_CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

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

		set_transient( self::VOTED_PAGES_CACHE_KEY, $result, HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Invalidates the voted-pages cache. Called after inserts so newly-voted
	 * URLs appear in the admin filter dropdown on the next page load.
	 */
	public static function invalidate_voted_pages_cache(): void {
		delete_transient( self::VOTED_PAGES_CACHE_KEY );
	}

	/**
	 * Returns the distinct months (YYYY-MM) that have votes, for the month filter.
	 *
	 * @return list<string>
	 */
	public static function get_voted_months(): array {
		global $wpdb;

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_col(
			"SELECT DISTINCT DATE_FORMAT(voted_at, '%Y-%m') AS month FROM {$table} ORDER BY month DESC"
		);

		return $rows ?: array();
	}
}
