# TEMPLATES

## Approach
This is a Divi 5 child theme. Divi provides all baseline fallback templates in the parent. The child theme only needs `index.php` for WordPress theme validity — no other baseline overrides are required unless deliberately changing Divi's default behavior.

All CPT-specific templates are built as PHP files.

## Template status

| Template | Approach | Status | Notes |
|---|---|---|---|
| `single-athlete.php` | PHP | ✅ Built | Bio, season history, PRs, results (Season → Meet) |
| `single-athletic_meet.php` | PHP | ✅ Built | Meet header, results by event, gated by `results_status` |
| `single-athletic_season.php` | PHP | ✅ Built | Header, coaches, meet schedule, athlete roster, sibling runner roster. Updated 2026-05-03: ul/li lists, base classes, header secondary section. Roster split by participation_type; gender column on both roster lists. |
| `single-coach.php` | PHP | ✅ Built | Photo, name, title, bio. Updated 2026-05-03: base template classes applied, photo in `.tb-single-header-secondary-section` |
| `single-athletic_event.php` | PHP | ✅ Built | Header with linked sport, records, results (Season → Meet) |
| `taxonomy-sport.php` | PHP | ✅ Built | Sport header, seasons, coaches, athletes table with filtering attributes |
| `archive-athlete.php` | PHP | ✅ Built | All athletes, sortable/filterable list, data attributes |
| `archive-athletic_meet.php` | PHP | ✅ Built | All meets, sortable/filterable list, data attributes |
| `archive-athletic_record.php` | PHP | ✅ Built | Sport → Event → Records, data attributes including data-is-current |
| `archive-athletic_season.php` | PHP | ✅ Built | All seasons, ul/li list, data-sport / data-status / data-year attributes, sorted start_date DESC |

## Likely no public template
- Family
- Application
- Enrollment
- Athletic Physical

## Base template styles — templates.css
`assets/css/templates.css` provides shared base classes for all archive and single
templates. Imported from `assets/css/styles.css`.

### Single base pattern
Add `.tb-single` alongside the template-specific outer class:
```html
<div class="tb-single tb-season">
```
Add `.tb-single-section` alongside template-specific section classes:
```html
<section class="tb-single-section tb-season-coaches">
```

### Header layout pattern
```html
<section class="tb-single-header tb-season-header">
  <div class="tb-single-headline">       <!-- title, meta, description -->
  <div class="tb-single-header-secondary-section">  <!-- image + CTA stacked -->
	<div class="tb-single-image">
	<div class="tb-single-cta">
```

### List pattern
All data lists use `ul/li` with CSS grid columns — no HTML tables.
```html
<ul class="tb-list tb-coaches-list">
  <li class="tb-list-header">
	<span class="tb-col">Name</span>
	...
  </li>
  <li class="tb-list-row" data-*="…">
	<a href="…" class="tb-list-link">
	  <span class="tb-col">Value</span>
	  ...
	</a>
  </li>
</ul>
```
Column widths are defined per-list via `--tb-cols` in `templates.css`:
```css
.tb-coaches-list { --tb-cols: 2fr 1.5fr 1.5fr; }
```

## Data attributes pattern
All archive and taxonomy templates use consistent data attributes for JS filtering.
Attribute values are always lowercase slugs. Space-separated values used for multi-term fields.

| Template | Data attributes |
|---|---|
| `archive-athlete.php` | `data-gender`, `data-grad-year`, `data-status`, `data-sport` |
| `archive-athletic_meet.php` | `data-season`, `data-sport`, `data-status`, `data-results-status`, `data-year` |
| `archive-athletic_record.php` | `data-record-type`, `data-sport`, `data-event`, `data-is-current` |
| `archive-athletic_season.php` | `data-sport`, `data-status`, `data-year` |
| `taxonomy-sport.php` athlete rows | `data-gender`, `data-status` |
| `single-athletic_season.php` coaches | `data-name`, `data-role` |
| `single-athletic_season.php` meets | `data-date`, `data-results` |
| `single-athletic_season.php` athletes | `data-grade`, `data-type` |

## Template-parts direction
Add when individual templates grow complex enough to warrant partials:
- `template-parts/athlete/`
- `template-parts/meet/`
- `template-parts/season/`
- `template-parts/globals/`

## Watchouts
- Divi Theme Builder can silently override PHP templates. If a template appears blank, check Theme Builder assignments.
- Hierarchical sport taxonomy: use `'include_children' => false` in `tax_query` when exact-term matching is needed.
- Coach role/bio override per season lives in the `coach_roster` repeater on Athletic Season — not on the Coach post.
- Archive templates require `has_archive => true` on the CPT. If an archive URL 404s, check ACF Post Types → Advanced → Has Archive.
- Results on `single-athletic_event.php` query via the `event` post object field. Results with only a free-text `event_name` won't appear here.
- Current record detection: if two records for the same athlete/event/type share the same meet date, both are flagged as current.
- `featured_image` on Athletic Season is an ACF image field (returns ID), not the WP native post thumbnail. Use `get_field( 'featured_image', $id )`, not `get_post_thumbnail_id()`.