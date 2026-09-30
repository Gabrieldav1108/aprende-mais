# Aprende Mais — Estrutura do projeto

Sistema de gestão acadêmica para faculdades, com várias escolas (instituições), cursos, períodos letivos semestrais, turmas, alunos e matérias.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Backend | PHP 8.4, Laravel 13 |
| Frontend | Inertia.js v3 + React + TypeScript, Vite |
| UI | HeroUI v3 (`@heroui/react`) e Tailwind; primitivos shadcn em `components/ui` são legados |
| Rotas no frontend | Laravel Wayfinder (funções tipadas geradas a partir das rotas) |
| Banco | MySQL 8.4 (testes usam SQLite em memória) |
| Autenticação | Laravel Fortify (login, 2FA, passkeys) |
| Autorização | spatie/laravel-permission + Policies |
| Auditoria | owen-it/laravel-auditing |
| Testes | Pest 5 |
| Qualidade | Pint, Larastan (`phpstan.neon`), ESLint, Prettier |
| Ambiente | Herd ou Docker (`docker-compose.yml`, pasta `docker/`) |

## Modelo de domínio

```
School (escola)
 ├── Course (curso)                  código único por escola
 │    ├── Subject (matéria)          catálogo do curso; código único por curso
 │    └── Classroom (turma)          pertence a um curso e a um período
 └── AcademicTerm (período letivo)   ex.: 2026/1; único por escola/ano/semestre

Classroom
 ├── ClassroomSubject   matéria ofertada na turma + professor (Teacher)
 ├── ClassroomStudent   matrícula do aluno (Student) na turma
 └── homeroom_teacher   professor regente (opcional)

ClassroomSubject ── Grade, Attendence   (notas e frequência; ainda não ligadas à matrícula)
User ── Teacher | Student               um usuário pode ter perfil de professor ou aluno
```

Principais atributos da turma: `name`, `code`, `term_number` (semestre do curso), `shift`, `status`, `capacity`, `room`, `homeroom_teacher_id`.

Todas as tabelas de domínio usam `SoftDeletes` e são auditadas.

### Enums (`app/Enums`)

`Shift`, `ClassroomStatus`, `CourseLevel`, `CourseStatus`, `TermStatus`, `EnrollmentStatus`. Cada um tem `label()` (rótulo em português) e `options()` (mapa valor → rótulo enviado ao frontend).

## Isolamento por escola

- `app/Concerns/BelongsToSchool.php`: trait que aplica `SchoolScope` e preenche `school_id` ao criar.
- `app/Models/Scopes/SchoolScope.php`: filtra pelo `school_id` do usuário autenticado. Usuário **sem escola** não é filtrado.
- Aplicado em `Course`, `AcademicTerm`, `Subject`, `Classroom`, `Student` e `Teacher`.
- `users.school_id` define a escola do usuário. A turma herda a escola do curso.
- Usuário sem escola não consegue criar curso nem período (o `school_id` fica nulo).

## Estrutura do backend

```
app/
  Actions/Fortify/     ações de autenticação (cadastro, redefinição de senha)
  Concerns/            traits: BelongsToSchool, regras de validação compartilhadas
  Enums/               enums de status, turno, nível
  Exceptions/          RepositoryException
  Http/
    Controllers/       um controller por recurso
    Requests/          Form Requests (autorização + validação)
  Models/              Eloquent + Scopes/
  Policies/            autorização por permissão
  Providers/           AppServiceProvider (Gate::before libera admin)
  Repositories/        acesso a dados e regras de negócio
database/
  factories/ migrations/ seeders/
routes/
  web.php  settings.php
tests/
  Feature/  Unit/  Pest.php
```

### Fluxo de uma requisição

`Rota → Form Request (autoriza e valida) → Controller → Repository → Model`

- O **Form Request** chama a Policy e valida. As regras compartilhadas entre criar e editar ficam em traits (`ClassroomValidationRules`, `CourseValidationRules`, `AcademicTermValidationRules`); `SchoolScopedRules` restringe `exists` à escola do usuário.
- O **Controller** é fino: chama o repositório, define um toast (`Inertia::flash('toast', ...)`) e redireciona ou renderiza uma página Inertia.
- O **Repository** faz try/catch de qualquer erro e relança `RepositoryException`; o controller reporta e mostra um toast de erro amigável. As regras de negócio vivem aqui:
  - `ClassroomSubjectRepository::attachToClassroom` só aceita matéria do mesmo curso da turma.
  - `ClassroomRepository::enrollStudent` respeita a capacidade da turma, dentro de transação.
  - `ClassroomRepository::create` deriva a escola do curso.

