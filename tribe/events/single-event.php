<?php
/**
 * Template: tribe/events/single-event.php
 * TEC theme override — single meet view for tribe_events in the athletic-meet category.
 *
 * This template overrides TEC's default single event view for ALL tribe_events.
 * A category guard at the top restricts the full meet layout to athletic-meet events.
 * Non-meet events (practice, team-event, community-run) get a minimal title fallback.
 *
 * Sections:
 *   1. Meet header (name, date, venue/location, season, results status badge)
 *   2. Results — grouped by event, sorted by place ascending
 *      Gated by results_status ACF field (Future | Pending | Available)
 *
 * Field references:
 *   group_tb_athletic_meet.json   — season (post object → ID), results_status (select)
 *   group_tb_athletic_result.json — athlete, meet, event_name, result_display,
 *                                   result_time_seconds, place
 *   group_tb_athlete.json         — names group (first_name, preferred_name, last_name)
 *
 * TEC field references (postmeta, not ACF):
 *   _EventStartDate     — meet start datetime (format: Y-m-d H:i:s)
 *   _EventVenueID       — linked tribe_venue post ID
 *   _VenueCity          — on the tribe_venue post
 *   _VenueStateProvince — on the tribe_venue post
 *
 * FIELD NAME NOTES:
 *   - Athlete name fields live inside the 'names' ACF group on the Athlete CPT.
 *     Read via: $names = get_field( 'names', $athlete_id ); $names['first_name']
 *     Direct get_field( 'first_name', $athlete_id ) returns NULL.
 *   - results_status is a top-level ACF field on tribe_events via group_tb_athletic_meet.
 *     No group wrapper — get_field( 'results_status', $meet_id ) works directly.
 *
 * CSS note:
 *   Add to templates.css:
 *     .tb-meet-results-list { --tb-cols: 1fr 3fr 2fr; }
 */

get_header();

while ( have_posts() ) :
	the_post();

	$meet_id = get_the_ID();

	// -------------------------------------------------------------------------
	// CATEGORY GUARD
	// This path overrides all tribe_events. Only render the full meet layout
	// for events in the athletic-meet category.
	// -------------------------------------------------------------------------
	$is_athletic_meet = has_term( 'athletic-meet', 'tribe_events_cat', $meet_id );

	if ( ! $is_athletic_meet ) :
?>
<div class="tb-single tb-tec-event">
	<section class="tb-single-header">
		<div class="tb-single-headline">
			<h1 class="tb-single-title"><?php the_title(); ?></h1>
		</div>
	</section>
</div>
<?php
	else :

	// -------------------------------------------------------------------------
	// MEET CORE FIELDS
	// -------------------------------------------------------------------------

	// Date — TEC postmeta, not ACF
	$raw_start    = get_post_meta( $meet_id, '_EventStartDate', true ); // Y-m-d H:i:s
	$date_display = $raw_start
		? date_i18n( 'F j, Y', strtotime( $raw_start ) )
		: '';

	// Venue — TEC postmeta chain: event → venue post → city/state
	$venue_id    = get_post_meta( $meet_id, '_EventVenueID', true );
	$venue_name  = $venue_id ? get_the_title( $venue_id ) : '';
	$venue_city  = $venue_id ? get_post_meta( $venue_id, '_VenueCity', true ) : '';
	$venue_state = $venue_id ? get_post_meta( $venue_id, '_VenueStateProvince', true ) : '';
	$loc_parts   = array_filter( [ $venue_name, $venue_city, $venue_state ] );
	$loc_display = implode( ', ', $loc_parts );

	// ACF fields from group_tb_athletic_meet (attached to tribe_events)
	$season_id      = get_field( 'season', $meet_id );          // athletic_season post ID
	$results_status = get_field( 'results_status', $meet_id );  // Future | Pending | Available

	$season_title = $season_id ? get_field( 'season_title', $season_id ) : '';
	$season_url   = $season_id ? get_permalink( $season_id ) : '';

	// -------------------------------------------------------------------------
	// RESULTS — query and group by event name
	// Only runs when results_status === 'Available'.
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
				$time_seconds   = get_field( 'result_time_seconds', $result->ID );

				// Athlete name — fields are inside the 'names' ACF group.
				// Direct get_field( 'first_name', $id ) returns NULL.
				$athlete_name = '';
				if ( $athlete_id ) {
					$names         = get_field( 'names', $athlete_id ) ?: [];
					$first         = $names['first_name']    ?? '';
					$preferred     = $names['preferred_name'] ?? '';
					$last          = $names['last_name']     ?? '';
					$athlete_name  = trim( ( $preferred ?: $first ) . ' ' . $last );
				}

				$event_key = $event_name ?: 'Unknown Event';

				$results_by_event[ $event_key ][] = [
					'result_id'      => $result->ID,
					'athlete_id'     => $athlete_id,
					'athlete_name'   => $athlete_name ?: 'Unknown Athlete',
					'result_display' => $result_display,
					'place'          => $place,
					'time_seconds'   => $time_seconds,
				];
			}

			// Sort each event group by place ascending (nulls last)
			foreach ( $results_by_event as $event_key => &$event_results ) {
				usort( $event_results, function( $a, $b ) {
					$pa = ( $a['place'] !== '' && $a['place'] !== null ) ? (int) $a['place'] : PHP_INT_MAX;
					$pb = ( $b['place'] !== '' && $b['place'] !== null ) ? (int) $b['place'] : PHP_INT_MAX;
					return $pa - $pb;
				} );
			}
			unset( $event_results );

			// Sort event groups alphabetically
			ksort( $results_by_event );
		}

		wp_reset_postdata();
	}

