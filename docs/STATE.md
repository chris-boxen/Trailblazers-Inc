# STATE

## Current Objective
Build a robust custom WordPress theme for Trailblazers using ACF Pro, ACF Local JSON, and a durable schema for families, athletes, seasons, applications, enrollments, physicals, results, and records.

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
- Sport

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
- Sport is implemented as a taxonomy rather than only a select field.
- Event-specific labels on results remain free text where needed (`event_name`) while canonical event structure lives in Athletic Event.
- Stored records linked to source results are preferred over fully computing every PR/SR on demand.

## Current Template Status
Theme currently has:
- `functions.php`
- `style.css`
- `assets/`
- `acf-json/`

Template buildout is still early and needs baseline fallback templates.

## Current Risks / Watchouts
- ACF schema changes can create DB/JSON drift if Git branches are switched carelessly.
- Athletic Result schema still needs normalization for sortable result data.
- `functions.php` should be split into an `inc/` structure before it becomes crowded.
- Theme folder name currently contains a space; consider changing to a slug-style folder name later.

## Immediate Next Actions
1. Finalize Athletic Event / Athletic Result field strategy.
2. Add baseline template files.
3. Split `functions.php` into `inc/`.
4. Begin building public-facing templates for Athlete, Season, Meet, and Sport.