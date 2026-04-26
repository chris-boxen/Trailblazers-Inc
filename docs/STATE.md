# STATE

## Current Objective
Data population is substantially complete. Athletic Results and Athletic Records
remain to be imported. After that, the GravityForms registration build begins.

## Current Repo Status
- Theme repo exists and is being tracked in GitHub.
- ACF Local JSON is active and saving into `acf-json/`.
- All schema changes from the TEC integration are committed.
- ACF Group wrapper removed from Enrollment post object fields (season, family,
  athlete, application) — required for WP Ultimate CSV Importer to resolve
  post object relationships correctly. See SCHEMA.md and CHANGELOG.md.
- Registration infrastructure complete. See CHANGELOG.md 2026-04.

## Completed — Data Population
The following have been successfully imported:

1. ✅ Sport taxonomy terms
2. ✅ Athletic Seasons
3. ✅ Athletic Events
4. ✅ TEC Venues (via WordPress XML importer)
5. ✅ TEC Events / Meets (via TEC CSV importer)
6. ✅ Families
7. ✅ Athletes
8. ✅ Applications (2024 XC and 2025 XC)
9. ✅ Enrollments (2025 XC and 2026 TF)

## Remaining — Data Population
10. ⬜ Athletic Results
11. ⬜ Athletic Records

## Current Development Environment
- Local is used for development.
- Dropbox is used as external backup.
- GitHub tracks the theme folder only.
- The Events Calendar Pro is installed and active.
- ACF Local JSON is active and synced.

## Confirmed Architecture
### Core CPTs
- Family
- Athlete
- Coach
- Athletic Season
- Athletic Meet (RETIRED — replaced by `tribe_events`)
- Athletic Event
- Athletic Result
- Athletic Record
- Athletic Physical
- Application
- Enrollment

### TEC-Managed Post Types
- `tribe_events` — Meet anchor post. ACF field group `group_tb_athletic_meet`
  attaches `season` and `results_status`.
- `tribe_venue` — Venue record.
- `tribe_events_cat` — Event categories: `athletic-meet`, `practice`,
  `team-event`, `community-run`.

### Taxonomies
- Sport (hierarchical) registered on: Athletic Season, Athletic Event,
  Athletic Result, Athletic Record, Enrollment, Athlete, Coach

## Confirmed CPT URL Slugs
- athlete → `/athlete/`
- coach → `/coach/`
- family → `/family/`
- enrollment → `/enrollment/`
- athletic_season → `/season/`
- tribe_events → `/event/`
- athletic_event → `/athletic-event/`
- athletic_result → `/result/`
- athletic_record → `/record/`
- athletic_physical → `/physical/`

## Confirmed Conceptual Model
- Family = household/account context
- Athlete = person/participant
- Application = family-season submission
- Enrollment = athlete-season operational record (connects Athlete + Family + Season + Application)
- Athletic Physical = medical/compliance record
- Athletic Result = athlete performance at an event/meet
- Athletic Record = stored PR/SR/achievement layer linked to a result
- Athletic Season = seasonal hub (carries per-season feature flags)
- `tribe_events` = meet instance (public calendar + internal data anchor)
- `tribe_venue` = reusable venue record
- Athletic Event = canonical event definition (5K, 100m, Long Jump, etc.)

## Confirmed Modeling Decisions
- ACF Local JSON is the schema source of truth.
- Enrollment is the athlete-season operational hub.
- Physical remains separate from Enrollment.
- `tribe_events` is the anchor post for all meet data. `athletic_meet` is retired.
- All `athletic_result` posts link to a `tribe_events` post via the `meet` field.
- `tribe_events` posts are always published. `calendar_show_meets` season flag
  controls public surfacing.
- TEC's native `/event/` archive to be redirected to a custom query page.
- Meet results display lives in `tribe/events/single-event.php` (to build).
- Season `results_enabled` flag controls display of results in templates.
- `results_status` on `tribe_events` gates per-meet results display.
- Milesplit and AthleticNet IDs live on Athlete. Season flags control rendering.
- ACF Post Object fields used in CSV imports must NOT be nested inside ACF Group
  fields. WP Ultimate CSV Importer does not reliably resolve post object
  relationships when the field is inside a Group wrapper.
- Registration system uses a centralized ACF options page (`registration-settings`).
  Permanent WP pages consume shortcodes. No seasonal page duplication. Season
  changeover requires updating only the options page + building new GF forms.
  See CHANGELOG.md 2026-04.

## Template Status
### Active PHP templates
- `single-athlete.php` — needs update: flag checks + external ID links
- `single-athletic_season.php` — updated for tribe_events
- `single-athletic_event.php` — needs update: flag checks
- `archive-athlete.php` — no change needed now
- `archive-athletic_record.php` — updated for tribe_events
- `taxonomy-sport.php` — needs update: tribe_events query
- `tribe/events/single-event.php` — TO BUILD

### Archived templates
- `single-athletic_meet.php` → `_archived-templates/`
- `archive-athletic_meet.php` → `_archived-templates/`

## ACF Schema — Current Canonical Field Groups
- `group_tb_athlete.json` — includes `participation_type`
- `group_tb_application.json` — includes `payment_amount`
- `group_tb_athletic_result.json` — `meet` targets `tribe_events`;
  `athletic_event` field (renamed from `event`) targets `athletic_event`
- `group_tb_athletic_season.json` — includes season flags in `customize_data`
  group; dates in `Dates` group
- `group_tb_athletic_meet.json` — attached to `tribe_events`; contains
  `season` and `results_status` (file key retained as `group_tb_athletic_meet`
  for historical reasons)
- `group_tb_enrollment.json` — post object fields (season, family, athlete,
  application) are TOP-LEVEL, not inside any Group wrapper
- `group_tb_registration_settings.json` — ACF options page field group;
  location: `options_page == registration-settings`

## ACF Options Pages
- `options_page_trailblazers-settings.json` — top-level TB Settings parent menu
- `options_page_registration-settings.json` — Registration Settings sub-page

## inc/ File Inventory
- `inc/divi.php` — Divi child theme helpers
- `inc/enqueue.php` — script/style enqueuing (stub)
- `inc/cpt-hooks.php` — CPT/taxonomy admin hooks
- `inc/query-mods.php` — pre_get_posts modifications
- `inc/acf-helpers.php` — ACF utility functions
- `inc/gravity-helpers.php` — GravityForms/GravityPerks hooks
- `inc/registration-helpers.php` — registration options page sync + shortcodes

## GravityForms Registration — Design Status
Designed. Field map in `docs/FORM-FIELD-MAP.md`. Build begins after data population
is complete.

## Registration Infrastructure — Build Status
Complete as of 2026-04-20. See CHANGELOG.md 2026-04 for full record.
- ✅ ACF options pages created (Trailblazers Settings + Registration Settings sub-page)
- ✅ ACF field group `group_tb_registration_settings` built and synced (16 fields)
- ✅ `inc/registration-helpers.php` created and loaded via `functions.php`
- ✅ Five permanent WP registration pages created with shortcodes
- ✅ System verified: coming soon state rendering correctly on all pages
- ✅ `tb_active_season_id` sync hook confirmed working (2026 XC auto-populated)
- ⬜ GF form IDs — to be populated after forms are built
- ✅ Confirmation page structure — Option A, two child pages created (Q12 resolved)
- ⬜ CSS styling for `.tb-reg-btn` and `.tb-reg-btn--disabled` — deferred to front-end build