### Rotas (`routes/web.php`)

Todas dentro de `auth` + `verified`.

| Nome | Método e caminho |
| --- | --- |
| `course`, `course.store`, `course.show`, `course.update` | `courses`, `courses/{course}` |
| `course.subjects.store` | `POST courses/{course}/subjects` |
| `academic-term`, `.store`, `.update` | `academic-terms`, `academic-terms/{academic_term}` |
| `classroom`, `.store`, `.show`, `.update` | `classroom`, `classroom/{classroom}` |
| `classroom.subjects.store`, `.destroy` | `classroom/{classroom}/subjects[/{classroomSubject}]` |

## Permissões

Definidas em `database/seeders/RolesAndPermissionsSeeder.php`:

`classrooms.create|update|delete`, `subjects.create|delete`, `courses.create|update|delete`, `terms.create|update`, `users.manage`.

Papéis: `admin`, `teacher`, `student`. O `admin` passa por qualquer verificação (`Gate::before` em `AppServiceProvider`). **Nenhuma permissão é atribuída ainda a professor e aluno.** As Policies apenas verificam a permissão, sem checar propriedade do registro.

O frontend recebe `auth.roles` e `auth.permissions` e usa o hook `useCan()` para exibir ou ocultar formulários.

## Estrutura do frontend

```
resources/js/
  app.tsx              cria o app Inertia e escolhe o layout pelo nome da página
  pages/
    classroom/         index.tsx, show.tsx
    course/            index.tsx, show.tsx
    academic-term/     index.tsx
    auth/ settings/    telas do starter kit
    dashboard.tsx  welcome.tsx
  components/
    classroom/ course/ academic-term/   formulários e tabelas de cada recurso
    form-select.tsx    wrapper do Select do HeroUI (opções vindas de enum ou lista)
    input-error.tsx    mensagem de erro de campo
    ui/                primitivos shadcn (legado; não estender)
  layouts/             app, auth, settings
  hooks/               use-can, use-appearance, use-flash-toast, ...
  routes/ actions/ wayfinder/   gerados pelo Wayfinder (não versionados)
resources/views/app.blade.php   única view Blade; só monta o container do Inertia
```

Convenções:
- Páginas são componentes React em `pages/`. O controller usa `Inertia::render('pasta/pagina', props)`. Não há views Blade de tela.
- Formulários usam o `<Form action={rota.store()}>` do Inertia com HeroUI; os erros vêm em `errors` e são exibidos com `InputError`.
- Tabelas usam `Table` do HeroUI com `renderEmptyState` retornando `EmptyState` direto.
- Os rótulos de enums vêm do backend (`Enum::options()`) como props.
- Ao mexer nas rotas, rode `php artisan wayfinder:generate --with-form`.
- Toda UI nova usa HeroUI (regra em `.ai/rules/js.md`). Atenção ao contraste: o CSS do HeroUI usa `--muted` como cor de texto, mas o tema shadcn o define como fundo; use `text-muted-foreground`.

## Comandos úteis

```bash
composer run dev                 # servidor, fila e Vite
php artisan migrate:fresh --seed # recria o banco com os usuários de exemplo
php artisan test --compact       # todos os testes
vendor/bin/pint --dirty --format agent
npx tsc --noEmit && npx eslint resources/js
```

Usuários de exemplo (`DefaultUsersSeeder`, senha `password`): `gabriel.admin@`, `gabriel.professor@`, `gabriel.aluno@` e os equivalentes `arthur.*`, todos `@aprendemais.com` e vinculados à escola "Aprende Mais".

## Testes

Pest, estilo função (`test('...', function () {})`), `RefreshDatabase` em `tests/Feature`. O helper `classroomPayload()` em `tests/Pest.php` monta uma turma válida com curso e período da mesma escola. Cobertura principal: `ClassroomTest`, `ClassroomAuthorizationTest`, `CourseTest`, `AcademicTermTest`, `SchoolScopeTest`, seeders.

## Pendências conhecidas

- Notas (`Grade`) e frequência (`Attendence`) ainda ligam ao aluno e à matéria da turma, não à matrícula.
- Não há tela nem rota de matrícula de alunos (só o método `enrollStudent`).
- Permissões de professor e aluno não estão atribuídas.
- Usuário sem escola não cria curso ou período; falta um seletor de escola.
- O select de professor regente não permite remover o regente depois de escolhido.
- Ao editar turma de um período encerrado, o select de período não exibe o valor atual.
- Os rótulos dos enums estão duplicados só no backend; não há enums no TypeScript.
