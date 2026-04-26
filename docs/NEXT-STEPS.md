# NEXT-STEPS

## Completed — Registration Infrastructure
As of 2026-04-20. Full record in CHANGELOG.md 2026-04.

- ✅ ACF options pages: Trailblazers Settings (parent) + Registration Settings (sub-page)
- ✅ ACF field group `group_tb_registration_settings` (16 fields, synced)
- ✅ `inc/registration-helpers.php` — sync hook + 3 shortcodes
- ✅ `functions.php` updated with new require line
- ✅ Seven permanent WP registration pages created:
  - `/registration/` — `[tb_reg_hub]`
  - `/registration/returning-families/` — `[tb_reg_form type="returning_family"]`
  - `/registration/new-families/` — `[tb_reg_form type="new_family"]`
- `/registration/confirmation/` — parent container (permanent)
  - `/registration/confirmation/new-family/` — `[tb_reg_confirmation type="new_family"]`
  - `/registration/confirmation/returning-family/` — `[tb_reg_confirmation type="returning_family"]`
  - `/registration/submit-physicals/` — `[tb_reg_form type="physicals"]`

---

## Now — Schema / Template Updates
These were blocked on ACF schema changes. Confirm all schema changes are committed,
then work through this list.

### Templates to update
- `single-athlete.php`
  - Add per-season `results_enabled` flag check with `results_unavailable_message` fallback
  - Add Milesplit / AthleticNet external ID links when season flags + IDs are present
  - No post_type query changes needed (queries Enrollment and Result, not Meet directly)

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

## Now — Data Population
After schema changes and template updates are committed, populate with live data.

**Import tool:** WP Ultimate CSV Importer (Smackcoders)

**Import order:**
1. Sport taxonomy terms — `01-sport-taxonomy.csv`
2. Athletic Seasons — `02-athletic-seasons.csv`
3. Athletic Events — `03-athletic-events.csv`
4. TEC Venues — `04a-tec-venues.csv`
5. TEC Events (meets) — `04b-tec-events.csv`
6. Families (+ parents_guardians repeater rows) — `05-families.csv`
7. Athletes (including `participation_type` and `account_status`) — `06-athletes.csv`
8. Enrollments (linking Athlete + Family + Season) — `07-enrollments.csv`
9. Athletic Results (meet column references tribe_events titles) — `08-athletic-results.csv`
10. Athletic Records — `09-athletic-records.csv`

---

## Next — GravityForms Build (after data is populated)
Field map is complete in `docs/FORM-FIELD-MAP.md`. Do not start until live data exists.

**Resolve first:**
- Open question Q12 (confirmation page structure) before configuring GF confirmations

**Build order:**
1. Build `Nested: 2026 Register Athlete` form first
2. Build `2026 Registration — New Family` form
3. Build `2026 Registration — Returning Family` form

**After forms are built:**
- Record all GF form IDs
- Enter form IDs in **TB Settings → Registration** options page
- Write hooks in `inc/gravity-helpers.php` against real field IDs
- Test New Family submission end-to-end
- Test Returning Family submission end-to-end

---

## Parked — JS Filtering (ready to build, not urgent)
Data attributes are wired on all archive templates. Can be built independently of
the forms/hooks work.

- Add filter controls + JS to `archive-athlete.php`
- Add filter controls + JS to `archive-athletic_record.php`
- Add filter controls + JS to `taxonomy-sport.php` athlete table
- Populate `inc/enqueue.php` with the filtering script

---

## Later
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
- CSS styling for `.tb-reg-hub`, `.tb-reg-btn`, `.tb-reg-btn--disabled`,
  `.tb-reg-hub__date` — scoped to front-end build

---

## Blocked / Waiting
- All template updates blocked on ACF schema changes being committed first
- Data population blocked on template updates
- GravityForms build blocked on data population
- Hook code blocked on GravityForms build (need real field IDs)
- Final decision on event metadata storage on results vs Athletic Event (Q7)
