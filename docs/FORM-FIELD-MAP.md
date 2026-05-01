# Trailblazers — GravityForms Field Map
**Version:** 2.3
**Season:** 2026 XC
**Status:** Ready to build

---

## Conventions

| Column | Meaning |
|---|---|
| **GF Type** | Gravity Forms field type |
| **Maps to** | CPT → ACF field name |
| **How set** | `user` = entered by submitter / `hidden` = invisible field / `hook` = set programmatically / `pre-pop` = GPPA pre-populated / `read-only` = GPPA pre-populated, non-editable |
| **Notes** | Conditional logic, GravityPerks dependencies, or implementation warnings |

---

## Registration Entry Point

`/registration/` uses `[tb_reg_router]` shortcode. Detects user state and routes:

| User state | Result |
|---|---|
| Not logged in | Shows Create Account + Log In buttons |
| Logged in, no Family post | Redirects to `/registration/new-families/` |
| Logged in, Family post, no active-season Enrollment | Redirects to `/registration/returning-families/` |
| Logged in, Family post, active-season Enrollment exists | Shows already-registered message from options |

Each child page is protected by user-state guards in the `[tb_reg_form]` shortcode handler (`inc/registration-helpers.php`). Logged-out users and users on the wrong form type are redirected to `/registration/`.

---

## Form 1: 2026 Registration — New Family

**GF Form ID:** Assigned on import — see TB Settings → Registration Settings → Form IDs.
**Hook fires:** `gform_after_submission` — form ID resolved from Registration Settings at runtime

---

### Page 1 — Family Contact Info

| Field | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| Family Name | Text | `family` → `family_display_name` | user | Required. |
| *(Season ID)* | Hidden | `application` → `season` | hidden | `gform_field_value` reads `get_option('tb_active_season_id')`. |
| *(Current User ID)* | Hidden | `family` → `account_user` | hidden | `gform_field_value` reads `get_current_user_id()`. |
| **— Address —** | Section | | | |
| Street Address | Text | `family` → `street_address` | user | Required. |
| City | Text | `family` → `city` | user | Required. |
| State | Select | `family` → `state` | user | Default: SC. |
| **— Primary Contact —** | Section | | | |
| First Name | Text | `family` → `parents_guardians[0].guardian_first_name` | user | Required. |
| Last Name | Text | `family` → `parents_guardians[0].guardian_last_name` | user | Required. |
| Relationship | Select | `family` → `parents_guardians[0].guardian_relationship` | user | Required. |
| Email | Email | `family` → `parents_guardians[0].guardian_email` | user | Required. |
| Phone | Phone | `family` → `parents_guardians[0].guardian_phone` | user | Required. |
| **— Secondary Contact (Optional) —** | Section | | | |
| First Name | Text | `family` → `parents_guardians[1].guardian_first_name` | user | Optional. |
| Last Name | Text | `family` → `parents_guardians[1].guardian_last_name` | user | Optional. |
| Relationship | Select | `family` → `parents_guardians[1].guardian_relationship` | user | Optional. |
| Email | Email | `family` → `parents_guardians[1].guardian_email` | user | Optional. |
| Phone | Phone | `family` → `parents_guardians[1].guardian_phone` | user | Optional. |
| Receive Email Notifications? | Radio | `family` → `parents_guardians[1].guardian_notifications` | user | Yes/No. Optional. Secondary contact only. |

> **Primary contact notifications:** Not collected on the form. Hook always sets `guardian_notifications = 1` (true_false) for the primary contact.

---

### Page 2 — Athletes

| Field | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| *(Instructions HTML)* | HTML | — | — | Instructions for registering athletes and sibling runners. |
| Register Athletes | GP Nested Form | See nested form map below | user | Nested: Register New Athlete (Form 100). Min: 1. |

---

### Page 3 — Handbook

| Field | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| *(Season Handbook URL)* | Hidden | *(internal)* | hidden | `gform_field_value` reads `handbook` field from active season post. Placeholder: `https://trailblazers.team` until handbook is published. |
| *(Handbook HTML)* | HTML | — | — | Button linking to handbook URL via merge tag. |
| Handbook Acknowledgment | Checkbox | *(gate only)* | user | Required. Single checkbox. |

---

### Page 4 — Waiver & Signature

