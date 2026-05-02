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

- ✅ ACF Group sub-field writes fixed
- ✅ Eligibility check fixed for NF and RF
- ✅ Zip code, Family ID, three-way linkage all verified
- ✅ New Family Check/Cash — all CPTs verified
- ✅ New Family Credit Card — Stripe charge confirmed, CPTs verified
- ✅ Singlet count formula corrected on NF Page 5
- ✅ Confirmation page content added for both form types
- ✅ Notification emails confirmed sending via Gravity SMTP

## Completed — Returning Family Testing + CC Unblock
As of 2026-05-02. See CHANGELOG.md 2026-05.

- ✅ RF eligibility check fixed — removed redundant fields 15.1 / 18.1
- ✅ RF new athlete IDs group write added to Section 4 loop
- ✅ RF Check/Cash — all CPTs writing correctly
- ✅ RF CC path unblocked: "Use Stripe's Payment Element" unchecked on
  field 57; switched to snapshot webhook payload; experimental handler
  (`Experimental_GF_Elements_Handler`) now creates PaymentIntent correctly
- ✅ `rgba` CSS values in `styles.css` fixed — was causing Stripe
  re-initialization loop on Payment Element
- ✅ `payment_status` logic updated — now reads payment method field value
  (NF field 48, RF field 55) instead of `$entry['payment_status']`
- ✅ RF Credit Card — Stripe charge confirmed, webhook delivering (200 OK),
  CPT writes verified

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

## Backlog — Known Bugs (post-launch)

- [ ] **RF CC `payment_status` not writing `Paid`** — Application and
  Enrollment posts show `Not Received` after CC submission despite charge
  succeeding. Field 55 contains `Credit Card` in entry; cause undiagnosed.
  Workaround: manual update in WP admin. Needs debug log investigation.
- [ ] **RF CC spinner — no visible progress** — ~27 seconds with no
  intermediate state shown. Inherent to experimental handler flow. Add
  "Processing your payment..." status message as interim UX fix.
- [ ] **GF Stripe + GPPA Payment Element conflict** — "Use Stripe's Payment
  Element" on RF field 57 causes 500 error. Root cause: `start_checkout()`
  aborts with "Submission is not valid" before PaymentIntent is created.
  Gravity Wiz ticket filed. Monitor for response.
- [ ] **`gform_disable_notification` filter** — "TB Registration Receipt - Paid"
  fires on failed CC payments. Add filter to suppress when payment status
  is not Paid.
- [ ] **Duplicate Stripe webhooks** — clean up stale sandbox webhooks; keep
  only one active snapshot webhook per environment.

---

## Backlog — UX Issues (non-blocking polish)

- [ ] WP subscriber dashboard — needs content and layout updates
- [ ] Handbook note inside nested athlete form is redundant with Page 3 —
  remove or relocate
- [ ] Optional donations field — choice labels missing amounts

---

## Deferred — Schema / Template Updates
Not blocking registration. Return to these after registration is live.

- Consider building proper single-site email customization
- Revisit payment abstraction if workflow becomes more complex
- Add stronger review workflows for physicals and approvals
- Revisit theme folder naming (space in folder name)
- Add `template-parts/` structure when templates grow complex enough
- Confirm `publicly_queryable` is false on Family, Application, Enrollment,
  and Athletic Physical CPTs (see OPEN-QUESTIONS.md Q3)
- Decide whether TEC `tribe_events_cat` needs sport sub-categories
  (see OPEN-QUESTIONS.md Q10)
- Additional Singlets (Returning) manual count field on RF Page 5 — future:
  derive from nested form entries rather than manual input
- Athletic Results and Athletic Records data import