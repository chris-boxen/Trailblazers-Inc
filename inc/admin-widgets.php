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
 *
 * All widgets are scoped to the active season via tb_active_season_id site option,
 * which is kept in sync by the acf/save_post hook in registration-helpers.php.
 *
 * Data is gathered once per page load via tb_dashboard_get_enrollment_data(),
 * which uses a static cache to avoid redundant queries across widgets.
 *
 * Field notes:
 *   - payment_status, physical_status, enrollment_status are sub-fields of the
 *     'status' ACF group (field_69c9e3f08452e). ACF stores group sub-fields as
 *     flat post meta rows, so get_post_meta() works directly.
 *   - singlet_status is a sub-field of the 'singlet' ACF group (field_69c9de8888649).
 *     Same storage pattern — queryable via get_post_meta().
 *   - participation_type and new_returning_athlete are top-level enrollment fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// =============================================================================
// REGISTRATION
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

} );


// =============================================================================
// DATA LAYER — gathered once, shared across all widget callbacks
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
		'post_type'           => 'enrollment',
		'numberposts'         => -1,
		'fields'              => 'ids',
		'no_found_rows'       => true,
		'update_post_term_cache' => false,
		'meta_query'          => [ [
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
			'Received'     => 0,
			'Not Received' => 0,
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

		$participation   = get_post_meta( $id, 'participation_type',   true );
		$new_returning   = get_post_meta( $id, 'new_returning_athlete', true );
		$payment_status  = get_field( 'payment_status',  $id );
		$physical_status = get_field( 'physical_status', $id );
		$singlet_status  = get_field( 'singlet_status',  $id );

		if ( isset( $data['participation'][ $participation ] ) ) {
			$data['participation'][ $participation ]++;
		}

		if ( isset( $data['new_returning'][ $new_returning ] ) ) {
			$data['new_returning'][ $new_returning ]++;
		}

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

	$cache = $data;
	return $cache;
}


// =============================================================================
// SHARED HELPERS
// =============================================================================

/**
 * Render the "no active season" notice used by all four widgets.
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
 * @param string $list_url Base URL for the enrollment admin list (optional link on total row).
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
		'Athletes'          => $d['participation']['Athlete'],
		'Sibling Runners'   => $d['participation']['Sibling Runner'],
		'—'                 => '',  // visual spacer
		'New Athletes'      => $d['new_returning']['New Athlete'],
		'Returning Athletes'=> $d['new_returning']['Returning Athlete'],
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

	$not_received = $d['physical']['Not Received'];

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