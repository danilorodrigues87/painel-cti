-- ============================================================
-- Opcional: normalizar status legado do caixa
-- Colar no phpMyAdmin. Rodar PREVIEW antes do APPLY.
-- Canônico atual: aberto = "Em aberto" | pago = 1
-- ============================================================

-- ---------- PREVIEW ----------
SELECT status, COUNT(*) AS qtd
FROM caixa
GROUP BY status
ORDER BY qtd DESC;

SELECT COUNT(*) AS abertos_numericos
FROM caixa
WHERE status = 0 OR status = '0';

SELECT COUNT(*) AS pagos_texto
FROM caixa
WHERE status = 'Pago' OR status = 'pago';

-- ---------- APPLY (descomente após revisar) ----------

-- 1) Legado aberto numérico → "Em aberto"
/*
UPDATE caixa
SET status = 'Em aberto'
WHERE status = 0 OR status = '0';
*/

-- 2) Legado "Pago" texto → 1
/*
UPDATE caixa
SET status = 1
WHERE status = 'Pago' OR status = 'pago';
*/
