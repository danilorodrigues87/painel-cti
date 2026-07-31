-- Agente Telegram nativo (Fase 2)
-- Colar no phpMyAdmin após database/agent_escola_config.sql

ALTER TABLE `agent_escola_config`
  ADD COLUMN `telegram_update_offset` BIGINT NOT NULL DEFAULT 0
    COMMENT 'offset getUpdates (worker long-poll)';

CREATE TABLE IF NOT EXISTS `agent_telegram_mensagens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT UNSIGNED NOT NULL,
  `chat_id` VARCHAR(64) NOT NULL,
  `role` ENUM('user','assistant') NOT NULL,
  `content` TEXT NOT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agent_tg_chat` (`id_admin`, `chat_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
