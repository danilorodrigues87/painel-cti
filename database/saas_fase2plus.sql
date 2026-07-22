-- Master SaaS fase 2+
-- Colar no phpMyAdmin após saas_assinatura.sql

-- Valor mensal por escola (cobrança mesmo sem plan_id, se > 0)
ALTER TABLE escolas_assinantes
  ADD COLUMN valor_mensal_custom DECIMAL(10,2) NULL DEFAULT NULL
    COMMENT 'Override de preço; se >0 cobra (inclui personalizado)' AFTER plan_id;

-- Fim do período trial (status assinatura_status=trial)
ALTER TABLE escolas_assinantes
  ADD COLUMN trial_ate DATE NULL DEFAULT NULL
    COMMENT 'Último dia do trial; após isso o worker cobra' AFTER assinatura_status;

-- Evita reenviar e-mail a cada rodada do worker
ALTER TABLE saas_faturas
  ADD COLUMN email_enviado_em DATETIME NULL DEFAULT NULL AFTER pix_qr_base64;
