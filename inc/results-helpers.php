<?php
/**
 * inc/results-helpers.php
 * Athletic result utilities: derived field sync and data integrity hooks.
 *
 * Hooks:
 *   acf/save_post (priority 20)
 *       On save of any athletic_result post, derives result_time_seconds
 *       from result_display (the canonical human-readable time string).
 *       Parses MM:SS.ss and H:MM:SS.ss formats. Stores as a float rounded
 *       to two decimal places (e.g. 19:55.00 → 1195.0, 17:05.1 → 1025.1).
 *       result_display is the single source of truth; result_time_seconds
 *       should never be edited directly.
 */


// ---------------------------------------------------------------------------
// 1. Derive result_time_seconds from result_display on save
// ---------------------------------------------------------------------------

add_action( 'acf/save_post', 'tb_sync_result_time_seconds', 20 );

/**
 * Parses result_display and writes result_time_seconds on athletic_result posts.
 *
 * @param int|string $post_id
 */
function tb_sync_result_time_seconds( $post_id ) {
	if ( get_post_type( $post_id ) !== 'athletic_result' ) return;

	$display = get_field( 'result_display', $post_id );
	if ( ! $display ) return;

	$parts   = explode( ':', trim( $display ) );
	$seconds = 0;

	if ( count( $parts ) === 2 ) {
		// MM:SS.ss
		$seconds = ( (int) $parts[0] * 60 ) + (float) $parts[1];
	} elseif ( count( $parts ) === 3 ) {
		// H:MM:SS.ss
		$seconds = ( (int) $parts[0] * 3600 ) + ( (int) $parts[1] * 60 ) + (float) $parts[2];
	}

	if ( $seconds > 0 ) {
		update_field( 'result_time_seconds', round( $seconds, 2 ), $post_id );
	}
}