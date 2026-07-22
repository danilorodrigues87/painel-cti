-- Avaliações de curso LMS pelo aluno (Fase A)
-- Colar no phpMyAdmin / DB da escola

CREATE TABLE IF NOT EXISTS `lms_curso_avaliacoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT NOT NULL,
  `id_aluno` INT NOT NULL,
  `id_curso` INT NOT NULL,
  `nota` TINYINT UNSIGNED NOT NULL,
  `comentario` VARCHAR(500) DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lms_aval_aluno_curso` (`id_aluno`, `id_curso`),
  KEY `idx_lms_aval_curso` (`id_curso`, `id_admin`),
  CONSTRAINT `chk_lms_aval_nota` CHECK (`nota` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
