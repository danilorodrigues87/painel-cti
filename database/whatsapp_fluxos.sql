-- Fluxos do chatbot WhatsApp (Fase A)
-- Colar no phpMyAdmin.

CREATE TABLE IF NOT EXISTS `whatsapp_fluxos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(120) NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `prioridade` INT NOT NULL DEFAULT 100,
  `definicao` JSON NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wa_fluxos_admin` (`id_admin`, `ativo`, `prioridade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_fluxo_sessoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT UNSIGNED NOT NULL,
  `conversa_id` INT UNSIGNED NOT NULL,
  `fluxo_id` INT UNSIGNED NOT NULL,
  `node_atual` VARCHAR(64) NOT NULL DEFAULT '',
  `aguardando` TINYINT(1) NOT NULL DEFAULT 0,
  `variaveis` JSON NULL,
  `passos` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wa_fluxo_sessao_conversa` (`conversa_id`),
  KEY `idx_wa_fluxo_sessao_admin` (`id_admin`),
  KEY `idx_wa_fluxo_sessao_fluxo` (`fluxo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_fluxo_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT UNSIGNED NOT NULL,
  `conversa_id` INT UNSIGNED NOT NULL,
  `fluxo_id` INT UNSIGNED NOT NULL,
  `node_id` VARCHAR(64) NULL DEFAULT NULL,
  `evento` VARCHAR(40) NOT NULL,
  `detalhe` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wa_fluxo_log_conv` (`conversa_id`, `id`),
  KEY `idx_wa_fluxo_log_admin` (`id_admin`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
