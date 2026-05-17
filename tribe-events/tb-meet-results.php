<?php
/**
 * View: tribe/events/single-event.php
 * Results section for athletic-meet events.
 *
 * Appended after TEC's normal event output by tribe/events/v2/default-template.php.
 * No category guard needed — default-template.php gates this to athletic-meet events.
 * No get_header() / get_footer() — handled by default-template.php.
 * No WordPress loop — use get_queried_object_id() for the current post ID.
 *
 * Results are grouped by athletic event. Each event block is an independent
 * Isotope instance (.tb-isotope-instance). Heat is a filterable data attribute
 * on each row — athletes can sort and compare times across heats within an event.
 * When no results for a given event have a heat value, the heat filter is omitted
 * for that event. The Heat column always renders (showing — when empty).
 *
 * Columns: Athlete | Grade | Heat | Result | Place
 *
 * Grade is sourced from the athlete's Enrollment for this season via a single
 * bulk enrollment query after the results loop.
 *
 * Field references:
 *   group_tb_athletic_meet.json   — season (post object → ID), results_status (select)
 *   group_tb_athletic_result.json — athlete, meet, event_name, heat, result_display,
 *                                   result_time_seconds, place
 *   group_tb_athlete.json         — names group (first_name, preferred_name, last_name)
 *   group_tb_enrollment.json      — athlete (post object → ID), season (post object → ID),
 *                                   grade (text)
 *
 * FIELD NAME NOTES:
 *   - Athlete names are inside the 'names' ACF group. Read via:
 *     $names = get_field( 'names', $athlete_id ); then $names['first_name'] etc.
 *     Direct get_field( 'first_name', $athlete_id ) returns NULL.
 *   - results_status and season are top-level ACF fields on tribe_events (no group wrapper).
 *   - heat is a top-level text field on athletic_result. Empty string = no heat assigned.
 *
 * CSS note:
 *   .tb-meet-event-list { --tb-cols: 3fr 1fr 1.5fr 1.5fr 1fr; }
 */

$meet_id        = get_queried_object_id();
$results_status = get_field( 'results_status', $meet_id ); // Future | Pending | Available
$season_id      = get_field( 'season', $meet_id );         // needed for grade lookup

// -------------------------------------------------------------------------
// RESULTS — query and group by athletic event (flat array per event).
// Only runs when results_status === 'Available'.
// -------------------------------------------------------------------------
$results_by_event = []; // [ event_key => [ result, ... ] ]
$heats_by_event   = []; // [ event_key => [ heat_name, ... ] ] — unique heats per event
$athlete_ids      = []; // unique athlete IDs for the bulk grade lookup

