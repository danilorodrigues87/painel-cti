-- ============================================================
-- Higiene legado: matrículas vencidas + canceladas com carnê aberto
-- Colar no phpMyAdmin. Rodar PREVIEW antes do APPLY.
-- NÃO altera usuarios.ativo (revisão manual).
-- ============================================================

-- ---------- PREVIEW ----------

-- Matrículas ainda "em andamento" com data fim já passada
SELECT id_admin, COUNT(*) AS qtd
FROM matriculas
WHERE status = 0
  AND fim IS NOT NULL AND fim != '0000-00-00' AND fim < CURDATE()
GROUP BY id_admin;

-- Canceladas (status=3) que ainda têm parcela em aberto
SELECT m.id_admin, COUNT(DISTINCT m.id) AS matriculas, COUNT(c.id) AS parcelas_abertas
FROM matriculas m
INNER JOIN caixa c ON c.id_ref = m.id AND c.id_admin = m.id_admin
WHERE m.status = 3
  AND c.tipo_transacao = 'Entrada'
  AND (c.status = 0 OR c.status = '0' OR c.status = 'Em aberto')
GROUP BY m.id_admin;

-- ---------- APPLY (descomente após revisar o preview) ----------

-- 1) Encerrar contratos com fim passado
/*
UPDATE matriculas
SET status = 1
WHERE status = 0
  AND fim IS NOT NULL AND fim != '0000-00-00' AND fim < CURDATE();
*/

-- 2) Baixa administrativa R$ 0 nas parcelas abertas de matrículas canceladas
/*
UPDATE caixa c
INNER JOIN matriculas m ON m.id = c.id_ref AND m.id_admin = c.id_admin
SET
  c.status = 1,
  c.valor_pago = 0,
  c.tipo_pagamento = 'Cancelamento',
  c.data_pagamento = CURDATE(),
  c.descricao = LEFT(CONCAT(TRIM(IFNULL(c.descricao,'')), ' | Cancelamento matrícula #', m.id), 250),
  c.ultima_alteracao = NOW()
WHERE m.status = 3
  AND c.tipo_transacao = 'Entrada'
  AND (c.status = 0 OR c.status = '0' OR c.status = 'Em aberto');
*/
