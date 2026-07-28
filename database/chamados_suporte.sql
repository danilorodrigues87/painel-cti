-- Chamados de suporte (escola ↔ CTI / Master)
-- Colar no phpMyAdmin. Idempotente onde possível.

CREATE TABLE IF NOT EXISTS `chamados` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero` VARCHAR(24) NOT NULL,
  `id_admin` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `categoria` VARCHAR(32) NOT NULL DEFAULT 'duvida',
  `assunto` VARCHAR(160) NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'aberto',
  `prioridade` VARCHAR(16) NOT NULL DEFAULT 'normal',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fechado_em` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chamados_numero` (`numero`),
  KEY `idx_chamados_escola_status` (`id_admin`, `status`),
  KEY `idx_chamados_status_updated` (`status`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chamado_mensagens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `chamado_id` INT UNSIGNED NOT NULL,
  `autor_tipo` ENUM('escola','master') NOT NULL,
  `autor_id` INT UNSIGNED NOT NULL,
  `mensagem` TEXT NOT NULL,
  `anexo_path` VARCHAR(255) NULL DEFAULT NULL,
  `anexo_nome` VARCHAR(160) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chamado_mensagens_chamado` (`chamado_id`, `created_at`),
  CONSTRAINT `fk_chamado_mensagens_chamado`
    FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
