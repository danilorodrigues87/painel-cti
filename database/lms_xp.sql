-- XP / ranking LMS por escola (colar no phpMyAdmin / DB cti_admin)
-- Data: 2026-07-20

CREATE TABLE IF NOT EXISTS `lms_xp_ledger` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT NOT NULL,
  `id_aluno` INT NOT NULL,
  `fonte` VARCHAR(40) NOT NULL,
  `id_ref` VARCHAR(64) NOT NULL,
  `xp` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lms_xp_once` (`id_aluno`, `fonte`, `id_ref`),
  KEY `idx_lms_xp_admin` (`id_admin`, `id_aluno`),
  KEY `idx_lms_xp_rank` (`id_admin`, `xp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
