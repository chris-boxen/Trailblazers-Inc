# SCHEMA

## Schema principles
1. `acf-json` is the schema source of truth.
2. Use clear field ownership.
3. Distinguish display fields from normalized/queryable fields.
4. Do not duplicate relationships without a strong reason.
5. Be cautious about renaming or deleting fields once content exists.

## ACF defaults
- Post Object return format: ID
- User return format: ID
- Image return format: ID
- Link return format: Array
- Repeater layout: Block
- One field group per CPT by default

## Field naming conventions
- Use snake_case slugs
- Use `_status` for process state fields
- Use `_date` for dates
- Use `_requested` for request flags
- Use `_notes` for admin/freeform notes

## Taxonomy registration
Sport taxonomy is registered on the following CPTs:
- Athletic Season
- Athletic Meet
- Athletic Event
- Athletic Result
- Athletic Record
- Enrollment
- Athlete
- Coach

Coach is registered with sport to allow direct sport-based querying (e.g. on
`taxonomy-sport.php`). A coach's role and bio for a specific season are stored in
the `coach_roster` repeater on Athletic Season post — not on the Coach post itself.

## Source-of-truth rules
- Family link is owned by Athlete
- Season participation is owned by Enrollment
- Submission status is owned by Application
- Physical validity/history is owned by Athletic Physical
- Performance data is owned by Athletic Result
- Achievement layer is owned by Athletic Record
- Coach sport association is owned by the Coach post (via sport taxonomy)
- Coach role per season is owned by Athletic Season (via `coach_roster` repeater)
- Current participation type (for filtering) is a denormalized snapshot on Athlete —
  source of truth for any given season is Enrollment (see below)
- User → Family relationship is bidirectional (see User field group section below)

## Athlete fields of note
- `first_name` — text
- `last_name` — text
- `preferred_name` — text
- `gender` — select (M / F)
- `dob` — date picker
- `graduation_year` — number
- `account_status` — select (Active / Inactive / Alumni / Archived)
- `athletic_net_id` — text (external ID reference)
- `participation_type` — select (Athlete / Sibling Runner) — denormalized, see below
- `family` — post object → Family (source of truth for family link)

These fields support filtering and data attributes on `archive-athlete.php` and
`taxonomy-sport.php`.

## Athlete.account_status vs Athlete.participation_type

These two fields answer different questions and must not be conflated.

**`account_status`** — What is this person's current relationship with the organization?
- Choices: Active / Inactive / Alumni / Archived
- Represents a lifecycle state — is this person still engaged, graduated out, or
  retained only for historical data?
- Set manually by an admin. Not touched by the registration hook.
- Alumni belongs here, not in participation_type. An alumnus is not enrolling in a
  season; they have no meaningful participation_type for current purposes.

**`participation_type`** on Athlete — What kind of participant were they most recently?
- Choices: Athlete / Sibling Runner
- A denormalized convenience snapshot for archive filtering (see below).
- Set by the enrollment creation hook. Updated each time a new enrollment is created.
- Do not use this field to infer lifecycle status.

## Special schema watchout: participation_type (Athlete vs Enrollment)

`participation_type` exists on both the Enrollment CPT and the Athlete CPT.
These serve different purposes and must not be confused.

### Enrollment.participation_type
- **Type:** Select (Athlete | Sibling Runner)
- **Role:** Source of truth for what a person was in a specific season
- **Set by:** Enrollment creation hook at form submission time
- **Never overwrite manually** without also reviewing the Athlete convenience field

### Athlete.participation_type
- **Type:** Select (Athlete | Sibling Runner)
- **Role:** Denormalized convenience field for archive filtering and data-attribute output
- **Set by:** Enrollment creation hook — mirrors the most recent Enrollment value
- **Not the source of truth** — use Enrollment if season-specific accuracy is needed

### Why this denormalization is intentional
The athlete archive and sport taxonomy templates filter by participation type using
`data-participation-type` attributes. Querying Enrollment for every athlete in an
archive loop is expensive and architecturally awkward. The Athlete field provides a
direct, fast, template-friendly value.

### Transition behavior
When a Sibling Runner enrolls as a full Athlete in a future season, the enrollment
hook updates `Athlete.participation_type` to `Athlete`. Historical Enrollment records
remain unchanged — they still correctly reflect `Sibling Runner` for prior seasons.

### Empty field handling rule
Athlete posts created manually or via CSV import may have no `participation_type`
value set. Templates and JS filters must treat an empty value as `Athlete`. Do not
treat empty as a third state or filter it out.
```php
// Correct pattern in templates:
$participation_type = get_field( 'participation_type', $athlete_id ) ?: 'Athlete';
```
```js
// Correct pattern in JS filter:
const type = row.dataset.participationType || 'athlete';
```

