# LMS — Checklist de produção (Fase 1)

Use este guia ao subir o EAD + portal aluno em um servidor novo ou atualizar um existente.

## 0. Verificação rápida (após SQL)

Cole `database/lms_verificar_producao.sql` no phpMyAdmin (somente leitura). Confirme tabelas/colunas e `total_conquistas_def` ≈ 100.

## 1. SQL (ordem obrigatória)

Colar no phpMyAdmin **nesta ordem**. Scripts idempotentes parcialmente: se uma coluna já existir, o `ALTER` falha — pule o trecho já aplicado.

| # | Arquivo | O que faz |
|---|---------|-----------|
| 1 | `database/lms_ead.sql` | Tabelas base (cursos, aulas, vídeos, materiais, atividades, questões, roleplay, progresso, AI) |
| 2 | `database/lms_xp.sql` | Ledger de XP + ranking |
| 3 | `database/lms_atividade_tentativas_status.sql` | `status` em tentativas (fluxo sequencial start/answer/finalize) |
| 4 | `database/lms_ciclo_avaliacao.sql` | Ciclos de unidade (`ciclo`, `precisa_revisar`, `nota_unidade`, …) |
| 5 | `database/lms_agenda_acesso.sql` | Agenda avulsa (reposição) + cota diária LMS (`agenda_avulso`, `lms_sessao_cota`) |
| 6 | `database/lms_curso_avaliacoes.sql` | Avaliações 1–5 do curso pelo aluno |
| 7 | `database/lms_certificados.sql` | Certificados simbólicos do portal EAD (sem QR) |
| 8 | `database/lms_conquistas.sql` | Tabelas base de conquistas + 9 seeds |
| 9 | `database/lms_conquistas_v2.sql` | ~100 conquistas + raridade/badge + prep escola/manual |
| 10 | `database/lms_notificacoes.sql` | Notificações in-app do portal |
| 11 | `database/lms_estudo_sessao.sql` | Heartbeat de tempo de estudo no player |
| 12 | `database/lms_aula_comentarios.sql` | Comentários nas aulas |
| 13 | `database/financeiro_acordos.sql` | Extrato consolidado: acordos + `caixa.id_acordo` (renegociação) |

Confirmação rápida:

```sql
SHOW COLUMNS FROM lms_atividade_tentativas LIKE 'status';
SHOW COLUMNS FROM lms_atividade_tentativas LIKE 'ciclo';
SHOW COLUMNS FROM lms_progresso_aula LIKE 'precisa_revisar';
SHOW TABLES LIKE 'lms_xp_ledger';
SHOW TABLES LIKE 'lms_curso_avaliacoes';
SHOW TABLES LIKE 'lms_certificados';
SHOW TABLES LIKE 'lms_conquistas_def';
SHOW COLUMNS FROM lms_conquistas_def LIKE 'subtitulo';
SELECT COUNT(*) FROM lms_conquistas_def;
SHOW TABLES LIKE 'lms_notificacoes';
SHOW TABLES LIKE 'lms_estudo_sessao';
SHOW TABLES LIKE 'lms_aula_comentarios';
```

## 2. Painel CTI (backend)

1. Deploy do código PHP (Git pull / upload).
2. `.env` de produção com DB correto; HTTPS.
3. Inclua `ASCEND_URL=https://aluno.SEU-DOMINIO` (link do e-mail de recuperar senha).
4. SMTP da escola (Config → Comunicação) ou SMTP_* do sistema — necessário para esqueci-senha.
5. Módulo `ead` liberado no plano da escola.
6. **Configurações → IA Pedagógica:** provider + chave + modelo  
   - Gemini: `gemini-2.0-flash` (evitar `gemini-1.5-flash` se a API rejeitar)  
   - OpenAI: `gpt-4o-mini` ou equivalente
7. Publicar ao menos 1 curso em **Cursos Online** (`publicado=1`).
8. Aluno com matrícula ativa (`matriculas.status=0` + datas) no mesmo `id_admin`.
9. **Pedagógico → Conquistas EAD:** ativar medalhas / liberar manualmente se quiser.
10. **Master → Conquistas:** upload de figurinha (badge) opcional por medalha.

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
- [ ] Atividade: start → responder 1 a 1 → finalize; V/F e aberta (IA); auto-avança ao próximo item (se não precisar reassistir)
- [ ] Após 3 falhas no ciclo: `precisa_revisar` → reassistir → novo ciclo
- [ ] Roleplay: chat + timer + nota + `xpEarned` no toast/resultado; prompt da IA **não** aparece para o aluno
- [ ] Assistente IA responde com contexto da aula (não stub “não configurado”)
- [ ] XP / ranking da escola
- [ ] Certificado: ao concluir 100% do curso aparece em Certificados; Visualizar/Baixar abre HTML+PDF (modelo da escola)
- [ ] Certificado desatualizado: após escola adicionar aula, card mostra “Desatualizado” + Continuar curso; PDF bloqueado até 100% de novo
- [ ] Admin: editar vídeo/material/atividade/questão/roleplay + Preview
- [ ] Admin Cursos Online: alerta se `lms_xp.sql` / `lms_xp_ledger` ausente
- [ ] Player: banner “Reassistir” + badge no currículo quando `needsRewatch`
- [ ] Tempo de estudo sobe ao deixar o player aberto (heartbeat ~30s)
- [ ] Comentários na aula: postar / responder / excluir o próprio
- [ ] Esqueci senha: recebe e-mail → `/reset-password?token=…` → login
- [ ] Admin Conquistas EAD: toggle + liberar medalha manual
- [ ] Admin Alunos → Progresso EAD: lista status das aulas + Liberar próxima aula
- [ ] Admin Pedagogico → Progresso EAD (turma): filtros + totais + CSV + link Detalhe
- [ ] Login Ascend: sem link de primeiro acesso; recuperar senha funciona
- [ ] Master: upload/remover figurinha aparece no portal
- [ ] Admin Alunos → Extrato financeiro (todas matrículas) + PDF + Renegociar débitos (após `financeiro_acordos.sql`)
- [ ] Portal aluno: menu Financeiro quando há títulos; só leitura
- [ ] Permissões: Cursos Online / Conquistas EAD no checklist; desmarcar remove do menu (também Diretor)

## 5. Admin EAD (painel)

Editor: `/painel/ead` → curso → aula.

- Criar/editar/excluir: vídeo, material, atividade (`tentativas_max`, duração), questão, roleplay
- Preview atividade / roleplay (visão do aluno, sem gabarito/prompt secreto)

## 6. Não fazer nesta fase

- Vitrine entre escolas / royalties (LMS L6+)
- Force-push / rewrite de histórico no Ascend (Lovable)
