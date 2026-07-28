-- Fase A produto social: biblioteca, histórico de publish, runs do worker
-- Cole no phpMyAdmin após social_posts.sql / social_posts_formato.sql

CREATE TABLE IF NOT EXISTS `social_biblioteca` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT NOT NULL,
  `titulo` VARCHAR(200) NULL DEFAULT NULL,
  `tipo` VARCHAR(16) NOT NULL DEFAULT 'image' COMMENT 'image|video',
  `path_local` VARCHAR(500) NULL DEFAULT NULL,
  `url_externa` VARCHAR(1000) NULL DEFAULT NULL,
  `mime` VARCHAR(100) NULL DEFAULT NULL,
  `bytes` INT UNSIGNED NULL DEFAULT NULL,
  `created_by` INT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_social_bib_admin` (`id_admin`, `tipo`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `social_publish_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT NOT NULL,
  `id_post` INT UNSIGNED NOT NULL,
  `origem` VARCHAR(20) NOT NULL DEFAULT 'worker' COMMENT 'cli|http|manual|cron',
  `status` VARCHAR(20) NOT NULL COMMENT 'ok|erro|parcial',
  `mensagem` VARCHAR(500) NULL DEFAULT NULL,
  `fb_post_id` VARCHAR(128) NULL DEFAULT NULL,
  `ig_media_id` VARCHAR(128) NULL DEFAULT NULL,
  `formato` VARCHAR(20) NULL DEFAULT NULL,
  `canais` VARCHAR(32) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_social_plog_admin` (`id_admin`, `created_at`),
  KEY `idx_social_plog_post` (`id_post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `social_worker_runs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `origem` VARCHAR(20) NOT NULL DEFAULT 'cli',
  `id_admin` INT NOT NULL DEFAULT 0 COMMENT '0=todas escolas',
  `processados` INT NOT NULL DEFAULT 0,
  `ok` INT NOT NULL DEFAULT 0,
  `erro` INT NOT NULL DEFAULT 0,
  `detalhe` VARCHAR(500) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_social_wrun` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
