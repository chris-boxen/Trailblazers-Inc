# CHANGELOG

## 2026-05-03 (continued)

### Updated `single-athletic_season.php` — split roster, added gender

- Athlete roster split into two separate sections:
  - Section 4: Athletes (participation_type = Athlete)
  - Section 5: Sibling Runners (participation_type = Sibling Runner)
  - Section 5 only renders if sibling runners exist
- Added `gender` column (sourced from `demographics` group on Athlete post) to both lists
- Added `data-gender` attribute to rows in both lists
- Removed `data-type` attribute from roster rows (redundant now that lists are separate)
- Sort changed from display name to `last_name` for more reliable alpha ordering
- Fixed typo: `firt_name` → `first_name` in entry builder
- New CSS class `.tb-sibling-runners-list` added; requires column definition in `templates.css`


## 2026-05-03

### Added `assets/css/templates.css` — base template styles

New file providing shared CSS for all CPT archive and single templates.
Imported from `assets/css/styles.css`.

Key systems:
- `.tb-list` / `.tb-list-header` / `.tb-list-row` / `.tb-list-link` / `.tb-col`
  — ul/li flex-grid list replacing HTML tables across all templates. Column
  widths are defined per-list via `--tb-cols` CSS custom property, inherited
  by both header and data rows automatically.
- `.tb-single` / `.tb-single-header` / `.tb-single-headline` /
  `.tb-single-image` / `.tb-single-meta` / `.tb-single-section`
  — base classes for single CPT templates.
- `.tb-archive` / `.tb-archive-header` / `.tb-archive-filters`
  — base classes for archive templates.
- `.tb-status` / `.tb-status--current` / `--future` / `--past`
  — status badge pills.

Per-list column definitions committed for coaches, meets, roster, and seasons lists.

---

### Added `archive-athletic_season.php`

New archive template for the `athletic_season` CPT.
- Columns: Season | Sport | Dates | Status
- Data attributes on each row: `data-sport`, `data-status`, `data-year`
- Sorted by `start_date` descending
- Uses full ul/li list system with `.tb-seasons-list` column definition

---

### Refactored `single-athletic_season.php`

- Replaced all three section tables (coaches, meets, athletes) with ul/li
  list structure using `.tb-coaches-list`, `.tb-meets-list`, `.tb-roster-list`
- Added `.tb-single` base class to outer wrapper
- Added `.tb-single-section` to all three section wrappers
- Added `.tb-single-header` layout with `.tb-single-headline` (left) and
  `.tb-single-header-secondary-section` (right) grouping image + CTA
- Meta row converted from separate `<p>` tags to flex `<span>` items inside
  `.tb-single-meta`
- Status badge updated to use `.tb-status` base class for pill shape
- `featured_image` corrected from `get_post_thumbnail_id()` to
  `get_field( 'featured_image' )` — field is ACF image, not WP post thumbnail
- Handbook CTA wrapped in `.button` class and moved into secondary header section

---

### Fixed `athletic_season` permalink slug

ACF CPT rewrite slug updated from `season` to `seasons` in both the ACF admin
and `acf-json/post_type_tb_athletic_season.json`. Permalinks flushed.


## 2026-05-02


### Production cutover — domain swap and Stripe live mode

- Primary domain switched to `trailblazers.team` on Flywheel
- GF confirmation redirect URLs updated from `trailblazers-inc.flywheelsites.com`
  to `trailblazers.team`
- Stripe switched from Test to Live mode in GF Settings → Stripe
- Stripe webhook endpoint updated to
  `https://trailblazers.team/?callback=gravityformsstripe`
- Live Signing Secret entered in GF
- Webhook delivery verified — resend of existing event returned 200 OK


### Fixed RF CC `payment_status` not writing `Paid` on Enrollment

**Root cause:** `payment_status` is a sub-field of the `status` group
(`field_69c9e3f08452e`) on the Enrollment CPT. It was absent from the group
array write in `tb_create_enrollment_post()`, causing `update_field()` to
silently fail. Application `payment_status` is top-level and was writing
correctly; Enrollment was not.

**Fix:**
- Added `'payment_status' => 'Not Received'` to `$defaults` in
  `tb_create_enrollment_post()`
- Added `'payment_status' => $args['payment_status']` to the status group
  array write
- Passed `payment_status` from all three `tb_create_enrollment_post()` call
  sites: NF handler, RF returning athlete loop, RF new athlete loop

**Verified on staging** — both Application and Enrollment write `Paid`
correctly on RF CC submissions.

---

### Returning Family CC path unblocked

The RF Credit Card path was blocked by a chain of three distinct issues,
all resolved in this session.

