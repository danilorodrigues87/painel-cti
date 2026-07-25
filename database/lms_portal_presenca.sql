-- Presença do aluno no portal Ascend (online mesmo fora da aula)
-- Cole no phpMyAdmin. Idempotente.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lms_portal_presenca (
  id_aluno INT UNSIGNED NOT NULL,
  id_admin INT UNSIGNED NOT NULL,
  last_seen_at DATETIME NOT NULL,
  rota VARCHAR(120) NULL DEFAULT NULL,
  id_curso INT UNSIGNED NULL DEFAULT NULL,
  id_aula INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (id_aluno, id_admin),
  KEY idx_presenca_seen (id_admin, last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
