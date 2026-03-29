# STATE

## Current Objective
Populate the site with live data (families, athletes, seasons, enrollments) using
WP Ultimate CSV Importer, then build the GravityForms registration flow against
real content. The public-facing template layer is complete. The GravityForms field
map is designed and parked in `docs/FORM-FIELD-MAP.md`.

## Current Repo Status
- Theme repo exists and is being tracked in GitHub.
- ACF Local JSON is active and saving into `acf-json/`.
- Original imported JSON files have been archived under `acf-json/_archived/`.
- ACF is now saving canonical files with the new naming pattern (e.g. `group_tb_*`).

## Current Development Environment
- Local is used for development.
- Site has been successfully exported/re-imported into Dropbox.
- Git tracks the theme folder only.
- Dropbox is being used as an external backup layer.

## Confirmed Architecture
### Core CPTs
- Family
- Athlete
- Coach
- Athletic Season
- Athletic Meet
- Athletic Event
- Athletic Result
- Athletic Record
- Athletic Physical
- Application
- Enrollment

### Taxonomies
- Sport (hierarchical — behaves like a category)
- Registered on: Athletic Season, Athletic Meet, Athletic Event, Athletic Result,
  Athletic Record, Enrollment, Coach, Athlete

## Confirmed CPT URL Slugs
- athlete → `/athlete/`
- coach → `/coach/`
- family → `/family/`
- enrollment → `/enrollment/`
- athletic_season → `/season/`
- athletic_meet → `/meet/`
- athletic_event → `/athletic-event/`
- athletic_result → `/result/`
- athletic_record → `/record/`
- athletic_physical → `/physical/`

## Confirmed Conceptual Model
- Family = household/account context
- Athlete = person/participant
- Application = family-season submission
- Enrollment = athlete-season operational record
- Athletic Physical = medical/compliance record
- Athletic Result = athlete performance at a meet/event
- Athletic Record = stored PR/SR/achievement layer linked to a result
- Athletic Season = seasonal hub
- Athletic Meet = meet-level event container
- Athletic Event = canonical event definition

## Confirmed Modeling Decisions
- ACF Local JSON is the schema source of truth.
- Enrollment is the athlete-season operational hub.
- Physical remains separate from Enrollment because one physical may span multiple seasons.
- Family does not maintain a mirrored Athlete relationship field as source of truth.
- Season no longer stores athlete roster rows; athlete-season participation is modeled
  through Enrollment.
- Participation Type belongs on Enrollment (source of truth) and is denormalized onto
  Athlete for archive filtering. See SCHEMA.md for rules and empty-value handling.
- Sport is implemented as a hierarchical taxonomy (category-style).
- Coach is registered with the sport taxonomy for direct sport-based querying. Role and
  bio override per season are managed via the `coach_roster` repeater on Athletic Season.
- Coaches can span multiple sports; roles are season/sport-specific and stored on the season.
- Event-specific labels on results remain free text where needed (`event_name`) while
  canonical event structure lives in Athletic Event.
- Stored records linked to source results are preferred over fully computing every PR/SR
  on demand.
- Athletic Result normalization is resolved: `result_display` for human-readable output
  plus type-specific normalized numeric fields (`result_time_seconds`,
  `result_distance_meters`, `result_height_meters`, `result_points`).
- `results_status` on Athletic Meet gates whether results are displayed
  (Future / Pending / Available). Empty field treated as Future.
- All CPT templates are PHP files. Divi Theme Builder is not used for any CPT templates.
- "Current" record detection: for each athlete + event + record_type group, the record
  with the most recent meet date is flagged `is_current = true`. Derived at render time
  from the linked result's meet date.
- Family, Application, Enrollment, and Athletic Physical are admin-only data objects.
  No public-facing templates. See OPEN-QUESTIONS.md Q3.
- `account_status` on Athlete (Active / Inactive / Alumni / Archived) tracks lifecycle.
  `participation_type` on Athlete (Athlete / Sibling Runner) is a denormalized snapshot
  of enrollment type for filtering. These are distinct fields answering different
  questions — see SCHEMA.md.

