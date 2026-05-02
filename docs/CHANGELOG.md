# CHANGELOG

## 2026-05-02

### Production cutover — domain swap and Stripe live mode

- Primary domain switched to `trailblazers.team` on Flywheel
- GF confirmation redirect URLs updated from `trailblazers-inc.flywheelsites.com`
  to `trailblazers.team`
- Stripe switched from Test to Live mode in GF Settings → Stripe
- Stripe webhook endpoint updated to
  `https://trailblazers.team/?callback=gravityformsstripe`
- New Live Signing Secret entered in GF
- Webhook delivery verified — resend of existing event returned 200 OK;
  endpoint confirmed active on production domain
- Registration is open

## 2026-04

### Built centralized registration infrastructure

Replaced the previous pattern of duplicating a full section of WP pages each
season (Registration XC 2025 → New Families, Returning Families, Confirmation,
Submit Physicals) with a permanent, shortcode-driven system backed by a centralized
ACF options page.

**Problem solved:** Season changeover previously required duplicating 4+ pages and
manually updating form embeds, confirmation text, and dates throughout. The new
system requires only updating the options page and building new GF forms.

**What was built:**

ACF options pages (created via ACF UI, JSON auto-captured):
- `options_page_trailblazers-settings.json` — top-level TB Settings parent menu
- `options_page_registration-settings.json` — Registration Settings sub-page

ACF field group:
- `group_tb_registration_settings.json` — 16 fields across three tabs:
  - **Season & Status:** `reg_active_season` (Post Object → athletic_season),
    `reg_status` (Select: coming_soon / open / closed),
    `reg_returning_open` (DateTime), `reg_new_family_open` (DateTime),
    `reg_close` (DateTime)
  - **Form IDs:** `reg_new_family_form_id`, `reg_returning_family_form_id`,
    `reg_physicals_form_id` (Number fields, blank until forms are built)
  - **Messaging:** `reg_coming_soon_message`, `reg_closed_message`,
    `reg_new_family_confirmation`, `reg_returning_family_confirmation`,
    `reg_physicals_confirmation` (Wysiwyg fields)

New file `inc/registration-helpers.php` (loaded via `functions.php`):
- `acf/save_post` sync hook — writes `tb_active_season_id` site option whenever
  `reg_active_season` is saved on the options page. Keeps existing
  `gform_field_value` hooks working without changes.
- `tb_reg_button_state()` helper — returns `enabled` / `pending` / `closed`
  based on open/close datetimes vs. current time
- `tb_reg_date_label()` helper — returns human-readable sub-label for each state
- `[tb_reg_hub]` shortcode — hub page with two date-driven buttons
- `[tb_reg_form type="..."]` shortcode — renders GF form or status message
- `[tb_reg_confirmation type="..."]` shortcode — renders WYSIWYG confirmation content

Five permanent WP pages created (never to be duplicated or renamed):
- `/registration/` — `[tb_reg_hub]`
- `/registration/returning-families/` — `[tb_reg_form type="returning_family"]`
- `/registration/new-families/` — `[tb_reg_form type="new_family"]`
- `/registration/confirmation/` — structure pending Q12 decision
- `/registration/submit-physicals/` — `[tb_reg_form type="physicals"]`

**Button state logic:** When `reg_status` is `open`, each button is evaluated
independently against its own open datetime and the shared close datetime.
Returning families can open 2–3 weeks before new families. The manual
`reg_status` override (coming_soon / closed) always trumps date logic.

**Date field conditional logic:** The three datetime fields show whenever
`reg_status != closed`. This allows setting open dates while still in
coming_soon state, which is the normal pre-season setup flow. Fields are
hidden only when closed, since dates are irrelevant post-season.

**`tb_active_season_id` note:** The existing site option and all hooks that
read it are unchanged. The sync hook in `registration-helpers.php` simply
keeps it in sync automatically whenever the options page is saved. Confirmed
working: saving the options page with 2026 XC selected auto-populated the
`tb_active_season_id` option correctly.

