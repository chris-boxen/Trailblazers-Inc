# CHANGELOG

## 2026-05-16

### PR/SR badges and sort improvements — season roster + meet results

#### `tribe-events/tb-meet-results.php` — PR/SR badges added to results list

- After the grade lookup block, a bulk `WP_Query` collects all result IDs
  from `$results_by_event` and fetches matching `athletic_record` posts in a
  single `IN` query. Builds `$result_record_map[ result_id ] => [ record_types ]`.
- In the render loop, the Result column now emits `.tb-record-badge` spans for
  any record types earned on that result. Same badge classes as `single-athlete.php`.

#### `single-athletic_season.php` — roster records column redesigned

**Problem:** `$roster_record_map` used a winner-takes-all accumulation: SR beat
PR, and only one record was stored per athlete. This caused 2025 roster to show
SR only and 2026 roster to show PR only, even when athletes had both.

**Fix — dual record storage:**
- `$roster_record_map` now stores `pr` and `sr` as independent sub-keys.
- Each stores `display`, `date`, and `seconds` (from `result_time_seconds`).
- Within each type, the most recent record by meet date is kept.

**Fix — render:**
- Records column renders both badges when present: `[PR 16:29.52] [SR 16:29.52]`
- `<br>` separator removed; column uses flex row layout instead.
- `data-pr` and `data-sr` attributes added to each `<li>` with time-in-seconds
  value for Isotope sort support. Variables set in a PHP block immediately before
  the `<li>` tag.

#### `assets/css/templates.css` — scoped roster badge overrides

- New scoped rules on `.tb-roster-list-wrap .tb-record-badge` and
  `.tb-sibling-runners-list .tb-record-badge` resize the badge to normal reading
  size (`0.82rem`) and use `inline-flex` with `align-items: center` so the time
  value sits inside the pill.
- `.tb-roster-list-wrap .tb-col:last-child` and
  `.tb-sibling-runners-list .tb-col:last-child` set `display: flex; flex-wrap: wrap;
  gap: 0.4rem` so PR and SR badges sit side by side.
- Base `.tb-record-badge` unchanged — inline result badges on `single-athlete.php`
  and `tb-meet-results.php` are unaffected.

#### `assets/js/tb.js` — numeric sort fixes

- `grade` sort converted from string shorthand to `parseInt` function — fixes
  lexicographic ordering (10, 11, 12, 6, 7…) → correct numeric order.
- `pr` and `sr` sorts converted from string shorthand to `parseFloat` functions —
  fixes same issue for decimal seconds values.
- All three use `Infinity` as the fallback for missing/empty values so athletes
  without a record sort to the bottom.

## 2026-05-16

### Athletic Records — display, badges, and auto-generation

#### `single-athlete.php` — Personal Records dedup + result badges

**Problem:** The Personal Records section showed every `athletic_record` post for the athlete, including all historical intermediate records. In a full season where every race set a new record, this list becomes long and redundant. The desired display is one row per event + record_type showing the current (most recent) record only.

**Fix — dedup:**

- Added `result_id` and `meet_date_raw` (Y-m-d) keys to each entry in the `$records` array.
- After the records query loop, `$result_record_map` is built from the full list (maps `result_id → [record_types]`) before any dedup — needed by the badges feature below.
- Dedup reduces `$records` to one entry per `event_id + record_type`, keeping the entry with the most recent `meet_date_raw`. This collapses the historical list to current PR and SR only.
- Records sorted PR before SR, then by event name within each type.

**Fix — result badges:**

- In Section 3 (Results history), each result's time span now checks `$result_record_map[$r['result_id']]` and renders inline `<span class="tb-record-badge">` elements for any record types earned on that result. Badges appear on every result where a record was set, not just the current best — preserving the historical narrative.
- `$result_record_map` is built from the full pre-dedup `$records` array, so all historical PR/SR records earn badges.

**Key implementation note:** `$result_record_map` must be built before the dedup. The dedup runs on `$records`; the map is a separate variable that retains all historical entries.

---

#### `single-athletic_season.php` — Records column populated in roster

