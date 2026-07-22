-- Estoque + PDV simples
-- Colar no phpMyAdmin (produção e local).
-- Depois: Master → plano da escola → liberar módulos "estoque" e "vendas".

CREATE TABLE IF NOT EXISTS stq_categorias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  descricao VARCHAR(500) NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  id_admin INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_stq_cat_admin (id_admin, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stq_produtos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(180) NOT NULL,
  id_categoria INT UNSIGNED NULL,
  quantidade INT NOT NULL DEFAULT 0,
  descricao TEXT NULL,
  valor_custo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  valor_venda DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  sku VARCHAR(80) NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  id_admin INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_stq_prod_admin (id_admin, status),
  KEY idx_stq_prod_sku (id_admin, sku),
  KEY idx_stq_prod_cat (id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stq_movimentacoes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_produto INT UNSIGNED NOT NULL,
  tipo ENUM('entrada','saida','ajuste') NOT NULL,
  quantidade INT NOT NULL,
  saldo_anterior INT NOT NULL DEFAULT 0,
  saldo_atual INT NOT NULL DEFAULT 0,
  observacao VARCHAR(500) NULL,
  id_admin INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_stq_mov_prod (id_produto, created_at),
  KEY idx_stq_mov_admin (id_admin, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stq_preco_historico (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_produto INT UNSIGNED NOT NULL,
  valor_custo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  valor_venda DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  id_admin INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_stq_preco_prod (id_produto, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stq_produto_imagens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_produto INT UNSIGNED NOT NULL,
  imagem VARCHAR(255) NOT NULL,
  principal TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_stq_img_prod (id_produto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stq_fornecedores (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(180) NOT NULL,
  cnpj VARCHAR(20) NULL,
  telefone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  contato VARCHAR(120) NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  id_admin INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_stq_forn_admin (id_admin, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Se a tabela stq_fornecedores já existia sem id_admin:
-- ALTER TABLE stq_fornecedores ADD COLUMN id_admin INT NOT NULL DEFAULT 0 AFTER status;
-- ALTER TABLE stq_fornecedores ADD KEY idx_stq_forn_admin (id_admin, status);

-- Se stq_produtos já existia sem status:
-- ALTER TABLE stq_produtos ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER sku;

CREATE TABLE IF NOT EXISTS stq_vendas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT NOT NULL,
  id_usuario INT NULL,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  tipo_pagamento VARCHAR(40) NOT NULL,
  id_caixa INT NULL,
  observacao VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_stq_venda_admin (id_admin, created_at),
  KEY idx_stq_venda_caixa (id_caixa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stq_venda_itens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_venda INT UNSIGNED NOT NULL,
  id_produto INT UNSIGNED NOT NULL,
  nome_snapshot VARCHAR(180) NOT NULL,
  qtd INT NOT NULL,
  valor_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  KEY idx_stq_item_venda (id_venda),
  KEY idx_stq_item_prod (id_produto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
