-- LMS Conquistas v3 — raridade secreto, snapshot de ranking, +50 conquistas NOVAS
-- NÃO altera badge_url / artes das medalhas já cadastradas (só INSERT de slugs novos).
-- Pré-requisito: lms_conquistas.sql + lms_conquistas_v2.sql
-- (Arquivo de referência no repo; se já rodou no banco, não precisa rodar de novo.)

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

ALTER TABLE lms_conquistas_def
  MODIFY COLUMN raridade ENUM('bronze','prata','ouro','lendario','secreto') NOT NULL DEFAULT 'bronze';

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
