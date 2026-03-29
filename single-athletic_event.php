<?php
/**
 * Template: single-athletic_event.php
 * Displays a single Athletic Event page.
 *
 * Sections:
 *   1. Event header (name, category, measurement type, distance, relay status)
 *   2. All-time records for this event (queried from Athletic Record)
 *   3. Results history — all results for this event across all athletes and meets,
 *      grouped by season → meet, sorted by date descending
 *
 * Field references:
 *   group_tb_athletic_event.json  — event fields
 *   group_tb_athletic_record.json — record fields (queried); event field name: 'event'
 *   group_tb_athletic_result.json — result fields (queried); event field name: 'athletic_event'
 *   group_tb_athletic_season.json — results_enabled flag (customize_data group, direct query)
 *
 * TEC field references (postmeta, not ACF):
 *   _EventStartDate — meet start datetime (format: Y-m-d H:i:s)
 *
 * FIELD NAME NOTE:
 *   On athletic_result: the Athletic Event relationship field is named 'athletic_event'
 *   On athletic_record: the Athletic Event relationship field is named 'event'
 *   These differ — use the correct name per CPT or meta_query will return nothing.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$event_id = get_the_ID();

	// -------------------------------------------------------------------------
	// EVENT CORE FIELDS
	// -------------------------------------------------------------------------
	$event_name      = get_field( 'event_name', $event_id );
	$event_category  = get_field( 'event_category', $event_id );
	$measurement     = get_field( 'measurement_type', $event_id ); // Time | Distance | Height | Points
	$distance_value  = get_field( 'distance_value', $event_id );
	$distance_unit   = get_field( 'distance_unit', $event_id );    // meters | kilometers | miles
	$unit_display    = get_field( 'unit_display', $event_id );     // mm:ss.00 | meters | feet/inches
	$is_relay        = get_field( 'is_relay', $event_id );

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

			$first     = $athlete_id ? get_field( 'first_name', $athlete_id ) : '';
			$preferred = $athlete_id ? get_field( 'preferred_name', $athlete_id ) : '';
			$last      = $athlete_id ? get_field( 'last_name', $athlete_id ) : '';

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
	// RESULTS HISTORY — all results for this event, grouped by season → meet
	// Note: athletic_result uses field name 'athletic_event' for the Athletic Event link
	// Seasons where results_enabled = false are excluded from display
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

	// Group: season_id => meet_id => [ results ]
	$results_by_season = [];
	$season_meta       = []; // season_id => [ title, start_date, results_enabled ]

	if ( $results_query->have_posts() ) {
		foreach ( $results_query->posts as $result ) {
			$meet_id    = get_field( 'meet', $result->ID );
			$athlete_id = get_field( 'athlete', $result->ID );
			if ( ! $meet_id ) continue;

			$season_id = get_field( 'season', $meet_id );
			if ( ! $season_id ) continue;

			// Cache season metadata — only look up once per season
			if ( ! isset( $season_meta[ $season_id ] ) ) {
				$season_meta[ $season_id ] = [
					'title'           => get_field( 'season_title', $season_id ) ?: get_the_title( $season_id ),
					'start_date'      => get_field( 'start_date', $season_id ),
					'results_enabled' => get_field( 'results_enabled', $season_id ),
				];
			}

			// Skip seasons where results display is disabled
			if ( ! $season_meta[ $season_id ]['results_enabled'] ) continue;

			$first     = $athlete_id ? get_field( 'first_name', $athlete_id ) : '';
			$preferred = $athlete_id ? get_field( 'preferred_name', $athlete_id ) : '';
			$last      = $athlete_id ? get_field( 'last_name', $athlete_id ) : '';

			// TEC date from _EventStartDate postmeta
			$raw_date  = get_post_meta( $meet_id, '_EventStartDate', true );
			$meet_date = $raw_date ? date( 'Y-m-d', strtotime( $raw_date ) ) : '';

			$results_by_season[ $season_id ][ $meet_id ][] = [
				'result_id'      => $result->ID,
				'athlete_id'     => $athlete_id,
				'athlete_name'   => trim( ( $preferred ?: $first ) . ' ' . $last ) ?: 'Unknown',
				'result_display' => get_field( 'result_display', $result->ID ),
				'place'          => get_field( 'place', $result->ID ),
				'meet_name'      => get_the_title( $meet_id ),
				'meet_date'      => $meet_date,
			];
		}

		// Sort each meet's results by place ascending (nulls last)
		foreach ( $results_by_season as $sid => &$meets ) {
			uasort( $meets, function( $a, $b ) {
				$da = $a[0]['meet_date'] ?? '';
				$db = $b[0]['meet_date'] ?? '';
				return strcmp( $da, $db ); // ascending by date
			} );
			foreach ( $meets as $mid => &$meet_results ) {
				usort( $meet_results, function( $a, $b ) {
					$pa = $a['place'] !== '' && $a['place'] !== null ? (int) $a['place'] : PHP_INT_MAX;
					$pb = $b['place'] !== '' && $b['place'] !== null ? (int) $b['place'] : PHP_INT_MAX;
					return $pa - $pb;
				} );
			}
			unset( $meet_results );
		}
		unset( $meets );

		// Sort seasons by start date descending (most recent first)
		uksort( $results_by_season, function( $a, $b ) use ( $season_meta ) {
			$da = $season_meta[ $a ]['start_date'] ?? '';
			$db = $season_meta[ $b ]['start_date'] ?? '';
			return strcmp( $db, $da );
		} );
	}
	wp_reset_postdata();

?>

<div class="tb-event">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: EVENT HEADER                                            ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-event-header">

		<h1 class="tb-event-name"><?php echo esc_html( $event_name ?: get_the_title() ); ?></h1>

		<div class="tb-event-meta">

			<?php if ( $sports && ! is_wp_error( $sports ) ) : ?>
				<p class="tb-event-sport">
					<?php
					$sport_links = [];
					foreach ( $sports as $sport ) {
						$sport_url = get_term_link( $sport );
						if ( ! is_wp_error( $sport_url ) ) {
							$sport_links[] = '<a href="' . esc_url( $sport_url ) . '">' . esc_html( $sport->name ) . '</a>';
						} else {
							$sport_links[] = esc_html( $sport->name );
						}
					}
					echo implode( ', ', $sport_links );
					?>
				</p>
			<?php endif; ?>

			<?php if ( $event_category ) : ?>
				<p class="tb-event-category"><?php echo esc_html( $event_category ); ?></p>
			<?php endif; ?>

			<?php if ( $distance_display ) : ?>
				<p class="tb-event-distance"><?php echo esc_html( $distance_display ); ?></p>
			<?php endif; ?>

			<?php if ( $measurement ) : ?>
				<p class="tb-event-measurement">
					Measured by: <?php echo esc_html( $measurement ); ?>
					<?php if ( $unit_display ) : ?>
						(<?php echo esc_html( $unit_display ); ?>)
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( $is_relay ) : ?>
				<p class="tb-event-relay">Relay event</p>
			<?php endif; ?>

		</div><!-- .tb-event-meta -->

	</section><!-- .tb-event-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: ALL-TIME RECORDS                                        ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-event-records">

		<h2>Records</h2>

		<?php if ( empty( $records ) ) : ?>
			<p class="tb-no-data">No records on file for this event.</p>
		<?php else : ?>
			<table class="tb-table">
				<thead>
					<tr>
						<th>Type</th>
						<th>Athlete</th>
						<th>Result</th>
						<th>Meet</th>
						<th>Date</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $records as $record ) : ?>
					<tr>
						<td><?php echo esc_html( $record['record_type'] ); ?></td>
						<td>
							<?php if ( $record['athlete_id'] ) : ?>
								<a href="<?php echo esc_url( get_permalink( $record['athlete_id'] ) ); ?>">
									<?php echo esc_html( $record['athlete_name'] ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $record['athlete_name'] ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $record['result_display'] ); ?></td>
						<td>
							<?php if ( $record['meet_id'] ) : ?>
								<a href="<?php echo esc_url( get_permalink( $record['meet_id'] ) ); ?>">
									<?php echo esc_html( $record['meet_name'] ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $record['meet_name'] ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $record['meet_date'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</section><!-- .tb-event-records -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 3: RESULTS HISTORY                                         ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-event-results">

		<h2>Results History</h2>

		<?php if ( empty( $results_by_season ) ) : ?>
			<p class="tb-no-data">No results on file for this event.</p>
		<?php else : ?>

			<?php foreach ( $results_by_season as $season_id => $meets ) :
				$season_info = $season_meta[ $season_id ] ?? [];
				$season_url  = get_permalink( $season_id );
			?>

			<div class="tb-results-season">
				<h3 class="tb-season-label">
					<?php if ( $season_url ) : ?>
						<a href="<?php echo esc_url( $season_url ); ?>">
							<?php echo esc_html( $season_info['title'] ?? '' ); ?>
						</a>
					<?php else : ?>
						<?php echo esc_html( $season_info['title'] ?? '' ); ?>
					<?php endif; ?>
				</h3>

				<?php foreach ( $meets as $meet_id => $meet_results ) :
					$first_result = $meet_results[0];
					$meet_date_display = $first_result['meet_date']
						? date_i18n( 'F j, Y', strtotime( $first_result['meet_date'] ) )
						: '';
				?>

				<div class="tb-results-meet">
					<h4 class="tb-meet-label">
						<a href="<?php echo esc_url( get_permalink( $meet_id ) ); ?>">
							<?php echo esc_html( $first_result['meet_name'] ); ?>
						</a>
						<?php if ( $meet_date_display ) : ?>
							<span class="tb-meet-date">(<?php echo esc_html( $meet_date_display ); ?>)</span>
						<?php endif; ?>
					</h4>

					<table class="tb-table">
						<thead>
							<tr>
								<th>Place</th>
								<th>Athlete</th>
								<th>Result</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $meet_results as $r ) : ?>
							<tr>
								<td><?php echo ( $r['place'] !== '' && $r['place'] !== null ) ? esc_html( $r['place'] ) : '—'; ?></td>
								<td>
									<?php if ( $r['athlete_id'] ) : ?>
										<a href="<?php echo esc_url( get_permalink( $r['athlete_id'] ) ); ?>">
											<?php echo esc_html( $r['athlete_name'] ); ?>
										</a>
									<?php else : ?>
										<?php echo esc_html( $r['athlete_name'] ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $r['result_display'] ?: '—' ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

				</div><!-- .tb-results-meet -->

				<?php endforeach; ?>

			</div><!-- .tb-results-season -->

			<?php endforeach; ?>

		<?php endif; ?>

	</section><!-- .tb-event-results -->

</div><!-- .tb-event -->

<?php
endwhile;

get_footer();