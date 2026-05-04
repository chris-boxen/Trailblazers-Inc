<?php
/**
 * Template: single-athletic_season.php
 * Displays a single Athletic Season page.
 *
 * Sections:
 *   1. Season header (title, sport, dates, status, description, handbook)
 *   2. Coaches — from coach_roster repeater on this season
 *   3. Meet Schedule — queried from tribe_events where season = this post
 *   4. Athlete Roster — queried from Enrollment where season = this post,
 *      participation_type = Athlete
 *   5. Sibling Runner Roster — queried from Enrollment where season = this post,
 *      participation_type = Sibling Runner
 *
 * Field references:
 *   group_tb_athletic_season.json — season fields, season flags (customize_data group)
 *   group_tb_athletic_meet.json   — season + results_status on tribe_events (via ACF)
 *   group_tb_enrollment.json      — enrollment fields (queried)
 *   group_tb_athlete.json         — athlete name fields (queried via enrollment)
 *
 * TEC field references (postmeta, not ACF):
 *   _EventStartDate — meet start datetime (format: Y-m-d H:i:s)
 *   _EventVenueID   — linked tribe_venue post ID
 *   _VenueCity, _VenueStateProvince — on the venue post
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
	$year            = get_field( 'year', $season_id );
	$timeline_status = get_field( 'timeline_status', $season_id ); // Past | Current | Future
	$handbook        = get_field( 'handbook', $season_id );        // link field → array
	$featured_image  = get_field( 'featured_image', $season_id );  // ACF image field → ID

	// Dates
	$start_date = get_field( 'start_date', $season_id ); // Y-m-d
	$end_date   = get_field( 'end_date', $season_id );   // Y-m-d

	// Season flags
	$results_enabled = get_field( 'results_enabled', $season_id );

	// Sport taxonomy terms
	$sports = get_the_terms( $season_id, 'sport' );

	// Format dates
	$start_display = $start_date ? date_i18n( 'F j, Y', strtotime( $start_date ) ) : '';
	$end_display   = $end_date   ? date_i18n( 'F j, Y', strtotime( $end_date ) )   : '';

	// -------------------------------------------------------------------------
	// COACHES
	// -------------------------------------------------------------------------
	$coaches      = [];
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
	// MEETS
	// -------------------------------------------------------------------------
	$meets_query = new WP_Query( [
		'post_type'      => 'tribe_events',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_key'       => '_EventStartDate',
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
			$raw_date  = get_post_meta( $meet->ID, '_EventStartDate', true );
			$meet_date = $raw_date ? date( 'Y-m-d', strtotime( $raw_date ) ) : '';

			$venue_id    = get_post_meta( $meet->ID, '_EventVenueID', true );
			$venue_city  = $venue_id ? get_post_meta( $venue_id, '_VenueCity', true ) : '';
			$venue_state = $venue_id ? get_post_meta( $venue_id, '_VenueStateProvince', true ) : '';

			$meets[] = [
				'meet_id'        => $meet->ID,
				'meet_name'      => get_the_title( $meet->ID ),
				'meet_date'      => $meet_date,
				'date_display'   => $meet_date ? date_i18n( 'M j, Y', strtotime( $meet_date ) ) : '',
				'city'           => $venue_city,
				'state'          => $venue_state,
				'results_status' => get_field( 'results_status', $meet->ID ),
			];
		}
	}
	wp_reset_postdata();

	// -------------------------------------------------------------------------
	// ATHLETE ROSTER
	// Split into athletes and sibling runners by participation_type on Enrollment.
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

	$athletes        = [];
	$sibling_runners = [];

	if ( $enrollment_query->have_posts() ) {
		foreach ( $enrollment_query->posts as $enrollment ) {
			$athlete_id = get_field( 'athlete', $enrollment->ID );
			if ( ! $athlete_id ) continue;

			$names        = get_field( 'names', $athlete_id );
			$demographics = get_field( 'demographics', $athlete_id );
			$first        = $names['first_name']     ?? '';
			$preferred    = $names['preferred_name'] ?? '';
			$last         = $names['last_name']      ?? '';
			$gender		  = $demographics['gender']  ?? '';

			$participation_type = get_field( 'participation_type', $enrollment->ID );

			$entry = [
				'athlete_id'         => $athlete_id,
				'name'               => trim( ( $preferred ?: $first ) . ' ' . $last ),
				'first_name'         => $first,
				'last_name'          => $last,
				'grade'              => get_field( 'grade', $enrollment->ID ),
				'gender'             => $demographics['gender'] ?? '',  // M | F
				'participation_type' => $participation_type,
			];

			if ( $participation_type === 'Sibling Runner' ) {
				$sibling_runners[] = $entry;
			} else {
				$athletes[] = $entry;
			}
		}

		usort( $athletes,        fn( $a, $b ) => strcmp( $a['last_name'], $b['last_name'] ) );
		usort( $sibling_runners, fn( $a, $b ) => strcmp( $a['last_name'], $b['last_name'] ) );
	}
	wp_reset_postdata();

?>

<div class="tb-single tb-season">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: SEASON HEADER                                           ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-header tb-season-header">

		<div class="tb-single-headline tb-season-headline">

			<h1 class="tb-single-title tb-season-title">
				<?php echo esc_html( $season_title ?: get_the_title() ); ?>
			</h1>

			<div class="tb-single-meta tb-season-meta">

				<?php if ( $sports && ! is_wp_error( $sports ) ) : ?>
					<span class="tb-meta-sport">
						<?php echo esc_html( implode( ', ', wp_list_pluck( $sports, 'name' ) ) ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $timeline_status ) : ?>
					<span class="tb-status tb-status--<?php echo esc_attr( strtolower( $timeline_status ) ); ?>">
						<?php echo esc_html( $timeline_status ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $start_display || $end_display ) : ?>
					<span class="tb-meta-dates">
						<?php if ( $start_display && $end_display ) : ?>
							<?php echo esc_html( $start_display . ' – ' . $end_display ); ?>
						<?php elseif ( $start_display ) : ?>
							<?php echo esc_html( 'Starting ' . $start_display ); ?>
						<?php endif; ?>
					</span>
				<?php endif; ?>

			</div><!-- .tb-single-meta -->

			<?php if ( $description ) : ?>
				<div class="tb-single-description tb-season-description">
					<?php echo wp_kses_post( nl2br( $description ) ); ?>
				</div>
			<?php endif; ?>

		</div><!-- .tb-single-headline -->

		<div class="tb-single-header-secondary-section">

			<?php if ( $featured_image ) : ?>
				<div class="tb-single-image tb-season-image">
					<?php echo wp_get_attachment_image( $featured_image, 'large' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $handbook['url'] ) ) : ?>
				<div class="tb-single-cta tb-season-cta">
					<a href="<?php echo esc_url( $handbook['url'] ); ?>"
					   class="button"
					   <?php echo ! empty( $handbook['target'] ) ? 'target="' . esc_attr( $handbook['target'] ) . '"' : ''; ?>
					   rel="noopener noreferrer">
						<?php echo esc_html( $handbook['title'] ?: 'Season Handbook' ); ?>
					</a>
				</div>
			<?php endif; ?>

		</div><!-- .tb-single-header-secondary-section -->

	</section><!-- .tb-single-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: COACHES                                                 ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-section tb-season-coaches">

		<h2>Coaches</h2>

		<?php if ( empty( $coaches ) ) : ?>
			<p class="tb-no-data">No coaches assigned yet.</p>
		<?php else : ?>
			<ul class="tb-list tb-coaches-list">
				<li class="tb-list-header">
					<span class="tb-col">Name</span>
					<span class="tb-col">Title</span>
					<span class="tb-col">Role</span>
				</li>
				<?php foreach ( $coaches as $coach ) : ?>
				<li class="tb-list-row"
					data-name="<?php echo esc_attr( $coach['name'] ); ?>"
					data-role="<?php echo esc_attr( strtolower( $coach['role'] ) ); ?>">
					<a href="<?php echo esc_url( get_permalink( $coach['coach_id'] ) ); ?>" class="tb-list-link">
						<span class="tb-col"><?php echo esc_html( $coach['name'] ); ?></span>
						<span class="tb-col"><?php echo esc_html( $coach['title'] ?: '—' ); ?></span>
						<span class="tb-col"><?php echo esc_html( $coach['role'] ?: '—' ); ?></span>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

	</section><!-- .tb-single-section .tb-season-coaches -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 3: MEETS                                                   ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-section tb-season-meets">

		<h2>Meet Schedule</h2>

		<?php if ( empty( $meets ) ) : ?>
			<p class="tb-no-data">No meets scheduled yet.</p>
		<?php else : ?>
			<ul class="tb-list tb-meets-list">
				<li class="tb-list-header">
					<span class="tb-col">Meet</span>
					<span class="tb-col">Date</span>
					<span class="tb-col">Location</span>
					<span class="tb-col">Results</span>
				</li>
				<?php foreach ( $meets as $meet ) : ?>
				<?php
				$loc         = array_filter( [ $meet['city'], $meet['state'] ] );
				$loc_display = $loc ? implode( ', ', $loc ) : '—';
				?>
				<li class="tb-list-row"
					data-date="<?php echo esc_attr( $meet['meet_date'] ); ?>"
					data-results="<?php echo esc_attr( strtolower( $meet['results_status'] ?: 'future' ) ); ?>">
					<a href="<?php echo esc_url( get_permalink( $meet['meet_id'] ) ); ?>" class="tb-list-link">
						<span class="tb-col"><?php echo esc_html( $meet['meet_name'] ); ?></span>
						<span class="tb-col"><?php echo esc_html( $meet['date_display'] ?: '—' ); ?></span>
						<span class="tb-col"><?php echo esc_html( $loc_display ); ?></span>
						<span class="tb-col"><?php echo esc_html( $meet['results_status'] ?: 'Future' ); ?></span>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

	</section><!-- .tb-single-section .tb-season-meets -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 4: ATHLETE ROSTER                                          ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-single-section tb-season-roster">

		<h2>Athletes (<span class="filter-count"></span>)</h2>
		
		<div id="ui-controls">
			<div id="filter-controls" class="controls-group">
				<h4>Filter By</h4>
				  <div class="ui-group">
					  <select type="select" class="filter-select filter-options" data-group="gender">
						<option value="">Select Gender</option>
						<option value="[data-gender='m']" id="filter-boys">Boys</option>
						<option value="[data-gender='f']" id="filter-girls">Girls</option>
						<option value="">All</option>
					  </select>
				  </div>
			</div>
		
			<div id="sort-controls" class="controls-group">
				<h4>Sort By</h4>
				<div id="sorts" class="button-group">  
					<!--<button class="button" data-sort-by="first_name">First Name</button>-->
					<button class="button" data-sort-by="last_name">Last Name</button>
					<button class="button" data-sort-by="grade">Grade</button>
					<!--<button class="button" data-sort-by="pr">PR</button>-->
					<!--<button class="button" data-sort-by="sr">SR</button>-->
				</div>
			</div>
		</div><!-- #ui-controls -->

		<?php if ( empty( $athletes ) ) : ?>
			<p class="tb-no-data">No athletes enrolled yet.</p>
		<?php else : ?>
			<div class="tb-list-wrap tb-roster-list-wrap">
				<div class="tb-list-header">
					<span class="tb-col">Athlete</span>
					<span class="tb-col">Grade</span>
					<span class="tb-col">Records</span>
				</div>
				<ul id="directory" class="tb-list tb-roster-list">
					<?php foreach ( $athletes as $athlete ) : ?>
					<li class="tb-list-row"
						data-last-name="<?php echo esc_attr( strtolower( $athlete['last_name'] ) ); ?>"
						data-gender="<?php echo esc_attr( strtolower( $athlete['gender'] ) ); ?>"
						data-grade="<?php echo esc_attr( $athlete['grade'] ); ?>">
						<a href="<?php echo esc_url( get_permalink( $athlete['athlete_id'] ) ); ?>" class="tb-list-link">
							<span class="tb-col"><?php echo esc_html( $athlete['name'] ); ?></span>
							<span class="tb-col"><?php echo esc_html( $athlete['grade'] ?: '—' ); ?></span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div><!-- .tb-list-wrap -->
		<?php endif; ?>

	</section><!-- .tb-single-section .tb-season-roster -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 5: SIBLING RUNNER ROSTER                                   ?>
	<?php // ----------------------------------------------------------------- ?>
	<?php if ( ! empty( $sibling_runners ) ) : ?>
	<section class="tb-single-section tb-season-sibling-runners">

		<h2>Sibling Runners</h2>
		
		<div class="tb-list-wrap tb-roster-list-wrap">
			<div class="tb-list-header">
				<span class="tb-col">Athlete</span>
				<span class="tb-col">Grade</span>
				<span class="tb-col">Records</span>
			</div>
			<ul class="tb-list tb-sibling-runners-list">
				<?php foreach ( $sibling_runners as $athlete ) : ?>
				<li class="tb-list-row"
					data-last-name="<?php echo esc_attr( strtolower( $athlete['last_name'] ) ); ?>"
					data-gender="<?php echo esc_attr( $athlete['gender'] ); ?>"
					data-grade="<?php echo esc_attr( $athlete['grade'] ); ?>">
					<a href="<?php echo esc_url( get_permalink( $athlete['athlete_id'] ) ); ?>" class="tb-list-link">
						<span class="tb-col"><?php echo esc_html( $athlete['name'] ); ?></span>
						<span class="tb-col"><?php echo esc_html( $athlete['grade'] ?: '—' ); ?></span>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
		</div><!-- .tb-list-wrap -->
	</section><!-- .tb-single-section .tb-season-sibling-runners -->
	<?php endif; ?>


</div><!-- .tb-single .tb-season -->

<?php
endwhile;

get_footer();