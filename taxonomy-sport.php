<?php
/**
 * Template: taxonomy-sport.php
 * Displays a Sport taxonomy term archive page.
 *
 * Sections:
 *   1. Sport header (name, description)
 *   2. Current and upcoming seasons for this sport
 *   3. Athletes in this sport
 *
 * WordPress taxonomy templates use get_queried_object() to access
 * the current term — not get_the_ID() or the standard post loop.
 *
 * Field references:
 *   taxonomy-sport — term name, slug, description
 *   group_tb_athletic_season.json — season fields (queried by sport term)
 *   group_tb_athlete.json — athlete fields (queried by sport term)
 */

get_header();

// -------------------------------------------------------------------------
// CURRENT TERM
// -------------------------------------------------------------------------
$term    = get_queried_object();
$term_id = $term->term_id;

// -------------------------------------------------------------------------
// SEASONS — query seasons tagged with this sport term, ordered by start date
// -------------------------------------------------------------------------
$seasons_query = new WP_Query( [
	'post_type'      => 'athletic_season',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'meta_key'       => 'start_date',
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
	'tax_query'      => [
		[
			'taxonomy'         => 'sport',
			'field'            => 'term_id',
			'terms'            => $term_id,
			'include_children' => false,
		],
	],
] );

$seasons = [];
if ( $seasons_query->have_posts() ) {
	foreach ( $seasons_query->posts as $season ) {
		$start      = get_field( 'start_date', $season->ID );
		$end        = get_field( 'end_date', $season->ID );
		$seasons[]  = [
			'season_id'      => $season->ID,
			'season_title'   => get_field( 'season_title', $season->ID ) ?: get_the_title( $season->ID ),
			'start_display'  => $start ? date_i18n( 'F j, Y', strtotime( $start ) ) : '',
			'end_display'    => $end   ? date_i18n( 'F j, Y', strtotime( $end ) )   : '',
			'timeline_status' => get_field( 'timeline_status', $season->ID ),
		];
	}
}
wp_reset_postdata();

// -------------------------------------------------------------------------
// COACHES — query coaches tagged with this sport term, sorted by last name
// Role/bio override per season is managed via the coach_roster repeater
// on each Athletic Season — not queried here.
// -------------------------------------------------------------------------
$coaches_query = new WP_Query( [
	'post_type'      => 'coach',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'tax_query'      => [
		[
			'taxonomy'         => 'sport',
			'field'            => 'term_id',
			'terms'            => $term_id,
			'include_children' => false,
		],
	],
] );

$coaches = [];
if ( $coaches_query->have_posts() ) {
	foreach ( $coaches_query->posts as $coach ) {
		$first = get_field( 'first_name', $coach->ID );
		$last  = get_field( 'last_name', $coach->ID );
		$coaches[] = [
			'coach_id'  => $coach->ID,
			'name'      => trim( $first . ' ' . $last ),
			'last_name' => $last,
			'title'     => get_field( 'preferred_title', $coach->ID ),
		];
	}
	usort( $coaches, function( $a, $b ) {
		return strcmp( $a['last_name'], $b['last_name'] );
	} );
}
wp_reset_postdata();

// -------------------------------------------------------------------------
// ATHLETES — query athletes tagged with this sport term, sorted by last name
// -------------------------------------------------------------------------
$athletes_query = new WP_Query( [
	'post_type'      => 'athlete',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'tax_query'      => [
		[
			'taxonomy'         => 'sport',
			'field'            => 'term_id',
			'terms'            => $term_id,
			'include_children' => false,
		],
	],
] );

$athletes = [];
if ( $athletes_query->have_posts() ) {
	foreach ( $athletes_query->posts as $athlete ) {
		$first     = get_field( 'first_name', $athlete->ID );
		$preferred = get_field( 'preferred_name', $athlete->ID );
		$last      = get_field( 'last_name', $athlete->ID );
		$athletes[] = [
			'athlete_id'      => $athlete->ID,
			'name'            => trim( ( $preferred ?: $first ) . ' ' . $last ),
			'last_name'       => $last,
			'gender'          => get_field( 'gender', $athlete->ID ),
			'account_status'  => get_field( 'account_status', $athlete->ID ),
			'graduation_year' => get_field( 'graduation_year', $athlete->ID ),
		];
	}
	// Sort by last name ascending
	usort( $athletes, function( $a, $b ) {
		return strcmp( $a['last_name'], $b['last_name'] );
	} );
}
wp_reset_postdata();

?>

<div class="tb-sport-archive">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: SPORT HEADER                                            ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-sport-header">

		<h1 class="tb-sport-name"><?php echo esc_html( $term->name ); ?></h1>

		<?php if ( $term->description ) : ?>
			<div class="tb-sport-description">
				<?php echo wp_kses_post( $term->description ); ?>
			</div>
		<?php endif; ?>

	</section><!-- .tb-sport-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: SEASONS                                                 ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-sport-seasons">

		<h2>Seasons</h2>

		<?php if ( empty( $seasons ) ) : ?>
			<p class="tb-no-data">No seasons on record for this sport.</p>
		<?php else : ?>
			<table class="tb-table">
				<thead>
					<tr>
						<th>Season</th>
						<th>Dates</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $seasons as $season ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_permalink( $season['season_id'] ) ); ?>">
								<?php echo esc_html( $season['season_title'] ); ?>
							</a>
						</td>
						<td>
							<?php
							$dates = array_filter( [ $season['start_display'], $season['end_display'] ] );
							echo esc_html( $dates ? implode( ' – ', $dates ) : '—' );
							?>
						</td>
						<td><?php echo esc_html( $season['timeline_status'] ?: '—' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</section><!-- .tb-sport-seasons -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 3: COACHES                                                 ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-sport-coaches">

		<h2>Coaches</h2>

		<?php if ( empty( $coaches ) ) : ?>
			<p class="tb-no-data">No coaches on record for this sport.</p>
		<?php else : ?>
			<table class="tb-table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Title</th>
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
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</section><!-- .tb-sport-coaches -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 4: ATHLETES                                                ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-sport-athletes">

		<h2>Athletes</h2>

		<?php if ( empty( $athletes ) ) : ?>
			<p class="tb-no-data">No athletes on record for this sport.</p>
		<?php else : ?>
			<table class="tb-table tb-athlete-roster">
				<thead>
					<tr>
						<th>Name</th>
						<th>Gender</th>
						<th>Grad Year</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $athletes as $athlete ) :
						$gender  = $athlete['gender'] ? strtolower( $athlete['gender'] ) : 'unknown';
						$status  = $athlete['account_status'] ? strtolower( $athlete['account_status'] ) : 'unknown';
					?>
					<tr class="tb-athlete-row"
						data-gender="<?php echo esc_attr( $gender ); ?>"
						data-status="<?php echo esc_attr( $status ); ?>">
						<td>
							<a href="<?php echo esc_url( get_permalink( $athlete['athlete_id'] ) ); ?>">
								<?php echo esc_html( $athlete['name'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $athlete['gender'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $athlete['graduation_year'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $athlete['account_status'] ?: '—' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</section><!-- .tb-sport-athletes -->

</div><!-- .tb-sport-archive -->

<?php get_footer(); ?>