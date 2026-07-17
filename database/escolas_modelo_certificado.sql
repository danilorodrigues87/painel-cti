-- Modelo de certificado por escola (fundo A4 paisagem já com a logo)
ALTER TABLE escolas_assinantes
  ADD COLUMN modelo_certificado VARCHAR(255) NULL DEFAULT NULL
  AFTER logo;
