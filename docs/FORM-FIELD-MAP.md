# Trailblazers — GravityForms Field Map
**Version:** 2.1
**Season:** 2026 XC
**Status:** Ready to build

---

## Conventions

| Column | Meaning |
|---|---|
| **GF Type** | Gravity Forms field type |
| **Maps to** | CPT → ACF field name |
| **How set** | `user` = entered by submitter / `hidden` = invisible field / `hook` = set programmatically, not in form / `pre-pop` = pre-populated via GP Populate Anything |
| **Notes** | Conditional logic, GravityPerks dependencies, or implementation warnings |

---

## Registration Entry Point

A standard WordPress page (not a form) with two buttons or links:

- **New Family Registration** → links to the New Family form page
- **Returning Family Registration** → links to the Returning Family form page

Both destination forms require login. If a user is not logged in, GF's login requirement redirects to the WP login page before the form is displayed.

---

## Form 1: 2026 Registration — New Family

**Hook fires:** `gform_after_submission` for this form ID
**Creates:** Family post, Application post, Athlete posts (new), Enrollment posts

---

### Page 1 — Family Contact Info

| Field Label | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| Family Name | Text | `family` → `family_display_name` | user | e.g. "The Smiths" / "The Anderson Academy". Required. |
| *(Season ID)* | Hidden | `application` → `season` | hidden | `gform_field_value` reads `get_option('tb_active_season_id')`. Never visible to user. |
| *(Current User ID)* | Hidden | `family` → `account_user` | hidden | `gform_field_value` reads `get_current_user_id()`. Hook uses this to link Family to WP User. |
| **— Address —** | Section | | | |
| Street Address | Text | `family` → `street_address` | user | Required. |
| City | Text | `family` → `city` | user | Required. |
| State | Select | `family` → `state` | user | Required. Two-letter abbreviations. Default: SC. |
| **— Primary Contact —** | Section | | | |
| First Name | Text | `family` → `parents_guardians[0].guardian_first_name` | user | Required. |
| Last Name | Text | `family` → `parents_guardians[0].guardian_last_name` | user | Required. |
| Relationship | Select | `family` → `parents_guardians[0].guardian_relationship` | user | Choices: Father / Mother / Stepfather / Stepmother / Legal Guardian / Other. Required. |
| Email | Email | `family` → `parents_guardians[0].guardian_email` | user | Required. Should match WP account email. |
| Phone | Phone | `family` → `parents_guardians[0].guardian_phone` | user | Required. |
| Receive Email Notifications? | Radio | `family` → `parents_guardians[0].guardian_notifications` | user | Yes / No. Required. |
| **— Secondary Contact (Optional) —** | Section | | | Fields are optional |
| First Name | Text | `family` → `parents_guardians[1].guardian_first_name` | user | Optional. |
| Last Name | Text | `family` → `parents_guardians[1].guardian_last_name` | user | Optional. |
| Relationship | Select | `family` → `parents_guardians[1].guardian_relationship` | user | Same choices as primary. Optional. |
| Email | Email | `family` → `parents_guardians[1].guardian_email` | user | Optional. |
| Phone | Phone | `family` → `parents_guardians[1].guardian_phone` | user | Optional. |
| Receive Email Notifications? | Radio | `family` → `parents_guardians[1].guardian_notifications` | user | Yes / No. Optional. |

---

### Page 2 — Athletes

| Field Label | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| *(Instructional HTML)* | HTML | — | — | "Register each athlete below. Sibling Runners (grades 1–6) should also be registered here." |
| Register Athletes | GP Nested Form | See nested form map below | user | Nested form: "2026 Register Athlete". Min: 1. No enforced max. |

---

### Page 3 — Handbook