## Theme Structure
- `functions.php` — clean loader only; requires all inc/ files
- `inc/divi.php` — Divi-specific filters
- `inc/enqueue.php` — scripts and styles (stub)
- `inc/cpt-hooks.php` — CPT/taxonomy behavior modifications (stub)
- `inc/query-mods.php` — pre_get_posts and archive query adjustments (stub)
- `inc/acf-helpers.php` — reusable ACF utility functions (stub)
- `inc/gravity-helpers.php` — Gravity Forms / GravityPerks helpers (stub)

## ACF Schema — Current Canonical Field Groups
Key changes from initial import:
- `group_tb_athlete.json` — added `participation_type` (select: Athlete / Sibling Runner,
  allow null, set by enrollment hook)
- `group_tb_application.json` — added `payment_amount` (number, step 0.01, set by hook
  from GF order total)

## GravityForms Registration — Design Status
Form architecture is fully designed. Field map is in `docs/FORM-FIELD-MAP.md`.

**Form set:**
- Registration entry page — WP page with two buttons (New / Returning). Not a form.
- `2026 Registration — New Family` — 5 pages
- `2026 Registration — Returning Family` — 5 pages
- `Nested: 2026 Register Athlete` — combined Athlete + Sibling Runner

**Build sequence (not yet started):**
1. Populate live data (WP Ultimate CSV Importer) — current priority
2. Build forms in GF admin against real data
3. Write hooks in `inc/gravity-helpers.php` against real GF field IDs

## Seed Data
Dev seed data exists in `public/scripts/seed-data.sh`. Creates:
- Sport term: Cross Country (ID: 2)
- Season: Cross Country 2025 (ID: 169)
- Events: 1 Mile (ID: 170), 5K (ID: 171)
- Meets: Autumn Opener (ID: 172), Harvest Classic (ID: 173)
- Families: Nguyen (ID: 174), Okafor (ID: 175)
- Athletes: Maya Nguyen (ID: 176), Leo Nguyen (ID: 177), Adaeze Okafor (ID: 178)
- Enrollments: IDs 179, 180, 181
- Results: IDs 182–187
- Records: Maya PR (ID: 188), Adaeze PR (ID: 189)
- Note: 2 coaches added manually (not in seed script)

## Current Template Status
- This is a Divi 5 child theme. Divi provides all baseline fallback templates.
- `index.php` exists in the child theme to satisfy WordPress theme validity requirement.
- All CPT templates are built as PHP files.

### Built — all complete
- `single-athlete.php` — bio, season history, PRs, results grouped by Season → Meet.
- `single-athletic_meet.php` — meet header, results grouped by event sorted by place.
  Gated by `results_status`.
- `single-athletic_season.php` — season header, coaches roster, meet schedule, athlete
  roster.
- `single-coach.php` — photo, name, title, bio.
- `single-athletic_event.php` — event header with linked sport taxonomy, records, results
  grouped by Season → Meet.
- `taxonomy-sport.php` — sport header, seasons, coaches, athletes table with filtering
  attributes.
- `archive-athlete.php` — all athletes, sortable/filterable table with data attributes.
- `archive-athletic_meet.php` — all meets, sortable/filterable table with data attributes.
- `archive-athletic_record.php` — Sport → Event → Records. Data attributes for JS
  filtering including `data-is-current`.

### Not yet built
- JS filtering (data attributes are wired; filter controls and script not yet added)
- No additional templates planned

## Current Risks / Watchouts
- ACF schema changes can create DB/JSON drift if Git branches are switched carelessly.
- Theme folder name currently contains a space; consider changing to a slug-style folder
  name later.
- Divi Theme Builder can silently override PHP templates — check Theme Builder assignments
  when a template appears blank.
- Hierarchical sport taxonomy: use `'include_children' => false` in `tax_query` when
  exact-term matching is needed.
- Archive templates require `has_archive => true` on the CPT — verify in ACF Post Types
  if an archive URL 404s.
- Results on `single-athletic_event.php` query via the `event` post object field. Results
  with only a free-text `event_name` won't appear — they still show on athlete and meet
  pages.
- Current record detection is derived at render time from meet date. If two records for
  the same athlete/event/type share the same meet date, both will be flagged as current.
- `Athlete.participation_type` is a denormalized field — empty values (manually created
  posts, CSV imports) must be treated as `Athlete` in templates and JS filters.