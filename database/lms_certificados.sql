-- Certificados simbólicos do portal EAD ( Ascend ).
-- Separados da tabela comercial `certificados` (pacotes + site da escola).

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS lms_certificados (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  id_aluno INT UNSIGNED NOT NULL,
  id_curso INT UNSIGNED NOT NULL,
  id_trilha INT UNSIGNED NOT NULL DEFAULT 0,
  titulo_curso VARCHAR(255) NOT NULL DEFAULT '',
  nome_escola VARCHAR(255) NOT NULL DEFAULT '',
  carga_h INT UNSIGNED NOT NULL DEFAULT 0,
  modulos TEXT NULL,
  codigo VARCHAR(32) NOT NULL,
  conclusao DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lms_cert_aluno_curso (id_aluno, id_curso),
  UNIQUE KEY uq_lms_cert_codigo (codigo),
  KEY idx_lms_cert_admin (id_admin),
  KEY idx_lms_cert_aluno (id_aluno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
