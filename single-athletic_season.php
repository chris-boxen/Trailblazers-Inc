<?php
/**
 * Template: single-athletic_season.php
 * Displays a single Athletic Season page.
 *
 * Sections:
 *   1. Season header (title, sport, dates, status, description)
 *   2. Meets — queried from Athletic Meet where season = this post
 *   3. Athlete roster — queried from Enrollment where season = this post
 *
 * Field references:
 *   group_tb_athletic_season.json — season fields
 *   group_tb_athletic_meet.json   — meet fields (queried)
 *   group_tb_enrollment.json      — enrollment fields (queried)
 *   group_tb_athlete.json         — athlete name fields (queried via enrollment)
 */

get_header();

while ( have_posts() ) :
	the_post();

	$season_id = get_the_ID();

	// -------------------------------------------------------------------------
	// SEASON CORE FIELDS
	// -------------------------------------------------------------------------
	$season_title    = get_field( 'season_title', $season_id );
	$description     = get_field( 'description', $season_id );
	$start_date      = get_field( 'start_date', $season_id );   // Y-m-d
	$end_date        = get_field( 'end_date', $season_id );     // Y-m-d
	$year            = get_field( 'year', $season_id );
	$timeline_status = get_field( 'timeline_status', $season_id ); // Past | Current | Future
	$handbook        = get_field( 'handbook', $season_id );     // link field → array
	$featured_image  = get_field( 'featured_image', $season_id ); // image ID

	// Sport taxonomy terms
	$sports = get_the_terms( $season_id, 'sport' );

	// Format dates
	$start_display = $start_date ? date_i18n( 'F j, Y', strtotime( $start_date ) ) : '';
	$end_display   = $end_date   ? date_i18n( 'F j, Y', strtotime( $end_date ) )   : '';

	// -------------------------------------------------------------------------
	// COACHES — read coach_roster repeater stored on the season post
	// -------------------------------------------------------------------------
	$coaches = [];
	$coach_roster = get_field( 'coach_roster', $season_id );

	if ( $coach_roster ) {
		foreach ( $coach_roster as $row ) {
			$coach_id = $row['coach'] ?? null;
			if ( ! $coach_id ) continue;

			$first     = get_field( 'first_name', $coach_id );
			$last      = get_field( 'last_name', $coach_id );
			$title     = get_field( 'preferred_title', $coach_id );
			$full_name = trim( $first . ' ' . $last );

			$coaches[] = [
				'coach_id' => $coach_id,
				'name'     => $full_name,
				'title'    => $title,
				'role'     => $row['coach_role'] ?? '',
			];
		}
	}

	// -------------------------------------------------------------------------
	// MEETS — query all meets linked to this season, ordered by date ascending
	// -------------------------------------------------------------------------
	$meets_query = new WP_Query( [
		'post_type'      => 'athletic_meet',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_key'       => 'date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => [
			[
				'key'     => 'season',
				'value'   => $season_id,
				'compare' => '=',
			],
		],
	] );

	$meets = [];
	if ( $meets_query->have_posts() ) {
		foreach ( $meets_query->posts as $meet ) {
			$meet_date   = get_field( 'date', $meet->ID );
			$meet_status = get_field( 'status', $meet->ID );
			$meets[] = [
				'meet_id'    => $meet->ID,
				'meet_name'  => get_field( 'meet_name', $meet->ID ) ?: get_the_title( $meet->ID ),
				'meet_date'  => $meet_date ? date_i18n( 'F j, Y', strtotime( $meet_date ) ) : '',
				'city'       => get_field( 'city', $meet->ID ),
				'state'      => get_field( 'state', $meet->ID ),
				'status'     => $meet_status,
				'results_status' => get_field( 'results_status', $meet->ID ),
			];
		}
	}
	wp_reset_postdata();

	// -------------------------------------------------------------------------
	// ATHLETE ROSTER — query enrollments for this season
	// -------------------------------------------------------------------------
	$enrollment_query = new WP_Query( [
		'post_type'      => 'enrollment',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'     => 'season',
				'value'   => $season_id,
				'compare' => '=',
			],
		],
	] );

	$athletes = [];
	if ( $enrollment_query->have_posts() ) {
		foreach ( $enrollment_query->posts as $enrollment ) {
			$athlete_id = get_field( 'athlete', $enrollment->ID );
			if ( ! $athlete_id ) continue;

			$first     = get_field( 'first_name', $athlete_id );
			$preferred = get_field( 'preferred_name', $athlete_id );
			$last      = get_field( 'last_name', $athlete_id );

			$athletes[] = [
				'athlete_id'         => $athlete_id,
				'name'               => trim( ( $preferred ?: $first ) . ' ' . $last ),
				'grade'              => get_field( 'grade', $enrollment->ID ),
				'participation_type' => get_field( 'participation_type', $enrollment->ID ),
			];
		}
		// Sort roster alphabetically by last name
		usort( $athletes, function( $a, $b ) {
			return strcmp( $a['name'], $b['name'] );
		} );
	}
	wp_reset_postdata();

