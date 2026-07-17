-- Intervalo mínimo entre envios de campanha WhatsApp para grupos/listas (segundos)
ALTER TABLE escola_integracoes
  ADD COLUMN whatsapp_grupo_delay_segundos INT UNSIGNED NOT NULL DEFAULT 3600
  AFTER whatsapp_max_hora;