| Field Label | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| *(Season Handbook URL)* | Hidden | *(internal)* | hidden | `gform_field_value` reads `get_field('handbook', $season_id)['url']` from the active season post. Provides the URL for the HTML block below. Use `https://trailblazers.team` as placeholder until handbook is published. |
| *(Handbook HTML block)* | HTML | — | — | "Please review the 2026 XC Handbook before continuing." Displays a button/link using the URL from the hidden field above via merge tag: `<a href="{Field ID:value}" target="_blank">View the 2026 XC Handbook</a>` |
| I have read the handbook | Checkbox | *(internal — gate only)* | user | Single checkbox. Required. Label: "I have read and agree to the policies outlined in the 2026 XC Handbook." |

> **Implementation note:** The handbook URL hidden field is populated via `gform_field_value`. The HTML block uses a GF merge tag `{FIELD_ID:value}` to inject the URL into the anchor href. When the handbook is not yet published, the placeholder URL should point to the team homepage or a "coming soon" page — not a broken link.

---

### Page 4 — Waiver & Signature

| Field Label | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| *(Waiver HTML)* | HTML | — | — | Static waiver copy (see Waiver Text section below). No field. |
| Digital Signature | Text | `application` → `digital_signature` | user | Required. Parent types their full legal name. |
| Today's Date | Date | `application` → `submission_date` | user | Defaults to today. Required. |

---

### Page 5 — Payment

All fees for the 2026 XC season. Registration and singlet fees calculated from athlete count. Processing contribution is optional but required to make an explicit choice.

| Field Label | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| **— Registration Fee —** | Section | | | |
| *(Fee summary HTML)* | HTML | — | — | "The 2026 XC registration fee is $75 per athlete. A team singlet ($35) is required for all new athletes." |
| Athlete Count | Number | *(calculation only)* | calculated | `enableCalculation: true`. Formula: `{NESTED_FIELD_ID:count}`. Not stored on any CPT. Displayed for transparency. |
| Registration Subtotal | Number | *(display only)* | calculated | `enableCalculation: true`. Formula: `{ATHLETE_COUNT_FIELD_ID} * 75`. Display only — feeds the product field below. |
| **— Singlet Fee —** | Section | | | |
| *(Singlet HTML)* | HTML | — | — | "All new athletes are required to purchase a team singlet ($35). Returning runners with a valid singlet from a prior season are not required to purchase a new one." |
| Singlet Count | Number | *(calculation only)* | calculated | `enableCalculation: true`. Formula: same as Athlete Count — `{NESTED_FIELD_ID:count}`. All new athletes require a singlet. Displayed for transparency. |
| Singlet Subtotal | Number | *(display only)* | calculated | `enableCalculation: true`. Formula: `{SINGLET_COUNT_FIELD_ID} * 35`. Display only. |
| **— Total —** | Section | | | |
| Registration Total | Product | *(feeds order total)* | calculated | GF Product field. `enableCalculation: true`. Formula: `({ATHLETE_COUNT_FIELD_ID} * 75) + ({SINGLET_COUNT_FIELD_ID} * 35)`. This is the amount Stripe charges before the optional contribution. |
| **— Optional: Help Cover Processing Costs —** | Section | | | |
| Processing Contribution | Product (Radio Buttons) | *(feeds order total)* | user | GF Product field, Radio Buttons display. No pre-selected default. Choices: "No thanks" $0.00 / "Help a little" $3.00 / "Cover processing" $5.00 / "Pay it forward" $10.00. Required (explicit choice). GF Total sums this with Registration Total automatically. |
| **— Order Summary —** | Section | | | |
| Order Total | Total | `application` → `payment_amount` | calculated | GF Total field. Sums all product fields. Hook writes this value to Application at submission. |
| Payment Method | Radio | `application` → `payment_method` | user | Choices: Credit Card / Check / Cash. Required. |
| Credit Card | Stripe Credit Card | — | user | Conditional: visible only if Payment Method = Credit Card. Stripe charges the Order Total. |

> **Hook note:** `payment_amount` is written from `$entry['payment_amount']` (GF Order Total). `payment_status` defaults to `Not Received`. Stripe submissions update to `Paid` via Stripe confirmation hook. Check/Cash remain `Not Received` — updated manually by admin.

