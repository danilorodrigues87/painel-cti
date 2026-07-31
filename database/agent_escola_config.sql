-- Config do Assistente Telegram nativo por escola (token, allowlist, flags)
-- Fonte da chave LLM: escola_integracoes.ai_* (Configurações de IA)
-- Colar no phpMyAdmin

CREATE TABLE IF NOT EXISTS `agent_escola_config` (
  `id_admin` INT UNSIGNED NOT NULL,
  `agent_ativo` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'legado; bot nativo usa llm_ativo',
  `llm_ativo` TINYINT(1) NOT NULL DEFAULT 0,
  `llm_provider` VARCHAR(32) NULL DEFAULT NULL,
  `llm_model` VARCHAR(64) NULL DEFAULT NULL,
  `llm_api_key` TEXT NULL COMMENT 'CryptoHelper — espelho opcional da chave (fonte: escola_integracoes.ai_*)',
  `telegram_bot_token` TEXT NULL COMMENT 'CryptoHelper',
  `telegram_bot_username` VARCHAR(64) NULL DEFAULT NULL,
  `telegram_chat_id` VARCHAR(64) NULL DEFAULT NULL COMMENT 'opcional: chat/grupo autorizado',
  `telegram_notas` VARCHAR(255) NULL DEFAULT NULL,
  `atualizado_em` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
