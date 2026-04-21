# SCHEMA

## Field group conventions

### Key naming
ACF field keys follow the pattern `field_tb_{name}`. Keys must be globally unique
across the entire installation. When a field name collision is possible (e.g., a field
named `family` exists on both a CPT and a user profile), the key uses an infix to
disambiguate (e.g., `field_tb_user_family` vs `field_tb_family`).

### Field name conventions
- snake_case throughout
- Prefix `tb_` is used on field keys, not field names
- Field names are the ACF `name` attribute — used in `get_field()` calls

---

## Options pages

### Trailblazers Settings (`trailblazers-settings`)
Top-level admin menu. Redirects to first child (Registration Settings). No fields
of its own. JSON: `options_page_trailblazers-settings.json`.

### Registration Settings (`registration-settings`)
Sub-page under Trailblazers Settings. Houses the centralized registration
configuration used by the `[tb_reg_hub]`, `[tb_reg_form]`, and
`[tb_reg_confirmation]` shortcodes. JSON: `options_page_registration-settings.json`.

**Field group:** `group_tb_registration_settings.json`
**Location rule:** `options_page == registration-settings`

#### Tab: Season & Status

| Field label | Field name | Type | Notes |
|---|---|---|---|
| Active Season | `reg_active_season` | Post Object → `athletic_season` | Single, returns object. Saving this field also writes `tb_active_season_id` site option via `acf/save_post` hook in `registration-helpers.php`. |
| Registration Status | `reg_status` | Select | `coming_soon` / `open` / `closed`. Default: `coming_soon`. Manual override — always trumps date logic. |
| Returning Family Registration Opens | `reg_returning_open` | Date Time Picker | Return format `Y-m-d H:i:s`. Shown when `reg_status != closed`. |
| New Family Registration Opens | `reg_new_family_open` | Date Time Picker | Return format `Y-m-d H:i:s`. Shown when `reg_status != closed`. Typically 2–3 weeks after `reg_returning_open`. |
| Registration Closes | `reg_close` | Date Time Picker | Return format `Y-m-d H:i:s`. Shown when `reg_status != closed`. Applies to both family types. |

#### Tab: Form IDs

| Field label | Field name | Type | Notes |
|---|---|---|---|
| New Family Form ID | `reg_new_family_form_id` | Number | GF form ID. Left blank until forms are built. |
| Returning Family Form ID | `reg_returning_family_form_id` | Number | GF form ID. Left blank until forms are built. |
| Submit Physicals Form ID | `reg_physicals_form_id` | Number | GF form ID. Left blank until forms are built. |

#### Tab: Messaging

| Field label | Field name | Type | Notes |
|---|---|---|---|
| Coming Soon Message | `reg_coming_soon_message` | Wysiwyg | Shown on hub + form pages when status is `coming_soon`. |
| Closed Message | `reg_closed_message` | Wysiwyg | Shown on hub + form pages when status is `closed` or after close date. |
| New Family Confirmation Text | `reg_new_family_confirmation` | Wysiwyg | Rendered by `[tb_reg_confirmation type="new_family"]`. |
| Returning Family Confirmation Text | `reg_returning_family_confirmation` | Wysiwyg | Rendered by `[tb_reg_confirmation type="returning_family"]`. |
| Physicals Confirmation Text | `reg_physicals_confirmation` | Wysiwyg | Rendered by `[tb_reg_confirmation type="physicals"]`. |

#### Button state logic
When `reg_status` is `open`, each registration button is evaluated independently:

| Condition | Result |
|---|---|
| `reg_status = coming_soon` | Both buttons disabled; coming soon message shown |
| `reg_status = closed` | Both buttons disabled; closed message shown |
| Now < `reg_returning_open` | Both buttons pending; open date shown beneath each |
| Now ≥ `reg_returning_open`, but < `reg_new_family_open` | Returning enabled; New pending |
| Now ≥ `reg_new_family_open`, and < `reg_close` | Both enabled; close date shown |
| Now ≥ `reg_close` | Both disabled; "Registration is closed." shown |

#### `tb_active_season_id` sync
The `acf/save_post` hook in `inc/registration-helpers.php` (priority 20) reads
`reg_active_season` from the options page on every save and writes the season post
ID to the `tb_active_season_id` site option. All existing `gform_field_value` hooks
that read `get_option('tb_active_season_id')` continue to work without modification.

---

## CPT field groups

### group_tb_athlete.json
Attached to `athlete` CPT.

