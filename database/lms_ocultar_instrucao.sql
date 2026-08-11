-- Ocultar caixa de instruções por cena (L-Editor) — colar no phpMyAdmin
-- Após database/lms_aulas_interativas.sql

ALTER TABLE `lms_aula_cenas`
  ADD COLUMN `ocultar_instrucao` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1=não exibe InstructionBox no player do aluno'
    AFTER `instrucao`;
