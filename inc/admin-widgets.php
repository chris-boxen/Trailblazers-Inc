<?php
/**
 * inc/admin-widgets.php
 * WordPress admin dashboard widgets for Trailblazers operational data.
 *
 * Widgets:
 *   tb_widget_season_summary   — enrollment totals + breakdown for active season
 *   tb_widget_payment_pipeline — enrollment payment_status counts
 *   tb_widget_physicals        — enrollment physical_status counts
 *   tb_widget_singlets         — enrollment singlet_status counts
 *   tb_widget_results          — per-meet result counts for active season;
 *                                sync notice + button for result_time_seconds
 *
 * All widgets are scoped to the active season via tb_active_season_id site option,
 * which is kept in sync by the acf/save_post hook in registration-helpers.php.
 *
 * Data is gathered once per page load via shared data functions that use a
 * static cache to avoid redundant queries across widgets.
 *
 * Enrollment field notes:
 *   - participation_type and new_returning (stored key for new_returning_athlete field)
 *     are top-level enrollment fields — queryable directly via get_post_meta().
 *   - payment_status, physical_status, enrollment_status are sub-fields of the
 *     'status' ACF group. ACF stores group sub-fields as {group_name}_{field_name},
 *     so the actual meta keys are status_payment_status, status_physical_status, etc.
 *   - singlet_status is a sub-field of the 'singlet' ACF group. The field name is
 *     singlet_status, so the stored key is singlet_singlet_status.
 *
 * Results field notes:
 *   - meet is a top-level ACF post object on athletic_result → tribe_events.
 *   - result_time_seconds sync POST is handled by admin_init in results-helpers.php.
 *     The widget reads tb_sync_done / tb_sync_updated / tb_sync_skipped query params
 *     set by that handler on redirect.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// =============================================================================
// WIDGET REGISTRATION
// =============================================================================

add_action( 'wp_dashboard_setup', function () {

	wp_add_dashboard_widget(
		'tb_widget_season_summary',
		'🏃 Season Summary',
		'tb_widget_season_summary_cb'
	);

	wp_add_dashboard_widget(
		'tb_widget_payment_pipeline',
		'💳 Payment Pipeline',
		'tb_widget_payment_pipeline_cb'
	);

	wp_add_dashboard_widget(
		'tb_widget_physicals',
		'📋 Physicals',
		'tb_widget_physicals_cb'
	);

	wp_add_dashboard_widget(
		'tb_widget_singlets',
		'👕 Singlets',
		'tb_widget_singlets_cb'
	);

	wp_add_dashboard_widget(
		'tb_widget_results',
		'📊 Results',
		'tb_widget_results_cb'
	);

} );


// =============================================================================
// ENROLLMENT DATA LAYER
// =============================================================================

/**
 * Gather enrollment data for the active season.
 *
 * Returns a structured array of counts and metadata, or null if no active
 * season is set. Results are statically cached so the query runs only once
 * per dashboard page load regardless of how many widgets are rendered.
 *
 * @return array|null {
 *   int    $season_id
 *   string $season_title
 *   string $season_url      Edit URL for the season post.
 *   int    $total           Total enrollment count.
 *   array  $participation   Counts keyed by participation_type value.
 *   array  $new_returning   Counts keyed by new_returning_athlete value.
 *   array  $payment         Counts keyed by payment_status value.
 *   array  $physical        Counts keyed by physical_status value.
 *   array  $singlet         Counts keyed by singlet_status value.
 *   string $list_url        Base URL for the enrollment admin list view.
 * }
 */