?>

<div class="tb-season">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: SEASON HEADER                                           ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-season-header">

		<?php if ( $featured_image ) : ?>
			<div class="tb-season-image">
				<?php echo wp_get_attachment_image( $featured_image, 'large' ); ?>
			</div>
		<?php endif; ?>

		<div class="tb-season-headline">

			<h1 class="tb-season-title"><?php echo esc_html( $season_title ?: get_the_title() ); ?></h1>

			<?php if ( $sports && ! is_wp_error( $sports ) ) : ?>
				<p class="tb-season-sport">
					<?php echo esc_html( implode( ', ', wp_list_pluck( $sports, 'name' ) ) ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $timeline_status ) : ?>
				<p class="tb-season-status tb-status--<?php echo esc_attr( strtolower( $timeline_status ) ); ?>">
					<?php echo esc_html( $timeline_status ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $start_display || $end_display ) : ?>
				<p class="tb-season-dates">
					<?php if ( $start_display && $end_display ) : ?>
						<?php echo esc_html( $start_display . ' – ' . $end_display ); ?>
					<?php elseif ( $start_display ) : ?>
						<?php echo esc_html( 'Starting ' . $start_display ); ?>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( $description ) : ?>
				<div class="tb-season-description">
					<?php echo wp_kses_post( nl2br( $description ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $handbook['url'] ) ) : ?>
				<p class="tb-season-handbook">
					<a href="<?php echo esc_url( $handbook['url'] ); ?>"
					   <?php echo ! empty( $handbook['target'] ) ? 'target="' . esc_attr( $handbook['target'] ) . '"' : ''; ?>
					   rel="noopener noreferrer">
						<?php echo esc_html( $handbook['title'] ?: 'Season Handbook' ); ?>
					</a>
				</p>
			<?php endif; ?>

		</div><!-- .tb-season-headline -->

	</section><!-- .tb-season-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: COACHES                                                 ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-season-coaches">

		<h2>Coaches</h2>

		<?php if ( empty( $coaches ) ) : ?>
			<p class="tb-no-data">No coaches assigned yet.</p>
		<?php else : ?>
			<table class="tb-table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Title</th>
						<th>Role</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $coaches as $coach ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_permalink( $coach['coach_id'] ) ); ?>">
								<?php echo esc_html( $coach['name'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $coach['title'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $coach['role'] ?: '—' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</section><!-- .tb-season-coaches -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 3: MEETS                                                   ?>    <?php // ----------------------------------------------------------------- ?>
	<section class="tb-season-meets">

		<h2>Meet Schedule</h2>

		<?php if ( empty( $meets ) ) : ?>
			<p class="tb-no-data">No meets scheduled yet.</p>
		<?php else : ?>
			<table class="tb-table">
				<thead>
					<tr>
						<th>Meet</th>
						<th>Date</th>
						<th>Location</th>
						<th>Status</th>
						<th>Results</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $meets as $meet ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_permalink( $meet['meet_id'] ) ); ?>">
								<?php echo esc_html( $meet['meet_name'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $meet['meet_date'] ?: '—' ); ?></td>
						<td>
							<?php
							$loc = array_filter( [ $meet['city'], $meet['state'] ] );
							echo esc_html( $loc ? implode( ', ', $loc ) : '—' );
							?>
						</td>
						<td><?php echo esc_html( $meet['status'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $meet['results_status'] ?: '—' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</section><!-- .tb-season-meets -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 4: ATHLETE ROSTER                                          ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-season-roster">

		<h2>Athletes</h2>

		<?php if ( empty( $athletes ) ) : ?>
			<p class="tb-no-data">No athletes enrolled yet.</p>
		<?php else : ?>
			<table class="tb-table">
				<thead>
					<tr>
						<th>Athlete</th>
						<th>Grade</th>
						<th>Participation</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $athletes as $athlete ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_permalink( $athlete['athlete_id'] ) ); ?>">
								<?php echo esc_html( $athlete['name'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $athlete['grade'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $athlete['participation_type'] ?: '—' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</section><!-- .tb-season-roster -->

</div><!-- .tb-season -->

<?php
endwhile;

get_footer();