## 2026-03 (continued)

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