# NEXT STEPS

## Now — Schema Changes in ACF Admin
Complete these in order before any data is imported. Each step produces ACF JSON
that must be committed to Git.

### Step 1: Remove retired athletic_meet CPT
- In ACF → Post Types: delete `Athletic Meet`
- ACF will remove `post_type_tb_athletic_meet.json` from `acf-json/`
- Move `single-athletic_meet.php` and `archive-athletic_meet.php` to
  `_archived-templates/` folder in theme root

### Step 2: Remove athletic_meet field group
- In ACF → Field Groups: delete `Athletic Meet Fields`
- ACF will remove `group_tb_athletic_meet.json` from `acf-json/`
- Move original archived copy at
  `acf-json/_archived/2026-03-21-initial-import/field-group-athletic_meet.json`
  to document the history (already archived — no action needed)

### Step 3: Configure TEC slug
- In The Events Calendar → Settings: set event slug to `event`
- Verify archive is reachable at `/event/`

### Step 4: Create `group_tb_tec_event` field group
- In ACF → Field Groups: create new group, assign to Post Type: `tribe_events`
- Add fields:
  - `season` — Post Object, post type: `athletic_season`, return format: ID
  - `results_status` — Select, choices: Future / Pending / Available, allow null,
    return format: value
- Save → confirm `group_tb_tec_event.json` appears in `acf-json/`

### Step 5: Update `group_tb_athletic_result` — meet field
- In ACF → Field Groups → Athletic Result Fields
- Edit the `meet` Post Object field: change post type from `athletic_meet`
  to `tribe_events`
- Save → confirm `group_tb_athletic_result.json` is updated in `acf-json/`

### Step 6: Update `group_tb_athletic_season` — season flags
- In ACF → Field Groups → Athletic Season Fields
- Add the following True/False fields (default: off):
  - `calendar_show_meets`
  - `calendar_show_practices`
  - `results_enabled`
  - `link_milesplit`
  - `link_athletic_net`
- Add one Textarea field: `results_unavailable_message` (not required)
- Save → confirm `group_tb_athletic_season.json` is updated in `acf-json/`

### Step 7: Commit all ACF JSON changes
```bash
git add acf-json/
git add _archived-templates/
git commit -m "schema: retire athletic_meet CPT, integrate TEC, add season flags"
git push origin main
```

---

## Now — Template Updates (after ACF changes are committed)
These templates need updates before results data can be displayed correctly.

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

**Build order:**
1. Build `Nested: 2026 Register Athlete` form first
2. Build `2026 Registration — New Family` form
3. Build `2026 Registration — Returning Family` form
4. Create the Registration entry WP page with New / Returning buttons

**After forms are built:**
- Record all GF field IDs from each form
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

---

## Blocked / Waiting
- All template updates blocked on ACF schema changes being committed first
- Data population blocked on template updates
- GravityForms build blocked on data population
- Hook code blocked on GravityForms build (need real field IDs)
- Final decision on event metadata storage on results vs Athletic Event (Q7)