**Issue 1: Stripe Payment Element re-initialization loop**
`rgba` CSS values in `styles.css` were being passed to Stripe's appearance
API, which only accepts HEX, `rgb()`, or `hsl()`. This caused repeated
Stripe initialization failures, multiplying `#stripe-payment-link` DOM
elements and preventing PaymentIntent creation.

Fix: replaced three `rgba()` values in `styles.css` with `rgb()` equivalents:
- `--gf-ctrl-bg-color: rgb(245, 245, 245)`
- `--gf-ctrl-bg-color-hover: rgb(235, 235, 235)`
- `--gf-ctrl-bg-color-focus: rgb(235, 235, 235)`

**Issue 2: GF Stripe Payment Element incompatibility with GPPA**
With the CSS fix applied, the RF form still failed with a 500 error on CC
submission. Root cause: `GF_Stripe_Payment_Element::start_checkout()` aborts
with "Submission is not valid" before creating a PaymentIntent. The NF form
(no GPPA) does not exhibit this behavior. A GPPA + GF Stripe interaction
prevents `start_checkout()` from validating the RF form correctly. Gravity
Wiz support ticket filed.

Fix: unchecked "Use Stripe's Payment Element" on RF Stripe field (field 57).
This switches RF to the `Experimental_GF_Elements_Handler` flow, which
creates the PaymentIntent via AJAX (`ajax_create_payment_intent`) after entry
creation rather than during form submission. The Payment Element flow remains
active on the NF form (field 50) where it works correctly.

**Issue 3: Webhook payload style**
With the experimental handler, GF waits for a webhook to confirm payment and
update entry status. The active webhook was configured with "Thin" payload
style, which does not include enough data for GF to process the event. Entry
status remained "Processing" indefinitely.

Fix: switched active Stripe sandbox webhook to "Snapshot" payload style.
Webhook now delivers `payment_intent.succeeded` and `charge.succeeded` events
correctly; GF processes them and marks entries as paid.

**Verified:** RF CC end-to-end — charge confirmed in Stripe, webhook
delivering (200 OK, "Callback processed successfully"), all CPT writes
correct.

---

### Fixed RF eligibility check and IDs group write

Two bugs found during RF Check/Cash testing:

**`tb_returning_athlete_eligibility_confirmed()`** — Policy Compliance
checkboxes (fields 15.1 for Athlete, 18.1 for Sibling Runner) were removed
from the Register Returning Athlete nested form as redundant with the handbook
agreement section. The function was still checking for these fields, causing
`eligibility_confirmed` to always write `false` on returning athlete
Enrollments. Fields removed from both the Athlete and Sibling Runner
conditions.

**Section 4 new athlete IDs group** — The RF handler's new athlete loop
(`tb_handle_returning_family()` Section 4) was not writing the IDs group to
newly created Athlete posts. The NF handler writes the IDs group using the
GP Unique ID from field 52. On RF, the family already exists and has a
`family_id` stored on the Family post. Fix: read `family_id` from the
existing Family post via `get_field('ids', $family_id)` and write it to each
new Athlete post created via the RF form.

---

### Fixed payment_status logic in both handlers

**Problem:** The experimental handler creates the GF entry first, then
creates the PaymentIntent via AJAX. At the time `gform_after_submission`
fires, `$entry['payment_status']` is `'Processing'` — not `'Paid'`. The
prior logic (`$entry['payment_status'] === 'Paid'`) therefore always wrote
`'Not Received'` to ACF on CC submissions.

**Fix:** Changed both NF and RF handlers to check the payment method field
value instead of entry payment status:
- NF handler: `rgar( $entry, '48' ) === 'Credit Card'`
- RF handler: `rgar( $entry, '55' ) === 'Credit Card'`

If Credit Card, write `'Paid'`. Otherwise write `'Not Received'`. The CC
failure guard already aborts post creation if the charge fails, so any post
that exists was paid.

**Known issue:** Despite this fix, `payment_status` is not writing `Paid`
on RF CC submissions in practice. Field 55 contains `'Credit Card'` in the
entry. Root cause undiagnosed — deferred to post-launch backlog. Workaround
is manual update in WP admin.

---

## 2026-05-01

### Hook fixes + end-to-end verification (New Family)

- ACF Group sub-field writes fixed — switched to parent group array pattern
  in `tb_create_athlete_post()` and `tb_create_enrollment_post()`
- Eligibility check fixed — removed non-existent field IDs (14, 17) from
  `tb_new_athlete_eligibility_confirmed()`
