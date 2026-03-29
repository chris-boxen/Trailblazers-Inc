<?php
/**
 * Template: archive-athletic_meet.php
 * Displays the public Athletic Meet archive.
 *
 * Columns: Meet Name, Date, Location, Season, Status
 * Data attributes: data-season, data-sport, data-status, data-results-status, data-year
 * Sorted: date descending (most recent first)
 *
 * Field references:
 *   group_tb_athletic_meet.json — meet fields
 *   group_tb_athletic_season.json — season title (via linked season)
 *   taxonomy: sport
 */

get_header();

// -------------------------------------------------------------------------
// MEETS — query all published meets, sorted by date descending
// -------------------------------------------------------------------------
$meets_query = new WP_Query( [
	'post_type'      => 'athletic_meet',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'meta_key'       => 'date',
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
] );

$meets = [];
if ( $meets_query->have_posts() ) {
	foreach ( $meets_query->posts as $meet ) {
		$meet_date = get_field( 'date', $meet->ID );       // Y-m-d
		$season_id = get_field( 'season', $meet->ID );
		$status    = get_field( 'status', $meet->ID );
		$results_status = get_field( 'results_status', $meet->ID );

		// Season label and slug for filtering
		$season_title = $season_id ? get_field( 'season_title', $season_id ) : '';
		$season_slug  = $season_id ? sanitize_title( $season_title ) : 'none';

		// Sport terms from the meet post
		$sport_terms = get_the_terms( $meet->ID, 'sport' );
		$sport_slugs = ( $sport_terms && ! is_wp_error( $sport_terms ) )
			? implode( ' ', wp_list_pluck( $sport_terms, 'slug' ) )
			: 'none';

		// Location
		$city  = get_field( 'city', $meet->ID );
		$state = get_field( 'state', $meet->ID );
		$loc   = implode( ', ', array_filter( [ $city, $state ] ) );

		// Year from date for filtering
		$year = $meet_date ? date( 'Y', strtotime( $meet_date ) ) : '';

		$meets[] = [
			'meet_id'        => $meet->ID,
			'meet_name'      => get_field( 'meet_name', $meet->ID ) ?: get_the_title( $meet->ID ),
			'meet_date'      => $meet_date,
			'date_display'   => $meet_date ? date_i18n( 'F j, Y', strtotime( $meet_date ) ) : '',
			'location'       => $loc,
			'season_id'      => $season_id,
			'season_title'   => $season_title,
			'season_slug'    => $season_slug,
			'sport_slugs'    => $sport_slugs,
			'status'         => $status,
			'results_status' => $results_status,
			'year'           => $year,
		];
	}
}
wp_reset_postdata();

?>

<div class="tb-archive tb-meet-archive">

	<header class="tb-archive-header">
		<h1>Meets</h1>
	</header>

	<?php if ( empty( $meets ) ) : ?>
		<p class="tb-no-data">No meets found.</p>
	<?php else : ?>

		<table class="tb-table tb-meet-list">
			<thead>
				<tr>
					<th>Meet</th>
					<th>Date</th>
					<th>Location</th>
					<th>Season</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $meets as $meet ) :
					$status_attr         = $meet['status']         ? strtolower( $meet['status'] )         : 'unknown';
					$results_status_attr = $meet['results_status'] ? strtolower( $meet['results_status'] ) : 'unknown';
				?>
				<tr class="tb-meet-row"
					data-season="<?php echo esc_attr( $meet['season_slug'] ); ?>"
					data-sport="<?php echo esc_attr( $meet['sport_slugs'] ); ?>"
					data-status="<?php echo esc_attr( $status_attr ); ?>"
					data-results-status="<?php echo esc_attr( $results_status_attr ); ?>"
					data-year="<?php echo esc_attr( $meet['year'] ); ?>">
					<td>
						<a href="<?php echo esc_url( get_permalink( $meet['meet_id'] ) ); ?>">
							<?php echo esc_html( $meet['meet_name'] ); ?>
						</a>
					</td>
					<td><?php echo esc_html( $meet['date_display'] ?: '—' ); ?></td>
					<td><?php echo esc_html( $meet['location'] ?: '—' ); ?></td>
					<td>
						<?php if ( $meet['season_id'] ) : ?>
							<a href="<?php echo esc_url( get_permalink( $meet['season_id'] ) ); ?>">
								<?php echo esc_html( $meet['season_title'] ); ?>
							</a>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $meet['status'] ?: '—' ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php endif; ?>

</div><!-- .tb-meet-archive -->

<?php get_footer(); ?>