<?php
/**
 * Template: archive-athletic_season.php
 * Displays the public Athletic Season archive.
 *
 * Columns: Season | Sport | Dates | Status
 * Data attributes: data-sport, data-status, data-year
 * Sorted: start_date descending (most recent first)
 *
 * Field references:
 *   group_tb_athletic_season.json — season_title, start_date, end_date,
 *                                   year, timeline_status
 *   taxonomy: sport
 */

get_header();

// -------------------------------------------------------------------------
// SEASONS QUERY
// -------------------------------------------------------------------------
$seasons_query = new WP_Query( [
	'post_type'      => 'athletic_season',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'meta_key'       => 'start_date',
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
] );

$seasons = [];
if ( $seasons_query->have_posts() ) {
	foreach ( $seasons_query->posts as $season ) {

		$start_date      = get_field( 'start_date', $season->ID );
		$end_date        = get_field( 'end_date', $season->ID );
		$timeline_status = get_field( 'timeline_status', $season->ID );
		$year            = get_field( 'year', $season->ID );

		$sport_terms = get_the_terms( $season->ID, 'sport' );
		$sport_names = ( $sport_terms && ! is_wp_error( $sport_terms ) )
			? implode( ', ', wp_list_pluck( $sport_terms, 'name' ) )
			: '';
		$sport_slugs = ( $sport_terms && ! is_wp_error( $sport_terms ) )
			? implode( ' ', wp_list_pluck( $sport_terms, 'slug' ) )
			: 'none';

		$start_display = $start_date ? date_i18n( 'M j, Y', strtotime( $start_date ) ) : '';
		$end_display   = $end_date   ? date_i18n( 'M j, Y', strtotime( $end_date ) )   : '';

		if ( $start_display && $end_display ) {
			$dates_display = $start_display . ' – ' . $end_display;
		} elseif ( $start_display ) {
			$dates_display = 'Starting ' . $start_display;
		} else {
			$dates_display = '';
		}

		$year_attr = $year ?: ( $start_date ? date( 'Y', strtotime( $start_date ) ) : '' );

		$seasons[] = [
			'season_id'       => $season->ID,
			'season_title'    => get_field( 'season_title', $season->ID ) ?: get_the_title( $season->ID ),
			'sport_names'     => $sport_names,
			'sport_slugs'     => $sport_slugs,
			'dates_display'   => $dates_display,
			'timeline_status' => $timeline_status,
			'status_slug'     => $timeline_status ? strtolower( $timeline_status ) : 'unknown',
			'year'            => $year_attr,
		];
	}
}
wp_reset_postdata();

?>

<div class="tb-archive tb-season-archive">

	<header class="tb-archive-header">
		<h1>Seasons</h1>
	</header>

	<?php if ( empty( $seasons ) ) : ?>
		<p class="tb-no-data">No seasons found.</p>
	<?php else : ?>

		<ul class="tb-list tb-seasons-list">
			<li class="tb-list-header">
				<span class="tb-col">Season</span>
				<span class="tb-col">Sport</span>
				<span class="tb-col">Dates</span>
				<span class="tb-col">Status</span>
			</li>
			<?php foreach ( $seasons as $season ) : ?>
			<li class="tb-list-row"
				data-sport="<?php echo esc_attr( $season['sport_slugs'] ); ?>"
				data-status="<?php echo esc_attr( $season['status_slug'] ); ?>"
				data-year="<?php echo esc_attr( $season['year'] ); ?>">
				<a href="<?php echo esc_url( get_permalink( $season['season_id'] ) ); ?>" class="tb-list-link">
					<span class="tb-col"><?php echo esc_html( $season['season_title'] ); ?></span>
					<span class="tb-col"><?php echo esc_html( $season['sport_names'] ?: '—' ); ?></span>
					<span class="tb-col"><?php echo esc_html( $season['dates_display'] ?: '—' ); ?></span>
					<span class="tb-col">
						<?php if ( $season['timeline_status'] ) : ?>
							<span class="tb-status tb-status--<?php echo esc_attr( $season['status_slug'] ); ?>">
								<?php echo esc_html( $season['timeline_status'] ); ?>
							</span>
						<?php else : ?>
							—
						<?php endif; ?>
					</span>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>

	<?php endif; ?>

</div><!-- .tb-archive .tb-season-archive -->

<?php get_footer(); ?>