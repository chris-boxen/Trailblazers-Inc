# NEXT-STEPS

## Completed — Registration Infrastructure
As of 2026-04-20. Full record in CHANGELOG.md 2026-04.

- ✅ ACF options pages: Trailblazers Settings (parent) + Registration Settings (sub-page)
- ✅ ACF field group `group_tb_registration_settings` (16 fields, synced)
- ✅ `inc/registration-helpers.php` — sync hook + shortcodes
- ✅ `functions.php` updated with new require line
- ✅ Seven permanent WP registration pages created

## Completed — GravityForms Registration Build
As of 2026-04-26. Full field spec in `docs/FORM-FIELD-MAP.md` v2.3.

- ✅ Register New Athlete nested form (permanent/reusable)
- ✅ Register Returning Athlete nested form (permanent/reusable)
- ✅ 2026 Registration — New Family (5-page, requireLogin)
- ✅ 2026 Registration — Returning Family (5-page, requireLogin, full GPPA config)
- ✅ `inc/registration-helpers.php` updated with routing + guards
- ✅ `docs/FORM-FIELD-MAP.md` v2.3 committed

## Completed — Submission Hooks + Code Audit
As of 2026-04-30. See CHANGELOG.md 2026-04-30.

- ✅ `inc/gravity-helpers.php` — New Family and Returning Family submission hooks
- ✅ Six audit fixes applied

## Completed — Flywheel + Stripe Setup
As of 2026-05-01.

- ✅ Forms deployed to Flywheel
- ✅ Stripe connected in live mode
- ✅ End-to-end tests passed for all four submission paths

## Completed — Production Cutover
As of 2026-05-02.

- ✅ Domain swap to trailblazers.team
- ✅ Stripe webhook updated and verified
- ✅ RF CC `payment_status` bug fixed

## Completed — Template Foundation
As of 2026-05-03.

- ✅ `assets/css/templates.css` — base styles for all CPT templates
  (`.tb-list`, `.tb-single`, `.tb-archive`, `.tb-status`, per-list `--tb-cols`)
- ✅ `archive-athletic_season.php` — new, ul/li list structure
- ✅ `single-athletic_season.php` — refactored to ul/li lists + base classes;
  header restructured with `.tb-single-header-secondary-section` grouping
  image and CTA as a right column
  
## Completed — Post-Launch Bug Fixes + Admin Tools
As of 2026-05-06.

- ✅ Enrollment `new_returning_athlete` field key collision diagnosed and fixed
- ✅ Sport taxonomy write added to `tb_create_enrollment_post()`
- ✅ Admin list columns added: Enrollment (New/Returning, Participation Type),
  Application (Payment Method, Payment Status)
- ✅ Admin list filters added: Enrollment and Application — both sortable
  and filterable; `inc/query-mods.php`
- ✅ Existing enrollment data remediated via bulk edit

---

## Completed — Athletic Results Data Import
As of 2026-05-09.

- ✅ 2025 XC Athletic Results imported — 951 posts, 14 meets, via WPUCI
- ✅ ACF field key collision fixed in `group_tb_athletic_result.json`;
  postmeta shadow keys migrated on local; production migration deferred to
  import time (no prior data)
- ✅ `inc/results-helpers.php` — parse helper, sync executor, ACF save hook,
  admin_init handler, Tools → Sync Result Times page
- ✅ 📊 Results dashboard widget — per-meet counts, sync notice, sync button
- ✅ `tribe_events_cat: athletic-meet` filter applied to all meet queries
  (`single-athletic_season.php`, admin widget)
  
---

## Completed — Athletic Records Data Import
As of 2026-05-16

- ✅ 2024 XC athletic records imported manually
- ✅ single-athlete.php — Personal Records section: deduped to one row
per event + record_type (most recent only); result_id and
meet_date_raw added to records array; dedup logic added post-query
- ✅ single-athlete.php — Results section: PR/SR achievement badges
rendered inline with result time; $result_record_map built from full
(pre-dedup) records array so all historical records earn badges
- ✅ single-athletic_season.php — Records column in athlete and sibling
runner rosters: bulk records query added after enrollment build;
season-scoped SR preferred, all-time PR as fallback; third tb-col
span added to roster rows
- ✅ inc/results-helpers.php — tb_auto_generate_records() added
(acf/save_post priority 25): auto-creates PR/SR record posts on WP
admin result save; handles all measurement types (Time/Distance/Height/
Points) with correct comparison direction; skips relays; idempotent
- ✅ inc/results-helpers.php — tb_create_record_post() helper added
- ✅ inc/results-helpers.php — tb_run_generate_records() and
Tools → Generate Records admin page added: bulk PR/SR generation for
a selected season; use after every WPUCI results import; idempotent;
walks results chronologically to preserve historical record sequence
- ✅ assets/css/templates.css — .tb-record-badge rules added

---

## Active — Template Build

Templates to build or refactor. Work through these in any order.

- [x] `single-athlete.php` — base classes, TEC meet queries, results refactor,
customize_data group fix, data attributes
- [x] `single-coach.php` — apply base classes
- [x] `single-athletic_event.php` — apply base classes; update queries
- [ ] `taxonomy-sport.php` — apply base classes; update queries for TEC
- [ ] `archive-athlete.php` — refactor tables to ul/li list system
- [ ] `archive-athletic_meet.php` — refactor tables to ul/li list system (or confirm archived)
- [ ] `archive-athletic_record.php` — refactor tables to ul/li list system
- [x] `tribe/events/single-event.php` — TEC meet results view, heat grouping 
+ filter, grade column via bulk enrollment query

---

## Backlog — Known Bugs (post-launch)
- [ ] **RF CC spinner — no visible progress** — ~27 seconds with no
  intermediate state shown. Add "Processing your payment..." status message.
- [ ] **GF Stripe + GPPA Payment Element conflict** — RF field 57 workaround
  in place. Monitor Gravity Wiz ticket
- [ ] **Duplicate Stripe webhooks** — clean up stale sandbox webhooks.

---

## Backlog — UX Issues (non-blocking polish)

- [ ] WP subscriber dashboard — needs content and layout updates
- [ ] Handbook note inside nested athlete form is redundant with Page 3
- [ ] Optional donations field — choice labels missing amounts
- [ ] `.tb-single-header-secondary-section` — handbook button is full-width
  on right column; confirm or constrain width

---

## Pending — Production Deployment

- [ ] Run shadow key migration SQL on production database:
      `_result_display`: `field_tb_result` → `field_tb_result_display`
      `_athletic_event`: `field_tb_event` → `field_tb_athletic_event`
- [ ] Verify results render on production single-athlete pages after migration
- [ ] Run Tools → Sync Result Times on production after migration
- [ ] Re-import 2025 XC results with heat column populated

---

## Deferred — Schema / Data

- [ ] Confirm `publicly_queryable` is false on Family, Application, Enrollment,
  and Athletic Physical CPTs
- [ ] Decide whether TEC `tribe_events_cat` needs sport sub-categories
- [ ] Additional Singlets (Returning) — future: derive from nested form entries
- [ ] Revisit payment abstraction if workflow becomes more complex
- [ ] Revisit theme folder naming (space in folder name)
- [ ] Add `template-parts/` structure when templates grow complex enough
- [ ] Consider proper single-site email customization
- [ ] heat field added to group_tb_athletic_result — existing 951 results have
no heat value; re-import from source data when ready