if ( $results_status === 'Available' ) {

	$results_query = new WP_Query( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'     => 'meet',
				'value'   => $meet_id,
				'compare' => '=',
			],
		],
	] );

	if ( $results_query->have_posts() ) {
		foreach ( $results_query->posts as $result ) {

			$athlete_id     = get_field( 'athlete', $result->ID );
			$event_name     = get_field( 'event_name', $result->ID );
			$heat           = (string) ( get_field( 'heat', $result->ID ) ?: '' );
			$result_display = get_field( 'result_display', $result->ID );
			$place          = get_field( 'place', $result->ID );
			$time_seconds   = get_field( 'result_time_seconds', $result->ID );

			// Athlete name — fields are inside the 'names' ACF group.
			// Direct get_field( 'first_name', $athlete_id ) returns NULL.
			$athlete_name = '';
			$last         = '';
			$gender       = ''; 
			if ( $athlete_id ) {
				$demographics = get_field( 'demographics', $athlete_id ) ?: [];
				$gender       = $demographics['gender'] ?? '';
				$names        = get_field( 'names', $athlete_id ) ?: [];
				$first        = $names['first_name']     ?? '';
				$preferred    = $names['preferred_name'] ?? '';
				$last         = $names['last_name']      ?? '';
				$athlete_name = trim( ( $preferred ?: $first ) . ' ' . $last );

				// Collect unique athlete IDs for the bulk grade lookup below.
				if ( ! in_array( $athlete_id, $athlete_ids, true ) ) {
					$athlete_ids[] = $athlete_id;
				}
			}

			$event_key = $event_name ?: 'Unknown Event';

			// Collect unique heat names per event for per-event filter UIs.
			// Empty heat values are excluded — they show as — in the column.
			if ( $heat !== '' ) {
				if ( ! isset( $heats_by_event[ $event_key ] ) ) {
					$heats_by_event[ $event_key ] = [];
				}
				if ( ! in_array( $heat, $heats_by_event[ $event_key ], true ) ) {
					$heats_by_event[ $event_key ][] = $heat;
				}
			}

			$results_by_event[ $event_key ][] = [
				'result_id'      => $result->ID,
				'athlete_id'     => $athlete_id,
				'athlete_name'   => $athlete_name ?: 'Unknown Athlete',
				'last_name'      => $last,
				'gender'         => $gender,  
				'result_display' => $result_display,
				'place'          => $place,
				'time_seconds'   => $time_seconds,
				'heat'           => $heat,
				'heat_slug'      => $heat !== '' ? sanitize_title( $heat ) : '',
			];
		}

		// Sort each event's results by place ascending (nulls last).
		foreach ( $results_by_event as $event_key => &$event_results ) {
			usort( $event_results, function( $a, $b ) {
				$pa = ( $a['place'] !== '' && $a['place'] !== null ) ? (int) $a['place'] : PHP_INT_MAX;
				$pb = ( $b['place'] !== '' && $b['place'] !== null ) ? (int) $b['place'] : PHP_INT_MAX;
				return $pa - $pb;
			} );
		}
		unset( $event_results );

		// Sort event groups alphabetically.
		ksort( $results_by_event );
	}

	wp_reset_postdata();

	// -------------------------------------------------------------------------
	// GRADE LOOKUP — single bulk enrollment query for all athletes in this meet.
	// Enrollments are matched by season + athlete. Grade lives on Enrollment,
	// not on the Athlete post directly.
	// -------------------------------------------------------------------------
	$grade_map = []; // [ athlete_id => grade ]

	if ( $season_id && ! empty( $athlete_ids ) ) {

		$enrollment_query = new WP_Query( [
			'post_type'      => 'enrollment',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => 'season',
					'value'   => $season_id,
					'compare' => '=',
				],
				[
					'key'     => 'athlete',
					'value'   => $athlete_ids,
					'compare' => 'IN',
				],
			],
		] );

		foreach ( $enrollment_query->posts as $enrollment_id ) {
			$enrolled_athlete = get_field( 'athlete', $enrollment_id );
			$grade            = get_field( 'grade', $enrollment_id );
			if ( $enrolled_athlete ) {
				$grade_map[ $enrolled_athlete ] = $grade ?: '';
			}
		}

		wp_reset_postdata();
	}
	
	// -------------------------------------------------------------------------
	// RECORD MAP — build result_id → [record_types] for badge display.
	// Queries athletic_record posts whose linked result is in this meet.
	// Only runs when there are results to display.
	// -------------------------------------------------------------------------
	$result_record_map = []; // [ result_id => [ 'PR', 'SR', ... ] ]
	
	if ( ! empty( $results_by_event ) ) {
	
		// Collect all result IDs from the grouped results.
		$all_result_ids = [];
		foreach ( $results_by_event as $event_results ) {
			foreach ( $event_results as $r ) {
				$all_result_ids[] = $r['result_id'];
			}
		}
	
		if ( ! empty( $all_result_ids ) ) {
			$records_query = new WP_Query( [
				'post_type'      => 'athletic_record',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => [
					[
						'key'     => 'result',
						'value'   => $all_result_ids,
						'compare' => 'IN',
					],
				],
			] );
	
			foreach ( $records_query->posts as $record_id ) {
				$linked_result = (int) get_post_meta( $record_id, 'result', true );
				$record_type   = get_field( 'record_type', $record_id );
				if ( $linked_result && $record_type ) {
					$result_record_map[ $linked_result ][] = $record_type;
				}
			}
	
			wp_reset_postdata();
		}
	}
}

?>

