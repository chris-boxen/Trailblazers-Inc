# CHANGELOG

## 2026-04

### Completed data population — Families, Athletes, Applications, Enrollments
Successfully imported via WP Ultimate CSV Importer:
- Families (90 records, including 14 new T&F-only families)
- Athletes (145 records, active + alumni)
- Applications (2024 XC and 2025 XC)
- Enrollments (2025 XC: 142 records; 2026 TF: 56 records)

Venues imported via WordPress XML importer.
TEC Events (meets) imported via TEC's native CSV importer.

---

### Discovered: WP Ultimate CSV Importer does not reliably resolve ACF Post Object
fields nested inside ACF Group wrappers

**Finding:** During enrollment import, ACF Post Object fields (season, family,
athlete, application) failed to resolve when those fields were nested inside an
ACF Group field. The same fields resolved correctly when moved to the top level
of the field group.

**Confirmed behavior:** The importer resolves post object relationships by post
title. This works correctly for top-level post object fields. Inside a Group
wrapper, resolution is unreliable — some fields may resolve, others silently fail.

**Resolution:** Removed the Group wrapper from the Enrollment field group's
Connections section. The four post object fields (season, family, athlete,
application) are now top-level fields. The Group was UI-only and had no
functional purpose.

**Rule going forward:** Do not nest ACF Post Object fields inside Group fields
on any CPT where CSV import will be used. This applies to Athletic Result and
Athletic Record field groups, which will be imported next. Confirmed: neither
uses Group wrappers.

**ACF JSON affected:** `group_tb_enrollment.json` — committed to repo.

---

### Renamed ACF field: `event` → `athletic_event` on Athletic Result
The post object field linking Athletic Result to Athletic Event was renamed from
`event` to `athletic_event` to eliminate naming ambiguity with TEC's `tribe_events`
post type. Updated in `group_tb_athletic_result.json`. All templates updated
accordingly (`meta_query` key changed from `'event'` to `'athletic_event'`).

## 2026-03 (TEC integration + schema restructure)

### Integrated The Events Calendar Pro — retired athletic_meet CPT
The Events Calendar Pro (TEC) is now part of the project stack. After architectural
review, `athletic_meet` has been retired as a custom CPT. `tribe_events` now serves
as the single anchor post for all meet data.

**Rationale:** Maintaining a parallel `athletic_meet` post alongside a TEC event for
every XC meet added admin overhead without architectural benefit. A single `tribe_events`
post per meet is cleaner — TEC handles public calendar display natively, and ACF fields
on `tribe_events` handle the internal data layer.

**What changed:**
- `post_type_tb_athletic_meet` — deleted from ACF, JSON retired to archive
- `group_tb_athletic_meet` — deleted from ACF, JSON retired to archive
- `single-athletic_meet.php` — moved to `_archived-templates/`
- `archive-athletic_meet.php` — moved to `_archived-templates/`
- New ACF field group `group_tb_tec_event` created on `tribe_events`:
  - `season` — Post Object → `athletic_season`
  - `results_status` — Select (Future / Pending / Available)
- `group_tb_athletic_result` — `meet` field target changed from `athletic_meet`
  to `tribe_events`
- TEC event slug configured as `/event/`
- `tribe_venue` adopted for venue management
- TEC archive (`/event/`) to be redirected to a custom query page via
  `template_redirect` hook in `inc/cpt-hooks.php`
- Single meet display to be built as TEC theme override:
  `tribe/events/single-event.php`

**Non-calendar meets (T&F / SportsYou coaches):**
All `tribe_events` meet posts are published regardless of public calendar visibility.
The `calendar_show_meets` season flag controls whether meets surface on the public
schedule. Published status is required for ACF post object queries to resolve.

**Two concepts named "event" — critical distinction:**
- `athletic_event` — canonical event *definition* (5K, 100m, Long Jump). Unchanged.
- `tribe_events` — a meet *instance* (specific date, location, season). Replaces `athletic_meet`.
This distinction must be maintained in all code comments and documentation.

---

### Added per-season feature flags to Athletic Season CPT
New True/False fields added to `group_tb_athletic_season`:

| Field | Purpose |
|---|---|
| `calendar_show_meets` | Whether upcoming meets publish to TEC public calendar |
| `calendar_show_practices` | Whether practices publish to TEC public calendar |
| `results_enabled` | Whether results are surfaced in templates (display control only) |
| `link_milesplit` | Whether Milesplit athlete links are rendered |
| `link_athletic_net` | Whether AthleticNet athlete links are rendered |

New Textarea field: `results_unavailable_message` — shown in templates when
`results_enabled = false`. Falls back to a generic message if blank.

Flags control display only. They do not prevent data entry. An admin can flip
`results_enabled` at any time to start or stop surfacing results for a season.

**Naming note:** The field was intentionally named `results_enabled` rather than
`track_results` to avoid semantic ambiguity with "track and field results."

---

### Resolved: `results_enabled` naming
Confirmed `results_enabled` as the canonical field name. `track_results` was
rejected because in a track and field context it reads as a noun phrase
("track-and-field results") rather than the intended verb phrase ("record/monitor
results"). Documented in SCHEMA.md.

---

## 2026-03 (continued from previous entries)

### Designed GravityForms registration form architecture
Replaced the previous multi-form, multi-user approach with a simplified architecture
reflecting the new single-household-user model. Key decisions:
- Registration entry point is a WP page with two buttons, not a form
- New Family and Returning Family are separate parent forms (5 pages each)
- Single combined nested form for Athlete + Sibling Runner registration
  (participation type radio drives conditional eligibility blocks)
- `Register New Parent`, `Register Returning Athlete`, and `Register Sibling Runner`
  from the 2025 form set are eliminated
- Both forms require login; Returning Family locates the Family post via
  `account_user = get_current_user_id()` — no user-visible Family ID
- Active season populated via `gform_field_value` filter reading `tb_active_season_id`
  site option
- Handbook page (Page 3) pulls handbook URL dynamically from the Athletic Season CPT
  `handbook` link field via `gform_field_value`
- Payment page includes optional processing contribution as a native GF Product field
  (radio buttons: No thanks $0 / Help a little $3 / Cover processing $5 / Pay it
  forward $10); folds into GF order total natively with no custom code
- Full field map documented in `docs/FORM-FIELD-MAP.md`

### Added participation_type to Athlete CPT
Added `participation_type` select field (Athlete / Sibling Runner, allow null) to
`group_tb_athlete.json`. Denormalized convenience field for archive filtering. Source
of truth for season-specific type remains Enrollment. Updated by enrollment creation
hook. Empty values treated as Athlete in templates and JS filters. See SCHEMA.md.

### Added payment_amount to Application CPT
Added `payment_amount` number field (step 0.01, prepend $) to
`group_tb_application.json`. Set by hook from GF order total at submission. Reflects
combined registration fee plus any optional processing contribution donation.

### Resolved: account_status vs participation_type distinction
Confirmed Alumni belongs in `account_status`, not `participation_type`. The two
fields answer different questions — lifecycle state vs. season enrollment type.
Documented in SCHEMA.md.

### Determined build sequence for registration system
Data population must precede form build; form build must precede hook code (hooks
require real GF field IDs). Parked GravityForms work pending data population thread.