-- Tempo de estudo real (heartbeat do player Ascend)
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS lms_estudo_sessao (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_aluno INT UNSIGNED NOT NULL,
  id_admin INT UNSIGNED NOT NULL,
  id_aula INT UNSIGNED NOT NULL,
  id_curso INT UNSIGNED NULL DEFAULT NULL,
  started_at DATETIME NOT NULL,
  last_ping_at DATETIME NOT NULL,
  ended_at DATETIME NULL DEFAULT NULL,
  segundos INT UNSIGNED NOT NULL DEFAULT 0,
  origem ENUM('presence','youtube','private') NOT NULL DEFAULT 'presence',
  PRIMARY KEY (id),
  KEY idx_estudo_aluno (id_aluno, id_admin, last_ping_at),
  KEY idx_estudo_aula (id_aula, id_aluno),
  KEY idx_estudo_sessao_aberta (id_aluno, id_aula, ended_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
