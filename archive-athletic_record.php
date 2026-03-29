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
 *   group_tb_athletic_record.json — record fields
 *   group_tb_athletic_event.json  — event name, sport taxonomy
 *   group_tb_athlete.json         — athlete name fields
 *   group_tb_athletic_result.json — result_display, meet link
 *   group_tb_athletic_meet.json   — meet name, date
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
		$event_id         = get_field( 'event', $record->ID );
		$linked_result_id = get_field( 'result', $record->ID );
		$record_type      = get_field( 'record_type', $record->ID );

		// Athlete name
		$first     = $athlete_id ? get_field( 'first_name', $athlete_id ) : '';
		$preferred = $athlete_id ? get_field( 'preferred_name', $athlete_id ) : '';
		$last      = $athlete_id ? get_field( 'last_name', $athlete_id ) : '';

		// Meet context via linked result
		$meet_id      = $linked_result_id ? get_field( 'meet', $linked_result_id ) : null;
		$meet_date    = $meet_id ? get_field( 'date', $meet_id ) : '';
		$meet_date_ts = $meet_date ? strtotime( $meet_date ) : 0;

		// Sport terms from the linked event
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
			'meet_name'      => $meet_id ? get_field( 'meet_name', $meet_id ) : '—',
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
		$group_key       = ( $r['athlete_id'] ?? 'x' ) . '_' . ( $r['event_id'] ?? 'x' ) . '_' . strtolower( $r['record_type'] ?? 'x' );
		$r['is_current'] = ( $r['meet_date_ts'] === ( $group_max_ts[ $group_key ] ?? null ) );
	}
	unset( $r );

	// -------------------------------------------------------------------------
	// GROUP INTO sport_slug => event_id => [ records ]
	// A record may appear in multiple sport buckets if the event has multiple sports
	// -------------------------------------------------------------------------
	foreach ( $flat_records as $r ) {
		foreach ( $r['sport_keys'] as $sport_slug ) {
			$event_key = $r['event_id'] ?? 0;
			$records_by_sport[ $sport_slug ][ $event_key ][] = $r;
		}
	}

	// Sort records within each event: record_type asc, then last name asc
	foreach ( $records_by_sport as $sport_slug => &$events ) {
		foreach ( $events as $eid => &$event_records ) {
			usort( $event_records, function( $a, $b ) {
				$type_cmp = strcmp( $a['record_type'], $b['record_type'] );
				return $type_cmp !== 0 ? $type_cmp : strcmp( $a['last_name'], $b['last_name'] );
			} );
		}
		unset( $event_records );

		// Sort events alphabetically within sport
		uksort( $events, function( $a, $b ) use ( $event_meta ) {
			return strcmp(
				$event_meta[ $a ]['sort_title'] ?? 'zzz',
				$event_meta[ $b ]['sort_title'] ?? 'zzz'
			);
		} );
	}
	unset( $events );

	// Sort sports alphabetically
	uksort( $records_by_sport, function( $a, $b ) use ( $sport_meta ) {
		return strcmp(
			$sport_meta[ $a ]['sort_name'] ?? 'zzz',
			$sport_meta[ $b ]['sort_name'] ?? 'zzz'
		);
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

		<?php foreach ( $records_by_sport as $sport_slug => $events ) :
			$sport = $sport_meta[ $sport_slug ] ?? [ 'name' => 'Uncategorised', 'url' => '' ];
		?>

		<div class="tb-records-sport">

			<h2 class="tb-sport-label">
				<?php if ( $sport['url'] ) : ?>
					<a href="<?php echo esc_url( $sport['url'] ); ?>">
						<?php echo esc_html( $sport['name'] ); ?>
					</a>
				<?php else : ?>
					<?php echo esc_html( $sport['name'] ); ?>
				<?php endif; ?>
			</h2>

			<?php foreach ( $events as $event_key => $event_records ) :
				$event_title = $event_meta[ $event_key ]['title'] ?? 'Unknown Event';
				$event_id    = $event_key ?: null;
			?>

			<div class="tb-records-event">

				<h3 class="tb-event-label">
					<?php if ( $event_id ) : ?>
						<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
							<?php echo esc_html( $event_title ); ?>
						</a>
					<?php else : ?>
						<?php echo esc_html( $event_title ); ?>
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
						<?php foreach ( $event_records as $r ) :
							$type_attr    = $r['record_type'] ? strtolower( $r['record_type'] ) : 'unknown';
							$current_attr = $r['is_current'] ? 'true' : 'false';
							$event_attr   = sanitize_title( $event_title );
						?>
						<tr class="tb-record-row"
							data-record-type="<?php echo esc_attr( $type_attr ); ?>"
							data-sport="<?php echo esc_attr( $r['sport_slugs'] ); ?>"
							data-event="<?php echo esc_attr( $event_attr ); ?>"
							data-is-current="<?php echo esc_attr( $current_attr ); ?>">
							<td><?php echo esc_html( $r['record_type'] ?: '—' ); ?></td>
							<td>
								<?php if ( $r['athlete_id'] ) : ?>
									<a href="<?php echo esc_url( get_permalink( $r['athlete_id'] ) ); ?>">
										<?php echo esc_html( $r['athlete_name'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $r['athlete_name'] ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $r['result_display'] ); ?></td>
							<td>
								<?php if ( $r['meet_id'] ) : ?>
									<a href="<?php echo esc_url( get_permalink( $r['meet_id'] ) ); ?>">
										<?php echo esc_html( $r['meet_name'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $r['meet_name'] ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $r['meet_date'] ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

			</div><!-- .tb-records-event -->

			<?php endforeach; ?>

		</div><!-- .tb-records-sport -->

		<?php endforeach; ?>

	<?php endif; ?>

</div><!-- .tb-record-archive -->

<?php get_footer(); ?>