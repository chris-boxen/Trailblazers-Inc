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

## Now — Flywheel + Stripe + Testing

### 1. Push theme to Flywheel
Push the current repo state to the Flywheel staging site
(trailblazers-inc.flywheelsites.com). All theme code is ready.

### 2. Connect Stripe on Flywheel
GF → Settings → Stripe → Connect with Stripe.
Use test mode. OAuth will work on the public Flywheel domain.
Register webhook endpoint. Enter signing secret.

### 3. GF Admin — configure Stripe feeds
On both New Family and Returning Family forms: GF → (form) → Settings → Stripe → Add New.
- Transaction Type: Products and Services
- Payment Amount: Form Total
- Billing Info: map to primary contact fields
- Condition: Payment Method is Credit Card

### 4. Test end-to-end
- New Family: full submission → confirm Family, Application, Athlete,
  Enrollment posts created with correct field values
- Returning Family: full submission → confirm Family updated (not recreated),
  Application created, Enrollments created for returning and new athletes
- Credit card path: verify Stripe test charge succeeds; verify confirmation
  redirect lands on correct page
- Check/Cash path: verify confirmation redirect lands on correct page
- Login redirect: confirm non-admin users land at `/registration/` after login
- User-state routing: confirm all three routing states work correctly
- Already-enrolled guard: confirm returning family cannot submit twice

### 5. Wire Stripe confirmation hook
After Stripe feeds are tested, confirm the correct hook name
(`gform_stripe_fulfillment` or `gform_stripe_after_payment_intent_succeeded`)
and uncomment + complete the stub in `inc/gravity-helpers.php`.

### 6. Active season post — update handbook URL
Add the 2026 XC handbook URL to the `handbook` field on the 2026 XC Athletic
Season post before opening registration.

### 7. Add confirmation page content
Add meaningful content to TB Settings → Registration Settings → Messaging tab:
- New Family Confirmation Text
- Returning Family Confirmation Text
These are rendered by `[tb_reg_confirmation]` on the confirmation pages.

### 8. Domain swap
Remove trailblazers.team from the old Flywheel site, add as primary domain
to the new site. Update the four GF confirmation redirect URLs to the new domain.
Re-verify Stripe webhook endpoint URL in Stripe dashboard.

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