-- LMS EAD independente das trilhas + matrícula EAD + trilhas.ativo
-- Colar no phpMyAdmin após lms_ead.sql
-- Data: 2026-07-24

-- ========== Curso: título próprio; trilha opcional ==========
-- Se a coluna já existir, ignore o erro e siga.
ALTER TABLE `lms_cursos`
  ADD COLUMN `titulo` VARCHAR(255) NULL DEFAULT NULL AFTER `id_trilha`;

ALTER TABLE `lms_cursos`
  ADD COLUMN `carga_h` INT NULL DEFAULT NULL AFTER `titulo`;

ALTER TABLE `lms_cursos`
  MODIFY COLUMN `id_trilha` INT NULL DEFAULT NULL;

-- Preenche título/carga a partir da trilha (cursos legados)
UPDATE `lms_cursos` c
INNER JOIN `trilhas` t ON t.id = c.id_trilha
SET c.titulo = COALESCE(NULLIF(TRIM(c.titulo), ''), t.nome),
    c.carga_h = COALESCE(c.carga_h, t.carga_h)
WHERE c.id_trilha IS NOT NULL;

UPDATE `lms_cursos`
SET titulo = COALESCE(NULLIF(TRIM(titulo), ''), slug, CONCAT('Curso ', id))
WHERE titulo IS NULL OR TRIM(titulo) = '';

-- ========== Matrícula EAD (acesso ao portal sem carnê/contrato) ==========
CREATE TABLE IF NOT EXISTS `lms_matricula_ead` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT NOT NULL COMMENT 'Escola do aluno (tenant que matricula)',
  `id_aluno` INT NOT NULL,
  `id_curso` INT UNSIGNED NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `inicio` DATE NULL DEFAULT NULL,
  `fim` DATE NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT NULL DEFAULT NULL,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lms_mat_ead_aluno_curso` (`id_aluno`, `id_curso`),
  KEY `idx_lms_mat_ead_admin` (`id_admin`, `ativo`),
  KEY `idx_lms_mat_ead_curso` (`id_curso`, `ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migração: matrícula comercial ativa → acesso EAD no curso 1:1 da mesma trilha
INSERT IGNORE INTO `lms_matricula_ead` (`id_admin`, `id_aluno`, `id_curso`, `ativo`, `inicio`, `created_by`)
SELECT DISTINCT m.id_admin, m.id_aluno, c.id, 1, CURDATE(), NULL
FROM `matriculas` m
INNER JOIN `lms_cursos` c
  ON c.id_admin = m.id_admin
 AND c.id_trilha = m.id_trilha
 AND c.publicado = 1
WHERE m.status = 0
  AND (m.fim IS NULL OR m.fim >= CURDATE());

-- ========== Trilhas comerciais: ativo operacional ==========
ALTER TABLE `trilhas`
  ADD COLUMN `ativo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `site`;