| Field | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| *(Waiver HTML)* | HTML | — | — | Static waiver copy. |
| Digital Signature | Text | `application` → `digital_signature` | user | Required. Parent types full legal name. |
| Today's Date | Date | `application` → `submission_date` | user | Defaults to today. |

---

### Page 5 — Payment

| Field | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| *(Fee summary HTML)* | HTML | — | — | $75/athlete, $35/singlet for new athletes. |
| Athlete Count | Number (calc) | *(display only)* | calculated | `{Register Athletes:23:count}` |
| Registration Subtotal | Number (calc) | *(display only)* | calculated | `{Athlete Count:36} * 75` |
| *(Singlet HTML)* | HTML | — | — | Singlet policy for new athletes. |
| Singlet Count | Number (calc) | *(display only)* | calculated | `{Register Athletes:23:count}` — all new athletes require a singlet. |
| Singlet Subtotal | Number (calc) | *(display only)* | calculated | `{Singlet Count:40} * 35` |
| Registration Total | Product (calc) | *(feeds order total)* | calculated | `({Athlete Count:36} * 75) + ({Singlet Count:40} * 35)` |
| Processing Contribution | Product (Radio) | *(feeds order total)* | user | No thanks $0 / Help a little $3 / Cover processing $5 / Pay it forward $10. Required. |
| Order Total | Total | `application` → `payment_amount` | calculated | GF Total field. Hook writes to Application. |
| Payment Method | Radio | `application` → `payment_method` | user | Credit Card / Check/Cash. Required. |
| Credit Card | Stripe | — | user | Conditional: visible if Payment Method = Credit Card. |

---

## Form 2: 2026 Registration — Returning Family

**GF Form ID:** Assigned on import — see TB Settings → Registration Settings → Form IDs.
**Hook fires:** `gform_after_submission` — form ID resolved from Registration Settings at runtime

---

### Page 1 — Family Contact Info

All fields pre-populated via GPPA. Field 60 (hidden) anchors all GPPA queries by resolving the current user's Family post ID.

| Field | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| *(Family Post ID)* | Hidden (Field 60) | *(GPPA anchor)* | GPPA | Queries family post where `meta_account_user = current_user:ID`. Referenced by all other GPPA fields on this form and by the returning athlete nested form. Never visible to user. |
| Family Name | Text | `family` → `family_display_name` | **read-only** | Pre-populated. **GW Read Only required.** Hook does NOT update `family_display_name` from this field. |
| *(Season ID)* | Hidden | `application` → `season` | hidden | `gform_field_value` reads `get_option('tb_active_season_id')`. |
| *(Current User ID)* | Hidden | `family` → `account_user` | hidden | Used by hook to locate Family post. |
| **— Address —** | Section | | | Pre-populated, editable. Hook updates on submission. |
| Street Address | Text | `family` → `street_address` | pre-pop | Editable. |
| City | Text | `family` → `city` | pre-pop | Editable. |
| State | Select | `family` → `state` | pre-pop | Editable. |
| **— Primary Contact —** | Section | | | Pre-populated, editable. |
| First Name | Text | `family` → `parents_guardians[0].guardian_first_name` | pre-pop | Editable. |
| Last Name | Text | `family` → `parents_guardians[0].guardian_last_name` | pre-pop | Editable. |
| Relationship | Select | `family` → `parents_guardians[0].guardian_relationship` | pre-pop | Editable. |
| Email | Email | `family` → `parents_guardians[0].guardian_email` | pre-pop | Editable. |
| Phone | Phone | `family` → `parents_guardians[0].guardian_phone` | pre-pop | Editable. |
| **— Secondary Contact (Optional) —** | Section | | | Pre-populated from `parents_guardians[1]` if exists. |
| First Name | Text | `family` → `parents_guardians[1].guardian_first_name` | pre-pop | Optional. |
| Last Name | Text | `family` → `parents_guardians[1].guardian_last_name` | pre-pop | Optional. |
| Relationship | Select | `family` → `parents_guardians[1].guardian_relationship` | pre-pop | Optional. |
| Email | Email | `family` → `parents_guardians[1].guardian_email` | pre-pop | Optional. |
| Phone | Phone | `family` → `parents_guardians[1].guardian_phone` | pre-pop | Optional. |
| Receive Email Notifications? | Radio | `family` → `parents_guardians[1].guardian_notifications` | pre-pop | Yes/No. Optional. Secondary contact only. |

