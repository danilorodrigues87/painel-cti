-- Mercado Pago (PIX) por escola
-- Execute no phpMyAdmin. Ignore "duplicate column" se a coluna já existir.

ALTER TABLE escola_integracoes
  ADD COLUMN mp_ativo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE escola_integracoes
  ADD COLUMN mp_access_token TEXT NULL DEFAULT NULL;

ALTER TABLE escola_integracoes
  ADD COLUMN mp_webhook_secret VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE escola_integracoes
  ADD COLUMN mp_webhook_token VARCHAR(64) NULL DEFAULT NULL;
