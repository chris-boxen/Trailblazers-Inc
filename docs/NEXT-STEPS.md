# NEXT STEPS

## Now
- Decide on next phase — options below

## JS Filtering (data attributes are ready on all archive templates)
- Add filter controls + JS to archive-athlete.php
- Add filter controls + JS to archive-athletic_meet.php
- Add filter controls + JS to archive-athletic_record.php (including current/all toggle)
- Add filter controls + JS to taxonomy-sport.php athlete table
- Populate `inc/enqueue.php` with the filtering script

## GravityForms Integration
- Design application form flow (family → athletes → payment)
- Map form fields to CPT creation via gform_after_submission hooks
- Add logic to inc/gravity-helpers.php

## Later
- Add coach → season backreference on single-coach.php
- Revisit payment abstraction if workflow becomes more complex
- Add stronger review workflows for physicals and approvals
- Revisit theme folder naming
- Add `template-parts/` structure when templates grow complex enough to warrant it
- Populate remaining stub inc/ files as functionality is needed

## Blocked / Waiting
- Final decision on how much event metadata is stored directly on results vs derived from athletic event