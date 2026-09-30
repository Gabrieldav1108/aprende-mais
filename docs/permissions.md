# Permissões de acesso

O acesso é controlado em quatro camadas independentes. Uma tela só funciona quando todas permitem.

## 1. Autenticação

Todas as rotas do sistema ficam em `Route::middleware(['auth', 'verified'])` (`routes/web.php`). Visitante é redirecionado para o login; usuário com e-mail não verificado não acessa as telas. Login, 2FA e passkeys são do Laravel Fortify.

## 2. Papéis e permissões (spatie/laravel-permission)

Criados em `database/seeders/RolesAndPermissionsSeeder.php`.

**Papéis:** `admin`, `teacher`, `student`.

**Permissões:**

| Permissão | Ação liberada |
| --- | --- |
| `courses.create` | Criar curso |
| `courses.update` | Editar curso |
| `courses.delete` | Excluir curso (Policy pronta, ainda sem rota) |
| `terms.create` | Criar período letivo |
| `terms.update` | Editar período letivo (rota pronta, ainda sem botão na tela) |
| `classrooms.create` | Criar turma |
| `classrooms.update` | Editar turma |
| `classrooms.delete` | Excluir turma (Policy pronta, ainda sem rota) |
| `subjects.create` | Adicionar matéria ao catálogo do curso **e** ofertar matéria em uma turma |
| `subjects.delete` | Remover matéria de uma turma |
| `users.manage` | Definida, ainda não usada |

**Situação atual:** o seeder cria os papéis e as permissões, mas **não liga nenhuma permissão a nenhum papel**. Na prática:

- `admin` faz tudo (veja o item 3).
- `teacher` e `student` só visualizam. Para liberar uma ação a alguém, é preciso conceder a permissão (veja "Como conceder acesso").

## 3. Policies e o bypass do admin

`AppServiceProvider::configureAuthorization()` registra:

```php
Gate::before(fn (User $user, string $ability): ?bool => $user->hasRole('admin') ? true : null);
```

Quem tem o papel `admin` passa em toda verificação, e as Policies nem chegam a ser consultadas. Para os demais, cada Policy apenas confere a permissão:

| Policy | Método | Verifica |
| --- | --- | --- |
| `CoursePolicy` | `create`, `update`, `delete` | `courses.*` |
| `AcademicTermPolicy` | `create`, `update` | `terms.*` |
| `ClassroomPolicy` | `create`, `update`, `delete` | `classrooms.*` |
| `ClassroomSubjectPolicy` | `create`, `delete` | `subjects.create`, `subjects.delete` |
| `SubjectPolicy` | `create`, `delete` | `subjects.create`, `subjects.delete` |

As Policies não recebem o registro e não verificam propriedade (por exemplo, "professor só edita a própria turma"). A checagem é só por permissão.

Onde a Policy é chamada:

| Ação | Onde |
| --- | --- |
| Criar/editar curso | `authorize()` de `StoreCourseRequest` / `UpdateCourseRequest` |
| Criar/editar período | `authorize()` de `StoreAcademicTermRequest` / `UpdateAcademicTermRequest` |
| Criar/editar turma | `authorize()` de `StoreClassroomRequest` / `UpdateClassroomRequest` |
| Adicionar matéria ao curso | `authorize()` de `StoreSubjectRequest` |
| Ofertar matéria na turma | `authorize()` de `StoreClassroomSubjectRequest` |
| Remover matéria da turma | `$this->authorize('delete', $classroomSubject)` em `ClassroomSubjectController::destroy` |

Sem permissão, a resposta é **403**.

## 4. Isolamento por escola

Independente das permissões, os dados são filtrados pela escola do usuário (`users.school_id`):

- Usuário **com escola** só enxerga e cria registros da própria escola. Abrir por URL um registro de outra escola retorna **404**. Os campos de curso, período e professor rejeitam ids de outra escola.
- Usuário **sem escola** não é filtrado e enxerga todas as escolas. Ele ainda não consegue criar curso ou período (o `school_id` fica nulo).

Detalhes em [structure.md](structure.md#isolamento-por-escola).

## Visualização

As telas de listagem e de detalhe (GET) **não exigem permissão**: qualquer usuário autenticado e verificado as abre, dentro do seu escopo de escola. As permissões controlam apenas as ações de escrita.

## No frontend

`HandleInertiaRequests` compartilha `auth.roles` e `auth.permissions` em toda página. Os hooks em `resources/js/hooks/use-can.ts` usam esses dados:

- `useCan('courses.create')`: `true` para admin ou para quem tem a permissão.
- `useHasRole('teacher')`: verifica o papel.

Eles só **escondem** formulários e botões. A segurança real está no servidor (itens 1 a 4), então um POST manual sem permissão continua retornando 403.

## Matriz de acesso hoje

| Ação | Permissão | Admin | Professor | Aluno |
| --- | --- | :-: | :-: | :-: |
| Ver cursos, períodos, turmas | (nenhuma) | sim | sim | sim |
| Criar / editar curso | `courses.create` / `courses.update` | sim | não | não |
| Adicionar matéria ao curso | `subjects.create` | sim | não | não |
| Criar / editar período | `terms.create` / `terms.update` | sim | não | não |
| Criar / editar turma | `classrooms.create` / `classrooms.update` | sim | não | não |
| Ofertar matéria na turma | `subjects.create` | sim | não | não |
| Remover matéria da turma | `subjects.delete` | sim | não | não |

"Não" significa que a permissão não foi concedida ainda; ela pode ser concedida.

## Como conceder acesso

Direto a um usuário:

```php
$user->givePermissionTo('classrooms.create');
```

A um papel (vale para todos que o têm). O ponto natural é o `RolesAndPermissionsSeeder`:

```php
Role::findByName('teacher')->givePermissionTo(['classrooms.update', 'subjects.create']);
```

Depois de alterar permissões em produção, rode `php artisan permission:cache-reset`.

## Como adicionar uma nova permissão

1. Inclua o nome em `RolesAndPermissionsSeeder::PERMISSIONS` e rode o seeder.
2. Crie ou atualize a Policy com `$user->can('recurso.acao')`.
3. Chame a Policy no `authorize()` do Form Request (ou com `$this->authorize()` no controller).
4. No React, esconda o elemento com `useCan('recurso.acao')`.
5. Cubra com teste: um usuário sem a permissão recebe 403 e outro com ela é bem-sucedido. Veja `tests/Feature/ClassroomAuthorizationTest.php`.
6. Atualize a lista de permissões no `RolesAndPermissionsSeederTest`, que confere a lista exata.

## Usuários de exemplo

`DefaultUsersSeeder` cria, todos com senha `password` e na escola "Aprende Mais": `gabriel.admin@`, `gabriel.professor@`, `gabriel.aluno@` e os equivalentes `arthur.*`, todos `@aprendemais.com`. Só os administradores conseguem executar ações de escrita, pela razão descrita acima.
