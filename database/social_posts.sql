-- Agenda / fila de posts sociais (Facebook + Instagram)
-- Cole no phpMyAdmin após escola_integracoes_meta.sql

CREATE TABLE IF NOT EXISTS `social_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT NOT NULL,
  `canais` VARCHAR(32) NOT NULL DEFAULT 'ambos' COMMENT 'facebook|instagram|ambos',
  `caption` TEXT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'rascunho' COMMENT 'rascunho|agendado|publicando|publicado|erro|cancelado',
  `agendado_em` DATETIME NULL DEFAULT NULL,
  `publicado_em` DATETIME NULL DEFAULT NULL,
  `fb_post_id` VARCHAR(64) NULL DEFAULT NULL,
  `ig_media_id` VARCHAR(64) NULL DEFAULT NULL,
  `erro_msg` VARCHAR(500) NULL DEFAULT NULL,
  `created_by` INT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_social_posts_admin_status` (`id_admin`, `status`),
  KEY `idx_social_posts_agendado` (`status`, `agendado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `social_post_midias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_post` INT UNSIGNED NOT NULL,
  `id_admin` INT NOT NULL,
  `tipo` VARCHAR(16) NOT NULL DEFAULT 'image' COMMENT 'image|video',
  `path_local` VARCHAR(500) NULL DEFAULT NULL,
  `url_externa` VARCHAR(1000) NULL DEFAULT NULL,
  `mime` VARCHAR(100) NULL DEFAULT NULL,
  `bytes` INT UNSIGNED NULL DEFAULT NULL,
  `ordem` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_social_midia_post` (`id_post`),
  KEY `idx_social_midia_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
