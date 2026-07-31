-- Agente Telegram: IA opcional + offset poll
-- Se telegram_update_offset / tabela mensagens já existirem, ignore o erro dessas linhas.

ALTER TABLE `agent_escola_config`
  ADD COLUMN `telegram_update_offset` BIGINT NOT NULL DEFAULT 0
    COMMENT 'offset getUpdates (worker long-poll)';

ALTER TABLE `agent_escola_config`
  ADD COLUMN `telegram_ia_ativo` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1 = respostas livres com IA; 0 = só palavras-chave (sem tokens)';

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
