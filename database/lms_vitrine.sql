-- Vitrine de cursos entre escolas (licença + fatura SaaS)
-- Colar após lms_ead_independente.sql
-- Data: 2026-07-24

ALTER TABLE `lms_cursos`
  ADD COLUMN `vitrine_ativo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `publicado`,
  ADD COLUMN `vitrine_preco_mensal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `vitrine_ativo`,
  ADD COLUMN `vitrine_descricao` VARCHAR(500) NULL DEFAULT NULL AFTER `vitrine_preco_mensal`;

CREATE TABLE IF NOT EXISTS `lms_vitrine_config` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `taxa_cti_mensal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Taxa CTI fixa por licença ativa/mês',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lms_vitrine_config` (`id`, `taxa_cti_mensal`) VALUES (1, 0.00);

CREATE TABLE IF NOT EXISTS `lms_vitrine_assinaturas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_escola_assinante` INT NOT NULL,
  `id_escola_criadora` INT NOT NULL,
  `id_curso` INT UNSIGNED NOT NULL,
  `status` ENUM('ativa','cancelada') NOT NULL DEFAULT 'ativa',
  `inicio` DATE NOT NULL,
  `cancelada_em` DATE NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vitrine_escola_curso` (`id_escola_assinante`, `id_curso`),
  KEY `idx_vitrine_status` (`status`, `id_escola_assinante`),
  KEY `idx_vitrine_criadora` (`id_escola_criadora`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `saas_fatura_itens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_fatura` INT UNSIGNED NOT NULL,
  `tipo` VARCHAR(40) NOT NULL COMMENT 'plano_painel|licenca_curso|taxa_vitrine_cti',
  `descricao` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `id_curso` INT UNSIGNED NULL DEFAULT NULL,
  `id_vitrine_assinatura` INT UNSIGNED NULL DEFAULT NULL,
  `id_escola_criadora` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_saas_item_fat` (`id_fatura`),
  KEY `idx_saas_item_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_vitrine_repasses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_fatura` INT UNSIGNED NOT NULL,
  `id_escola_criadora` INT NOT NULL,
  `id_escola_assinante` INT NOT NULL,
  `id_curso` INT UNSIGNED NOT NULL,
  `competencia` CHAR(7) NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pendente','pago') NOT NULL DEFAULT 'pendente',
  `pago_em` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_repasse_criadora` (`id_escola_criadora`, `status`),
  KEY `idx_repasse_fat` (`id_fatura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
