-- LMS Conquistas v2 — colunas extras, origem, override por escola e ~100 seeds
-- Idempotente onde possível. ALTERs de coluna podem falhar se a coluna já existir (ok).

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =============================================================================
-- 1) Colunas novas em lms_conquistas_def
-- Pode falhar se a coluna já existir — ignore o erro e siga.
-- =============================================================================

ALTER TABLE lms_conquistas_def
  ADD COLUMN subtitulo VARCHAR(120) NOT NULL DEFAULT '' AFTER titulo;

ALTER TABLE lms_conquistas_def
  ADD COLUMN como TEXT NULL AFTER descricao;

ALTER TABLE lms_conquistas_def
  ADD COLUMN raridade ENUM('bronze','prata','ouro','lendario') NOT NULL DEFAULT 'bronze' AFTER icone;

ALTER TABLE lms_conquistas_def
  ADD COLUMN badge_url VARCHAR(255) NULL DEFAULT NULL AFTER raridade;

-- =============================================================================
-- 2) Origem em lms_conquistas_aluno
-- Pode falhar se a coluna já existir — ignore o erro e siga.
-- =============================================================================
ALTER TABLE lms_conquistas_aluno
  ADD COLUMN origem ENUM('auto','manual') NOT NULL DEFAULT 'auto' AFTER unlocked_at;

-- =============================================================================
-- 3) Override de conquistas por escola (tenant)
-- =============================================================================
CREATE TABLE IF NOT EXISTS lms_escola_conquistas (
  id_admin INT UNSIGNED NOT NULL,
  slug VARCHAR(64) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_admin, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4) Seeds (~100) — ON DUPLICATE KEY UPDATE enriquece as 9 originais
-- =============================================================================
INSERT INTO lms_conquistas_def
  (slug, titulo, descricao, icone, meta_tipo, meta_valor, ordem, ativo, subtitulo, como, raridade, badge_url)
