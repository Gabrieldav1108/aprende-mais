# Documentação — Aprende Mais

| Documento | Conteúdo |
| --- | --- |
| [structure.md](structure.md) | Stack, modelo de domínio, camadas do backend e do frontend, convenções |
| [permissions.md](permissions.md) | Papéis, permissões, Policies, isolamento por escola e como conceder acesso |

## Telas

| Tela | Rota | Documento |
| --- | --- | --- |
| Dashboard | `/dashboard` | [screens/dashboard.md](screens/dashboard.md) |
| Cursos — listagem | `/courses` | [screens/course-list.md](screens/course-list.md) |
| Cursos — detalhe | `/courses/{course}` | [screens/course-detail.md](screens/course-detail.md) |
| Períodos letivos | `/academic-terms` | [screens/academic-term.md](screens/academic-term.md) |
| Turmas — listagem | `/classroom` | [screens/classroom-list.md](screens/classroom-list.md) |
| Turmas — detalhe | `/classroom/{classroom}` | [screens/classroom-detail.md](screens/classroom-detail.md) |

As telas de autenticação (`pages/auth`), de configurações (`pages/settings`) e a `welcome` vêm do starter kit do Laravel (Fortify) e não têm documento próprio.

## Como ler os documentos de tela

Cada um segue a mesma estrutura: rota e arquivos, dados recebidos do backend, o que aparece na tela e sob qual permissão, campos e validações, ações e mensagens, e observações.
