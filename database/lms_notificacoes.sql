-- Notificações in-app do portal do aluno (Ascend)
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS lms_notificacoes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  id_aluno INT UNSIGNED NOT NULL,
  tipo ENUM('lesson','course','certificate','ai','system') NOT NULL DEFAULT 'system',
  titulo VARCHAR(180) NOT NULL,
  mensagem VARCHAR(500) NOT NULL DEFAULT '',
  link VARCHAR(255) NULL DEFAULT NULL,
  lida TINYINT(1) NOT NULL DEFAULT 0,
  ref_chave VARCHAR(120) NULL DEFAULT NULL COMMENT 'Idempotencia opcional (ex: cert:123)',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lms_notif_aluno_lida (id_aluno, lida, created_at),
  KEY idx_lms_notif_admin (id_admin),
  UNIQUE KEY uq_lms_notif_ref (id_aluno, ref_chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
