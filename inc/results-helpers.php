<?php
/**
 * inc/results-helpers.php
 * Athletic result utilities: derived field sync and admin tools.
 *
 * Functions:
 *   tb_parse_result_time_seconds( $display )
 *       Parses MM:SS.ss or H:MM:SS.ss → float seconds. Returns 0 if
 *       unparseable.
 *
 *   tb_run_result_times_sync()
 *       Finds all athletic_result posts with empty result_time_seconds,
 *       calculates from result_display, writes via update_field().
 *       Returns [ 'updated' => int, 'skipped' => int ].
 *       Called by the admin_init handler; available to other callers.
 *
 * Hooks:
 *   acf/save_post (priority 20)
 *       Derives result_time_seconds on WP admin saves of athletic_result.
 *       Does not fire during WPUCI imports — use the sync tool after import.
 *
 *   admin_init
 *       Catches POST from the sync button (Tools page or dashboard widget),
 *       runs tb_run_result_times_sync(), redirects back to the referer with
 *       result params: tb_sync_done, tb_sync_updated, tb_sync_skipped.
 *
 * Admin pages:
 *   Tools → Sync Result Times
 *       Standalone sync page. The 📊 Results dashboard widget also provides
 *       a sync button and reads the same redirect params for its notice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// ---------------------------------------------------------------------------
// 1. Shared helper: parse result_display → seconds
// ---------------------------------------------------------------------------

/**
 * Parse a result_display string into total seconds as a float.
 *
 * Supports MM:SS.ss (e.g. "19:55.00") and H:MM:SS.ss (e.g. "1:02:30.5").
 * Returns 0 if the string is empty or cannot be parsed.
 *
 * @param string $display
 * @return float
 */
function tb_parse_result_time_seconds( string $display ): float {
	$parts = explode( ':', trim( $display ) );

	if ( count( $parts ) === 2 ) {
		// MM:SS.ss
		return ( (int) $parts[0] * 60 ) + (float) $parts[1];
	}

	if ( count( $parts ) === 3 ) {
		// H:MM:SS.ss
		return ( (int) $parts[0] * 3600 ) + ( (int) $parts[1] * 60 ) + (float) $parts[2];
	}

	return 0;
}


// ---------------------------------------------------------------------------
// 2. Sync executor
// ---------------------------------------------------------------------------

/**
 * Find all athletic_result posts with empty result_time_seconds, derive the
 * value from result_display, and write it via update_field().
 *
 * Uses get_post_meta() to read result_display directly, bypassing ACF shadow
 * key resolution — safe for a plain text field and necessary because shadow
 * keys may be absent on freshly-imported posts.
 *
 * @return array { int $updated, int $skipped }
 */
function tb_run_result_times_sync(): array {
	$updated = 0;
	$skipped = 0;

	$missing = get_posts( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [ [
			'key'     => 'result_time_seconds',
			'compare' => 'NOT EXISTS',
		] ],
	] );

	$empty = get_posts( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [ [
			'key'     => 'result_time_seconds',
			'value'   => '',
			'compare' => '=',
		] ],
	] );

	$ids = array_unique( array_merge( $missing, $empty ) );

	foreach ( $ids as $post_id ) {
		$display = get_post_meta( $post_id, 'result_display', true );
		if ( ! $display ) {
			$skipped++;
			continue;
		}
		$seconds = tb_parse_result_time_seconds( $display );
		if ( $seconds > 0 ) {
			update_field( 'result_time_seconds', round( $seconds, 2 ), $post_id );
			$updated++;
		} else {
			$skipped++;
		}
	}

	return [ 'updated' => $updated, 'skipped' => $skipped ];
}


// ---------------------------------------------------------------------------
// 3. ACF save hook: sync on WP admin saves
// ---------------------------------------------------------------------------

add_action( 'acf/save_post', 'tb_sync_result_time_seconds', 20 );