> **Primary contact notifications:** Not collected on the form. Hook always sets `guardian_notifications = 1` (true_false) for the primary contact.

---

### Page 2 — Athletes

| Field | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| **— Returning Athletes —** | Section | | | |
| *(Instructions HTML)* | HTML | — | — | Explains that identity fields are pre-filled and grade + eligibility must be confirmed each season. |
| Register Returning Athletes | GP Nested Form | See nested form map below | user | Nested: Register Returning Athlete (Form 103). Min: 0. |
| **— New Athletes (Optional) —** | Section | | | |
| *(Instructions HTML)* | HTML | — | — | Instructions for adding a new athlete. |
| Register New Athletes | GP Nested Form | See nested form map below | user | Nested: Register New Athlete (Form 100). Min: 0. |

---

### Page 3 — Handbook

Identical in structure to New Family Page 3.

---

### Page 4 — Waiver & Signature

Identical in structure to New Family Page 4.

---

### Page 5 — Payment

| Field | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| *(Fee summary HTML)* | HTML | — | — | Fee explanation for mixed new/returning families. |
| Returning Athletes | Number (calc) | *(display only)* | calculated | `{Register Returning Athletes:24:count}` |
| New Athletes | Number (calc) | *(display only)* | calculated | `{Register New Athletes:27:count}` |
| Total Athletes | Number (calc) | *(display only)* | calculated | `{Returning Athletes:40} + {New Athletes:41}` |
| Registration Subtotal | Number (calc) | *(display only)* | calculated | `{Total Athletes:42} * 75` |
| *(Singlet HTML)* | HTML | — | — | Singlet policy explanation. |
| Required Singlets (New Athletes) | Number (calc) | *(display only)* | calculated | `{Register New Athletes:27:count}` |
| Additional Singlets (Returning) | Number (manual) | *(payment calc)* | user | Default 0. Parent enters count of returning athletes who selected a new singlet. |
| Singlet Subtotal | Number (calc) | *(display only)* | calculated | `({Required Singlets:46} + {Additional Singlets:47}) * 35` |
| Registration Total | Product (calc) | *(feeds order total)* | calculated | `({Total Athletes:42} * 75) + (({Required Singlets:46} + {Additional Singlets:47}) * 35)` |
| Processing Contribution | Product (Radio) | *(feeds order total)* | user | Identical to New Family. |
| Order Total | Total | `application` → `payment_amount` | calculated | Hook writes to Application. |
| Payment Method | Radio | `application` → `payment_method` | user | Credit Card / Check/Cash. |
| Credit Card | Stripe | — | user | Conditional: visible if Payment Method = Credit Card. |

---

## Nested Form: Register New Athlete (Form 100)

**Used by:** New Family Page 2 (all athletes)
**Also used by:** Returning Family Page 2 (new athletes only)
**Permanent / reusable** — no season-specific content.

| Field | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| Participation Type | Radio | `enrollment` → `participation_type` | user | Athlete / Sibling Runner. Drives all conditional logic. |
| **— Identity —** | Section | | | |
| First Name | Text | `athlete` → `first_name` | user | Required. |
| Last Name | Text | `athlete` → `last_name` | user | Required. |
| Preferred Name / Nickname | Text | `athlete` → `preferred_name` | user | Optional. |
| Gender | Radio | `athlete` → `gender` | user | Male / Female. |
| Date of Birth | Date | `athlete` → `dob` | user | Required. |
| Grade | Select | `enrollment` → `grade` | user | 1st–12th. Required. |
| **— Eligibility — Athlete —** | Section | | | Conditional: Participation Type = Athlete |
| Residency | Checkbox | `enrollment` → `eligibility_confirmed` | user | Required if Athlete. |
| Homeschooled | Checkbox | `enrollment` → `eligibility_confirmed` | user | Required if Athlete. |
| Academic Eligibility | Checkbox | `enrollment` → `eligibility_confirmed` | user | Required if Athlete. |
| Running Commitment | Checkbox | `enrollment` → `eligibility_confirmed` | user | Required if Athlete. |
| Policy Compliance | Checkbox | `enrollment` → `eligibility_confirmed` | user | Required if Athlete. |
| **— Eligibility — Sibling Runner —** | Section | | | Conditional: Participation Type = Sibling Runner |
| Parent Supervision Acknowledgment | Checkbox | `enrollment` → `eligibility_confirmed` | user | Required if Sibling Runner. |
| Policy Compliance (Sibling Runner) | Checkbox | `enrollment` → `eligibility_confirmed` | user | Required if Sibling Runner. |
| **— Uniform —** | Section | | | |
| Requesting New Singlet? | Radio | `enrollment` → `singlet_requested` | user | **Default: Yes.** New athletes are required to purchase a singlet. |
| Singlet Sizing Group | Select | `enrollment` → `singlet_sizing_group` | user | Conditional: visible if Singlet Requested = Yes. |
| Singlet Size | Select | `enrollment` → `singlet_size` | user | Conditional: visible if Singlet Requested = Yes. |