## Special schema watchout: Athletic Result
Athletic Result should distinguish between:
- exact display label from the meet (`event_name`)
- canonical event structure (`event`)
- display-ready result value (`result_display`)
- normalized sortable/queryable values (`result_time_seconds`, `result_distance_meters`,
  `result_height_meters`, `result_points`)

## Special schema watchout: Coach
Coach identity and sport association live on the Coach post.
Coach role, bio override, and image override per season live in the `coach_roster`
repeater on Athletic Season.
Do not move role data onto the Coach post — it is intentionally season/sport-specific.

## Special schema watchout: Sport taxonomy
Sport is hierarchical. When querying by exact term (not including children), always use:
```php
'include_children' => false
```
in `tax_query` to avoid unintended matches on child terms.

## Special schema watchout: User → Family relationship

The User ↔ Family relationship is intentionally bidirectional and maintained on
both sides. Each direction serves a distinct purpose.

### Family.account_user (group_tb_family.json)
- **Type:** ACF User field, return format: ID
- **Direction:** Family → User
- **Role:** The authoritative link. Identifies which WP user account belongs to
  this household. Used to locate a family from the current user:
  ```php
  // Standard pattern — locate family from logged-in user
  $families = get_posts([
      'post_type'  => 'family',
      'meta_key'   => 'account_user',
      'meta_value' => get_current_user_id(),
  ]);
  ```
- **Set by:** Registration hook (new family) or manual admin assignment.

### User.family (group_tb_user.json)
- **Type:** ACF Post Object field, post type: family, return format: ID
- **Direction:** User → Family
- **Role:** Reverse reference. Provides a direct, GPPA-friendly path from the
  current user to their Family post without a meta query. Required for
  GravityForms Populate Anything chains that start at the current user and
  need to read Family post fields.
  ```php
  // Direct lookup — no meta query needed
  $family_id = get_field( 'family', 'user_' . get_current_user_id() );
  ```
- **Set by:** Same operation that sets `account_user` on the Family post.
  Both sides must be populated together.

### User.family_id (group_tb_user.json)
- **Type:** ACF Text field
- **Role:** Scalar TB identifier (e.g. `TB-66281`). Provides a simple string
  anchor for GPPA filter matching without requiring post object traversal.
  Also useful as a human-readable identifier in admin views and debug output.
- **Set by:** Same linkage pass as `User.family` and `Family.account_user`.
- **Not the primary key for lookups** — use `User.family` (post object) for
  any query that needs to read Family post fields.

### Field key naming rationale
ACF field keys must be globally unique across the entire installation. The user
field group uses the `_user_` infix in its keys (`field_tb_user_family`,
`field_tb_user_family_id`) to avoid collisions with identically-named fields on
CPTs (`field_tb_family` on Application, `field_tb_family_id` on Family/Athlete).
The field `name` values (`family`, `family_id`) are intentionally the same as their
CPT counterparts — they live in `wp_usermeta`, not `wp_postmeta`, so there is no
storage collision.

### Linkage rule
All three fields must be populated atomically:
1. `Family.account_user` = WP user ID
2. `User.family` = Family post ID
3. `User.family_id` = Family TB identifier string

This happens during the user import linkage pass and during the New Family
registration hook. Never set one without setting all three.

### admin-facing notes
- `User.family` is `allow_null: 1` — admin users and coaches do not have a
  Family post and must not be forced to select one.
- The user field group appears on all user profile screens (`user_form == all`).
  On non-parent accounts, both fields will simply be empty.

## WP Ultimate CSV Importer — ACF import constraints

### Post Object fields inside ACF Group wrappers
WP Ultimate CSV Importer does not reliably resolve ACF Post Object (and
Relationship) fields when those fields are nested inside an ACF Group field.
Resolution by post title works correctly for top-level fields only.

**Rule:** Any CPT whose post object fields will be populated via CSV import must
have those fields at the top level of the field group — not inside a Group wrapper.

**Affected field groups (confirmed top-level, import-safe):**
- `group_tb_enrollment.json` — season, family, athlete, application are top-level
- `group_tb_athletic_result.json` — no Group wrappers
- `group_tb_athletic_record.json` — no Group wrappers

**Groups used for UI only (no post object fields inside):**
- `group_tb_athletic_season.json` — `customize_data` group contains only
  True/False and Textarea fields (safe); `Dates` group contains only date
  and number fields (safe)

### Post Object field resolution
WP Ultimate CSV Importer resolves ACF Post Object fields by **post title**.
Supply the exact `post_title` value of the referenced post in the CSV column.
Resolution is case-sensitive and must match exactly.

Post IDs are not required for standard post object field imports.