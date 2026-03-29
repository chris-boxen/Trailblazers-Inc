# STATE

## Current Objective
Finalize schema changes in ACF admin before data population begins. The Events
Calendar Pro integration has changed the meet anchor post type from `athletic_meet`
(retired) to `tribe_events`. ACF must be updated, templates archived or migrated,
and CSV templates revised before any imports run.

## Current Repo Status
- Theme repo exists and is being tracked in GitHub.
- ACF Local JSON is active and saving into `acf-json/`.
- Original imported JSON files have been archived under `acf-json/_archived/`.
- ACF is now saving canonical files with the new naming pattern (e.g. `group_tb_*`).

## Pending Admin Actions (before data population)
These changes must be made in the WP admin and committed before import begins:

1. **Delete `athletic_meet` Post Type** in ACF → Post Types
2. **Delete `group_tb_athletic_meet` field group** in ACF → Field Groups
3. **Create `group_tb_tec_event` field group** attached to `tribe_events`:
   - `season` — Post Object → `athletic_season`
   - `results_status` — Select (Future / Pending / Available)
4. **Update `group_tb_athletic_result`** — change `meet` field target from
   `athletic_meet` to `tribe_events`
5. **Update `group_tb_athletic_season`** — add season flags (see below)
6. **Commit all ACF JSON changes**

## ACF Schema — Season Flags to Add
Add these fields to `group_tb_athletic_season.json` in ACF admin:

| Field name | Type | Default |
|---|---|---|
| `calendar_show_meets` | True/False | Off |
| `calendar_show_practices` | True/False | Off |
| `results_enabled` | True/False | Off |
| `link_milesplit` | True/False | Off |
| `link_athletic_net` | True/False | Off |
| `results_unavailable_message` | Textarea | blank |

## Current Development Environment
- Local is used for development.
- Site has been successfully exported/re-imported into Dropbox.
- Git tracks the theme folder only.
- Dropbox is being used as an external backup layer.
- The Events Calendar Pro is installed and active.

## Confirmed Architecture
### Core CPTs
- Family
- Athlete
- Coach
- Athletic Season
- Athletic Meet (**RETIRED** — replaced by `tribe_events`)
- Athletic Event
- Athletic Result
- Athletic Record
- Athletic Physical
- Application
- Enrollment

### TEC-Managed Post Types
- `tribe_events` — Meet anchor post (replaces `athletic_meet`). ACF field group
  `group_tb_tec_event` attaches `season` and `results_status`.
- `tribe_venue` — Venue record. Owned by TEC. Used by meet events.
- `tribe_events_cat` — Event categories: `athletic-meet`, `practice`,
  `team-event`, `community-run` (and others TBD).

### Taxonomies
- Sport (hierarchical — behaves like a category)
- Registered on: Athletic Season, Athletic Meet (retired), Athletic Event,
  Athletic Result, Athletic Record, Enrollment, Coach, Athlete

## Confirmed CPT URL Slugs
- athlete → `/athlete/`
- coach → `/coach/`
- family → `/family/`
- enrollment → `/enrollment/`
- athletic_season → `/season/`
- athletic_meet → RETIRED
- tribe_events → `/event/` (configure in TEC settings)
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
- Athletic Result = athlete performance at an event/meet
- Athletic Record = stored PR/SR/achievement layer linked to a result
- Athletic Season = seasonal hub (now carries per-season feature flags)
- `tribe_events` = meet instance (public calendar + internal data anchor)
- `tribe_venue` = reusable venue record
- Athletic Event = canonical event definition (5K, 100m, Long Jump, etc.)

## Confirmed Modeling Decisions
- ACF Local JSON is the schema source of truth.
- Enrollment is the athlete-season operational hub.
- Physical remains separate from Enrollment because one physical may span multiple seasons.
- `tribe_events` is the anchor post for all meet data. `athletic_meet` is retired.
- All `athletic_result` posts link to a `tribe_events` post via the `meet` field.
- `tribe_events` posts are always published (never draft), regardless of whether
  the meet appears on the public calendar. The `calendar_show_meets` season flag
  controls public surfacing; published status is required for ACF queries to work.
