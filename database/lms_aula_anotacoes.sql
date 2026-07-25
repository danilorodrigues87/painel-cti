-- Anotações privadas do aluno por aula (portal Ascend)
-- Colar no phpMyAdmin após lms_aula_comentarios.sql
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `lms_aula_anotacoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT UNSIGNED NOT NULL,
  `id_aluno` INT UNSIGNED NOT NULL,
  `id_aula` INT UNSIGNED NOT NULL,
  `id_curso` INT UNSIGNED NULL DEFAULT NULL,
  `texto` MEDIUMTEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lms_anot_aluno_aula` (`id_aluno`, `id_aula`),
  KEY `idx_lms_anot_admin` (`id_admin`),
  KEY `idx_lms_anot_aula` (`id_aula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
