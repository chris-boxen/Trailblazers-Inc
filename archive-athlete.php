<?php
/**
 * Template: archive-athlete.php
 * Displays the public Athlete archive.
 *
 * Columns: Name, Gender, Grad Year, Account Status, Sport
 * Data attributes: data-gender, data-grad-year, data-status, data-sport
 * Sorted: last name ascending
 *
 * Field references:
 *   group_tb_athlete.json — athlete fields
 *   taxonomy: sport
 */

get_header();

// -------------------------------------------------------------------------
// ATHLETES — query all published athletes, sorted by last name via usort
// -------------------------------------------------------------------------
$athletes_query = new WP_Query( [
	'post_type'      => 'athlete',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
] );

$athletes = [];
if ( $athletes_query->have_posts() ) {
	foreach ( $athletes_query->posts as $athlete ) {
		$first     = get_field( 'first_name', $athlete->ID );
		$preferred = get_field( 'preferred_name', $athlete->ID );
		$last      = get_field( 'last_name', $athlete->ID );
		$status    = get_field( 'account_status', $athlete->ID );
		$gender    = get_field( 'gender', $athlete->ID );
		$grad_year = get_field( 'graduation_year', $athlete->ID );

		// Sport taxonomy terms
		$sport_terms = get_the_terms( $athlete->ID, 'sport' );
		$sport_names = ( $sport_terms && ! is_wp_error( $sport_terms ) )
			? implode( ', ', wp_list_pluck( $sport_terms, 'name' ) )
			: '';
		$sport_slugs = ( $sport_terms && ! is_wp_error( $sport_terms ) )
			? implode( ' ', wp_list_pluck( $sport_terms, 'slug' ) )
			: '';

		$athletes[] = [
			'athlete_id' => $athlete->ID,
			'name'       => trim( ( $preferred ?: $first ) . ' ' . $last ),
			'last_name'  => $last,
			'gender'     => $gender,
			'grad_year'  => $grad_year,
			'status'     => $status,
			'sport_names' => $sport_names,
			'sport_slugs' => $sport_slugs,
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
		<h1>Athletes</h1>
	</header>

	<?php if ( empty( $athletes ) ) : ?>
		<p class="tb-no-data">No athletes found.</p>
	<?php else : ?>

		<table class="tb-table tb-athlete-roster">
			<thead>
				<tr>
					<th>Name</th>
					<th>Gender</th>
					<th>Grad Year</th>
					<th>Status</th>
					<th>Sport</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $athletes as $athlete ) :
					$gender_attr = $athlete['gender']   ? strtolower( $athlete['gender'] )   : 'unknown';
					$status_attr = $athlete['status']   ? strtolower( $athlete['status'] )   : 'unknown';
					$year_attr   = $athlete['grad_year'] ?: '';
					$sport_attr  = $athlete['sport_slugs'] ?: 'none';
				?>
				<tr class="tb-athlete-row"
					data-gender="<?php echo esc_attr( $gender_attr ); ?>"
					data-grad-year="<?php echo esc_attr( $year_attr ); ?>"
					data-status="<?php echo esc_attr( $status_attr ); ?>"
					data-sport="<?php echo esc_attr( $sport_attr ); ?>">
					<td>
						<a href="<?php echo esc_url( get_permalink( $athlete['athlete_id'] ) ); ?>">
							<?php echo esc_html( $athlete['name'] ); ?>
						</a>
					</td>
					<td><?php echo esc_html( $athlete['gender'] ?: '—' ); ?></td>
					<td><?php echo esc_html( $athlete['grad_year'] ?: '—' ); ?></td>
					<td><?php echo esc_html( $athlete['status'] ?: '—' ); ?></td>
					<td><?php echo esc_html( $athlete['sport_names'] ?: '—' ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php endif; ?>

</div><!-- .tb-athlete-archive -->

<?php get_footer(); ?>