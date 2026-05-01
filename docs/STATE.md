# STATE

## Current Objective
Pre-launch UX fixes and Stripe / Returning Family testing in progress on
https://trailblazers-inc.flywheelsites.com/. All CPT writes verified clean
for New Family / Check/Cash path as of 2026-05-01. Remaining work before
opening registration to families: singlet count formula, confirmation emails,
confirmation page content, Stripe testing, and RF flow testing.

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
  - ✅ GF form IDs entered in TB Settings → Registration Settings
  - ✅ GPPA configured on Returning Family Page 1 (Field 60 anchor + 16 fields)
  - ✅ GPPA configured on Register Returning Athlete (athlete selector + identity)
  - ✅ GW Read Only configured on Family Name (RF Field 1) and identity fields
  - ✅ GF confirmations updated — conditional redirects by payment method to
    type-specific confirmation pages on trailblazers-inc.flywheelsites.com
  - ✅ Stripe connected: Test mode, York County Trailblazers, Inc.
  - ✅ Stripe webhook registered
  - ✅ Stripe Test Signing Secret entered in GF
  - ✅ Stripe feeds configured on both parent forms
  - ⬜ Handbook URL — update active season post before opening registration

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
  - ⬜ Stripe confirmation hook — stub present; wire after Stripe confirmed

  ### End-to-end test status
  - ✅ Test 3 (New Family, Check/Cash) — 2026-05-01 — all CPT writes verified:
    Family, Application, Athlete, Enrollment all fields correct
  - ⬜ New Family, Credit Card — not yet tested
  - ⬜ Returning Family — not yet tested

  ### Known pre-launch issues
  - ⬜ Singlet count formula on NF Page 5 uses Athlete Count rather than
    count of athletes who requested a singlet
  - ⬜ Confirmation emails not sending — GF notifications / SMTP2GO not verified
  - ⬜ Confirmation pages blank — content needed in Registration Settings → Messaging