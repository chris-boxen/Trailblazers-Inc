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

## Active — Template Build

Templates to build or refactor. Work through these in any order.

- [x] `single-athlete.php` — apply base classes; update queries for TEC meets
- [x] `single-coach.php` — apply base classes
- [ ] `single-athletic_event.php` — apply base classes; update queries
- [ ] `taxonomy-sport.php` — apply base classes; update queries for TEC
- [ ] `archive-athlete.php` — refactor tables to ul/li list system
- [ ] `archive-athletic_meet.php` — refactor tables to ul/li list system (or confirm archived)
- [ ] `archive-athletic_record.php` — refactor tables to ul/li list system
- [ ] `tribe/events/single-event.php` — TEC theme override (meet single)

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

## Deferred — Schema / Data

- [ ] Athletic Results and Athletic Records data import
- [ ] Confirm `publicly_queryable` is false on Family, Application, Enrollment,
  and Athletic Physical CPTs
- [ ] Decide whether TEC `tribe_events_cat` needs sport sub-categories
- [ ] Additional Singlets (Returning) — future: derive from nested form entries
- [ ] Revisit payment abstraction if workflow becomes more complex
- [ ] Revisit theme folder naming (space in folder name)
- [ ] Add `template-parts/` structure when templates grow complex enough
- [ ] Consider proper single-site email customization