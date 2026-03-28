<?php
/**
 * Template: single-athlete.php
 * Displays a single Athlete public profile.
 *
 * Sections:
 *   1. Athlete header (name + photo)
 *   2. Basic info (sport, family, current grade/season)
 *   3. PR / SR Records
 *   4. Results history: Season → Meet → Results
 *
 * All field names reference acf-json/group_tb_athlete.json,
 * group_tb_athletic_result.json, group_tb_athletic_record.json,
 * group_tb_enrollment.json, and group_tb_athletic_meet.json.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$athlete_id = get_the_ID();

	// -------------------------------------------------------------------------
	// ATHLETE CORE FIELDS
	// -------------------------------------------------------------------------
	$first_name     = get_field( 'first_name', $athlete_id );
	$last_name      = get_field( 'last_name', $athlete_id );
	$preferred_name = get_field( 'preferred_name', $athlete_id );
	$family_id      = get_field( 'family', $athlete_id ); // returns post ID
	$display_name   = $preferred_name ?: $first_name;
	$full_name      = trim( $display_name . ' ' . $last_name );
	$photo_id       = get_post_thumbnail_id( $athlete_id );

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
				'key'   => 'athlete',
				'value' => $athlete_id,
				'compare' => '=',
			],
		],
	] );

	// Build a map of season_id => enrollment data
	$seasons_map = [];
	if ( $enrollment_query->have_posts() ) {
		foreach ( $enrollment_query->posts as $enrollment ) {
			$season_id = get_field( 'season', $enrollment->ID );
			if ( ! $season_id ) continue;

			$seasons_map[ $season_id ] = [
				'enrollment_id'      => $enrollment->ID,
				'grade'              => get_field( 'grade', $enrollment->ID ),
				'participation_type' => get_field( 'participation_type', $enrollment->ID ),
				'new_returning'      => get_field( 'new_returning', $enrollment->ID ),
				'season_title'       => get_field( 'season_title', $season_id ),
				'season_start'       => get_field( 'start_date', $season_id ),
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
	// -------------------------------------------------------------------------
	$results_query = new WP_Query( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'   => 'athlete',
				'value' => $athlete_id,
				'compare' => '=',
			],
		],
	] );

	// Group results: season_id => meet_id => [ results ]
	$results_grouped = [];
	if ( $results_query->have_posts() ) {
		foreach ( $results_query->posts as $result ) {
			$meet_id   = get_field( 'meet', $result->ID );
			if ( ! $meet_id ) continue;

			$season_id = get_field( 'season', $meet_id );
			if ( ! $season_id ) continue;

			$results_grouped[ $season_id ][ $meet_id ][] = [
				'result_id'      => $result->ID,
				'event_name'     => get_field( 'event_name', $result->ID ),
				'result_display' => get_field( 'result_display', $result->ID ),
				'place'          => get_field( 'place', $result->ID ),
				'meet_name'      => get_field( 'meet_name', $meet_id ),
				'meet_date'      => get_field( 'date', $meet_id ),
			];
		}
	}
	wp_reset_postdata();

	// -------------------------------------------------------------------------
	// RECORDS — pull PR/SR records for this athlete
	// -------------------------------------------------------------------------
	$records_query = new WP_Query( [
		'post_type'      => 'athletic_record',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'   => 'athlete',
				'value' => $athlete_id,
				'compare' => '=',
			],
		],
	] );

	$records = [];
	if ( $records_query->have_posts() ) {
		foreach ( $records_query->posts as $record ) {
			$linked_result_id = get_field( 'result', $record->ID );
			$linked_event_id  = get_field( 'event', $record->ID );

			$records[] = [
				'record_id'      => $record->ID,
				'record_type'    => get_field( 'record_type', $record->ID ),
				'event_name'     => $linked_event_id ? get_the_title( $linked_event_id ) : '—',
				'result_display' => $linked_result_id ? get_field( 'result_display', $linked_result_id ) : '—',
				'meet_id'        => $linked_result_id ? get_field( 'meet', $linked_result_id ) : null,
				'meet_name'      => $linked_result_id ? get_field( 'meet_name', get_field( 'meet', $linked_result_id ) ) : '—',
				'meet_date'      => $linked_result_id ? get_field( 'date', get_field( 'meet', $linked_result_id ) ) : '',
			];
		}
	}
	wp_reset_postdata();

?>

<div class="tb-athlete-profile">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: HEADER                                                  ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-athlete-header">

		<?php if ( $photo_id ) : ?>
			<div class="tb-athlete-photo">
				<?php echo wp_get_attachment_image( $photo_id, 'medium' ); ?>
			</div>
		<?php endif; ?>

		<div class="tb-athlete-headline">
			<h1 class="tb-athlete-name"><?php echo esc_html( $full_name ); ?></h1>

			<?php if ( $sports && ! is_wp_error( $sports ) ) : ?>
				<p class="tb-athlete-sport">
					<?php echo esc_html( implode( ', ', wp_list_pluck( $sports, 'name' ) ) ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $family_display ) : ?>
				<p class="tb-athlete-family">
					<a href="<?php echo esc_url( get_permalink( $family_id ) ); ?>">
						<?php echo esc_html( $family_display ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>

	</section><!-- .tb-athlete-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: BASIC INFO / SEASON CONTEXT                             ?>
	<?php // ----------------------------------------------------------------- ?>
	<?php if ( ! empty( $seasons_map ) ) : ?>
	<section class="tb-athlete-seasons">
		<h2>Season History</h2>
		<table class="tb-table">
			<thead>
				<tr>
					<th>Season</th>
					<th>Grade</th>
					<th>Status</th>
					<th>Participation</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $seasons_map as $season_id => $season_data ) : ?>
				<tr>
					<td>
							<a href="<?php echo esc_url( get_permalink( $season_id ) ); ?>">
								<?php echo esc_html( $season_data['season_title'] ); ?>
							</a>
						</td>
					<td><?php echo esc_html( $season_data['grade'] ?: '—' ); ?></td>
					<td><?php echo esc_html( $season_data['new_returning'] ?: '—' ); ?></td>
					<td><?php echo esc_html( $season_data['participation_type'] ?: '—' ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section><!-- .tb-athlete-seasons -->
	<?php endif; ?>


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 3: PR / SR RECORDS                                         ?>
	<?php // ----------------------------------------------------------------- ?>
	<?php if ( ! empty( $records ) ) : ?>
	<section class="tb-athlete-records">
		<h2>Personal Records</h2>
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
	</section><!-- .tb-athlete-records -->
	<?php endif; ?>


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 4: RESULTS HISTORY — Season → Meet → Results               ?>
	<?php // ----------------------------------------------------------------- ?>
	<?php if ( ! empty( $results_grouped ) ) : ?>
	<section class="tb-athlete-results">
		<h2>Results</h2>

		<?php foreach ( $results_grouped as $season_id => $meets ) :
			$season_label = isset( $seasons_map[ $season_id ] )
				? $seasons_map[ $season_id ]['season_title']
				: get_field( 'season_title', $season_id );
		?>

		<div class="tb-results-season">
			<h3 class="tb-season-label"><?php echo esc_html( $season_label ?: 'Season ' . $season_id ); ?></h3>

			<?php
			// Sort meets by date ascending within this season
			uasort( $meets, function( $a, $b ) {
				$date_a = $a[0]['meet_date'] ?? '';
				$date_b = $b[0]['meet_date'] ?? '';
				return strcmp( $date_a, $date_b );
			} );

			foreach ( $meets as $meet_id => $meet_results ) :
				$meet_name = $meet_results[0]['meet_name'] ?? 'Meet ' . $meet_id;
				$meet_date = $meet_results[0]['meet_date'] ?? '';
			?>

			<div class="tb-results-meet">
				<h4 class="tb-meet-label">
						<a href="<?php echo esc_url( get_permalink( $meet_id ) ); ?>">
							<?php echo esc_html( $meet_name ); ?>
						</a>
						<?php if ( $meet_date ) : ?>
							<span class="tb-meet-date"><?php echo esc_html( $meet_date ); ?></span>
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
							<td><?php echo esc_html( $r['event_name'] ); ?></td>
							<td><?php echo esc_html( $r['result_display'] ); ?></td>
							<td><?php echo $r['place'] ? esc_html( $r['place'] ) : '—'; ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

			</div><!-- .tb-results-meet -->

			<?php endforeach; ?>

		</div><!-- .tb-results-season -->

		<?php endforeach; ?>

	</section><!-- .tb-athlete-results -->
	<?php endif; ?>

	<?php if ( empty( $results_grouped ) && empty( $records ) ) : ?>
		<p class="tb-no-data">No results on record yet.</p>
	<?php endif; ?>

</div><!-- .tb-athlete-profile -->

<?php
endwhile;

get_footer();