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

**Action item:** Confirm `publicly_queryable` is set to false on all four CPTs in their
ACF Post Type settings. Flag if any are currently set to true.

---

## 4. Payment abstraction
### Question
At what point should payment become its own object rather than status fields on
Application/Enrollment?

### Current lean
Keep payment as status/summary fields for now. Revisit if the workflow grows.

---

## 5. Baseline templates in a Divi child theme
### Status: RESOLVED
### Decision
Baseline fallback templates are not needed in the child theme. Divi provides these
in the parent. The child theme only needs `index.php`.

---

## 6. PHP template vs Divi Theme Builder split
### Status: RESOLVED (updated for TEC)
### Decision
All CPT templates are PHP files. Divi Theme Builder is not used for any CPT templates.

**Updated template list reflecting TEC integration:**
- `single-athlete.php` → PHP (needs flag check updates)
- `single-athletic_season.php` → PHP
- `single-coach.php` → PHP
- `single-athletic_event.php` → PHP (needs flag check + query updates)
- `taxonomy-sport.php` → PHP (needs query update)
- `archive-athlete.php` → PHP
- `archive-athletic_record.php` → PHP (needs query update)
- `tribe/events/single-event.php` → PHP TEC theme override (to build)
- `single-athletic_meet.php` → **ARCHIVED** (`_archived-templates/`)
- `archive-athletic_meet.php` → **ARCHIVED** (`_archived-templates/`)

---

## 7. Event metadata on results vs derived from Athletic Event
### Question
How much event metadata should be stored directly on Athletic Result vs derived at
render time from the linked Athletic Event?

### Current lean
Unresolved. Deferred until query patterns from the GravityForms build make the
tradeoff clearer.

---

## 8. GravityForms registration form architecture
### Status: RESOLVED
### Decision
See CHANGELOG.md 2026-03 for full decision record.

---

## 9. athletic_meet CPT retirement + TEC integration
### Status: RESOLVED
### Decision
`athletic_meet` CPT is retired. `tribe_events` (The Events Calendar Pro) is the
single anchor post for all meet data — both public calendar events and internal
results-bearing records.

**Key rules:**
- All `tribe_events` meet posts are published (never draft), regardless of whether
  they appear publicly. Published status is required for ACF queries.
- `calendar_show_meets` on Athletic Season controls public surfacing, not post status.
- `athletic_result.meet` now points to `tribe_events`.
- TEC's native archive (`/event/`) is redirected to a custom query page via
  `template_redirect` hook.
- Single meet display is a TEC theme override at `tribe/events/single-event.php`.
- **Naming rule:** Always say "Athletic Event" (the definition CPT) vs. "TEC Event"
  or "Meet Event" (the `tribe_events` instance). Never just "event."

---

## 10. TEC event categories — sport differentiation
### Question
Should `tribe_events_cat` include sport-specific sub-categories under `athletic-meet`
(e.g., `athletic-meet > cross-country`, `athletic-meet > track-field`)?

### Why it matters
The TEC public calendar and custom query page may need to filter meets by sport.
The Sport taxonomy is registered on `athletic_event` and other CPTs but is not
natively available to `tribe_events`. Options:
- Add top-level sport terms to `tribe_events_cat` (simple, some duplication)
- Add Sport taxonomy registration to `tribe_events` (cleaner, requires ACF/code change)
- Use sub-categories under `athletic-meet` in `tribe_events_cat`
- Filter the custom query page by season (which implies sport) rather than by taxonomy term

### Current lean
Unresolved. Defer until the custom meet schedule query page is being built. The
answer depends on how the calendar needs to be filtered by site visitors.

---

## 11. T&F coach calendar adoption
### Question
Track & Field coaches are currently leaning toward SportsYou for events. If they
adopt TEC later, what is the migration path for existing T&F meet results?

### Why it matters
T&F results are attached to `tribe_events` posts. If T&F meets are currently
created as bare `tribe_events` posts with no public calendar presence, enabling
them later only requires flipping `calendar_show_meets` on the season. No data
migration needed.

### Current lean
Non-issue architecturally. Document this for coaches so they understand the path
forward is low-friction.