- Zip code written to Family post (NF field 51, RF field 62)
- Family ID (field 52, GP Unique ID) written to Family post, each Athlete
  post (IDs group), and WP User — three-way linkage confirmed
- Athlete slug computed via `sanitize_title()` from first + last name
- Duplicate `payment_amount` block removed from NF handler Section 1
- New Family Check/Cash — all four CPTs verified correct
- New Family Credit Card — Stripe charge confirmed, all CPTs verified,
  notification emails confirmed via Gravity SMTP

### CC testing + payment + handbook fixes

- Handbook URL added to 2026 XC Athletic Season post
- `gform_field_value_tb_handbook_url` filter added — reads ACF link field
  array, extracts `['url']`; GF HTML merge tag fixed (missing `@` prefix)
- `payment_status` logic updated to read `$entry['payment_status']` at hook
  time (later superseded by payment method field check — see 2026-05-02)
- `payment_amount` uses `GFCommon::get_order_total($form, $entry)` in both
  handlers — reliable for both CC and Check/Cash
- Stripe confirmation hook stub removed — not needed for NF Payment Element
  redirect flow
- GF notification merge tag corrected from Order Total field to
  `{payment_amount}`
- Singlet count formula on NF Page 5 corrected — now counts athletes who
  requested a singlet rather than mirroring total athlete count
- Confirmation page content added for both NF and RF in Registration
  Settings → Messaging

---

## 2026-04

### Built centralized registration infrastructure

Replaced the previous pattern of duplicating a full section of WP pages each
season (Registration XC 2025 → New Families, Returning Families, Confirmation,
Submit Physicals) with a permanent, shortcode-driven system backed by a
centralized ACF options page.

**Problem solved:** Season changeover previously required duplicating 4+ pages
and manually updating form embeds, confirmation text, and dates throughout.
The new system requires only updating the options page and building new GF
forms.

**What was built:**

ACF options pages (created via ACF UI, JSON auto-captured):
- `options_page_trailblazers-settings.json` — top-level TB Settings parent menu
- `options_page_registration-settings.json` — Registration Settings sub-page

ACF field group:
- `group_tb_registration_settings.json` — 16 fields across three tabs:
  - **Season & Status:** `reg_active_season`, `reg_status`,
    `reg_returning_open`, `reg_new_family_open`, `reg_close`
  - **Form IDs:** `reg_new_family_form_id`, `reg_returning_family_form_id`,
    `reg_physicals_form_id`
  - **Messaging:** `reg_coming_soon_message`, `reg_closed_message`,
    `reg_new_family_confirmation`, `reg_returning_family_confirmation`,
    `reg_physicals_confirmation`

New file `inc/registration-helpers.php`:
- `acf/save_post` sync hook — writes `tb_active_season_id` site option
- `tb_reg_button_state()` helper
- `tb_reg_date_label()` helper
- `[tb_reg_hub]` shortcode
- `[tb_reg_form type="..."]` shortcode
- `[tb_reg_confirmation type="..."]` shortcode

Seven permanent WP pages created.

### Built GravityForms registration forms
See `docs/FORM-FIELD-MAP.md` v2.3 for full field spec.

- Register New Athlete nested form (permanent/reusable)
- Register Returning Athlete nested form (permanent/reusable)
- 2026 Registration — New Family (5-page, requireLogin)
- 2026 Registration — Returning Family (5-page, requireLogin, GPPA)

### Submission hooks written

`inc/gravity-helpers.php` — `gform_after_submission` handlers for both NF
and RF forms. Six audit fixes applied post-write. See NEXT-STEPS.md
Completed — Submission Hooks + Code Audit for full list.

---

## 2026-03

### Designed GravityForms registration form architecture

Replaced the previous multi-form, multi-user approach with a simplified
architecture reflecting the new single-household-user model. Key decisions:
- Registration entry point is a WP page with two buttons, not a form
- New Family and Returning Family are separate parent forms (5 pages each)
- Single combined nested form for Athlete + Sibling Runner registration
- Both forms require login
- Active season populated via `gform_field_value` filter
- Handbook URL pulled dynamically from Athletic Season CPT
- Optional processing contribution as native GF Product field

### Added participation_type to Athlete CPT
Denormalized convenience field for archive filtering. Source of truth remains
Enrollment. See SCHEMA.md.

### Added payment_amount to Application CPT
Number field (step 0.01, prepend $). Set by hook from GF order total.

### Added per-season feature flags to Athletic Season CPT
`calendar_show_meets`, `calendar_show_practices`, `results_enabled`,
`link_milesplit`, `link_athletic_net`, `results_unavailable_message`.
Controls display only — do not prevent data entry.