**Problem:** The athlete roster header had a "Records" column but each row only rendered two `<span class="tb-col">` elements (Athlete and Grade). No records data was fetched. Column was always blank.

**Fix:**

- After `usort()` on the `$athletes` array, a bulk `WP_Query` fetches all `athletic_record` posts where `athlete IN $athlete_ids_for_records`.
- For each record: if it's an SR and its linked result's meet is in `$season_meet_ids`, it qualifies as a season SR. All-time PR always qualifies. SR beats PR in the display; within the same type, the most recent record by meet date is kept.
- Result stored in `$roster_record_map[athlete_id]`.
- A third `<span class="tb-col">` added to each roster row (athletes and sibling runners) rendering `{type} {result_display}` or `—`.

---

#### `inc/results-helpers.php` — auto-generate PR/SR on result save

**New function: `tb_auto_generate_records()`** Attached to `acf/save_post` at priority 25 (after `result_time_seconds` sync at priority 20). On save of an `athletic_result` post via WP admin:

1. Reads `athlete`, `athletic_event`, `meet` from the result.
2. Reads `measurement_type` and `is_relay` from the linked Athletic Event. Skips relay events.
3. Selects the numeric comparison field by measurement type:
    - Time → `result_time_seconds` (lower is better)
    - Distance → `result_distance_meters` (higher is better)
    - Height → `result_height_meters` (higher is better)
    - Points → `result_points` (higher is better)
4. Checks for existing record posts already pointing to this result (idempotency guard — prevents duplicates on re-save).
5. PR check: queries all prior results for this athlete + event (excluding current). If no priors exist, or this result beats the prior best, creates a PR record post.
6. SR check: queries the season's meet IDs, then prior results for this athlete + event scoped to those meets. If no season priors exist, or this result beats the season best, creates an SR record post.

**Does not fire during WPUCI imports.** Use Tools → Generate Records for post-import bulk generation.

---

**New function: `tb_create_record_post()`** Shared helper called by both `tb_auto_generate_records()` and `tb_run_generate_records()`. Creates a single `athletic_record` post and writes all four fields: `athlete`, `event`, `result`, `record_type`.

Post title format: `{Athlete Display Name} – {Event Name} {Record Type}` (e.g. "Jack Anderson – 5K PR").

Field name reminder: the Athletic Event relationship on `athletic_record` is named `event`, not `athletic_event` (differs from `athletic_result`).

---

**New function: `tb_run_generate_records()` + Tools → Generate Records page** Bulk companion for post-WPUCI-import record generation. Accessible at Tools → Generate Records in WP admin. Select a season, click the button.

Process:

1. Fetches all meet IDs in the season (filtered by `athletic-meet` taxonomy, matching existing query patterns).
2. Builds a meet_id → meet_date map for chronological sorting.
3. Fetches all results in those meets; groups by athlete + event.
4. Per athlete + event group:
    - Establishes a pre-season all-time best baseline (results in other seasons) so cross-season PRs are evaluated correctly.
    - Sorts this season's results chronologically (ascending by meet date).
    - Walks results in order, tracking running all-time best and season best. Creates PR and/or SR record posts each time a best improves.
5. Idempotent: skips any result that already has a record post of that type pointing to it. Safe to re-run after partial generation.
6. Returns `['created' => int, 'skipped' => int]`; displayed in admin notice after redirect.

---

#### `assets/css/templates.css` — record badge styles

New rules:

- `.tb-record-badge` — inline-block pill, small caps, tight padding
- `.tb-record-badge--pr` — green background (`var(--tb-green)`)
- `.tb-record-badge--sr` — gold background (`var(--tb-gold)`)

## 2026-05-12

### Multi-grid Isotope refactor — tb.js, isotope.css, single-athletic_event.php

- Isotope now supports multiple independent grids per page via a
  `.tb-isotope-instance` wrapper pattern and jQuery `.each()` scoped init
- Each instance maintains its own `filters` object and `sortValue` — no
  shared global state between grids
- `manageCheckbox`, `manageSelect`, `getComboFilter` refactored to accept
  `filters` as a parameter instead of reading a global
