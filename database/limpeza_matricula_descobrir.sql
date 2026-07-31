-- ============================================================
-- 1) DESCOBRIR matrículas do aluno (só leitura)
-- ============================================================
-- Troque os valores abaixo antes de executar.

SET @id_admin = 1;          -- id da escola (escolas_assinantes / id_admin)
SET @id_aluno = 0;          -- id do aluno em usuarios (nivel Cliente)
-- OU busque pelo nome (deixe @id_aluno = 0 e preencha @nome):
SET @nome     = '%teste%';  -- parte do nome do aluno

-- A) Listar alunos que batem com o nome (para achar o id_aluno)
SELECT
  u.id AS id_aluno,
  u.nome,
  u.cpf,
  u.whatsapp,
  u.email
FROM usuarios u
WHERE u.id_admin = @id_admin
  AND u.nivel = 'Cliente'
  AND (
    (@id_aluno > 0 AND u.id = @id_aluno)
    OR (@id_aluno = 0 AND u.nome LIKE @nome)
  )
ORDER BY u.nome;

-- B) Matrículas do aluno + totais de parcelas no caixa
SELECT
  m.id AS id_matricula,
  m.id_aluno,
  u.nome AS aluno,
  t.nome AS curso,
  m.valor,
  m.qtd_parcelas,
  m.inicio,
  m.fim,
  m.status,
  CASE m.status
    WHEN 0 THEN 'andamento'
    WHEN 1 THEN 'encerrado'
    WHEN 3 THEN 'cancelado'
    ELSE CONCAT('outro(', m.status, ')')
  END AS status_label,
  IFNULL(m.bolsista, 0) AS bolsista,
  m.tipo_parcelamento,
  COUNT(c.id) AS qtd_titulos_caixa,
  COALESCE(SUM(c.valor), 0) AS soma_parcelas
FROM matriculas m
INNER JOIN usuarios u ON u.id = m.id_aluno
LEFT JOIN trilhas t ON t.id = m.id_trilha
LEFT JOIN caixa c
  ON c.id_ref = m.id
 AND c.id_admin = m.id_admin
WHERE m.id_admin = @id_admin
  AND (
    (@id_aluno > 0 AND m.id_aluno = @id_aluno)
    OR (@id_aluno = 0 AND u.nome LIKE @nome)
  )
GROUP BY m.id
ORDER BY m.id DESC;

-- C) Detalhe das parcelas de UMA matrícula (troque o id)
SET @id_matricula = 0;  -- <<< cole o id_matricula da consulta B

SELECT
  c.id AS id_caixa,
  c.descricao,
  c.valor,
  c.vencimento,
  c.status,
  c.tipo_pagamento,
  c.valor_pago,
  c.id_ref AS id_matricula,
  c.id_acordo,
  c.referencia
FROM caixa c
WHERE c.id_admin = @id_admin
  AND c.id_ref = @id_matricula
ORDER BY c.vencimento, c.id;
