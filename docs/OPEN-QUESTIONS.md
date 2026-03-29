# OPEN QUESTIONS

## 1. Athletic Result normalization
### Status: RESOLVED
### Decision
Use `result_display` for human-readable output plus type-specific normalized numeric
fields: `result_time_seconds`, `result_distance_meters`, `result_height_meters`, and
`result_points`. This is reflected in the current `group_tb_athletic_result.json`.

---

## 2. Theme folder naming
### Question
Should the theme folder be renamed from `Trailblazers 2026` to a slug-style folder
name like `trailblazers-2026`?

### Why it matters
Cleaner terminal/Git workflows and fewer path quirks.

### Current lean
Probably yes, but not urgent.

---

## 3. Public exposure of operational CPTs
### Status: RESOLVED
### Decision
Family, Application, Enrollment, and Athletic Physical are **admin-only data objects**.
They have no public-facing templates and will not be given meaningful ones.

- `family` — no public template. Managed via GravityForms registration flow and WP admin.
- `application` — no public template. Created programmatically via form submission hook.
- `enrollment` — no public template. Created programmatically via form submission hook.
- `athletic_physical` — no public template. Managed via WP admin or a future
  admin-facing form.

If a family-facing dashboard is built in the future, it will be a custom page template
that queries these CPTs — not their native single templates.

**Action item:** Confirm `publicly_queryable` is set to false on all four CPTs in their
ACF Post Type settings. Flag if any are currently set to true.

---

## 4. Payment abstraction
### Question
At what point should payment become its own object rather than status fields on
Application/Enrollment?

### Why it matters
May become necessary if partial payments, refunds, or multiple payment methods grow
more complex.

### Current lean
Keep payment as status/summary fields for now. Revisit if the workflow grows.

---

## 5. Baseline templates in a Divi child theme
### Status: RESOLVED
### Decision
Baseline fallback templates (`header.php`, `footer.php`, `page.php`, `single.php`,
`archive.php`) are not needed in the child theme. Divi provides these in the parent.
The child theme only needs `index.php` to satisfy WordPress theme validity, which
already exists.

---

## 6. PHP template vs Divi Theme Builder split
### Status: RESOLVED
### Decision
Templates that require relational queries or custom loops must be built as PHP
templates. Templates that are primarily layout and dynamic field display can use
Divi Theme Builder.

- `single-athlete.php` → PHP
- `single-athletic_meet.php` → PHP
- `single-athletic_season.php` → PHP
- `single-coach.php` → PHP
- `single-athletic_event.php` → PHP
- `taxonomy-sport.php` → PHP
- `archive-athlete.php` → PHP
- `archive-athletic_meet.php` → PHP
- `archive-athletic_record.php` → PHP

All CPT templates are PHP files. Divi Theme Builder is not used for any CPT templates.

---

## 7. Event metadata on results vs derived from Athletic Event
### Question
How much event metadata should be stored directly on Athletic Result vs derived at
render time from the linked Athletic Event?

### Why it matters
Affects query complexity, template logic, and how much denormalization is acceptable
on the result record.

### Current lean
Unresolved. Deferred until query patterns from the GravityForms build make the
tradeoff clearer.

---

## 8. GravityForms registration form architecture
### Status: RESOLVED
### Decision

**Form set:**
- `Start` — new vs. returning gate; redirect only; no submission hook
- `New Family` — creates Family post, Application post, Athlete posts, Enrollment posts
- `Returning Family` — updates Family post, creates Application post, new Athlete posts
  where needed, Enrollment posts for all registered athletes
- `Nested: Register Athlete` — single combined nested form for both Athlete and Sibling
  Runner entries; participation type radio drives conditional eligibility block display

**Eliminated from the 2025 form set:**
- `Register New Parent` — additional contacts are now collected inline via a conditional
  section in the parent form and written to the `parents_guardians` repeater on Family
- `Register Returning Athlete` — athlete lookup is handled via GP Populate Anything
  querying Athlete CPT posts by family; no separate form needed
- `Register Sibling Runner` — Sibling Runner is now a participation type within the
  combined athlete nested form, not a separate form

**Login requirement:**
- Both New Family and Returning Family forms require the user to be logged in
- New Family: WP account must exist before submission; Family post is created at
  submission time and immediately linked to `account_user`
- Returning Family: Family post is located at hook time via
  `account_user = get_current_user_id()` — no user-visible Family ID field

**Season targeting:**
- A hidden field on both parent forms is populated at form load via `gform_field_value`
  reading an `active_season_id` site option set by the admin
- Admin sets the active season in one place; both forms pick it up automatically

**Athlete nested form — participation type handling:**
- `participation_type` radio (Athlete / Sibling Runner) appears at the top of the form
- Eligibility checkbox block is conditional on participation type selection
- Athlete block: Residency, Homeschooled, Academic Eligibility, Running Commitment,
  Policy Compliance
- Sibling Runner block: Parent Supervision Acknowledgment, Policy Compliance (lighter)
- Grade field is visible to both; appropriate range enforced via eligibility checkboxes

**Hook strategy (all logic in `inc/gravity-helpers.php`):**
- One flat function per CPT operation — no cascading smart logic
- Execution sequence: Family → Application → Athletes → Enrollments
- Each function receives IDs from previous steps directly; no re-querying mid-chain
- Each function writes its own `error_log()` trail
- A failure at any step does not roll back prior steps — each is independently
  recoverable by an admin

---

## 9. Athlete.participation_type denormalization
### Status: RESOLVED
### Decision
A `participation_type` field (Select: Athlete / Sibling Runner) is added to the
Athlete CPT as a **denormalized convenience field** for archive filtering.

The source of truth for any given season remains `Enrollment.participation_type`.
The Athlete field reflects the most recent enrollment and is updated by the enrollment
creation hook each time a new enrollment is written.

Empty values — for posts created manually or via CSV import — are treated as `Athlete`
in both PHP templates and JS filter logic. See SCHEMA.md for implementation patterns.

**ACF JSON change required:** add `participation_type` field to `group_tb_athlete.json`.
**Risk level:** Low — additive only. No existing field renamed or removed.
**Workflow:** Make the change via ACF admin UI, let ACF write the JSON, then commit.