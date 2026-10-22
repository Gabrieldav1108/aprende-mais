---
paths:
  - 'resources/js/**'
---

# Js

## Use HeroUI components for all frontend UI
All new frontend UI must be built with `@heroui/react` components (Table, Select, Input, Button, Label, Card, ListBox, etc.), not the shadcn primitives in `resources/js/components/ui/*`. The shadcn primitives are legacy and should be migrated away from over time, not extended.

Known trap: HeroUI's CSS (`@heroui/styles`, imported in `resources/css/app.css`) uses the raw `--muted` variable as a *text* color across many components (table headers, field placeholders, card descriptions, etc.). The shadcn theme in the same file redefines `--muted` as a *background tint* (the real muted text color is `--muted-foreground`). This collision makes HeroUI text render near-invisible (especially in dark mode) unless addressed. This is unresolved as of now — when styling a new HeroUI component, check contrast in both light and dark mode before considering it done.

## Create and edit every record in a modal
All registrations (create and edit) use the same modal pattern, as in users, students and teachers: a "Novo ..." button above the table, an "Editar" button per row, and one HeroUI Modal (components/<resource>/modal.tsx + form.tsx) used for both. The modal closes on success and stays open on validation errors. The edit data comes from the index props; there are no separate create/edit/show pages and no inline forms.

## Use AddressFields for every address form
Address inputs must use components/address-fields.tsx (not hand-written inputs). It fills street/neighborhood/city/state and the hidden IBGE codes from the CEP via GET cep.show (CepController -> App\Services\CepService -> BrasilAPI CEP v2, cached). It renders sibling fields sized for a 4-column grid and submits `address[...]`; backend rules live in PersonValidationRules.

## Use CpfCnpjField for CPF/CNPJ inputs
CPF/CNPJ inputs must use components/cpf-cnpj-field.tsx: it masks the document and, for a complete new CNPJ, calls GET cnpj.show (CnpjController -> App\Services\CnpjService -> BrasilAPI CNPJ v1, cached 24h) and passes the company to onCompanyFound. Fill the address by remounting AddressFields with a new `key` and the company address. Lookup hooks share hooks/use-lookup.ts.

## Regenerate Wayfinder with --with-form
The Vite plugin runs Wayfinder with `formVariants: true`, and many forms use `route.form()`. When regenerating by hand (e.g. after adding a route while Vite HMR is down), always run `php artisan wayfinder:generate --with-form`; without the flag the `.form` variants disappear and `tsc` fails across auth/settings pages.
