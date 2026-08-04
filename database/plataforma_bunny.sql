-- Bunny global (Master) — Stream + Storage para todas as escolas
-- Cole no phpMyAdmin. Credenciais únicas da plataforma (não por escola).

CREATE TABLE IF NOT EXISTS `plataforma_bunny` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `stream_ativo` TINYINT(1) NOT NULL DEFAULT 0,
  `stream_library_id` VARCHAR(32) NULL DEFAULT NULL,
  `stream_cdn_hostname` VARCHAR(255) NULL DEFAULT NULL,
  `stream_api_key` TEXT NULL DEFAULT NULL COMMENT 'AccessKey conta (criptografada)',
  `stream_token_key` TEXT NULL DEFAULT NULL COMMENT 'Token Auth da library (criptografada)',
  `storage_ativo` TINYINT(1) NOT NULL DEFAULT 0,
  `storage_zone` VARCHAR(128) NULL DEFAULT NULL COMMENT 'ex: cti-escola-midias',
  `storage_access_key` TEXT NULL DEFAULT NULL COMMENT 'Access Key API/HTTP da zone (criptografada)',
  `storage_endpoint` VARCHAR(255) NULL DEFAULT 'storage.bunnycdn.com',
  `storage_cdn_hostname` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Pull Zone CDN (ex: xxx.b-cdn.net)',
  `storage_token_key` TEXT NULL DEFAULT NULL COMMENT 'Token Auth do Pull Zone (opcional, criptografada)',
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `plataforma_bunny` (`id`) VALUES (1);

-- Cenas interativas: URLs longas (CDN) + video_id Stream
ALTER TABLE `lms_aula_cenas`
  MODIFY COLUMN `media_url` TEXT NOT NULL;

ALTER TABLE `lms_aula_cenas`
  MODIFY COLUMN `narracao_url` TEXT NULL;

ALTER TABLE `lms_aula_cenas`
  ADD COLUMN `media_bunny_video_id` VARCHAR(64) NULL DEFAULT NULL
    COMMENT 'GUID Bunny Stream quando media_kind=video'
    AFTER `media_url`;
