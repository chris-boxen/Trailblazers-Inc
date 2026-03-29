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
Simplified all CPT rewrite slugs via ACF Post Types:
- `athletic_meet` → `meet`
- `athletic_season` → `season`
- `athletic_event` → `athletic-event` (kept prefix to avoid plugin conflicts)
- `athletic_result` → `result`
- `athletic_record` → `record`
- `athletic_physical` → `physical`
- `coach` → `coach` (explicitly set)

### Set sport taxonomy to hierarchical
Changed Sport taxonomy to hierarchical (category-style) to support future sub-sport or division structure.

### Registered Coach CPT with sport taxonomy
Added sport taxonomy to Coach CPT registration. Coaches are now directly queryable by sport via tax_query. Role and bio override per season remain managed via the `coach_roster` repeater on Athletic Season. Coaches can span multiple sports.

### Created dev seed data script
`public/scripts/seed-data.sh` — WP-CLI script that creates 2 families, 3 athletes, 1 season, 2 meets, 2 events, 3 enrollments, 6 results, and 2 PR records with correct ACF field key meta entries. Note: `wp post term set` requires slug not term ID.

### Split functions.php into inc/ structure
Moved Divi Projects filter to `inc/divi.php`. Added stub files for `inc/enqueue.php`, `inc/cpt-hooks.php`, `inc/query-mods.php`, `inc/acf-helpers.php`, and `inc/gravity-helpers.php`. `functions.php` is now a clean loader.

### Built single-athlete.php
PHP template. Displays athlete bio, season history, PR/SR records, and results grouped by Season → Meet. Cross-links to family, season, and meet pages.

### Built single-athletic_meet.php
PHP template. Displays meet header (name, date, location, season, status) and results grouped by event and sorted by place. Cross-links to athlete pages and season. Results display gated by `results_status` field (Future / Pending / Available). Empty `results_status` treated as Future.

### Built single-athletic_season.php
PHP template. Displays season header, coaches roster (from `coach_roster` repeater), meet schedule, and athlete roster (from Enrollments). Cross-links throughout.

### Built single-coach.php
PHP template. Displays coach photo, name, preferred title, and bio.

### Built single-athletic_event.php
PHP template. Displays event header (name, sport linked to taxonomy page, category, distance, measurement type), all-time records table, and results history grouped by Season → Meet → Results. Sports link to their taxonomy archive pages via get_term_link().

### Built taxonomy-sport.php
PHP template. Displays sport name and description, seasons list, coaches list (queried directly via sport taxonomy), and athletes table. Athletes table includes gender, graduation year, and account status columns. Rows have `data-gender` and `data-status` attributes for JavaScript filtering. Athletes and coaches sorted by last name.

### Built archive-athlete.php
PHP template. Public athlete archive. Table with name, gender, grad year, account status, and sport columns. Rows have `data-gender`, `data-grad-year`, `data-status`, and `data-sport` attributes for JS filtering. Sorted by last name.

### Built archive-athletic_meet.php
PHP template. Public meet archive. Table with meet name, date, location, season, and status columns. Rows have `data-season`, `data-sport`, `data-status`, `data-results-status`, and `data-year` attributes for JS filtering. Sorted by date descending.