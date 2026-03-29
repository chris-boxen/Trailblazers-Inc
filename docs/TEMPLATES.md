# TEMPLATES

## Approach
This is a Divi 5 child theme. Divi provides all baseline fallback templates in the parent. The child theme only needs `index.php` for WordPress theme validity — no other baseline overrides are required unless deliberately changing Divi's default behavior.

All CPT-specific templates are built as PHP files. Divi Theme Builder may be used later to replace any template if a visual editing workflow is preferred, but PHP is the current standard.

## Template status

| Template | Approach | Status | Notes |
|---|---|---|---|
| `single-athlete.php` | PHP | ✅ Built | Bio, season history, PRs, results (Season → Meet) |
| `single-athletic_meet.php` | PHP | ✅ Built | Meet header, results by event, gated by `results_status` |
| `single-athletic_season.php` | PHP | ✅ Built | Header, coaches, meet schedule, athlete roster |
| `single-coach.php` | PHP | ✅ Built | Photo, name, title, bio |
| `taxonomy-sport.php` | PHP | ✅ Built | Sport header, seasons, coaches, athletes table with filtering attributes |
| `archive-athlete.php` | PHP | ✅ Built | All athletes, sortable/filterable table, data attributes |
| `archive-athletic_meet.php` | PHP | ✅ Built | All meets, sortable/filterable table, data attributes |
| `single-athletic_event.php` | PHP | ⬜ Not started | — |
| `archive-athletic_record.php` | PHP | ⬜ Not started | — |

## Likely no public template
- Family
- Application
- Enrollment
- Athletic Physical

## Data attributes pattern
Archive and taxonomy templates follow a consistent pattern for JS filtering:
- Athlete rows: `data-gender`, `data-grad-year`, `data-status`, `data-sport`
- Meet rows: `data-season`, `data-sport`, `data-status`, `data-results-status`, `data-year`
- Sport taxonomy athlete rows: `data-gender`, `data-status`
- Attribute values are always lowercase slugs
- Space-separated values used when a row has multiple terms (e.g. multi-sport athletes)

## Template-parts direction
Add when individual templates grow complex enough to warrant partials:
- `template-parts/athlete/`
- `template-parts/meet/`
- `template-parts/season/`
- `template-parts/globals/`

## Watchouts
- Divi Theme Builder can silently override PHP templates. If a PHP template appears blank, check Theme Builder for a conflicting Singles or All Posts assignment targeting that CPT.
- Hierarchical sport taxonomy: use `'include_children' => false` in `tax_query` when exact-term matching is needed.
- Coach role/bio override per season lives in the `coach_roster` repeater on Athletic Season — not on the Coach post itself.
- Archive templates require `has_archive => true` on the CPT. If an archive URL 404s, check ACF Post Types → Advanced → Has Archive.