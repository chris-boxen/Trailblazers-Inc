<?php
/**
 * Template: single-athlete.php
 * Displays a single Athlete public profile.
 *
 * Sections:
 *   1. Athlete header (name, photo, sport, family)
 *   2. PR / SR Records
 *   3. Results history: Season → Meet → Results
 *      Gated per season by results_enabled flag on Athletic Season.
 *      When results_enabled = false, shows results_unavailable_message instead.
 *      When link_milesplit / link_athletic_net = true and IDs exist, shows external links.
 *
 * Field references:
 *   group_tb_athlete.json         — athlete fields; milesplit_id, athletic_net_id
 *   group_tb_athletic_result.json — result fields
 *   group_tb_athletic_record.json — record fields; Athletic Event link field name: 'event'
 *   group_tb_enrollment.json      — enrollment fields
 *   group_tb_athletic_season.json — season fields + flags (customize_data group, direct query)
 *
 * TEC field references (postmeta, not ACF):
 *   _EventStartDate — meet start datetime (format: Y-m-d H:i:s)
 *
 * FIELD NAME NOTE:
 *   On athletic_result: the Athletic Event relationship field is named 'athletic_event'
 *   On athletic_record: the Athletic Event relationship field is named 'event'
 */

get_header();

while ( have_posts() ) :
	the_post();

	$athlete_id = get_the_ID();

	// -------------------------------------------------------------------------
	// ATHLETE CORE FIELDS
	// -------------------------------------------------------------------------
	$first_name      = get_field( 'first_name', $athlete_id );
	$last_name       = get_field( 'last_name', $athlete_id );
	$preferred_name  = get_field( 'preferred_name', $athlete_id );
	$family_id       = get_field( 'family', $athlete_id ); // returns post ID
	$milesplit_id    = get_field( 'milesplit_id', $athlete_id );
	$athletic_net_id = get_field( 'athletic_net_id', $athlete_id );
	$display_name    = $preferred_name ?: $first_name;
	$full_name       = trim( $display_name . ' ' . $last_name );
	$photo_id        = get_post_thumbnail_id( $athlete_id );

	// Sport taxonomy terms
	$sports = get_the_terms( $athlete_id, 'sport' );

	// Family display name
	$family_display = '';
	if ( $family_id ) {
		$family_display = get_field( 'family_display_name', $family_id );
	}

	// -------------------------------------------------------------------------
	// ENROLLMENTS — pull all enrollments for this athlete, ordered by season
	// Used to build season context for results grouping
	// -------------------------------------------------------------------------
	$enrollment_query = new WP_Query( [
		'post_type'      => 'enrollment',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'     => 'athlete',
				'value'   => $athlete_id,
				'compare' => '=',
			],
		],
	] );

	// Build a map of season_id => enrollment + season data
	$seasons_map = [];
	if ( $enrollment_query->have_posts() ) {
		foreach ( $enrollment_query->posts as $enrollment ) {
			$season_id = get_field( 'season', $enrollment->ID );
			if ( ! $season_id ) continue;

			$seasons_map[ $season_id ] = [
				'enrollment_id'              => $enrollment->ID,
				'grade'                      => get_field( 'grade', $enrollment->ID ),
				'participation_type'         => get_field( 'participation_type', $enrollment->ID ),
				'new_returning'              => get_field( 'new_returning', $enrollment->ID ),
				'season_title'               => get_field( 'season_title', $season_id ),
				'season_start'               => get_field( 'start_date', $season_id ),
				// Season feature flags (sub-fields of customize_data group — directly queryable)
				'results_enabled'            => get_field( 'results_enabled', $season_id ),
				'results_unavailable_message'=> get_field( 'results_unavailable_message', $season_id ),
				'link_milesplit'             => get_field( 'link_milesplit', $season_id ),
				'link_athletic_net'          => get_field( 'link_athletic_net', $season_id ),
			];
		}
		// Sort seasons by start date descending (most recent first)
		uasort( $seasons_map, function( $a, $b ) {
			return strcmp( $b['season_start'], $a['season_start'] );
		} );
	}
	wp_reset_postdata();

	// -------------------------------------------------------------------------
	// RESULTS — pull all results for this athlete
	// TEC date from _EventStartDate postmeta
	// -------------------------------------------------------------------------
	$results_query = new WP_Query( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'     => 'athlete',
				'value'   => $athlete_id,
				'compare' => '=',
			],
		],
	] );

	// Group results: season_id => meet_id => [ results ]
	$results_grouped = [];
	if ( $results_query->have_posts() ) {
		foreach ( $results_query->posts as $result ) {
			$meet_id = get_field( 'meet', $result->ID );
			if ( ! $meet_id ) continue;

			$season_id = get_field( 'season', $meet_id );
			if ( ! $season_id ) continue;

			// TEC date from _EventStartDate postmeta
			$raw_date  = get_post_meta( $meet_id, '_EventStartDate', true );
			$meet_date = $raw_date ? date( 'Y-m-d', strtotime( $raw_date ) ) : '';

			$results_grouped[ $season_id ][ $meet_id ][] = [
				'result_id'      => $result->ID,
				'event_name'     => get_field( 'event_name', $result->ID ),
				'result_display' => get_field( 'result_display', $result->ID ),
				'place'          => get_field( 'place', $result->ID ),
				'meet_name'      => get_the_title( $meet_id ),
				'meet_date'      => $meet_date,
			];
		}
	}
	wp_reset_postdata();

	// -------------------------------------------------------------------------
	// RECORDS — pull PR/SR records for this athlete
	// Note: athletic_record uses field name 'event' for the Athletic Event link
	// -------------------------------------------------------------------------
	$records_query = new WP_Query( [
		'post_type'      => 'athletic_record',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'     => 'athlete',
				'value'   => $athlete_id,
				'compare' => '=',
			],
		],
	] );

	$records = [];
	if ( $records_query->have_posts() ) {
		foreach ( $records_query->posts as $record ) {
			$linked_result_id = get_field( 'result', $record->ID );
			$linked_event_id  = get_field( 'event', $record->ID ); // 'event' on athletic_record

			$meet_id   = $linked_result_id ? get_field( 'meet', $linked_result_id ) : null;
			$raw_date  = $meet_id ? get_post_meta( $meet_id, '_EventStartDate', true ) : '';
			$meet_date = $raw_date ? date( 'Y-m-d', strtotime( $raw_date ) ) : '';

			$records[] = [
				'record_id'      => $record->ID,
				'record_type'    => get_field( 'record_type', $record->ID ),
				'event_name'     => $linked_event_id ? get_the_title( $linked_event_id ) : '—',
				'result_display' => $linked_result_id ? get_field( 'result_display', $linked_result_id ) : '—',
				'meet_id'        => $meet_id,
				'meet_name'      => $meet_id ? get_the_title( $meet_id ) : '—',
				'meet_date'      => $meet_date ? date_i18n( 'F j, Y', strtotime( $meet_date ) ) : '—',
			];
		}
	}
	wp_reset_postdata();

