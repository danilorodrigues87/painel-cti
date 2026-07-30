-- Agent API (OpenClaw / automações) — Fase 1
-- Colar no phpMyAdmin (banco do painel).
-- Depois: também executar database/agent_escola_config.sql (LLM + Telegram por escola).

CREATE TABLE IF NOT EXISTS `agent_api_keys` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(120) NOT NULL DEFAULT '',
  `escopo` ENUM('master','escola') NOT NULL,
  `id_admin` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = chave Master',
  `key_prefix` VARCHAR(16) NOT NULL,
  `key_hash` VARCHAR(255) NOT NULL,
  `scopes` JSON NULL COMMENT 'ex: ["read:all"] ou scopes granulares',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_uso_em` DATETIME NULL DEFAULT NULL,
  `rate_window_start` DATETIME NULL DEFAULT NULL,
  `rate_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revogado_em` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_agent_keys_prefix` (`key_prefix`),
  KEY `idx_agent_keys_escopo_admin` (`escopo`, `id_admin`),
  KEY `idx_agent_keys_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `agent_api_audit` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_key` INT UNSIGNED NULL DEFAULT NULL,
  `escopo` VARCHAR(16) NULL,
  `id_admin` INT UNSIGNED NULL DEFAULT NULL,
  `method` VARCHAR(10) NOT NULL DEFAULT 'GET',
  `path` VARCHAR(255) NOT NULL DEFAULT '',
  `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 200,
  `ip` VARCHAR(45) NULL DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agent_audit_key` (`id_key`),
  KEY `idx_agent_audit_criado` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