> **Singlet note:** Because all new athletes are required to purchase a singlet, the Singlet Count equals the Athlete Count for the New Family form. The two separate fields are kept for clarity and to make the math transparent to the parent.

---

## Form 2: 2026 Registration — Returning Family

**Hook fires:** `gform_after_submission` for this form ID
**Updates:** Family post (contact fields + address)
**Creates:** Application post, new Athlete posts (if any), Enrollment posts for all registered athletes

**Key difference from New Family:** Family post is located at hook time via `account_user = get_current_user_id()`. No Family post is created.

---

### Page 1 — Family Contact Info

All fields pre-populated via GP Populate Anything reading from the existing Family post.

| Field Label | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| Family Name | Text | `family` → `family_display_name` | pre-pop | Editable. Hook updates if changed. Required. |
| *(Season ID)* | Hidden | `application` → `season` | hidden | Same as New Family. |
| *(Current User ID)* | Hidden | `family` → `account_user` | hidden | Hook uses this to locate existing Family post. Not for creation. |
| **— Address —** | Section | | | Pre-populated from existing Family post |
| Street Address | Text | `family` → `street_address` | pre-pop | Editable. Required. |
| City | Text | `family` → `city` | pre-pop | Editable. Required. |
| State | Select | `family` → `state` | pre-pop | Editable. Required. |
| **— Primary Contact —** | Section | | | Pre-populated from `parents_guardians[0]` |
| First Name | Text | `family` → `parents_guardians[0].guardian_first_name` | pre-pop | Editable. Required. |
| Last Name | Text | `family` → `parents_guardians[0].guardian_last_name` | pre-pop | Editable. Required. |
| Relationship | Select | `family` → `parents_guardians[0].guardian_relationship` | pre-pop | Editable. Required. |
| Email | Email | `family` → `parents_guardians[0].guardian_email` | pre-pop | Editable. Required. |
| Phone | Phone | `family` → `parents_guardians[0].guardian_phone` | pre-pop | Editable. Required. |
| Receive Email Notifications? | Radio | `family` → `parents_guardians[0].guardian_notifications` | pre-pop | Editable. Required. |
| **— Secondary Contact (Optional) —** | Section | | | Pre-populated from `parents_guardians[1]` if exists |
| First Name | Text | `family` → `parents_guardians[1].guardian_first_name` | pre-pop | Editable. Optional. |
| Last Name | Text | `family` → `parents_guardians[1].guardian_last_name` | pre-pop | Editable. Optional. |
| Relationship | Select | `family` → `parents_guardians[1].guardian_relationship` | pre-pop | Editable. Optional. |
| Email | Email | `family` → `parents_guardians[1].guardian_email` | pre-pop | Editable. Optional. |
| Phone | Phone | `family` → `parents_guardians[1].guardian_phone` | pre-pop | Editable. Optional. |
| Receive Email Notifications? | Radio | `family` → `parents_guardians[1].guardian_notifications` | pre-pop | Editable. Optional. |

---

### Page 2 — Athletes

| Field Label | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| **— Returning Athletes —** | Section | | | |
| *(Instructional HTML)* | HTML | — | — | "Select each athlete from your family who is registering for 2026 XC." |
| Select Returning Athletes | Checkboxes | *(internal — athlete post IDs)* | user | GP Populate Anything populates choices from Athlete CPT posts where `family = current family post ID`. Each checked athlete = one Enrollment created by hook. Values are athlete post IDs. |
| Returning Athletes Registering | Number | *(payment calculation)* | user | Parent enters the count of returning athletes they checked above. Used on Page 5 for fee calculation. Required if any returning athletes are selected. |
| **— New Athletes (Optional) —** | Section | | | |
| *(Instructional HTML)* | HTML | — | — | "Adding a new athlete to your family? Register them below." |
| Register New Athletes | GP Nested Form | See nested form map below | user | Same nested form as New Family. Min: 0 — new athletes are optional for returning families. |