function tb_dashboard_get_enrollment_data(): ?array {

	static $cache = null;

	if ( $cache !== null ) {
		return $cache;
	}

	$season_id = (int) get_option( 'tb_active_season_id' );

	if ( ! $season_id ) {
		$cache = null;
		return null;
	}

	$season_post  = get_post( $season_id );
	$season_title = $season_post ? get_field( 'season_title', $season_id ) : 'Unknown Season';
	if ( ! $season_title ) {
		$season_title = $season_post ? $season_post->post_title : 'Unknown Season';
	}

	// Fetch all enrollment IDs for this season.
	// no_found_rows skips SQL_CALC_FOUND_ROWS — we don't need pagination data.
	$enrollment_ids = get_posts( [
		'post_type'              => 'enrollment',
		'numberposts'            => -1,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'meta_query'             => [ [
			'key'   => 'season',
			'value' => $season_id,
		] ],
	] );

	// Initialise count buckets.
	$data = [
		'season_id'    => $season_id,
		'season_title' => $season_title,
		'season_url'   => get_edit_post_link( $season_id ),
		'total'        => count( $enrollment_ids ),
		'participation' => [
			'Athlete'        => 0,
			'Sibling Runner' => 0,
		],
		'new_returning' => [
			'New Athlete'       => 0,
			'Returning Athlete' => 0,
		],
		'payment' => [
			'Paid'            => 0,
			'Not Received'    => 0,
			'Partially Paid'  => 0,
			'Waived'          => 0,
			'Refunded'        => 0,
		],
		'physical' => [
			'Received'              => 0,
			'Valid On File'         => 0,
			'Not Received'          => 0,
			'Rejected / Incomplete' => 0,
		],
		'singlet' => [
			'Ordered'        => 0,
			'Paid'           => 0,
			'Issued'         => 0,
			'Using Existing' => 0,
			'Not Needed'     => 0,
		],
		'list_url' => admin_url( 'edit.php?post_type=enrollment' ),
	];

	if ( empty( $enrollment_ids ) ) {
		$cache = $data;
		return $cache;
	}

	// Pull all relevant meta in a single pass.
	// ACF group sub-fields are stored as flat meta rows; get_post_meta() is
	// cheaper than get_field() inside a loop.
	foreach ( $enrollment_ids as $id ) {

		$participation   = get_post_meta( $id, 'participation_type',     true );
		$new_returning   = get_post_meta( $id, 'new_returning',          true ); // field name is 'new_returning', not 'new_returning_athlete'
		$payment_status  = get_post_meta( $id, 'status_payment_status',  true ); // ACF group sub-fields stored as {group_name}_{field_name}
		$physical_status = get_post_meta( $id, 'status_physical_status', true );
		$singlet_status  = get_post_meta( $id, 'singlet_singlet_status', true ); // singlet group + singlet_status field

		if ( isset( $data['participation'][ $participation ] ) ) {
			$data['participation'][ $participation ]++;
		}

		if ( isset( $data['new_returning'][ $new_returning ] ) ) {
			$data['new_returning'][ $new_returning ]++;
		}

		if ( $participation === 'Athlete' ) {
			if ( isset( $data['payment'][ $payment_status ] ) ) {
				$data['payment'][ $payment_status ]++;
			}

			if ( isset( $data['physical'][ $physical_status ] ) ) {
				$data['physical'][ $physical_status ]++;
			}

			if ( isset( $data['singlet'][ $singlet_status ] ) ) {
				$data['singlet'][ $singlet_status ]++;
			}
		}
	}

	$cache = $data;
	return $cache;
}


// =============================================================================
// RESULTS DATA LAYER
// =============================================================================

/**
 * Gather results data for the active season.
 *
 * Queries tribe_events meets linked to the active season, counts
 * athletic_result posts per meet, and counts posts needing sync.
 * Statically cached per page load.
 *
 * Note: needs_sync is a global count across all athletic_result posts,
 * not scoped to the active season. A freshly-imported batch is typically
 * the only source of unsynced results.
 *
 * @return array|null {
 *   int    $season_id
 *   string $season_title
 *   array  $meets          Ordered by _EventStartDate ASC. Each entry:
 *                            int    meet_id
 *                            string meet_title
 *                            string meet_date     Formatted "M j" (e.g. "Aug 9")
 *                            int    result_count
 *   int    $total_results  Sum of result_count across all meets.
 *   int    $needs_sync     Posts with empty result_time_seconds (all seasons).
 *   string $results_url    Admin list URL for athletic_result.
 * }
 */
