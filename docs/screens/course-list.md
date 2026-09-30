# Tela: Cursos — listagem

## Rota e arquivos

| Item | Valor |
| --- | --- |
| URL / nome | `GET /courses` / `course` |
| Controller | `CourseController::index` |
| Página | `resources/js/pages/course/index.tsx` |
| Componentes | `components/course/form.tsx`, `components/course/table.tsx` |
| Criação | `POST /courses` / `course.store` (`StoreCourseRequest`) |

## Dados recebidos

| Prop | Conteúdo |
| --- | --- |
| `courses` | Cursos da escola do usuário, ordenados por nome, com `subjects_count` e `classrooms_count` |
| `levels` | Mapa nível → rótulo (`CourseLevel`) |
| `statuses` | Mapa status → rótulo (`CourseStatus`) |

## O que aparece

1. **Formulário "Novo curso"**, visível só com `courses.create`.
2. **Tabela de cursos** com as colunas Nome (link para o detalhe, com o código ao lado), Nível, Duração (em semestres), Status, Matérias e Turmas.
3. **Estado vazio:** "Nenhum curso encontrado".

## Formulário

| Campo | Tipo | Validação |
| --- | --- | --- |
| Nome | texto | obrigatório, até 255 |
| Código | texto | obrigatório, até 255, único na escola |
| Duração (semestres) | número | obrigatório, de 1 a 20 |
| Nível | select | obrigatório: Bacharelado, Licenciatura, Tecnólogo, Pós-graduação |
| Status | select | obrigatório: Ativo, Inativo (padrão Ativo) |

A escola do curso é a do usuário logado.

## Ações e mensagens

- **Criar curso:** redireciona para a própria listagem e mostra "Curso criado." Em falha de banco: "Não foi possível criar o curso."
- Clicar no nome abre o [detalhe do curso](course-detail.md).

## Permissões

| Elemento | Requer |
| --- | --- |
| Ver a tela e a tabela | apenas login |
| Formulário / criar | `courses.create` |

Veja [permissions.md](../permissions.md).

## Observações

- Usuário sem escola não consegue criar curso: o `school_id` fica nulo e o banco recusa.
- Não há exclusão de curso na interface (a Policy `courses.delete` existe, sem rota).
- Testes: `tests/Feature/CourseTest.php`.
