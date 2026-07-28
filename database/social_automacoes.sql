-- Automações Meta: keyword em comentário → DM (private reply)
-- Cole no phpMyAdmin após escola_integracoes_meta.sql

CREATE TABLE IF NOT EXISTS `social_automacoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT NOT NULL,
  `palavra_chave` VARCHAR(120) NOT NULL,
  `match_modo` VARCHAR(20) NOT NULL DEFAULT 'contem' COMMENT 'contem|exato|inicia',
  `mensagem_dm` TEXT NOT NULL,
  `canais` VARCHAR(32) NOT NULL DEFAULT 'ambos' COMMENT 'instagram|facebook|ambos',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_social_auto_admin` (`id_admin`, `ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `social_automacao_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT NOT NULL,
  `id_automacao` INT UNSIGNED NULL DEFAULT NULL,
  `comment_id` VARCHAR(128) NOT NULL,
  `canal` VARCHAR(20) NOT NULL DEFAULT 'instagram',
  `comentario_txt` VARCHAR(500) NULL DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'ok' COMMENT 'ok|erro|ignorado',
  `erro_msg` VARCHAR(500) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_social_auto_comment` (`comment_id`),
  KEY `idx_social_auto_log_admin` (`id_admin`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Liga/desliga automações na escola (além das regras ativas)
ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_auto_ativo` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1=processar keyword→DM no webhook'
    AFTER `meta_conectado_em`;