function tb_dashboard_get_results_data(): ?array {

	static $cache = null;

	if ( $cache !== null ) {
		return $cache;
	}

	$season_id = (int) get_option( 'tb_active_season_id' );

	if ( ! $season_id ) {
		$cache = null;
		return null;
	}

	$season_post  = get_post( $season_id );
	$season_title = $season_post ? get_field( 'season_title', $season_id ) : 'Unknown Season';
	if ( ! $season_title ) {
		$season_title = $season_post ? $season_post->post_title : 'Unknown Season';
	}

	// Meets for this season, ordered by event start date.
	// _EventStartDate is a TEC postmeta field (Y-m-d H:i:s), not ACF.
	$meet_ids = get_posts( [
		'post_type'      => 'tribe_events',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'orderby'        => 'meta_value',
		'meta_key'       => '_EventStartDate',
		'order'          => 'ASC',
		'tax_query'      => [ [
			'taxonomy' => 'tribe_events_cat',
			'field'    => 'slug',
			'terms'    => 'athletic-meet',
		] ],
		'meta_query'     => [ [
			'key'   => 'season',
			'value' => $season_id,
		] ],
	] );


	$meets         = [];
	$total_results = 0;

	foreach ( $meet_ids as $meet_id ) {
		$raw_date     = get_post_meta( $meet_id, '_EventStartDate', true );
		$date_display = $raw_date ? date_i18n( 'M j', strtotime( $raw_date ) ) : '—';

		$result_count = count( get_posts( [
			'post_type'      => 'athletic_result',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [ [
				'key'   => 'meet',
				'value' => $meet_id,
			] ],
		] ) );

		$total_results += $result_count;

		$meets[] = [
			'meet_id'      => $meet_id,
			'meet_title'   => get_the_title( $meet_id ),
			'meet_date'    => $date_display,
			'result_count' => $result_count,
		];
	}

	// Count results missing result_time_seconds (global, not season-scoped).
	$needs_sync = count( get_posts( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [ [
			'key'     => 'result_time_seconds',
			'compare' => 'NOT EXISTS',
		] ],
	] ) );

	$needs_sync += count( get_posts( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [ [
			'key'     => 'result_time_seconds',
			'value'   => '',
			'compare' => '=',
		] ],
	] ) );

	$cache = [
		'season_id'     => $season_id,
		'season_title'  => $season_title,
		'meets'         => $meets,
		'total_results' => $total_results,
		'needs_sync'    => $needs_sync,
		'results_url'   => admin_url( 'edit.php?post_type=athletic_result' ),
	];

	return $cache;
}


// =============================================================================
// SHARED HELPERS
// =============================================================================

/**
 * Render the "no active season" notice used by all widgets.
 */
function tb_dashboard_no_season_notice(): void {
	$settings_url = admin_url( 'admin.php?page=registration-settings' );
	echo '<p style="color:#888;">No active season set. '
		. '<a href="' . esc_url( $settings_url ) . '">Set one in Registration Settings →</a>'
		. '</p>';
}

/**
 * Render a simple two-column count table.
 *
 * @param array  $rows     Associative array of label => count.
 * @param string $list_url Admin list URL (optional — adds a "View all" link).
 */
function tb_dashboard_count_table( array $rows, string $list_url = '' ): void {

	$total = array_sum( $rows );

	echo '<table style="width:100%;border-collapse:collapse;">';

	foreach ( $rows as $label => $count ) {
		$pct   = $total > 0 ? round( ( $count / $total ) * 100 ) : 0;
		$style = $count === 0 ? 'color:#bbb;' : '';
		echo '<tr>';
		echo '<td style="padding:3px 0;' . $style . '">' . esc_html( $label ) . '</td>';
		echo '<td style="text-align:right;font-weight:600;' . $style . '">' . esc_html( $count ) . '</td>';
		echo '<td style="text-align:right;color:#aaa;padding-left:8px;font-size:11px;">'
			. ( $count > 0 ? esc_html( $pct ) . '%' : '' )
			. '</td>';
		echo '</tr>';
	}

	echo '</table>';

	if ( $list_url ) {
		echo '<p style="margin-top:8px;">'
			. '<a href="' . esc_url( $list_url ) . '">View all enrollments →</a>'
			. '</p>';
	}
}


