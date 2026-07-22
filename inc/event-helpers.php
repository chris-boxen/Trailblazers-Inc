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