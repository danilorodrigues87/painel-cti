-- LMS Conquistas v3 — raridade secreto, snapshot de ranking, +50 conquistas NOVAS
-- NÃO altera badge_url / artes das medalhas já cadastradas (só INSERT de slugs novos).
-- Pré-requisito: lms_conquistas.sql + lms_conquistas_v2.sql

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =============================================================================
-- 1) Raridade secreto
-- Se o ENUM já tiver 'secreto', ignore o erro e siga.
-- =============================================================================
ALTER TABLE lms_conquistas_def
  MODIFY COLUMN raridade ENUM('bronze','prata','ouro','lendario','secreto') NOT NULL DEFAULT 'bronze';

-- =============================================================================
-- 2) Snapshot diário de ranking (para conquistas de posição × dias)
-- =============================================================================
CREATE TABLE IF NOT EXISTS lms_ranking_diario (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  data DATE NOT NULL,
  scope ENUM('escola','global') NOT NULL,
  id_admin INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = ranking global',
  id_aluno INT UNSIGNED NOT NULL,
  posicao INT UNSIGNED NOT NULL,
  xp INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_rank_dia (data, scope, id_admin, id_aluno),
  KEY idx_rank_aluno (id_aluno, scope, id_admin, data),
  KEY idx_rank_pos (data, scope, id_admin, posicao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3) Seeds novas (~50) — slugs inéditos; badge_url NULL (artes depois no Master)
-- ON DUPLICATE: atualiza textos/meta, NUNCA badge_url
-- =============================================================================
INSERT INTO lms_conquistas_def
  (slug, titulo, descricao, icone, meta_tipo, meta_valor, ordem, ativo, subtitulo, como, raridade, badge_url)
VALUES
  -- ===== BRONZE (+10) =====
  ('xp_250', 'Acumule 250 XP', 'Duzentos e cinquenta pontos — aquecendo o placar.', 'Zap', 'xp_total', 250, 101, 1, 'XP a caminho', 'Some XP com aulas, atividades e roleplays até 250.', 'bronze', NULL),
  ('xp_300', 'Acumule 300 XP', 'Trezentos XP: o hábito de estudar está firme.', 'Star', 'xp_total', 300, 102, 1, 'Trezentos', 'Continue até seu XP total chegar a 300.', 'bronze', NULL),
  ('aulas_6', 'Conclua 6 aulas', 'Seis aulas no currículo — ritmo de quem voltou.', 'BookOpen', 'aulas_concluidas', 6, 103, 1, 'Meia dúzia', 'Conclua 6 aulas no portal.', 'bronze', NULL),
  ('aulas_12', 'Conclua 12 aulas', 'Uma dúzia de aulas: você já tem trilha percorrida.', 'BookOpen', 'aulas_concluidas', 12, 104, 1, 'Dúzia de aulas', 'Conclua 12 aulas no total.', 'bronze', NULL),
  ('estudo_45', 'Estude 45 minutos', 'Quarenta e cinco minutos acumulados de foco.', 'Clock', 'estudo_min', 45, 105, 1, 'Três quartos', 'Acumule 45 minutos de estudo nas aulas.', 'bronze', NULL),
  ('estudo_75', 'Estude 75 minutos', 'Uma hora e quinze — sessão bem aproveitada.', 'Clock', 'estudo_min', 75, 106, 1, '75 minutos', 'Acumule 75 minutos de estudo.', 'bronze', NULL),
  ('atividades_3', 'Acerte 3 atividades', 'Três atividades aprovadas no currículo prático.', 'Target', 'atividades_ok', 3, 107, 1, 'Trinca prática', 'Complete 3 atividades com aprovação.', 'bronze', NULL),
  ('atividades_4', 'Acerte 4 atividades', 'Quatro acertos — consistência começando.', 'Target', 'atividades_ok', 4, 108, 1, 'Quatro no alvo', 'Complete 4 atividades com aprovação.', 'bronze', NULL),
  ('nota_75', 'Tire 75% ou mais', 'Nota boa em atividade ou roleplay.', 'Award', 'nota_min', 75, 109, 1, 'Nota sólida', 'Obtenha nota ≥ 75% em uma atividade ou roleplay.', 'bronze', NULL),
  ('streak_4', '4 dias de streak', 'Quatro dias seguidos na agenda de estudos.', 'Flame', 'streak', 4, 110, 1, 'Quatro no fogo', 'Cumprir 4 dias consecutivos de agenda.', 'bronze', NULL),

  -- ===== PRATA (+10) =====
  ('xp_600', 'Acumule 600 XP', 'Seiscentos XP — você já é figura no placar.', 'Trophy', 'xp_total', 600, 111, 1, 'Seiscentos', 'Acumule 600 pontos de experiência.', 'prata', NULL),
  ('xp_900', 'Acumule 900 XP', 'Novecentos XP: quase mil no bolso.', 'Star', 'xp_total', 900, 112, 1, 'Quase mil', 'Continue até somar 900 XP.', 'prata', NULL),
  ('aulas_28', 'Conclua 28 aulas', 'Vinte e oito aulas — mês de dedicação.', 'BookOpen', 'aulas_concluidas', 28, 113, 1, 'Vinte e oito', 'Conclua 28 aulas no portal.', 'prata', NULL),
  ('aulas_35', 'Conclua 35 aulas', 'Trinta e cinco aulas no histórico.', 'BookOpen', 'aulas_concluidas', 35, 114, 1, 'Trinta e cinco', 'Conclua 35 aulas no total.', 'prata', NULL),
  ('estudo_210', 'Estude 3h30', 'Duzentos e dez minutos de estudo acumulado.', 'Clock', 'estudo_min', 210, 115, 1, '3h30', 'Acumule 210 minutos de estudo.', 'prata', NULL),
  ('estudo_300', 'Estude 5 horas', 'Cinco horas totais de foco no portal.', 'Clock', 'estudo_min', 300, 116, 1, 'Cinco horas', 'Acumule 300 minutos de estudo.', 'prata', NULL),
  ('atividades_6', 'Acerte 6 atividades', 'Seis atividades aprovadas.', 'Target', 'atividades_ok', 6, 117, 1, 'Seis certeiras', 'Complete 6 atividades com aprovação.', 'prata', NULL),
  ('atividades_12', 'Acerte 12 atividades', 'Uma dúzia de atividades no currículo.', 'Target', 'atividades_ok', 12, 118, 1, 'Dúzia prática', 'Complete 12 atividades com aprovação.', 'prata', NULL),
  ('roleplays_6', 'Conclua 6 roleplays', 'Seis simulações finalizadas.', 'Swords', 'roleplays_ok', 6, 119, 1, 'Seis rounds', 'Conclua 6 roleplays até o fim.', 'prata', NULL),
  ('streak_12', '12 dias de streak', 'Doze dias consecutivos — disciplina de elite.', 'Flame', 'streak', 12, 120, 1, 'Doze dias fire', 'Cumprir 12 dias consecutivos de agenda.', 'prata', NULL),

  -- ===== OURO (+10) — inclui ranking escola 7 dias =====
  ('rank_esc_1_7d', '1º da escola por 7 dias', 'Fique em 1º no ranking da sua escola por 7 dias seguidos.', 'Crown', 'rank_escola_1', 7, 121, 1, 'Trono da escola', 'Mantenha o 1º lugar no ranking da escola por 7 dias consecutivos (snapshot diário).', 'ouro', NULL),
  ('rank_esc_2_7d', '2º da escola por 7 dias', 'Fique em 2º no ranking da escola por 7 dias seguidos.', 'Medal', 'rank_escola_2', 7, 122, 1, 'Vice da escola', 'Mantenha o 2º lugar no ranking da escola por 7 dias consecutivos.', 'ouro', NULL),
  ('rank_esc_3_7d', '3º da escola por 7 dias', 'Fique em 3º no ranking da escola por 7 dias seguidos.', 'Award', 'rank_escola_3', 7, 123, 1, 'Pódio da escola', 'Mantenha o 3º lugar no ranking da escola por 7 dias consecutivos.', 'ouro', NULL),
  ('xp_3500', 'Acumule 3500 XP', 'Três mil e quinhentos XP — elite avançada.', 'Gem', 'xp_total', 3500, 124, 1, '3500 XP', 'Acumule 3500 pontos de experiência.', 'ouro', NULL),
  ('aulas_80', 'Conclua 80 aulas', 'Oitenta aulas no currículo.', 'BookOpen', 'aulas_concluidas', 80, 125, 1, 'Oitenta aulas', 'Conclua 80 aulas no total.', 'ouro', NULL),
  ('estudo_720', 'Estude 12 horas', 'Doze horas acumuladas de estudo.', 'Clock', 'estudo_min', 720, 126, 1, 'Doze horas', 'Acumule 720 minutos de estudo.', 'ouro', NULL),
  ('atividades_25', 'Acerte 25 atividades', 'Vinte e cinco atividades aprovadas.', 'Target', 'atividades_ok', 25, 127, 1, 'Vinte e cinco', 'Complete 25 atividades com aprovação.', 'ouro', NULL),
  ('roleplays_15', 'Conclua 15 roleplays', 'Quinze simulações concluídas.', 'Swords', 'roleplays_ok', 15, 128, 1, 'Quinze rounds', 'Conclua 15 roleplays no total.', 'ouro', NULL),
  ('nivel_8', 'Alcance o nível 8', 'Nível 8 desbloqueado.', 'Rocket', 'nivel', 8, 129, 1, 'Nível 8', 'Acumule XP até chegar ao nível 8.', 'ouro', NULL),
  ('streak_35', '35 dias de streak', 'Trinta e cinco dias consecutivos na agenda.', 'Flame', 'streak', 35, 130, 1, '35 dias fire', 'Cumprir 35 dias consecutivos de agenda.', 'ouro', NULL),

  -- ===== LENDÁRIO (+10) — inclui ranking global 3 dias =====
  ('rank_glb_1_3d', '1º global por 3 dias', 'Fique em 1º no ranking global por 3 dias seguidos.', 'Crown', 'rank_global_1', 3, 131, 1, 'Topo do mundo', 'Mantenha o 1º lugar no ranking global (XP 30 dias) por 3 dias consecutivos.', 'lendario', NULL),
  ('rank_glb_2_3d', '2º global por 3 dias', 'Fique em 2º no ranking global por 3 dias seguidos.', 'Medal', 'rank_global_2', 3, 132, 1, 'Vice global', 'Mantenha o 2º lugar no ranking global por 3 dias consecutivos.', 'lendario', NULL),
  ('rank_glb_3_3d', '3º global por 3 dias', 'Fique em 3º no ranking global por 3 dias seguidos.', 'Award', 'rank_global_3', 3, 133, 1, 'Pódio global', 'Mantenha o 3º lugar no ranking global por 3 dias consecutivos.', 'lendario', NULL),
  ('xp_12000', 'Acumule 12000 XP', 'Doze mil XP — status de lenda.', 'Crown', 'xp_total', 12000, 134, 1, '12k XP', 'Acumule 12000 pontos de experiência.', 'lendario', NULL),
  ('aulas_250', 'Conclua 250 aulas', 'Duzentas e cinquenta aulas — maratona épica.', 'Brain', 'aulas_concluidas', 250, 135, 1, '250 aulas', 'Conclua 250 aulas no portal.', 'lendario', NULL),
  ('estudo_3600', 'Estude 60 horas', 'Sessenta horas acumuladas de estudo.', 'Mountain', 'estudo_min', 3600, 136, 1, '60 horas', 'Acumule 3600 minutos de estudo.', 'lendario', NULL),
  ('atividades_75', 'Acerte 75 atividades', 'Setenta e cinco atividades aprovadas.', 'Target', 'atividades_ok', 75, 137, 1, '75 no alvo', 'Complete 75 atividades com aprovação.', 'lendario', NULL),
  ('roleplays_40', 'Conclua 40 roleplays', 'Quarenta simulações — orador lendário.', 'Swords', 'roleplays_ok', 40, 138, 1, '40 rounds', 'Conclua 40 roleplays no total.', 'lendario', NULL),
  ('nivel_25', 'Alcance o nível 25', 'Nível 25: horizonte raro.', 'Gem', 'nivel', 25, 139, 1, 'Nível 25', 'Acumule XP até o nível 25.', 'lendario', NULL),
  ('streak_120', '120 dias de streak', 'Cento e vinte dias consecutivos — trimestre+ de fogo.', 'Shield', 'streak', 120, 140, 1, '120 dias', 'Cumprir 120 dias consecutivos de agenda.', 'lendario', NULL),

  -- ===== SECRETO (+10) — nomes reais no banco; portal mascara até desbloquear =====
  ('sec_avatar', 'Retrato revelado', 'Atualize sua foto de perfil no portal.', 'Camera', 'avatar_ok', 1, 141, 1, 'Quem é você?', 'Envie uma foto de perfil (JPG, PNG ou WEBP) na página Perfil.', 'secreto', NULL),
  ('sec_rank_esc_1_30', 'Reinado de 30 dias', '1º no ranking da escola por 30 dias seguidos.', 'Crown', 'rank_escola_1', 30, 142, 1, 'Trono absoluto', 'Mantenha o 1º lugar da escola por 30 dias consecutivos.', 'secreto', NULL),
  ('sec_rank_glb_1_7', 'Uma semana no topo', '1º no ranking global por 7 dias seguidos.', 'Globe', 'rank_global_1', 7, 143, 1, 'Topo mundial', 'Mantenha o 1º lugar global por 7 dias consecutivos.', 'secreto', NULL),
  ('sec_conq_100', 'Colecionador 100', 'Desbloqueie 100 conquistas no portal.', 'Trophy', 'conquistas_unlocked', 100, 144, 1, 'Centena de medalhas', 'Desbloqueie 100 conquistas diferentes (qualquer raridade).', 'secreto', NULL),
  ('sec_conq_500', 'Colecionador 500', 'Desbloqueie 500 conquistas (meta de longo prazo).', 'Gem', 'conquistas_unlocked', 500, 145, 1, 'Quinhentas medalhas', 'Desbloqueie 500 conquistas. Pode exigir expansões futuras do catálogo.', 'secreto', NULL),
  ('sec_curso_100', 'Curso perfeito', 'Tire 100% em todas as atividades de um curso.', 'GraduationCap', 'curso_atividades_100', 1, 146, 1, 'Nota máxima no curso', 'Em um mesmo curso, obtenha 100% em cada atividade existente.', 'secreto', NULL),
  ('sec_indicar', 'Embaixador', 'Indique um amigo que se matricule na escola.', 'Heart', 'manual', 1, 147, 1, 'Traga um amigo', 'Use Indicar amigo no perfil. A escola libera esta conquista após matricular o indicado.', 'secreto', NULL),
  ('sec_streak_365', 'Ano de fogo', '365 dias consecutivos de streak.', 'Flame', 'streak', 365, 148, 1, '365 dias', 'Cumprir 365 dias consecutivos de agenda sem quebrar.', 'secreto', NULL),
  ('sec_nota_100_x10', 'Dez notas 10', 'Tire 100% em 10 atividades diferentes.', 'Award', 'nota_100_count', 10, 149, 1, 'Dez perfeitas', 'Obtenha nota 100% em 10 atividades distintas.', 'secreto', NULL),
  ('sec_xp_15000', 'XP infinito', 'Acumule 15000 XP no portal.', 'Mountain', 'xp_total', 15000, 150, 1, '15k XP', 'Acumule 15000 pontos de experiência.', 'secreto', NULL)
ON DUPLICATE KEY UPDATE
  titulo = VALUES(titulo),
  descricao = VALUES(descricao),
  icone = VALUES(icone),
  meta_tipo = VALUES(meta_tipo),
  meta_valor = VALUES(meta_valor),
  ordem = VALUES(ordem),
  ativo = VALUES(ativo),
  subtitulo = VALUES(subtitulo),
  como = VALUES(como),
  raridade = VALUES(raridade);
  -- badge_url propositalmente NÃO atualizado (preserva artes já enviadas)

-- Conferência:
-- SELECT raridade, COUNT(*) FROM lms_conquistas_def GROUP BY raridade;
-- SELECT COUNT(*) FROM lms_conquistas_def; -- esperado ~150
