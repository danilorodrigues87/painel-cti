-- =============================================================================
-- Central de Ajuda: Assistente IA / OpenClaw (novas funcionalidades)
-- Cole no phpMyAdmin DEPOIS de database/platform_help.sql
-- Idempotente: pode rodar de novo; preserva video_url se já preenchido.
-- Também incluído em PlatformHelpSeed (Master → Carregar tutoriais padrão).
-- =============================================================================
SET NAMES utf8mb4;

-- Categoria
INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Assistente IA', 'assistente-ia', 125, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'assistente-ia');
UPDATE `platform_help_categorias` SET `titulo` = 'Assistente IA', `ordem` = 125, `ativo` = 1 WHERE `slug` = 'assistente-ia';

-- Artigo: visão geral
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id,
  'O que é o Assistente IA',
  'assistente-ia-visao-geral',
  'Assistente operacional (OpenClaw) que consulta dados da escola e responde no Telegram.',
  '<p>O <strong>Assistente IA</strong> é um agente externo (OpenClaw) que consulta o painel em modo somente leitura — agenda do dia, inadimplentes, CRM, WhatsApp, etc. — e pode responder pelo Telegram.</p><p><strong>Onde encontrar:</strong> Configurações → Assistente IA</p><h2>Passo a passo</h2><ol><li>O módulo precisa estar liberado no <strong>plano</strong> da escola (slug <code>assistente_ia</code>).</li><li>O Diretor cadastra a <strong>chave LLM do OpenClaw</strong> e o <strong>bot do Telegram</strong> em Configurações → Assistente IA.</li><li>A <strong>Agent API</strong> (acesso aos dados do painel) é gerada e ativada pelo <strong>suporte CTI / Master</strong> — a escola não gera essa chave.</li><li>Isso é independente da <strong>IA Pedagógica</strong> do portal EAD.</li></ol><h2>Dicas e cuidados</h2><p><strong>Segurança:</strong> cada escola usa a própria Agent API. A chave Master do SaaS não deve ser usada no bot da escola.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
  NULL,
  'Tutorial: O que é o Assistente IA',
  10, 1
FROM `platform_help_categorias` c
WHERE c.slug = 'assistente-ia'
  AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assistente-ia-visao-geral');

UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'assistente-ia'
SET a.id_categoria = c.id,
    a.titulo = 'O que é o Assistente IA',
    a.resumo = 'Assistente operacional (OpenClaw) que consulta dados da escola e responde no Telegram.',
    a.corpo = '<p>O <strong>Assistente IA</strong> é um agente externo (OpenClaw) que consulta o painel em modo somente leitura — agenda do dia, inadimplentes, CRM, WhatsApp, etc. — e pode responder pelo Telegram.</p><p><strong>Onde encontrar:</strong> Configurações → Assistente IA</p><h2>Passo a passo</h2><ol><li>O módulo precisa estar liberado no <strong>plano</strong> da escola (slug <code>assistente_ia</code>).</li><li>O Diretor cadastra a <strong>chave LLM do OpenClaw</strong> e o <strong>bot do Telegram</strong> em Configurações → Assistente IA.</li><li>A <strong>Agent API</strong> (acesso aos dados do painel) é gerada e ativada pelo <strong>suporte CTI / Master</strong> — a escola não gera essa chave.</li><li>Isso é independente da <strong>IA Pedagógica</strong> do portal EAD.</li></ol><h2>Dicas e cuidados</h2><p><strong>Segurança:</strong> cada escola usa a própria Agent API. A chave Master do SaaS não deve ser usada no bot da escola.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: O que é o Assistente IA', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'assistente-ia-visao-geral';

