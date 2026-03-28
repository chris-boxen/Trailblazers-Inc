# OPEN QUESTIONS

## 1. Athletic Result normalization
### Question
How should athletic results be stored so they are both human-readable and sortable/filterable?

### Why it matters
Results need to power athlete pages, meet pages, records, leaderboards, and PR/SR logic.

### Current options
- Display text + one generic numeric field
- Display text + multiple normalized numeric fields by measurement type

### Current lean
Use `result_display` plus type-specific normalized numeric fields.

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