> **Hook behavior:** For each checked returning athlete ID, hook creates one Enrollment post (no new Athlete post). For each new athlete nested entry, hook creates one Athlete post then one Enrollment post.

> **Note on returning athlete count field:** A manual count field is used because GF calculation formulas cannot count checked checkbox values. The hook should cross-check this count against the actual number of athlete IDs submitted as a data integrity guard.

---

### Page 3 — Handbook

Identical to New Family form. Same fields, same `gform_field_value` population, same handbook placeholder logic.

---

### Page 4 — Waiver & Signature

Identical to New Family form. Same waiver HTML, same Digital Signature field, same Date field.

---

### Page 5 — Payment

Similar to New Family, but accounts for the mix of returning athletes (no singlet required) and new athletes (singlet required).

| Field Label | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| **— Registration Fee —** | Section | | | |
| *(Fee summary HTML)* | HTML | — | — | "The 2026 XC registration fee is $75 per athlete. New athletes are required to purchase a team singlet ($35). Returning athletes with a valid singlet are not required to purchase a new one." |
| New Athletes | Number | *(calculation only)* | calculated | `enableCalculation: true`. Formula: `{NESTED_FIELD_ID:count}`. Count of new athletes from nested form. |
| Total Athletes | Number | *(calculation only)* | calculated | `enableCalculation: true`. Formula: `{RETURNING_COUNT_FIELD_ID} + {NEW_ATHLETES_FIELD_ID}`. Sum of returning + new. |
| Registration Subtotal | Number | *(display only)* | calculated | Formula: `{TOTAL_ATHLETES_FIELD_ID} * 75`. |
| **— Singlet Fee —** | Section | | | |
| *(Singlet HTML)* | HTML | — | — | "New athletes require a team singlet ($35). Returning athletes who need a replacement singlet may request one below." |
| Required Singlets (New Athletes) | Number | *(calculation only)* | calculated | Formula: `{NEW_ATHLETES_FIELD_ID}`. Mirrors new athlete count — all new athletes require a singlet. |
| Additional Singlets (Returning Athletes) | Number | `enrollment` → (admin follow-up) | user | Parent enters the number of returning athletes who need a new/replacement singlet. Default: 0. Min: 0. |
| Singlet Subtotal | Number | *(display only)* | calculated | Formula: `({REQUIRED_SINGLETS_FIELD_ID} + {ADDITIONAL_SINGLETS_FIELD_ID}) * 35`. |
| **— Total —** | Section | | | |
| Registration Total | Product | *(feeds order total)* | calculated | GF Product. `enableCalculation: true`. Formula: `({TOTAL_ATHLETES_FIELD_ID} * 75) + (({REQUIRED_SINGLETS_FIELD_ID} + {ADDITIONAL_SINGLETS_FIELD_ID}) * 35)`. |
| **— Optional: Help Cover Processing Costs —** | Section | | | |
| Processing Contribution | Product (Radio Buttons) | *(feeds order total)* | user | Identical to New Family. No pre-selected default. Choices: "No thanks" $0.00 / "Help a little" $3.00 / "Cover processing" $5.00 / "Pay it forward" $10.00. Required. |
| **— Order Summary —** | Section | | | |
| Order Total | Total | `application` → `payment_amount` | calculated | GF Total field. Sums all product fields. Hook writes to Application. |
| Payment Method | Radio | `application` → `payment_method` | user | Choices: Credit Card / Check / Cash. Required. |
| Credit Card | Stripe Credit Card | — | user | Conditional: visible only if Payment Method = Credit Card. |

---

## Nested Form: 2026 Register Athlete

**Used by:** Both New Family (Page 2) and Returning Family (Page 2, new athletes only)
**Creates (via parent form hook):** One Athlete post per entry + one Enrollment post per entry

