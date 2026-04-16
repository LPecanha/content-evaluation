<?php
/**
 * Admin area: menu registration, asset enqueueing, report page, CSV export.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Vote_Admin
 */
class Content_Vote_Admin {

	/**
	 * Registers WordPress admin hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_content_vote_export_csv', array( __CLASS__, 'export_csv' ) );
	}

	/**
	 * Registers a top-level "Content Evaluation" menu in the WordPress sidebar.
	 */
	public static function register_menu(): void {
		add_menu_page(
			__( 'Content Evaluation', 'content-vote' ),
			__( 'Content Evaluation', 'content-vote' ),
			'manage_options',
			'content-evaluation',
			array( __CLASS__, 'render_report_page' ),
			'dashicons-chart-bar',
			25
		);

		// Rename the auto-generated first submenu item to "Report".
		add_submenu_page(
			'content-evaluation',
			__( 'Votes Report', 'content-vote' ),
			__( 'Votes Report', 'content-vote' ),
			'manage_options',
			'content-evaluation',
			array( __CLASS__, 'render_report_page' )
		);
	}

	/**
	 * Enqueues admin-only styles and scripts.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public static function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_content-evaluation' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'content-vote-admin',
			CONTENT_VOTE_URL . 'assets/css/content-vote-admin.css',
			array(),
			CONTENT_VOTE_VERSION
		);

		wp_enqueue_script(
			'content-vote-admin',
			CONTENT_VOTE_URL . 'assets/js/content-vote-admin.js',
			array(),
			CONTENT_VOTE_VERSION,
			array( 'strategy' => 'defer' )
		);
	}

	/**
	 * Renders the report page.
	 */
	public static function render_report_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'content-vote' ) );
		}

		$filters  = self::get_filters_from_request();
		$per_page = 20;
		$paged    = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$offset   = ( $paged - 1 ) * $per_page;
		// phpcs:disable WordPress.Security.NonceVerification
		$orderby  = sanitize_text_field( $_GET['orderby'] ?? 'total_votes' );
		$order    = sanitize_text_field( $_GET['order']   ?? 'DESC' );
		// phpcs:enable WordPress.Security.NonceVerification

		// Page-level aggregates (one row per page).
		$page_summaries = Content_Vote_Database::get_pages_summary( $filters, $per_page, $offset, $orderby, $order );
		$total_pages_db = Content_Vote_Database::get_pages_count( $filters );
		$total_pages    = (int) ceil( $total_pages_db / $per_page );

		// Section details for the pages on the current result page.
		$page_urls = array_column( $page_summaries, 'page_url' );
		$sections_flat = Content_Vote_Database::get_sections_by_page_urls( $page_urls, $filters );

		// Group sections by page_url for easy lookup in the view.
		$sections_map = array();
		foreach ( $sections_flat as $section ) {
			$sections_map[ $section['page_url'] ][] = $section;
		}

		$pages_with_titles = Content_Vote_Database::get_voted_pages_with_titles();
		$months            = Content_Vote_Database::get_voted_months();

		// Pass data to the view via variables (no globals).
		require CONTENT_VOTE_PATH . 'admin/views/report-page.php';
	}

	/**
	 * Exports the filtered report as a CSV download.
	 */
	public static function export_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'content-vote' ) );
		}

		check_admin_referer( 'content_vote_export_csv' );

		$filters  = self::get_filters_from_request();
		$filename = 'content-vote-report-' . gmdate( 'Y-m-d' ) . '.csv';

		// Send CSV headers.
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// BOM for Excel UTF-8 compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		fputcsv(
			$output,
			array(
				__( 'Page URL', 'content-vote' ),
				__( 'Section ID', 'content-vote' ),
				__( 'Upvotes', 'content-vote' ),
				__( 'Downvotes', 'content-vote' ),
				__( 'Total Votes', 'content-vote' ),
				__( 'Last Vote', 'content-vote' ),
			)
		);

		// Stream rows in chunks so memory stays bounded regardless of dataset size.
		$chunk_size = 500;
		$offset     = 0;
		do {
			$report = Content_Vote_Database::get_report(
				array_merge( $filters, array( 'per_page' => $chunk_size, 'offset' => $offset ) )
			);
			foreach ( $report['rows'] as $row ) {
				fputcsv(
					$output,
					array(
						$row['page_url'],
						$row['section_id'],
						$row['total_up'],
						$row['total_down'],
						$row['total_votes'],
						$row['last_vote'],
					)
				);
			}
			$fetched = count( $report['rows'] );
			$offset += $chunk_size;
			// Flush output so the browser receives bytes progressively on very large exports.
			if ( function_exists( 'ob_get_level' ) && ob_get_level() > 0 ) {
				@ob_flush();
			}
			flush();
		} while ( $fetched === $chunk_size );

		fclose( $output );
		exit;
	}

	/**
	 * Parses and sanitises filter params from the current request.
	 *
	 * @return array
	 */
	private static function get_filters_from_request(): array {
		// phpcs:disable WordPress.Security.NonceVerification
		return array(
			'page_url'  => ! empty( $_GET['filter_page_url'] ) ? sanitize_url( wp_unslash( $_GET['filter_page_url'] ) ) : '',
			'month'     => ! empty( $_GET['filter_month'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_month'] ) ) : '',
			'date_from' => ! empty( $_GET['filter_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) : '',
			'date_to'   => ! empty( $_GET['filter_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification
	}
}
