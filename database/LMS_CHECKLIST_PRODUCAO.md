# LMS — Checklist de produção (Fase 1)

Use este guia ao subir o EAD + portal aluno em um servidor novo ou atualizar um existente.

## 1. SQL (ordem obrigatória)

Colar no phpMyAdmin **nesta ordem**. Scripts idempotentes parcialmente: se uma coluna já existir, o `ALTER` falha — pule o trecho já aplicado.

| # | Arquivo | O que faz |
|---|---------|-----------|
| 1 | `database/lms_ead.sql` | Tabelas base (cursos, aulas, vídeos, materiais, atividades, questões, roleplay, progresso, AI) |
| 2 | `database/lms_xp.sql` | Ledger de XP + ranking |
| 3 | `database/lms_atividade_tentativas_status.sql` | `status` em tentativas (fluxo sequencial start/answer/finalize) |
| 4 | `database/lms_ciclo_avaliacao.sql` | Ciclos de unidade (`ciclo`, `precisa_revisar`, `nota_unidade`, …) |

Confirmação rápida:

```sql
SHOW COLUMNS FROM lms_atividade_tentativas LIKE 'status';
SHOW COLUMNS FROM lms_atividade_tentativas LIKE 'ciclo';
SHOW COLUMNS FROM lms_progresso_aula LIKE 'precisa_revisar';
SHOW TABLES LIKE 'lms_xp_ledger';
```

## 2. Painel CTI (backend)

1. Deploy do código PHP (Git pull / upload).
2. `.env` de produção com DB correto; HTTPS.
3. Módulo `ead` liberado no plano da escola.
4. **Configurações → IA Pedagógica:** provider + chave + modelo  
   - Gemini: `gemini-2.0-flash` (evitar `gemini-1.5-flash` se a API rejeitar)  
   - OpenAI: `gpt-4o-mini` ou equivalente
5. Publicar ao menos 1 curso em **Cursos Online** (`publicado=1`).
6. Aluno com matrícula ativa (`matriculas.status=0` + datas) no mesmo `id_admin`.

## 3. Portal Ascend (frontend)

1. No projeto `ascend-academy`, `.env` / `.env.production`:

```env
VITE_API_BASE_URL=https://SEU-DOMINIO/api/v1/student
```

(ajuste o path se o painel estiver em subpasta)

2. Build e publicar a pasta `dist`:

```bash
npm run build
```

3. Servir o build (Apache/Nginx) com fallback SPA para `index.html`.
4. CORS: a API aluno já libera o portal; confirme origem HTTPS.

## 4. Smoke test (obrigatório)

- [ ] Login aluno no portal
- [ ] Lista de cursos / abrir curso / player com currículo
- [ ] Vídeo YouTube carrega (embed)
- [ ] Material abre
- [ ] Atividade: start → responder 1 a 1 → finalize; V/F e aberta (IA)
- [ ] Após 3 falhas no ciclo: `precisa_revisar` → reassistir → novo ciclo
- [ ] Roleplay: chat + timer + nota; prompt da IA **não** aparece para o aluno
- [ ] Assistente IA responde com contexto da aula (não stub “não configurado”)
- [ ] XP / ranking da escola
- [ ] Admin: editar vídeo/material/atividade/questão/roleplay + Preview

## 5. Admin EAD (painel)

Editor: `/painel/ead` → curso → aula.

- Criar/editar/excluir: vídeo, material, atividade (`tentativas_max`, duração), questão, roleplay
- Preview atividade / roleplay (visão do aluno, sem gabarito/prompt secreto)

## 6. Não fazer nesta fase

- Vitrine entre escolas / royalties (LMS L6+)
- Force-push / rewrite de histórico no Ascend (Lovable)
