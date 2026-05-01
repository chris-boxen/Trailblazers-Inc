# STATE

## Current Objective
End-to-end registration testing in progress on https://trailblazers-inc.flywheelsites.com/.
Site is live, Stripe connected (Test mode), feeds configured. Multiple code audit
passes completed and pushed. Latest push (2026-05-01) restores missing Enrollment
field writes, adds submitted_by + digital_signature to Enrollment, and applies
Check/Cash payment amount fallback to New Family handler. Re-test pending.

## Current Repo Status
- Theme repo exists and is being tracked in GitHub.
- ACF Local JSON is active and saving into `acf-json/`.
- All schema changes from the TEC integration are committed.
- ACF Group wrapper removed from Enrollment post object fields (season, family,
  athlete, application) — required for WP Ultimate CSV Importer to resolve
  post object relationships correctly. See SCHEMA.md and CHANGELOG.md.
- Registration infrastructure complete. See CHANGELOG.md 2026-04.
- 2026 XC registration forms built and imported. See CHANGELOG.md 2026-04 and
  FORM-FIELD-MAP.md v2.3.
- Registration code audit complete as of 2026-04-30. Six fixes applied.
  See CHANGELOG.md 2026-04-30.

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

## Remaining — Data Population (deferred, not blocking registration)
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
- `group_tb_application.json` — includes `payment_amount`; `payment_method`
  choices consolidated to `Credit Card` and `Check/Cash`
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
- `inc/enqueue.php` — script/style enqueuing (stub — not yet needed)
- `inc/cpt-hooks.php` — CPT/taxonomy admin hooks (stub — not yet needed)
- `inc/query-mods.php` — pre_get_posts modifications (stub — not yet needed)
- `inc/acf-helpers.php` — ACF utility functions (stub — not yet needed)
- `inc/gravity-helpers.php` — GF dispatch hook, New Family handler, Returning
  Family handler, Stripe confirmation hook stub
- `inc/registration-helpers.php` — season sync hook, `tb_get_family_post_id()`
  helper, `[tb_reg_router]`, `[tb_reg_hub]`, `[tb_reg_form]` (with user-state
  guards including enrollment check), `[tb_reg_confirmation]`, `login_redirect`
  filter
- `inc/login.php` — login page branding, registration form first/last name
  fields, username auto-generation

## GravityForms Registration — Build Status
  Full field spec in `docs/FORM-FIELD-MAP.md`.
  
  ### Forms
  - ✅ Register New Athlete — nested form, permanent/reusable
  - ✅ Register Returning Athlete — nested form, permanent/reusable
  - ✅ 2026 Registration — New Family — 5-page, requireLogin
  - ✅ 2026 Registration — Returning Family — 5-page, requireLogin, GPPA configured
  
  ### New Family form updates (applied on Flywheel, 2026-05-01)
  - ✅ Zip code field added to address section
  - ✅ Primary contact First Name / Last Name / Email populated via GPPA from WP user;
    marked read-only
  - ✅ Relationship field placeholder changed to "Please Select..." for both guardians
  - ✅ Enhanced dropdown UX deactivated (was breaking CSS styles)
  
  ### Post-import manual configuration
  - ✅ GF form IDs entered in TB Settings → Registration Settings
  - ✅ GPPA configured on Returning Family Page 1 (Field 60 anchor + 16 fields)
  - ✅ GPPA configured on Register Returning Athlete (athlete selector + identity)
  - ✅ GW Read Only configured on Family Name (RF Field 1) and identity fields
  - ✅ GF confirmations updated — conditional redirects by payment method to
    type-specific confirmation pages on trailblazers-inc.flywheelsites.com
  - ✅ Stripe connected: Test mode, York County Trailblazers, Inc.
  - ✅ Stripe webhook registered: https://trailblazers-inc.flywheelsites.com/?callback=gravityformsstripe
  - ✅ Stripe Test Signing Secret entered in GF
  - ✅ Stripe feeds configured on both parent forms (Products & Services, Form Total,
    conditional on Credit Card)
  - ⬜ Zip code field added to NF form — hook mapping to Family `zip_code` field
    not yet verified
  - ⬜ Handbook URL — update active season post before opening registration
  
  ### Registration flow (inc/registration-helpers.php)
  - ✅ `login_redirect` filter — non-admin users → `/registration/` after login
  - ✅ `[tb_reg_router]` — user-state routing on `/registration/`
  - ✅ `/registration/` page updated to use `[tb_reg_router]`
  - ✅ `[tb_reg_form]` guards — wrong-type, logged-out, and already-enrolled redirects
  
  ### Hooks (inc/gravity-helpers.php)
  - ✅ `gform_after_submission` — New Family: creates Family, Application, Athletes,
    Enrollments; CC failure guard; season/user ID fallbacks
  - ✅ `gform_after_submission` — Returning Family: updates Family (address +
    guardians only), creates Application, Athletes (if any), Enrollments;
    CC failure guard; season/user ID fallbacks
  - ⬜ Stripe confirmation hook — stub present; wire after Stripe confirmed on live site
  
  ### Code audit fixes applied (gravity-helpers.php) — as of 2026-05-01
  1. ✅ `tb_reg_get_form_id()` option key names corrected (`_id` suffix)
  2. ✅ Season ID / User ID fallbacks added to both handlers
  3. ✅ CC payment failure guard added to both handlers
  4. ✅ `guardian_notifications` changed from string to int 1/0 (true_false)
  5. ✅ `is_primary_contact` added to both guardian rows
  6. ✅ `User.family` reverse reference set after Family post creation
  7. ✅ `dob` field name corrected (was `date_of_birth`)
  8. ✅ Gender value mapping added (Male/Female → M/F)
  9. ✅ Enrollment expanded: grade, participation_type, singlet group fields
  10. ✅ ACF group field keys used in `tb_create_athlete_post()` — name writes
      silently fail for sub-fields of ACF Group types
  11. ✅ ACF group field keys used for singlet + physical_status in
      `tb_create_enrollment_post()`
  12. ✅ Restored missing Enrollment relationship writes (season, application,
      family, athlete, new_returning, eligibility_confirmed) — accidentally
      dropped in fix #11
  13. ✅ `submitted_by` and `digital_signature` added as args to
      `tb_create_enrollment_post()`; all three call sites updated
  14. ✅ Check/Cash payment amount fallback applied to both handlers — reads
      GF Total field directly when `entry['payment_amount']` is 0
  
  ### End-to-end test status (as of 2026-05-01)
  Test 1 (New Family, Check/Cash) completed before fixes 10–14 were applied.
  Re-test pending after latest push. Known outstanding issues:
  - ⬜ Confirmation emails not received by test user — cause unknown
  - ⬜ Singlet count on NF Page 5 appears to use Athlete Count rather than
    requested singlet count — form formula likely needs adjustment
  - ⬜ Family ID blank — admin-populated field, not set by hook (by design)
  - ⬜ Zip code write not yet verified (field newly added to form)
  - ⬜ Confirmation pages blank — content needed in Registration Settings → Messaging