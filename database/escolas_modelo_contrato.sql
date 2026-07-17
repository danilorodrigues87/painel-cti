-- Modelo de contrato HTML por escola (NULL = usa o padrão CTI / escola 1 atual)
-- Frase editável do certificado (NULL = "Concluiu com louvor o curso de")
-- Rode no phpMyAdmin. Ignore erro de "duplicate column" se já existir.

ALTER TABLE escolas_assinantes
  ADD COLUMN modelo_contrato_html MEDIUMTEXT NULL DEFAULT NULL;

ALTER TABLE escolas_assinantes
  ADD COLUMN certificado_frase_conclusao VARCHAR(255) NULL DEFAULT NULL;