- TEC's native `/event/` archive is redirected to a custom query page via a
  `template_redirect` hook (to be built). TEC archive views are not used directly.
- Meet results display lives in `tribe/events/single-event.php` (TEC theme override).
- Season `results_enabled` flag controls display of results in templates. It does
  not prevent data entry. Flag off → show `results_unavailable_message`.
- `results_status` on `tribe_events` gates per-meet results display
  (Future / Pending / Available). Empty field treated as Future.
- Milesplit and AthleticNet IDs live on Athlete. Season flags `link_milesplit` and
  `link_athletic_net` control whether those links are rendered.
- Family does not maintain a mirrored Athlete relationship field as source of truth.
- Participation Type belongs on Enrollment (source of truth) and is denormalized onto
  Athlete for archive filtering. See SCHEMA.md for rules and empty-value handling.
- Sport is implemented as a hierarchical taxonomy (category-style).
- Coach is registered with the sport taxonomy for direct sport-based querying. Role and
  bio override per season are managed via the `coach_roster` repeater on Athletic Season.
- Event-specific labels on results remain free text where needed (`event_name`) while
  canonical event structure lives in Athletic Event.
- Stored records linked to source results are preferred over fully computing every PR/SR
  on demand.
- Athletic Result normalization is resolved: `result_display` for human-readable output
  plus type-specific normalized numeric fields (`result_time_seconds`,
  `result_distance_meters`, `result_height_meters`, `result_points`).
- "Current" record detection: for each athlete + event + record_type group, the record
  with the most recent meet date is flagged `is_current = true`. Derived at render time
  from the linked result's meet date.
- Family, Application, Enrollment, and Athletic Physical are admin-only data objects.
  No public-facing templates. See OPEN-QUESTIONS.md Q3.
- `account_status` on Athlete (Active / Inactive / Alumni / Archived) tracks lifecycle.
  `participation_type` on Athlete (Athlete / Sibling Runner) is a denormalized snapshot
  of enrollment type for filtering. These are distinct fields answering different
  questions — see SCHEMA.md.

## Template Status
### Active PHP templates
- `single-athlete.php` — needs update: flag checks + external ID links
- `single-athletic_season.php` — no change needed now
- `single-athletic_event.php` — needs update: flag checks, query post_type → tribe_events
- `archive-athlete.php` — no change needed now
- `archive-athletic_record.php` — needs update: query post_type → tribe_events
- `taxonomy-sport.php` — needs update: query post_type → tribe_events
- `tribe/events/single-event.php` — TO BUILD (TEC theme override, replaces single-athletic_meet.php)

### Archived templates (no longer active)
- `single-athletic_meet.php` → move to `_archived-templates/`
- `archive-athletic_meet.php` → move to `_archived-templates/`

### Custom page templates to build
- Meet schedule / calendar query page (queries `tribe_events` filtered by season flags)

## Theme Structure
- `functions.php` — clean loader only; requires all inc/ files
- `inc/divi.php` — Divi-specific filters
- `inc/enqueue.php` — scripts and styles (stub)
- `inc/cpt-hooks.php` — CPT/taxonomy behavior modifications (stub; add TEC archive
  redirect hook here)
- `inc/query-mods.php` — pre_get_posts and archive query adjustments (stub)
- `inc/acf-helpers.php` — reusable ACF utility functions (stub)
- `inc/gravity-helpers.php` — Gravity Forms / GravityPerks helpers (stub)
- `tribe/events/single-event.php` — TEC single event override (to build)

## ACF Schema — Current Canonical Field Groups
- `group_tb_athlete.json` — added `participation_type`
- `group_tb_application.json` — added `payment_amount`
- `group_tb_athletic_result.json` — `meet` field target: **pending update** to `tribe_events`
- `group_tb_athletic_season.json` — **pending**: add season flags
- `group_tb_tec_event.json` — **pending creation**: `season` + `results_status`
- `group_tb_athletic_meet.json` — **RETIRED** (delete in ACF admin, archive JSON)
- `post_type_tb_athletic_meet.json` — **RETIRED** (delete in ACF admin, archive JSON)

## GravityForms Registration — Design Status
Unchanged. Field map is in `docs/FORM-FIELD-MAP.md`. Build blocked on data population.