<?php
/**
 * Single Event Template
 * A single event. This displays the event title, description, meta, and
 * optionally, the Google map for the event.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/single-event.php
 *
 * @package TribeEventsCalendar
 * @version 4.6.19
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$events_label_singular = tribe_get_event_label_singular();
$events_label_plural   = tribe_get_event_label_plural();

$event_id = Tribe__Events__Main::postIdHelper( get_the_ID() );

/**
 * Allows filtering of the event ID.
 *
 * @since 6.0.1
 *
 * @param numeric $event_id
 */
$event_id = apply_filters( 'tec_events_single_event_id', $event_id );

/**
 * Allows filtering of the single event template title classes.
 *
 * @since 5.8.0
 *
 * @param array   $title_classes List of classes to create the class string from.
 * @param numeric $event_id      The ID of the displayed event.
 */
$title_classes = apply_filters( 'tribe_events_single_event_title_classes', [ 'tribe-events-single-event-title' ], $event_id );
$title_classes = implode( ' ', tribe_get_classes( $title_classes ) );

/**
 * Allows filtering of the single event template title before HTML.
 *
 * @since 5.8.0
 *
 * @param string  $before   HTML string to display before the title text.
 * @param numeric $event_id The ID of the displayed event.
 */
$before = apply_filters( 'tribe_events_single_event_title_html_before', '<h1 class="' . $title_classes . '">', $event_id );

/**
 * Allows filtering of the single event template title after HTML.
 *
 * @since 5.8.0
 *
 * @param string  $after    HTML string to display after the title text.
 * @param numeric $event_id The ID of the displayed event.
 */
$after = apply_filters( 'tribe_events_single_event_title_html_after', '</h1>', $event_id );

/**
 * Allows filtering of the single event template title HTML.
 *
 * @since 5.8.0
 *
 * @param string  $after    HTML string to display. Return an empty string to not display the title.
 * @param numeric $event_id The ID of the displayed event.
 */
$title = apply_filters( 'tribe_events_single_event_title_html', the_title( $before, $after, false ), $event_id );
$cost  = tribe_get_formatted_cost( $event_id );

?>

<div class="tribe-events-single tb-single">

	<!-- TB Event Header -->
	<section class="tb-single-header tb-event-header">
		<div class="tb-event-header-group">
			<p class="tribe-events-back">
				<a href="<?php echo esc_url( tribe_get_events_link() ); ?>"> <?php printf( '&laquo; ' . esc_html_x( 'All %s', '%s Events plural label', 'the-events-calendar' ), $events_label_plural ); ?></a>
			</p>
				
			<?php echo $title; ?>
		
			<div class="tribe-events-schedule tribe-clearfix">
				<?php echo tribe_events_event_schedule_details( $event_id, '<div>', '</div>' ); // phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped ?>
				<?php if ( ! empty( $cost ) ) : ?>
					<span class="tribe-events-cost"><?php echo esc_html( $cost ) ?></span>
				<?php endif; ?>
			</div>
			
			<!-- Notices -->
			<?php tribe_the_notices() ?>
			
			<?php do_action( 'tribe_events_single_event_after_the_content' ) ?>
			
		</div><!-- #tb-event-header-group -->
		
		<!-- Event featured image, but exclude link -->
		<?php echo tribe_event_featured_image( $event_id, 'full', false ); ?>
		
	</section><!-- .tb-event-header -->
	
	<!-- Event Meta -->
	<section class="tb-single-section tb-event-meta">
		<div id="tb-event-meta">
			<?php do_action( 'tribe_events_single_event_before_the_meta' ) ?>
			<?php tribe_get_template_part( 'modules/meta' ); ?>
			<?php do_action( 'tribe_events_single_event_after_the_meta' ) ?>
		</div>
	</section>
	
	<!-- Event Content -->
	<?php while ( have_posts() ) :  the_post(); ?>
		<section class="tb-single-section tb-event-content">
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<!-- Event content -->
			<?php do_action( 'tribe_events_single_event_before_the_content' ) ?>
			<div class="tribe-events-single-event-description tribe-events-content">
				<?php the_content(); ?>
			</div>
			<!-- .tribe-events-single-event-description -->
			
		</div> <!-- #post-x -->
		<?php if ( get_post_type() == Tribe__Events__Main::POSTTYPE && tribe_get_option( 'showComments', false ) ) comments_template() ?>
		</section>
	<?php endwhile; ?>
	
	<?php
	// For athletic-meet events, append the results section below TEC's output.
	$post_id = get_queried_object_id();
	if ( is_singular( 'tribe_events' ) && has_term( 'athletic-meet', 'tribe_events_cat', $post_id ) ) {
		get_template_part( 'tribe-events/tb-meet-results' );
	}
	?>
	
	<!-- Event footer -->
	<section class="tb-single-section tb-event-footer">
		<div id="tribe-events-footer">
			<!-- Navigation -->
			<nav class="tribe-events-nav-pagination" aria-label="<?php printf( esc_html__( '%s Navigation', 'the-events-calendar' ), $events_label_singular ); ?>">
				<ul class="tribe-events-sub-nav">
					<li class="tribe-events-nav-previous"><?php tribe_the_prev_event_link( '<span>&laquo;</span> %title%' ) ?></li>
					<li class="tribe-events-nav-next"><?php tribe_the_next_event_link( '%title% <span>&raquo;</span>' ) ?></li>
				</ul>
				<!-- .tribe-events-sub-nav -->
			</nav>
		</div>
		<!-- #tribe-events-footer -->
	</section>

</div><!-- #tribe-events-content -->