> **Shorts note:** Shorts are not sold through registration for XC. `shorts_requested`, `shorts_sizing_group`, and `shorts_size` fields exist on the Enrollment CPT but are not collected here.

---

## Nested Form: Register Returning Athlete (Form 103)

**Used by:** Returning Family Page 2 (returning athletes only)
**Permanent / reusable** — no season-specific content. Built once, reused every season.

Field 1 (Family Post ID) is a hidden GPPA anchor that self-resolves via `family post where meta_account_user = current_user:ID`. The nested form is self-contained and does not require a reference to any parent form field.

| Field | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| *(Family Post ID)* | Hidden (Field 1) | *(GPPA anchor)* | GPPA | Queries family post where `meta_account_user = current_user:ID` → returns post ID. Anchors Field 2 athlete selector. |
| Select Athlete | Select (Field 2) | `enrollment` → `athlete` | user | GPPA choices: Athlete posts where `meta_family = gf_field:1` AND `meta_account_status = Active`. Label = `post_title`, value = post ID. |
| **— Athlete Identity —** | Section | | | Fields 4–7 are read-only, GPPA-populated from selected Athlete post. |
| First Name | Text | *(display only)* | **read-only** | GPPA values: Athlete post where `ID = gf_field:2` → `meta_first_name`. GW Read Only required. |
| Last Name | Text | *(display only)* | **read-only** | GPPA values → `meta_last_name`. |
| Gender | Radio | *(display only)* | **read-only** | GPPA values → `meta_gender`. |
| Date of Birth | Date | *(display only)* | **read-only** | GPPA values → `meta_dob`. |
| Grade | Select | `enrollment` → `grade` | user | Required. Entered fresh each season. 1st–12th. |
| Participation Type | Radio | `enrollment` → `participation_type` | user | Athlete / Sibling Runner. May change season to season (sibling runner can become full athlete). |
| **— Eligibility — Athlete —** | Section | | | Conditional: Participation Type = Athlete |
| Residency | Checkbox | `enrollment` → `eligibility_confirmed` | user | Re-confirmed each season. |
| Homeschooled | Checkbox | `enrollment` → `eligibility_confirmed` | user | Re-confirmed each season. |
| Academic Eligibility | Checkbox | `enrollment` → `eligibility_confirmed` | user | Re-confirmed each season. |
| Running Commitment | Checkbox | `enrollment` → `eligibility_confirmed` | user | Re-confirmed each season. |
| Policy Compliance | Checkbox | `enrollment` → `eligibility_confirmed` | user | Re-confirmed each season. |
| **— Eligibility — Sibling Runner —** | Section | | | Conditional: Participation Type = Sibling Runner |
| Parent Supervision Acknowledgment | Checkbox | `enrollment` → `eligibility_confirmed` | user | Re-confirmed each season. |
| Policy Compliance (Sibling Runner) | Checkbox | `enrollment` → `eligibility_confirmed` | user | Re-confirmed each season. |
| **— Uniform —** | Section | | | |
| Requesting New Singlet? | Radio | `enrollment` → `singlet_requested` | user | **Default: No.** Returning athletes already have a singlet. Yes only if requesting a replacement. |
| Singlet Sizing Group | Select | `enrollment` → `singlet_sizing_group` | user | Conditional: visible if Singlet Requested = Yes. |
| Singlet Size | Select | `enrollment` → `singlet_size` | user | Conditional: visible if Singlet Requested = Yes. |

---

## Waiver Text (Page 4 — both parent forms)

