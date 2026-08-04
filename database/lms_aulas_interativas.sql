-- Aulas interativas (L-Editor) — colar no phpMyAdmin após lms_ead.sql
-- Extende lms_aulas; cenas em lms_aula_cenas; progresso por aluno; token do editor.

ALTER TABLE `lms_aulas`
  ADD COLUMN `tipo_conteudo` ENUM('video','interativa') NOT NULL DEFAULT 'video'
    COMMENT 'video=clássico; interativa=cenas L-Editor'
    AFTER `bloqueado`,
  ADD COLUMN `voz_narracao` VARCHAR(32) NULL DEFAULT 'alloy' AFTER `tipo_conteudo`,
  ADD COLUMN `interativa_status` ENUM('rascunho','publicada') NOT NULL DEFAULT 'rascunho'
    AFTER `voz_narracao`;

CREATE TABLE IF NOT EXISTS `lms_aula_cenas` (
  `id` CHAR(36) NOT NULL,
  `id_aula` INT UNSIGNED NOT NULL,
  `id_admin` INT NOT NULL,
  `ordem` INT NOT NULL DEFAULT 0,
  `media_kind` ENUM('image','video') NOT NULL DEFAULT 'image',
  `media_url` VARCHAR(500) NOT NULL,
  `auto_advance` TINYINT(1) NOT NULL DEFAULT 0,
  `instrucao` TEXT NULL,
  `tone` ENUM('light','dark') NOT NULL DEFAULT 'light',
  `interacao` JSON NOT NULL,
  `narracao_url` VARCHAR(500) NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cena_ordem` (`id_aula`, `ordem`),
  KEY `idx_cena_aula` (`id_aula`),
  KEY `idx_cena_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_aula_interativa_progresso` (
  `id_aluno` INT NOT NULL,
  `id_aula` INT UNSIGNED NOT NULL,
  `passo` INT NOT NULL DEFAULT 0,
  `max_passo` INT NOT NULL DEFAULT 0,
  `concluida` TINYINT(1) NOT NULL DEFAULT 0,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_aluno`, `id_aula`),
  KEY `idx_prog_aula` (`id_aula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_editor_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token_hash` CHAR(64) NOT NULL,
  `id_admin` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  `id_curso` INT UNSIGNED NULL,
  `expira_em` DATETIME NOT NULL,
  `usado_em` DATETIME NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_editor_token` (`token_hash`),
  KEY `idx_editor_token_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
