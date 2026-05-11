<?php
/**
 * Template: single-athletic_event.php
 * Displays a single Athletic Event page.
 *
 * Sections:
 *   1. Event header (name, sport, category, measurement type, distance, relay status)
 *   2. All-time records for this event (queried from Athletic Record)
 *   3. Results history — all results for this event across all athletes and meets,
 *      single flat list sorted by date descending
 *
 * Field references:
 *   group_tb_athletic_event.json  — event fields
 *   group_tb_athletic_record.json — record fields (queried); event field name: 'event'
 *   group_tb_athletic_result.json — result fields (queried); event field name: 'athletic_event'
 *   group_tb_athletic_season.json — results_enabled flag (customize_data group)
 *   group_tb_enrollment.json      — grade (queried per athlete+season)
 *   group_tb_athlete.json         — names group, demographics group (gender)
 *
 * TEC field references (postmeta, not ACF):
 *   _EventStartDate — meet start datetime (format: Y-m-d H:i:s)
 *
 * FIELD NAME NOTES:
 *   On athletic_result: the Athletic Event relationship field is named 'athletic_event'
 *   On athletic_record: the Athletic Event relationship field is named 'event'
 *   These differ — use the correct name per CPT or meta_query will return nothing.
 *
 *   Athlete name fields live inside the 'names' ACF group.
 *   Direct get_field( 'first_name', $id ) returns NULL.
 *
 *   Gender lives inside the 'demographics' ACF group on Athlete.
 *
 *   results_enabled is a sub-field of 'customize_data' on Athletic Season.
 *   Read via: $customize = get_field( 'customize_data', $season_id ) ?: [];
 *
 *   Grade lives on Enrollment (not Athlete). Looked up via bulk enrollment query
 *   after the main results loop, keyed by [ athlete_id ][ season_id ].
 *
 * CSS note — add to templates.css:
 *   .tb-event-records-list { --tb-cols: 1fr 2fr 1.5fr 2fr 1.5fr; }
 *   .tb-event-results-list { --tb-cols: 2fr 1fr 2fr 0.75fr 1.5fr 1.5fr; }
 */

get_header();

