-- Conquistas / metas do portal EAD

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS lms_conquistas_def (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(64) NOT NULL,
  titulo VARCHAR(120) NOT NULL,
  descricao VARCHAR(255) NOT NULL DEFAULT '',
  icone VARCHAR(64) NOT NULL DEFAULT 'Trophy',
  meta_tipo VARCHAR(32) NOT NULL,
  meta_valor INT UNSIGNED NOT NULL DEFAULT 1,
  ordem INT UNSIGNED NOT NULL DEFAULT 0,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lms_conq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lms_conquistas_aluno (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  id_aluno INT UNSIGNED NOT NULL,
  slug VARCHAR(64) NOT NULL,
  progresso INT UNSIGNED NOT NULL DEFAULT 0,
  meta INT UNSIGNED NOT NULL DEFAULT 1,
  unlocked_at DATETIME NULL DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lms_conq_aluno (id_aluno, slug),
  KEY idx_lms_conq_admin_aluno (id_admin, id_aluno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO lms_conquistas_def (slug, titulo, descricao, icone, meta_tipo, meta_valor, ordem, ativo)
VALUES
  ('primeira_aula', 'Primeira aula', 'Conclua sua primeira aula no portal', 'Sparkles', 'aulas_concluidas', 1, 10, 1),
  ('xp_100', '100 XP', 'Acumule 100 pontos de experiência', 'Star', 'xp_total', 100, 20, 1),
  ('nota_80', 'Nota alta', 'Tire 80% ou mais em uma atividade ou roleplay', 'Award', 'nota_min', 80, 30, 1),
  ('streak_3', '3 sessões seguidas', 'Cumpra 3 dias de agenda consecutivos', 'Flame', 'streak', 3, 40, 1),
  ('estudo_60', '1 hora de estudo', 'Some 60 minutos nas aulas concluídas', 'Clock', 'estudo_min', 60, 50, 1),
  ('xp_500', '500 XP', 'Acumule 500 pontos de experiência', 'Trophy', 'xp_total', 500, 60, 1),
  ('streak_7', '7 sessões seguidas', 'Cumpra 7 dias de agenda consecutivos', 'Calendar', 'streak', 7, 70, 1),
  ('curso_completo', 'Curso concluído', 'Emita seu primeiro certificado no portal', 'GraduationCap', 'certificados', 1, 80, 1),
  ('xp_2000', '2000 XP', 'Acumule 2000 pontos de experiência', 'Crown', 'xp_total', 2000, 90, 1)
ON DUPLICATE KEY UPDATE
  titulo = VALUES(titulo),
  descricao = VALUES(descricao),
  icone = VALUES(icone),
  meta_tipo = VALUES(meta_tipo),
  meta_valor = VALUES(meta_valor),
  ordem = VALUES(ordem),
  ativo = VALUES(ativo);
