---
paths:
  - 'resources/js/**'
---

# Js

## Use HeroUI components for all frontend UI
All new frontend UI must be built with `@heroui/react` components (Table, Select, Input, Button, Label, Card, ListBox, etc.), not the shadcn primitives in `resources/js/components/ui/*`. The shadcn primitives are legacy and should be migrated away from over time, not extended.

Known trap: HeroUI's CSS (`@heroui/styles`, imported in `resources/css/app.css`) uses the raw `--muted` variable as a *text* color across many components (table headers, field placeholders, card descriptions, etc.). The shadcn theme in the same file redefines `--muted` as a *background tint* (the real muted text color is `--muted-foreground`). This collision makes HeroUI text render near-invisible (especially in dark mode) unless addressed. This is unresolved as of now — when styling a new HeroUI component, check contrast in both light and dark mode before considering it done.
