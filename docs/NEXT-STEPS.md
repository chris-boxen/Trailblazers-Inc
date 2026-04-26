# NEXT-STEPS

## Completed — Registration Infrastructure
As of 2026-04-20. Full record in CHANGELOG.md 2026-04.

- ✅ ACF options pages: Trailblazers Settings (parent) + Registration Settings (sub-page)
- ✅ ACF field group `group_tb_registration_settings` (16 fields, synced)
- ✅ `inc/registration-helpers.php` — sync hook + shortcodes
- ✅ `functions.php` updated with new require line
- ✅ Seven permanent WP registration pages created:
  - `/registration/` — `[tb_reg_router]` (was `[tb_reg_hub]` — update in WP admin)
  - `/registration/returning-families/` — `[tb_reg_form type="returning_family"]`
  - `/registration/new-families/` — `[tb_reg_form type="new_family"]`
  - `/registration/confirmation/` — parent container (permanent)
  - `/registration/confirmation/new-family/` — `[tb_reg_confirmation type="new_family"]`
  - `/registration/confirmation/returning-family/` — `[tb_reg_confirmation type="returning_family"]`
  - `/registration/submit-physicals/` — `[tb_reg_form type="physicals"]`

## Completed — GravityForms Registration Build
As of 2026-04-26. Full field spec in `docs/FORM-FIELD-MAP.md` v2.3.

- ✅ Register New Athlete nested form (permanent/reusable, no year in name)
- ✅ Register Returning Athlete nested form (permanent/reusable, new)
- ✅ 2026 Registration — New Family (5-page, requireLogin)
- ✅ 2026 Registration — Returning Family (5-page, requireLogin, full GPPA config)
- ✅ `inc/registration-helpers.php` updated: `login_redirect` filter,
  `tb_get_family_post_id()` helper, `[tb_reg_router]` shortcode,
  user-state guards on `[tb_reg_form]`
- ✅ `docs/FORM-FIELD-MAP.md` v2.3 committed

---

## Now — Complete Registration Before May 1

### 1. WP Admin — update /registration/ page shortcode
Replace `[tb_reg_hub]` with `[tb_reg_router]` on the `/registration/` page.

### 2. WP Admin — enter GF form IDs in Registration Settings
TB Settings → Registration Settings → Form IDs tab. Enter the IDs GF assigned
to the three registration forms on import (New Family, Returning Family, Physicals).

### 3. GF Admin — post-import GPPA configuration
GPPA rules cannot be reliably exported/imported. Configure manually:

**Returning Family form — Page 1 (Field 60 anchor):**
- Field 60 (Family Post ID, hidden): GPPA values → Post type: family,
  filter: `meta_account_user = current_user:ID`, return: `ID`
- All 16 contact/address fields: GPPA values → filter: `ID = gf_field:60`,
  each returning its respective ACF meta key

**Register Returning Athlete nested form:**
- Field 1 (Family Post ID, hidden): same GPPA query as RF Field 60
- Field 2 (Select Athlete): GPPA choices → Post type: athlete,
  filter: `meta_family = gf_field:1` AND `meta_account_status = Active`,
  label: `post_title`, value: `ID`
- Fields 4–7 (identity, read-only): GPPA values → filter: `ID = gf_field:2`,
  returning `meta_first_name`, `meta_last_name`, `meta_gender`, `meta_dob`

### 4. GF Admin — configure GW Read Only
- Returning Family Field 1 (Family Name): enable GW Read Only
- Register Returning Athlete Fields 4–7 (name, DOB, gender): enable GW Read Only

### 5. GF Admin — configure Stripe feed
On both New Family and Returning Family forms: GF Settings → Stripe → Add New Feed.
Map the Registration Total product field as the payment amount.

### 6. Write submission hook — inc/gravity-helpers.php
Write `gform_after_submission` hooks for both parent forms. See FORM-FIELD-MAP.md
Hook-Set Fields table for the full list of what each hook must create/update.

**New Family hook must:**
- Create Family post (account_user, family_display_name, address, parents_guardians)
- Create Application post (season, family, payment fields, signature, new_returning=New)
- For each nested athlete entry: create Athlete post, then create Enrollment post

**Returning Family hook must:**
- Locate existing Family post via account_user
- Update Family post (address, secondary contact only — do NOT overwrite family_display_name)
- Create Application post (season, family, payment fields, signature, new_returning=Returning)
- For each returning nested athlete entry: create Enrollment post (no new Athlete post)
- For each new nested athlete entry: create Athlete post, then create Enrollment post
- Primary contact guardian_notifications always set to "Yes" (not in form)

**Stripe confirmation hook:**
- Wire hook (likely `gform_stripe_fulfillment`) to update Application
  `payment_status` → `Paid` on successful charge

### 7. Test end-to-end
- New Family: full submission → confirm Family, Application, Athlete,
  Enrollment posts created with correct field values
- Returning Family: full submission → confirm Family updated (not recreated),
  Application created, Enrollments created for returning and new athletes
- Login redirect → confirm non-admin users land at `/registration/`
- User-state routing → confirm new user hits New Family, returning user hits
  Returning Family, already-enrolled user sees message

### 8. Active season post — update handbook URL
Add the 2026 XC handbook URL to the `handbook` field on the 2026 XC Athletic
Season post before opening registration. The form currently shows a placeholder.

---

## Deferred — Schema / Template Updates
Not blocking registration. Return to these after May 1.

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
- CSS styling for `.tb-reg-btn`, `.tb-reg-btn--disabled`, `.tb-reg-hub__date`,
  `.tb-reg-router` — scoped to front-end build
- Add coach → season backreference on `single-coach.php`
- Revisit payment abstraction if workflow becomes more complex
- Add stronger review workflows for physicals and approvals
- Revisit theme folder naming (space in folder name)
- Add `template-parts/` structure when templates grow complex enough to warrant it
- Populate remaining stub `inc/` files as functionality is needed
- Confirm `publicly_queryable` is false on Family, Application, Enrollment, and
  Athletic Physical CPTs (see OPEN-QUESTIONS.md Q3)
- Decide whether TEC `tribe_events_cat` needs sport sub-categories for calendar
  filtering (see OPEN-QUESTIONS.md Q10)
- Additional Singlets (Returning) manual count field on RF Page 5 — future
  improvement: derive from nested form entries rather than manual input