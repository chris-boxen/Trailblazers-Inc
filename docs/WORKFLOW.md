# WORKFLOW

## Development environment
- Local for WordPress development
- Dropbox for external backup
- GitHub for theme version control
- ACF Local JSON for schema versioning

## Repo scope
Git tracks the theme only, not the entire WordPress site.

## ACF JSON workflow
### When editing ACF in admin
1. Edit field groups / CPTs / taxonomies in ACF
2. Save
3. Confirm updated JSON appears in `acf-json/`
4. Commit those JSON changes to Git

### When switching branches
Because the database does not branch:
- treat JSON as the source of truth
- after switching branches, use ACF Sync if needed to realign DB and files

## Git workflow
- `main` = stable
- use short-lived feature branches for meaningful/risky changes
- avoid too many concurrent schema branches
- prefer one schema-changing branch at a time

## Documentation workflow
After meaningful work:
- update `STATE.md`
- update `NEXT-STEPS.md`
- update `CHANGELOG.md`
- update `OPEN-QUESTIONS.md` if needed