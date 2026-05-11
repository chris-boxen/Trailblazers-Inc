# STATE


## Current Objective
Active focus is template build. Next: taxonomy-sport.php.
Production deployment of shadow key migration SQL still outstanding.

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
- Registration hooks complete and verified. See CHANGELOG.md 2026-05.
- `assets/css/templates.css` added — base styles for all CPT templates.
  Imported from `assets/css/styles.css`.
- `archive-athletic_season.php` added. `single-athletic_season.php` refactored
  to ul/li list system with base template classes.
- `inc/results-helpers.php` added — result time sync utility, ACF save hook,
    admin_init POST handler, Tools → Sync Result Times page.
- `inc/admin-widgets.php` updated — 📊 Results widget added; all meet queries
  now filter by tribe_events_cat: athletic-meet.
- ACF field key collision fixed in `group_tb_athletic_result.json`
  (field_tb_event → field_tb_athletic_event, field_tb_result → field_tb_result_display).
- `single-athlete.php` results section refactored: flat list per season,
    data attributes, row-as-link. `customize_data` group access pattern fixed.
- 2025 XC enrollment corrected to 118 athletes (26 erroneous posts removed).
- Shadow key migration SQL run on local. **Not yet run on production.**
- `tribe/events/single-event.php` and `tribe/events/v2/default-template.php` added —
    TEC meet results view. Appends results section after TEC's native event output.
- `single-athlete.php` updated — Heat column and data-heat attribute added to results list.
- `single-athletic_event.php` refactored — base classes, ul/li lists, flat results list
  (season → meet grouping removed), heat added, grade + gender data attributes added.
- `group_tb_athletic_result.json` updated — heat text field added (field_tb_heat).
  Shadow key migration SQL still outstanding on production.

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

## Remaining — Data Population (deferred, not blocking)
10. ✅ Athletic Results (2025 XC — 951 posts, 14 meets, via WPUCI)
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

## Registration System Status

### Forms
  - ✅ Register New Athlete — nested form, permanent/reusable
  - ✅ Register Returning Athlete — nested form, permanent/reusable
  - ✅ 2026 Registration — New Family — 5-page, requireLogin
  - ✅ 2026 Registration — Returning Family — 5-page, requireLogin, GPPA configured

  ### New Family form updates (applied on Flywheel, 2026-05-01)
  - ✅ Zip code field added (field 51)
  - ✅ Family ID field added (field 52, GP Unique ID)
  - ✅ Primary contact First Name / Last Name / Email populated via GPPA from
    WP user; marked read-only
  - ✅ Relationship field placeholder changed to "Please Select..."
  - ✅ Enhanced dropdown UX deactivated

  ### Post-import manual configuration
  - ✅ GF confirmations updated — conditional redirects by payment method to
    type-specific confirmation pages on trailblazers.team
  - ✅ Stripe connected: Live mode, York County Trailblazers, Inc.
  - ✅ Stripe webhook registered — https://trailblazers.team/?callback=gravityformsstripe
  - ✅ Stripe Live Signing Secret entered in GF
  - ✅ Webhook delivery confirmed (200 OK) on trailblazers.team — 2026-05-02
  - ✅ Handbook URL — updated on 2026 XC Athletic Season post

  ### Registration flow (inc/registration-helpers.php)
  - ✅ `login_redirect` filter — non-admin users → `/registration/` after login
  - ✅ `[tb_reg_router]` — user-state routing on `/registration/`
  - ✅ `/registration/` page updated to use `[tb_reg_router]`
  - ✅ `[tb_reg_form]` guards — wrong-type, logged-out, and already-enrolled redirects

  ### Hooks (inc/gravity-helpers.php)
  - ✅ `gform_after_submission` — New Family: creates Family, Application,
    Athletes, Enrollments; CC failure guard; season/user ID fallbacks
  - ✅ `gform_after_submission` — Returning Family: updates Family (address +
    guardians only), creates Application, Athletes (if any), Enrollments;
    CC failure guard; season/user ID fallbacks
  - ✅ `gform_field_value_tb_handbook_url` — populates hidden handbook URL
    field on Page 3 of both registration forms from active season post
  - ✅ RF uses Experimental GF Elements handler — PaymentIntent created via AJAX after entry creation; webhook required for payment confirmation
  - Use Stripe's Payment Element" must be unchecked on RF field 57; Payment Element flow throws 500 due to known GF Stripe + GPPA interaction (Gravity Wiz ticket filed)

  ### End-to-end test status
  - ✅ Test 3 (New Family, Check/Cash) — 2026-05-01 — all CPT writes verified:
    Family, Application, Athlete, Enrollment all fields correct
  - ✅ New Family, Credit Card — 2026-05-01 — Stripe charge confirmed, CPT
    writes verified.
  - ✅ Returning Family, Check/Cash — 2026-05-02 — all CPT writes verified
  - ✅ Returning Family, Credit Card — 2026-05-02 — Stripe charge confirmed, webhook delivering, CPT writes verified
  
  ### Post-launch bug fixes (2026-05-06)
  - ✅ Enrollment `new_returning_athlete` field key collision fixed —
    `field_tb_new_returning` → `field_tb_new_returning_athlete`; all call
    sites updated; existing records remediated
  - ✅ Sport taxonomy now written to Enrollment on creation — inherited from
    linked season via `wp_set_object_terms()`
  - ✅ Admin list columns + filters added for Enrollment and Application
    CPTs in `inc/query-mods.php`

  ### Known issues
  - RF CC submission spinner takes ~27 seconds with no visible progress — inherent to experimental handler flow; UX polish deferred