```html
<p>As a parent or legal guardian of the above named student-athlete(s). I give
permission for his/her participation in athletic events and the physical evaluation
for that participation. I understand that this is simply a screening evaluation and
not a substitute for regular health care. I also grant permission for treatment
deemed necessary for a condition arising during participation of these events,
including medical or surgical treatment that is recommended by a medical doctor.
I grant permission to nurses, trainers and coaches as well as physicians or those
under their direction who are part of athletic injury prevention and treatment, to
have access to necessary medical information. I know that the risk of injury to my
child/ward comes with participation in sports and during travel to and from play
and practice. I have had the opportunity to understand the risk of injury during
participation in sports through meetings, written information or by some other
means. My signature indicates that to the best of my knowledge, my answers to the
above questions are complete and correct. I understand that the data acquired
during these evaluations may be used for research purposes.</p>
```

---

## Hook-Set Fields — Not in Any Form

| CPT | Field | Value set by hook |
|---|---|---|
| `family` | `account_user` | `get_current_user_id()` |
| `family` | `family_status` | `Active` |
| `family` | `family_display_name` | Set by New Family hook only. **NOT updated by Returning Family hook.** |
| `family` | `parents_guardians[0].guardian_notifications` | Always `1` (true_false) — not collected in form |
| `family` | `parents_guardians[0].is_primary_contact` | Always `1` (true_false) |
| `family` | `parents_guardians[1].is_primary_contact` | Always `0` (true_false) |
| `user` | `family` | ID of newly created Family post — set on the WP user via `update_field('family', $family_id, 'user_' . $user_id)` |
| `application` | `payment_amount` | `$entry['payment_amount']` |
| `application` | `gravity_form_entry_id` | `$entry['id']` |
| `application` | `family` | ID of created or located Family post |
| `application` | `season` | Value from hidden Season ID field |
| `application` | `submission_date` | `date('Y-m-d')` at submission |
| `application` | `submitted_by` | `get_current_user_id()` |
| `application` | `new_returning` | `New` (New Family form) or `Returning` (Returning Family form) |
| `application` | `application_status` | `Completed` |
| `application` | `payment_status` | `Not Received` (default); `Paid` on Stripe confirmation |
| `athlete` | `family` | ID of created or located Family post |
| `athlete` | `account_status` | `Active` |
| `athlete` | `participation_type` | From nested form `participation_type` field |
| `enrollment` | `application` | ID of created Application post |
| `enrollment` | `family` | ID of created or located Family post |
| `enrollment` | `season` | Value from hidden Season ID field |
| `enrollment` | `athlete` | ID of created or located Athlete post |
| `enrollment` | `new_returning` | `New Athlete` or `Returning Athlete` |
| `enrollment` | `eligibility_confirmed` | `true` if all required eligibility checkboxes checked |
| `enrollment` | `physical_status` | `Not Received` |
| `enrollment` | `singlet_status` | `Not Needed` if singlet_requested = No; `Ordered` if Yes |

---

## Open Items

- [ ] **Stripe confirmation hook:** Confirm which hook updates `payment_status` to `Paid` — likely `gform_stripe_fulfillment` or `gform_stripe_after_payment_intent_succeeded`. Uncomment stub in `inc/gravity-helpers.php` once Stripe is connected on live domain.
- [ ] **Handbook URL:** Active season post `handbook` field is a placeholder. Update before opening registration.
- [ ] **Policy Compliance links:** Athlete eligibility checkbox references Eligibility, Code of Conduct, and Dress & Appearance Guidelines pages. Confirm URLs are current before go-live.
- [ ] **GW Read Only dependency:** Field 1 (Family Name) on Returning Family form and identity fields on Register Returning Athlete nested form require GW Read Only (GravityPerks) to enforce non-editability. Confirm plugin is installed and active.
- [x] **Confirmation page structure (Q12):** Resolved — Option A. Two child pages created: `/registration/confirmation/new-family/` and `/registration/confirmation/returning-family/`. GF confirmations redirect conditionally by payment method.
- [ ] **Additional Singlets (Returning):** Manual count field on RF Page 5 (Field 47) asks the parent to enter how many returning athletes selected a new singlet. Future improvement: derive this count from nested form entries rather than manual input.
- [ ] **User.family_id:** Not set at registration time. The TB family identifier (`TB-66281` style) is not auto-generated by the New Family hook. Decide whether to generate it at registration or leave as admin-populated.
