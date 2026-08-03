# Process Guide UI backup

Created: 2026-07-31 (before UI/UX upgrade)

## Contents

| File | Original location |
|------|-------------------|
| `index.blade.php` | `Resources/views/admin/process-guide/` |
| `partials/_flow.blade.php` | `Resources/views/admin/process-guide/partials/` |
| `partials/_scripts.blade.php` | `Resources/views/admin/process-guide/partials/` |
| `partials/_text-guide.blade.php` | `Resources/views/admin/process-guide/partials/` |
| `partials/_text-guide-steps.blade.php` | `Resources/views/admin/process-guide/partials/` |
| `support/LeadQualificationTextGuide.php` | `Modules/AdminModule/Support/` |
| `controller/ProcessGuideController.php` | `Http/Controllers/Web/Admin/` |
| `miro-board.json` | `public/assets/admin-module/process-guide/` |

## Restore

From the `panun-admin` project root:

```bash
BACKUP="Modules/AdminModule/Resources/views/admin/process-guide/_backup-pre-ui-upgrade-2026-07-31"
cp "$BACKUP/index.blade.php" Modules/AdminModule/Resources/views/admin/process-guide/
cp "$BACKUP/partials/"*.blade.php Modules/AdminModule/Resources/views/admin/process-guide/partials/
cp "$BACKUP/support/LeadQualificationTextGuide.php" Modules/AdminModule/Support/
cp "$BACKUP/controller/ProcessGuideController.php" Modules/AdminModule/Http/Controllers/Web/Admin/
cp "$BACKUP/miro-board.json" public/assets/admin-module/process-guide/
```

Then hard-refresh the browser (Cmd+Shift+R).