/**
 * On ACF save of an athletic_result, write result_time_seconds from
 * result_display. Fires only through the WP admin save pipeline.
 *
 * @param int|string $post_id
 */
function tb_sync_result_time_seconds( $post_id ): void {
	if ( get_post_type( $post_id ) !== 'athletic_result' ) return;

	$display = get_field( 'result_display', $post_id );
	if ( ! $display ) return;

	$seconds = tb_parse_result_time_seconds( $display );
	if ( $seconds > 0 ) {
		update_field( 'result_time_seconds', round( $seconds, 2 ), $post_id );
	}
}


// ---------------------------------------------------------------------------
// 4. admin_init POST handler
// ---------------------------------------------------------------------------

add_action( 'admin_init', function() {
	if ( ! isset( $_POST['tb_sync_result_times'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	check_admin_referer( 'tb_sync_result_times_action', 'tb_sync_result_times_nonce' );

	$result = tb_run_result_times_sync();

	$redirect = wp_get_referer() ?: admin_url( 'index.php' );
	$redirect = add_query_arg( [
		'tb_sync_done'    => 1,
		'tb_sync_updated' => $result['updated'],
		'tb_sync_skipped' => $result['skipped'],
	], $redirect );

	wp_safe_redirect( $redirect );
	exit;
} );


// ---------------------------------------------------------------------------
// 5. Tools page: Sync Result Times
// ---------------------------------------------------------------------------

add_action( 'admin_menu', function() {
	add_management_page(
		'Sync Result Times',
		'Sync Result Times',
		'manage_options',
		'tb-sync-result-times',
		'tb_sync_result_times_page'
	);
} );

/**
 * Render the Tools → Sync Result Times page.
 * POST is handled by the admin_init hook above; this page only displays state
 * and the button, and reads redirect params for the success notice.
 */
function tb_sync_result_times_page(): void {

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

	$did_sync = isset( $_GET['tb_sync_done'] );
	$updated  = $did_sync ? (int) ( $_GET['tb_sync_updated'] ?? 0 ) : 0;
	$skipped  = $did_sync ? (int) ( $_GET['tb_sync_skipped'] ?? 0 ) : 0;

	?>
	<div class="wrap">
		<h1>Sync Result Times</h1>
		<p>
			Calculates <code>result_time_seconds</code> from <code>result_display</code>
			for all Athletic Result posts where it is missing. Run this after every
			CSV import via WPUCI.
		</p>

		<?php if ( $did_sync ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong>Sync complete.</strong>
					<?php echo esc_html( $updated ); ?> result<?php echo $updated !== 1 ? 's' : ''; ?> updated.
					<?php if ( $skipped > 0 ) : ?>
						<?php echo esc_html( $skipped ); ?> skipped (no result_display value).
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th>Results needing sync</th>
				<td>
					<strong><?php echo esc_html( $needs_sync ); ?></strong>
					<?php if ( $needs_sync === 0 ) : ?>
						<span style="color:#46b450;">&#10003; All results are in sync.</span>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php if ( $needs_sync > 0 ) : ?>
			<form method="post">
				<?php wp_nonce_field( 'tb_sync_result_times_action', 'tb_sync_result_times_nonce' ); ?>
				<p>
					<button type="submit" name="tb_sync_result_times" class="button button-primary">
						Sync Result Times
					</button>
				</p>
			</form>
		<?php endif; ?>
	</div>
	<?php
}


// ---------------------------------------------------------------------------
// 6. Auto-generate PR / SR records on result save (WP admin only)
// ---------------------------------------------------------------------------
//
// Fires at priority 25 — after tb_sync_result_time_seconds (priority 20),
// so result_time_seconds is already written before this check runs.
//
// NOT triggered by WPUCI imports. For post-import record generation, use
// the Tools → Generate Records page (section 7 below).
//
// Comparison direction by measurement_type:
//   Time     → lower is better (result_time_seconds)
//   Distance → higher is better (result_distance_meters)
//   Height   → higher is better (result_height_meters)
//   Points   → higher is better (result_points)
//
// Idempotent: if a record post already exists pointing to this exact result
// (for a given type), a duplicate is not created. Safe on re-save.
//
// Field name note:
//   On athletic_result: Athletic Event link is named 'athletic_event'
//   On athletic_record: Athletic Event link is named 'event'
//   These differ — use the correct name per CPT.
// ---------------------------------------------------------------------------

add_action( 'acf/save_post', 'tb_auto_generate_records', 25 );

/**
 * After an athletic_result is saved via WP admin, check whether it sets a
 * new PR (all-time) or SR (season-scoped) for the athlete in that event.
 * Creates an athletic_record post for each record type earned.
 *
 * @param int|string $post_id
 */
function tb_auto_generate_records( $post_id ): void {

	if ( get_post_type( $post_id ) !== 'athletic_result' ) return;

	// -------------------------------------------------------------------------
	// Read this result's key fields.
	// -------------------------------------------------------------------------
	$athlete_id = get_field( 'athlete',        $post_id ); // int
	$event_id   = get_field( 'athletic_event', $post_id ); // int — 'athletic_event' on result
	$meet_id    = get_field( 'meet',           $post_id ); // int

	if ( ! $athlete_id || ! $event_id || ! $meet_id ) return;

	// -------------------------------------------------------------------------
	// Read measurement type from the Athletic Event.
	// Skip relay events — individual legs don't generate personal records.
	// -------------------------------------------------------------------------
	$measurement = get_field( 'measurement_type', $event_id ); // Time | Distance | Height | Points
	$is_relay    = get_field( 'is_relay',         $event_id );

	if ( $is_relay )    return;
	if ( ! $measurement ) return;

	// -------------------------------------------------------------------------
	// Get the numeric value for this result and set comparison direction.
	// -------------------------------------------------------------------------
	$value           = null;
	$lower_is_better = false;

	switch ( $measurement ) {
		case 'Time':
			$value           = (float) get_field( 'result_time_seconds',    $post_id );
			$lower_is_better = true;
			break;
		case 'Distance':
			$value = (float) get_field( 'result_distance_meters', $post_id );
			break;
		case 'Height':
			$value = (float) get_field( 'result_height_meters',   $post_id );
			break;
		case 'Points':
			$value = (float) get_field( 'result_points',          $post_id );
			break;
	}

	if ( ! $value || $value <= 0 ) return;

	// -------------------------------------------------------------------------
	// Get season ID (needed for SR scope) via meet → season.
	// -------------------------------------------------------------------------
	$season_id = get_field( 'season', $meet_id );
	if ( ! $season_id ) return;

	// -------------------------------------------------------------------------
	// Check which record types already exist pointing to this exact result.
	// Prevents duplicates when a result post is re-saved.
	// -------------------------------------------------------------------------
	$existing_for_this_result = get_posts( [
		'post_type'      => 'athletic_record',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [
			'relation' => 'AND',
			[ 'key' => 'athlete', 'value' => $athlete_id, 'compare' => '=' ],
			[ 'key' => 'result',  'value' => $post_id,    'compare' => '=' ],
		],
	] );

	$already_created = [];
	foreach ( $existing_for_this_result as $rec_id ) {
		$already_created[] = get_field( 'record_type', $rec_id );
	}

	// -------------------------------------------------------------------------
	// Helper: find the best value across a set of result post IDs.
	// Returns null if no valid values exist (athlete's first result).
	// -------------------------------------------------------------------------
	$get_best = function( array $result_ids ) use ( $measurement, $lower_is_better ): ?float {
		$best = null;
		foreach ( $result_ids as $rid ) {
			switch ( $measurement ) {
				case 'Time':     $v = (float) get_post_meta( $rid, 'result_time_seconds',    true ); break;
				case 'Distance': $v = (float) get_post_meta( $rid, 'result_distance_meters', true ); break;
				case 'Height':   $v = (float) get_post_meta( $rid, 'result_height_meters',   true ); break;
				case 'Points':   $v = (float) get_post_meta( $rid, 'result_points',          true ); break;
				default:         $v = 0;
			}
			if ( ! $v || $v <= 0 ) continue;
			if ( $best === null
				|| ( $lower_is_better  && $v < $best )
				|| ( ! $lower_is_better && $v > $best )
			) {
				$best = $v;
			}
		}
		return $best;
	};

	// -------------------------------------------------------------------------
	// PR CHECK — compare against all prior results for this athlete + event.
	// First result ever (no priors) is an automatic PR.
	// -------------------------------------------------------------------------
	if ( ! in_array( 'PR', $already_created, true ) ) {

		$prior_results = get_posts( [
			'post_type'      => 'athletic_result',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'exclude'        => [ $post_id ],
			'meta_query'     => [
				'relation' => 'AND',
				[ 'key' => 'athlete',        'value' => $athlete_id, 'compare' => '=' ],
				[ 'key' => 'athletic_event', 'value' => $event_id,   'compare' => '=' ],
			],
		] );

		$prior_best = $get_best( $prior_results );

		$is_pr = $prior_best === null                          // first result ever
			|| ( $lower_is_better  && $value < $prior_best )
			|| ( ! $lower_is_better && $value > $prior_best );

		if ( $is_pr ) {
			tb_create_record_post( $athlete_id, $event_id, $post_id, 'PR' );
		}
	}

	// -------------------------------------------------------------------------
	// SR CHECK — compare against results for this athlete + event scoped to
	// meets within this season. First result of the season is an automatic SR.
	// -------------------------------------------------------------------------
	if ( ! in_array( 'SR', $already_created, true ) ) {

		$season_meet_ids = get_posts( [
			'post_type'      => 'tribe_events',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => [ [
				'taxonomy' => 'tribe_events_cat',
				'field'    => 'slug',
				'terms'    => 'athletic-meet',
			] ],
			'meta_query'     => [ [
				'key'     => 'season',
				'value'   => $season_id,
				'compare' => '=',
			] ],
		] );

		if ( ! empty( $season_meet_ids ) ) {

			$prior_season_results = get_posts( [
				'post_type'      => 'athletic_result',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'exclude'        => [ $post_id ],
				'meta_query'     => [
					'relation' => 'AND',
					[ 'key' => 'athlete',        'value' => $athlete_id,     'compare' => '=' ],
					[ 'key' => 'athletic_event', 'value' => $event_id,       'compare' => '=' ],
					[ 'key' => 'meet',           'value' => $season_meet_ids, 'compare' => 'IN' ],
				],
			] );

			$season_best = $get_best( $prior_season_results );

			$is_sr = $season_best === null                          // first result this season
				|| ( $lower_is_better  && $value < $season_best )
				|| ( ! $lower_is_better && $value > $season_best );

			if ( $is_sr ) {
				tb_create_record_post( $athlete_id, $event_id, $post_id, 'SR' );
			}
		}
	}
}


/**
 * Create a single athletic_record post.
 *
 * Post title format: "Jack Anderson – 5K PR"
 * Note: the Athletic Event link on athletic_record is named 'event',
 * not 'athletic_event' — different from the field name on athletic_result.
 *
 * @param int    $athlete_id  Athlete post ID.
 * @param int    $event_id    Athletic Event post ID.
 * @param int    $result_id   Athletic Result post ID.
 * @param string $record_type 'PR' or 'SR'.
 * @return int|WP_Error New record post ID, or WP_Error on failure.
 */
function tb_create_record_post( int $athlete_id, int $event_id, int $result_id, string $record_type ) {

	$event_name   = get_field( 'event_name', $event_id ) ?: get_the_title( $event_id );
	$names        = get_field( 'names', $athlete_id ) ?: [];
	$first        = $names['first_name']     ?? '';
	$preferred    = $names['preferred_name'] ?? '';
	$last         = $names['last_name']      ?? '';
	$display_name = trim( ( $preferred ?: $first ) . ' ' . $last );

	$post_id = wp_insert_post( [
		'post_type'   => 'athletic_record',
		'post_status' => 'publish',
		'post_title'  => $display_name . ' – ' . $event_name . ' ' . $record_type,
	] );

	if ( is_wp_error( $post_id ) ) return $post_id;

	// 'event' on athletic_record (not 'athletic_event' — field names differ per CPT).
	update_field( 'athlete',     $athlete_id,  $post_id );
	update_field( 'event',       $event_id,    $post_id );
	update_field( 'result',      $result_id,   $post_id );
	update_field( 'record_type', $record_type, $post_id );

	return $post_id;
}


// ---------------------------------------------------------------------------
// 7. Tools page: Generate Records
// ---------------------------------------------------------------------------
//
// Bulk-generates PR / SR records for all results in a selected season.
// Use this after every WPUCI results import, since acf/save_post does not
// fire during imports.
//
// The tool is idempotent — it skips any result that already has a record
// post of the given type pointing to it. Safe to re-run.
//
// Process:
//   For each athlete + event combination in the season:
//     1. Sort results chronologically (by meet date, ascending).
//     2. Walk through results in order, tracking the running best.
//     3. Each time a new best is found, create the appropriate record post.
//
// Sorting chronologically is important: it preserves the historical sequence
// so that the PR badge appears on each result where the record was set, not
// just the final best.
// ---------------------------------------------------------------------------

add_action( 'admin_menu', function() {
	add_management_page(
		'Generate Records',
		'Generate Records',
		'manage_options',
		'tb-generate-records',
		'tb_generate_records_page'
	);
} );

add_action( 'admin_init', 'tb_handle_generate_records_post' );

/**
 * Handle the Generate Records form POST.
 */
function tb_handle_generate_records_post(): void {

	if ( ! isset( $_POST['tb_generate_records'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	check_admin_referer( 'tb_generate_records_action', 'tb_generate_records_nonce' );

	$season_id = (int) ( $_POST['tb_season_id'] ?? 0 );
	if ( ! $season_id ) {
		wp_safe_redirect( add_query_arg( 'tb_gen_error', '1', wp_get_referer() ) );
		exit;
	}

	$result = tb_run_generate_records( $season_id );

	$redirect = add_query_arg( [
		'tb_gen_done'    => 1,
		'tb_gen_created' => $result['created'],
		'tb_gen_skipped' => $result['skipped'],
	], wp_get_referer() ?: admin_url( 'tools.php?page=tb-generate-records' ) );

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Run the bulk record generation for all results in a season.
 *
 * @param  int   $season_id  Athletic Season post ID.
 * @return array { int $created, int $skipped }
 */
function tb_run_generate_records( int $season_id ): array {

	$created = 0;
	$skipped = 0;

	// Get all meet IDs in this season.
	$season_meet_ids = get_posts( [
		'post_type'      => 'tribe_events',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'tax_query'      => [ [
			'taxonomy' => 'tribe_events_cat',
			'field'    => 'slug',
			'terms'    => 'athletic-meet',
		] ],
		'meta_query'     => [ [
			'key'     => 'season',
			'value'   => $season_id,
			'compare' => '=',
		] ],
	] );

	if ( empty( $season_meet_ids ) ) return [ 'created' => 0, 'skipped' => 0 ];

	// Build meet_id → meet_date map for chronological sorting.
	$meet_dates = [];
	foreach ( $season_meet_ids as $mid ) {
		$raw              = get_post_meta( $mid, '_EventStartDate', true );
		$meet_dates[$mid] = $raw ? date( 'Y-m-d', strtotime( $raw ) ) : '';
	}

	// Get all results for this season.
	$all_results = get_posts( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [ [
			'key'     => 'meet',
			'value'   => $season_meet_ids,
			'compare' => 'IN',
		] ],
	] );

	if ( empty( $all_results ) ) return [ 'created' => 0, 'skipped' => 0 ];

	// Group results by athlete + event.
	$grouped = []; // [ athlete_id ][ event_id ] => [ [ result_id, meet_date, value ], ... ]

	foreach ( $all_results as $rid ) {

		$athlete_id = (int) get_post_meta( $rid, 'athlete', true );
		$event_id   = (int) get_post_meta( $rid, 'athletic_event', true );
		$mid        = (int) get_post_meta( $rid, 'meet', true );

		if ( ! $athlete_id || ! $event_id || ! $mid ) continue;

		$measurement     = get_field( 'measurement_type', $event_id );
		$is_relay        = get_field( 'is_relay',         $event_id );
		$lower_is_better = ( $measurement === 'Time' );

		if ( $is_relay || ! $measurement ) continue;

		$value = 0;
		switch ( $measurement ) {
			case 'Time':     $value = (float) get_post_meta( $rid, 'result_time_seconds',    true ); break;
			case 'Distance': $value = (float) get_post_meta( $rid, 'result_distance_meters', true ); break;
			case 'Height':   $value = (float) get_post_meta( $rid, 'result_height_meters',   true ); break;
			case 'Points':   $value = (float) get_post_meta( $rid, 'result_points',          true ); break;
		}

		if ( ! $value || $value <= 0 ) {
			$skipped++;
			continue;
		}

		$grouped[$athlete_id][$event_id][] = [
			'result_id'       => $rid,
			'meet_date'       => $meet_dates[$mid] ?? '',
			'value'           => $value,
			'measurement'     => $measurement,
			'lower_is_better' => $lower_is_better,
		];
	}

	// For each athlete + event, walk results chronologically.
	foreach ( $grouped as $athlete_id => $events ) {
		foreach ( $events as $event_id => $results ) {

			$lower_is_better = $results[0]['lower_is_better'];

			// Sort by meet date ascending (chronological).
			usort( $results, fn( $a, $b ) => strcmp( $a['meet_date'], $b['meet_date'] ) );

			// Build lookup: result_id → existing record types (to avoid duplicates).
			$existing_by_result = []; // result_id => [ 'PR', 'SR', ... ]
			$existing_records   = get_posts( [
				'post_type'      => 'athletic_record',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => [
					'relation' => 'AND',
					[ 'key' => 'athlete', 'value' => $athlete_id, 'compare' => '=' ],
					[ 'key' => 'event',   'value' => $event_id,   'compare' => '=' ],
				],
			] );
			foreach ( $existing_records as $rec_id ) {
				$linked_result = (int) get_post_meta( $rec_id, 'result', true );
				$rec_type      = get_field( 'record_type', $rec_id );
				if ( $linked_result ) {
					$existing_by_result[$linked_result][] = $rec_type;
				}
			}

			// Walk forward through time, tracking running bests.
			// PR: all-time best across all seasons.
			// SR: best within this season only.
			$all_time_best  = null;
			$season_best    = null;

			// We also need all prior results from before this season for PR baseline.
			// Query once per athlete+event outside the season.
			$prior_all_results = get_posts( [
				'post_type'      => 'athletic_result',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => [
					'relation' => 'AND',
					[ 'key' => 'athlete',        'value' => $athlete_id,    'compare' => '=' ],
					[ 'key' => 'athletic_event', 'value' => $event_id,      'compare' => '=' ],
					[ 'key' => 'meet',           'value' => $season_meet_ids, 'compare' => 'NOT IN' ],
				],
			] );

			foreach ( $prior_all_results as $pid ) {
				$measurement = $results[0]['measurement'];
				$pv          = 0;
				switch ( $measurement ) {
					case 'Time':     $pv = (float) get_post_meta( $pid, 'result_time_seconds',    true ); break;
					case 'Distance': $pv = (float) get_post_meta( $pid, 'result_distance_meters', true ); break;
					case 'Height':   $pv = (float) get_post_meta( $pid, 'result_height_meters',   true ); break;
					case 'Points':   $pv = (float) get_post_meta( $pid, 'result_points',          true ); break;
				}
				if ( ! $pv || $pv <= 0 ) continue;
				if ( $all_time_best === null
					|| ( $lower_is_better  && $pv < $all_time_best )
					|| ( ! $lower_is_better && $pv > $all_time_best )
				) {
					$all_time_best = $pv;
				}
			}

			// Now walk this season's results chronologically.
			foreach ( $results as $r ) {

				$rid   = $r['result_id'];
				$value = $r['value'];
				$already = $existing_by_result[$rid] ?? [];

				// PR check.
				if ( ! in_array( 'PR', $already, true ) ) {
					$is_pr = $all_time_best === null
						|| ( $lower_is_better  && $value < $all_time_best )
						|| ( ! $lower_is_better && $value > $all_time_best );

					if ( $is_pr ) {
						$result = tb_create_record_post( $athlete_id, $event_id, $rid, 'PR' );
						is_wp_error( $result ) ? $skipped++ : $created++;
					}
				}

				// SR check.
				if ( ! in_array( 'SR', $already, true ) ) {
					$is_sr = $season_best === null
						|| ( $lower_is_better  && $value < $season_best )
						|| ( ! $lower_is_better && $value > $season_best );

					if ( $is_sr ) {
						$result = tb_create_record_post( $athlete_id, $event_id, $rid, 'SR' );
						is_wp_error( $result ) ? $skipped++ : $created++;
					}
				}

				// Update running bests after processing this result.
				if ( $all_time_best === null
					|| ( $lower_is_better  && $value < $all_time_best )
					|| ( ! $lower_is_better && $value > $all_time_best )
				) {
					$all_time_best = $value;
				}
				if ( $season_best === null
					|| ( $lower_is_better  && $value < $season_best )
					|| ( ! $lower_is_better && $value > $season_best )
				) {
					$season_best = $value;
				}
			}
		}
	}

	return [ 'created' => $created, 'skipped' => $skipped ];
}


/**
 * Render the Tools → Generate Records admin page.
 */
function tb_generate_records_page(): void {

	// Load all athletic seasons for the selector.
	$seasons = get_posts( [
		'post_type'      => 'athletic_season',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	$did_run    = isset( $_GET['tb_gen_done'] );
	$has_error  = isset( $_GET['tb_gen_error'] );
	$created    = $did_run ? (int) ( $_GET['tb_gen_created'] ?? 0 ) : 0;
	$skipped    = $did_run ? (int) ( $_GET['tb_gen_skipped'] ?? 0 ) : 0;

	?>
	<div class="wrap">
		<h1>Generate Records</h1>
		<p>
			Scans all results for a season and creates <code>athletic_record</code> posts
			for any PR or SR that hasn't been recorded yet. Run this after every WPUCI
			results import. The tool is idempotent — safe to re-run.
		</p>

		<?php if ( $did_run ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong>Done.</strong>
					<?php echo esc_html( $created ); ?> record<?php echo $created !== 1 ? 's' : ''; ?> created,
					<?php echo esc_html( $skipped ); ?> skipped.
				</p>
			</div>
		<?php endif; ?>

		<?php if ( $has_error ) : ?>
			<div class="notice notice-error is-dismissible">
				<p><strong>Error:</strong> Please select a season before running.</p>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'tb_generate_records_action', 'tb_generate_records_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="tb_season_id">Season</label></th>
					<td>
						<select name="tb_season_id" id="tb_season_id">
							<option value="">— Select a season —</option>
							<?php foreach ( $seasons as $season ) :
								$title = get_field( 'season_title', $season->ID ) ?: $season->post_title;
							?>
								<option value="<?php echo esc_attr( $season->ID ); ?>">
									<?php echo esc_html( $title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="tb_generate_records" class="button button-primary">
					Generate Records
				</button>
			</p>
		</form>
	</div>
	<?php
}