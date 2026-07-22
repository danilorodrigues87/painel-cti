-- Agenda avulsa (reposição) + cota diária do portal LMS
-- Colar no phpMyAdmin (produção e local)

CREATE TABLE IF NOT EXISTS `agenda_avulso` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT UNSIGNED NOT NULL,
  `id_aluno` INT UNSIGNED NOT NULL,
  `matricula_id` INT UNSIGNED NOT NULL,
  `id_trilha` INT UNSIGNED NOT NULL,
  `id_horario` INT UNSIGNED NOT NULL,
  `data` DATE NOT NULL,
  `aulas_cota` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `motivo` VARCHAR(255) DEFAULT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_por` INT UNSIGNED DEFAULT NULL,
  `data_cadastro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_avulso_aluno_data` (`id_admin`, `id_aluno`, `data`, `ativo`),
  KEY `idx_avulso_trilha` (`id_trilha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_sessao_cota` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT UNSIGNED NOT NULL,
  `id_aluno` INT UNSIGNED NOT NULL,
  `data` DATE NOT NULL,
  `aulas_ids` JSON NOT NULL,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cota_aluno_dia` (`id_admin`, `id_aluno`, `data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
