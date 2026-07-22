-- Extrato consolidado + renegociação de débitos
-- Colar no phpMyAdmin (produção e local).

CREATE TABLE IF NOT EXISTS financeiro_acordos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT NOT NULL,
  id_aluno INT NOT NULL,
  valor_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  valor_parcela DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  qtd_parcelas INT NOT NULL DEFAULT 1,
  dia_vencimento TINYINT NOT NULL DEFAULT 10,
  primeiro_vencimento DATE NOT NULL,
  observacao VARCHAR(500) NULL,
  ids_titulos_origem TEXT NULL COMMENT 'JSON array dos ids caixa baixados',
  status ENUM('ativo','cancelado') NOT NULL DEFAULT 'ativo',
  id_usuario INT NULL COMMENT 'funcionário que criou',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_acordo_admin_aluno (id_admin, id_aluno),
  KEY idx_acordo_status (id_admin, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Parcela de acordo: id_ref=0 e id_acordo preenchido
ALTER TABLE caixa
  ADD COLUMN id_acordo INT UNSIGNED NULL DEFAULT NULL AFTER id_ref,
  ADD KEY idx_caixa_acordo (id_acordo);