-- Artigo: configurar escola
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id,
  'Configurar LLM e Telegram (Diretor)',
  'assistente-ia-configurar-escola',
  'Como o Diretor cadastra a IA do OpenClaw e o token do bot no painel.',
  '<p>Nesta tela você guarda, de forma segura, o que o suporte CTI precisa para ligar o subagente da escola no OpenClaw — sem enviar tokens por WhatsApp.</p><p><strong>Onde encontrar:</strong> Configurações → Assistente IA</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Assistente IA</strong> (apenas Diretor, com o módulo no plano).</li><li>Em <strong>IA do OpenClaw (LLM)</strong>: ative, escolha o provedor (Gemini/OpenAI), informe o modelo e cole a API key.</li><li>Opcional: use o botão <em>Usar dados da IA Pedagógica</em> se quiser pré-preencher com a mesma conta — depois revise e salve.</li><li>Em <strong>Telegram</strong>: crie o bot no <code>@BotFather</code>, cole o token, o username (ex.: escola_xyz_bot) e, se quiser, o Chat ID autorizado e observações.</li><li>Clique em <strong>Salvar</strong>. Tokens já salvos aparecem mascarados; deixe o campo em branco para manter.</li><li>Acompanhe o status na parte superior (LLM / Telegram / Agent API aguardando Master).</li></ol><h2>Dicas e cuidados</h2><ul><li>Não compartilhe tokens em grupos ou WhatsApp.</li><li>A Agent API continua sendo criada pelo suporte CTI após você salvar LLM e Telegram.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
  NULL,
  'Tutorial: Configurar LLM e Telegram (Diretor)',
  20, 1
FROM `platform_help_categorias` c
WHERE c.slug = 'assistente-ia'
  AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assistente-ia-configurar-escola');

UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'assistente-ia'
SET a.id_categoria = c.id,
    a.titulo = 'Configurar LLM e Telegram (Diretor)',
    a.resumo = 'Como o Diretor cadastra a IA do OpenClaw e o token do bot no painel.',
    a.corpo = '<p>Nesta tela você guarda, de forma segura, o que o suporte CTI precisa para ligar o subagente da escola no OpenClaw — sem enviar tokens por WhatsApp.</p><p><strong>Onde encontrar:</strong> Configurações → Assistente IA</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Assistente IA</strong> (apenas Diretor, com o módulo no plano).</li><li>Em <strong>IA do OpenClaw (LLM)</strong>: ative, escolha o provedor (Gemini/OpenAI), informe o modelo e cole a API key.</li><li>Opcional: use o botão <em>Usar dados da IA Pedagógica</em> se quiser pré-preencher com a mesma conta — depois revise e salve.</li><li>Em <strong>Telegram</strong>: crie o bot no <code>@BotFather</code>, cole o token, o username (ex.: escola_xyz_bot) e, se quiser, o Chat ID autorizado e observações.</li><li>Clique em <strong>Salvar</strong>. Tokens já salvos aparecem mascarados; deixe o campo em branco para manter.</li><li>Acompanhe o status na parte superior (LLM / Telegram / Agent API aguardando Master).</li></ol><h2>Dicas e cuidados</h2><ul><li>Não compartilhe tokens em grupos ou WhatsApp.</li><li>A Agent API continua sendo criada pelo suporte CTI após você salvar LLM e Telegram.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Configurar LLM e Telegram (Diretor)', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'assistente-ia-configurar-escola';

-- Artigo: diferença das IAs
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id,
  'IA Pedagógica × Assistente IA (OpenClaw)',
  'assistente-ia-vs-pedagogica',
  'Duas configurações de IA diferentes no painel — quando usar cada uma.',
  '<p>O painel tem <strong>duas</strong> configurações de chave de IA. Podem usar a mesma conta Gemini/OpenAI, mas são salvas em lugares distintos.</p><p><strong>Onde encontrar:</strong> Configurações → IA Pedagógica · Configurações → Assistente IA</p><h2>Passo a passo</h2><ol><li><strong>IA Pedagógica</strong> (<em>Configurações → IA Pedagógica</em>): tutor e role play no <strong>portal do aluno (EAD)</strong>.</li><li><strong>Assistente IA / OpenClaw</strong> (<em>Configurações → Assistente IA</em>): agente operacional na VPS, tipicamente via <strong>Telegram</strong>, consultando agenda, financeiro, CRM etc.</li><li>Uma escola pode ter só uma, só a outra, ou as duas.</li><li>Se já configurou a pedagógica e quiser a mesma key no OpenClaw, use o botão de copiar dados na tela do Assistente IA e salve.</li></ol><h2>Dicas e cuidados</h2><p>Confundir as duas telas é o erro mais comum: configurar só a pedagógica <strong>não</strong> ativa o Telegram do OpenClaw.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
  NULL,
  'Tutorial: IA Pedagógica × Assistente IA (OpenClaw)',
  30, 1
FROM `platform_help_categorias` c
WHERE c.slug = 'assistente-ia'
  AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assistente-ia-vs-pedagogica');

UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'assistente-ia'
SET a.id_categoria = c.id,
    a.titulo = 'IA Pedagógica × Assistente IA (OpenClaw)',
    a.resumo = 'Duas configurações de IA diferentes no painel — quando usar cada uma.',
    a.corpo = '<p>O painel tem <strong>duas</strong> configurações de chave de IA. Podem usar a mesma conta Gemini/OpenAI, mas são salvas em lugares distintos.</p><p><strong>Onde encontrar:</strong> Configurações → IA Pedagógica · Configurações → Assistente IA</p><h2>Passo a passo</h2><ol><li><strong>IA Pedagógica</strong> (<em>Configurações → IA Pedagógica</em>): tutor e role play no <strong>portal do aluno (EAD)</strong>.</li><li><strong>Assistente IA / OpenClaw</strong> (<em>Configurações → Assistente IA</em>): agente operacional na VPS, tipicamente via <strong>Telegram</strong>, consultando agenda, financeiro, CRM etc.</li><li>Uma escola pode ter só uma, só a outra, ou as duas.</li><li>Se já configurou a pedagógica e quiser a mesma key no OpenClaw, use o botão de copiar dados na tela do Assistente IA e salve.</li></ol><h2>Dicas e cuidados</h2><p>Confundir as duas telas é o erro mais comum: configurar só a pedagógica <strong>não</strong> ativa o Telegram do OpenClaw.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: IA Pedagógica × Assistente IA (OpenClaw)', a.video_titulo),
    a.ordem = 30,
    a.publicado = 1
WHERE a.slug = 'assistente-ia-vs-pedagogica';

-- Artigo: ativação e segurança
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id,
  'Ativação pelo suporte CTI e segurança das chaves',
  'assistente-ia-ativacao-seguranca',
  'Agent API por escola, ativação Master e por que não se usa uma chave única para todos.',
  '<p>Depois que o Diretor salva LLM e Telegram, o suporte CTI gera a <strong>Agent API</strong> da escola e liga o subagente no OpenClaw.</p><p><strong>Onde encontrar:</strong> Suporte CTI · Master → Agent API</p><h2>Passo a passo</h2><ol><li>No painel Master (equipe CTI): <strong>Agent API</strong> → abrir a escola → gerar chave → revelar segredos (LLM/Telegram) → configurar na VPS.</li><li>O assistente só responde na API se estiver <strong>Ativo</strong> no Master e o módulo estiver no plano.</li><li><strong>Uma escola = uma Agent API key.</strong> Isso evita que um bot ou vazamento de uma unidade acesse dados de outra.</li><li>A chave Master do SaaS é só para o agente do dono CTI — não deve ir no bot Telegram da escola.</li><li>Se precisar pausar: o Master desativa o assistente da escola (a API passa a negar acesso).</li></ol><h2>Dicas e cuidados</h2><ul><li>Nunca reutilize a mesma Agent API em duas escolas.</li><li>Em caso de suspeita de vazamento, peça ao suporte para revogar a chave e gerar outra.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
  NULL,
  'Tutorial: Ativação pelo suporte CTI e segurança das chaves',
  40, 1
FROM `platform_help_categorias` c
WHERE c.slug = 'assistente-ia'
  AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assistente-ia-ativacao-seguranca');

UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'assistente-ia'
SET a.id_categoria = c.id,
    a.titulo = 'Ativação pelo suporte CTI e segurança das chaves',
    a.resumo = 'Agent API por escola, ativação Master e por que não se usa uma chave única para todos.',
    a.corpo = '<p>Depois que o Diretor salva LLM e Telegram, o suporte CTI gera a <strong>Agent API</strong> da escola e liga o subagente no OpenClaw.</p><p><strong>Onde encontrar:</strong> Suporte CTI · Master → Agent API</p><h2>Passo a passo</h2><ol><li>No painel Master (equipe CTI): <strong>Agent API</strong> → abrir a escola → gerar chave → revelar segredos (LLM/Telegram) → configurar na VPS.</li><li>O assistente só responde na API se estiver <strong>Ativo</strong> no Master e o módulo estiver no plano.</li><li><strong>Uma escola = uma Agent API key.</strong> Isso evita que um bot ou vazamento de uma unidade acesse dados de outra.</li><li>A chave Master do SaaS é só para o agente do dono CTI — não deve ir no bot Telegram da escola.</li><li>Se precisar pausar: o Master desativa o assistente da escola (a API passa a negar acesso).</li></ol><h2>Dicas e cuidados</h2><ul><li>Nunca reutilize a mesma Agent API em duas escolas.</li><li>Em caso de suspeita de vazamento, peça ao suporte para revogar a chave e gerar outra.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Ativação pelo suporte CTI e segurança das chaves', a.video_titulo),
    a.ordem = 40,
    a.publicado = 1