// =============================================================================
// WIDGET 1 — SEASON SUMMARY
// =============================================================================

function tb_widget_season_summary_cb(): void {

	$d = tb_dashboard_get_enrollment_data();

	if ( ! $d ) {
		tb_dashboard_no_season_notice();
		return;
	}

	echo '<p style="margin-bottom:8px;">'
		. '<strong>Season:</strong> '
		. '<a href="' . esc_url( $d['season_url'] ) . '">'
		. esc_html( $d['season_title'] )
		. '</a>'
		. '</p>';

	echo '<p style="font-size:24px;font-weight:700;margin:0 0 4px;">'
		. esc_html( $d['total'] )
		. '</p>';
	echo '<p style="color:#888;margin:0 0 12px;">Total Enrollments</p>';

	echo '<table style="width:100%;border-collapse:collapse;">';

	$breakdown = [
		'Athletes'           => $d['participation']['Athlete'],
		'Sibling Runners'    => $d['participation']['Sibling Runner'],
		'—'                  => '',  // visual spacer
		'New Athletes'       => $d['new_returning']['New Athlete'],
		'Returning Athletes' => $d['new_returning']['Returning Athlete'],
	];

	foreach ( $breakdown as $label => $count ) {
		if ( $count === '' ) {
			echo '<tr><td colspan="2" style="padding:4px 0;border-top:1px solid #eee;"></td></tr>';
			continue;
		}
		$style = $count === 0 ? 'color:#bbb;' : '';
		echo '<tr>';
		echo '<td style="padding:3px 0;' . $style . '">' . esc_html( $label ) . '</td>';
		echo '<td style="text-align:right;font-weight:600;' . $style . '">' . esc_html( $count ) . '</td>';
		echo '</tr>';
	}

	echo '</table>';

	echo '<p style="margin-top:10px;">'
		. '<a href="' . esc_url( $d['list_url'] ) . '">View all enrollments →</a>'
		. '</p>';
}


// =============================================================================
// WIDGET 2 — PAYMENT PIPELINE
// =============================================================================

function tb_widget_payment_pipeline_cb(): void {

	$d = tb_dashboard_get_enrollment_data();

	if ( ! $d ) {
		tb_dashboard_no_season_notice();
		return;
	}

	$unpaid = $d['payment']['Not Received'] + $d['payment']['Partially Paid'];

	if ( $unpaid > 0 ) {
		echo '<p style="background:#fff3cd;border-left:3px solid #ffc107;padding:6px 10px;margin-bottom:10px;font-size:12px;">'
			. '<strong>' . esc_html( $unpaid ) . '</strong> enrollment'
			. ( $unpaid !== 1 ? 's' : '' )
			. ' with outstanding payment.'
			. '</p>';
	}

	tb_dashboard_count_table( $d['payment'], $d['list_url'] );
}


// =============================================================================
// WIDGET 3 — PHYSICALS
// =============================================================================

function tb_widget_physicals_cb(): void {

	$d = tb_dashboard_get_enrollment_data();

	if ( ! $d ) {
		tb_dashboard_no_season_notice();
		return;
	}

	$not_received = $d['physical']['Not Received'] + $d['physical']['Rejected / Incomplete'];

	if ( $not_received > 0 ) {
		echo '<p style="background:#fff3cd;border-left:3px solid #ffc107;padding:6px 10px;margin-bottom:10px;font-size:12px;">'
			. '<strong>' . esc_html( $not_received ) . '</strong> physical'
			. ( $not_received !== 1 ? 's' : '' )
			. ' not yet received.'
			. '</p>';
	}

	tb_dashboard_count_table( $d['physical'], $d['list_url'] );
}


// =============================================================================
// WIDGET 4 — SINGLETS
// =============================================================================

