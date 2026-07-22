-- Comentários nas aulas (portal Ascend)
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS lms_aula_comentarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  id_aula INT UNSIGNED NOT NULL,
  id_curso INT UNSIGNED NULL DEFAULT NULL,
  id_autor INT UNSIGNED NOT NULL COMMENT 'usuarios.id (aluno ou equipe)',
  autor_tipo ENUM('aluno','equipe') NOT NULL DEFAULT 'aluno',
  id_pai INT UNSIGNED NULL DEFAULT NULL COMMENT 'resposta a outro comentário',
  texto TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_lms_com_aula (id_aula, created_at),
  KEY idx_lms_com_admin (id_admin),
  KEY idx_lms_com_pai (id_pai),
  KEY idx_lms_com_autor (id_autor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
