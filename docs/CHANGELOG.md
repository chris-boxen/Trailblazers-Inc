# CHANGELOG

## 2026-03
### Established initial content architecture
Defined the core CPT model for Family, Athlete, Season, Application, Enrollment, Physical, Result, and Record.

### Moved athlete-season participation into Enrollment
Removed athlete roster duplication from Season and made Enrollment the athlete-season operational hub.

### Removed mirrored Family → Athletes relationship
Kept Athlete → Family as the primary relationship direction to avoid duplicate maintenance.

### Converted sport into a taxonomy
Chose Sport as a taxonomy rather than only a select field to support filtering across multiple content types.

### Separated Physical from Enrollment
Kept physicals as independent records because a single physical may span more than one season.

### Added ACF Local JSON package
Imported CPTs, taxonomy, and field groups into ACF and confirmed local JSON saving is working.

### Archived original imported JSON files
Moved original imported JSON into `acf-json/_archived/2026-03-21-initial-import` and allowed ACF to own active canonical filenames going forward.