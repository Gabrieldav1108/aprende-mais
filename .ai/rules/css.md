---
paths:
  - resources/css/app.css
---

# Css

## Fix HeroUI token collisions in app.css, unlayered
When a HeroUI component reads a token the shadcn theme redefines as a background tint (`--muted` as text, `--accent` as an indicator), override it in a small unlayered rule in resources/css/app.css targeting the HeroUI class (e.g. `.table-root .table__column`, `.tabs__tab`, `.tabs--secondary .tabs__indicator`) with `--muted-foreground` / `--foreground`. HeroUI ships in `@layer components`, so the unlayered rule wins over its state selectors too.
