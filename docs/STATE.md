# STATE

## Current Objective
Pre-launch testing in progress on https://trailblazers-inc.flywheelsites.com/.
New Family CC path tested and payment flow confirmed working. Remaining work
before opening registration: singlet count formula fix, confirmation page
content, Returning Family testing, and domain swap.

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
    writes verified. payment_status writes Paid, payment_amount writes from
    GFCommon::get_order_total(). Notification emails confirmed sending.
  - ✅ Singlet count formula on NF Page 5 corrected — now counts athletes who
    requested a singlet rather than mirroring total athlete count
  - ✅ Confirmation page content added — New Family and Returning Family
    confirmation text entered in Registration Settings → Messaging
  - ✅ Returning Family, Check/Cash — 2026-05-02 — all CPT writes verified
  - ✅ Returning Family, Credit Card — 2026-05-02 — Stripe charge confirmed, webhook delivering, CPT writes verified

  ### Known issues
  - payment_status on Application and Enrollment does not write Paid for RF CC submissions — cause undiagnosed; workaround is manual update
  - RF CC submission spinner takes ~27 seconds with no visible progress — inherent to experimental handler flow; UX polish deferred