while ( have_posts() ) :
	the_post();

	$event_id = get_the_ID();

	// -------------------------------------------------------------------------
	// EVENT CORE FIELDS
	// -------------------------------------------------------------------------
	$event_name     = get_field( 'event_name', $event_id );
	$event_category = get_field( 'event_category', $event_id );
	$measurement    = get_field( 'measurement_type', $event_id ); // Time | Distance | Height | Points
	$distance_value = get_field( 'distance_value', $event_id );
	$distance_unit  = get_field( 'distance_unit', $event_id );    // meters | kilometers | miles
	$unit_display   = get_field( 'unit_display', $event_id );     // mm:ss.00 | meters | feet/inches
	$is_relay       = get_field( 'is_relay', $event_id );

	// Sport taxonomy terms
	$sports = get_the_terms( $event_id, 'sport' );

	// Build distance string
	$distance_display = '';
	if ( $distance_value && $distance_unit ) {
		$unit_label = [
			'meters'     => 'm',
			'kilometers' => 'km',
			'miles'      => $distance_value == 1 ? 'mile' : 'miles',
		];
		$distance_display = $distance_value . ' ' . ( $unit_label[ $distance_unit ] ?? $distance_unit );
	}

	// -------------------------------------------------------------------------
	// RECORDS — all-time records for this event
	// Note: athletic_record uses field name 'event' for the Athletic Event link
	// -------------------------------------------------------------------------
	$records_query = new WP_Query( [
		'post_type'      => 'athletic_record',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'     => 'event',
				'value'   => $event_id,
				'compare' => '=',
			],
		],
	] );

	$records = [];
	if ( $records_query->have_posts() ) {
		foreach ( $records_query->posts as $record ) {
			$athlete_id       = get_field( 'athlete', $record->ID );
			$linked_result_id = get_field( 'result', $record->ID );
			$meet_id          = $linked_result_id ? get_field( 'meet', $linked_result_id ) : null;

			// Athlete name — fields are inside the 'names' ACF group.
			$names     = $athlete_id ? ( get_field( 'names', $athlete_id ) ?: [] ) : [];
			$first     = $names['first_name']     ?? '';
			$preferred = $names['preferred_name'] ?? '';
			$last      = $names['last_name']      ?? '';

			// TEC date from _EventStartDate postmeta
			$raw_date  = $meet_id ? get_post_meta( $meet_id, '_EventStartDate', true ) : '';
			$meet_date = $raw_date ? date( 'Y-m-d', strtotime( $raw_date ) ) : '';

			$records[] = [
				'record_id'      => $record->ID,
				'record_type'    => get_field( 'record_type', $record->ID ),
				'athlete_id'     => $athlete_id,
				'athlete_name'   => trim( ( $preferred ?: $first ) . ' ' . $last ),
				'result_display' => $linked_result_id ? get_field( 'result_display', $linked_result_id ) : '—',
				'meet_id'        => $meet_id,
				'meet_name'      => $meet_id ? get_the_title( $meet_id ) : '—',
				'meet_date'      => $meet_date ? date_i18n( 'F j, Y', strtotime( $meet_date ) ) : '—',
			];
		}
	}
	wp_reset_postdata();

	// -------------------------------------------------------------------------
	// RESULTS HISTORY — flat list across all seasons and meets
	// Seasons where results_enabled = false are excluded.
	// -------------------------------------------------------------------------
	$results_query = new WP_Query( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'     => 'athletic_event',
				'value'   => $event_id,
				'compare' => '=',
			],
		],
	] );

	$flat_results = [];
	$season_meta  = []; // season_id => [ title, start_date, results_enabled ]
	$athlete_ids  = []; // unique athlete IDs for bulk lookups
	$season_ids   = []; // unique season IDs for bulk enrollment query

	if ( $results_query->have_posts() ) {
		foreach ( $results_query->posts as $result ) {
			$meet_id    = get_field( 'meet', $result->ID );
			$athlete_id = get_field( 'athlete', $result->ID );
			if ( ! $meet_id ) continue;

			$season_id = get_field( 'season', $meet_id );
			if ( ! $season_id ) continue;

			// Cache season metadata; results_enabled is inside customize_data group.
			if ( ! isset( $season_meta[ $season_id ] ) ) {
				$customize = get_field( 'customize_data', $season_id ) ?: [];
				$season_meta[ $season_id ] = [
					'title'           => get_field( 'season_title', $season_id ) ?: get_the_title( $season_id ),
					'start_date'      => get_field( 'start_date', $season_id ),
					'results_enabled' => $customize['results_enabled'] ?? false,
				];
			}

			// Skip seasons where results display is disabled.
			if ( ! $season_meta[ $season_id ]['results_enabled'] ) continue;

			// Athlete name — fields are inside the 'names' ACF group.
			$names     = $athlete_id ? ( get_field( 'names', $athlete_id ) ?: [] ) : [];
			$first     = $names['first_name']     ?? '';
			$preferred = $names['preferred_name'] ?? '';
			$last      = $names['last_name']      ?? '';

			// TEC date from _EventStartDate postmeta
			$raw_date  = get_post_meta( $meet_id, '_EventStartDate', true );
			$meet_date = $raw_date ? date( 'Y-m-d', strtotime( $raw_date ) ) : '';

			// Collect unique IDs for bulk lookups below.
			if ( $athlete_id && ! in_array( $athlete_id, $athlete_ids, true ) ) {
				$athlete_ids[] = $athlete_id;
			}
			if ( ! in_array( $season_id, $season_ids, true ) ) {
				$season_ids[] = $season_id;
			}

			$flat_results[] = [
				'result_id'      => $result->ID,
				'meet_id'        => $meet_id,
				'meet_name'      => get_the_title( $meet_id ),
				'meet_slug'      => sanitize_title( get_the_title( $meet_id ) ),
				'meet_date'      => $meet_date,
				'meet_year'      => $meet_date ? date( 'Y', strtotime( $meet_date ) ) : '',
				'season_id'      => $season_id,
				'athlete_id'     => $athlete_id,
				'athlete_name'   => trim( ( $preferred ?: $first ) . ' ' . $last ) ?: 'Unknown',
				'last_name'      => $last,
				'heat'           => (string) ( get_field( 'heat', $result->ID ) ?: '' ),
				'result_display' => get_field( 'result_display', $result->ID ),
				'time_seconds'   => get_field( 'result_time_seconds', $result->ID ),
			];
		}

		// Sort flat list by date descending (most recent first).
		usort( $flat_results, function( $a, $b ) {
			return strcmp( $b['meet_date'], $a['meet_date'] );
		} );
	}
	wp_reset_postdata();

	// -------------------------------------------------------------------------
	// GRADE LOOKUP — bulk enrollment query across all athlete + season combos.
	// Grade lives on Enrollment, keyed by [ athlete_id ][ season_id ].
	// -------------------------------------------------------------------------
	$grade_map  = []; // [ athlete_id ][ season_id ] => grade
	$gender_map = []; // [ athlete_id ] => gender (from demographics group on Athlete)

	if ( ! empty( $athlete_ids ) ) {

		// Grade — one enrollment query covering all athletes and seasons.
		if ( ! empty( $season_ids ) ) {
			$enrollment_query = new WP_Query( [
				'post_type'      => 'enrollment',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'     => 'athlete',
						'value'   => $athlete_ids,
						'compare' => 'IN',
					],
					[
						'key'     => 'season',
						'value'   => $season_ids,
						'compare' => 'IN',
					],
				],
			] );

			foreach ( $enrollment_query->posts as $enrollment_id ) {
				$enrolled_athlete = get_field( 'athlete', $enrollment_id );
				$enrolled_season  = get_field( 'season', $enrollment_id );
				$grade            = get_field( 'grade', $enrollment_id );
				if ( $enrolled_athlete && $enrolled_season ) {
					$grade_map[ $enrolled_athlete ][ $enrolled_season ] = $grade ?: '';
				}
			}
			wp_reset_postdata();
		}

		// Gender — from the 'demographics' ACF group on each Athlete post.
		foreach ( $athlete_ids as $aid ) {
			$demographics        = get_field( 'demographics', $aid ) ?: [];
			$gender_map[ $aid ]  = strtolower( $demographics['gender'] ?? '' );
		}
	}

