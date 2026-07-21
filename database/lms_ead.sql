-- LMS EAD — Cursos Online (colar no phpMyAdmin / DB cti_admin)
-- Camada paralela às trilhas. NÃO altera matriculas/contratos/agenda.
-- Data: 2026-07-20

-- ========== Config IA por escola ==========
-- Se a coluna já existir, ignore o erro e siga.
ALTER TABLE `escola_integracoes` ADD COLUMN `ai_ativo` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `escola_integracoes` ADD COLUMN `ai_provider` VARCHAR(32) NULL DEFAULT NULL;
ALTER TABLE `escola_integracoes` ADD COLUMN `ai_api_key` TEXT NULL DEFAULT NULL;
ALTER TABLE `escola_integracoes` ADD COLUMN `ai_model` VARCHAR(120) NULL DEFAULT NULL;

-- ========== Curso EAD (1:1 trilha + tenant) ==========
CREATE TABLE IF NOT EXISTS `lms_cursos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` INT NOT NULL,
  `id_trilha` INT NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `short_description` VARCHAR(500) NULL,
  `cover_url` VARCHAR(500) NULL,
  `banner_url` VARCHAR(500) NULL,
  `level` ENUM('Iniciante','Intermediário','Avançado') NOT NULL DEFAULT 'Iniciante',
  `objectives` JSON NULL,
  `instructor_name` VARCHAR(160) NULL,
  `instructor_title` VARCHAR(160) NULL,
  `instructor_bio` TEXT NULL,
  `instructor_avatar_url` VARCHAR(500) NULL,
  `publicado` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lms_curso_trilha` (`id_admin`, `id_trilha`),
  UNIQUE KEY `uk_lms_curso_slug` (`id_admin`, `slug`),
  KEY `idx_lms_curso_pub` (`id_admin`, `publicado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_modulos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_curso` INT UNSIGNED NOT NULL,
  `id_admin` INT NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `ordem` INT NOT NULL DEFAULT 0,
  `bloqueado` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_lms_mod_curso` (`id_curso`, `ordem`),
  KEY `idx_lms_mod_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_aulas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_modulo` INT UNSIGNED NOT NULL,
  `id_admin` INT NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `descricao` TEXT NULL,
  `ordem` INT NOT NULL DEFAULT 0,
  `bloqueado` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_lms_aula_mod` (`id_modulo`, `ordem`),
  KEY `idx_lms_aula_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_videos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_aula` INT UNSIGNED NOT NULL,
  `id_admin` INT NOT NULL,
  `titulo` VARCHAR(255) NULL,
  `url` VARCHAR(1000) NOT NULL,
  `provider` ENUM('youtube','private') NOT NULL DEFAULT 'youtube',
  `duracao_min` INT NOT NULL DEFAULT 0,
  `ordem` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_lms_vid_aula` (`id_aula`, `ordem`),
  KEY `idx_lms_vid_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_materiais` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_aula` INT UNSIGNED NOT NULL,
  `id_admin` INT NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `url` VARCHAR(1000) NOT NULL,
  `tipo` ENUM('pdf','link','file') NOT NULL DEFAULT 'link',
  `ordem` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_lms_mat_aula` (`id_aula`, `ordem`),
  KEY `idx_lms_mat_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_atividades` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_curso` INT UNSIGNED NOT NULL,
  `id_aula` INT UNSIGNED NULL,
  `id_admin` INT NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `descricao` TEXT NULL,
  `duracao_min` INT NOT NULL DEFAULT 30,
  `tentativas_max` INT NOT NULL DEFAULT 3,
  `ordem` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_lms_ativ_curso` (`id_curso`),
  KEY `idx_lms_ativ_aula` (`id_aula`),
  KEY `idx_lms_ativ_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_questoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_atividade` INT UNSIGNED NOT NULL,
  `id_admin` INT NOT NULL,
  `tipo` ENUM('multiple','boolean','essay') NOT NULL DEFAULT 'multiple',
  `enunciado` TEXT NOT NULL,
  `opcoes` JSON NULL,
  `resposta_correta` TEXT NULL,
  `ordem` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_lms_q_ativ` (`id_atividade`, `ordem`),
  KEY `idx_lms_q_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_roleplay_cenarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_curso` INT UNSIGNED NOT NULL,
  `id_modulo` INT UNSIGNED NULL,
  `id_aula` INT UNSIGNED NULL,
  `id_admin` INT NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `tema` VARCHAR(255) NULL,
  `cenario` TEXT NULL,
  `user_role` VARCHAR(255) NULL,
  `ai_role` VARCHAR(255) NULL,
  `ai_character_name` VARCHAR(160) NULL,
  `ai_character_avatar_url` VARCHAR(500) NULL,
  `objectives` JSON NULL,
  `criteria` JSON NULL,
  `difficulty` ENUM('easy','medium','hard','expert') NOT NULL DEFAULT 'medium',
  `min_score` INT NOT NULL DEFAULT 70,
  `base_prompt` TEXT NULL,
  `initial_personality` TEXT NULL,
  `initial_message` TEXT NULL,
  `estimated_minutes` INT NOT NULL DEFAULT 15,
  PRIMARY KEY (`id`),
  KEY `idx_lms_rp_curso` (`id_curso`),
  KEY `idx_lms_rp_aula` (`id_aula`),
  KEY `idx_lms_rp_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_progresso_aula` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_aluno` INT NOT NULL,
  `id_aula` INT UNSIGNED NOT NULL,
  `id_admin` INT NOT NULL,
  `concluida_em` DATETIME NULL,
  `ultimo_acesso` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lms_prog` (`id_aluno`, `id_aula`),
  KEY `idx_lms_prog_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_atividade_tentativas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_aluno` INT NOT NULL,
  `id_atividade` INT UNSIGNED NOT NULL,
  `id_admin` INT NOT NULL,
  `respostas` JSON NULL,
  `nota` DECIMAL(5,2) NULL,
  `feedback` TEXT NULL,
  `strengths` JSON NULL,
  `improvements` JSON NULL,
  `competencies` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_tent_aluno` (`id_aluno`, `id_atividade`),
  KEY `idx_lms_tent_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_roleplay_sessoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_cenario` INT UNSIGNED NOT NULL,
  `id_aluno` INT NOT NULL,
  `id_admin` INT NOT NULL,
  `difficulty` ENUM('easy','medium','hard','expert') NOT NULL DEFAULT 'medium',
  `status` ENUM('pending','in_progress','approved','retry') NOT NULL DEFAULT 'in_progress',
  `messages` JSON NULL,
  `score` DECIMAL(5,2) NULL,
  `evaluation` JSON NULL,
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ended_at` DATETIME NULL,
  `duration_seconds` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_lms_rps_aluno` (`id_aluno`),
  KEY `idx_lms_rps_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_ai_conversas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_aluno` INT NOT NULL,
  `id_admin` INT NOT NULL,
  `titulo` VARCHAR(255) NOT NULL DEFAULT 'Nova conversa',
  `messages` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_ai_aluno` (`id_aluno`),
  KEY `idx_lms_ai_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
