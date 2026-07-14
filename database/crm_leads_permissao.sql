-- Permissões e privacidade dos leads do CRM
-- Execute este script no banco de dados do painel-cti

ALTER TABLE `crm_leads`
  ADD COLUMN `usuario_id` INT UNSIGNED DEFAULT NULL COMMENT 'Usuário que cadastrou ou assumiu o lead' AFTER `id_admin`,
  ADD COLUMN `visibilidade` VARCHAR(20) NOT NULL DEFAULT 'publico' COMMENT 'publico ou privado' AFTER `usuario_id`,
  ADD KEY `idx_crm_leads_usuario` (`usuario_id`),
  ADD KEY `idx_crm_leads_visibilidade` (`visibilidade`);

-- Leads existentes ficam públicos; ajuste manualmente se necessário
UPDATE `crm_leads` SET `visibilidade` = 'publico' WHERE `visibilidade` IS NULL OR `visibilidade` = '';
