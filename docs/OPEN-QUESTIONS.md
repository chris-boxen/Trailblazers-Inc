# OPEN QUESTIONS

## 1. Athletic Result normalization
### Status: RESOLVED
### Decision
Use `result_display` for human-readable output plus type-specific normalized numeric fields: `result_time_seconds`, `result_distance_meters`, `result_height_meters`, and `result_points`. This is reflected in the current `group_tb_athletic_result.json`.

---

## 2. Theme folder naming
### Question
Should the theme folder be renamed from `Trailblazers 2026` to a slug-style folder name like `trailblazers-2026`?

### Why it matters
Cleaner terminal/Git workflows and fewer path quirks.

### Current lean
Probably yes, but not urgent.

---

## 3. Public exposure of operational CPTs
### Question
Should Family, Application, Enrollment, and Athletic Physical ever have public-facing templates?

### Why it matters
Affects template strategy, security assumptions, and front-end architecture.

### Current lean
Likely admin/data objects only, with no meaningful public templates.

---

## 4. Payment abstraction
### Question
At what point should payment become its own object rather than status fields on Application/Enrollment?

### Why it matters
May become necessary if partial payments, refunds, or multiple payment methods grow more complex.

### Current lean
Keep payment as status/summary fields for now.

---

## 5. Baseline templates in a Divi child theme
### Status: RESOLVED
### Decision
Baseline fallback templates (`header.php`, `footer.php`, `page.php`, `single.php`, `archive.php`) are not needed in the child theme. Divi provides these in the parent. The child theme only needs `index.php` to satisfy WordPress theme validity, which already exists.

---

## 6. PHP template vs Divi Theme Builder split
### Status: RESOLVED
### Decision
Templates that require relational queries or custom loops must be built as PHP templates. Templates that are primarily layout and dynamic field display can use Divi Theme Builder.

- `single-athlete.php` → PHP (results history, PR/SR records, season history require WP_Query)
- `single-athletic_meet.php` → PHP (results table with event filtering requires a loop)
- `single-athletic_season.php` → Divi Theme Builder
- `taxonomy-sport.php` → Divi Theme Builder
- `single-coach.php` → Divi Theme Builder

---

## 7. Event metadata on results vs derived from Athletic Event
### Question
How much event metadata should be stored directly on Athletic Result vs derived at render time from the linked Athletic Event?

### Why it matters
Affects query complexity, template logic, and how much denormalization is acceptable on the result record.

### Current lean
Unresolved. Deferred until template build begins and query patterns become clearer.