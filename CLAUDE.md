# Trailblazers theme — Claude Code instructions

Custom WordPress child theme (`trailblazers-2026`, parent: Divi 5) for the
Trailblazers youth running program at https://trailblazers.team. It tracks
families, athletes, registration, results, and records across seasons. The site
is live in production and used by non-technical admins.

## Start of every session

Read these before proposing changes. They are the project's memory and are
authoritative over anything you assume:

- `docs/STATE.md` — what exists and works today
- `docs/NEXT-STEPS.md` — active work and backlog
- `docs/OPEN-QUESTIONS.md` — unresolved decisions

Read on demand when the task touches them:

- `docs/ARCHITECTURE.md`, `docs/SCHEMA.md` — data model and ACF fields
- `docs/FORM-FIELD-MAP.md` — Gravity Forms field IDs and mappings
- `docs/TEMPLATES.md` — template inventory and public anchors
- `docs/WORKFLOW.md` — ACF JSON and git workflow
- `docs/CHANGELOG.md` — history and rationale

## How Chris works (follow this)

- Diagnose before fixing. State the cause and the evidence before proposing a
  fix. Don't present a guess as a diagnosis.
- One file at a time. Explain the approach, show a targeted diff, and wait for
  confirmation before writing the full file. Never batch edits across several
  files without approval.
- Search the repo before writing code. Don't claim a file or function doesn't
  exist without grepping for it.
- Distinguish "do now", "later", and "optional".
- For every proposed change, state the downstream impact on ACF JSON,
  templates, queries, Gravity Forms field IDs, and existing data.
- Claude handles commits and pushes to `origin/main`. Commit when a piece of
  work is done and approved; keep each commit to one logical change and leave
  unrelated uncommitted files out. Messages: a short title plus a description
  that explains why, not just what. Report the commit hash after pushing.
- Close meaningful sessions with `/session-close` to update the docs.

## Stack

- WordPress, Divi 5 parent, this child theme.
- ACF Pro defines CPTs, taxonomies, field groups, and options pages.
- Gravity Forms + Gravity Perks (GP Nested Forms, GP Populate Anything,
  GP Unique ID, GW Read Only), Stripe add-on.
- The Events Calendar (TEC) — `tribe_events` is the meet post type.
- WP Ultimate CSV Importer (WPUCI) for bulk data.
- Isotope.js for archive filtering/sorting.
- Local by Flywheel for dev; Flywheel for staging and production.
- Git tracks this theme folder only. DB changes never travel with git.

## Data model rules

- CPTs: `family`, `athlete`, `coach`, `application`, `enrollment`,
  `athletic_season`, `athletic_event`, `athletic_result`, `athletic_record`,
  `athletic_physical`, plus TEC `tribe_events`. `athletic_meet` is retired.
- Enrollment is the athlete-season operational hub. Keep identity (Athlete,
  Family), submission (Application), season participation (Enrollment),
  compliance (Physical), and performance (Result, Record) distinct.
- One source of truth. No mirrored fields unless code derives one from the
  other (e.g. `result_display` is canonical; `result_time_seconds` is derived).
- Records are append-only historical markers ("PR at the time"), not
  current-best flags.

## ACF rules

- `acf-json/` is the schema source of truth. Change schema in the ACF admin UI
  so Local JSON regenerates; do not hand-edit JSON unless Chris asks. Edits
  there require approval.
- Never rename or delete a field without spelling out the migration: existing
  postmeta, `_fieldname` key references, templates, queries, and WPUCI headers.
- Field keys must be globally unique. Collisions fail silently.
- Group sub-fields: read with `get_field('group_name', $id)['sub']` and write by
  passing the whole group array to the group's field key. Sub-field writes by
  key silently fail.
- Relationship name differs by CPT: `event` on `athletic_record`,
  `athletic_event` on `athletic_result`.
- Options pages are not created by Local JSON sync; they need the ACF UI.

## Gravity Forms rules

- Handlers in `inc/gravity-helpers.php` read hard-coded GF field IDs. Never
  suggest rebuilding a form from scratch; duplicate it so IDs survive.
- Form IDs differ between local and production. Code resolves registration
  form IDs from TB Settings → Registration Settings, never from literals.
- Nested forms (Register New Athlete, Register Returning Athlete) and Submit
  Physical are permanent and reused every season. Parent registration forms
  are duplicated per season.

## Imports and data integrity

- `acf/save_post` does not fire during WPUCI imports. After every import, run
  Tools → Sync Result Times, then Tools → Generate Records, in that order.
- WPUCI post-object columns (`athletic_event`, `meet`) take post IDs, not
  titles. Post IDs differ between local and production.
- Direct SQL uses the table prefix `wp_qscbs1aqvn_` (same on local and prod).
  Never run a write query without Chris's explicit approval.

## Code conventions

- New functionality that doesn't belong in an existing file gets a new file
  under `inc/`, required from `functions.php`.
- Bump the asset version in `tb_enqueue_assets()` after CSS or JS changes.
- TEC CSS variable overrides use `html:root`, not `:root`.
- TEC single event overrides live at `tribe-events/single-event.php`.
