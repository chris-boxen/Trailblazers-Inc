# STATE

## Current Objective
Build a robust custom WordPress child theme for Trailblazers using ACF Pro, ACF Local JSON, and a durable schema for families, athletes, seasons, applications, enrollments, physicals, results, and records.

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
- Registered on: Athletic Season, Athletic Meet, Athletic Event, Athletic Result, Athletic Record, Enrollment, Coach, Athlete

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
- Season no longer stores athlete roster rows; athlete-season participation is modeled through Enrollment.
- Participation Type belongs on Enrollment, not Athlete.
- Sport is implemented as a hierarchical taxonomy (category-style).
- Coach is registered with the sport taxonomy for direct sport-based querying. Role and bio override per season are managed via the `coach_roster` repeater on Athletic Season.
- Coaches can span multiple sports; roles are season/sport-specific and stored on the season.
- Event-specific labels on results remain free text where needed (`event_name`) while canonical event structure lives in Athletic Event.
- Stored records linked to source results are preferred over fully computing every PR/SR on demand.
- Athletic Result normalization is resolved: `result_display` for human-readable output plus type-specific normalized numeric fields (`result_time_seconds`, `result_distance_meters`, `result_height_meters`, `result_points`).
- `results_status` on Athletic Meet gates whether results are displayed (Future / Pending / Available). Empty field treated as Future.
- All CPT templates are PHP files. Divi Theme Builder is not used for any CPT templates at this stage.

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

### Built
- `single-athlete.php` — bio, season history, PRs, results grouped by Season → Meet. Cross-links to family, season, and meet pages.
- `single-athletic_meet.php` — meet header, results grouped by event sorted by place. Cross-links to athletes and season. Gated by `results_status`.
- `single-athletic_season.php` — season header, coaches roster, meet schedule, athlete roster. Cross-links throughout.
- `single-coach.php` — photo, name, title, bio.
- `taxonomy-sport.php` — sport header, seasons list, coaches list, athletes table with gender/grad year/status columns and data attributes for filtering.

### Not yet built
- `archive-athlete.php`
- `archive-athletic_meet.php`
- `single-athletic_event.php`
- `archive-athletic_record.php`

## Current Risks / Watchouts
- ACF schema changes can create DB/JSON drift if Git branches are switched carelessly.
- `functions.php` should be split into an `inc/` structure before it becomes crowded.
- Theme folder name currently contains a space; consider changing to a slug-style folder name later.
- Divi Theme Builder can silently override PHP templates — check Theme Builder assignments when a template appears blank.
- Hierarchical sport taxonomy: use `'include_children' => false` in `tax_query` when exact-term matching is needed.