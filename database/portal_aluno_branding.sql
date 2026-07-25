-- Portal do aluno — branding global (logo + fundo do login)
-- Cole no phpMyAdmin. Idempotente.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS portal_aluno_branding (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  logo VARCHAR(255) NULL DEFAULT NULL COMMENT 'basename em uploads/img/portal/',
  login_hero VARCHAR(255) NULL DEFAULT NULL COMMENT 'basename em uploads/img/portal/',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO portal_aluno_branding (id, logo, login_hero)
VALUES (1, NULL, NULL)
ON DUPLICATE KEY UPDATE id = id;
