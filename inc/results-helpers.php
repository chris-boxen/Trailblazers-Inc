<?php
/**
 * inc/results-helpers.php
 * Athletic result utilities: derived field sync and admin tools.
 *
 * Functions:
 *   tb_parse_result_time_seconds( $display )
 *       Parses MM:SS.ss or H:MM:SS.ss → float seconds. Returns 0 if
 *       unparseable.
 *
 *   tb_run_result_times_sync()
 *       Finds all athletic_result posts with empty result_time_seconds,
 *       calculates from result_display, writes via update_field().
 *       Returns [ 'updated' => int, 'skipped' => int ].
 *       Called by the admin_init handler; available to other callers.
 *
 * Hooks:
 *   acf/save_post (priority 20)
 *       Derives result_time_seconds on WP admin saves of athletic_result.
 *       Does not fire during WPUCI imports — use the sync tool after import.
 *
 *   admin_init
 *       Catches POST from the sync button (Tools page or dashboard widget),
 *       runs tb_run_result_times_sync(), redirects back to the referer with
 *       result params: tb_sync_done, tb_sync_updated, tb_sync_skipped.
 *
 * Admin pages:
 *   Tools → Sync Result Times
 *       Standalone sync page. The 📊 Results dashboard widget also provides
 *       a sync button and reads the same redirect params for its notice.
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
// 2. Sync executor
// ---------------------------------------------------------------------------

/**
 * Find all athletic_result posts with empty result_time_seconds, derive the
 * value from result_display, and write it via update_field().
 *
 * Uses get_post_meta() to read result_display directly, bypassing ACF shadow
 * key resolution — safe for a plain text field and necessary because shadow
 * keys may be absent on freshly-imported posts.
 *
 * @return array { int $updated, int $skipped }
 */
function tb_run_result_times_sync(): array {
	$updated = 0;
	$skipped = 0;

	$missing = get_posts( [
		'post_type'      => 'athletic_result',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [ [
			'key'     => 'result_time_seconds',
			'compare' => 'NOT EXISTS',
		] ],
	] );

	$empty = get_posts( [
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

	$ids = array_unique( array_merge( $missing, $empty ) );

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

	return [ 'updated' => $updated, 'skipped' => $skipped ];
}


// ---------------------------------------------------------------------------
// 3. ACF save hook: sync on WP admin saves
// ---------------------------------------------------------------------------

add_action( 'acf/save_post', 'tb_sync_result_time_seconds', 20 );

/**
 * On ACF save of an athletic_result, write result_time_seconds from
 * result_display. Fires only through the WP admin save pipeline.
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
// 4. admin_init POST handler
// ---------------------------------------------------------------------------

add_action( 'admin_init', function() {
	if ( ! isset( $_POST['tb_sync_result_times'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	check_admin_referer( 'tb_sync_result_times_action', 'tb_sync_result_times_nonce' );

	$result = tb_run_result_times_sync();

	$redirect = wp_get_referer() ?: admin_url( 'index.php' );
	$redirect = add_query_arg( [
		'tb_sync_done'    => 1,
		'tb_sync_updated' => $result['updated'],
		'tb_sync_skipped' => $result['skipped'],
	], $redirect );

	wp_safe_redirect( $redirect );
	exit;
} );


// ---------------------------------------------------------------------------
// 5. Tools page: Sync Result Times
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
 * Render the Tools → Sync Result Times page.
 * POST is handled by the admin_init hook above; this page only displays state
 * and the button, and reads redirect params for the success notice.
 */
function tb_sync_result_times_page(): void {

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

	$did_sync = isset( $_GET['tb_sync_done'] );
	$updated  = $did_sync ? (int) ( $_GET['tb_sync_updated'] ?? 0 ) : 0;
	$skipped  = $did_sync ? (int) ( $_GET['tb_sync_skipped'] ?? 0 ) : 0;

	?>
	<div class="wrap">
		<h1>Sync Result Times</h1>
		<p>
			Calculates <code>result_time_seconds</code> from <code>result_display</code>
			for all Athletic Result posts where it is missing. Run this after every
			CSV import via WPUCI.
		</p>

		<?php if ( $did_sync ) : ?>
			<div class="notice notice-success is-dismissible">
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

		<?php if ( $needs_sync > 0 ) : ?>
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