?>

<div class="tb-athlete">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: ATHLETE HEADER                                          ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-athlete-header">

		<?php if ( $photo_id ) : ?>
			<div class="tb-athlete-photo">
				<?php echo wp_get_attachment_image( $photo_id, 'medium' ); ?>
			</div>
		<?php endif; ?>

		<div class="tb-athlete-identity">

			<h1 class="tb-athlete-name"><?php echo esc_html( $full_name ); ?></h1>

			<?php if ( $sports && ! is_wp_error( $sports ) ) : ?>
				<p class="tb-athlete-sport">
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

			<?php if ( $family_display ) : ?>
				<p class="tb-athlete-family"><?php echo esc_html( $family_display ); ?></p>
			<?php endif; ?>

		</div><!-- .tb-athlete-identity -->

	</section><!-- .tb-athlete-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: RECORDS                                                 ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-athlete-records">

		<h2>Personal Records</h2>

		<?php if ( empty( $records ) ) : ?>
			<p class="tb-no-data">No records on file.</p>
		<?php else : ?>
			<table class="tb-table">
				<thead>
					<tr>
						<th>Type</th>
						<th>Event</th>
						<th>Result</th>
						<th>Meet</th>
						<th>Date</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $records as $record ) : ?>
					<tr>
						<td><?php echo esc_html( $record['record_type'] ); ?></td>
						<td><?php echo esc_html( $record['event_name'] ); ?></td>
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

	</section><!-- .tb-athlete-records -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 3: RESULTS BY SEASON                                       ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-athlete-results">

		<h2>Results</h2>

		<?php if ( empty( $seasons_map ) ) : ?>
			<p class="tb-no-data">No seasons on record.</p>
		<?php else : ?>

			<?php foreach ( $seasons_map as $season_id => $season_data ) :

				$season_url   = get_permalink( $season_id );
				$season_title = $season_data['season_title'] ?: get_the_title( $season_id );

			?>
			<div class="tb-results-season">

				<h3 class="tb-season-label">
					<?php if ( $season_url ) : ?>
						<a href="<?php echo esc_url( $season_url ); ?>"><?php echo esc_html( $season_title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $season_title ); ?>
					<?php endif; ?>
				</h3>

				<?php if ( ! $season_data['results_enabled'] ) : ?>

					<?php // Results display is disabled for this season ?>
					<div class="tb-results-unavailable">
						<?php if ( ! empty( $season_data['results_unavailable_message'] ) ) : ?>
							<?php echo wp_kses_post( $season_data['results_unavailable_message'] ); ?>
						<?php else : ?>
							<p>Results for this season are not available on this site.</p>
						<?php endif; ?>

						<?php // External links when IDs exist and season flags are on ?>
						<?php if ( $season_data['link_milesplit'] && $milesplit_id ) : ?>
							<p class="tb-external-link">
								<a href="https://www.milesplit.com/athletes/view/<?php echo esc_attr( $milesplit_id ); ?>"
								   target="_blank" rel="noopener noreferrer">
									View on Milesplit
								</a>
							</p>
						<?php endif; ?>

						<?php if ( $season_data['link_athletic_net'] && $athletic_net_id ) : ?>
							<p class="tb-external-link">
								<a href="https://www.athletic.net/TrackAndField/Athlete.aspx?AID=<?php echo esc_attr( $athletic_net_id ); ?>"
								   target="_blank" rel="noopener noreferrer">
									View on Athletic.net
								</a>
							</p>
						<?php endif; ?>
					</div><!-- .tb-results-unavailable -->

				<?php elseif ( empty( $results_grouped[ $season_id ] ) ) : ?>

					<p class="tb-no-data">No results recorded for this season.</p>

					<?php // Still show external links if enabled and IDs exist ?>
					<?php if ( $season_data['link_milesplit'] && $milesplit_id ) : ?>
						<p class="tb-external-link">
							<a href="https://www.milesplit.com/athletes/view/<?php echo esc_attr( $milesplit_id ); ?>"
							   target="_blank" rel="noopener noreferrer">
								View on Milesplit
							</a>
						</p>
					<?php endif; ?>

					<?php if ( $season_data['link_athletic_net'] && $athletic_net_id ) : ?>
						<p class="tb-external-link">
							<a href="https://www.athletic.net/TrackAndField/Athlete.aspx?AID=<?php echo esc_attr( $athletic_net_id ); ?>"
							   target="_blank" rel="noopener noreferrer">
								View on Athletic.net
							</a>
						</p>
					<?php endif; ?>

				<?php else : ?>

					<?php foreach ( $results_grouped[ $season_id ] as $meet_id => $meet_results ) :
						$first_result  = $meet_results[0];
						$date_display  = $first_result['meet_date']
							? date_i18n( 'F j, Y', strtotime( $first_result['meet_date'] ) )
							: '';
					?>

					<div class="tb-results-meet">

						<h4 class="tb-meet-label">
							<a href="<?php echo esc_url( get_permalink( $meet_id ) ); ?>">
								<?php echo esc_html( $first_result['meet_name'] ); ?>
							</a>
							<?php if ( $date_display ) : ?>
								<span class="tb-meet-date">(<?php echo esc_html( $date_display ); ?>)</span>
							<?php endif; ?>
						</h4>

						<table class="tb-table">
							<thead>
								<tr>
									<th>Event</th>
									<th>Result</th>
									<th>Place</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $meet_results as $r ) : ?>
								<tr>
									<td><?php echo esc_html( $r['event_name'] ?: '—' ); ?></td>
									<td><?php echo esc_html( $r['result_display'] ?: '—' ); ?></td>
									<td><?php echo ( $r['place'] !== '' && $r['place'] !== null ) ? esc_html( $r['place'] ) : '—'; ?></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

					</div><!-- .tb-results-meet -->

					<?php endforeach; ?>

					<?php // External links at the bottom of results-enabled seasons ?>
					<?php if ( $season_data['link_milesplit'] && $milesplit_id ) : ?>
						<p class="tb-external-link">
							<a href="https://www.milesplit.com/athletes/view/<?php echo esc_attr( $milesplit_id ); ?>"
							   target="_blank" rel="noopener noreferrer">
								Full profile on Milesplit
							</a>
						</p>
					<?php endif; ?>

					<?php if ( $season_data['link_athletic_net'] && $athletic_net_id ) : ?>
						<p class="tb-external-link">
							<a href="https://www.athletic.net/TrackAndField/Athlete.aspx?AID=<?php echo esc_attr( $athletic_net_id ); ?>"
							   target="_blank" rel="noopener noreferrer">
								Full profile on Athletic.net
							</a>
						</p>
					<?php endif; ?>

				<?php endif; ?>

			</div><!-- .tb-results-season -->

			<?php endforeach; ?>

		<?php endif; ?>

	</section><!-- .tb-athlete-results -->

</div><!-- .tb-athlete -->

<?php
endwhile;

get_footer();