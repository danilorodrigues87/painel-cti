-- Central de Ajuda: Assistente IA (bot Telegram nativo)
-- Pré-requisito: database/platform_help.sql
-- Idempotente. video_url preenchido NÃO é sobrescrito.
-- Alternativa: Master → Documentação → Carregar tutoriais padrão

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Assistente IA', 'assistente-ia', 125, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'assistente-ia');
UPDATE `platform_help_categorias` SET `titulo` = 'Assistente IA', `ordem` = 125, `ativo` = 1 WHERE `slug` = 'assistente-ia';

-- Artigo: assistente-ia-visao-geral
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id,
  'O que é o Assistente IA',
  'assistente-ia-visao-geral',
  'Bot Telegram nativo que consulta dados da escola em modo somente leitura.',
  '<p>O <strong>Assistente IA</strong> é um bot do Telegram integrado ao painel. Ele consulta agenda, inadimplentes, CRM, WhatsApp e outros indicadores — sempre em modo somente leitura.</p><p><strong>Onde encontrar:</strong> Configurações → Configurações de IA</p><h2>Passo a passo</h2><ol><li>O módulo precisa estar liberado no <strong>plano</strong> da escola (slug <code>assistente_ia</code>).</li><li>O Diretor configura tudo em <strong>Configurações → Configurações de IA</strong>: chave compartilhada, bot do Telegram e Chat ID autorizado.</li><li>Por padrão o bot responde com <strong>palavras-chave</strong> (sem gastar tokens). A IA em perguntas livres é opcional.</li><li>Independente da <strong>IA Pedagógica</strong> do portal EAD (mesmo que compartilhem a mesma API key).</li></ol><h2>Dicas e cuidados</h2><p><strong>Segurança:</strong> só os Chat IDs cadastrados recebem resposta. Não compartilhe o token do bot.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
  NULL,
  'Tutorial: O que é o Assistente IA',
  10,
  1
FROM `platform_help_categorias` c WHERE c.slug = 'assistente-ia'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assistente-ia-visao-geral');

UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'assistente-ia'
SET a.id_categoria = c.id,
    a.titulo = 'O que é o Assistente IA',
    a.resumo = 'Bot Telegram nativo que consulta dados da escola em modo somente leitura.',
    a.corpo = '<p>O <strong>Assistente IA</strong> é um bot do Telegram integrado ao painel. Ele consulta agenda, inadimplentes, CRM, WhatsApp e outros indicadores — sempre em modo somente leitura.</p><p><strong>Onde encontrar:</strong> Configurações → Configurações de IA</p><h2>Passo a passo</h2><ol><li>O módulo precisa estar liberado no <strong>plano</strong> da escola (slug <code>assistente_ia</code>).</li><li>O Diretor configura tudo em <strong>Configurações → Configurações de IA</strong>: chave compartilhada, bot do Telegram e Chat ID autorizado.</li><li>Por padrão o bot responde com <strong>palavras-chave</strong> (sem gastar tokens). A IA em perguntas livres é opcional.</li><li>Independente da <strong>IA Pedagógica</strong> do portal EAD (mesmo que compartilhem a mesma API key).</li></ol><h2>Dicas e cuidados</h2><p><strong>Segurança:</strong> só os Chat IDs cadastrados recebem resposta. Não compartilhe o token do bot.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.ordem = 10,
    a.publicado = 1,
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: O que é o Assistente IA', a.video_titulo)
WHERE a.slug = 'assistente-ia-visao-geral';

