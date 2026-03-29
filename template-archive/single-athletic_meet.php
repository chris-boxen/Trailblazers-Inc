<?php
/**
 * Template: single-athletic_meet.php
 * Displays a single Athletic Meet page.
 *
 * Sections:
 *   1. Meet header (name, date, location, season, status)
 *   2. Results — grouped by event, sorted by place
 *      Gated by results_status field
 *
 * Field references:
 *   group_tb_athletic_meet.json — meet fields
 *   group_tb_athletic_result.json — result fields
 *   group_tb_athlete.json — athlete name fields
 */

get_header();

while ( have_posts() ) :
	the_post();

	$meet_id = get_the_ID();

	// -------------------------------------------------------------------------
	// MEET CORE FIELDS
	// -------------------------------------------------------------------------
	$meet_name      = get_field( 'meet_name', $meet_id );
	$meet_date      = get_field( 'date', $meet_id );         // returns Y-m-d
	$venue          = get_field( 'venue', $meet_id );
	$city           = get_field( 'city', $meet_id );
	$state          = get_field( 'state', $meet_id );
	$directions     = get_field( 'directions', $meet_id );   // link field → array
	$status         = get_field( 'status', $meet_id );       // Upcoming | Completed | Cancelled
	$results_status = get_field( 'results_status', $meet_id ); // Future | Pending | Available
	$season_id      = get_field( 'season', $meet_id );       // post ID

	// Format date for display
	$date_display = $meet_date
		? date_i18n( 'F j, Y', strtotime( $meet_date ) )
		: '';

	// Build location string
	$location_parts = array_filter( [ $venue, $city, $state ] );
	$location       = implode( ', ', $location_parts );

	// Season label + permalink
	$season_title = $season_id ? get_field( 'season_title', $season_id ) : '';
	$season_url   = $season_id ? get_permalink( $season_id ) : '';

	// -------------------------------------------------------------------------
	// RESULTS — only query if results are available
	// -------------------------------------------------------------------------
	$results_by_event = [];

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
				$result_display = get_field( 'result_display', $result->ID );
				$place          = get_field( 'place', $result->ID );

				// Athlete name
				$first      = $athlete_id ? get_field( 'first_name', $athlete_id ) : '';
				$preferred  = $athlete_id ? get_field( 'preferred_name', $athlete_id ) : '';
				$last       = $athlete_id ? get_field( 'last_name', $athlete_id ) : '';
				$display    = trim( ( $preferred ?: $first ) . ' ' . $last );

				$event_key = $event_name ?: 'Unknown Event';

				$results_by_event[ $event_key ][] = [
					'result_id'      => $result->ID,
					'athlete_id'     => $athlete_id,
					'athlete_name'   => $display ?: 'Unknown Athlete',
					'result_display' => $result_display,
					'place'          => $place,
					// Normalized sort field — time-based events
					'time_seconds'   => get_field( 'result_time_seconds', $result->ID ),
				];
			}

			// Sort each event group by place ascending (nulls last)
			foreach ( $results_by_event as $event_key => &$event_results ) {
				usort( $event_results, function( $a, $b ) {
					$pa = $a['place'] !== '' && $a['place'] !== null ? (int) $a['place'] : PHP_INT_MAX;
					$pb = $b['place'] !== '' && $b['place'] !== null ? (int) $b['place'] : PHP_INT_MAX;
					return $pa - $pb;
				} );
			}
			unset( $event_results );

			// Sort events alphabetically
			ksort( $results_by_event );
		}

		wp_reset_postdata();
	}

?>

<div class="tb-meet">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: MEET HEADER                                             ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-meet-header">

		<h1 class="tb-meet-name"><?php echo esc_html( $meet_name ?: get_the_title() ); ?></h1>

		<div class="tb-meet-meta">

			<?php if ( $date_display ) : ?>
				<p class="tb-meet-date"><?php echo esc_html( $date_display ); ?></p>
			<?php endif; ?>

			<?php if ( $location ) : ?>
				<p class="tb-meet-location">
					<?php echo esc_html( $location ); ?>
					<?php if ( ! empty( $directions['url'] ) ) : ?>
						&mdash;
						<a href="<?php echo esc_url( $directions['url'] ); ?>"
						   target="_blank"
						   rel="noopener noreferrer">
							<?php echo esc_html( $directions['title'] ?: 'Directions' ); ?>
						</a>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( $season_title && $season_url ) : ?>
				<p class="tb-meet-season">
					<a href="<?php echo esc_url( $season_url ); ?>">
						<?php echo esc_html( $season_title ); ?>
					</a>
				</p>
			<?php endif; ?>

			<?php if ( $status ) : ?>
				<p class="tb-meet-status tb-status--<?php echo esc_attr( strtolower( $status ) ); ?>">
					<?php echo esc_html( $status ); ?>
				</p>
			<?php endif; ?>

		</div><!-- .tb-meet-meta -->

	</section><!-- .tb-meet-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: RESULTS                                                 ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-meet-results">

		<h2>Results</h2>

		<?php if ( $results_status === 'Future' ) : ?>
			<p class="tb-results-notice">Results will be available after the meet.</p>

		<?php elseif ( $results_status === 'Pending' ) : ?>
			<p class="tb-results-notice">Results are being finalized. Check back soon.</p>

		<?php elseif ( $results_status === 'Available' && empty( $results_by_event ) ) : ?>
			<p class="tb-results-notice">No results found for this meet.</p>

		<?php elseif ( $results_status === 'Available' && ! empty( $results_by_event ) ) : ?>

			<?php foreach ( $results_by_event as $event_name => $event_results ) : ?>

			<div class="tb-results-event">
				<h3 class="tb-event-label"><?php echo esc_html( $event_name ); ?></h3>

				<table class="tb-table">
					<thead>
						<tr>
							<th>Place</th>
							<th>Athlete</th>
							<th>Result</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $event_results as $r ) : ?>
						<tr>
							<td><?php echo $r['place'] !== '' && $r['place'] !== null ? esc_html( $r['place'] ) : '—'; ?></td>
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

			</div><!-- .tb-results-event -->

			<?php endforeach; ?>

		<?php else : ?>
			<p class="tb-results-notice">Results status unavailable.</p>
		<?php endif; ?>

	</section><!-- .tb-meet-results -->

</div><!-- .tb-meet -->

<?php
endwhile;

get_footer();