<?php
/**
 * Template: single-coach.php
 * Displays a single Coach profile page.
 *
 * Sections:
 *   1. Coach header (photo, name, title)
 *   2. Bio
 *
 * Note: Season backreference (which seasons this coach has coached) is not
 * included here because the relationship is stored on the Season post via the
 * coach_roster repeater, not on the Coach post. A reverse lookup via LIKE
 * meta query is possible but fragile. Deferred for future enhancement.
 *
 * Field references:
 *   group_tb_coach.json — coach fields
 */

get_header();

while ( have_posts() ) :
	the_post();

	$coach_id = get_the_ID();

	// -------------------------------------------------------------------------
	// COACH CORE FIELDS
	// -------------------------------------------------------------------------
	$first_name = get_field( 'first_name', $coach_id );
	$last_name  = get_field( 'last_name', $coach_id );
	$title      = get_field( 'preferred_title', $coach_id );
	$bio        = get_field( 'bio', $coach_id );
	$image_id   = get_field( 'featured_image', $coach_id );

	$full_name  = trim( $first_name . ' ' . $last_name );

?>

<div class="tb-coach">

	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 1: COACH HEADER                                            ?>
	<?php // ----------------------------------------------------------------- ?>
	<section class="tb-coach-header">

		<?php if ( $image_id ) : ?>
			<div class="tb-coach-photo">
				<?php echo wp_get_attachment_image( $image_id, 'medium' ); ?>
			</div>
		<?php endif; ?>

		<div class="tb-coach-headline">

			<h1 class="tb-coach-name"><?php echo esc_html( $full_name ?: get_the_title() ); ?></h1>

			<?php if ( $title ) : ?>
				<p class="tb-coach-title"><?php echo esc_html( $title ); ?></p>
			<?php endif; ?>

		</div><!-- .tb-coach-headline -->

	</section><!-- .tb-coach-header -->


	<?php // ----------------------------------------------------------------- ?>
	<?php // SECTION 2: BIO                                                     ?>
	<?php // ----------------------------------------------------------------- ?>
	<?php if ( $bio ) : ?>
	<section class="tb-coach-bio">
		<div class="tb-coach-bio-content">
			<?php echo wp_kses_post( $bio ); ?>
		</div>
	</section><!-- .tb-coach-bio -->
	<?php endif; ?>

</div><!-- .tb-coach -->

<?php
endwhile;

get_footer();