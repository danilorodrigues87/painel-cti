-- Histórico de observações / anotações do aluno
CREATE TABLE IF NOT EXISTS aluno_observacoes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  aluno_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  observacao TEXT NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_aluno_obs_aluno (aluno_id, criado_em),
  KEY idx_aluno_obs_admin (id_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