?>

<div class="tb-single tb-event">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: EVENT HEADER                                            ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-header tb-event-header">

		<div class="tb-single-headline tb-event-headline">

			<h1 class="tb-single-title tb-event-name"><?php echo esc_html( $event_name ?: get_the_title() ); ?></h1>

			<div class="tb-single-meta tb-event-meta">

				<?php if ( $sports && ! is_wp_error( $sports ) ) : ?>
					<?php foreach ( $sports as $sport ) :
						$sport_url = get_term_link( $sport );
					?>
					<span class="tb-meta-sport">
						<?php if ( ! is_wp_error( $sport_url ) ) : ?>
							<a href="<?php echo esc_url( $sport_url ); ?>"><?php echo esc_html( $sport->name ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $sport->name ); ?>
						<?php endif; ?>
					</span>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ( $event_category ) : ?>
					<span class="tb-meta-category"><?php echo esc_html( $event_category ); ?></span>
				<?php endif; ?>

				<?php if ( $distance_display ) : ?>
					<span class="tb-meta-distance"><?php echo esc_html( $distance_display ); ?></span>
				<?php endif; ?>

				<?php if ( $measurement ) : ?>
					<span class="tb-meta-measurement">
						<?php echo esc_html( $measurement ); ?>
						<?php if ( $unit_display ) : ?>
							(<?php echo esc_html( $unit_display ); ?>)
						<?php endif; ?>
					</span>
				<?php endif; ?>

				<?php if ( $is_relay ) : ?>
					<span class="tb-meta-relay">Relay</span>
				<?php endif; ?>

			</div><!-- .tb-single-meta .tb-event-meta -->

		</div><!-- .tb-single-headline .tb-event-headline -->

	</section><!-- .tb-single-header .tb-event-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: ALL-TIME RECORDS                                        ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-section tb-event-records">

		<h2>Records</h2>

		<?php if ( empty( $records ) ) : ?>
			<p class="tb-no-data">No records on file for this event.</p>
		<?php else : ?>
			<div class="tb-list-wrap tb-event-records-list-wrap">
				<div class="tb-list-header">
					<span class="tb-col">Type</span>
					<span class="tb-col">Athlete</span>
					<span class="tb-col">Result</span>
					<span class="tb-col">Meet</span>
					<span class="tb-col">Date</span>
				</div>
				<ul class="tb-list tb-event-records-list">
					<?php foreach ( $records as $record ) : ?>
					<li class="tb-list-row">
						<div class="tb-list-link">
							<span class="tb-col"><?php echo esc_html( $record['record_type'] ?: '—' ); ?></span>
							<span class="tb-col">
								<?php if ( $record['athlete_id'] ) : ?>
									<a href="<?php echo esc_url( get_permalink( $record['athlete_id'] ) ); ?>">
										<?php echo esc_html( $record['athlete_name'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $record['athlete_name'] ?: '—' ); ?>
								<?php endif; ?>
							</span>
							<span class="tb-col"><?php echo esc_html( $record['result_display'] ?: '—' ); ?></span>
							<span class="tb-col">
								<?php if ( $record['meet_id'] ) : ?>
									<a href="<?php echo esc_url( get_permalink( $record['meet_id'] ) ); ?>">
										<?php echo esc_html( $record['meet_name'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $record['meet_name'] ?: '—' ); ?>
								<?php endif; ?>
							</span>
							<span class="tb-col"><?php echo esc_html( $record['meet_date'] ?: '—' ); ?></span>
						</div>
					</li>
					<?php endforeach; ?>
				</ul>
			</div><!-- .tb-list-wrap -->
		<?php endif; ?>

	</section><!-- .tb-single-section .tb-event-records -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 3: RESULTS HISTORY                                         ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-section tb-event-results">

		<h2>Results History</h2>

		<?php if ( empty( $flat_results ) ) : ?>
			<p class="tb-no-data">No results on file for this event.</p>
		<?php else : ?>
			<div class="tb-list-wrap tb-event-results-list-wrap">
				<div class="tb-list-header">
					<span class="tb-col">Meet</span>
					<span class="tb-col">Date</span>
					<span class="tb-col">Athlete</span>
					<span class="tb-col">Grade</span>
					<span class="tb-col">Heat</span>
					<span class="tb-col">Result</span>
				</div>
				<ul class="tb-list tb-event-results-list">
					<?php foreach ( $flat_results as $r ) :
						$grade        = $grade_map[ $r['athlete_id'] ][ $r['season_id'] ] ?? '';
						$gender       = $gender_map[ $r['athlete_id'] ] ?? '';
						$seconds_attr = is_numeric( $r['time_seconds'] ) ? (string) $r['time_seconds'] : '';
						$date_display = $r['meet_date'] ? date_i18n( 'M j, Y', strtotime( $r['meet_date'] ) ) : '—';
					?>
					<li class="tb-list-row"
						data-meet="<?php echo esc_attr( $r['meet_slug'] ); ?>"
						data-date="<?php echo esc_attr( $r['meet_date'] ); ?>"
						data-year="<?php echo esc_attr( $r['meet_year'] ); ?>"
						data-last-name="<?php echo esc_attr( strtolower( $r['last_name'] ) ); ?>"
						data-grade="<?php echo esc_attr( strtolower( $grade ) ); ?>"
						data-gender="<?php echo esc_attr( $gender ); ?>"
						data-heat="<?php echo esc_attr( $r['heat'] !== '' ? sanitize_title( $r['heat'] ) : '' ); ?>"
						data-result-seconds="<?php echo esc_attr( $seconds_attr ); ?>">
						<div class="tb-list-link">
							<span class="tb-col">
								<a href="<?php echo esc_url( get_permalink( $r['meet_id'] ) ); ?>">
									<?php echo esc_html( $r['meet_name'] ); ?>
								</a>
							</span>
							<span class="tb-col"><?php echo esc_html( $date_display ); ?></span>
							<span class="tb-col">
								<?php if ( $r['athlete_id'] ) : ?>
									<a href="<?php echo esc_url( get_permalink( $r['athlete_id'] ) ); ?>">
										<?php echo esc_html( $r['athlete_name'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $r['athlete_name'] ); ?>
								<?php endif; ?>
							</span>
							<span class="tb-col"><?php echo $grade !== '' ? esc_html( $grade ) : '—'; ?></span>
							<span class="tb-col"><?php echo $r['heat'] !== '' ? esc_html( $r['heat'] ) : '—'; ?></span>
							<span class="tb-col"><?php echo esc_html( $r['result_display'] ?: '—' ); ?></span>
						</div>
					</li>
					<?php endforeach; ?>
				</ul>
			</div><!-- .tb-list-wrap -->
		<?php endif; ?>

	</section><!-- .tb-single-section .tb-event-results -->

</div><!-- .tb-single .tb-event -->

<?php
endwhile;

get_footer();