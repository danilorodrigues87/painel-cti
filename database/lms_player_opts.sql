-- Opções de player por aula/cena (L-Editor) — colar no phpMyAdmin
-- Após database/lms_aulas_interativas.sql

ALTER TABLE `lms_aulas`
  ADD COLUMN `interativa_auto_narracao` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1=narração automática ligada por padrão na aula'
    AFTER `interativa_status`,
  ADD COLUMN `interativa_delay_ms` INT UNSIGNED NOT NULL DEFAULT 2000
    COMMENT 'Delay ms antes de revelar destaque (padrão da aula)'
    AFTER `interativa_auto_narracao`,
  ADD COLUMN `interativa_duracao_ms` INT UNSIGNED NOT NULL DEFAULT 4000
    COMMENT 'Tempo ms na cena sem narração (padrão da aula)'
    AFTER `interativa_delay_ms`;

ALTER TABLE `lms_aula_cenas`
  ADD COLUMN `auto_narracao` TINYINT(1) DEFAULT NULL
    COMMENT 'NULL=herda da aula; 0=desliga narração auto na cena'
    AFTER `ocultar_instrucao`,
  ADD COLUMN `delay_revelar_ms` INT UNSIGNED DEFAULT NULL
    COMMENT 'NULL=herda da aula; delay ms antes do destaque'
    AFTER `auto_narracao`,
  ADD COLUMN `duracao_ms` INT UNSIGNED DEFAULT NULL
    COMMENT 'NULL=herda da aula; tempo ms na cena sem narração'
    AFTER `delay_revelar_ms`;

-- Se as colunas acima já existirem, rode só este bloco:
-- ALTER TABLE `lms_aulas` ADD COLUMN `interativa_duracao_ms` INT UNSIGNED NOT NULL DEFAULT 4000 AFTER `interativa_delay_ms`;
-- ALTER TABLE `lms_aula_cenas` ADD COLUMN `duracao_ms` INT UNSIGNED DEFAULT NULL AFTER `delay_revelar_ms`;
