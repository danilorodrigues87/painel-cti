-- Ciclos de avaliação por aula (XAMPP + produção)
-- 3 tentativas por ciclo; se média < 70%, reassistir a aula libera +3.

ALTER TABLE `lms_progresso_aula`
  ADD COLUMN `ciclo` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `ultimo_acesso`,
  ADD COLUMN `precisa_revisar` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ciclo`,
  ADD COLUMN `nota_unidade` DECIMAL(5,2) NULL AFTER `precisa_revisar`,
  ADD COLUMN `unidade_aprovada` TINYINT(1) NOT NULL DEFAULT 0 AFTER `nota_unidade`;

ALTER TABLE `lms_atividade_tentativas`
  ADD COLUMN `ciclo` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `status`;

ALTER TABLE `lms_roleplay_sessoes`
  ADD COLUMN `ciclo` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `duration_seconds`;

-- Garante padrão de 3 tentativas nas atividades existentes
UPDATE `lms_atividades` SET `tentativas_max` = 3 WHERE `tentativas_max` IS NULL OR `tentativas_max` < 1 OR `tentativas_max` > 10;
