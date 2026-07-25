-- Meta / Facebook / Instagram por escola (tokens em escola_integracoes)
-- Cole no phpMyAdmin. Ignore "Duplicate column" se a coluna já existir.

ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_fb_ativo` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_ig_ativo` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_page_id` VARCHAR(64) NULL DEFAULT NULL;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_page_name` VARCHAR(191) NULL DEFAULT NULL;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_ig_user_id` VARCHAR(64) NULL DEFAULT NULL;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_ig_username` VARCHAR(191) NULL DEFAULT NULL;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_page_token` TEXT NULL DEFAULT NULL;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_token_expires_at` DATETIME NULL DEFAULT NULL;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_webhook_token` VARCHAR(64) NULL DEFAULT NULL;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `meta_conectado_em` DATETIME NULL DEFAULT NULL;
