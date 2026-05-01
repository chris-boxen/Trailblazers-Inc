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

---

## Now — Pre-Launch UX + Testing

### 1. Fix singlet count formula (NF Page 5)
Singlet Count field currently mirrors Athlete Count. For new families every
athlete requires a singlet, so these happen to match — but the formula is
semantically wrong and will break if logic changes. Revisit the calculated
field to count athletes who selected a singlet rather than total athlete count.

### 2. Investigate confirmation emails
Test user has not received confirmation emails. Check in order:
- GF → New Family form → Notifications — is a notification configured and active?
- Is SMTP2GO configured on the Flywheel site with valid credentials and a
  from address? Send a test email to confirm delivery.
- After SMTP confirmed, re-submit a test registration and check the GF entry
  log for notification send status.

### 3. Add confirmation page content
Add content in TB Settings → Registration Settings → Messaging:
- New Family Confirmation Text
- Returning Family Confirmation Text

### 4. Test New Family, Credit Card
Run a Credit Card submission using a Stripe test card. Verify:
- Stripe test charge succeeds in Stripe dashboard
- Confirmation redirect lands on the correct page
- Application `payment_status` updates to Paid once Stripe hook is wired

### 5. Test Returning Family flow
After NF tests pass, test a Returning Family submission (Check/Cash first,
then Credit Card). Verify:
- Family address and guardians updated (display name not overwritten)
- Returning athlete Enrollment created with correct athlete post ID
- New athlete path works same as NF
- Payment flow mirrors NF results

### 6. Wire Stripe confirmation hook
After Stripe is tested end-to-end, confirm the correct hook name and
uncomment + complete the stub in `inc/gravity-helpers.php`. Candidates:
- `gform_stripe_fulfillment`
- `gform_stripe_after_payment_intent_succeeded`
Hook should locate Application post by `gravity_form_entry_id` and update
`payment_status` → `Paid`.

### 7. Update handbook URL
Add the 2026 XC handbook URL to the `handbook` field on the 2026 XC Athletic
Season post before opening registration.

### 8. Domain swap
Remove trailblazers.team from the old Flywheel site, add as primary domain
to the new site. Update the four GF confirmation redirect URLs to the new
domain. Re-verify Stripe webhook endpoint URL in Stripe dashboard.

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