-- Tabela de funis do CRM
CREATE TABLE IF NOT EXISTS crm_funis (
	id INT AUTO_INCREMENT PRIMARY KEY,
	id_admin INT NOT NULL,
	nome VARCHAR(100) NOT NULL,
	ativo TINYINT(1) NOT NULL DEFAULT 1,
	data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_crm_funis_admin (id_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coluna funil_id em crm_leads (execute uma vez)
ALTER TABLE crm_leads
	ADD COLUMN funil_id INT NULL AFTER visibilidade;

ALTER TABLE crm_leads
	ADD INDEX idx_crm_leads_funil (funil_id);

-- Migração: cria funil "Geral" por admin e associa leads existentes
INSERT INTO crm_funis (id_admin, nome)
SELECT DISTINCT l.id_admin, 'Geral'
FROM crm_leads l
LEFT JOIN crm_funis f ON f.id_admin = l.id_admin AND f.nome = 'Geral'
WHERE f.id IS NULL;

UPDATE crm_leads l
INNER JOIN crm_funis f ON f.id_admin = l.id_admin AND f.nome = 'Geral'
SET l.funil_id = f.id
WHERE l.funil_id IS NULL;
