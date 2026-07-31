-- Só a coluna de IA opcional (se já rodou telegram_agent_nativo.sql antes)
ALTER TABLE `agent_escola_config`
  ADD COLUMN `telegram_ia_ativo` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1 = respostas livres com IA; 0 = só palavras-chave';
