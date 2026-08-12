-- Catálogo CTI: cursos Master incluídos em planos SaaS
-- Execute no phpMyAdmin. Ignore "duplicate column" se já existir.

ALTER TABLE escolas_assinantes
  ADD COLUMN catalogo_cti TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = tenant do catálogo EAD CTI';

ALTER TABLE lms_cursos
  ADD COLUMN origem VARCHAR(16) NOT NULL DEFAULT 'escola' COMMENT 'escola|cti';

CREATE TABLE IF NOT EXISTS planos_cursos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  plan_id INT UNSIGNED NOT NULL,
  curso_id INT UNSIGNED NOT NULL,
  ordem INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_plano_curso (plan_id, curso_id),
  KEY idx_planos_cursos_curso (curso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lms_escola_cursos_cti (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL COMMENT 'escola assinante',
  curso_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NULL DEFAULT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  provisionado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_escola_curso_cti (id_admin, curso_id),
  KEY idx_escola_cti_ativo (id_admin, ativo),
  KEY idx_escola_cti_plan (plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
