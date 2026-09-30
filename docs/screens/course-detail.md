# Tela: Cursos — detalhe

## Rota e arquivos

| Item | Valor |
| --- | --- |
| URL / nome | `GET /courses/{course}` / `course.show` |
| Controller | `CourseController::show` |
| Página | `resources/js/pages/course/show.tsx` |
| Componentes | `components/course/form.tsx`, `subject-form.tsx`, `subjects-table.tsx` |
| Edição | `PUT /courses/{course}` / `course.update` (`UpdateCourseRequest`) |
| Nova matéria | `POST /courses/{course}/subjects` / `course.subjects.store` (`StoreSubjectRequest`, `SubjectController::store`) |

## Dados recebidos

| Prop | Conteúdo |
| --- | --- |
| `course` | O curso |
| `subjects` | Catálogo de matérias do curso, ordenado por semestre sugerido e nome |
| `levels`, `statuses` | Mapas de rótulos dos enums |

Um curso de outra escola retorna 404.

## O que aparece

1. **Formulário "Editar curso"** preenchido, com `courses.update`.
2. **Formulário "Adicionar matéria ao curso"**, com `subjects.create`.
3. **Tabela do catálogo** com Matéria, Código, Carga horária (em horas) e Semestre sugerido (ou "—").
4. **Estado vazio:** "Nenhuma matéria cadastrada neste curso".

O breadcrumb é Dashboard › Cursos › nome do curso.

## Formulário de edição

Mesmos campos e validações da [listagem](course-list.md). O código continua único na escola, ignorando o próprio curso.

## Formulário de matéria

| Campo | Validação |
| --- | --- |
| Nome | obrigatório, até 255 |
| Código | obrigatório, até 255, único **dentro do curso** |
| Carga horária | obrigatório, inteiro de 1 a 65535 |
| Semestre sugerido | opcional, de 1 até a duração do curso |

A matéria é criada com a escola do curso.

## Ações e mensagens

- **Salvar curso:** "Curso atualizado." / "Não foi possível atualizar o curso."
- **Adicionar matéria:** "Matéria adicionada ao curso." / "Não foi possível adicionar a matéria." Ambas retornam ao detalhe do curso.

## Permissões

| Elemento | Requer |
| --- | --- |
| Ver a tela e o catálogo | apenas login |
| Editar curso | `courses.update` |
| Adicionar matéria | `subjects.create` |

## Observações

- É aqui que as matérias são **criadas**. Na turma elas só são escolhidas, e só as do curso da turma.
- O catálogo não tem edição nem exclusão de matéria.
- Testes: `tests/Feature/CourseTest.php`.