**Implementation note — ACF options page JSON:** ACF options page JSON files
cannot be reliably hand-crafted and dropped into `acf-json/` for sync. ACF's
Sync UI handles field groups only. Options pages must be created in the ACF
admin UI (ACF → Options Pages → Add New), after which ACF auto-generates their
JSON. Field groups targeting options pages sync normally.

**Open:** Confirmation page structure (Q12). Form IDs remain blank until GF
forms are built. CSS for `.tb-reg-btn`, `.tb-reg-btn--disabled`,
`.tb-reg-hub__date` deferred to front-end build.

---

## 2026-03 (continued from previous entries)

### Designed GravityForms registration form architecture
Replaced the previous multi-form, multi-user approach with a simplified architecture
reflecting the new single-household-user model. Key decisions:
- Registration entry point is a WP page with two buttons, not a form
- New Family and Returning Family are separate parent forms (5 pages each)
- Single combined nested form for Athlete + Sibling Runner registration
  (participation type radio drives conditional eligibility blocks)
- `Register New Parent`, `Register Returning Athlete`, and `Register Sibling Runner`
  from the 2025 form set are eliminated
- Both forms require login; Returning Family locates the Family post via
  `account_user = get_current_user_id()` — no user-visible Family ID
- Active season populated via `gform_field_value` filter reading `tb_active_season_id`
  site option
- Handbook page (Page 3) pulls handbook URL dynamically from the Athletic Season CPT
  `handbook` link field via `gform_field_value`
- Payment page includes optional processing contribution as a native GF Product field
  (radio buttons: No thanks $0 / Help a little $3 / Cover processing $5 / Pay it
  forward $10); folds into GF order total natively with no custom code
- Full field map documented in `docs/FORM-FIELD-MAP.md`

### Added participation_type to Athlete CPT
Added `participation_type` select field (Athlete / Sibling Runner, allow null) to
`group_tb_athlete.json`. Denormalized convenience field for archive filtering. Source
of truth for season-specific type remains Enrollment. Updated by enrollment creation
hook. Empty values treated as Athlete in templates and JS filters. See SCHEMA.md.

### Added payment_amount to Application CPT
Added `payment_amount` number field (step 0.01, prepend $) to
`group_tb_application.json`. Set by hook from GF order total at submission. Reflects
combined registration fee plus any optional processing contribution donation.

### Resolved: account_status vs participation_type distinction
Confirmed Alumni belongs in `account_status`, not `participation_type`. The two
fields answer different questions — lifecycle state vs. season enrollment type.
Documented in SCHEMA.md.

### Determined build sequence for registration system
Data population must precede form build; form build must precede hook code (hooks
require real GF field IDs). Parked GravityForms work pending data population thread.

### Added per-season feature flags to Athletic Season CPT
New True/False fields added to `group_tb_athletic_season`:

| Field | Purpose |
|---|---|
| `calendar_show_meets` | Whether upcoming meets publish to TEC public calendar |
| `calendar_show_practices` | Whether practices publish to TEC public calendar |
| `results_enabled` | Whether results are surfaced in templates (display control only) |
| `link_milesplit` | Whether Milesplit athlete links are rendered |
| `link_athletic_net` | Whether AthleticNet athlete links are rendered |

New Textarea field: `results_unavailable_message` — shown in templates when
`results_enabled = false`. Falls back to a generic message if blank.

Flags control display only. They do not prevent data entry. An admin can flip
`results_enabled` at any time to start or stop surfacing results for a season.

**Naming note:** The field was intentionally named `results_enabled` rather than
`track_results` to avoid semantic ambiguity with "track and field results."

---

### Resolved: `results_enabled` naming
Confirmed `results_enabled` as the canonical field name. `track_results` was
rejected because in a track and field context it reads as a noun phrase
("track-and-field results") rather than the intended verb phrase ("record/monitor
results"). Documented in SCHEMA.md.