- ID selectors replaced with classes throughout:
  `#directory` → `.tb-isotope-grid`
  `#ui-controls` → `.tb-ui-controls`
  `#sorts` → `.tb-sorts`
  `#filter-controls` → `.tb-filter-controls`
  `#sort-controls` → `.tb-sort-controls`
- Numeric sort functions added for `place` and `result_seconds` (previously
  string comparison would mis-sort values like "10" < "2")
- Entire `tb.js` wrapped in jQuery noConflict IIFE `(function($){ })(jQuery)`
  — fixes `$ is not defined` error on TEC templates where
  `jQuery.noConflict()` is called before page scripts execute
- `assets/css/isotope.css` updated — all ID selectors converted to classes
- `single-athletic_event.php` updated — `.tb-isotope-instance` added to
  section tags; ID attributes removed from controls

### Refactored tribe/events/single-event.php — per-event Isotope instances

- Results data structure flattened from `event → heat → results` to
  `event → results`; heat is now a filterable data attribute on each row,
  allowing athletes to sort and compare times across heats within an event
- `$heats_by_event[ event_key ]` added alongside results accumulator —
  provides unique heat names per event for per-event filter UIs
- Each `.tb-results-event` div is now a `.tb-isotope-instance`
- Per-event heat filter select rendered only when that event has heat values
- Sort buttons per event: Place (default), Time, Name
- `data-last-name` added — sourced from `$last` already in scope during
  results loop
- `data-gender` added — sourced from `demographics` group on Athlete post;
  `$gender` initialized before `if ( $athlete_id )` block to prevent
  PHP notices on rows with no linked athlete

## 2026-05-10 (continued)

### Added heat field to group_tb_athletic_result

- New text field `heat` (key: `field_tb_heat`) added after `event_name`
- Import-populated; empty on all existing 951 XC results pending re-import
- Documented in SCHEMA.md

### Built tribe/events/single-event.php — TEC meet results view

- `tribe/events/v2/default-template.php` — overrides TEC outer wrapper;
  renders TEC natively for all events, appends results section for
  athletic-meet events only
- `tribe/events/single-event.php` — results section only (no header/footer);
  grouped by event → heat; heat sub-headers and filter gated by $has_heats;
  heat column always shown (— when empty); grade via bulk enrollment query;
  columns: Athlete | Grade | Heat | Result | Place
- `.tb-meet-event-list { --tb-cols: 3fr 1fr 1.5fr 1.5fr 1fr; }` added to templates.css

### Updated single-athlete.php — heat column added

- Heat column added between Event and Result
- data-heat attribute added to li.tb-list-row
- CSS updated: .tb-results-list { --tb-cols: 2fr 1fr 1fr 1fr 1fr 1fr; }

### Refactored single-athletic_event.php

- Base classes applied throughout: .tb-single, .tb-single-header,
  .tb-single-headline, .tb-single-meta, .tb-single-section
- Records and results sections converted from <table> to ul/li list system
- Results history restructured from season → meet grouped tables to single
  flat list across all seasons and meets
- Heat column added; grade and gender data attributes added
- Columns: Meet | Date | Athlete | Grade | Heat | Result
- Row-level link removed; Meet and Athlete cells link independently (Option C)
- Fixed athlete name reading in both records and results loops:
  get_field( 'names', $athlete_id ) group pattern applied
- Fixed results_enabled: now read via customize_data group
- Grade sourced via bulk enrollment query keyed by [athlete_id][season_id]
- Gender sourced from demographics group on Athlete post
- New CSS rules: .tb-event-records-list and .tb-event-results-list

## 2026-05-10

### Fixed 2025 XC enrollment count — 26 erroneous posts removed

Enrollment import had pulled from the full Athlete CPT roster (including alumni)
rather than active 2025 registrants. 26 enrollment posts were identified and
deleted. Confirmed correct count: **118 athletes** for 2025 XC season.

Root cause: import prep did not gate on `account_status = Active`. Standing rule:
always filter by active account status when preparing enrollment import data.

---

### Fixed `results_enabled` always returning NULL on single-athlete.php

