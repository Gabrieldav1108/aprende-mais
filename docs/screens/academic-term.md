# Tela: Períodos letivos

## Rota e arquivos

| Item | Valor |
| --- | --- |
| URL / nome | `GET /academic-terms` / `academic-term` |
| Controller | `AcademicTermController::index` |
| Página | `resources/js/pages/academic-term/index.tsx` |
| Componentes | `components/academic-term/form.tsx`, `table.tsx` |
| Criação | `POST /academic-terms` / `academic-term.store` (`StoreAcademicTermRequest`) |
| Edição | `PUT /academic-terms/{academic_term}` / `academic-term.update` (rota e request prontos, **sem botão na tela**) |

## Dados recebidos

| Prop | Conteúdo |
| --- | --- |
| `terms` | Períodos da escola, do mais recente ao mais antigo, com `classrooms_count` |
| `statuses` | Mapa status → rótulo (`TermStatus`) |

## O que aparece

1. **Formulário "Novo período letivo"**, com `terms.create`.
2. **Tabela** com Período (por exemplo `2026/1`), Início e Fim (formato dd/mm/aaaa), Status e Turmas.
3. **Estado vazio:** "Nenhum período encontrado".

## Formulário

| Campo | Tipo | Validação |
| --- | --- | --- |
| Ano | número | obrigatório, de 2000 a 2999 |
| Semestre | select | obrigatório: 1º ou 2º; único por escola e ano |
| Início | data | obrigatório |
| Fim | data | obrigatório, depois do início |
| Status | select | Planejado, Aberto, Encerrado (padrão Planejado) |

O nome (`ano/semestre`) é gerado automaticamente pelo repositório.

## Ações e mensagens

- **Criar período:** "Período criado." / "Não foi possível criar o período."
- **Atualizar** (via rota, sem tela): "Período atualizado." / "Não foi possível atualizar o período."

## Permissões

| Elemento | Requer |
| --- | --- |
| Ver a tela | apenas login |
| Formulário / criar | `terms.create` |
| Atualizar (rota) | `terms.update` |

## Observações

- O status do período controla as turmas: só é possível criar ou mover uma turma para um período **Planejado** ou **Aberto**. Encerrar o período impede novas turmas nele.
- Usuário sem escola não consegue criar período.
- Testes: `tests/Feature/AcademicTermTest.php`.
