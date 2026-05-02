# NEXT-STEPS

## Completed — Registration Infrastructure
As of 2026-04-20. Full record in CHANGELOG.md 2026-04.

- ✅ ACF options pages: Trailblazers Settings (parent) + Registration Settings (sub-page)
- ✅ ACF field group `group_tb_registration_settings` (16 fields, synced)
- ✅ `inc/registration-helpers.php` — sync hook + shortcodes
- ✅ `functions.php` updated with new require line
- ✅ Seven permanent WP registration pages created:
  - `/registration/` — `[tb_reg_router]`
  - `/registration/returning-families/` — `[tb_reg_form type="returning_family"]`
  - `/registration/new-families/` — `[tb_reg_form type="new_family"]`
  - `/registration/confirmation/` — parent container (permanent)
  - `/registration/confirmation/new-family/` — `[tb_reg_confirmation type="new_family"]`
  - `/registration/confirmation/returning-family/` — `[tb_reg_confirmation type="returning_family"]`
  - `/registration/submit-physicals/` — `[tb_reg_form type="physicals"]`

## Completed — GravityForms Registration Build
As of 2026-04-26. Full field spec in `docs/FORM-FIELD-MAP.md` v2.3.

- ✅ Register New Athlete nested form (permanent/reusable)
- ✅ Register Returning Athlete nested form (permanent/reusable)
- ✅ 2026 Registration — New Family (5-page, requireLogin)
- ✅ 2026 Registration — Returning Family (5-page, requireLogin, full GPPA config)
- ✅ `inc/registration-helpers.php` updated: `login_redirect` filter,
  `tb_get_family_post_id()` helper, `[tb_reg_router]` shortcode,
  user-state guards on `[tb_reg_form]`
- ✅ `docs/FORM-FIELD-MAP.md` v2.3 committed
- ✅ `/registration/` page updated to use `[tb_reg_router]`
- ✅ GF form IDs entered in TB Settings → Registration Settings
- ✅ GPPA configured on Returning Family and Register Returning Athlete
- ✅ GW Read Only configured on Family Name and identity fields

## Completed — Submission Hooks + Code Audit
As of 2026-04-30. See CHANGELOG.md 2026-04-30.

- ✅ `inc/gravity-helpers.php` — New Family and Returning Family submission
  hooks written; Stripe confirmation hook stubbed
- ✅ Six audit fixes applied:
  1. `tb_reg_get_form_id()` option key names corrected (`_id` suffix)
  2. Season ID / User ID fallbacks added to both handlers
  3. GF confirmation redirects updated — conditional by payment method,
     pointing to type-specific pages on trailblazers-inc.flywheelsites.com
  4. CC payment failure guard added to both handlers
  5. Enrollment check added to `[tb_reg_form type="returning_family"]` guard
  6. Registration shortcode CSS added to `assets/css/styles.css`

## Completed — Flywheel + Stripe Setup
As of 2026-05-01.

- ✅ Theme pushed to https://trailblazers-inc.flywheelsites.com/
- ✅ Stripe connected: Test mode, York County Trailblazers, Inc.
- ✅ Stripe webhook registered; Test Signing Secret entered in GF
- ✅ Stripe feeds configured on both New Family and Returning Family forms
- ✅ New Family form updated: zip code field (51), Family ID field (52, GP
  Unique ID), GPPA for primary contact, relationship placeholder,
  enhanced dropdown UX deactivated

## Completed — Hook Fixes + End-to-End Verification
As of 2026-05-01. See CHANGELOG.md 2026-05.

- ✅ ACF Group sub-field writes fixed — switched to parent group array pattern
  in `tb_create_athlete_post()` and `tb_create_enrollment_post()`
- ✅ Eligibility check fixed — removed non-existent field IDs (14, 17) from
  `tb_new_athlete_eligibility_confirmed()`
- ✅ Zip code written to Family post (NF field 51, RF field 62)
- ✅ Family ID (field 52, GP Unique ID) written to Family post, each Athlete
  post (IDs group), and WP User — three-way linkage complete
- ✅ Athlete slug computed via `sanitize_title()` from first + last name
- ✅ Duplicate `payment_amount` block removed from NF handler Section 1
- ✅ Test 3 (New Family, Check/Cash) — all four CPTs writing correctly:
  Family, Application, Athlete, Enrollment all fields verified

## Completed — CC Testing + Payment + Handbook Fixes
  As of 2026-05-01. See CHANGELOG.md 2026-05.
  
  - ✅ Handbook URL added to 2026 XC Athletic Season post
  - ✅ `gform_field_value_tb_handbook_url` filter added to `gravity-helpers.php`
    (Section 5) — reads ACF link field array, extracts ['url']
  - ✅ GF HTML field merge tag fixed (missing `@` prefix) — handbook button
    now resolves correctly on registration form Page 3
  - ✅ payment_status hardcode removed from both NF and RF handlers — now reads
    $entry['payment_status'] at hook time; writes Paid for CC, Not Received
    for Check/Cash
  - ✅ payment_amount block replaced in both handlers with
    GFCommon::get_order_total($form, $entry) — reliable for both payment
    methods, independent of Stripe's async entry update timing
  - ✅ Stripe confirmation hook stub removed — not needed in Payment Element
    redirect flow; payment data is available within gform_after_submission
  - ✅ GF notification merge tag updated from Order Total field (always empty)
    to {payment_amount} — confirmed correct for CC submissions
  - ✅ Confirmation emails confirmed sending via Gravity SMTP — Admin
    Notification and TB Registration Receipt - Paid both verified
  - ✅ New Family, Credit Card — end-to-end test passed
  
  ---
  
## Completed — Production Cutover
  As of 2026-05-02.
  
  - ✅ trailblazers.team removed from old Flywheel site
  - ✅ trailblazers.team set as primary domain on new Flywheel site
  - ✅ GF confirmation redirect URLs updated to trailblazers.team
  - ✅ Stripe switched to Live mode in GF Settings → Stripe
  - ✅ Stripe webhook endpoint updated to https://trailblazers.team/?callback=gravityformsstripe
  - ✅ Live Signing Secret entered in GF
  - ✅ Webhook delivery confirmed (200 OK) — resend test passed

---

## Backlog — UX Issues (non-blocking, pre-launch polish)

- [ ] WP subscriber dashboard — needs content and layout updates
- [ ] Handbook note inside nested athlete form is redundant with the
  standalone Handbook page (Page 3) — remove or relocate
- [ ] Optional donations field choices don't display amounts — fix choice labels

---

## Deferred — Schema / Template Updates
Not blocking registration. Return to these after registration is live.

- Consider building proper single-site email customization
- Revisit payment abstraction if workflow becomes more complex
- Add stronger review workflows for physicals and approvals
- Revisit theme folder naming (space in folder name)
- Add `template-parts/` structure when templates grow complex enough to warrant it
- Confirm `publicly_queryable` is false on Family, Application, Enrollment, and
  Athletic Physical CPTs (see OPEN-QUESTIONS.md Q3)
- Decide whether TEC `tribe_events_cat` needs sport sub-categories for calendar
  filtering (see OPEN-QUESTIONS.md Q10)
- Additional Singlets (Returning) manual count field on RF Page 5 — future
  improvement: derive from nested form entries rather than manual input