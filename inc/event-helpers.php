<?php
// Unhook TEC Pro's related events from its default position.
// Priority 10 is the TEC Pro default — confirm by searching
// events-calendar-pro for add_action( 'tribe_events_single_event_after_the_meta' )
add_action( 'wp', function() {
	if ( ! class_exists( 'Tribe__Events__Pro__Main' ) ) {
		return;
	}
	$main = Tribe__Events__Pro__Main::instance();
	remove_action(
		'tribe_events_single_event_after_the_meta',
		[ $main, 'register_related_events_view' ],
		10
	);
});

add_filter( 'tribe_ical_feed_posts_per_page', 'custom_increase_ical_feed_limit' );
function custom_increase_ical_feed_limit( $count ) {
	return 100; // Changes the limit from default to 100 events
}

/**
 * Include past events in The Events Calendar iCalendar subscription feeds.
 *
 * Change the "-2 years" value below to control how far back the feed goes.
 */
add_action( 'pre_get_posts', function ( $query ) {

	// Only affect front-end iCalendar / ICS feed requests.
	if (
		is_admin()
		|| ! $query->is_main_query()
		|| empty( $_GET['ical'] )
	) {
		return;
	}

	// Prevent TEC from treating the feed as upcoming-events-only.
	$query->set( 'eventDisplay', 'custom' );

	// Include events beginning two years ago.
	$query->set(
		'start_date',
		wp_date( 'Y-m-d H:i:s', strtotime( '-2 years', current_time( 'timestamp' ) ) )
	);

	// Increase the feed limit so past events do not crowd out future events.
	$query->set( 'posts_per_page', 500 );

}, 20 );

/**
 * TEC applies its own event-count limit to iCal feeds.
 */
add_filter( 'tribe_ical_feed_posts_per_page', function () {
	return 500;
} );