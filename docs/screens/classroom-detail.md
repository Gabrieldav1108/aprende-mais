# Tela: Turmas — detalhe

## Rota e arquivos

| Item | Valor |
| --- | --- |
| URL / nome | `GET /classroom/{classroom}` / `classroom.show` |
| Controller | `ClassroomController::show` |
| Página | `resources/js/pages/classroom/show.tsx` |
| Componentes | `components/classroom/form.tsx`, `subject-form.tsx`, `subjects-table.tsx` |
| Edição | `PUT /classroom/{classroom}` / `classroom.update` (`UpdateClassroomRequest`) |
| Ofertar matéria | `POST /classroom/{classroom}/subjects` / `classroom.subjects.store` (`StoreClassroomSubjectRequest`) |
| Remover matéria | `DELETE /classroom/{classroom}/subjects/{classroomSubject}` / `classroom.subjects.destroy` |

## Dados recebidos

| Prop | Conteúdo |
| --- | --- |
| `classroom` | A turma com curso, período, professor regente e `classroom_students_count` |
| `classroomSubjects` | Matérias ofertadas, cada uma com a matéria e o professor |
| `courseSubjects` | Catálogo do **curso da turma**, para o select de matéria |
| `courses`, `terms`, `teachers`, `shifts`, `statuses` | Opções dos formulários |

Uma turma de outra escola retorna 404.

## O que aparece

1. **Resumo** em uma linha: curso · período · semestre · `alunos/vagas`.
2. **Formulário "Editar turma"** preenchido, com `classrooms.update`.
3. **Formulário "Ofertar matéria"**, com `subjects.create`.
4. **Tabela de matérias da turma** com Matéria, Código, Carga horária, Professor e o botão "Remover" (com `subjects.delete`).
5. **Estado vazio:** "Nenhuma matéria ofertada nesta turma".

O breadcrumb é Dashboard › Turmas › nome da turma.

## Edição

Mesmos campos da [listagem](classroom-list.md), com estas diferenças:

- O **curso não aparece**: não pode ser trocado depois de criada.
- O código continua único por curso e período, ignorando a própria turma.
- As **vagas não podem ficar abaixo do número de alunos já matriculados**.
- O select de período lista apenas períodos Planejado ou Aberto.

## Ofertar matéria

| Campo | Validação |
| --- | --- |
| Matéria do curso | obrigatória, do mesmo curso da turma, ainda não ofertada nela |
| Professor | obrigatório, precisa existir |

O select mostra `código · nome (carga horária)`. Para uma matéria aparecer, ela precisa estar no catálogo do curso (ver [detalhe do curso](course-detail.md)).

## Ações e mensagens

| Ação | Sucesso | Falha |
| --- | --- | --- |
| Salvar turma | "Turma atualizada." | "Não foi possível atualizar a turma." |
| Ofertar matéria | "Matéria adicionada." | "Não foi possível adicionar a matéria." |
| Remover matéria | "Matéria removida." | "Não foi possível remover a matéria." |

Todas retornam ao detalhe da turma. A remoção é lógica (soft delete).

## Permissões

| Elemento | Requer |
| --- | --- |
| Ver a tela | apenas login |
| Editar turma | `classrooms.update` |
| Ofertar matéria | `subjects.create` |
| Remover matéria | `subjects.delete` |

## Observações

- A mesma matéria não pode ser ofertada duas vezes na turma (validação e índice único no banco).
- Não há listagem de alunos nem matrícula nesta tela. A regra de capacidade existe em `ClassroomRepository::enrollStudent`, coberta por teste.
- Testes: `tests/Feature/ClassroomTest.php`, `ClassroomAuthorizationTest.php`.
