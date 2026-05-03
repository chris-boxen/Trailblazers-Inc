<?php
/**
 * Template: archive-athlete.php
 * Displays the public Athlete archive.
 *
 * Columns: Name | Gender | Grad Year | Status | Sport
 * Data attributes: data-gender, data-grad-year, data-status, data-sport, data-seasons
 * Sorted: last name ascending
 *
 * Field references:
 *   group_tb_athlete.json    — names group, demographics group, status group
 *   group_tb_enrollment.json — athlete (id), season (id)
 *   taxonomy: sport
 */

get_header();

// -------------------------------------------------------------------------
// ENROLLMENT PRE-QUERY
// Build a lookup map of athlete_id => [ season_slug, ... ] before the
// athlete loop. One query here instead of N queries inside the loop.
// Both 'athlete' and 'season' fields return IDs (return_format: id).
// -------------------------------------------------------------------------
$enrollment_query = new WP_Query( [
	'post_type'      => 'enrollment',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
] );

$athlete_seasons = []; // athlete_id (int) => [ season_slug, ... ]
if ( $enrollment_query->have_posts() ) {
	foreach ( $enrollment_query->posts as $enrollment ) {
		$enr_athlete_id = get_field( 'athlete', $enrollment->ID ); // int
		$enr_season_id  = get_field( 'season',  $enrollment->ID ); // int
		if ( ! $enr_athlete_id || ! $enr_season_id ) continue;
		$season_slug = get_post_field( 'post_name', $enr_season_id );
		if ( $season_slug ) {
			$athlete_seasons[ $enr_athlete_id ][] = $season_slug;
		}
	}
}
wp_reset_postdata();

// -------------------------------------------------------------------------
// ATHLETES QUERY
// Sorted by last name via usort after collection.
// -------------------------------------------------------------------------
$athletes_query = new WP_Query( [
	'post_type'      => 'athlete',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
] );

$athletes = [];
if ( $athletes_query->have_posts() ) {
	foreach ( $athletes_query->posts as $athlete ) {

		$names        = get_field( 'names',        $athlete->ID );
		$demographics = get_field( 'demographics', $athlete->ID );
		$status_data  = get_field( 'status',       $athlete->ID );

		$first     = $names['first_name']            ?? '';
		$preferred = $names['preferred_name']         ?? '';
		$last      = $names['last_name']             ?? '';
		$gender    = $demographics['gender']          ?? '';
		$grad_year = $demographics['graduation_year'] ?? '';
		$status    = $status_data['account_status']   ?? '';

		// Sport taxonomy terms
		$sport_terms = get_the_terms( $athlete->ID, 'sport' );
		$sport_names = ( $sport_terms && ! is_wp_error( $sport_terms ) )
			? implode( ', ', wp_list_pluck( $sport_terms, 'name' ) )
			: '';
		$sport_slugs = ( $sport_terms && ! is_wp_error( $sport_terms ) )
			? implode( ' ', wp_list_pluck( $sport_terms, 'slug' ) )
			: '';

		// Season slugs from pre-built map — deduplicated in case of duplicate enrollments
		$season_slugs = isset( $athlete_seasons[ $athlete->ID ] )
			? array_unique( $athlete_seasons[ $athlete->ID ] )
			: [];

		$athletes[] = [
			'athlete_id'   => $athlete->ID,
			'name'         => trim( ( $preferred ?: $first ) . ' ' . $last ),
			'last_name'    => $last,
			'gender'       => $gender,
			'grad_year'    => $grad_year,
			'status'       => $status,
			'sport_names'  => $sport_names,
			'sport_slugs'  => $sport_slugs,
			'season_slugs' => $season_slugs,
		];
	}
	usort( $athletes, function( $a, $b ) {
		return strcmp( $a['last_name'], $b['last_name'] );
	} );
}
wp_reset_postdata();

?>