`get_field( 'results_enabled', $season_id )` returned NULL because the field
is a sub-field inside the `customize_data` ACF Group. ACF stores group
sub-fields with a prefixed meta key (`customize_data_results_enabled`), not
the bare sub-field name. Direct `get_field()` by sub-field name alone fails.

**Fix:** Read the full group first, then access sub-fields from the array:
```php
$customize = get_field( 'customize_data', $season_id ) ?: [];
$results_enabled = $customize['results_enabled'] ?? false;
```
Applies to all four `customize_data` sub-fields: `results_enabled`,
`results_unavailable_message`, `link_milesplit`, `link_athletic_net`.

**Standing rule:** ACF Group sub-fields must be read via the parent group.
Direct `get_field( 'sub_field_name', $post_id )` will not resolve them.

---

### Fixed result_display and result_time_seconds not rendering on single-athlete.php

Shadow key migration SQL had never been run on production. After pulling the
prod DB to local, ran:

```sql
UPDATE wp_qscbs1aqvn_postmeta SET meta_value = 'field_tb_result_display'
WHERE meta_key = '_result_display' AND meta_value = 'field_tb_result';

UPDATE wp_qscbs1aqvn_postmeta SET meta_value = 'field_tb_athletic_event'
WHERE meta_key = '_athletic_event' AND meta_value = 'field_tb_event';
```

**This migration must also be run on production before deploying.**

---

### Refactored results output on single-athlete.php

Replaced nested Meet → Results structure (repeated header per meet) with a
single flat list per season. Columns: Meet | Date | Event | Result | Place.
Each row links to the meet page. Data attributes added to `li.tb-list-row`:
`data-meet-id`, `data-meet-date`, `data-event`, `data-result-seconds`,
`data-place`. CSS column rule: `.tb-results-list { --tb-cols: 2fr 1fr 1fr 1fr 1fr; }`

## 2026-05-09

### Fixed ACF field key collisions in group_tb_athletic_result

**Root cause:** `group_tb_athletic_result` and `group_tb_athletic_record` shared
two field keys, causing ACF to resolve the wrong definition at render time:

- `field_tb_event` — used in both groups for the Athletic Event link. On
  `athletic_result` it backed `athletic_event`; on `athletic_record` it backed
  `event`. ACF cached one definition, breaking field display on the other.
- `field_tb_result` — different types in each group: `text` on `athletic_result`
  (backing `result_display`) vs. `post_object → athletic_result` on
  `athletic_record`. Caused `result_display` to render as a post_object selector.

**Fix — `acf-json/group_tb_athletic_result.json`:**
- `field_tb_event` → `field_tb_athletic_event` (field name `athletic_event` unchanged)
- `field_tb_result` → `field_tb_result_display` (field name `result_display` unchanged)

**Database migration (required on each environment after re-import):**
```sql
UPDATE wp_postmeta SET meta_value = 'field_tb_athletic_event'
  WHERE meta_key = '_athletic_event' AND meta_value = 'field_tb_event';
UPDATE wp_postmeta SET meta_value = 'field_tb_result_display'
  WHERE meta_key = '_result_display' AND meta_value = 'field_tb_result';
```
952 rows affected on local. Environments with no prior import do not need
this migration — the correct key is written at import time once the JSON fix
is in place.

---

### Imported 2025 XC Athletic Results (951 posts)

Imported via WP Ultimate CSV Importer from `TB Stats | 2025.xlsx`:
- 14 meets across the full 2025 XC season
- All meets renamed with year suffix for title uniqueness (e.g. "Homecoming 2025")
- Eye Opener 2025 added manually to tribe_events before import (was missing)
- All three post_object fields (athlete, meet, athletic_event) resolve correctly
- `result_display` populating as plain text after field key collision fix
- `result_time_seconds` backfilled via Tools → Sync Result Times after import

**WPUCI field mapping behavior:**
- `athlete` — matched by `post_name`
- `meet` — matched by `post_title`
- `athletic_event` — matched by post ID (249 for "5K")
- Shadow keys absent on freshly-imported posts; sync tool reads `result_display`
  via `get_post_meta()` directly to bypass ACF resolution

