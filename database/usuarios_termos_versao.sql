-- Termos de uso: auditoria LGPD (versão + data do aceite)
-- Cole no phpMyAdmin. Seguro rodar mais de uma vez se as colunas já existirem (ajuste manual se der erro de duplicata).

ALTER TABLE `usuarios`
  ADD COLUMN `termos_aceito_em` DATETIME NULL DEFAULT NULL AFTER `termos_uso`,
  ADD COLUMN `termos_versao` VARCHAR(20) NULL DEFAULT NULL AFTER `termos_aceito_em`;