-- Artigo: assistente-ia-configurar-escola
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id,
  'Configurar IA e Telegram (Diretor)',
  'assistente-ia-configurar-escola',
  'Como o Diretor liga o bot nativo: credenciais, token e Chat ID.',
  '<p>Nesta tela você ativa o Assistente e cadastra o bot do Telegram com segurança.</p><p><strong>Onde encontrar:</strong> Configurações → Configurações de IA</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Configurações de IA</strong> (Diretor, com o módulo no plano).</li><li>Em <strong>Credenciais compartilhadas</strong>: provedor, modelo e API key.</li><li>Ative <strong>Ativar Assistente no Telegram</strong>.</li><li>Opcional: ligue <strong>Usar IA em perguntas livres</strong> (gasta tokens). Desligado = só /resumo, /agenda, etc.</li><li>Crie o bot no <code>@BotFather</code>, cole o token, o username e o <strong>Chat ID autorizado</strong> (obrigatório; use @userinfobot).</li><li>Clique em <strong>Salvar</strong>, depois <strong>Enviar teste</strong>. Em produção HTTPS: <strong>Ativar webhook</strong>.</li></ol><h2>Dicas e cuidados</h2><ul><li>Não compartilhe tokens em grupos ou WhatsApp.</li><li>Em XAMPP (sem HTTPS), use o worker <code>php worker/telegram_agent.php</code>.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
  NULL,
  'Tutorial: Configurar IA e Telegram (Diretor)',
  20,
  1
FROM `platform_help_categorias` c WHERE c.slug = 'assistente-ia'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assistente-ia-configurar-escola');

UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'assistente-ia'
SET a.id_categoria = c.id,
    a.titulo = 'Configurar IA e Telegram (Diretor)',
    a.resumo = 'Como o Diretor liga o bot nativo: credenciais, token e Chat ID.',
    a.corpo = '<p>Nesta tela você ativa o Assistente e cadastra o bot do Telegram com segurança.</p><p><strong>Onde encontrar:</strong> Configurações → Configurações de IA</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Configurações de IA</strong> (Diretor, com o módulo no plano).</li><li>Em <strong>Credenciais compartilhadas</strong>: provedor, modelo e API key.</li><li>Ative <strong>Ativar Assistente no Telegram</strong>.</li><li>Opcional: ligue <strong>Usar IA em perguntas livres</strong> (gasta tokens). Desligado = só /resumo, /agenda, etc.</li><li>Crie o bot no <code>@BotFather</code>, cole o token, o username e o <strong>Chat ID autorizado</strong> (obrigatório; use @userinfobot).</li><li>Clique em <strong>Salvar</strong>, depois <strong>Enviar teste</strong>. Em produção HTTPS: <strong>Ativar webhook</strong>.</li></ol><h2>Dicas e cuidados</h2><ul><li>Não compartilhe tokens em grupos ou WhatsApp.</li><li>Em XAMPP (sem HTTPS), use o worker <code>php worker/telegram_agent.php</code>.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.ordem = 20,
    a.publicado = 1,
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Configurar IA e Telegram (Diretor)', a.video_titulo)
WHERE a.slug = 'assistente-ia-configurar-escola';

-- Artigo: assistente-ia-vs-pedagogica
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id,
  'Uma tela de IA, vários recursos',
  'assistente-ia-vs-pedagogica',
  'Credenciais únicas com toggles por módulo do plano.',
  '<p>O painel usa <strong>uma</strong> configuração de chave de IA, com interruptores por recurso liberado no plano.</p><p><strong>Onde encontrar:</strong> Configurações → Configurações de IA</p><h2>Passo a passo</h2><ol><li><strong>IA Pedagógica</strong>: tutor e role play no <strong>portal do aluno (EAD)</strong>.</li><li><strong>Assistente IA</strong>: bot Telegram nativo (consulta agenda, financeiro, CRM).</li><li><strong>Variar textos WhatsApp</strong>: anti-template em campanhas.</li><li>Tudo em <em>Configurações → Configurações de IA</em>.</li></ol><h2>Dicas e cuidados</h2><p>Ativar só a pedagógica <strong>não</strong> liga o bot do Telegram — e vice-versa.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
  NULL,
  'Tutorial: Uma tela de IA, vários recursos',
  30,
  1
FROM `platform_help_categorias` c WHERE c.slug = 'assistente-ia'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assistente-ia-vs-pedagogica');

UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'assistente-ia'
SET a.id_categoria = c.id,
    a.titulo = 'Uma tela de IA, vários recursos',
    a.resumo = 'Credenciais únicas com toggles por módulo do plano.',
    a.corpo = '<p>O painel usa <strong>uma</strong> configuração de chave de IA, com interruptores por recurso liberado no plano.</p><p><strong>Onde encontrar:</strong> Configurações → Configurações de IA</p><h2>Passo a passo</h2><ol><li><strong>IA Pedagógica</strong>: tutor e role play no <strong>portal do aluno (EAD)</strong>.</li><li><strong>Assistente IA</strong>: bot Telegram nativo (consulta agenda, financeiro, CRM).</li><li><strong>Variar textos WhatsApp</strong>: anti-template em campanhas.</li><li>Tudo em <em>Configurações → Configurações de IA</em>.</li></ol><h2>Dicas e cuidados</h2><p>Ativar só a pedagógica <strong>não</strong> liga o bot do Telegram — e vice-versa.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.ordem = 30,
    a.publicado = 1,
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Uma tela de IA, vários recursos', a.video_titulo)
WHERE a.slug = 'assistente-ia-vs-pedagogica';

-- Artigo: assistente-ia-ativacao-seguranca (antes: Agent API / OpenClaw)
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id,
  'Segurança do bot e Chat ID',
  'assistente-ia-ativacao-seguranca',
  'Allowlist de chats, token do bot e boas práticas.',
  '<p>O Assistente só responde a chats autorizados. Isso protege os dados da escola.</p><p><strong>Onde encontrar:</strong> Configurações → Configurações de IA</p><h2>Passo a passo</h2><ol><li>Cadastre o <strong>Chat ID</strong> em Configurações de IA (vários IDs separados por vírgula).</li><li>Descubra o ID falando com <code>@userinfobot</code> (ou equivalente) no Telegram.</li><li>O token do bot fica criptografado no painel — nunca envie por WhatsApp.</li><li>Em produção, use <strong>webhook HTTPS</strong>. Em local, use o worker long-poll.</li><li>Para pausar: desative <strong>Ativar Assistente no Telegram</strong> e salve (ou remova o webhook).</li></ol><h2>Dicas e cuidados</h2><ul><li>Não reutilize o mesmo bot em duas escolas.</li><li>Se suspeitar de vazamento do token, revogue no @BotFather e cadastre um novo.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
  NULL,
  'Tutorial: Segurança do bot e Chat ID',
  40,
  1
FROM `platform_help_categorias` c WHERE c.slug = 'assistente-ia'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assistente-ia-ativacao-seguranca');

UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'assistente-ia'
SET a.id_categoria = c.id,
    a.titulo = 'Segurança do bot e Chat ID',
    a.resumo = 'Allowlist de chats, token do bot e boas práticas.',
    a.corpo = '<p>O Assistente só responde a chats autorizados. Isso protege os dados da escola.</p><p><strong>Onde encontrar:</strong> Configurações → Configurações de IA</p><h2>Passo a passo</h2><ol><li>Cadastre o <strong>Chat ID</strong> em Configurações de IA (vários IDs separados por vírgula).</li><li>Descubra o ID falando com <code>@userinfobot</code> (ou equivalente) no Telegram.</li><li>O token do bot fica criptografado no painel — nunca envie por WhatsApp.</li><li>Em produção, use <strong>webhook HTTPS</strong>. Em local, use o worker long-poll.</li><li>Para pausar: desative <strong>Ativar Assistente no Telegram</strong> e salve (ou remova o webhook).</li></ol><h2>Dicas e cuidados</h2><ul><li>Não reutilize o mesmo bot em duas escolas.</li><li>Se suspeitar de vazamento do token, revogue no @BotFather e cadastre um novo.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.ordem = 40,
    a.publicado = 1,
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Segurança do bot e Chat ID', a.video_titulo)
WHERE a.slug = 'assistente-ia-ativacao-seguranca';