<div class="tb-archive tb-athlete-archive">

	<header class="tb-archive-header">
		<h1>Athletes (<span class="filter-count"></span>)</h1>
	</header>
	
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
			<div class="ui-group">
				<select type="select" class="filter-select filter-options" data-group="type">
					<option value="">Status</option>
					<option value="[data-status='active']" id="filter-ms">Active</option>
					<option value="[data-status='alumni']">Alumni</option>
					<option value="[data-status='inactive']">Inactive</option>
					<option value="">All</option>
				</select>
			</div>
			<div class="ui-group">
				<select type="select" class="filter-select filter-options" data-group="type">
					<option value="">Season</option>
					<option value="[data-season='active']" id="filter-ms">Active</option>
					<option value="[data-status='alumni']">Alumni</option>
					<option value="[data-status='inactive']">Inactive</option>
					<option value="">All</option>
				</select>
			</div>
		</div>
	
		<div id="sort-controls" class="controls-group">
			<h4>Sort By</h4>
			<div id="sorts" class="button-group">  
				<!--<button class="button" data-sort-by="first_name">First Name</button>-->
				<button class="button" data-sort-by="last_name">Last Name</button>
				<button class="button" data-sort-by="grad_year">Grad Year</button>
				<!--<button class="button" data-sort-by="pr">PR</button>-->
				<!--<button class="button" data-sort-by="sr">SR</button>-->
			</div>
		</div>
	</div><!-- #ui-controls -->

	<?php if ( empty( $athletes ) ) : ?>
		<p class="tb-no-data">No athletes found.</p>
	<?php else : ?>

		<div class="tb-list-wrap tb-athlete-list-wrap">
			<div class="tb-list-header">
				<span class="tb-col">Name</span>
				<span class="tb-col">Gender</span>
				<span class="tb-col">Grad Year</span>
				<span class="tb-col">Status</span>
				<span class="tb-col">Sport</span>
			</div>
			<ul id="directory" class="tb-list tb-athlete-list">

				<?php foreach ( $athletes as $athlete ) :
					$gender_attr  = $athlete['gender']    ? strtolower( $athlete['gender'] )  : 'unknown';
					$status_attr  = $athlete['status']    ? strtolower( $athlete['status'] )  : 'unknown';
					$year_attr    = $athlete['grad_year'] ?: '';
					$sport_attr   = $athlete['sport_slugs'] ?: 'none';
					$seasons_attr = ! empty( $athlete['season_slugs'] )
						? implode( ' ', $athlete['season_slugs'] )
						: 'none';
				?>
				<li class="tb-list-row"
					data-last-name="<?php echo esc_attr( strtolower( $athlete['last_name'] ) ); ?>"
					data-gender="<?php echo esc_attr( $gender_attr ); ?>"
					data-grad-year="<?php echo esc_attr( $year_attr ); ?>"
					data-status="<?php echo esc_attr( $status_attr ); ?>"
					data-sport="<?php echo esc_attr( $sport_attr ); ?>"
					data-seasons="<?php echo esc_attr( $seasons_attr ); ?>">
					<a href="<?php echo esc_url( get_permalink( $athlete['athlete_id'] ) ); ?>" class="tb-list-link">
						<span class="tb-col"><?php echo esc_html( $athlete['name'] ); ?></span>
						<span class="tb-col"><?php echo esc_html( $athlete['gender'] ?: '—' ); ?></span>
						<span class="tb-col"><?php echo esc_html( $athlete['grad_year'] ?: '—' ); ?></span>
						<span class="tb-col"><?php echo esc_html( $athlete['status'] ?: '—' ); ?></span>
						<span class="tb-col"><?php echo esc_html( $athlete['sport_names'] ?: '—' ); ?></span>
					</a>
				</li>
				<?php endforeach; ?>

			</ul>
		</div><!-- .tb-list-wrap -->

	<?php endif; ?>

</div><!-- .tb-athlete-archive -->

<?php get_footer(); ?>