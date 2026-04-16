<?php
/**
 * Admin report page view — grouped by page with expandable section rows.
 *
 * Variables from Content_Vote_Admin::render_report_page():
 *   $filters            array   Active filters (page_url, month).
 *   $pages_with_titles  array   [{url, title}, …]
 *   $months             array   ['YYYY-MM', …]
 *   $page_summaries     array   Page-level aggregates (one row per page).
 *   $sections_map       array   Section rows keyed by page_url.
 *   $total_pages_db     int     Total distinct pages matching filters.
 *   $total_pages        int     Pagination page count.
 *   $paged              int     Current page.
 *   $per_page           int     Pages per result page.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_orderby = sanitize_text_field( $_GET['orderby'] ?? 'total_votes' ); // phpcs:ignore WordPress.Security.NonceVerification
$current_order   = 'ASC' === strtoupper( $_GET['order'] ?? '' ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification

// Helpers guarded with function_exists so re-including the view in the same
// request (e.g. unusual controller flows) does not trigger a fatal redeclaration.
if ( ! function_exists( 'cv_humanize_section_id' ) ) {
	/**
	 * Converts a slug-style section ID to a human-readable label.
	 */
	function cv_humanize_section_id( string $id ): string {
		return ucwords( str_replace( array( '-', '_' ), ' ', $id ) );
	}
}

if ( ! function_exists( 'cv_month_label' ) ) {
	/**
	 * Converts a YYYY-MM string to a localised "Month Year" label.
	 */
	function cv_month_label( string $ym ): string {
		$ts = strtotime( $ym . '-01' );
		return $ts ? wp_date( 'F Y', $ts ) : $ym;
	}
}

if ( ! function_exists( 'cv_sort_link' ) ) {
	/**
	 * Builds a sortable column header anchor. Resets pagination on sort change.
	 */
	function cv_sort_link( string $column, string $label, string $current_col, string $current_dir ): string {
		$new_order = ( $current_col === $column && 'DESC' === $current_dir ) ? 'ASC' : 'DESC';
		$indicator = '';
		if ( $current_col === $column ) {
			$indicator = 'ASC' === $current_dir ? ' &#8593;' : ' &#8595;';
		}
		$url = add_query_arg( array( 'orderby' => $column, 'order' => $new_order, 'paged' => 1 ) );
		return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . $indicator . '</a>';
	}
}

