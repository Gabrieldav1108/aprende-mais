# Tela: Turmas — listagem

## Rota e arquivos

| Item | Valor |
| --- | --- |
| URL / nome | `GET /classroom` / `classroom` |
| Controller | `ClassroomController::index` |
| Página | `resources/js/pages/classroom/index.tsx` |
| Componentes | `components/classroom/form.tsx`, `table.tsx` |
| Criação | `POST /classroom` / `classroom.store` (`StoreClassroomRequest`) |

## Dados recebidos

| Prop | Conteúdo |
| --- | --- |
| `classrooms` | Turmas da escola, mais recentes primeiro, com curso, período, professor regente e as contagens de matérias e de alunos |
| `courses` | Cursos para o select (id, nome, código) |
| `terms` | Períodos **Planejado ou Aberto** para o select |
| `teachers` | Professores (com o usuário) para regente |
| `shifts`, `statuses` | Mapas de rótulos (`Shift`, `ClassroomStatus`) |

## O que aparece

1. **Formulário "Nova turma"**, com `classrooms.create`.
2. **Tabela** com Nome (link para o detalhe, com o código ao lado), Curso, Período (por exemplo `2026/1 · 3º`), Turno, Status, Ocupação (`alunos/vagas`) e Matérias.
3. **Estado vazio:** "Nenhuma turma encontrada".

## Formulário

| Campo | Validação |
| --- | --- |
| Curso | obrigatório, da escola do usuário |
| Período letivo | obrigatório, da escola, com status Planejado ou Aberto |
| Nome | obrigatório, até 255 |
| Código | obrigatório, único por curso e período |
| Semestre do curso | obrigatório, de 1 a 20 |
| Turno | obrigatório: Manhã, Tarde, Noite |
| Status | obrigatório: Planejada, Em andamento, Encerrada, Cancelada (padrão Em andamento) |
| Vagas | obrigatório, de 1 a 1000 (padrão 40) |
| Professor regente | opcional, da escola |
| Sala | opcional, até 255 |

A escola da turma vem do curso escolhido.

## Ações e mensagens

- **Criar turma:** "Turma criada." / "Não foi possível criar a turma."
- Clicar no nome abre o [detalhe da turma](classroom-detail.md).

## Permissões

| Elemento | Requer |
| --- | --- |
| Ver a tela e a tabela | apenas login |
| Formulário / criar | `classrooms.create` |

## Observações

- Para criar uma turma é preciso ter antes um curso e um período aberto ou planejado, cada um em sua tela.
- A coluna Ocupação só passa de zero quando houver matrícula de alunos, e ainda não há tela para isso.
- Testes: `tests/Feature/ClassroomTest.php`, `ClassroomAuthorizationTest.php`, `SchoolScopeTest.php`.