<section class="tb-single-section tb-meet-results">

	<h2>Team Results</h2>

	<?php if ( $results_status === 'Future' ) : ?>
		<p class="tb-results-notice">Results will be available after the meet.</p>

	<?php elseif ( $results_status === 'Pending' ) : ?>
		<p class="tb-results-notice">Results are being finalized. Check back soon.</p>

	<?php elseif ( $results_status === 'Available' && empty( $results_by_event ) ) : ?>
		<p class="tb-no-data">No results found for this meet.</p>

	<?php elseif ( ! empty( $results_by_event ) ) : ?>

		<?php foreach ( $results_by_event as $event_name => $event_results ) :
			$event_slug      = sanitize_title( $event_name );
			$event_has_heats = ! empty( $heats_by_event[ $event_name ] );
		?>
		<div class="tb-results-event tb-isotope-instance" data-event="<?php echo esc_attr( $event_slug ); ?>">

			<h3 class="tb-event-label"><?php echo esc_html( $event_name ); ?> (<span class="filter-count"></span>)</h3>

			<div class="tb-ui-controls">

				<?php if ( $event_has_heats ) : ?>
				<div class="tb-filter-controls controls-group">
					<h4>Filter By</h4>
					<div class="ui-group">
						  <select type="select" class="filter-select filter-options" data-group="gender">
							<option value="">Gender</option>
							<option value="[data-gender='m']" id="filter-boys">Boys</option>
							<option value="[data-gender='f']" id="filter-girls">Girls</option>
							<option value="">All</option>
						  </select>
					  </div>
					<div class="ui-group">
						<select type="select" class="filter-select filter-options" data-group="heat">
							<option value="">All Heats</option>
							<?php foreach ( $heats_by_event[ $event_name ] as $heat_name ) : ?>
							<option value="[data-heat='<?php echo esc_attr( sanitize_title( $heat_name ) ); ?>']">
								<?php echo esc_html( $heat_name ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<?php endif; ?>

				<div class="tb-sort-controls controls-group">
					<h4>Sort By</h4>
					<div class="tb-sorts button-group">
						<button class="button" data-sort-by="result_seconds">Time</button>
						<button class="button" data-sort-by="last_name">Name</button>
						<!--<button class="button is-checked" data-sort-by="place">Place</button>-->
					</div>
				</div>

			</div><!-- .tb-ui-controls -->

			<div class="tb-list-wrap tb-meet-event-list-wrap">
				<div class="tb-list-header">
					<span class="tb-col">Athlete</span>
					<span class="tb-col">Grade</span>
					<span class="tb-col">Heat</span>
					<span class="tb-col">Result</span>
					<span class="tb-col">Place</span>
				</div>
				<ul class="tb-isotope-grid tb-list tb-meet-event-list">
					<?php foreach ( $event_results as $r ) :
						$place_attr   = ( $r['place'] !== '' && $r['place'] !== null ) ? (string) $r['place'] : '';
						$seconds_attr = is_numeric( $r['time_seconds'] ) ? (string) $r['time_seconds'] : '';
						$grade        = isset( $grade_map[ $r['athlete_id'] ] ) ? $grade_map[ $r['athlete_id'] ] : '';
					?>
					<li class="tb-list-row"
						data-last-name="<?php echo esc_attr( strtolower( $r['last_name'] ) ); ?>"
						data-gender="<?php echo esc_attr( strtolower( $r['gender'] ) ); ?>"
						data-place="<?php echo esc_attr( $place_attr ); ?>"
						data-result-seconds="<?php echo esc_attr( $seconds_attr ); ?>"
						data-athlete-id="<?php echo esc_attr( (string) ( $r['athlete_id'] ?: '' ) ); ?>"
						data-heat="<?php echo esc_attr( $r['heat_slug'] ); ?>"
						data-grade="<?php echo esc_attr( strtolower( $grade ) ); ?>">
						<?php if ( $r['athlete_id'] ) : ?>
							<a href="<?php echo esc_url( get_permalink( $r['athlete_id'] ) ); ?>" class="tb-list-link">
						<?php else : ?>
							<div class="tb-list-link">
						<?php endif; ?>
								<span class="tb-col"><?php echo esc_html( $r['athlete_name'] ); ?></span>
								<span class="tb-col"><?php echo $grade !== '' ? esc_html( $grade ) : '—'; ?></span>
								<span class="tb-col"><?php echo $r['heat'] !== '' ? esc_html( $r['heat'] ) : '—'; ?></span>
								<span class="tb-col">
									<?php echo esc_html( $r['result_display'] ?: '—' ); ?>
									<?php
									$badges = $result_record_map[ $r['result_id'] ] ?? [];
									if ( $badges ) :
										sort( $badges ); // PR before SR alphabetically
										foreach ( $badges as $badge ) :
									?>
										<span class="tb-record-badge tb-record-badge--<?php echo esc_attr( strtolower( $badge ) ); ?>">
											<?php echo esc_html( $badge ); ?>
										</span>
									<?php
										endforeach;
									endif;
									?>
								</span>
								<span class="tb-col"><?php echo $place_attr !== '' ? esc_html( $place_attr ) : '—'; ?></span>
						<?php if ( $r['athlete_id'] ) : ?>
							</a>
						<?php else : ?>
							</div>
						<?php endif; ?>
					</li>
					<?php endforeach; ?>
				</ul>
			</div><!-- .tb-list-wrap .tb-meet-event-list-wrap -->

		</div><!-- .tb-results-event .tb-isotope-instance -->
		<?php endforeach; ?>

	<?php endif; ?>

</section><!-- .tb-single-section .tb-meet-results -->