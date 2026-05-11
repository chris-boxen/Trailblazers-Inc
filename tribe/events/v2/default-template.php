<?php
/**
 * Template: tribe/events/v2/default-template.php
 * Overrides TEC's default event template wrapper.
 *
 * For all tribe_events, TEC's normal view renders first (date, time, venue,
 * Add to Calendar, etc.). For athletic-meet events specifically, our custom
 * results section is appended after TEC's output via get_template_part().
 *
 * Override path documented in TEC's own default-template.php:
 *   [your-theme]/tribe/events/v2/default-template.php
 */

use Tribe\Events\Views\V2\Template_Bootstrap;

get_header();

// Always render TEC's normal event view.
echo tribe( Template_Bootstrap::class )->get_view_html();

// For athletic-meet events, append the results section below TEC's output.
$post_id = get_queried_object_id();

if ( is_singular( 'tribe_events' ) && has_term( 'athletic-meet', 'tribe_events_cat', $post_id ) ) {
	get_template_part( 'tribe/events/single-event' );
}

get_footer();