| Field Label | GF Type | Maps to | How set | Notes |
|---|---|---|---|---|
| Participation Type | Radio | `enrollment` → `participation_type` AND `athlete` → `participation_type` | user | **Drives all conditional logic below.** Choices: Athlete / Sibling Runner. Required. |
| **— Identity —** | Section | | | |
| First Name | Text | `athlete` → `first_name` | user | Required. |
| Last Name | Text | `athlete` → `last_name` | user | Required. |
| Preferred Name / Nickname | Text | `athlete` → `preferred_name` | user | Optional. |
| Gender | Radio | `athlete` → `gender` | user | Choices: Male / Female. Required. |
| Date of Birth | Date | `athlete` → `dob` | user | Required. |
| Grade | Select | `enrollment` → `grade` | user | Full range 1–12. Required. |
| **— Eligibility — Athlete Block —** | Section | | | Conditional: visible if Participation Type = Athlete |
| Residency | Checkbox | `enrollment` → `eligibility_confirmed` | user | Single checkbox. Required if Athlete. Label: "We reside in York County, SC or a neighboring county." |
| Homeschooled | Checkbox | `enrollment` → `eligibility_confirmed` | user | Single checkbox. Required if Athlete. Label: "{First Name} is homeschooled per SC Law, is schooled at home through SC Virtual Schools, or homeschooled per NC Law for those residing in Mecklenburg County." Uses GP merge tag for athlete first name. |
| Academic Eligibility | Checkbox | `enrollment` → `eligibility_confirmed` | user | Single checkbox. Required if Athlete. Label: "{First Name} maintained a minimum 2.5 GPA in the previous semester and is academically eligible to participate." |
| Running Commitment | Checkbox | `enrollment` → `eligibility_confirmed` | user | Single checkbox. Required if Athlete. Label: "We commit to three or more days of running per week, at least two of those days with the team. We understand this is for the safety of our athletes." |
| Policy Compliance | Checkbox | `enrollment` → `eligibility_confirmed` | user | Single checkbox. Required if Athlete. Label: "WE HAVE READ AND AGREE TO THE POLICIES OUTLINED IN THE HANDBOOK." Include links to Eligibility, Code of Conduct, and Dress & Appearance Guidelines pages when available. |
| **— Eligibility — Sibling Runner Block —** | Section | | | Conditional: visible if Participation Type = Sibling Runner |
| Parent Supervision Acknowledgment | Checkbox | `enrollment` → `eligibility_confirmed` | user | Single checkbox. Required if Sibling Runner. Label: "I understand {First Name} is to be under my supervision at all times and that coaches are not responsible for the ultimate safety of my youth runner." |
| Policy Compliance | Checkbox | `enrollment` → `eligibility_confirmed` | user | Single checkbox. Required if Sibling Runner. Lighter copy than Athlete version. |
| **— Uniform —** | Section | | | |
| Requesting New Singlet? | Radio | `enrollment` → `singlet_requested` | user | Yes / No. **Default: Yes.** Required. New athletes are required to purchase a singlet — default Yes reflects this. Exceptions (e.g. borrowing a sibling's singlet) can be handled by the parent selecting No. Add instructional copy: "New athletes are required to purchase a team singlet ($35). Select No only if you have received prior approval for an exception." |
| Singlet Sizing Group | Select | `enrollment` → `singlet_sizing_group` | user | Conditional: visible if Singlet Requested = Yes. Choices: Youth / Men's / Women's. |
| Singlet Size | Select | `enrollment` → `singlet_size` | user | Conditional: visible if Singlet Requested = Yes. Choices: Youth S / Youth M / Youth L / Youth XL / Men's S / Men's M / Men's L / Men's XL / Women's S / Women's M / Women's L / Women's XL. |

> **Shorts note:** Shorts are not sold through registration for XC. The `shorts_requested`, `shorts_sizing_group`, and `shorts_size` fields exist on the Enrollment CPT but are not collected on this form. Those fields may be used in future seasons or for other sports.

---

## Waiver Text (Page 4 — both forms)

The following HTML is placed in the static HTML field on Page 4:

```html
<p>As a parent or legal guardian of the above named student-athlete(s). I give permission for his/her participation in athletic events and the physical evaluation for that participation. I understand that this is simply a screening evaluation and not a substitute for regular health care. I also grant permission for treatment deemed necessary for a condition arising during participation of these events, including medical or surgical treatment that is recommended by a medical doctor. I grant permission to nurses, trainers and coaches as well as physicians or those under their direction who are part of athletic injury prevention and treatment, to have access to necessary medical information. I know that the risk of injury to my child/ward comes with participation in sports and during travel to and from play and practice. I have had the opportunity to understand the risk of injury during participation in sports through meetings, written information or by some other means. My signature indicates that to the best of my knowledge, my answers to the above questions are complete and correct. I understand that the data acquired during these evaluations may be used for research purposes.</p>
```

> **Note:** Review this text before going live to confirm it is still accurate for 2026 XC. It is carried forward verbatim from the 2025 registration form.

---

## Hook-Set Fields — Not in Any Form

These fields are written programmatically at submission time and never appear as form fields.

| CPT | Field | Value set by hook |
|---|---|---|
| `family` | `account_user` | `get_current_user_id()` |
| `family` | `family_status` | `Active` |
| `application` | `payment_amount` | `$entry['payment_amount']` — GF Order Total |
| `application` | `gravity_form_entry_id` | `$entry['id']` — audit trail to source entry |
| `application` | `family` | ID of created or located Family post |
| `application` | `season` | Value from hidden Season ID field |
| `application` | `submission_date` | `date('Y-m-d')` at time of submission |
| `application` | `submitted_by` | `get_current_user_id()` |
| `application` | `new_returning` | `New` (New Family form) or `Returning` (Returning Family form) |
| `application` | `application_status` | `Completed` |
| `application` | `payment_status` | `Not Received` (default); updated to `Paid` on Stripe confirmation |
| `athlete` | `family` | ID of created or located Family post |
| `athlete` | `account_status` | `Active` |
| `athlete` | `participation_type` | From nested form `participation_type` field |
| `enrollment` | `application` | ID of created Application post |
| `enrollment` | `family` | ID of created or located Family post |
| `enrollment` | `season` | Value from hidden Season ID field |
| `enrollment` | `athlete` | ID of created or located Athlete post |
| `enrollment` | `new_returning` | `New Athlete` (new entry) or `Returning Athlete` (from returning athlete checkbox) |
| `enrollment` | `eligibility_confirmed` | `true` if all required eligibility checkboxes were checked |
| `enrollment` | `physical_status` | `Not Received` |
| `enrollment` | `singlet_status` | `Not Needed` if singlet_requested = No; `Ordered` if singlet_requested = Yes |

---

## Sibling Runner Eligibility Text

For reference when building the eligibility section of the nested form:

**Grade range:** 1–6 (per v1.7 convention; confirm with program director if this has changed).

**Instructional HTML to display above Sibling Runner eligibility checkboxes:**
> "Youth runners must be in grades 1–6 and accompanied by their parents at all times during practice. They may run only under their parent's supervision."

**Parent Supervision checkbox label:**
> "I understand [First Name] is to be under my supervision at all times and that coaches are not responsible for the ultimate safety of my youth runner."

---

## Open Items

- [ ] **Returning Family athlete count field:** The "Returning Athletes Registering" number field on Page 2 requires the parent to manually count their checked athletes. Minor UX friction. Future improvement: use GP Populate Anything or JS to auto-populate from checked values.
- [ ] **Stripe confirmation hook:** Confirm which GF Stripe add-on hook updates `payment_status` to `Paid` — likely `gform_stripe_fulfillment` or `gform_stripe_after_payment_intent_succeeded`. Wire in `inc/gravity-helpers.php`.
- [ ] **Handbook URL:** Active season post `handbook` field is a placeholder until the 2026 XC handbook is published. Update the season post before opening registration.
- [ ] **Policy Compliance links:** The Athlete eligibility "Policy Compliance" checkbox references Eligibility, Code of Conduct, and Dress & Appearance Guidelines pages. Confirm these URLs are current before go-live.
