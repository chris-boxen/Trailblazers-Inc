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

---

## Completed — Flywheel + Stripe Setup
As of 2026-05-01.

- ✅ Theme pushed to https://trailblazers-inc.flywheelsites.com/
- ✅ Stripe connected: Test mode, York County Trailblazers, Inc.
- ✅ Stripe webhook registered; Test Signing Secret entered in GF
- ✅ Stripe feeds configured on both New Family and Returning Family forms
- ✅ New Family form updated: zip code field, GPPA for primary contact,
  relationship placeholder, enhanced dropdown UX deactivated

## Now — End-to-End Testing

### 1. Re-test New Family, Check/Cash (IMMEDIATE)
Latest push (2026-05-01) restores missing Enrollment writes and adds
submitted_by + digital_signature + payment amount fallback. Delete previous
test records and run a fresh New Family / Check/Cash submission. Verify:
- Family post: name, address, zip, guardians, account_user, family_status
- Application post: family, season, payment_amount (non-zero), payment_method,
  digital_signature, submitted_by, new_returning, application_status, payment_status
- Athlete post: first_name, last_name, gender (M/F), dob, family, account_status,
  participation_type
- Enrollment post: season, application, family, athlete, new_returning,
  grade, participation_type, eligibility_confirmed, submitted_by,
  digital_signature, physical_status, singlet fields

### 2. Fix zip code hook mapping
The NF form now has a zip code field. Verify the hook writes it to the Family
`zip_code` ACF field. Check the field ID on the live form and confirm
`update_field('zip_code', rgar($entry, 'X'), $family_id)` is in both handlers
with the correct field ID.

### 3. Investigate confirmation emails
Test user received no confirmation emails after NF submission. Check:
- GF → New Family form → Notifications — is a notification configured and active?
- Is SMTP configured on the Flywheel site (SMTP2GO is installed)?
- Check GF entry log for notification send status

### 4. Fix singlet count formula on NF Page 5
Singlet count appears to use Athlete Count rather than the number of athletes
who requested a singlet. Revisit the NF Page 5 Registration Total formula and
the Singlet Count calculated field.

### 5. Test New Family, Credit Card
After Check/Cash test passes, run a Credit Card submission using a Stripe test
card. Verify Stripe test charge succeeds and confirmation redirect lands on the
correct page.

### 6. Test Returning Family flow
After NF tests pass, test a Returning Family submission (Check/Cash first,
then Credit Card).

### 7. Wire Stripe confirmation hook
After Stripe feeds are tested end-to-end, confirm the correct hook name and
uncomment + complete the stub in `inc/gravity-helpers.php`.

### 8. Add confirmation page content
Add content in TB Settings → Registration Settings → Messaging:
- New Family Confirmation Text
- Returning Family Confirmation Text

### 9. Update handbook URL
Add the 2026 XC handbook URL to the `handbook` field on the 2026 XC Athletic
Season post before opening registration.

### 10. Domain swap
Remove trailblazers.team from the old Flywheel site, add as primary domain
to the new site. Update the four GF confirmation redirect URLs to the new domain.
Re-verify Stripe webhook endpoint URL in Stripe dashboard.

---

## Backlog — UX Issues (non-blocking, pre-launch polish)
Noted during Test 1. Address before opening registration to families.

- [ ] WP subscriber dashboard — needs content and layout updates
- [ ] Handbook note inside nested athlete form is redundant with the
  standalone Handbook page (Page 3) — remove or relocate
- [ ] Optional donations field choices don't display amounts — fix choice labels
- [ ] Singlet count formula fix (also in step 4 above)
- [ ] Confirmation pages need content (also in step 8 above)

---

## Deferred — Schema / Template Updates
Not blocking registration. Return to these after registration is live.

### Templates to update
- `single-athlete.php`
  - Add per-season `results_enabled` flag check with `results_unavailable_message` fallback
  - Add Milesplit / AthleticNet external ID links when season flags + IDs are present

- `single-athletic_event.php`
  - Add per-season `results_enabled` flag check
  - Update any `post_type => athletic_meet` references → `post_type => tribe_events`

- `archive-athletic_record.php`
  - Update any `post_type => athletic_meet` references → `post_type => tribe_events`

- `taxonomy-sport.php`
  - Update any `post_type => athletic_meet` references → `post_type => tribe_events`

### Templates to build new
- `tribe/events/single-event.php` — TEC theme override
  - Renders meet header (name, date, venue via `tribe_venue`, season link)
  - Renders results section gated by `results_status` field
  - Mirrors the structure of the retired `single-athletic_meet.php`

- Meet schedule custom query page template
  - Queries `tribe_events` by category `athletic-meet`
  - Filters by seasons where `calendar_show_meets = true`
  - Replaces the retired `archive-athletic_meet.php`

### TEC archive redirect
- In `inc/cpt-hooks.php`: add `template_redirect` hook to redirect TEC's native
  `/event/` archive to the custom meet schedule page
- Only redirect the main archive, not single event pages

---

## Deferred — Data Population
- ⬜ Athletic Results — `08-athletic-results.csv`
- ⬜ Athletic Records — `09-athletic-records.csv`

---

## Parked — JS Filtering (ready to build, not urgent)
Data attributes are wired on all archive templates. Can be built independently.

- Add filter controls + JS to `archive-athlete.php`
- Add filter controls + JS to `archive-athletic_record.php`
- Add filter controls + JS to `taxonomy-sport.php` athlete table
- Populate `inc/enqueue.php` with the filtering script

---

## Later
- CSS refinement for registration button styles (baseline added; may need
  design polish to match full site aesthetic)
- `inc/login.php` — `login_headerurl` hardcoded to `https://trailblazers.team`;
  update after domain swap or change to use `home_url()`
- `inc/login.php` — activation email hooks use `wpmu_signup_user_notification_*`
  filters which only fire on multisite; single-site activation emails use
  default WP template. Consider building proper single-site email customization.
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