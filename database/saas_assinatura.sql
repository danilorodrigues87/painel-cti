-- Master fase 2 — cobrança de assinatura SaaS (PIX CTI)
-- Execute no phpMyAdmin. Ignore "duplicate column" se já existir.

ALTER TABLE planos_assinatura
  ADD COLUMN valor_mensal DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER descricao;

ALTER TABLE escolas_assinantes
  ADD COLUMN dia_vencimento_assinatura TINYINT UNSIGNED NOT NULL DEFAULT 10;

ALTER TABLE escolas_assinantes
  ADD COLUMN assinatura_status VARCHAR(20) NOT NULL DEFAULT 'ativa';

ALTER TABLE escolas_assinantes
  ADD COLUMN assinatura_proximo_vencimento DATE NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS saas_faturas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NULL DEFAULT NULL,
  competencia CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  valor DECIMAL(10,2) NOT NULL,
  vencimento DATE NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'aberta' COMMENT 'aberta,pago,cancelada,vencida',
  mp_payment_id VARCHAR(64) NULL DEFAULT NULL,
  pix_copia_cola TEXT NULL,
  pago_em DATETIME NULL DEFAULT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_saas_escola_comp (id_admin, competencia),
  KEY idx_saas_status_venc (status, vencimento),
  KEY idx_saas_mp (mp_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
