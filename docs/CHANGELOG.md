# CHANGELOG

## 2026-03
### Established initial content architecture
Defined the core CPT model for Family, Athlete, Season, Application, Enrollment, Physical, Result, and Record.

### Moved athlete-season participation into Enrollment
Removed athlete roster duplication from Season and made Enrollment the athlete-season operational hub.

### Removed mirrored Family → Athletes relationship
Kept Athlete → Family as the primary relationship direction to avoid duplicate maintenance.

### Converted sport into a taxonomy
Chose Sport as a taxonomy rather than only a select field to support filtering across multiple content types.

### Separated Physical from Enrollment
Kept physicals as independent records because a single physical may span more than one season.

### Added ACF Local JSON package
Imported CPTs, taxonomy, and field groups into ACF and confirmed local JSON saving is working.

### Archived original imported JSON files
Moved original imported JSON into `acf-json/_archived/2026-03-21-initial-import` and allowed ACF to own active canonical filenames going forward.

### Confirmed all CPT templates as PHP files
Decision made to build all CPT templates as PHP files rather than mixing PHP and Divi Theme Builder. Theme Builder can be used later to replace any template if preferred.

### Updated CPT URL slugs
Simplified all CPT rewrite slugs via ACF Post Types.

### Set sport taxonomy to hierarchical
Changed Sport taxonomy to hierarchical (category-style) to support future sub-sport or division structure.

### Registered Coach CPT with sport taxonomy
Added sport taxonomy to Coach CPT registration. Coaches are now directly queryable by sport via tax_query. Role and bio override per season remain managed via the `coach_roster` repeater on Athletic Season. Coaches can span multiple sports.

### Created dev seed data script
`public/scripts/seed-data.sh` — WP-CLI script. Note: `wp post term set` requires slug not term ID.

### Split functions.php into inc/ structure
Moved Divi Projects filter to `inc/divi.php`. Added stub files for `inc/enqueue.php`, `inc/cpt-hooks.php`, `inc/query-mods.php`, `inc/acf-helpers.php`, and `inc/gravity-helpers.php`. `functions.php` is now a clean loader.

### Built single-athlete.php
PHP template. Bio, season history, PR/SR records, results grouped by Season → Meet. Cross-links throughout.

### Built single-athletic_meet.php
PHP template. Meet header, results grouped by event sorted by place. Gated by `results_status`. Cross-links to athletes and season.

### Built single-athletic_season.php
PHP template. Season header, coaches roster (from repeater), meet schedule, athlete roster. Cross-links throughout.

### Built single-coach.php
PHP template. Photo, name, preferred title, bio.

### Built single-athletic_event.php
PHP template. Event header (name, sport linked to taxonomy, category, distance, measurement), all-time records, results grouped by Season → Meet. Sports link via get_term_link().

### Built taxonomy-sport.php
PHP template. Sport header, seasons, coaches (queried via sport taxonomy), athletes table with gender/grad year/status columns and data attributes for JS filtering.

### Built archive-athlete.php
PHP template. All athletes. Table with name, gender, grad year, account status, sport. Data attributes: data-gender, data-grad-year, data-status, data-sport. Sorted by last name.

### Built archive-athletic_meet.php
PHP template. All meets. Table with name, date, location, season, status. Data attributes: data-season, data-sport, data-status, data-results-status, data-year. Sorted by date descending.

### Built archive-athletic_record.php
PHP template. Structure: Sport → Event → Records table. Data attributes: data-record-type, data-sport, data-event, data-is-current. Current record detection derived from most recent meet date per athlete/event/record_type group. Sport headings link to taxonomy archive. Event headings link to event pages.