?>

<div class="tb-single tb-meet">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: MEET HEADER                                             ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-header tb-meet-header">

		<div class="tb-single-headline">

			<h1 class="tb-single-title"><?php echo esc_html( get_the_title() ); ?></h1>

			<div class="tb-single-meta">

				<?php if ( $date_display ) : ?>
					<span class="tb-meta-date"><?php echo esc_html( $date_display ); ?></span>
				<?php endif; ?>

				<?php if ( $loc_display ) : ?>
					<span class="tb-meta-location"><?php echo esc_html( $loc_display ); ?></span>
				<?php endif; ?>

				<?php if ( $season_title && $season_url ) : ?>
					<span class="tb-meta-season">
						<a href="<?php echo esc_url( $season_url ); ?>">
							<?php echo esc_html( $season_title ); ?>
						</a>
					</span>
				<?php endif; ?>

				<?php if ( $results_status ) : ?>
					<span class="tb-status tb-status--<?php echo esc_attr( strtolower( $results_status ) ); ?>">
						<?php echo esc_html( $results_status ); ?>
					</span>
				<?php endif; ?>

			</div><!-- .tb-single-meta -->

		</div><!-- .tb-single-headline -->

	</section><!-- .tb-single-header .tb-meet-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: RESULTS                                                 ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-section tb-meet-results">

		<h2>Results</h2>

		<?php if ( $results_status === 'Future' ) : ?>
			<p class="tb-results-notice">Results will be available after the meet.</p>

		<?php elseif ( $results_status === 'Pending' ) : ?>
			<p class="tb-results-notice">Results are being finalized. Check back soon.</p>

		<?php elseif ( $results_status === 'Available' && empty( $results_by_event ) ) : ?>
			<p class="tb-no-data">No results found for this meet.</p>

		<?php elseif ( ! empty( $results_by_event ) ) : ?>

			<?php foreach ( $results_by_event as $event_name => $event_results ) :
				$event_slug = sanitize_title( $event_name );
			?>
			<div class="tb-results-event" data-event="<?php echo esc_attr( $event_slug ); ?>">

				<h3 class="tb-event-label"><?php echo esc_html( $event_name ); ?></h3>

				<div class="tb-list-wrap tb-meet-results-list-wrap">
					<div class="tb-list-header">
						<span class="tb-col">Place</span>
						<span class="tb-col">Athlete</span>
						<span class="tb-col">Result</span>
					</div>
					<ul class="tb-list tb-meet-results-list">
						<?php foreach ( $event_results as $r ) :
							$place_attr   = ( $r['place'] !== '' && $r['place'] !== null ) ? (string) $r['place'] : '';
							$seconds_attr = is_numeric( $r['time_seconds'] ) ? (string) $r['time_seconds'] : '';
						?>
						<li class="tb-list-row"
							data-place="<?php echo esc_attr( $place_attr ); ?>"
							data-result-seconds="<?php echo esc_attr( $seconds_attr ); ?>"
							data-athlete-id="<?php echo esc_attr( (string) ( $r['athlete_id'] ?: '' ) ); ?>">
							<?php if ( $r['athlete_id'] ) : ?>
								<a href="<?php echo esc_url( get_permalink( $r['athlete_id'] ) ); ?>" class="tb-list-link">
							<?php else : ?>
								<div class="tb-list-link">
							<?php endif; ?>
									<span class="tb-col"><?php echo $place_attr !== '' ? esc_html( $place_attr ) : '—'; ?></span>
									<span class="tb-col"><?php echo esc_html( $r['athlete_name'] ); ?></span>
									<span class="tb-col"><?php echo esc_html( $r['result_display'] ?: '—' ); ?></span>
							<?php if ( $r['athlete_id'] ) : ?>
								</a>
							<?php else : ?>
								</div>
							<?php endif; ?>
						</li>
						<?php endforeach; ?>
					</ul>
				</div><!-- .tb-list-wrap .tb-meet-results-list-wrap -->

			</div><!-- .tb-results-event -->
			<?php endforeach; ?>

		<?php endif; ?>

	</section><!-- .tb-single-section .tb-meet-results -->

</div><!-- .tb-single .tb-meet -->

<?php
	endif; // $is_athletic_meet

endwhile;

get_footer();