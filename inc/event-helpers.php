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