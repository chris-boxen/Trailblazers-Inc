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

## Source-of-truth rules
- Family link is owned by Athlete
- Season participation is owned by Enrollment
- Submission status is owned by Application
- Physical validity/history is owned by Athletic Physical
- Performance data is owned by Athletic Result
- Achievement layer is owned by Athletic Record

## Special schema watchout: Athletic Result
Athletic Result should distinguish between:
- exact display label from the meet (`event_name`)
- canonical event structure (`event`)
- display-ready result value
- normalized sortable/queryable result values