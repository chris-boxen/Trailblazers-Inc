<?php
/**
 * Template: archive-athletic_record.php
 * Displays the public Athletic Record archive.
 *
 * Structure: Sport → Event → Records table
 *
 * Data attributes on rows for JS filtering:
 *   data-record-type  — e.g. "pr", "sr"
 *   data-sport        — space-separated sport slugs
 *   data-event        — sanitized event title slug
 *   data-is-current   — "true" if this is the most recent record for this
 *                       athlete + event + record_type combination, else "false"
 *
 * "Current" is determined by meet date on the linked result — the most recent
 * date per athlete/event/record_type group is flagged as current. This allows
 * JS to filter between "all records ever set" and "current records only".
 *
 * Field references:
 *   group_tb_athletic_record.json — record fields; Athletic Event link field name: 'event'
 *   group_tb_athletic_event.json  — event name, sport taxonomy
 *   group_tb_athlete.json         — athlete name fields
 *   group_tb_athletic_result.json — result_display, meet link
 *
 * TEC field references (postmeta, not ACF):
 *   _EventStartDate — meet start datetime (format: Y-m-d H:i:s)
 */

get_header();

// -------------------------------------------------------------------------
// RECORDS — query all published records
// -------------------------------------------------------------------------
$records_query = new WP_Query( [
	'post_type'      => 'athletic_record',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
] );

// Final grouped structure: sport_slug => event_id => [ records ]
$records_by_sport = [];

// Metadata caches
$sport_meta = []; // sport_slug => [ name, url, sort_name ]
$event_meta = []; // event_id  => [ title, sort_title ]

// Intermediate flat list for current-record detection
$flat_records = [];

if ( $records_query->have_posts() ) {

	foreach ( $records_query->posts as $record ) {
		$athlete_id       = get_field( 'athlete', $record->ID );
		$event_id         = get_field( 'event', $record->ID );    // 'event' on athletic_record
		$linked_result_id = get_field( 'result', $record->ID );
		$record_type      = get_field( 'record_type', $record->ID );

		// Athlete name
		$first     = $athlete_id ? get_field( 'first_name', $athlete_id ) : '';
		$preferred = $athlete_id ? get_field( 'preferred_name', $athlete_id ) : '';
		$last      = $athlete_id ? get_field( 'last_name', $athlete_id ) : '';

		// Meet context via linked result
		// TEC date from _EventStartDate postmeta
		$meet_id      = $linked_result_id ? get_field( 'meet', $linked_result_id ) : null;
		$raw_date     = $meet_id ? get_post_meta( $meet_id, '_EventStartDate', true ) : '';
		$meet_date    = $raw_date ? date( 'Y-m-d', strtotime( $raw_date ) ) : '';
		$meet_date_ts = $meet_date ? strtotime( $meet_date ) : 0;

		// Sport terms from the linked Athletic Event
		$sport_terms = [];
		if ( $event_id ) {
			$raw_terms = get_the_terms( $event_id, 'sport' );
			if ( $raw_terms && ! is_wp_error( $raw_terms ) ) {
				$sport_terms = $raw_terms;
			}
		}

		// Default to 'no-sport' bucket if no terms
		$sport_keys = $sport_terms
			? wp_list_pluck( $sport_terms, 'slug' )
			: [ 'no-sport' ];

		// Cache sport metadata
		foreach ( $sport_terms as $sport ) {
			if ( ! isset( $sport_meta[ $sport->slug ] ) ) {
				$sport_url = get_term_link( $sport );
				$sport_meta[ $sport->slug ] = [
					'name'      => $sport->name,
					'url'       => ! is_wp_error( $sport_url ) ? $sport_url : '',
					'sort_name' => strtolower( $sport->name ),
				];
			}
		}
		if ( ! $sport_terms && ! isset( $sport_meta['no-sport'] ) ) {
			$sport_meta['no-sport'] = [
				'name'      => 'Uncategorised',
				'url'       => '',
				'sort_name' => 'zzz',
			];
		}

		// Cache event metadata
		if ( $event_id && ! isset( $event_meta[ $event_id ] ) ) {
			$event_title = get_field( 'event_name', $event_id ) ?: get_the_title( $event_id );
			$event_meta[ $event_id ] = [
				'title'      => $event_title,
				'sort_title' => strtolower( $event_title ),
			];
		}

		// Build record entry
		$entry = [
			'record_id'      => $record->ID,
			'record_type'    => $record_type,
			'athlete_id'     => $athlete_id,
			'athlete_name'   => trim( ( $preferred ?: $first ) . ' ' . $last ) ?: '—',
			'last_name'      => $last,
			'event_id'       => $event_id,
			'result_display' => $linked_result_id ? get_field( 'result_display', $linked_result_id ) : '—',
			'meet_id'        => $meet_id,
			'meet_name'      => $meet_id ? get_the_title( $meet_id ) : '—',
			'meet_date'      => $meet_date ? date_i18n( 'F j, Y', strtotime( $meet_date ) ) : '—',
			'meet_date_raw'  => $meet_date,
			'meet_date_ts'   => $meet_date_ts,
			'sport_keys'     => $sport_keys,
			'sport_slugs'    => implode( ' ', $sport_keys ),
			'is_current'     => false, // determined below
		];

		$flat_records[] = $entry;
	}

	// -------------------------------------------------------------------------
	// DETERMINE CURRENT RECORDS
	// For each athlete + event + record_type group, flag the most recent as current
	// -------------------------------------------------------------------------
	$group_max_ts = [];
	foreach ( $flat_records as $r ) {
		$group_key = ( $r['athlete_id'] ?? 'x' ) . '_' . ( $r['event_id'] ?? 'x' ) . '_' . strtolower( $r['record_type'] ?? 'x' );
		if ( ! isset( $group_max_ts[ $group_key ] ) || $r['meet_date_ts'] > $group_max_ts[ $group_key ] ) {
			$group_max_ts[ $group_key ] = $r['meet_date_ts'];
		}
	}

	foreach ( $flat_records as &$r ) {
		$group_key    = ( $r['athlete_id'] ?? 'x' ) . '_' . ( $r['event_id'] ?? 'x' ) . '_' . strtolower( $r['record_type'] ?? 'x' );
		$r['is_current'] = ( isset( $group_max_ts[ $group_key ] ) && $r['meet_date_ts'] === $group_max_ts[ $group_key ] );
	}
	unset( $r );

	// -------------------------------------------------------------------------
	// GROUP BY SPORT → EVENT
	// -------------------------------------------------------------------------
	foreach ( $flat_records as $entry ) {
		foreach ( $entry['sport_keys'] as $sport_slug ) {
			$event_id = $entry['event_id'] ?? 0;
			$records_by_sport[ $sport_slug ][ $event_id ][] = $entry;
		}
	}

	// Sort events within each sport by event title
	foreach ( $records_by_sport as $sport_slug => &$events ) {
		uksort( $events, function( $a, $b ) use ( $event_meta ) {
			$ta = $event_meta[ $a ]['sort_title'] ?? '';
			$tb = $event_meta[ $b ]['sort_title'] ?? '';
			return strcmp( $ta, $tb );
		} );
	}
	unset( $events );

	// Sort sports alphabetically
	uksort( $records_by_sport, function( $a, $b ) use ( $sport_meta ) {
		$sa = $sport_meta[ $a ]['sort_name'] ?? '';
		$sb = $sport_meta[ $b ]['sort_name'] ?? '';
		return strcmp( $sa, $sb );
	} );
}
wp_reset_postdata();

