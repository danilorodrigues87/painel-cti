-- Corrige matrículas com data inválida (0000-00-00) usando a data de início
UPDATE matriculas
SET matriculado_em = inicio
WHERE matriculado_em = '0000-00-00';