Notable fields:
- `participation_type` — Select (Athlete / Sibling Runner, allow null). Denormalized
  convenience field for archive filtering. Source of truth for season-specific type
  remains Enrollment. Updated by enrollment creation hook.
- `account_status` — Select. Lifecycle state (Active / Inactive / Alumni / etc.).
  Distinct from `participation_type`. Alumni belongs here, not in participation_type.

---

### group_tb_application.json
Attached to `application` CPT.

Notable fields:
- `payment_amount` — Number (step 0.01, prepend $). Set by hook from GF order total
  at submission. Reflects combined registration fee plus optional processing
  contribution donation.
- `digital_signature` — Text. Parent types full legal name on waiver page.
- `submission_date` — Date. Defaults to today at submission.

---

### group_tb_athletic_season.json
Attached to `athletic_season` CPT.

Notable fields:
- `handbook` — Link field. URL used by `gform_field_value` hook to populate the
  handbook URL hidden field on registration forms.
- `customize_data` group — contains per-season feature flags:
  - `calendar_show_meets` — True/False. Controls public meet calendar surfacing.
  - `calendar_show_practices` — True/False. Controls practice calendar surfacing.
  - `results_enabled` — True/False. Controls results display in templates.
  - `link_milesplit` — True/False. Controls Milesplit link rendering on athlete pages.
  - `link_athletic_net` — True/False. Controls AthleticNet link rendering.
  - `results_unavailable_message` — Textarea. Fallback when `results_enabled` is false.
- `Dates` group — registration and season date fields.

**Naming note:** `results_enabled` was chosen over `track_results` to avoid the
ambiguity of "track-and-field results" vs. "record/monitor results."

---

### group_tb_athletic_meet.json
Attached to `tribe_events` (TEC). File key retained as `group_tb_athletic_meet`
for historical continuity.

Fields:
- `season` — Post Object → `athletic_season`
- `results_status` — Select (Future / Pending / Available). Gates per-meet results
  display in `tribe/events/single-event.php`.

---

### group_tb_athletic_result.json
Attached to `athletic_result` CPT.

Notable fields:
- `meet` — Post Object → `tribe_events` (not `athletic_meet`, which is retired)
- `athletic_event` — Post Object → `athletic_event` (renamed from `event`)
- `result_display` — Text. Human-readable result string.
- `result_time_seconds` — Number. Normalized for time-based events.
- `result_distance_meters` — Number. Normalized for distance events.
- `result_height_meters` — Number. Normalized for height events.
- `result_points` — Number. For points-based events.

---

### group_tb_enrollment.json
Attached to `enrollment` CPT.

**Critical constraint:** Post Object fields (`season`, `family`, `athlete`,
`application`) are at the TOP LEVEL of this field group — not inside any Group
wrapper. WP Ultimate CSV Importer does not reliably resolve post object
relationships when fields are nested inside a Group wrapper.

---

## User field group

### group_tb_user.json
Attached to all WP user profiles.

Fields:
- `family` — Post Object → `family`. Direct reference from user to their Family post.
  Provides a GPPA-friendly lookup path without a meta query.
- `family_id` — Text. Scalar TB identifier (e.g. `TB-66281`).

**Linkage rule:** All three of these must be populated atomically:
1. `Family.account_user` = WP user ID
2. `User.family` = Family post ID
3. `User.family_id` = Family TB identifier string

**Key naming note:** User field keys use the `_user_` infix (`field_tb_user_family`,
`field_tb_user_family_id`) to avoid collisions with identically-named CPT fields.
Field `name` values are intentionally the same as CPT counterparts — they live in
`wp_usermeta`, not `wp_postmeta`, so there is no storage collision.

---

## WP Ultimate CSV Importer — ACF import constraints

### Post Object fields inside ACF Group wrappers
WP Ultimate CSV Importer does not reliably resolve ACF Post Object (and
Relationship) fields when those fields are nested inside an ACF Group field.
Resolution by post title works correctly for top-level fields only.

**Rule:** Any CPT whose post object fields will be populated via CSV import must
have those fields at the top level of the field group — not inside a Group wrapper.

### ACF options page JSON
Options page JSON files cannot be hand-crafted and synced via ACF's Sync UI.
ACF Sync only handles field groups. Options pages must be created in the ACF admin
UI (ACF → Options Pages → Add New). ACF auto-generates their JSON after creation.
Field groups targeting options pages sync normally.