VALUES
  -- ===== BRONZE (~25) — iniciante =====
  ('primeira_aula', 'Conclua 1 aula', 'Dê o primeiro passo: termine sua primeira aula no portal.', 'Sparkles', 'aulas_concluidas', 1, 1, 1, 'Primeiro passo!', 'Abra qualquer curso, assista e conclua 1 aula até o fim.', 'bronze', NULL),
  ('xp_50', 'Acumule 50 XP', 'Comece a subir no placar com seus primeiros pontos.', 'Zap', 'xp_total', 50, 2, 1, 'XP de aquecimento', 'Conclua aulas, atividades ou roleplays para ganhar XP até somar 50.', 'bronze', NULL),
  ('xp_100', 'Acumule 100 XP', 'Cem pontos no bolso — o hábito está começando.', 'Star', 'xp_total', 100, 3, 1, 'Cem e contando', 'Continue estudando até seu XP total chegar a 100.', 'bronze', NULL),
  ('aulas_3', 'Conclua 3 aulas', 'Três aulas no currículo — ritmo de quem está engajado.', 'BookOpen', 'aulas_concluidas', 3, 4, 1, 'Trio de ouro', 'Conclua 3 aulas diferentes (ou da mesma trilha) no portal.', 'bronze', NULL),
  ('aulas_5', 'Conclua 5 aulas', 'Cinco aulas concluídas: você já saiu do modo turista.', 'BookOpen', 'aulas_concluidas', 5, 5, 1, 'Mão na massa', 'Conclua 5 aulas no total. O progresso soma entre cursos.', 'bronze', NULL),
  ('streak_1', '1 dia de streak', 'Mostrou presença: cumpriu 1 dia na agenda de estudos.', 'Flame', 'streak', 1, 6, 1, 'Fogo aceso', 'Cumprir 1 dia de agenda consecutivo (sessão válida no dia).', 'bronze', NULL),
  ('streak_3', '3 dias de streak', 'Três dias seguidos — constância já é superpoder.', 'Flame', 'streak', 3, 7, 1, 'Trinca quente', 'Cumprir 3 dias consecutivos de agenda sem quebrar a sequência.', 'bronze', NULL),
  ('estudo_15', 'Estude 15 minutos', 'Um quarto de hora focado já conta — e muito.', 'Clock', 'estudo_min', 15, 8, 1, 'Sprint rápido', 'Some 15 minutos nas aulas concluídas (duração registrada).', 'bronze', NULL),
  ('estudo_30', 'Estude 30 minutos', 'Meia hora de foco: o cérebro agradece.', 'Clock', 'estudo_min', 30, 9, 1, 'Meia hora power', 'Acumule 30 minutos de estudo pelas aulas que você concluir.', 'bronze', NULL),
  ('estudo_60', 'Estude 60 minutos', 'Uma hora no total — bem-vindo ao clube do foco.', 'Clock', 'estudo_min', 60, 10, 1, 'Hora nobre', 'Some 60 minutos nas aulas concluídas ao longo do tempo.', 'bronze', NULL),
  ('atividades_1', 'Acerte 1 atividade', 'Primeira atividade concluída com sucesso.', 'Target', 'atividades_ok', 1, 11, 1, 'Tiro certeiro', 'Complete 1 atividade (quiz/avaliação) com aprovação.', 'bronze', NULL),
  ('roleplays_1', 'Conclua 1 roleplay', 'Entrou no jogo: finalize sua primeira simulação.', 'Swords', 'roleplays_ok', 1, 12, 1, 'Primeiro round', 'Inicie e conclua 1 roleplay até o fim.', 'bronze', NULL),
  ('nota_60', 'Tire 60% ou mais', 'Nota mínima de aprovação em atividade ou roleplay.', 'Award', 'nota_min', 60, 13, 1, 'Passou raspando? Ok!', 'Obtenha nota ≥ 60% em qualquer atividade ou roleplay.', 'bronze', NULL),
  ('nota_70', 'Tire 70% ou mais', 'Bom desempenho: sete em dez (ou melhor).', 'Medal', 'nota_min', 70, 14, 1, 'Nota decente', 'Obtenha nota ≥ 70% em uma atividade ou roleplay.', 'bronze', NULL),
  ('nivel_2', 'Alcance o nível 2', 'Subiu de nível — a barra de XP não mente.', 'Rocket', 'nivel', 2, 15, 1, 'Level up!', 'Acumule XP suficiente para chegar ao nível 2.', 'bronze', NULL),
  ('cursos_avaliados_1', 'Avalie 1 curso', 'Deixe sua opinião e ajude a melhorar o portal.', 'Heart', 'cursos_avaliados', 1, 16, 1, 'Feedback friend', 'Avalie 1 curso com nota/comentário no portal.', 'bronze', NULL),
  ('aulas_8', 'Conclua 8 aulas', 'Oito aulas no histórico — você já tem rotina.', 'BookOpen', 'aulas_concluidas', 8, 17, 1, 'Rotina ligou', 'Conclua 8 aulas no total no portal.', 'bronze', NULL),
  ('xp_150', 'Acumule 150 XP', 'Cento e cinquenta pontos: o placar está aquecendo.', 'Star', 'xp_total', 150, 18, 1, 'XP aquecido', 'Some XP até atingir 150 no total.', 'bronze', NULL),
  ('atividades_2', 'Acerte 2 atividades', 'Duas atividades no currículo — consistência prática.', 'Target', 'atividades_ok', 2, 19, 1, 'Dupla prática', 'Complete 2 atividades com aprovação.', 'bronze', NULL),
  ('estudo_90', 'Estude 90 minutos', 'Uma hora e meia acumulada de estudo real.', 'Clock', 'estudo_min', 90, 20, 1, 'Foco estendido', 'Acumule 90 minutos de estudo via aulas concluídas.', 'bronze', NULL),
  ('nota_80', 'Tire 80% ou mais', 'Nota alta: desempenho acima da média.', 'Award', 'nota_min', 80, 21, 1, 'Nota alta', 'Obtenha nota ≥ 80% em uma atividade ou roleplay.', 'bronze', NULL),
  ('streak_2', '2 dias de streak', 'Voltou no dia seguinte — é assim que se constrói hábito.', 'Flame', 'streak', 2, 22, 1, 'Dia seguinte', 'Cumprir 2 dias consecutivos de agenda.', 'bronze', NULL),
  ('aulas_10', 'Conclua 10 aulas', 'Dez aulas: marca simbólica de quem está dentro.', 'BookOpen', 'aulas_concluidas', 10, 23, 1, 'Dez no placar', 'Conclua 10 aulas no total.', 'bronze', NULL),
  ('xp_200', 'Acumule 200 XP', 'Duzentos XP — você já não é iniciante absoluto.', 'Zap', 'xp_total', 200, 24, 1, 'Duzentos firmes', 'Acumule 200 pontos de experiência no total.', 'bronze', NULL),
  ('roleplays_2', 'Conclua 2 roleplays', 'Duas simulações finalizadas: prática com propósito.', 'Swords', 'roleplays_ok', 2, 25, 1, 'Segundo round', 'Conclua 2 roleplays até o fim.', 'bronze', NULL),

  -- ===== PRATA (~25) — intermediário =====
  ('xp_500', 'Acumule 500 XP', 'Quinhentos pontos: você já está no meio do jogo.', 'Trophy', 'xp_total', 500, 26, 1, 'Meio do jogo', 'Continue estudando até somar 500 XP no total.', 'prata', NULL),
  ('aulas_15', 'Conclua 15 aulas', 'Quinze aulas no histórico — progresso sólido.', 'BookOpen', 'aulas_concluidas', 15, 27, 1, 'Quinze firmes', 'Conclua 15 aulas no portal.', 'prata', NULL),
  ('aulas_20', 'Conclua 20 aulas', 'Vinte aulas: ritmo de quem leva a sério.', 'BookOpen', 'aulas_concluidas', 20, 28, 1, 'Vinte no bolso', 'Conclua 20 aulas no total.', 'prata', NULL),
  ('aulas_25', 'Conclua 25 aulas', 'Vinte e cinco aulas — metade do caminho para o avançado.', 'BookOpen', 'aulas_concluidas', 25, 29, 1, 'Quarto de cento', 'Conclua 25 aulas no total.', 'prata', NULL),
  ('streak_5', '5 dias de streak', 'Cinco dias seguidos sem quebrar — respeito.', 'Flame', 'streak', 5, 30, 1, 'Semana quase', 'Cumprir 5 dias consecutivos de agenda.', 'prata', NULL),
  ('streak_7', '7 dias de streak', 'Uma semana inteira de constância. Impressionante.', 'Calendar', 'streak', 7, 31, 1, 'Semana fechada', 'Cumprir 7 dias consecutivos de agenda sem falhar.', 'prata', NULL),
  ('estudo_120', 'Estude 2 horas', 'Duas horas acumuladas de estudo real.', 'Clock', 'estudo_min', 120, 32, 1, 'Duas horas', 'Acumule 120 minutos de estudo pelas aulas concluídas.', 'prata', NULL),
  ('estudo_180', 'Estude 3 horas', 'Três horas no cronômetro — dedicação de verdade.', 'Clock', 'estudo_min', 180, 33, 1, 'Três horas', 'Acumule 180 minutos de estudo.', 'prata', NULL),
  ('estudo_240', 'Estude 4 horas', 'Quatro horas totais: você está investindo pesado.', 'Clock', 'estudo_min', 240, 34, 1, 'Quatro horas', 'Acumule 240 minutos de estudo via aulas.', 'prata', NULL),
  ('atividades_5', 'Acerte 5 atividades', 'Cinco atividades aprovadas — prática consistente.', 'Target', 'atividades_ok', 5, 35, 1, 'Cinco no alvo', 'Complete 5 atividades com aprovação.', 'prata', NULL),
  ('atividades_8', 'Acerte 8 atividades', 'Oito atividades no currículo prático.', 'Target', 'atividades_ok', 8, 36, 1, 'Oito certeiras', 'Complete 8 atividades com aprovação.', 'prata', NULL),
  ('roleplays_3', 'Conclua 3 roleplays', 'Três simulações concluídas: você já conversa bem com a IA.', 'Swords', 'roleplays_ok', 3, 37, 1, 'Trinca de rounds', 'Conclua 3 roleplays até o fim.', 'prata', NULL),
  ('roleplays_5', 'Conclua 5 roleplays', 'Cinco roleplays: prática deliberada em ação.', 'Swords', 'roleplays_ok', 5, 38, 1, 'Cinco rounds', 'Conclua 5 roleplays no total.', 'prata', NULL),
  ('nota_85', 'Tire 85% ou mais', 'Nota excelente em atividade ou roleplay.', 'Medal', 'nota_min', 85, 39, 1, 'Quase perfeito', 'Obtenha nota ≥ 85% em uma atividade ou roleplay.', 'prata', NULL),
  ('nota_90', 'Tire 90% ou mais', 'Nota de elite: nove em dez (ou mais).', 'Award', 'nota_min', 90, 40, 1, 'Nota elite', 'Obtenha nota ≥ 90% em uma atividade ou roleplay.', 'prata', NULL),
  ('nota_100', 'Tire 100%', 'Nota máxima — zero erros, zero arrependimentos.', 'Gem', 'nota_100', 1, 41, 1, 'Nota 10!', 'Obtenha 100% em uma atividade ou roleplay.', 'prata', NULL),
  ('nivel_3', 'Alcance o nível 3', 'Nível 3 desbloqueado — a jornada acelera.', 'Rocket', 'nivel', 3, 42, 1, 'Nível 3', 'Acumule XP até chegar ao nível 3.', 'prata', NULL),
  ('nivel_4', 'Alcance o nível 4', 'Quarto nível: você já é referência entre iniciantes.', 'Rocket', 'nivel', 4, 43, 1, 'Nível 4', 'Chegue ao nível 4 acumulando XP.', 'prata', NULL),
  ('curso_completo', 'Emita 1 certificado', 'Primeiro certificado no portal — curso concluído de verdade.', 'GraduationCap', 'certificados', 1, 44, 1, 'Diploma na mão', 'Conclua um curso e emita seu primeiro certificado.', 'prata', NULL),
  ('cursos_avaliados_2', 'Avalie 2 cursos', 'Dois feedbacks enviados — comunidade agradece.', 'Heart', 'cursos_avaliados', 2, 45, 1, 'Dupla opinião', 'Avalie 2 cursos diferentes no portal.', 'prata', NULL),
  ('cursos_avaliados_3', 'Avalie 3 cursos', 'Três avaliações: você ajuda a elevar a qualidade.', 'Heart', 'cursos_avaliados', 3, 46, 1, 'Crítico amigável', 'Avalie 3 cursos no portal.', 'prata', NULL),
  ('xp_750', 'Acumule 750 XP', 'Setecentos e cinquenta XP — quase mil!', 'Star', 'xp_total', 750, 47, 1, 'Quase mil', 'Some XP até atingir 750 no total.', 'prata', NULL),
  ('aulas_30', 'Conclua 30 aulas', 'Trinta aulas: marca de quem não desiste.', 'BookOpen', 'aulas_concluidas', 30, 48, 1, 'Trinta e firme', 'Conclua 30 aulas no total.', 'prata', NULL),
  ('streak_10', '10 dias de streak', 'Dez dias consecutivos — disciplina de atleta.', 'Flame', 'streak', 10, 49, 1, 'Dez dias fire', 'Cumprir 10 dias consecutivos de agenda.', 'prata', NULL),
  ('atividades_10', 'Acerte 10 atividades', 'Dez atividades aprovadas — domínio prático.', 'Target', 'atividades_ok', 10, 50, 1, 'Dez no alvo', 'Complete 10 atividades com aprovação.', 'prata', NULL),

  -- ===== OURO (~25) — avançado =====
  ('xp_1000', 'Acumule 1000 XP', 'Mil XP: placa de ouro no placar.', 'Trophy', 'xp_total', 1000, 51, 1, 'Mil no placar', 'Acumule 1000 pontos de experiência.', 'ouro', NULL),
  ('xp_1500', 'Acumule 1500 XP', 'Mil e quinhentos XP — você está voando.', 'Star', 'xp_total', 1500, 52, 1, 'XP turbo', 'Continue até somar 1500 XP no total.', 'ouro', NULL),
  ('xp_2000', 'Acumule 2000 XP', 'Dois mil XP: status de veterano no portal.', 'Crown', 'xp_total', 2000, 53, 1, 'Veterano XP', 'Acumule 2000 pontos de experiência.', 'ouro', NULL),
  ('aulas_40', 'Conclua 40 aulas', 'Quarenta aulas — currículo de quem estuda de verdade.', 'BookOpen', 'aulas_concluidas', 40, 54, 1, 'Quarenta aulas', 'Conclua 40 aulas no total.', 'ouro', NULL),
  ('aulas_50', 'Conclua 50 aulas', 'Cinquenta aulas: meio século de aprendizados.', 'BookOpen', 'aulas_concluidas', 50, 55, 1, 'Meio século', 'Conclua 50 aulas no portal.', 'ouro', NULL),
  ('aulas_60', 'Conclua 60 aulas', 'Sessenta aulas no histórico — elite estudantil.', 'BookOpen', 'aulas_concluidas', 60, 56, 1, 'Sessenta firmes', 'Conclua 60 aulas no total.', 'ouro', NULL),
  ('streak_14', '14 dias de streak', 'Duas semanas seguidas sem quebrar. Lendário quase.', 'Flame', 'streak', 14, 57, 1, 'Duas semanas', 'Cumprir 14 dias consecutivos de agenda.', 'ouro', NULL),
  ('streak_21', '21 dias de streak', 'Três semanas: o hábito já é identidade.', 'Calendar', 'streak', 21, 58, 1, 'Hábito de aço', 'Cumprir 21 dias consecutivos de agenda.', 'ouro', NULL),
  ('estudo_360', 'Estude 6 horas', 'Seis horas acumuladas de estudo profundo.', 'Clock', 'estudo_min', 360, 59, 1, 'Seis horas', 'Acumule 360 minutos de estudo via aulas.', 'ouro', NULL),
  ('estudo_480', 'Estude 8 horas', 'Oito horas totais — jornada de um dia de foco.', 'Clock', 'estudo_min', 480, 60, 1, 'Jornada de 8h', 'Acumule 480 minutos de estudo.', 'ouro', NULL),
  ('estudo_600', 'Estude 10 horas', 'Dez horas no cronômetro: compromisso sério.', 'Clock', 'estudo_min', 600, 61, 1, 'Dez horas', 'Acumule 600 minutos de estudo pelas aulas.', 'ouro', NULL),
  ('atividades_15', 'Acerte 15 atividades', 'Quinze atividades aprovadas — domínio técnico.', 'Target', 'atividades_ok', 15, 62, 1, 'Quinze certeiras', 'Complete 15 atividades com aprovação.', 'ouro', NULL),
  ('atividades_20', 'Acerte 20 atividades', 'Vinte atividades no currículo prático.', 'Target', 'atividades_ok', 20, 63, 1, 'Vinte no alvo', 'Complete 20 atividades com aprovação.', 'ouro', NULL),
  ('roleplays_8', 'Conclua 8 roleplays', 'Oito simulações: fluência em cenários reais.', 'Swords', 'roleplays_ok', 8, 64, 1, 'Oito rounds', 'Conclua 8 roleplays até o fim.', 'ouro', NULL),
  ('roleplays_10', 'Conclua 10 roleplays', 'Dez roleplays — você treina como profissional.', 'Swords', 'roleplays_ok', 10, 65, 1, 'Dez rounds', 'Conclua 10 roleplays no total.', 'ouro', NULL),
  ('roleplays_12', 'Conclua 12 roleplays', 'Doze simulações concluídas: maestria em diálogo.', 'Swords', 'roleplays_ok', 12, 66, 1, 'Dúzia de rounds', 'Conclua 12 roleplays no total.', 'ouro', NULL),
  ('nivel_5', 'Alcance o nível 5', 'Nível 5: você entrou no clube dos avançados.', 'Rocket', 'nivel', 5, 67, 1, 'Nível 5', 'Acumule XP até chegar ao nível 5.', 'ouro', NULL),
  ('nivel_6', 'Alcance o nível 6', 'Sexto nível — poucos chegam aqui tão rápido.', 'Rocket', 'nivel', 6, 68, 1, 'Nível 6', 'Chegue ao nível 6 acumulando XP.', 'ouro', NULL),
  ('nivel_7', 'Alcance o nível 7', 'Nível 7 desbloqueado: status de referência.', 'Mountain', 'nivel', 7, 69, 1, 'Nível 7', 'Acumule XP suficiente para o nível 7.', 'ouro', NULL),
  ('certificados_2', 'Emita 2 certificados', 'Dois certificados no bolso — polímata em formação.', 'GraduationCap', 'certificados', 2, 70, 1, 'Dupla certificação', 'Conclua e emita certificados de 2 cursos.', 'ouro', NULL),
  ('certificados_3', 'Emita 3 certificados', 'Três certificados: portfólio de aprendizagens.', 'GraduationCap', 'certificados', 3, 71, 1, 'Trinca de diplomas', 'Emita 3 certificados de cursos concluídos.', 'ouro', NULL),
  ('cursos_avaliados_5', 'Avalie 5 cursos', 'Cinco avaliações enviadas — voz ativa na comunidade.', 'Heart', 'cursos_avaliados', 5, 72, 1, 'Voz da galera', 'Avalie 5 cursos no portal.', 'ouro', NULL),
  ('aulas_75', 'Conclua 75 aulas', 'Setenta e cinco aulas — quase cem no placar.', 'BookOpen', 'aulas_concluidas', 75, 73, 1, 'Setenta e cinco', 'Conclua 75 aulas no total.', 'ouro', NULL),
  ('xp_2500', 'Acumule 2500 XP', 'Dois mil e quinhentos XP: elite do placar.', 'Gem', 'xp_total', 2500, 74, 1, 'Elite XP', 'Acumule 2500 pontos de experiência.', 'ouro', NULL),
  ('streak_30', '30 dias de streak', 'Um mês inteiro sem quebrar. Disciplina pura.', 'Flame', 'streak', 30, 75, 1, 'Mês blindado', 'Cumprir 30 dias consecutivos de agenda.', 'ouro', NULL),

  -- ===== LENDÁRIO (~25) — mais difíceis =====
  ('xp_3000', 'Acumule 3000 XP', 'Três mil XP: você é lenda viva do portal.', 'Crown', 'xp_total', 3000, 76, 1, 'Lenda do XP', 'Acumule 3000 pontos de experiência.', 'lendario', NULL),
  ('xp_5000', 'Acumule 5000 XP', 'Cinco mil XP — poucos alcançam este cume.', 'Crown', 'xp_total', 5000, 77, 1, 'Cume do XP', 'Continue até somar 5000 XP no total.', 'lendario', NULL),
  ('xp_8000', 'Acumule 8000 XP', 'Oito mil XP: status mítico no ranking.', 'Gem', 'xp_total', 8000, 78, 1, 'XP mítico', 'Acumule 8000 pontos de experiência.', 'lendario', NULL),
  ('xp_10000', 'Acumule 10000 XP', 'Dez mil XP — o Everest do placar.', 'Mountain', 'xp_total', 10000, 79, 1, 'Everest XP', 'Acumule 10000 pontos de experiência no total.', 'lendario', NULL),
  ('aulas_100', 'Conclua 100 aulas', 'Cem aulas concluídas: maratona intelectual.', 'BookOpen', 'aulas_concluidas', 100, 80, 1, 'Centena de aulas', 'Conclua 100 aulas no portal.', 'lendario', NULL),
  ('aulas_150', 'Conclua 150 aulas', 'Cento e cinquenta aulas — currículo de mestre.', 'BookOpen', 'aulas_concluidas', 150, 81, 1, 'Mestre das aulas', 'Conclua 150 aulas no total.', 'lendario', NULL),
  ('aulas_200', 'Conclua 200 aulas', 'Duzentas aulas: enciclopédia ambulante.', 'Brain', 'aulas_concluidas', 200, 82, 1, 'Enciclopédia', 'Conclua 200 aulas no portal.', 'lendario', NULL),
  ('streak_45', '45 dias de streak', 'Quarenta e cinco dias: constância sobre-humana.', 'Flame', 'streak', 45, 83, 1, 'Quase invencível', 'Cumprir 45 dias consecutivos de agenda.', 'lendario', NULL),
  ('streak_60', '60 dias de streak', 'Dois meses seguidos — a chama nunca apagou.', 'Flame', 'streak', 60, 84, 1, 'Chama eterna', 'Cumprir 60 dias consecutivos de agenda.', 'lendario', NULL),
  ('streak_90', '90 dias de streak', 'Noventa dias: um trimestre de disciplina pura.', 'Calendar', 'streak', 90, 85, 1, 'Trimestre fire', 'Cumprir 90 dias consecutivos de agenda.', 'lendario', NULL),
  ('streak_100', '100 dias de streak', 'Cem dias seguidos — conquista lendária absoluta.', 'Shield', 'streak', 100, 86, 1, 'Centena de fogo', 'Cumprir 100 dias consecutivos de agenda sem quebrar.', 'lendario', NULL),
  ('estudo_900', 'Estude 15 horas', 'Quinze horas acumuladas de estudo profundo.', 'Clock', 'estudo_min', 900, 87, 1, 'Quinze horas', 'Acumule 900 minutos de estudo via aulas.', 'lendario', NULL),
  ('estudo_1200', 'Estude 20 horas', 'Vinte horas no cronômetro — jornada épica.', 'Clock', 'estudo_min', 1200, 88, 1, 'Vinte horas', 'Acumule 1200 minutos de estudo.', 'lendario', NULL),
  ('estudo_1800', 'Estude 30 horas', 'Trinta horas totais: dedicação de campeão.', 'Clock', 'estudo_min', 1800, 89, 1, 'Trinta horas', 'Acumule 1800 minutos de estudo pelas aulas.', 'lendario', NULL),
  ('estudo_3000', 'Estude 50 horas', 'Cinquenta horas de estudo — título de lenda.', 'Mountain', 'estudo_min', 3000, 90, 1, 'Cinquenta horas', 'Acumule 3000 minutos de estudo no total.', 'lendario', NULL),
  ('atividades_30', 'Acerte 30 atividades', 'Trinta atividades aprovadas — domínio absoluto.', 'Target', 'atividades_ok', 30, 91, 1, 'Trinta no alvo', 'Complete 30 atividades com aprovação.', 'lendario', NULL),
  ('atividades_50', 'Acerte 50 atividades', 'Cinquenta atividades: ninja das avaliações.', 'Target', 'atividades_ok', 50, 92, 1, 'Ninja das provas', 'Complete 50 atividades com aprovação.', 'lendario', NULL),
  ('roleplays_20', 'Conclua 20 roleplays', 'Vinte simulações: orador nato do portal.', 'Swords', 'roleplays_ok', 20, 93, 1, 'Vinte rounds', 'Conclua 20 roleplays até o fim.', 'lendario', NULL),
  ('roleplays_30', 'Conclua 30 roleplays', 'Trinta roleplays — lenda das simulações.', 'Swords', 'roleplays_ok', 30, 94, 1, 'Lenda dos rounds', 'Conclua 30 roleplays no total.', 'lendario', NULL),
  ('nivel_10', 'Alcance o nível 10', 'Nível 10: marco lendário de progressão.', 'Crown', 'nivel', 10, 95, 1, 'Nível 10', 'Acumule XP até chegar ao nível 10.', 'lendario', NULL),
  ('nivel_15', 'Alcance o nível 15', 'Nível 15 — poucos enxergam este horizonte.', 'Mountain', 'nivel', 15, 96, 1, 'Nível 15', 'Chegue ao nível 15 acumulando XP.', 'lendario', NULL),
  ('nivel_20', 'Alcance o nível 20', 'Nível 20: o topo da montanha de XP.', 'Gem', 'nivel', 20, 97, 1, 'Nível 20', 'Acumule XP suficiente para o nível 20.', 'lendario', NULL),
  ('certificados_5', 'Emita 5 certificados', 'Cinco certificados: portfólio de mestre.', 'GraduationCap', 'certificados', 5, 98, 1, 'Cinco diplomas', 'Emita 5 certificados de cursos concluídos.', 'lendario', NULL),
  ('certificados_10', 'Emita 10 certificados', 'Dez certificados — biblioteca de conquistas.', 'GraduationCap', 'certificados', 10, 99, 1, 'Dez diplomas', 'Emita 10 certificados no portal.', 'lendario', NULL),
  ('cursos_avaliados_10', 'Avalie 10 cursos', 'Dez avaliações: guardião da qualidade do portal.', 'Heart', 'cursos_avaliados', 10, 100, 1, 'Guardião do feedback', 'Avalie 10 cursos diferentes no portal.', 'lendario', NULL)
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
  raridade = VALUES(raridade),
  badge_url = VALUES(badge_url);