// Build URL → title lookup.
$title_map = array();
foreach ( $pages_with_titles as $p ) {
	$title_map[ $p['url'] ] = $p['title'];
}
?>
<div class="wrap cv-admin-report">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Content Evaluation Report', 'content-vote' ); ?></h1>
	<hr class="wp-header-end">

	<?php // ---- Filter form ---- ?>
	<form method="GET" class="cv-filters">
		<input type="hidden" name="page" value="content-evaluation">

		<div class="cv-filters__row">

			<label for="cv-filter-page"><?php esc_html_e( 'Page', 'content-vote' ); ?></label>
			<select id="cv-filter-page" name="filter_page_url">
				<option value=""><?php esc_html_e( '— All Pages —', 'content-vote' ); ?></option>
				<?php foreach ( $pages_with_titles as $page ) : ?>
					<option value="<?php echo esc_attr( $page['url'] ); ?>" <?php selected( $filters['page_url'], $page['url'] ); ?>>
						<?php echo esc_html( $page['title'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<label for="cv-filter-month"><?php esc_html_e( 'Month', 'content-vote' ); ?></label>
			<select id="cv-filter-month" name="filter_month">
				<option value=""><?php esc_html_e( '— All Months —', 'content-vote' ); ?></option>
				<?php foreach ( $months as $ym ) : ?>
					<option value="<?php echo esc_attr( $ym ); ?>" <?php selected( $filters['month'], $ym ); ?>>
						<?php echo esc_html( cv_month_label( $ym ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'content-vote' ); ?></button>

			<?php if ( array_filter( $filters ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=content-evaluation' ) ); ?>" class="button">
					<?php esc_html_e( 'Clear', 'content-vote' ); ?>
				</a>
			<?php endif; ?>

		</div>
	</form>

	<?php // ---- Summary bar ---- ?>
	<div class="cv-summary-bar">
		<div class="cv-summary-bar__left">
			<span>
				<?php
				printf(
					/* translators: %d: number of pages */
					esc_html( _n( '%d page', '%d pages', $total_pages_db, 'content-vote' ) ),
					(int) $total_pages_db
				);
				?>
			</span>
			<?php if ( $total_pages_db > 0 ) : ?>
				<button type="button" class="button cv-expand-all" data-action="expand">
					<?php esc_html_e( 'Expand All', 'content-vote' ); ?>
				</button>
				<button type="button" class="button cv-expand-all" data-action="collapse">
					<?php esc_html_e( 'Collapse All', 'content-vote' ); ?>
				</button>
			<?php endif; ?>
		</div>

		<?php if ( $total_pages_db > 0 ) : ?>
			<a href="<?php echo esc_url(
				wp_nonce_url(
					add_query_arg(
						array_filter(
							array(
								'action'            => 'content_vote_export_csv',
								'filter_page_url'   => $filters['page_url'],
								'filter_month'      => $filters['month'],
								'filter_date_from'  => $filters['date_from'] ?? '',
								'filter_date_to'    => $filters['date_to'] ?? '',
							)
						),
						admin_url( 'admin-post.php' )
					),
					'content_vote_export_csv'
				)
			); ?>" class="button button-secondary cv-export-btn">
				&#x21E9; <?php esc_html_e( 'Export CSV', 'content-vote' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php // ---- Results ---- ?>
	<?php if ( empty( $page_summaries ) ) : ?>
		<div class="cv-no-results">
			<p><?php esc_html_e( 'No votes found for the selected criteria.', 'content-vote' ); ?></p>
		</div>
	<?php else : ?>

		<table class="widefat cv-report-table">
			<thead>
				<tr>
					<th class="cv-col-page">
						<?php echo cv_sort_link( 'page_url', __( 'Page', 'content-vote' ), $current_orderby, $current_order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</th>
					<th class="cv-col-sections"><?php esc_html_e( 'Sections', 'content-vote' ); ?></th>
					<th class="cv-col-up">
						<?php echo cv_sort_link( 'total_up', __( 'Upvotes', 'content-vote' ), $current_orderby, $current_order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</th>
					<th class="cv-col-down">
						<?php echo cv_sort_link( 'total_down', __( 'Downvotes', 'content-vote' ), $current_orderby, $current_order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</th>
					<th class="cv-col-total">
						<?php echo cv_sort_link( 'total_votes', __( 'Total', 'content-vote' ), $current_orderby, $current_order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</th>
					<th class="cv-col-score"><?php esc_html_e( 'Score %', 'content-vote' ); ?></th>
					<th class="cv-col-bar"><?php esc_html_e( 'Distribution', 'content-vote' ); ?></th>
					<th class="cv-col-date">
						<?php echo cv_sort_link( 'last_vote', __( 'Last Vote', 'content-vote' ), $current_orderby, $current_order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $page_summaries as $idx => $page ) :
				$up            = (int) $page['total_up'];
				$down          = (int) $page['total_down'];
				$total         = (int) $page['total_votes'];
				$section_count = (int) $page['section_count'];
				$score_pct     = $total > 0 ? round( ( $up / $total ) * 100 ) : 0;
				$page_title    = $title_map[ $page['page_url'] ] ?? $page['page_url'];
				$group_id      = 'cv-group-' . $idx;
				$page_sections = $sections_map[ $page['page_url'] ] ?? array();
				?>

				<?php // ---- Page summary row (clickable) ---- ?>
				<tr class="cv-row--page<?php echo $section_count > 0 ? ' cv-row--expandable' : ''; ?>"
					data-group="<?php echo esc_attr( $group_id ); ?>"
					aria-expanded="false">
					<td class="cv-col-page">
						<div class="cv-page-cell">
							<?php if ( $section_count > 0 ) : ?>
								<button type="button" class="cv-toggle-btn" aria-controls="<?php echo esc_attr( $group_id ); ?>" aria-expanded="false">
									<span class="cv-toggle-icon">&#9658;</span>
								</button>
							<?php else : ?>
								<span class="cv-toggle-placeholder"></span>
							<?php endif; ?>
							<a href="<?php echo esc_url( $page['page_url'] ); ?>" target="_blank" rel="noopener noreferrer"
								title="<?php echo esc_attr( $page['page_url'] ); ?>"
								onclick="event.stopPropagation()">
								<?php echo esc_html( $page_title ); ?>
							</a>
						</div>
					</td>
					<td class="cv-col-sections">
						<?php echo (int) $section_count; ?>
					</td>
					<td class="cv-col-up cv-up-count"><?php echo (int) $up; ?></td>
					<td class="cv-col-down cv-down-count"><?php echo (int) $down; ?></td>
					<td class="cv-col-total"><?php echo (int) $total; ?></td>
					<td class="cv-col-score">
						<span class="cv-score <?php echo $score_pct >= 50 ? 'cv-score--positive' : 'cv-score--negative'; ?>">
							<?php echo (int) $score_pct; ?>%
						</span>
					</td>
					<td class="cv-col-bar">
						<div class="cv-bar" title="<?php echo (int) $score_pct; ?>% positive">
							<div class="cv-bar__fill cv-bar__fill--up" style="width:<?php echo (int) $score_pct; ?>%"></div>
						</div>
					</td>
					<td class="cv-col-date">
						<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $page['last_vote'] ) ) ); ?>
					</td>
				</tr>

				<?php // ---- Section detail rows (hidden until expanded) ---- ?>
				<?php foreach ( $page_sections as $section ) :
					$s_up    = (int) $section['total_up'];
					$s_down  = (int) $section['total_down'];
					$s_total = (int) $section['total_votes'];
					$s_score = $s_total > 0 ? round( ( $s_up / $s_total ) * 100 ) : 0;
					?>
					<tr class="cv-row--section" data-parent="<?php echo esc_attr( $group_id ); ?>" hidden>
						<td class="cv-col-page">
							<div class="cv-section-cell">
								<span class="cv-section-name"><?php echo esc_html( cv_humanize_section_id( $section['section_id'] ) ); ?></span>
								<span class="cv-section-raw"><?php echo esc_html( $section['section_id'] ); ?></span>
							</div>
						</td>
						<td class="cv-col-sections">—</td>
						<td class="cv-col-up cv-up-count"><?php echo (int) $s_up; ?></td>
						<td class="cv-col-down cv-down-count"><?php echo (int) $s_down; ?></td>
						<td class="cv-col-total"><?php echo (int) $s_total; ?></td>
						<td class="cv-col-score">
							<span class="cv-score <?php echo $s_score >= 50 ? 'cv-score--positive' : 'cv-score--negative'; ?>">
								<?php echo (int) $s_score; ?>%
							</span>
						</td>
						<td class="cv-col-bar">
							<div class="cv-bar" title="<?php echo (int) $s_score; ?>% positive">
								<div class="cv-bar__fill cv-bar__fill--up" style="width:<?php echo (int) $s_score; ?>%"></div>
							</div>
						</td>
						<td class="cv-col-date">
							<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $section['last_vote'] ) ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>

			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="cv-pagination tablenav">
				<div class="tablenav-pages">
					<?php
					echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'total'     => $total_pages,
							'current'   => $paged,
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>

	<?php endif; ?>

</div><!-- .cv-admin-report -->