function tb_widget_singlets_cb(): void {

	$d = tb_dashboard_get_enrollment_data();

	if ( ! $d ) {
		tb_dashboard_no_season_notice();
		return;
	}

	$ordered = $d['singlet']['Ordered'];

	if ( $ordered > 0 ) {
		echo '<p style="background:#d4edda;border-left:3px solid #28a745;padding:6px 10px;margin-bottom:10px;font-size:12px;">'
			. '<strong>' . esc_html( $ordered ) . '</strong> singlet'
			. ( $ordered !== 1 ? 's' : '' )
			. ' ordered, not yet issued.'
			. '</p>';
	}

	tb_dashboard_count_table( $d['singlet'], $d['list_url'] );
}


// =============================================================================
// WIDGET 5 — RESULTS
// =============================================================================

function tb_widget_results_cb(): void {

	$d = tb_dashboard_get_results_data();

	if ( ! $d ) {
		tb_dashboard_no_season_notice();
		return;
	}

	// Show sync result notice when redirected back after a completed sync.
	if ( isset( $_GET['tb_sync_done'] ) ) {
		$updated = (int) ( $_GET['tb_sync_updated'] ?? 0 );
		$skipped = (int) ( $_GET['tb_sync_skipped'] ?? 0 );
		echo '<p style="background:#d4edda;border-left:3px solid #28a745;padding:6px 10px;margin-bottom:10px;font-size:12px;">'
			. '<strong>Sync complete.</strong> '
			. esc_html( $updated ) . ' result' . ( $updated !== 1 ? 's' : '' ) . ' updated.'
			. ( $skipped > 0 ? ' ' . esc_html( $skipped ) . ' skipped.' : '' )
			. '</p>';
	}

	// Per-meet results table.
	if ( empty( $d['meets'] ) ) {
		echo '<p style="color:#888;">No meets found for this season.</p>';
	} else {
		echo '<table style="width:100%;border-collapse:collapse;">';
		echo '<tr style="font-size:11px;color:#aaa;border-bottom:1px solid #eee;">'
			. '<td style="padding:2px 0 4px;">Meet</td>'
			. '<td style="text-align:right;padding:2px 4px 4px;">Date</td>'
			. '<td style="text-align:right;padding:2px 0 4px;">Results</td>'
			. '</tr>';

		foreach ( $d['meets'] as $meet ) {
			$style = $meet['result_count'] === 0 ? 'color:#bbb;' : '';
			echo '<tr>';
			echo '<td style="padding:3px 0;' . $style . '">'
				. '<a href="' . esc_url( get_edit_post_link( $meet['meet_id'] ) ) . '" style="' . $style . 'text-decoration:none;">'
				. esc_html( $meet['meet_title'] )
				. '</a>'
				. '</td>';
			echo '<td style="text-align:right;padding:3px 4px;color:#aaa;font-size:11px;">'
				. esc_html( $meet['meet_date'] )
				. '</td>';
			echo '<td style="text-align:right;font-weight:600;' . $style . '">'
				. esc_html( $meet['result_count'] )
				. '</td>';
			echo '</tr>';
		}

		echo '</table>';

		echo '<p style="margin-top:8px;">'
			. '<a href="' . esc_url( $d['results_url'] ) . '">View all results →</a>'
			. '</p>';
	}

	// Sync status.
	echo '<hr style="margin:10px 0 8px;">';

	if ( $d['needs_sync'] > 0 ) {
		echo '<p style="background:#fff3cd;border-left:3px solid #ffc107;padding:6px 10px;margin-bottom:8px;font-size:12px;">'
			. '<strong>' . esc_html( $d['needs_sync'] ) . '</strong> result'
			. ( $d['needs_sync'] !== 1 ? 's' : '' )
			. ' missing <code>result_time_seconds</code>.'
			. '</p>';

		echo '<form method="post">';
		wp_nonce_field( 'tb_sync_result_times_action', 'tb_sync_result_times_nonce' );
		echo '<button type="submit" name="tb_sync_result_times" class="button button-small button-primary">'
			. 'Sync Result Times'
			. '</button>';
		echo '</form>';
	} else {
		echo '<p style="color:#46b450;font-size:12px;margin:0;">&#10003; result_time_seconds in sync.</p>';
	}
}