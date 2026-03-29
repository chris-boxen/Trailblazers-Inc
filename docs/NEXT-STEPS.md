# NEXT STEPS

## Now — Data Population (new thread)
Populate the site with live data before building forms. Forms depend on real CPT
posts existing — especially the Returning Family form (GP Populate Anything queries
real Athlete posts) and handbook links (real Season posts).

**Import tool:** WP Ultimate CSV Importer (Smackcoders)

**Import order:**
1. Sport taxonomy terms
2. Athletic Seasons
3. Athletic Events
4. Athletic Meets
5. Families (+ parents_guardians repeater rows)
6. Athletes (including `participation_type` and `account_status`)
7. Enrollments (linking Athlete + Family + Season)
8. Athletic Results
9. Athletic Records

## Next — GravityForms Build (after data is populated)
Field map is complete in `docs/FORM-FIELD-MAP.md`. Do not start until live data exists.

**Build order:**
1. Build `Nested: 2026 Register Athlete` form first (nested form must exist before
   parent forms can reference it)
2. Build `2026 Registration — New Family` form
3. Build `2026 Registration — Returning Family` form (requires GP Populate Anything
   configured against real Athlete + Family posts)
4. Create the Registration entry WP page with New / Returning buttons

**After forms are built:**
- Record all GF field IDs from each form
- Write hooks in `inc/gravity-helpers.php` against real field IDs
- Test New Family submission end-to-end
- Test Returning Family submission end-to-end

## Parked — JS Filtering (ready to build, not urgent)
Data attributes are wired on all archive templates. Can be built independently of
the forms/hooks work.

- Add filter controls + JS to `archive-athlete.php`
  (include participation_type filter using `data-participation-type`)
- Add filter controls + JS to `archive-athletic_meet.php`
- Add filter controls + JS to `archive-athletic_record.php` (including current/all toggle)
- Add filter controls + JS to `taxonomy-sport.php` athlete table
- Populate `inc/enqueue.php` with the filtering script

## Later
- Add coach → season backreference on `single-coach.php`
- Revisit payment abstraction if workflow becomes more complex
- Add stronger review workflows for physicals and approvals
- Revisit theme folder naming (space in folder name)
- Add `template-parts/` structure when templates grow complex enough to warrant it
- Populate remaining stub `inc/` files as functionality is needed
- Confirm `publicly_queryable` is false on Family, Application, Enrollment, and
  Athletic Physical CPTs (see OPEN-QUESTIONS.md Q3 action item)

## Blocked / Waiting
- GravityForms build blocked on data population
- Hook code blocked on GravityForms build (need real field IDs)
- Final decision on how much event metadata is stored directly on results vs derived
  from Athletic Event (Q7)