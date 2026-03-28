# TEMPLATES

## Approach
This is a Divi 5 child theme. Divi provides all baseline fallback templates in the parent. The child theme only needs `index.php` for WordPress theme validity — no other baseline overrides are required unless deliberately changing Divi's default behavior.

CPT-specific templates are built in the child theme because Divi has no meaningful fallback for custom post type data and relational queries.

## Template strategy: PHP vs Divi Theme Builder

| Template | Approach | Status | Reason |
|---|---|---|---|
| `single-athlete.php` | PHP template | ✅ Built | Results history, PR/SR records, and season history require relational WP_Query loops |
| `single-athletic_meet.php` | PHP template | ✅ Built | Results table with event filtering requires a custom loop and sort logic |
| `single-athletic_season.php` | Divi Theme Builder | ⬜ Not started | Primarily layout + dynamic fields; no complex relational queries |
| `taxonomy-sport.php` | Divi Theme Builder | ⬜ Not started | Filtered archive view; Divi handles this reasonably |
| `single-coach.php` | Divi Theme Builder | ⬜ Not started | Bio, image, season associations — simple dynamic fields |

## Build order

### Now
- `single-athletic_season.php` (Theme Builder)
- `taxonomy-sport.php` (Theme Builder)

### Next
- `single-coach.php` (Theme Builder)

### Later
- `archive-athlete.php` (approach TBD)
- `archive-athletic_meet.php` (approach TBD)
- `single-athletic_event.php` (approach TBD)
- `archive-athletic_record.php` (approach TBD)

## Likely no public template
- Family
- Application
- Enrollment
- Athletic Physical

## Template-parts direction
Add for PHP templates when complexity grows:
- `template-parts/athlete/`
- `template-parts/meet/`
- `template-parts/globals/`

Not needed (Theme Builder handles):
- `template-parts/season/`
- `template-parts/coach/`

## Watchout
Divi Theme Builder can silently override PHP templates. If a PHP template appears blank, check Theme Builder for a conflicting Singles or All Posts assignment targeting that CPT.