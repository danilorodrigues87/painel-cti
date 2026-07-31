-- ============================================================
-- 2) EXCLUIR tudo vinculado a UMA matrícula (teste)
-- ============================================================
-- ATENÇÃO: irreversível. Rode primeiro o script de DESCOBRIR.
-- Troque os IDs e rode o bloco PREVIEW antes do DELETE.

SET @id_admin    = 1;   -- escola
SET @id_matricula = 0;   -- <<< ID da matrícula a apagar

-- ------------------------------------------------------------
-- PREVIEW (rode isto antes de apagar)
-- ------------------------------------------------------------
SELECT 'matricula' AS tipo, m.*
FROM matriculas m
WHERE m.id = @id_matricula AND m.id_admin = @id_admin;

SELECT 'parcelas_caixa' AS tipo, c.id, c.descricao, c.valor, c.vencimento, c.status, c.id_acordo
FROM caixa c
WHERE c.id_admin = @id_admin AND c.id_ref = @id_matricula;

SELECT 'log_cobranca' AS tipo, l.*
FROM email_cobranca_log l
WHERE l.caixa_id IN (
  SELECT c.id FROM caixa c
  WHERE c.id_admin = @id_admin AND c.id_ref = @id_matricula
);

-- Agenda (se existir a coluna matricula_id)
-- SELECT 'agenda_plano' AS tipo, p.* FROM agenda_plano p
-- WHERE p.id_admin = @id_admin AND p.matricula_id = @id_matricula;
-- SELECT 'agenda_avulso' AS tipo, a.* FROM agenda_avulso a
-- WHERE a.id_admin = @id_admin AND a.matricula_id = @id_matricula;

-- ------------------------------------------------------------
-- DELETE (descomente só depois de conferir o PREVIEW)
-- Ordem: logs → parcelas → agenda → matrícula
-- ------------------------------------------------------------

START TRANSACTION;

-- 1) Log de e-mails de cobrança das parcelas desta matrícula
DELETE FROM email_cobranca_log
WHERE caixa_id IN (
  SELECT x.id FROM (
    SELECT c.id FROM caixa c
    WHERE c.id_admin = @id_admin AND c.id_ref = @id_matricula
  ) AS x
);

-- 2) Parcelas / carnê no caixa (id_ref = matrícula)
DELETE FROM caixa
WHERE id_admin = @id_admin
  AND id_ref = @id_matricula;

-- 3) Agenda ligada à matrícula (descomente se as tabelas existirem)
-- DELETE FROM agenda_avulso
-- WHERE id_admin = @id_admin AND matricula_id = @id_matricula;
-- DELETE FROM agenda_plano
-- WHERE id_admin = @id_admin AND matricula_id = @id_matricula;

-- 4) A matrícula em si
DELETE FROM matriculas
WHERE id = @id_matricula
  AND id_admin = @id_admin;

-- Confira contagens; se ok: COMMIT; se errado: ROLLBACK;
-- COMMIT;
-- ROLLBACK;

COMMIT;

-- Conferência (deve voltar vazio)
SELECT * FROM matriculas WHERE id = @id_matricula;
SELECT * FROM caixa WHERE id_ref = @id_matricula AND id_admin = @id_admin;
