-- Matrícula bolsista: sem carnê/débitos no caixa
-- Colar no phpMyAdmin

ALTER TABLE `matriculas`
  ADD COLUMN `bolsista` TINYINT(1) NOT NULL DEFAULT 0
  COMMENT '1 = bolsa (não gera parcelas no caixa)'
  AFTER `desconto_pontualidade`;
