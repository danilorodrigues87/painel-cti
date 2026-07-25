-- Bunny Stream por escola (credenciais em escola_integracoes)
-- Cole no phpMyAdmin. Ignore "Duplicate column" se a coluna já existir.

ALTER TABLE `escola_integracoes`
  ADD COLUMN `bunny_ativo` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `bunny_library_id` VARCHAR(32) NULL DEFAULT NULL;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `bunny_cdn_hostname` VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `bunny_api_key` TEXT NULL DEFAULT NULL;

ALTER TABLE `escola_integracoes`
  ADD COLUMN `bunny_token_key` TEXT NULL DEFAULT NULL;
