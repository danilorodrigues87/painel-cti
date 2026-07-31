-- Fase 1: anti-ban WhatsApp + flag variar texto
-- Colar no phpMyAdmin. Se a coluna já existir, ignore o erro dessa linha.

ALTER TABLE `escola_integracoes`
  ADD COLUMN `whatsapp_variar_texto` TINYINT(1) NOT NULL DEFAULT 0
  COMMENT '1 = variar texto de campanha WA com IA compartilhada';

ALTER TABLE `campanha_fila`
  ADD COLUMN `mensagem_enviada` TEXT NULL
  COMMENT 'Texto efetivamente enviado (após variação)';
