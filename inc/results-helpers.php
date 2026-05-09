<?php
/**
 * inc/results-helpers.php
 * Athletic result utilities: derived field sync and admin tools.
 *
 * Functions:
 *   tb_parse_result_time_seconds( $display )
 *       Parses a result_display string (MM:SS.ss or H:MM:SS.ss) and returns
 *       the equivalent float in seconds, or 0 if unparseable.
 *
 * Hooks:
 *   acf/save_post (priority 20)
 *       On save of any athletic_result post via the WP admin, derives
 *       result_time_seconds from result_display and writes it directly.
 *       Does not fire during CSV imports (importers bypass the ACF save
 *       pipeline). Use the Tools → Sync Result Times page after each import.
 *
 * Admin pages:
 *   Tools → Sync Result Times
 *       Finds all athletic_result posts where result_time_seconds is empty,
 *       calculates the value from result_display, and writes it directly.
 *       Use this after every WPUCI import.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// ---------------------------------------------------------------------------
// 1. Shared helper: parse result_display → seconds
// ---------------------------------------------------------------------------

/**
 * Parse a result_display string into total seconds as a float.
 *
 * Supports MM:SS.ss (e.g. "19:55.00") and H:MM:SS.ss (e.g. "1:02:30.5").
 * Returns 0 if the string is empty or cannot be parsed.
 *
 * @param string $display
 * @return float
 */
function tb_parse_result_time_seconds( string $display ): float {
	$parts = explode( ':', trim( $display ) );

	if ( count( $parts ) === 2 ) {
		// MM:SS.ss
		return ( (int) $parts[0] * 60 ) + (float) $parts[1];
	}

	if ( count( $parts ) === 3 ) {
		// H:MM:SS.ss
		return ( (int) $parts[0] * 3600 ) + ( (int) $parts[1] * 60 ) + (float) $parts[2];
	}

	return 0;
}


// ---------------------------------------------------------------------------
// 2. ACF save hook: derive result_time_seconds on WP admin saves
// ---------------------------------------------------------------------------

add_action( 'acf/save_post', 'tb_sync_result_time_seconds', 20 );

/**
 * On ACF save of an athletic_result, write result_time_seconds from result_display.
 * Fires only through the WP admin save pipeline, not during CSV imports.
 *
 * @param int|string $post_id
 */
function tb_sync_result_time_seconds( $post_id ): void {
	if ( get_post_type( $post_id ) !== 'athletic_result' ) return;

	$display = get_field( 'result_display', $post_id );
	if ( ! $display ) return;

	$seconds = tb_parse_result_time_seconds( $display );
	if ( $seconds > 0 ) {
		update_field( 'result_time_seconds', round( $seconds, 2 ), $post_id );
	}
}


// ---------------------------------------------------------------------------
// 3. Admin Tools page: Sync Result Times
// ---------------------------------------------------------------------------

add_action( 'admin_menu', function() {
	add_management_page(
		'Sync Result Times',
		'Sync Result Times',
		'manage_options',
		'tb-sync-result-times',
		'tb_sync_result_times_page'
	);
} );

/**
 * Render the Tools → Sync Result Times admin page.
 * Handles both the display state and the POST action.
 */
function tb_sync_result_times_page(): void {

	// --- Run sync if form was submitted ---
	$updated = 0;
	$skipped = 0;
	$ran     = false;

	if (
		isset( $_POST['tb_sync_result_times'] ) &&
		check_admin_referer( 'tb_sync_result_times_action', 'tb_sync_result_times_nonce' )
	) {
		$ran = true;

		$posts = get_posts( [
			'post_type'      => 'athletic_result',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [ [
				'key'     => 'result_time_seconds',
				'compare' => 'NOT EXISTS',
			] ],
		] );

		// Also catch posts where the field exists but is empty
		$posts_empty = get_posts( [
			'post_type'      => 'athletic_result',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [ [
				'key'     => 'result_time_seconds',
				'value'   => '',
				'compare' => '=',
			] ],
		] );

		$ids = array_unique( array_merge( $posts, $posts_empty ) );

		foreach ( $ids as $post_id ) {
			$display = get_post_meta( $post_id, 'result_display', true );
			if ( ! $display ) {
				$skipped++;
				continue;
			}
			$seconds = tb_parse_result_time_seconds( $display );
			if ( $seconds > 0 ) {
				update_field( 'result_time_seconds', round( $seconds, 2 ), $post_id );
				$updated++;
			} else {
				$skipped++;
			}
		}
	}

	// --- Count posts still needing sync (for display) ---
	$needs_sync = count( get_posts( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [ [
			'key'     => 'result_time_seconds',
			'compare' => 'NOT EXISTS',
		] ],
	] ) );

	$needs_sync += count( get_posts( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [ [
			'key'     => 'result_time_seconds',
			'value'   => '',
			'compare' => '=',
		] ],
	] ) );

	?>
	<div class="wrap">
		<h1>Sync Result Times</h1>
		<p>
			Calculates <code>result_time_seconds</code> from <code>result_display</code>
			for all Athletic Result posts where it is missing. Run this after every
			CSV import via WPUCI.
		</p>

		<?php if ( $ran ) : ?>
			<div class="notice notice-success">
				<p>
					<strong>Sync complete.</strong>
					<?php echo esc_html( $updated ); ?> result<?php echo $updated !== 1 ? 's' : ''; ?> updated.
					<?php if ( $skipped > 0 ) : ?>
						<?php echo esc_html( $skipped ); ?> skipped (no result_display value).
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th>Results needing sync</th>
				<td>
					<strong><?php echo esc_html( $needs_sync ); ?></strong>
					<?php if ( $needs_sync === 0 ) : ?>
						<span style="color:#46b450;">&#10003; All results are in sync.</span>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php if ( $needs_sync > 0 || ! $ran ) : ?>
			<form method="post">
				<?php wp_nonce_field( 'tb_sync_result_times_action', 'tb_sync_result_times_nonce' ); ?>
				<p>
					<button type="submit" name="tb_sync_result_times" class="button button-primary">
						Sync Result Times
					</button>
				</p>
			</form>
		<?php endif; ?>
	</div>
	<?php
}