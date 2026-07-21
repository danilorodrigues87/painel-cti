-- Fluxo sequencial de atividades (colar no phpMyAdmin)
-- Adiciona status na tentativa: in_progress = respondendo questão a questão; completed = finalizada.

ALTER TABLE `lms_atividade_tentativas`
  ADD COLUMN `status` ENUM('in_progress','completed') NOT NULL DEFAULT 'completed'
    AFTER `competencies`;

-- Tentativas antigas já têm nota → permanecem completed (default).
-- Índice para buscar tentativa em andamento rapidamente:
ALTER TABLE `lms_atividade_tentativas`
  ADD KEY `idx_lms_tent_status` (`id_aluno`, `id_atividade`, `status`);
