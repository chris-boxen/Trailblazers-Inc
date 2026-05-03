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
	$names        = get_field( 'names',        $athlete_id );
	$demographics = get_field( 'demographics', $athlete_id );
	$status_data  = get_field( 'status',       $athlete_id );
	
	$first_name      = $names['first_name']        ?? '';
	$last_name       = $names['last_name']         ?? '';
	$preferred_name  = $names['preferred_name']    ?? '';
	$family_id       = get_field( 'family',          $athlete_id ); // top-level
	$milesplit_id    = get_field( 'milesplit_id',     $athlete_id ); // top-level
	$athletic_net_id = $demographics['athletic_net_id'] ?? '';
	$photo_id        = get_field( 'featured_image',  $athlete_id ); // top-level
	
	$display_name   = $preferred_name ?: $first_name;
	$full_name      = trim( $display_name . ' ' . $last_name );
	$family_display = $family_id ? get_the_title( $family_id ) : '';

	$sports = get_the_terms( $athlete_id, 'sport' );

	// -------------------------------------------------------------------------
	// SEASONS MAP — build via Enrollment
	// Collect enrollments for this athlete; for each, pull season flags.
	// -------------------------------------------------------------------------
	$enrollments_query = new WP_Query( [
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

	$seasons_map = []; // season_id => season metadata + flags
	if ( $enrollments_query->have_posts() ) {
		foreach ( $enrollments_query->posts as $enrollment ) {
			$season_id = get_field( 'season', $enrollment->ID );
			if ( ! $season_id || isset( $seasons_map[ $season_id ] ) ) continue;

			$seasons_map[ $season_id ] = [
				'season_title'              => get_field( 'season_title', $season_id ),
				'results_enabled'           => get_field( 'results_enabled', $season_id ),
				'results_unavailable_message' => get_field( 'results_unavailable_message', $season_id ),
				'link_milesplit'            => get_field( 'link_milesplit', $season_id ),
				'link_athletic_net'         => get_field( 'link_athletic_net', $season_id ),
				'start_date'                => get_field( 'start_date', $season_id ),
			];
		}
	}
	wp_reset_postdata();

	// Sort seasons by start_date descending (most recent first)
	uasort( $seasons_map, function( $a, $b ) {
		$ts_a = $a['start_date'] ? strtotime( $a['start_date'] ) : 0;
		$ts_b = $b['start_date'] ? strtotime( $b['start_date'] ) : 0;
		return $ts_b - $ts_a;
	} );

	// -------------------------------------------------------------------------
	// RESULTS — query all results for this athlete, group by season → meet
	// -------------------------------------------------------------------------
	$results_grouped = []; // season_id => [ meet_id => [ result rows ] ]

	if ( ! empty( $seasons_map ) ) {
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
	}

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

<div class="tb-single tb-athlete">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: ATHLETE HEADER                                          ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-header tb-athlete-header">

		<div class="tb-single-headline tb-athlete-headline">

			<h1 class="tb-single-title tb-athlete-name">
				<?php echo esc_html( $full_name ); ?>
			</h1>

			<div class="tb-single-meta tb-athlete-meta">

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

				<?php if ( $family_display ) : ?>
					<span class="tb-meta-family"><?php echo esc_html( $family_display ); ?></span>
				<?php endif; ?>

			</div><!-- .tb-single-meta -->

		</div><!-- .tb-single-headline -->

		<div class="tb-single-header-secondary-section">

			<?php if ( $photo_id ) : ?>
				<div class="tb-single-image tb-athlete-image">
					<?php echo wp_get_attachment_image( $photo_id, 'medium' ); ?>
				</div>
			<?php endif; ?>

		</div><!-- .tb-single-header-secondary-section -->

	</section><!-- .tb-single-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: RECORDS                                                 ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-section tb-athlete-records">

		<h2>Personal Records</h2>

		<?php if ( empty( $records ) ) : ?>
			<p class="tb-no-data">No records on file.</p>
		<?php else : ?>
			<div class="tb-list-wrap">
				<ul class="tb-list tb-records-list">

					<li class="tb-list-header">
						<span class="tb-col">Type</span>
						<span class="tb-col">Event</span>
						<span class="tb-col">Result</span>
						<span class="tb-col">Meet</span>
						<span class="tb-col">Date</span>
					</li>

					<?php foreach ( $records as $record ) : ?>
					<li class="tb-list-row">
						<div class="tb-list-link">
							<span class="tb-col"><?php echo esc_html( $record['record_type'] ?: '—' ); ?></span>
							<span class="tb-col"><?php echo esc_html( $record['event_name'] ); ?></span>
							<span class="tb-col"><?php echo esc_html( $record['result_display'] ); ?></span>
							<span class="tb-col">
								<?php if ( $record['meet_id'] ) : ?>
									<a href="<?php echo esc_url( get_permalink( $record['meet_id'] ) ); ?>" class="tb-link-action">
										<?php echo esc_html( $record['meet_name'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $record['meet_name'] ); ?>
								<?php endif; ?>
							</span>
							<span class="tb-col"><?php echo esc_html( $record['meet_date'] ); ?></span>
						</div>
					</li>
					<?php endforeach; ?>

				</ul>
			</div><!-- .tb-list-wrap -->
		<?php endif; ?>

	</section><!-- .tb-single-section .tb-athlete-records -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 3: RESULTS BY SEASON                                       ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-section tb-athlete-results">

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

					<?php if ( $season_data['results_unavailable_message'] ) : ?>
						<p class="tb-section-note"><?php echo wp_kses_post( $season_data['results_unavailable_message'] ); ?></p>
					<?php else : ?>
						<p class="tb-no-data">Results not available for this season.</p>
					<?php endif; ?>

				<?php elseif ( empty( $results_grouped[ $season_id ] ) ) : ?>

					<p class="tb-no-data">No results recorded for this season.</p>

				<?php else : ?>

					<?php foreach ( $results_grouped[ $season_id ] as $meet_id => $meet_results ) :

						$first_result = reset( $meet_results );
						$date_display = ( ! empty( $first_result['meet_date'] ) )
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

						<div class="tb-list-wrap">
							<ul class="tb-list tb-results-list">

								<li class="tb-list-header">
									<span class="tb-col">Event</span>
									<span class="tb-col">Result</span>
									<span class="tb-col">Place</span>
								</li>

								<?php foreach ( $meet_results as $r ) : ?>
								<li class="tb-list-row">
									<div class="tb-list-link">
										<span class="tb-col"><?php echo esc_html( $r['event_name'] ?: '—' ); ?></span>
										<span class="tb-col"><?php echo esc_html( $r['result_display'] ?: '—' ); ?></span>
										<span class="tb-col">
											<?php echo ( $r['place'] !== '' && $r['place'] !== null )
												? esc_html( $r['place'] )
												: '—'; ?>
										</span>
									</div>
								</li>
								<?php endforeach; ?>

							</ul>
						</div><!-- .tb-list-wrap -->

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

				<?php endif; // results_enabled ?>

			</div><!-- .tb-results-season -->

			<?php endforeach; ?>

		<?php endif; ?>

	</section><!-- .tb-single-section .tb-athlete-results -->

</div><!-- .tb-single .tb-athlete -->

<?php
endwhile;

get_footer();