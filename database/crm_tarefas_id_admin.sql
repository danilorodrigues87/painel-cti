-- Isolamento multi-tenant para CRM Tarefas
ALTER TABLE crm_tarefas_listas
ADD COLUMN id_admin INT UNSIGNED NOT NULL DEFAULT 1 AFTER id;

UPDATE crm_tarefas_listas SET id_admin = 1 WHERE id_admin = 0;

CREATE INDEX idx_crm_tarefas_listas_admin ON crm_tarefas_listas(id_admin);
