-- =============================================================================
-- LMS — Verificação rápida de produção (somente leitura)
-- Cole no phpMyAdmin e confira se cada consulta retorna o esperado.
-- Não altera dados. Se alguma tabela/coluna faltar, rode o script da ordem em
-- database/LMS_CHECKLIST_PRODUCAO.md.
-- =============================================================================

-- 1) Tabelas essenciais (cada linha deve aparecer)
SHOW TABLES LIKE 'lms_cursos';
SHOW TABLES LIKE 'lms_modulos';
SHOW TABLES LIKE 'lms_aulas';
SHOW TABLES LIKE 'lms_progresso_aula';
SHOW TABLES LIKE 'lms_xp_ledger';
SHOW TABLES LIKE 'lms_certificados';
SHOW TABLES LIKE 'lms_conquistas_def';
SHOW TABLES LIKE 'lms_escola_conquistas';
SHOW TABLES LIKE 'lms_notificacoes';
SHOW TABLES LIKE 'lms_estudo_sessao';
SHOW TABLES LIKE 'lms_aula_comentarios';
SHOW TABLES LIKE 'lms_sessao_cota';
SHOW TABLES LIKE 'agenda_avulso';

-- 2) Colunas de fluxo de avaliação
SHOW COLUMNS FROM lms_atividade_tentativas LIKE 'status';
SHOW COLUMNS FROM lms_atividade_tentativas LIKE 'ciclo';
SHOW COLUMNS FROM lms_progresso_aula LIKE 'precisa_revisar';
SHOW COLUMNS FROM lms_conquistas_def LIKE 'subtitulo';
SHOW COLUMNS FROM lms_conquistas_def LIKE 'badge_url';

-- 3) Seeds de conquistas (esperado ~100)
SELECT COUNT(*) AS total_conquistas_def FROM lms_conquistas_def;

-- 4) Cursos publicados por escola (ajuste id_admin se quiser filtrar)
SELECT id_admin, COUNT(*) AS cursos, SUM(publicado = 1) AS publicados
FROM lms_cursos
GROUP BY id_admin;

-- 5) Progresso recente (últimos 7 dias) — opcional
SELECT COUNT(*) AS aulas_concluidas_7d
FROM lms_progresso_aula
WHERE concluida_em IS NOT NULL
  AND concluida_em >= DATE_SUB(NOW(), INTERVAL 7 DAY);
