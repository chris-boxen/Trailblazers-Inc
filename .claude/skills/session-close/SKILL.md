---
name: session-close
description: Close a work session by updating the repo docs (CHANGELOG, STATE, NEXT-STEPS, and others when relevant) then committing and pushing the session's work.
disable-model-invocation: true
---

Close out this session. Work from what actually changed, not from memory of
the conversation alone.

1. Establish what changed.
   - Run `git status` and `git diff` (and `git diff --stat`) to see the real
     file changes since the last commit.
   - Combine that with the decisions made in this session. Separate what was
     built and verified from what was only discussed or proposed.

2. Read the current docs before editing them: `docs/CHANGELOG.md`,
   `docs/STATE.md`, `docs/NEXT-STEPS.md`, and `docs/OPEN-QUESTIONS.md`.

3. Propose the doc updates as targeted diffs. Do not write yet.
   - `CHANGELOG.md`: a dated entry (today's date, `## YYYY-MM-DD`) at the top,
     matching the existing style. Say what changed and why.
   - `STATE.md`: update only lines whose truth changed. Mark verified items
     ✅ and known issues ⚠️, following the file's existing conventions.
   - `NEXT-STEPS.md`: check off completed items, add new follow-ups, and move
     finished sections to "Completed" with an "As of" date.
   - `OPEN-QUESTIONS.md`: add questions raised; resolve answered ones with the
     decision.
   - Also propose updates to `SCHEMA.md` (ACF fields changed),
     `FORM-FIELD-MAP.md` (GF fields or logic changed), `TEMPLATES.md`
     (templates or public anchors changed), or `ARCHITECTURE.md` (data model
     changed) when this session touched those areas.
   - Flag anything that must happen in production by hand: DB changes, ACF
     sync, GF form edits, options-page values. Git does not carry these.

4. Wait for Chris to confirm, then apply the approved edits.

5. Commit and push. Group the session's changes into logical commits (code
   separate from docs when they are independent), each with a short title
   (under ~70 characters) and a description that explains the why. Leave
   unrelated uncommitted files out. Push to `origin/main` and report the
   commit hashes.
