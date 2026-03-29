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

Coach is registered with sport to allow direct sport-based querying (e.g. on taxonomy-sport.php). A coach's role and bio for a specific season are stored in the `coach_roster` repeater on the Athletic Season post — not on the Coach post itself.

## Source-of-truth rules
- Family link is owned by Athlete
- Season participation is owned by Enrollment
- Submission status is owned by Application
- Physical validity/history is owned by Athletic Physical
- Performance data is owned by Athletic Result
- Achievement layer is owned by Athletic Record
- Coach sport association is owned by the Coach post (via sport taxonomy)
- Coach role per season is owned by Athletic Season (via `coach_roster` repeater)

## Special schema watchout: Athletic Result
Athletic Result should distinguish between:
- exact display label from the meet (`event_name`)
- canonical event structure (`event`)
- display-ready result value (`result_display`)
- normalized sortable/queryable values (`result_time_seconds`, `result_distance_meters`, `result_height_meters`, `result_points`)

## Special schema watchout: Coach
Coach identity and sport association live on the Coach post.
Coach role, bio override, and image override per season live in the `coach_roster` repeater on Athletic Season.
Do not move role data onto the Coach post — it is intentionally season/sport-specific.

## Special schema watchout: Sport taxonomy
Sport is hierarchical. When querying by exact term (not including children), always use:
```php
'include_children' => false
```
in `tax_query` to avoid unintended matches on child terms.

## Athlete fields of note
- `gender` — select (M / F)
- `graduation_year` — number
- `account_status` — select (Active / Inactive / Alumni / Archived)
- `dob` — date picker
- `athletic_net_id` — text (external ID reference)
These fields support filtering and display on the sport taxonomy archive page.