?>

<div class="tb-archive tb-record-archive">

	<header class="tb-archive-header">
		<h1>Records</h1>
	</header>

	<?php if ( empty( $records_by_sport ) ) : ?>
		<p class="tb-no-data">No records on file.</p>
	<?php else : ?>

		<?php foreach ( $records_by_sport as $sport_slug => $events_in_sport ) :
			$sport_info = $sport_meta[ $sport_slug ] ?? [ 'name' => 'Uncategorised', 'url' => '' ];
		?>

		<section class="tb-records-sport"
				 data-sport="<?php echo esc_attr( $sport_slug ); ?>">

			<h2 class="tb-sport-heading">
				<?php if ( $sport_info['url'] ) : ?>
					<a href="<?php echo esc_url( $sport_info['url'] ); ?>">
						<?php echo esc_html( $sport_info['name'] ); ?>
					</a>
				<?php else : ?>
					<?php echo esc_html( $sport_info['name'] ); ?>
				<?php endif; ?>
			</h2>

			<?php foreach ( $events_in_sport as $event_id => $event_records ) :
				$event_info  = $event_meta[ $event_id ] ?? [ 'title' => 'Unknown Event' ];
				$event_slug  = $event_id ? sanitize_title( $event_info['title'] ) : 'unknown';
				$event_url   = $event_id ? get_permalink( $event_id ) : '';
			?>

			<div class="tb-records-event"
				 data-event="<?php echo esc_attr( $event_slug ); ?>">

				<h3 class="tb-event-heading">
					<?php if ( $event_url ) : ?>
						<a href="<?php echo esc_url( $event_url ); ?>">
							<?php echo esc_html( $event_info['title'] ); ?>
						</a>
					<?php else : ?>
						<?php echo esc_html( $event_info['title'] ); ?>
					<?php endif; ?>
				</h3>

				<table class="tb-table">
					<thead>
						<tr>
							<th>Type</th>
							<th>Athlete</th>
							<th>Result</th>
							<th>Meet</th>
							<th>Date</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $event_records as $rec ) : ?>
						<tr data-record-type="<?php echo esc_attr( strtolower( $rec['record_type'] ) ); ?>"
							data-sport="<?php echo esc_attr( $rec['sport_slugs'] ); ?>"
							data-event="<?php echo esc_attr( $event_slug ); ?>"
							data-is-current="<?php echo $rec['is_current'] ? 'true' : 'false'; ?>">
							<td><?php echo esc_html( $rec['record_type'] ); ?></td>
							<td>
								<?php if ( $rec['athlete_id'] ) : ?>
									<a href="<?php echo esc_url( get_permalink( $rec['athlete_id'] ) ); ?>">
										<?php echo esc_html( $rec['athlete_name'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $rec['athlete_name'] ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $rec['result_display'] ); ?></td>
							<td>
								<?php if ( $rec['meet_id'] ) : ?>
									<a href="<?php echo esc_url( get_permalink( $rec['meet_id'] ) ); ?>">
										<?php echo esc_html( $rec['meet_name'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $rec['meet_name'] ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $rec['meet_date'] ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

			</div><!-- .tb-records-event -->

			<?php endforeach; ?>

		</section><!-- .tb-records-sport -->

		<?php endforeach; ?>

	<?php endif; ?>

</div><!-- .tb-record-archive -->

<?php get_footer(); ?>