WHERE a.slug = 'assistente-ia-ativacao-seguranca';

-- Artigo: o que pode consultar
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id,
  'O que o Assistente pode consultar',
  'assistente-ia-consultas',
  'Consultas somente leitura: agenda, inadimplentes, CRM, matrículas, WhatsApp e mais.',
  '<p>A Agent API é <strong>somente leitura</strong>: o assistente analisa e responde; não dá baixa, não altera matrícula e não envia WhatsApp pelo painel.</p><p><strong>Onde encontrar:</strong> Telegram do Assistente IA (após ativação)</p><h2>Passo a passo</h2><ol><li><strong>Resumo do dia:</strong> matrículas ativas, recebido hoje, a receber na semana, CRM e indicadores rápidos.</li><li><strong>Agenda:</strong> aulas/agendamentos de hoje.</li><li><strong>Financeiro:</strong> inadimplentes (mês/semana/hoje) e títulos a receber.</li><li><strong>CRM:</strong> leads e conversão no período.</li><li><strong>Matrículas:</strong> ativas e novas no mês.</li><li><strong>WhatsApp:</strong> fila, não lidas e conversas abertas (quando o módulo existir).</li></ol><h2>Dicas e cuidados</h2><p>Exemplos de perguntas: “Quem tem aula hoje?”, “Quanto está inadimplente esta semana?”, “Como está o funil de leads?”.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
  NULL,
  'Tutorial: O que o Assistente pode consultar',
  50, 1
FROM `platform_help_categorias` c
WHERE c.slug = 'assistente-ia'
  AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assistente-ia-consultas');

UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'assistente-ia'
SET a.id_categoria = c.id,
    a.titulo = 'O que o Assistente pode consultar',
    a.resumo = 'Consultas somente leitura: agenda, inadimplentes, CRM, matrículas, WhatsApp e mais.',
    a.corpo = '<p>A Agent API é <strong>somente leitura</strong>: o assistente analisa e responde; não dá baixa, não altera matrícula e não envia WhatsApp pelo painel.</p><p><strong>Onde encontrar:</strong> Telegram do Assistente IA (após ativação)</p><h2>Passo a passo</h2><ol><li><strong>Resumo do dia:</strong> matrículas ativas, recebido hoje, a receber na semana, CRM e indicadores rápidos.</li><li><strong>Agenda:</strong> aulas/agendamentos de hoje.</li><li><strong>Financeiro:</strong> inadimplentes (mês/semana/hoje) e títulos a receber.</li><li><strong>CRM:</strong> leads e conversão no período.</li><li><strong>Matrículas:</strong> ativas e novas no mês.</li><li><strong>WhatsApp:</strong> fila, não lidas e conversas abertas (quando o módulo existir).</li></ol><h2>Dicas e cuidados</h2><p>Exemplos de perguntas: “Quem tem aula hoje?”, “Quanto está inadimplente esta semana?”, “Como está o funil de leads?”.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: O que o Assistente pode consultar', a.video_titulo),
    a.ordem = 50,
    a.publicado = 1
WHERE a.slug = 'assistente-ia-consultas';

-- Atualiza artigo existente de IA Pedagógica (categoria Configurações) para citar a diferença
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'configuracoes'
SET a.resumo = 'IA do portal EAD (tutor/roleplay) — diferente do Assistente IA / OpenClaw.',
    a.corpo = '<p>A <strong>IA Pedagógica</strong> alimenta o tutor e o role play no portal do aluno (EAD). Não é a mesma configuração do Assistente IA (Telegram/OpenClaw).</p><p><strong>Onde encontrar:</strong> Configurações → IA Pedagógica</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → IA Pedagógica</strong> (Diretor).</li><li>Escolha o provedor (Gemini, OpenAI, etc.), o modelo e cole a API key.</li><li>Ative a opção e salve. A chave fica criptografada e não é enviada ao navegador do aluno.</li><li>Se também usar o Assistente IA, pode ser a mesma key na prática — mas as telas são independentes.</li></ol><h2>Dicas e cuidados</h2><p>Para o assistente no Telegram, veja a categoria <strong>Assistente IA</strong>.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.publicado = 1
WHERE a.slug = 'ia-pedagogica';

-- Fim
