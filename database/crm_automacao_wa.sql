-- CRM Fase 5+: templates editáveis de WhatsApp automático ao mudar status do lead (por escola).
-- Execute no phpMyAdmin após backup.

ALTER TABLE escola_integracoes
  ADD COLUMN crm_wa_automacao_ativo TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Master: envia WA ao mudar status CRM',
  ADD COLUMN crm_wa_enviar_novo TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN crm_wa_enviar_em_atendimento TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN crm_wa_enviar_matriculado TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN crm_wa_msg_novo TEXT NULL DEFAULT NULL,
  ADD COLUMN crm_wa_msg_em_atendimento TEXT NULL DEFAULT NULL,
  ADD COLUMN crm_wa_msg_matriculado TEXT NULL DEFAULT NULL;
