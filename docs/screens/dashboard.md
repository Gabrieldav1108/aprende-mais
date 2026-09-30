# Tela: Dashboard

## Rota e arquivos

| Item | Valor |
| --- | --- |
| URL / nome | `/dashboard` / `dashboard` |
| Rota | `Route::inertia('dashboard', 'dashboard')` em `routes/web.php` (sem controller) |
| Página | `resources/js/pages/dashboard.tsx` |
| Acesso | Usuário autenticado e verificado |

## Dados recebidos

Nenhum dado de domínio. Só as props compartilhadas de toda página (`auth.user`, `auth.roles`, `auth.permissions`).

## O que aparece

Uma grade de blocos com o padrão de placeholder do starter kit. **O primeiro bloco é um link para a listagem de turmas.** Os demais são apenas decorativos.

## Ações

Clicar no primeiro bloco leva a `/classroom`.

## Observações

- É uma tela provisória: não há indicadores, gráficos nem atalhos para cursos e períodos.
- O menu lateral (`components/app-sidebar.tsx`) é a navegação real: Dashboard, Cursos, Períodos letivos e Turmas.
- Os testes em `tests/Feature/DashboardTest.php` cobrem o redirecionamento de visitantes e o acesso autenticado.