---

### Added `inc/results-helpers.php`

New module for athletic result utilities. Required in `functions.php`.

- `tb_parse_result_time_seconds()` — parses MM:SS.ss or H:MM:SS.ss → float seconds
- `tb_run_result_times_sync()` — finds all `athletic_result` posts with empty
  `result_time_seconds`, calculates from `result_display`, writes via `update_field()`.
  Returns `['updated' => int, 'skipped' => int]`.
- `acf/save_post` hook (priority 20) — derives `result_time_seconds` on WP admin
  saves. Does not fire during WPUCI imports.
- `admin_init` handler — catches sync button POST from any page, runs sync,
  redirects to referer with result params.
- Tools → Sync Result Times — standalone admin page. Use after every WPUCI import.

---

### Updated `inc/admin-widgets.php` — added Results widget + meet query fix

Added `📊 Results` dashboard widget:
- `tb_dashboard_get_results_data()` — queries meets for active season (filtered
  by `tribe_events_cat: athletic-meet`), counts results per meet, counts posts
  needing sync. Statically cached.
- `tb_widget_results_cb()` — per-meet results table, sync notice on redirect,
  sync button when unsynced results exist.

---

### Fixed meet queries — tribe_events_cat filter

`single-athletic_season.php` and `inc/admin-widgets.php` were returning all
`tribe_events` linked to a season, including non-meet types (practices,
information meetings). Both now filter by `tribe_events_cat: athletic-meet`.

**Standing rule:** All `tribe_events` queries scoped to a season must include
a `tax_query` on `tribe_events_cat: athletic-meet` unless explicitly querying
all event types.

---

## 2026-05-06

### Fixed ACF field key collision — Enrollment `new_returning_athlete`

**Root cause:** `group_tb_enrollment` and `group_tb_application` both defined
a field with key `field_tb_new_returning`. The application field has choices
`New` / `Returning`; the enrollment field has choices `New Athlete` /
`Returning Athlete`. ACF resolved the key to the application definition at
write time, causing the enrollment value to fail validation silently and
write as empty on every submission.

**Fix:**
- `group_tb_enrollment.json` — field key changed from `field_tb_new_returning`
  to `field_tb_new_returning_athlete`; field name changed from `new_returning`
  to `new_returning_athlete`
- `inc/gravity-helpers.php` — all three `tb_create_enrollment_post()` call
  sites updated from `'new_returning'` to `'new_returning_athlete'`; function
  `$defaults` and `update_field()` call updated to match
- `docs/FORM-FIELD-MAP.md` updated to v2.4
- Existing enrollment posts remediated via WP Admin bulk edit

### Fixed sport taxonomy not written on Enrollment creation

`tb_create_enrollment_post()` never called `wp_set_object_terms()`. Sport
terms are now inherited from the linked season post at enrollment creation time.

```php

### Refactored `single-athlete.php` — base classes confirmed, records query fix

- Base template classes verified: `.tb-single`, `.tb-single-header`,
  `.tb-single-headline`, `.tb-single-meta`, `.tb-single-header-secondary-section`,
  `.tb-single-image`, `.tb-single-section` — all present and consistent
- Photo secondary section made conditional (omitted when no photo)
- Records query: event name now uses `get_field( 'event_name' )` with
  `get_the_title()` fallback — was using `get_the_title()` only
- Event name column in records list is now a link to the Athletic Event single
- Meet name in records list is now a link to the meet
- `uksort` on meets within each season by `meet_date` descending — cleaned up

### Refactored `single-coach.php` — applied base template classes

- Outer wrapper: added `.tb-single` alongside `.tb-coach`
- Header restructured to match base single pattern:
  - `.tb-single-header` on the section
  - `.tb-single-headline` wraps name and title
  - Photo moved into `.tb-single-header-secondary-section` > `.tb-single-image`
  - Secondary section omitted entirely when no photo exists
- Bio section: added `.tb-single-section` alongside `.tb-coach-bio`
- No schema changes, no new queries

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