-- Artigo: assistente-ia-consultas
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id,
  'O que o Assistente pode consultar',
  'assistente-ia-consultas',
  'Consultas somente leitura: agenda, inadimplentes, CRM, matrículas, WhatsApp e mais.',
  '<p>O Assistente é <strong>somente leitura</strong>: analisa e responde; não dá baixa, não altera matrícula e não envia WhatsApp pelo painel.</p><p><strong>Onde encontrar:</strong> Telegram do Assistente IA</p><h2>Passo a passo</h2><ol><li><strong>Resumo do dia:</strong> matrículas ativas, recebido hoje, a receber na semana, CRM e indicadores rápidos.</li><li><strong>Agenda:</strong> aulas/agendamentos de hoje.</li><li><strong>Financeiro:</strong> inadimplentes (mês/semana/hoje) e títulos a receber.</li><li><strong>CRM:</strong> leads e conversão no período.</li><li><strong>Matrículas:</strong> ativas e novas no mês.</li><li><strong>WhatsApp:</strong> fila, não lidas e conversas abertas (quando o módulo existir).</li></ol><h2>Dicas e cuidados</h2><p>Comandos sem IA: <code>/resumo</code>, <code>/agenda</code>, <code>/ajuda</code> e outros listados no próprio bot.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
  NULL,
  'Tutorial: O que o Assistente pode consultar',
  50,
  1
FROM `platform_help_categorias` c WHERE c.slug = 'assistente-ia'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assistente-ia-consultas');

UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'assistente-ia'
SET a.id_categoria = c.id,
    a.titulo = 'O que o Assistente pode consultar',
    a.resumo = 'Consultas somente leitura: agenda, inadimplentes, CRM, matrículas, WhatsApp e mais.',
    a.corpo = '<p>O Assistente é <strong>somente leitura</strong>: analisa e responde; não dá baixa, não altera matrícula e não envia WhatsApp pelo painel.</p><p><strong>Onde encontrar:</strong> Telegram do Assistente IA</p><h2>Passo a passo</h2><ol><li><strong>Resumo do dia:</strong> matrículas ativas, recebido hoje, a receber na semana, CRM e indicadores rápidos.</li><li><strong>Agenda:</strong> aulas/agendamentos de hoje.</li><li><strong>Financeiro:</strong> inadimplentes (mês/semana/hoje) e títulos a receber.</li><li><strong>CRM:</strong> leads e conversão no período.</li><li><strong>Matrículas:</strong> ativas e novas no mês.</li><li><strong>WhatsApp:</strong> fila, não lidas e conversas abertas (quando o módulo existir).</li></ol><h2>Dicas e cuidados</h2><p>Comandos sem IA: <code>/resumo</code>, <code>/agenda</code>, <code>/ajuda</code> e outros listados no próprio bot.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.ordem = 50,
    a.publicado = 1,
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: O que o Assistente pode consultar', a.video_titulo)
WHERE a.slug = 'assistente-ia-consultas';

-- Artigo ia-pedagogica (categoria Configurações): remove menções OpenClaw
UPDATE `platform_help_artigos` a
SET a.resumo = 'Uma chave compartilhada: pedagógica (EAD), Assistente (Telegram) e variação WhatsApp.',
    a.corpo = '<p>Em <strong>Configurações de IA</strong> você cadastra <strong>uma</strong> chave (provedor + modelo + API key) e liga os recursos liberados no plano.</p><p><strong>Onde encontrar:</strong> Configurações → Configurações de IA</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Configurações de IA</strong> (Diretor).</li><li>Preencha provedor, modelo e API key (compartilhados).</li><li>Se o plano tiver EAD: ative <strong>IA Pedagógica</strong> para tutor/role play no portal do aluno.</li><li>Se o plano tiver Assistente IA: ative o Assistente no Telegram, informe o bot e o Chat ID autorizado.</li><li>Se o plano tiver WhatsApp: opcionalmente ative <strong>Variar textos das campanhas</strong>.</li></ol><h2>Dicas e cuidados</h2><p>O Assistente no Telegram é nativo do painel — não depende de serviços externos além do próprio Telegram.</p><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>'
WHERE a.slug = 'ia-pedagogica';
