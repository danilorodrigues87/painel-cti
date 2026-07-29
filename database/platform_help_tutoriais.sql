-- =============================================================================
-- Tutoriais da Central de Ajuda (Painel CTI)
-- Cole no phpMyAdmin DEPOIS de database/platform_help.sql (tabelas criadas).
-- video_url fica NULL. Se já houver URL de vídeo, o UPDATE preserva.
-- Gerado por App\Common\Help\PlatformHelpSeed::gerarSql()
-- =============================================================================
SET NAMES utf8mb4;

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Primeiros passos', 'primeiros-passos', 10, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'primeiros-passos');
UPDATE `platform_help_categorias` SET `titulo` = 'Primeiros passos', `ordem` = 10, `ativo` = 1 WHERE `slug` = 'primeiros-passos';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Usuários e cadastros', 'usuarios-cadastros', 20, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'usuarios-cadastros');
UPDATE `platform_help_categorias` SET `titulo` = 'Usuários e cadastros', `ordem` = 20, `ativo` = 1 WHERE `slug` = 'usuarios-cadastros';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Pedagógico', 'pedagogico', 30, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'pedagogico');
UPDATE `platform_help_categorias` SET `titulo` = 'Pedagógico', `ordem` = 30, `ativo` = 1 WHERE `slug` = 'pedagogico';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Portal EAD / Cursos Online', 'portal-ead', 40, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'portal-ead');
UPDATE `platform_help_categorias` SET `titulo` = 'Portal EAD / Cursos Online', `ordem` = 40, `ativo` = 1 WHERE `slug` = 'portal-ead';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'CRM e Leads', 'crm-leads', 50, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'crm-leads');
UPDATE `platform_help_categorias` SET `titulo` = 'CRM e Leads', `ordem` = 50, `ativo` = 1 WHERE `slug` = 'crm-leads';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'WhatsApp', 'whatsapp', 60, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'whatsapp');
UPDATE `platform_help_categorias` SET `titulo` = 'WhatsApp', `ordem` = 60, `ativo` = 1 WHERE `slug` = 'whatsapp';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Redes sociais', 'redes-sociais', 70, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'redes-sociais');
UPDATE `platform_help_categorias` SET `titulo` = 'Redes sociais', `ordem` = 70, `ativo` = 1 WHERE `slug` = 'redes-sociais';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Campanhas e e-mail', 'campanhas-email', 80, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'campanhas-email');
UPDATE `platform_help_categorias` SET `titulo` = 'Campanhas e e-mail', `ordem` = 80, `ativo` = 1 WHERE `slug` = 'campanhas-email';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Financeiro', 'financeiro', 90, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'financeiro');
UPDATE `platform_help_categorias` SET `titulo` = 'Financeiro', `ordem` = 90, `ativo` = 1 WHERE `slug` = 'financeiro';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Vendas e estoque', 'vendas-estoque', 100, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'vendas-estoque');
UPDATE `platform_help_categorias` SET `titulo` = 'Vendas e estoque', `ordem` = 100, `ativo` = 1 WHERE `slug` = 'vendas-estoque';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Agenda', 'agenda', 110, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'agenda');
UPDATE `platform_help_categorias` SET `titulo` = 'Agenda', `ordem` = 110, `ativo` = 1 WHERE `slug` = 'agenda';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Configurações', 'configuracoes', 120, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'configuracoes');
UPDATE `platform_help_categorias` SET `titulo` = 'Configurações', `ordem` = 120, `ativo` = 1 WHERE `slug` = 'configuracoes';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Suporte', 'suporte', 130, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'suporte');
UPDATE `platform_help_categorias` SET `titulo` = 'Suporte', `ordem` = 130, `ativo` = 1 WHERE `slug` = 'suporte';

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Portal do aluno', 'portal-aluno', 140, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'portal-aluno');
UPDATE `platform_help_categorias` SET `titulo` = 'Portal do aluno', `ordem` = 140, `ativo` = 1 WHERE `slug` = 'portal-aluno';

-- Artigo: bem-vindo
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Bem-vindo ao Painel CTI', 'bem-vindo', 'Visão geral do painel, menu e o que cada área faz.', '<p>O Painel CTI é o sistema completo da sua escola: cadastros, matrículas, financeiro, CRM, WhatsApp, campanhas, EAD, redes sociais e suporte — conforme o plano contratado e as permissões de cada usuário.</p><p><strong>Onde encontrar:</strong> Dashboard e menu lateral</p><h2>Passo a passo</h2><ol><li>Acesse <strong>/painel</strong> com o e-mail e a senha fornecidos pela escola ou pelo suporte CTI.</li><li>No primeiro acesso, leia e aceite os <strong>Termos de Uso (LGPD)</strong> — sem isso os módulos ficam bloqueados.</li><li>Explore o <strong>menu lateral</strong>: só aparecem itens liberados no plano da escola e no checklist do seu usuário.</li><li>Use o <strong>Dashboard</strong> (página inicial) para ver resumos de matrículas, financeiro, leads e indicadores.</li><li>Em dúvida, abra <strong>Ajuda</strong> (esta central) ou <strong>Suporte</strong> para abrir um chamado com a equipe CTI.</li><li>No topo você pode alternar o <strong>tema claro/escuro</strong> e acessar o perfil.</li></ol><h2>Dicas e cuidados</h2><ul><li>Diretor costuma ter mais módulos liberados automaticamente.</li><li>Funcionários recebem permissões no cadastro de usuários.</li><li>Não compartilhe login entre pessoas.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Bem-vindo ao Painel CTI', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'primeiros-passos'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'bem-vindo');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'primeiros-passos'
SET a.id_categoria = c.id,
    a.titulo = 'Bem-vindo ao Painel CTI',
    a.resumo = 'Visão geral do painel, menu e o que cada área faz.',
    a.corpo = '<p>O Painel CTI é o sistema completo da sua escola: cadastros, matrículas, financeiro, CRM, WhatsApp, campanhas, EAD, redes sociais e suporte — conforme o plano contratado e as permissões de cada usuário.</p><p><strong>Onde encontrar:</strong> Dashboard e menu lateral</p><h2>Passo a passo</h2><ol><li>Acesse <strong>/painel</strong> com o e-mail e a senha fornecidos pela escola ou pelo suporte CTI.</li><li>No primeiro acesso, leia e aceite os <strong>Termos de Uso (LGPD)</strong> — sem isso os módulos ficam bloqueados.</li><li>Explore o <strong>menu lateral</strong>: só aparecem itens liberados no plano da escola e no checklist do seu usuário.</li><li>Use o <strong>Dashboard</strong> (página inicial) para ver resumos de matrículas, financeiro, leads e indicadores.</li><li>Em dúvida, abra <strong>Ajuda</strong> (esta central) ou <strong>Suporte</strong> para abrir um chamado com a equipe CTI.</li><li>No topo você pode alternar o <strong>tema claro/escuro</strong> e acessar o perfil.</li></ol><h2>Dicas e cuidados</h2><ul><li>Diretor costuma ter mais módulos liberados automaticamente.</li><li>Funcionários recebem permissões no cadastro de usuários.</li><li>Não compartilhe login entre pessoas.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Bem-vindo ao Painel CTI', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'bem-vindo';

-- Artigo: dashboard
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Dashboard (visão geral)', 'dashboard', 'Entender os gráficos e atalhos da página inicial.', '<p>O Dashboard resume a operação da escola em um só lugar: indicadores, gráficos e caminhos rápidos para o dia a dia.</p><p><strong>Onde encontrar:</strong> Menu → Dashboard</p><h2>Passo a passo</h2><ol><li>Abra o menu <strong>Dashboard</strong> (ou clique no logo / painel).</li><li>Confira os cartões e gráficos disponíveis no seu perfil (podem variar conforme permissões).</li><li>Use os atalhos para ir a CRM, matrículas, carnês ou WhatsApp quando aparecerem.</li><li>Os gráficos respeitam o tema claro/escuro do painel.</li></ol><h2>Dicas e cuidados</h2><ul><li>Se algum indicador parecer zerado, confira se há dados no período e se o módulo está liberado.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Dashboard (visão geral)', 15, 1
FROM `platform_help_categorias` c WHERE c.slug = 'primeiros-passos'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'dashboard');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'primeiros-passos'
SET a.id_categoria = c.id,
    a.titulo = 'Dashboard (visão geral)',
    a.resumo = 'Entender os gráficos e atalhos da página inicial.',
    a.corpo = '<p>O Dashboard resume a operação da escola em um só lugar: indicadores, gráficos e caminhos rápidos para o dia a dia.</p><p><strong>Onde encontrar:</strong> Menu → Dashboard</p><h2>Passo a passo</h2><ol><li>Abra o menu <strong>Dashboard</strong> (ou clique no logo / painel).</li><li>Confira os cartões e gráficos disponíveis no seu perfil (podem variar conforme permissões).</li><li>Use os atalhos para ir a CRM, matrículas, carnês ou WhatsApp quando aparecerem.</li><li>Os gráficos respeitam o tema claro/escuro do painel.</li></ol><h2>Dicas e cuidados</h2><ul><li>Se algum indicador parecer zerado, confira se há dados no período e se o módulo está liberado.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Dashboard (visão geral)', a.video_titulo),
    a.ordem = 15,
    a.publicado = 1
WHERE a.slug = 'dashboard';

-- Artigo: permissoes-acesso
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Permissões e níveis de acesso', 'permissoes-acesso', 'Como o diretor libera módulos e o que cada perfil enxerga.', '<p>O que aparece no menu depende de dois filtros: o plano da escola e as permissões do usuário logado.</p><p><strong>Onde encontrar:</strong> Usuários → Funcionários</p><h2>Passo a passo</h2><ol><li>O <strong>plano</strong> da escola (Master CTI) define quais módulos podem ser usados (ex.: WhatsApp, EAD, Redes sociais).</li><li>Em <strong>Usuários → Funcionários</strong>, o diretor marca as permissões de cada pessoa no checklist.</li><li>Módulos como <strong>Redes sociais</strong> exigem permissão explícita no usuário (não bastam só o plano).</li><li>Diretor recebe automaticamente vários itens (Comunicação, Campanhas, WhatsApp, Assinatura, Dados da escola…).</li><li>Se alguém não vê um menu, confira nesta ordem: plano da escola → checklist do usuário → aceite dos Termos.</li></ol><h2>Dicas e cuidados</h2><ul><li>Não compartilhe login entre funcionários.</li><li>Revise permissões ao mudar de função.</li><li>Relatórios CRM são exclusivos do Diretor (com permissão de Leads).</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Permissões e níveis de acesso', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'primeiros-passos'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'permissoes-acesso');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'primeiros-passos'
SET a.id_categoria = c.id,
    a.titulo = 'Permissões e níveis de acesso',
    a.resumo = 'Como o diretor libera módulos e o que cada perfil enxerga.',
    a.corpo = '<p>O que aparece no menu depende de dois filtros: o plano da escola e as permissões do usuário logado.</p><p><strong>Onde encontrar:</strong> Usuários → Funcionários</p><h2>Passo a passo</h2><ol><li>O <strong>plano</strong> da escola (Master CTI) define quais módulos podem ser usados (ex.: WhatsApp, EAD, Redes sociais).</li><li>Em <strong>Usuários → Funcionários</strong>, o diretor marca as permissões de cada pessoa no checklist.</li><li>Módulos como <strong>Redes sociais</strong> exigem permissão explícita no usuário (não bastam só o plano).</li><li>Diretor recebe automaticamente vários itens (Comunicação, Campanhas, WhatsApp, Assinatura, Dados da escola…).</li><li>Se alguém não vê um menu, confira nesta ordem: plano da escola → checklist do usuário → aceite dos Termos.</li></ol><h2>Dicas e cuidados</h2><ul><li>Não compartilhe login entre funcionários.</li><li>Revise permissões ao mudar de função.</li><li>Relatórios CRM são exclusivos do Diretor (com permissão de Leads).</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Permissões e níveis de acesso', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'permissoes-acesso';

-- Artigo: termos-lgpd
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Termos de Uso e LGPD', 'termos-lgpd', 'Aceite obrigatório no primeiro acesso e onde consultar depois.', '<p>O aceite dos Termos de Uso é obrigatório para usar o painel, alinhado à LGPD e às regras da plataforma CTI.</p><p><strong>Onde encontrar:</strong> Menu → Termos de Uso</p><h2>Passo a passo</h2><ol><li>No primeiro login, leia o texto completo e clique em aceitar.</li><li>Depois, você pode consultar em <strong>Termos de Uso</strong> no menu.</li><li>Cadastre dados de alunos, responsáveis e leads com cuidado: use só o necessário para a operação da escola.</li><li>Quando a CTI publicar uma nova versão dos termos, o sistema pode pedir novo aceite.</li></ol><h2>Dicas e cuidados</h2><ul><li>Sem o aceite, o acesso aos módulos fica bloqueado e você é direcionado à tela de Termos.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Termos de Uso e LGPD', 30, 1
FROM `platform_help_categorias` c WHERE c.slug = 'primeiros-passos'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'termos-lgpd');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'primeiros-passos'
SET a.id_categoria = c.id,
    a.titulo = 'Termos de Uso e LGPD',
    a.resumo = 'Aceite obrigatório no primeiro acesso e onde consultar depois.',
    a.corpo = '<p>O aceite dos Termos de Uso é obrigatório para usar o painel, alinhado à LGPD e às regras da plataforma CTI.</p><p><strong>Onde encontrar:</strong> Menu → Termos de Uso</p><h2>Passo a passo</h2><ol><li>No primeiro login, leia o texto completo e clique em aceitar.</li><li>Depois, você pode consultar em <strong>Termos de Uso</strong> no menu.</li><li>Cadastre dados de alunos, responsáveis e leads com cuidado: use só o necessário para a operação da escola.</li><li>Quando a CTI publicar uma nova versão dos termos, o sistema pode pedir novo aceite.</li></ol><h2>Dicas e cuidados</h2><ul><li>Sem o aceite, o acesso aos módulos fica bloqueado e você é direcionado à tela de Termos.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Termos de Uso e LGPD', a.video_titulo),
    a.ordem = 30,
    a.publicado = 1
WHERE a.slug = 'termos-lgpd';

-- Artigo: perfil-senha
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Perfil e senha', 'perfil-senha', 'Atualizar dados pessoais, foto e senha do usuário.', '<p>Cada usuário gerencia o próprio perfil: nome, contato, foto e senha.</p><p><strong>Onde encontrar:</strong> Menu → Perfil</p><h2>Passo a passo</h2><ol><li>Abra <strong>Perfil</strong> no menu (ou pelo atalho do usuário no topo).</li><li>Atualize nome, e-mail e demais campos disponíveis.</li><li>Altere a senha quando necessário (use senha forte e pessoal).</li><li>Envie foto de perfil se a tela permitir.</li></ol><h2>Dicas e cuidados</h2><ul><li>Não use e-mails fictícios.</li><li>Em caso de esquecimento de senha, use a recuperação na tela de login.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Perfil e senha', 40, 1
FROM `platform_help_categorias` c WHERE c.slug = 'primeiros-passos'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'perfil-senha');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'primeiros-passos'
SET a.id_categoria = c.id,
    a.titulo = 'Perfil e senha',
    a.resumo = 'Atualizar dados pessoais, foto e senha do usuário.',
    a.corpo = '<p>Cada usuário gerencia o próprio perfil: nome, contato, foto e senha.</p><p><strong>Onde encontrar:</strong> Menu → Perfil</p><h2>Passo a passo</h2><ol><li>Abra <strong>Perfil</strong> no menu (ou pelo atalho do usuário no topo).</li><li>Atualize nome, e-mail e demais campos disponíveis.</li><li>Altere a senha quando necessário (use senha forte e pessoal).</li><li>Envie foto de perfil se a tela permitir.</li></ol><h2>Dicas e cuidados</h2><ul><li>Não use e-mails fictícios.</li><li>Em caso de esquecimento de senha, use a recuperação na tela de login.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Perfil e senha', a.video_titulo),
    a.ordem = 40,
    a.publicado = 1
WHERE a.slug = 'perfil-senha';

-- Artigo: funcionarios
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Cadastrar e gerenciar funcionários', 'funcionarios', 'Criar usuários internos, senha, permissões e status.', '<p>Funcionários são usuários internos do painel (secretaria, comercial, financeiro etc.).</p><p><strong>Onde encontrar:</strong> Usuários → Funcionários</p><h2>Passo a passo</h2><ol><li>Abra <strong>Usuários → Funcionários</strong>.</li><li>Clique em novo cadastro e preencha nome, e-mail válido e senha.</li><li>Marque as <strong>permissões</strong> conforme a função (CRM, Carnês, WhatsApp…).</li><li>Salve e peça para a pessoa fazer o primeiro login e aceitar os Termos.</li><li>Para desativar acesso, edite o usuário e ajuste o status conforme a tela.</li></ol><h2>Dicas e cuidados</h2><ul><li>E-mail é obrigatório e não pode ser fictício.</li><li>Prefira um usuário por pessoa.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Cadastrar e gerenciar funcionários', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'usuarios-cadastros'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'funcionarios');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'usuarios-cadastros'
SET a.id_categoria = c.id,
    a.titulo = 'Cadastrar e gerenciar funcionários',
    a.resumo = 'Criar usuários internos, senha, permissões e status.',
    a.corpo = '<p>Funcionários são usuários internos do painel (secretaria, comercial, financeiro etc.).</p><p><strong>Onde encontrar:</strong> Usuários → Funcionários</p><h2>Passo a passo</h2><ol><li>Abra <strong>Usuários → Funcionários</strong>.</li><li>Clique em novo cadastro e preencha nome, e-mail válido e senha.</li><li>Marque as <strong>permissões</strong> conforme a função (CRM, Carnês, WhatsApp…).</li><li>Salve e peça para a pessoa fazer o primeiro login e aceitar os Termos.</li><li>Para desativar acesso, edite o usuário e ajuste o status conforme a tela.</li></ol><h2>Dicas e cuidados</h2><ul><li>E-mail é obrigatório e não pode ser fictício.</li><li>Prefira um usuário por pessoa.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Cadastrar e gerenciar funcionários', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'funcionarios';

-- Artigo: alunos
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Cadastro de alunos', 'alunos', 'Incluir alunos, dados de contato, responsável e observações.', '<p>Alunos são o núcleo pedagógico e financeiro da escola.</p><p><strong>Onde encontrar:</strong> Usuários → Alunos</p><h2>Passo a passo</h2><ol><li>Abra <strong>Usuários → Alunos</strong>.</li><li>Cadastre nome, documentos, contatos e vínculo com responsável (quando houver).</li><li>Informe e-mail e WhatsApp válidos para cobrança e comunicação.</li><li>Use observações para anotações internas.</li><li>Depois do cadastro, vincule o aluno a uma <strong>matrícula</strong> na trilha desejada.</li></ol><h2>Dicas e cuidados</h2><ul><li>E-mail opcional, mas se preencher deve ser real.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Cadastro de alunos', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'usuarios-cadastros'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'alunos');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'usuarios-cadastros'
SET a.id_categoria = c.id,
    a.titulo = 'Cadastro de alunos',
    a.resumo = 'Incluir alunos, dados de contato, responsável e observações.',
    a.corpo = '<p>Alunos são o núcleo pedagógico e financeiro da escola.</p><p><strong>Onde encontrar:</strong> Usuários → Alunos</p><h2>Passo a passo</h2><ol><li>Abra <strong>Usuários → Alunos</strong>.</li><li>Cadastre nome, documentos, contatos e vínculo com responsável (quando houver).</li><li>Informe e-mail e WhatsApp válidos para cobrança e comunicação.</li><li>Use observações para anotações internas.</li><li>Depois do cadastro, vincule o aluno a uma <strong>matrícula</strong> na trilha desejada.</li></ol><h2>Dicas e cuidados</h2><ul><li>E-mail opcional, mas se preencher deve ser real.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Cadastro de alunos', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'alunos';

-- Artigo: responsaveis
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Cadastro de responsáveis', 'responsaveis', 'Pais/responsáveis financeiros e de contato.', '<p>Responsáveis recebem cobranças e comunicações quando a escola assim define.</p><p><strong>Onde encontrar:</strong> Usuários → Responsáveis</p><h2>Passo a passo</h2><ol><li>Abra <strong>Usuários → Responsáveis</strong>.</li><li>Cadastre nome, telefone, e-mail e documentos.</li><li>Vincule o responsável aos alunos correspondentes.</li><li>Confira e-mail/WhatsApp antes de disparar campanhas ou carnês.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Cadastro de responsáveis', 30, 1
FROM `platform_help_categorias` c WHERE c.slug = 'usuarios-cadastros'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'responsaveis');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'usuarios-cadastros'
SET a.id_categoria = c.id,
    a.titulo = 'Cadastro de responsáveis',
    a.resumo = 'Pais/responsáveis financeiros e de contato.',
    a.corpo = '<p>Responsáveis recebem cobranças e comunicações quando a escola assim define.</p><p><strong>Onde encontrar:</strong> Usuários → Responsáveis</p><h2>Passo a passo</h2><ol><li>Abra <strong>Usuários → Responsáveis</strong>.</li><li>Cadastre nome, telefone, e-mail e documentos.</li><li>Vincule o responsável aos alunos correspondentes.</li><li>Confira e-mail/WhatsApp antes de disparar campanhas ou carnês.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Cadastro de responsáveis', a.video_titulo),
    a.ordem = 30,
    a.publicado = 1
WHERE a.slug = 'responsaveis';

-- Artigo: trilhas-categorias
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Trilhas e categorias de curso', 'trilhas-categorias', 'Estrutura comercial dos cursos presenciais/contratos.', '<p>Trilhas representam os cursos/planos comerciais da escola (contrato e carnê). São diferentes dos cursos EAD.</p><p><strong>Onde encontrar:</strong> Pedagógico → Categorias / Trilhas</p><h2>Passo a passo</h2><ol><li>Em <strong>Pedagógico → Categorias</strong>, organize as áreas.</li><li>Em <strong>Pedagógico → Trilhas</strong>, crie ou edite a trilha (nome, valores, status ativo).</li><li>Mantenha só trilhas ativas disponíveis para novas matrículas.</li></ol><h2>Dicas e cuidados</h2><ul><li>Trilha = comercial. Curso Online (EAD) = portal Ascend.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Trilhas e categorias de curso', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'pedagogico'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'trilhas-categorias');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'pedagogico'
SET a.id_categoria = c.id,
    a.titulo = 'Trilhas e categorias de curso',
    a.resumo = 'Estrutura comercial dos cursos presenciais/contratos.',
    a.corpo = '<p>Trilhas representam os cursos/planos comerciais da escola (contrato e carnê). São diferentes dos cursos EAD.</p><p><strong>Onde encontrar:</strong> Pedagógico → Categorias / Trilhas</p><h2>Passo a passo</h2><ol><li>Em <strong>Pedagógico → Categorias</strong>, organize as áreas.</li><li>Em <strong>Pedagógico → Trilhas</strong>, crie ou edite a trilha (nome, valores, status ativo).</li><li>Mantenha só trilhas ativas disponíveis para novas matrículas.</li></ol><h2>Dicas e cuidados</h2><ul><li>Trilha = comercial. Curso Online (EAD) = portal Ascend.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Trilhas e categorias de curso', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'trilhas-categorias';

-- Artigo: matriculas
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Matrículas', 'matriculas', 'Matricular aluno em trilha, status e vínculo financeiro.', '<p>A matrícula liga o aluno a uma trilha e ao fluxo comercial.</p><p><strong>Onde encontrar:</strong> Pedagógico → Matriculas</p><h2>Passo a passo</h2><ol><li>Abra <strong>Pedagógico → Matriculas</strong>.</li><li>Selecione o aluno e a trilha desejada.</li><li>Confira datas, valores e status da matrícula.</li><li>Gere ou vincule o carnê/contrato conforme o processo da escola.</li></ol><h2>Dicas e cuidados</h2><ul><li>Matrícula comercial não libera sozinha o EAD.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Matrículas', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'pedagogico'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'matriculas');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'pedagogico'
SET a.id_categoria = c.id,
    a.titulo = 'Matrículas',
    a.resumo = 'Matricular aluno em trilha, status e vínculo financeiro.',
    a.corpo = '<p>A matrícula liga o aluno a uma trilha e ao fluxo comercial.</p><p><strong>Onde encontrar:</strong> Pedagógico → Matriculas</p><h2>Passo a passo</h2><ol><li>Abra <strong>Pedagógico → Matriculas</strong>.</li><li>Selecione o aluno e a trilha desejada.</li><li>Confira datas, valores e status da matrícula.</li><li>Gere ou vincule o carnê/contrato conforme o processo da escola.</li></ol><h2>Dicas e cuidados</h2><ul><li>Matrícula comercial não libera sozinha o EAD.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Matrículas', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'matriculas';

-- Artigo: certificacoes
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Certificações', 'certificacoes', 'Emitir e gerenciar certificados da escola.', '<p>O módulo de certificações gera documentos de conclusão.</p><p><strong>Onde encontrar:</strong> Pedagógico → Certificações</p><h2>Passo a passo</h2><ol><li>Abra <strong>Pedagógico → Certificações</strong>.</li><li>Localize o aluno/curso elegível.</li><li>Emita o certificado e faça o download/impressão.</li><li>Confira dados da escola em Configurações se algo estiver desatualizado.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Certificações', 30, 1
FROM `platform_help_categorias` c WHERE c.slug = 'pedagogico'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'certificacoes');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'pedagogico'
SET a.id_categoria = c.id,
    a.titulo = 'Certificações',
    a.resumo = 'Emitir e gerenciar certificados da escola.',
    a.corpo = '<p>O módulo de certificações gera documentos de conclusão.</p><p><strong>Onde encontrar:</strong> Pedagógico → Certificações</p><h2>Passo a passo</h2><ol><li>Abra <strong>Pedagógico → Certificações</strong>.</li><li>Localize o aluno/curso elegível.</li><li>Emita o certificado e faça o download/impressão.</li><li>Confira dados da escola em Configurações se algo estiver desatualizado.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Certificações', a.video_titulo),
    a.ordem = 30,
    a.publicado = 1
WHERE a.slug = 'certificacoes';

-- Artigo: cursos-online-ead
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Cursos Online (EAD) — visão geral', 'cursos-online-ead', 'Criar cursos, módulos e publicar no portal Ascend.', '<p>Cursos Online são independentes das trilhas comerciais. O aluno estuda no portal Ascend.</p><p><strong>Onde encontrar:</strong> Portal EAD → Cursos Online</p><h2>Passo a passo</h2><ol><li>Abra <strong>Portal EAD → Cursos Online</strong>.</li><li>Crie um curso com título, descrição e status (rascunho/publicado).</li><li>Entre no editor do curso para montar módulos e aulas.</li><li>Publique o curso quando o conteúdo estiver pronto.</li><li>Matricule alunos na aba de alunos do curso (matrícula EAD).</li></ol><h2>Dicas e cuidados</h2><ul><li>Sem matrícula EAD ativa + curso publicado, o aluno não vê o conteúdo.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Cursos Online (EAD) — visão geral', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'portal-ead'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'cursos-online-ead');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'portal-ead'
SET a.id_categoria = c.id,
    a.titulo = 'Cursos Online (EAD) — visão geral',
    a.resumo = 'Criar cursos, módulos e publicar no portal Ascend.',
    a.corpo = '<p>Cursos Online são independentes das trilhas comerciais. O aluno estuda no portal Ascend.</p><p><strong>Onde encontrar:</strong> Portal EAD → Cursos Online</p><h2>Passo a passo</h2><ol><li>Abra <strong>Portal EAD → Cursos Online</strong>.</li><li>Crie um curso com título, descrição e status (rascunho/publicado).</li><li>Entre no editor do curso para montar módulos e aulas.</li><li>Publique o curso quando o conteúdo estiver pronto.</li><li>Matricule alunos na aba de alunos do curso (matrícula EAD).</li></ol><h2>Dicas e cuidados</h2><ul><li>Sem matrícula EAD ativa + curso publicado, o aluno não vê o conteúdo.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Cursos Online (EAD) — visão geral', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'cursos-online-ead';

-- Artigo: editor-curso-aulas
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Editor de curso: aulas, vídeos e materiais', 'editor-curso-aulas', 'Montar currículo: módulos, aulas, vídeo Bunny, materiais e atividades.', '<p>O editor organiza o currículo do curso EAD.</p><p><strong>Onde encontrar:</strong> Portal EAD → Cursos Online → editor</p><h2>Passo a passo</h2><ol><li>Abra o curso e entre no editor.</li><li>Crie <strong>módulos</strong> na ordem desejada.</li><li>Adicione <strong>aulas</strong> (vídeo, texto, material, atividade, roleplay etc.).</li><li>Vídeos usam a integração <strong>Bunny Stream</strong>.</li><li>Salve e teste no portal do aluno com uma matrícula de teste.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Editor de curso: aulas, vídeos e materiais', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'portal-ead'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'editor-curso-aulas');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'portal-ead'
SET a.id_categoria = c.id,
    a.titulo = 'Editor de curso: aulas, vídeos e materiais',
    a.resumo = 'Montar currículo: módulos, aulas, vídeo Bunny, materiais e atividades.',
    a.corpo = '<p>O editor organiza o currículo do curso EAD.</p><p><strong>Onde encontrar:</strong> Portal EAD → Cursos Online → editor</p><h2>Passo a passo</h2><ol><li>Abra o curso e entre no editor.</li><li>Crie <strong>módulos</strong> na ordem desejada.</li><li>Adicione <strong>aulas</strong> (vídeo, texto, material, atividade, roleplay etc.).</li><li>Vídeos usam a integração <strong>Bunny Stream</strong>.</li><li>Salve e teste no portal do aluno com uma matrícula de teste.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Editor de curso: aulas, vídeos e materiais', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'editor-curso-aulas';

-- Artigo: vitrine-cursos
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Vitrine de cursos', 'vitrine-cursos', 'Licenciar cursos de outras escolas e gerenciar assinatura da vitrine.', '<p>A vitrine permite consumir cursos compartilhados por outras escolas.</p><p><strong>Onde encontrar:</strong> Portal EAD → Vitrine de cursos</p><h2>Passo a passo</h2><ol><li>Abra <strong>Portal EAD → Vitrine de cursos</strong>.</li><li>Veja cursos disponíveis para licenciar.</li><li>Contrate/ative a licença conforme o fluxo financeiro.</li><li>Após ativa, o curso aparece para matrícula EAD na sua escola.</li></ol><h2>Dicas e cuidados</h2><ul><li>O menu só aparece se houver oferta ou licença ativa.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Vitrine de cursos', 30, 1
FROM `platform_help_categorias` c WHERE c.slug = 'portal-ead'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'vitrine-cursos');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'portal-ead'
SET a.id_categoria = c.id,
    a.titulo = 'Vitrine de cursos',
    a.resumo = 'Licenciar cursos de outras escolas e gerenciar assinatura da vitrine.',
    a.corpo = '<p>A vitrine permite consumir cursos compartilhados por outras escolas.</p><p><strong>Onde encontrar:</strong> Portal EAD → Vitrine de cursos</p><h2>Passo a passo</h2><ol><li>Abra <strong>Portal EAD → Vitrine de cursos</strong>.</li><li>Veja cursos disponíveis para licenciar.</li><li>Contrate/ative a licença conforme o fluxo financeiro.</li><li>Após ativa, o curso aparece para matrícula EAD na sua escola.</li></ol><h2>Dicas e cuidados</h2><ul><li>O menu só aparece se houver oferta ou licença ativa.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Vitrine de cursos', a.video_titulo),
    a.ordem = 30,
    a.publicado = 1
WHERE a.slug = 'vitrine-cursos';

-- Artigo: progresso-ead
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Progresso e alunos online', 'progresso-ead', 'Acompanhar turma, % concluído, liberar aula e alunos conectados.', '<p>Acompanhe o andamento da turma no EAD.</p><p><strong>Onde encontrar:</strong> Portal EAD → Progresso EAD / Alunos online</p><h2>Passo a passo</h2><ol><li>Em <strong>Progresso EAD</strong>, filtre por curso e status.</li><li>Abra o detalhe do aluno para ver histórico de aulas.</li><li>Use <strong>Liberar próxima aula</strong> quando o avanço for manual.</li><li>Em <strong>Alunos online</strong>, veja quem está ativo.</li><li>Exporte CSV quando precisar reportar.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Progresso e alunos online', 40, 1
FROM `platform_help_categorias` c WHERE c.slug = 'portal-ead'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'progresso-ead');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'portal-ead'
SET a.id_categoria = c.id,
    a.titulo = 'Progresso e alunos online',
    a.resumo = 'Acompanhar turma, % concluído, liberar aula e alunos conectados.',
    a.corpo = '<p>Acompanhe o andamento da turma no EAD.</p><p><strong>Onde encontrar:</strong> Portal EAD → Progresso EAD / Alunos online</p><h2>Passo a passo</h2><ol><li>Em <strong>Progresso EAD</strong>, filtre por curso e status.</li><li>Abra o detalhe do aluno para ver histórico de aulas.</li><li>Use <strong>Liberar próxima aula</strong> quando o avanço for manual.</li><li>Em <strong>Alunos online</strong>, veja quem está ativo.</li><li>Exporte CSV quando precisar reportar.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Progresso e alunos online', a.video_titulo),
    a.ordem = 40,
    a.publicado = 1
WHERE a.slug = 'progresso-ead';

-- Artigo: conquistas-ead
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Conquistas EAD', 'conquistas-ead', 'Badges e gamificação do portal do aluno.', '<p>Conquistas reforçam engajamento (badges, XP, streaks).</p><p><strong>Onde encontrar:</strong> Portal EAD → Conquistas EAD</p><h2>Passo a passo</h2><ol><li>Abra <strong>Portal EAD → Conquistas EAD</strong> (se liberado).</li><li>Cadastre ou revise conquistas disponíveis.</li><li>Oriente os alunos a acompanharem o progresso no Ascend.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Conquistas EAD', 50, 1
FROM `platform_help_categorias` c WHERE c.slug = 'portal-ead'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'conquistas-ead');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'portal-ead'
SET a.id_categoria = c.id,
    a.titulo = 'Conquistas EAD',
    a.resumo = 'Badges e gamificação do portal do aluno.',
    a.corpo = '<p>Conquistas reforçam engajamento (badges, XP, streaks).</p><p><strong>Onde encontrar:</strong> Portal EAD → Conquistas EAD</p><h2>Passo a passo</h2><ol><li>Abra <strong>Portal EAD → Conquistas EAD</strong> (se liberado).</li><li>Cadastre ou revise conquistas disponíveis.</li><li>Oriente os alunos a acompanharem o progresso no Ascend.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Conquistas EAD', a.video_titulo),
    a.ordem = 50,
    a.publicado = 1
WHERE a.slug = 'conquistas-ead';

-- Artigo: crm-leads-kanban
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'CRM: funil e leads', 'crm-leads-kanban', 'Cadastrar leads, mover no funil e registrar histórico.', '<p>O CRM organiza o comercial: do interesse até a matrícula, com Kanban por status e histórico de contatos.</p><p><strong>Onde encontrar:</strong> CRM → Leads</p><h2>Passo a passo</h2><ol><li>Abra <strong>CRM → Leads</strong>.</li><li>Cadastre um lead (nome, WhatsApp, curso de interesse, origem, valor estimado).</li><li>Altere o status no funil: <em>novo</em>, <em>em atendimento</em>, <em>matriculado</em>, <em>perdido</em>.</li><li>Abra o detalhe do lead para editar dados, ver histórico e comentar.</li><li>Importe planilha quando tiver muitos leads de uma vez.</li><li>Use o botão WhatsApp no lead para abrir o Inbox (se conectado) ou o WhatsApp Web.</li></ol><h2>Dicas e cuidados</h2><ul><li>WhatsApp do lead deve ser válido para abrir conversa.</li><li>Ao mudar status, pode haver mensagem automática configurada.</li><li>Em perda, informe o motivo quando a tela pedir.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: CRM: funil e leads', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'crm-leads'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'crm-leads-kanban');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'crm-leads'
SET a.id_categoria = c.id,
    a.titulo = 'CRM: funil e leads',
    a.resumo = 'Cadastrar leads, mover no funil e registrar histórico.',
    a.corpo = '<p>O CRM organiza o comercial: do interesse até a matrícula, com Kanban por status e histórico de contatos.</p><p><strong>Onde encontrar:</strong> CRM → Leads</p><h2>Passo a passo</h2><ol><li>Abra <strong>CRM → Leads</strong>.</li><li>Cadastre um lead (nome, WhatsApp, curso de interesse, origem, valor estimado).</li><li>Altere o status no funil: <em>novo</em>, <em>em atendimento</em>, <em>matriculado</em>, <em>perdido</em>.</li><li>Abra o detalhe do lead para editar dados, ver histórico e comentar.</li><li>Importe planilha quando tiver muitos leads de uma vez.</li><li>Use o botão WhatsApp no lead para abrir o Inbox (se conectado) ou o WhatsApp Web.</li></ol><h2>Dicas e cuidados</h2><ul><li>WhatsApp do lead deve ser válido para abrir conversa.</li><li>Ao mudar status, pode haver mensagem automática configurada.</li><li>Em perda, informe o motivo quando a tela pedir.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: CRM: funil e leads', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'crm-leads-kanban';

-- Artigo: crm-tarefas
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Tarefas do CRM', 'crm-tarefas', 'Kanban de tarefas (estilo Trello) para follow-up da equipe.', '<p>Tarefas ajudam a organizar follow-ups e pendências do time comercial/secretaria em quadros com listas e cards.</p><p><strong>Onde encontrar:</strong> CRM → Tarefas</p><h2>Passo a passo</h2><ol><li>Abra <strong>CRM → Tarefas</strong>.</li><li>Crie ou renomeie listas (colunas do quadro).</li><li>Adicione cards com título e descrição.</li><li>Arraste cards entre listas conforme o andamento.</li><li>Use checklists e comentários nos cards quando disponíveis.</li></ol><h2>Dicas e cuidados</h2><ul><li>Combine com o Kanban de leads: lead no funil + tarefa de follow-up.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Tarefas do CRM', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'crm-leads'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'crm-tarefas');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'crm-leads'
SET a.id_categoria = c.id,
    a.titulo = 'Tarefas do CRM',
    a.resumo = 'Kanban de tarefas (estilo Trello) para follow-up da equipe.',
    a.corpo = '<p>Tarefas ajudam a organizar follow-ups e pendências do time comercial/secretaria em quadros com listas e cards.</p><p><strong>Onde encontrar:</strong> CRM → Tarefas</p><h2>Passo a passo</h2><ol><li>Abra <strong>CRM → Tarefas</strong>.</li><li>Crie ou renomeie listas (colunas do quadro).</li><li>Adicione cards com título e descrição.</li><li>Arraste cards entre listas conforme o andamento.</li><li>Use checklists e comentários nos cards quando disponíveis.</li></ol><h2>Dicas e cuidados</h2><ul><li>Combine com o Kanban de leads: lead no funil + tarefa de follow-up.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Tarefas do CRM', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'crm-tarefas';

-- Artigo: relatorios-crm
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Relatórios CRM (Diretor)', 'relatorios-crm', 'KPIs de leads, funis, conversão, perdas e origens.', '<p>Relatórios gerenciais do CRM, exclusivos do <strong>Diretor</strong>, com filtro por período de cadastro dos leads.</p><p><strong>Onde encontrar:</strong> CRM → Relatórios CRM</p><h2>Passo a passo</h2><ol><li>Abra <strong>CRM → Relatórios CRM</strong>.</li><li>Escolha o período (De / Até) e clique em Filtrar.</li><li>Analise os KPIs: total de leads, matriculados, perdidos, % de conversão e valor estimado.</li><li>Veja a distribuição <strong>por status</strong> e <strong>por funil</strong>.</li><li>Confira motivos de perda e as principais origens.</li></ol><h2>Dicas e cuidados</h2><ul><li>Só o Diretor vê este menu (com permissão de Leads).</li><li>Relatório detalhado de tarefas Kanban ainda está no roadmap.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Relatórios CRM (Diretor)', 30, 1
FROM `platform_help_categorias` c WHERE c.slug = 'crm-leads'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'relatorios-crm');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'crm-leads'
SET a.id_categoria = c.id,
    a.titulo = 'Relatórios CRM (Diretor)',
    a.resumo = 'KPIs de leads, funis, conversão, perdas e origens.',
    a.corpo = '<p>Relatórios gerenciais do CRM, exclusivos do <strong>Diretor</strong>, com filtro por período de cadastro dos leads.</p><p><strong>Onde encontrar:</strong> CRM → Relatórios CRM</p><h2>Passo a passo</h2><ol><li>Abra <strong>CRM → Relatórios CRM</strong>.</li><li>Escolha o período (De / Até) e clique em Filtrar.</li><li>Analise os KPIs: total de leads, matriculados, perdidos, % de conversão e valor estimado.</li><li>Veja a distribuição <strong>por status</strong> e <strong>por funil</strong>.</li><li>Confira motivos de perda e as principais origens.</li></ol><h2>Dicas e cuidados</h2><ul><li>Só o Diretor vê este menu (com permissão de Leads).</li><li>Relatório detalhado de tarefas Kanban ainda está no roadmap.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Relatórios CRM (Diretor)', a.video_titulo),
    a.ordem = 30,
    a.publicado = 1
WHERE a.slug = 'relatorios-crm';

-- Artigo: whatsapp-conexao
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Conectar WhatsApp', 'whatsapp-conexao', 'Parear o número da escola via QR Code.', '<p>O WhatsApp da escola conecta via Evolution API.</p><p><strong>Onde encontrar:</strong> WhatsApp</p><h2>Passo a passo</h2><ol><li>Abra o módulo <strong>WhatsApp</strong>.</li><li>Inicie a conexão e escaneie o <strong>QR Code</strong> com o WhatsApp da escola.</li><li>Aguarde o status conectado e faça um teste de mensagem.</li><li>Se desconectar, reconecte pelo mesmo fluxo.</li></ol><h2>Dicas e cuidados</h2><ul><li>Use um número dedicado da escola.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Conectar WhatsApp', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'whatsapp'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'whatsapp-conexao');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'whatsapp'
SET a.id_categoria = c.id,
    a.titulo = 'Conectar WhatsApp',
    a.resumo = 'Parear o número da escola via QR Code.',
    a.corpo = '<p>O WhatsApp da escola conecta via Evolution API.</p><p><strong>Onde encontrar:</strong> WhatsApp</p><h2>Passo a passo</h2><ol><li>Abra o módulo <strong>WhatsApp</strong>.</li><li>Inicie a conexão e escaneie o <strong>QR Code</strong> com o WhatsApp da escola.</li><li>Aguarde o status conectado e faça um teste de mensagem.</li><li>Se desconectar, reconecte pelo mesmo fluxo.</li></ol><h2>Dicas e cuidados</h2><ul><li>Use um número dedicado da escola.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Conectar WhatsApp', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'whatsapp-conexao';

-- Artigo: whatsapp-inbox
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Inbox, setores e atendimento humano', 'whatsapp-inbox', 'Assumir conversas, transferir setor e responder clientes.', '<p>O inbox concentra as conversas com clientes e leads.</p><p><strong>Onde encontrar:</strong> WhatsApp → Inbox</p><h2>Passo a passo</h2><ol><li>Abra <strong>WhatsApp</strong>.</li><li>Veja conversas abertas, na fila ou atribuídas.</li><li><strong>Assuma</strong> um atendimento para responder como humano.</li><li>Transfira para outro <strong>setor</strong> quando necessário.</li><li>Cadastre setores e atendentes (Diretor) na área do WhatsApp.</li></ol><h2>Dicas e cuidados</h2><ul><li>Em atendimento humano, o bot não interfere.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Inbox, setores e atendimento humano', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'whatsapp'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'whatsapp-inbox');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'whatsapp'
SET a.id_categoria = c.id,
    a.titulo = 'Inbox, setores e atendimento humano',
    a.resumo = 'Assumir conversas, transferir setor e responder clientes.',
    a.corpo = '<p>O inbox concentra as conversas com clientes e leads.</p><p><strong>Onde encontrar:</strong> WhatsApp → Inbox</p><h2>Passo a passo</h2><ol><li>Abra <strong>WhatsApp</strong>.</li><li>Veja conversas abertas, na fila ou atribuídas.</li><li><strong>Assuma</strong> um atendimento para responder como humano.</li><li>Transfira para outro <strong>setor</strong> quando necessário.</li><li>Cadastre setores e atendentes (Diretor) na área do WhatsApp.</li></ol><h2>Dicas e cuidados</h2><ul><li>Em atendimento humano, o bot não interfere.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Inbox, setores e atendimento humano', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'whatsapp-inbox';

-- Artigo: whatsapp-fluxos-bot
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Fluxos do bot (automações)', 'whatsapp-fluxos-bot', 'Templates, gatilhos, simulador, lead CRM e timeout.', '<p>Fluxos configuráveis respondem automaticamente por palavra-chave, saudação ou primeira mensagem.</p><p><strong>Onde encontrar:</strong> WhatsApp → Fluxos</p><h2>Passo a passo</h2><ol><li>Em WhatsApp, abra <strong>Fluxos do bot</strong>.</li><li>Escolha um <strong>template pronto</strong> ou crie do zero.</li><li>Defina o <strong>gatilho</strong> e a prioridade.</li><li>Monte os passos: texto, pergunta, opções, condição, delay, lead CRM, setor, humano, fim.</li><li>Use o <strong>simulador</strong> antes de ativar.</li><li>Digite <em>sair</em> encerra o bot; <em>menu</em> volta ao menu de setores.</li></ol><h2>Dicas e cuidados</h2><ul><li>Não ative dois fluxos com o mesmo gatilho ao mesmo tempo.</li><li>Se nenhum fluxo casar, vale o menu clássico de setores.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Fluxos do bot (automações)', 30, 1
FROM `platform_help_categorias` c WHERE c.slug = 'whatsapp'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'whatsapp-fluxos-bot');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'whatsapp'
SET a.id_categoria = c.id,
    a.titulo = 'Fluxos do bot (automações)',
    a.resumo = 'Templates, gatilhos, simulador, lead CRM e timeout.',
    a.corpo = '<p>Fluxos configuráveis respondem automaticamente por palavra-chave, saudação ou primeira mensagem.</p><p><strong>Onde encontrar:</strong> WhatsApp → Fluxos</p><h2>Passo a passo</h2><ol><li>Em WhatsApp, abra <strong>Fluxos do bot</strong>.</li><li>Escolha um <strong>template pronto</strong> ou crie do zero.</li><li>Defina o <strong>gatilho</strong> e a prioridade.</li><li>Monte os passos: texto, pergunta, opções, condição, delay, lead CRM, setor, humano, fim.</li><li>Use o <strong>simulador</strong> antes de ativar.</li><li>Digite <em>sair</em> encerra o bot; <em>menu</em> volta ao menu de setores.</li></ol><h2>Dicas e cuidados</h2><ul><li>Não ative dois fluxos com o mesmo gatilho ao mesmo tempo.</li><li>Se nenhum fluxo casar, vale o menu clássico de setores.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Fluxos do bot (automações)', a.video_titulo),
    a.ordem = 30,
    a.publicado = 1
WHERE a.slug = 'whatsapp-fluxos-bot';

-- Artigo: conectar-meta
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Conectar Facebook e Instagram (Meta)', 'conectar-meta', 'OAuth da Página e Instagram Professional.', '<p>A publicação nas redes exige conexão Meta (diretor + permissão Redes sociais).</p><p><strong>Onde encontrar:</strong> Configurações → Conexão Meta</p><h2>Passo a passo</h2><ol><li>Vá em <strong>Configurações → Conexão Meta</strong>.</li><li>Inicie o login OAuth e autorize a Página e o Instagram Professional.</li><li>Confirme que a conexão aparece como ativa.</li><li>Depois use o módulo <strong>Redes sociais</strong> para agendar posts.</li></ol><h2>Dicas e cuidados</h2><ul><li>É necessário Instagram profissional vinculado à Página.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Conectar Facebook e Instagram (Meta)', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'redes-sociais'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'conectar-meta');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'redes-sociais'
SET a.id_categoria = c.id,
    a.titulo = 'Conectar Facebook e Instagram (Meta)',
    a.resumo = 'OAuth da Página e Instagram Professional.',
    a.corpo = '<p>A publicação nas redes exige conexão Meta (diretor + permissão Redes sociais).</p><p><strong>Onde encontrar:</strong> Configurações → Conexão Meta</p><h2>Passo a passo</h2><ol><li>Vá em <strong>Configurações → Conexão Meta</strong>.</li><li>Inicie o login OAuth e autorize a Página e o Instagram Professional.</li><li>Confirme que a conexão aparece como ativa.</li><li>Depois use o módulo <strong>Redes sociais</strong> para agendar posts.</li></ol><h2>Dicas e cuidados</h2><ul><li>É necessário Instagram profissional vinculado à Página.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Conectar Facebook e Instagram (Meta)', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'conectar-meta';

-- Artigo: agendar-posts-social
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Agendar Feed, Story, Reel e Carrossel', 'agendar-posts-social', 'Biblioteca, agenda semanal/mensal e histórico de publicações.', '<p>O módulo Redes sociais agenda conteúdos para Facebook/Instagram.</p><p><strong>Onde encontrar:</strong> Menu → Redes sociais</p><h2>Passo a passo</h2><ol><li>Abra <strong>Redes sociais</strong>.</li><li>Faça upload na biblioteca ou anexe mídia ao criar o post.</li><li>Escolha o formato: Feed, Story, Reel ou Carrossel.</li><li>Defina data/hora na visão semana ou mês.</li><li>Acompanhe o status e o histórico de publicação.</li></ol><h2>Dicas e cuidados</h2><ul><li>Sem conexão Meta ativa, o agendamento não publica.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Agendar Feed, Story, Reel e Carrossel', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'redes-sociais'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'agendar-posts-social');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'redes-sociais'
SET a.id_categoria = c.id,
    a.titulo = 'Agendar Feed, Story, Reel e Carrossel',
    a.resumo = 'Biblioteca, agenda semanal/mensal e histórico de publicações.',
    a.corpo = '<p>O módulo Redes sociais agenda conteúdos para Facebook/Instagram.</p><p><strong>Onde encontrar:</strong> Menu → Redes sociais</p><h2>Passo a passo</h2><ol><li>Abra <strong>Redes sociais</strong>.</li><li>Faça upload na biblioteca ou anexe mídia ao criar o post.</li><li>Escolha o formato: Feed, Story, Reel ou Carrossel.</li><li>Defina data/hora na visão semana ou mês.</li><li>Acompanhe o status e o histórico de publicação.</li></ol><h2>Dicas e cuidados</h2><ul><li>Sem conexão Meta ativa, o agendamento não publica.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Agendar Feed, Story, Reel e Carrossel', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'agendar-posts-social';

-- Artigo: comunicacao-smtp
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Configurar e-mail (SMTP) da escola', 'comunicacao-smtp', 'Remetente, SMTP e teste de envio.', '<p>Sem SMTP válido, campanhas e cobranças por e-mail não saem.</p><p><strong>Onde encontrar:</strong> Configurações → Comunicação</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Comunicação</strong>.</li><li>Preencha host, porta, usuário, senha e e-mail remetente.</li><li>Salve e envie um <strong>e-mail de teste</strong>.</li><li>Use o auditor de e-mails para limpar cadastros inválidos.</li></ol><h2>Dicas e cuidados</h2><ul><li>Prefira e-mail profissional do domínio da escola.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Configurar e-mail (SMTP) da escola', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'campanhas-email'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'comunicacao-smtp');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'campanhas-email'
SET a.id_categoria = c.id,
    a.titulo = 'Configurar e-mail (SMTP) da escola',
    a.resumo = 'Remetente, SMTP e teste de envio.',
    a.corpo = '<p>Sem SMTP válido, campanhas e cobranças por e-mail não saem.</p><p><strong>Onde encontrar:</strong> Configurações → Comunicação</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Comunicação</strong>.</li><li>Preencha host, porta, usuário, senha e e-mail remetente.</li><li>Salve e envie um <strong>e-mail de teste</strong>.</li><li>Use o auditor de e-mails para limpar cadastros inválidos.</li></ol><h2>Dicas e cuidados</h2><ul><li>Prefira e-mail profissional do domínio da escola.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Configurar e-mail (SMTP) da escola', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'comunicacao-smtp';

-- Artigo: campanhas-email
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Campanhas de e-mail', 'campanhas-email', 'Segmentar público, montar mensagem e disparar.', '<p>Campanhas enviam comunicados para alunos, responsáveis ou leads.</p><p><strong>Onde encontrar:</strong> Menu → Campanhas</p><h2>Passo a passo</h2><ol><li>Abra <strong>Campanhas</strong>.</li><li>Crie uma campanha e escolha o segmento.</li><li>Redija o assunto e o corpo.</li><li>Agende ou dispare e acompanhe o status.</li></ol><h2>Dicas e cuidados</h2><ul><li>E-mails fake são bloqueados pelo validador.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Campanhas de e-mail', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'campanhas-email'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'campanhas-email');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'campanhas-email'
SET a.id_categoria = c.id,
    a.titulo = 'Campanhas de e-mail',
    a.resumo = 'Segmentar público, montar mensagem e disparar.',
    a.corpo = '<p>Campanhas enviam comunicados para alunos, responsáveis ou leads.</p><p><strong>Onde encontrar:</strong> Menu → Campanhas</p><h2>Passo a passo</h2><ol><li>Abra <strong>Campanhas</strong>.</li><li>Crie uma campanha e escolha o segmento.</li><li>Redija o assunto e o corpo.</li><li>Agende ou dispare e acompanhe o status.</li></ol><h2>Dicas e cuidados</h2><ul><li>E-mails fake são bloqueados pelo validador.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Campanhas de e-mail', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'campanhas-email';

-- Artigo: cobranca-automatica
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Cobrança automática por e-mail', 'cobranca-automatica', 'Avisos antes/no dia/atraso da mensalidade.', '<p>A cobrança automática lembra mensalidades por e-mail.</p><p><strong>Onde encontrar:</strong> Financeiro + Configurações → Comunicação</p><h2>Passo a passo</h2><ol><li>Confirme que o SMTP está ok em Comunicação.</li><li>Verifique carnês/títulos no Financeiro.</li><li>Os disparos rodam pelo worker de cobrança.</li><li>Monitore resultados e atualize e-mails inválidos.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Cobrança automática por e-mail', 30, 1
FROM `platform_help_categorias` c WHERE c.slug = 'campanhas-email'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'cobranca-automatica');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'campanhas-email'
SET a.id_categoria = c.id,
    a.titulo = 'Cobrança automática por e-mail',
    a.resumo = 'Avisos antes/no dia/atraso da mensalidade.',
    a.corpo = '<p>A cobrança automática lembra mensalidades por e-mail.</p><p><strong>Onde encontrar:</strong> Financeiro + Configurações → Comunicação</p><h2>Passo a passo</h2><ol><li>Confirme que o SMTP está ok em Comunicação.</li><li>Verifique carnês/títulos no Financeiro.</li><li>Os disparos rodam pelo worker de cobrança.</li><li>Monitore resultados e atualize e-mails inválidos.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Cobrança automática por e-mail', a.video_titulo),
    a.ordem = 30,
    a.publicado = 1
WHERE a.slug = 'cobranca-automatica';

-- Artigo: assinatura-saas
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Assinatura do Painel (SaaS)', 'assinatura-saas', 'Faturas da plataforma CTI, PIX e situação da escola.', '<p>A assinatura SaaS é o pagamento do próprio Painel CTI (não confundir com carnê de aluno).</p><p><strong>Onde encontrar:</strong> Financeiro → Assinatura</p><h2>Passo a passo</h2><ol><li>Abra <strong>Financeiro → Assinatura</strong>.</li><li>Veja faturas em aberto e o status da escola.</li><li>Pague via PIX quando disponível.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Assinatura do Painel (SaaS)', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'financeiro'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'assinatura-saas');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'financeiro'
SET a.id_categoria = c.id,
    a.titulo = 'Assinatura do Painel (SaaS)',
    a.resumo = 'Faturas da plataforma CTI, PIX e situação da escola.',
    a.corpo = '<p>A assinatura SaaS é o pagamento do próprio Painel CTI (não confundir com carnê de aluno).</p><p><strong>Onde encontrar:</strong> Financeiro → Assinatura</p><h2>Passo a passo</h2><ol><li>Abra <strong>Financeiro → Assinatura</strong>.</li><li>Veja faturas em aberto e o status da escola.</li><li>Pague via PIX quando disponível.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Assinatura do Painel (SaaS)', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'assinatura-saas';

-- Artigo: carnes-pix
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Carnês e cobrança de alunos', 'carnes-pix', 'Gerar carnês, acompanhar parcelas e PIX (Mercado Pago).', '<p>Carnês controlam as mensalidades dos alunos.</p><p><strong>Onde encontrar:</strong> Financeiro → Carnês</p><h2>Passo a passo</h2><ol><li>Abra <strong>Financeiro → Carnês</strong>.</li><li>Localize o aluno/matrícula e gere ou abra o carnê.</li><li>Acompanhe parcelas pagas, abertas e atrasadas.</li><li>Se usa Mercado Pago, o aluno pode pagar via PIX.</li></ol><h2>Dicas e cuidados</h2><ul><li>Configure credenciais em Configurações → Pagamentos.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Carnês e cobrança de alunos', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'financeiro'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'carnes-pix');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'financeiro'
SET a.id_categoria = c.id,
    a.titulo = 'Carnês e cobrança de alunos',
    a.resumo = 'Gerar carnês, acompanhar parcelas e PIX (Mercado Pago).',
    a.corpo = '<p>Carnês controlam as mensalidades dos alunos.</p><p><strong>Onde encontrar:</strong> Financeiro → Carnês</p><h2>Passo a passo</h2><ol><li>Abra <strong>Financeiro → Carnês</strong>.</li><li>Localize o aluno/matrícula e gere ou abra o carnê.</li><li>Acompanhe parcelas pagas, abertas e atrasadas.</li><li>Se usa Mercado Pago, o aluno pode pagar via PIX.</li></ol><h2>Dicas e cuidados</h2><ul><li>Configure credenciais em Configurações → Pagamentos.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Carnês e cobrança de alunos', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'carnes-pix';

-- Artigo: caixa-entrada-saida
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Caixa: entradas e saídas', 'caixa-entrada-saida', 'Lançar movimentos do caixa da escola.', '<p>Entradas e saídas registram o fluxo de caixa operacional.</p><p><strong>Onde encontrar:</strong> Financeiro → Entrada / Saída</p><h2>Passo a passo</h2><ol><li>Em <strong>Financeiro → Entrada</strong>, lance recebimentos avulsos.</li><li>Em <strong>Saída</strong>, registre despesas.</li><li>Informe valor, data e descrição.</li><li>Use Relatórios para consolidar o período.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Caixa: entradas e saídas', 30, 1
FROM `platform_help_categorias` c WHERE c.slug = 'financeiro'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'caixa-entrada-saida');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'financeiro'
SET a.id_categoria = c.id,
    a.titulo = 'Caixa: entradas e saídas',
    a.resumo = 'Lançar movimentos do caixa da escola.',
    a.corpo = '<p>Entradas e saídas registram o fluxo de caixa operacional.</p><p><strong>Onde encontrar:</strong> Financeiro → Entrada / Saída</p><h2>Passo a passo</h2><ol><li>Em <strong>Financeiro → Entrada</strong>, lance recebimentos avulsos.</li><li>Em <strong>Saída</strong>, registre despesas.</li><li>Informe valor, data e descrição.</li><li>Use Relatórios para consolidar o período.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Caixa: entradas e saídas', a.video_titulo),
    a.ordem = 30,
    a.publicado = 1
WHERE a.slug = 'caixa-entrada-saida';

-- Artigo: relatorios-financeiros
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Relatórios financeiros', 'relatorios-financeiros', 'Visão consolidada de caixa e indicadores.', '<p>Relatórios ajudam a fechar o mês e acompanhar entradas/saídas do caixa da escola.</p><p><strong>Onde encontrar:</strong> Financeiro → Relatórios</p><h2>Passo a passo</h2><ol><li>Abra <strong>Financeiro → Relatórios</strong> (caminho do caixa/relatório).</li><li>Filtre o período desejado.</li><li>Analise entradas, saídas e saldos conforme os totais da tela.</li><li>Exporte ou imprima quando a tela oferecer a opção.</li></ol><h2>Dicas e cuidados</h2><ul><li>Não confundir com Relatórios CRM (comercial) nem com Assinatura SaaS (mensalidade do painel).</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Relatórios financeiros', 40, 1
FROM `platform_help_categorias` c WHERE c.slug = 'financeiro'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'relatorios-financeiros');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'financeiro'
SET a.id_categoria = c.id,
    a.titulo = 'Relatórios financeiros',
    a.resumo = 'Visão consolidada de caixa e indicadores.',
    a.corpo = '<p>Relatórios ajudam a fechar o mês e acompanhar entradas/saídas do caixa da escola.</p><p><strong>Onde encontrar:</strong> Financeiro → Relatórios</p><h2>Passo a passo</h2><ol><li>Abra <strong>Financeiro → Relatórios</strong> (caminho do caixa/relatório).</li><li>Filtre o período desejado.</li><li>Analise entradas, saídas e saldos conforme os totais da tela.</li><li>Exporte ou imprima quando a tela oferecer a opção.</li></ol><h2>Dicas e cuidados</h2><ul><li>Não confundir com Relatórios CRM (comercial) nem com Assinatura SaaS (mensalidade do painel).</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Relatórios financeiros', a.video_titulo),
    a.ordem = 40,
    a.publicado = 1
WHERE a.slug = 'relatorios-financeiros';

-- Artigo: pagamentos-mercadopago
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Pagamentos (Mercado Pago da escola)', 'pagamentos-mercadopago', 'Credenciais PIX/MP para carnês dos alunos.', '<p>As credenciais Mercado Pago da escola habilitam PIX nos carnês.</p><p><strong>Onde encontrar:</strong> Configurações → Pagamentos</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Pagamentos</strong> (Diretor).</li><li>Informe as chaves conforme o guia da tela.</li><li>Salve e teste um pagamento em ambiente controlado.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Pagamentos (Mercado Pago da escola)', 50, 1
FROM `platform_help_categorias` c WHERE c.slug = 'financeiro'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'pagamentos-mercadopago');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'financeiro'
SET a.id_categoria = c.id,
    a.titulo = 'Pagamentos (Mercado Pago da escola)',
    a.resumo = 'Credenciais PIX/MP para carnês dos alunos.',
    a.corpo = '<p>As credenciais Mercado Pago da escola habilitam PIX nos carnês.</p><p><strong>Onde encontrar:</strong> Configurações → Pagamentos</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Pagamentos</strong> (Diretor).</li><li>Informe as chaves conforme o guia da tela.</li><li>Salve e teste um pagamento em ambiente controlado.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Pagamentos (Mercado Pago da escola)', a.video_titulo),
    a.ordem = 50,
    a.publicado = 1
WHERE a.slug = 'pagamentos-mercadopago';

-- Artigo: estoque
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Estoque de produtos', 'estoque', 'Cadastrar produtos, quantidades e movimentações.', '<p>O estoque controla materiais e produtos vendidos na escola.</p><p><strong>Onde encontrar:</strong> Vendas → Estoque</p><h2>Passo a passo</h2><ol><li>Abra <strong>Vendas → Estoque</strong>.</li><li>Cadastre produtos com nome, preço e quantidade.</li><li>Ajuste entradas/saídas conforme a operação.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Estoque de produtos', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'vendas-estoque'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'estoque');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'vendas-estoque'
SET a.id_categoria = c.id,
    a.titulo = 'Estoque de produtos',
    a.resumo = 'Cadastrar produtos, quantidades e movimentações.',
    a.corpo = '<p>O estoque controla materiais e produtos vendidos na escola.</p><p><strong>Onde encontrar:</strong> Vendas → Estoque</p><h2>Passo a passo</h2><ol><li>Abra <strong>Vendas → Estoque</strong>.</li><li>Cadastre produtos com nome, preço e quantidade.</li><li>Ajuste entradas/saídas conforme a operação.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Estoque de produtos', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'estoque';

-- Artigo: pdv
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'PDV (ponto de venda)', 'pdv', 'Vender produtos do estoque no balcão.', '<p>O PDV registra vendas rápidas vinculadas ao estoque.</p><p><strong>Onde encontrar:</strong> Vendas → PDV</p><h2>Passo a passo</h2><ol><li>Abra <strong>Vendas → PDV</strong>.</li><li>Adicione os itens à venda.</li><li>Confirme o pagamento e finalize.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: PDV (ponto de venda)', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'vendas-estoque'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'pdv');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'vendas-estoque'
SET a.id_categoria = c.id,
    a.titulo = 'PDV (ponto de venda)',
    a.resumo = 'Vender produtos do estoque no balcão.',
    a.corpo = '<p>O PDV registra vendas rápidas vinculadas ao estoque.</p><p><strong>Onde encontrar:</strong> Vendas → PDV</p><h2>Passo a passo</h2><ol><li>Abra <strong>Vendas → PDV</strong>.</li><li>Adicione os itens à venda.</li><li>Confirme o pagamento e finalize.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: PDV (ponto de venda)', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'pdv';

-- Artigo: laboratorios-horarios
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Laboratórios e horários', 'laboratorios-horarios', 'Cadastro de salas/labs e grade de horários.', '<p>Laboratórios e horários estruturam a agenda presencial.</p><p><strong>Onde encontrar:</strong> Agenda → Laboratórios / Horários</p><h2>Passo a passo</h2><ol><li>Em <strong>Agenda → Laboratórios</strong>, cadastre salas/labs.</li><li>Em <strong>Horários</strong>, monte a grade.</li><li>Associe aos agendamentos da turma.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Laboratórios e horários', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'agenda'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'laboratorios-horarios');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'agenda'
SET a.id_categoria = c.id,
    a.titulo = 'Laboratórios e horários',
    a.resumo = 'Cadastro de salas/labs e grade de horários.',
    a.corpo = '<p>Laboratórios e horários estruturam a agenda presencial.</p><p><strong>Onde encontrar:</strong> Agenda → Laboratórios / Horários</p><h2>Passo a passo</h2><ol><li>Em <strong>Agenda → Laboratórios</strong>, cadastre salas/labs.</li><li>Em <strong>Horários</strong>, monte a grade.</li><li>Associe aos agendamentos da turma.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Laboratórios e horários', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'laboratorios-horarios';

-- Artigo: agendamentos-diario
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Agendamentos e diário', 'agendamentos-diario', 'Reservas de lab e registro diário de aulas.', '<p>Agendamentos ocupam labs; o diário registra a aula do dia.</p><p><strong>Onde encontrar:</strong> Agenda → Agendamentos / Diário</p><h2>Passo a passo</h2><ol><li>Abra <strong>Agenda → Agendamentos</strong> e crie a reserva.</li><li>Evite conflito de horário no mesmo laboratório.</li><li>No <strong>Diário</strong>, registre a aula/presença conforme o fluxo da escola.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Agendamentos e diário', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'agenda'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'agendamentos-diario');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'agenda'
SET a.id_categoria = c.id,
    a.titulo = 'Agendamentos e diário',
    a.resumo = 'Reservas de lab e registro diário de aulas.',
    a.corpo = '<p>Agendamentos ocupam labs; o diário registra a aula do dia.</p><p><strong>Onde encontrar:</strong> Agenda → Agendamentos / Diário</p><h2>Passo a passo</h2><ol><li>Abra <strong>Agenda → Agendamentos</strong> e crie a reserva.</li><li>Evite conflito de horário no mesmo laboratório.</li><li>No <strong>Diário</strong>, registre a aula/presença conforme o fluxo da escola.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Agendamentos e diário', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'agendamentos-diario';

-- Artigo: dados-escola
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Dados da escola', 'dados-escola', 'Razão social, endereço, logo e contatos.', '<p>Dados cadastrais alimentam contratos, impressos e comunicação.</p><p><strong>Onde encontrar:</strong> Configurações → Dados da escola</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Dados da escola</strong> (Diretor).</li><li>Atualize razão social, CNPJ, endereço e contatos.</li><li>Envie o logo para impressos e documentos.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Dados da escola', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'configuracoes'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'dados-escola');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'configuracoes'
SET a.id_categoria = c.id,
    a.titulo = 'Dados da escola',
    a.resumo = 'Razão social, endereço, logo e contatos.',
    a.corpo = '<p>Dados cadastrais alimentam contratos, impressos e comunicação.</p><p><strong>Onde encontrar:</strong> Configurações → Dados da escola</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Dados da escola</strong> (Diretor).</li><li>Atualize razão social, CNPJ, endereço e contatos.</li><li>Envie o logo para impressos e documentos.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Dados da escola', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'dados-escola';

-- Artigo: modelo-contrato
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Modelo de contrato', 'modelo-contrato', 'Texto-base do contrato da escola e frases do certificado.', '<p>O modelo de contrato é personalizado por escola.</p><p><strong>Onde encontrar:</strong> Configurações → Modelo de contrato</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Modelo de contrato</strong>.</li><li>Edite o texto-base e cláusulas.</li><li>Salve e gere um contrato de teste.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Modelo de contrato', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'configuracoes'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'modelo-contrato');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'configuracoes'
SET a.id_categoria = c.id,
    a.titulo = 'Modelo de contrato',
    a.resumo = 'Texto-base do contrato da escola e frases do certificado.',
    a.corpo = '<p>O modelo de contrato é personalizado por escola.</p><p><strong>Onde encontrar:</strong> Configurações → Modelo de contrato</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Modelo de contrato</strong>.</li><li>Edite o texto-base e cláusulas.</li><li>Salve e gere um contrato de teste.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Modelo de contrato', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'modelo-contrato';

-- Artigo: bunny-stream
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Bunny Stream (vídeos EAD)', 'bunny-stream', 'Biblioteca de vídeo para aulas online.', '<p>O Bunny Stream hospeda os vídeos das aulas EAD.</p><p><strong>Onde encontrar:</strong> Configurações → Bunny Stream</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Bunny Stream</strong> (quando liberado).</li><li>Confirme as credenciais conforme orientação do suporte CTI.</li><li>No editor do curso, anexe os vídeos às aulas.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Bunny Stream (vídeos EAD)', 30, 1
FROM `platform_help_categorias` c WHERE c.slug = 'configuracoes'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'bunny-stream');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'configuracoes'
SET a.id_categoria = c.id,
    a.titulo = 'Bunny Stream (vídeos EAD)',
    a.resumo = 'Biblioteca de vídeo para aulas online.',
    a.corpo = '<p>O Bunny Stream hospeda os vídeos das aulas EAD.</p><p><strong>Onde encontrar:</strong> Configurações → Bunny Stream</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → Bunny Stream</strong> (quando liberado).</li><li>Confirme as credenciais conforme orientação do suporte CTI.</li><li>No editor do curso, anexe os vídeos às aulas.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Bunny Stream (vídeos EAD)', a.video_titulo),
    a.ordem = 30,
    a.publicado = 1
WHERE a.slug = 'bunny-stream';

-- Artigo: ia-pedagogica
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'IA Pedagógica', 'ia-pedagogica', 'Configuração da IA usada no portal EAD (diretor).', '<p>A IA pedagógica apoia o aluno no Ascend, quando o plano inclui o recurso.</p><p><strong>Onde encontrar:</strong> Configurações → IA Pedagógica</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → IA Pedagógica</strong> (Diretor).</li><li>Ative/ajuste conforme as opções da tela.</li><li>Oriente o uso responsável com os alunos.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: IA Pedagógica', 40, 1
FROM `platform_help_categorias` c WHERE c.slug = 'configuracoes'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'ia-pedagogica');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'configuracoes'
SET a.id_categoria = c.id,
    a.titulo = 'IA Pedagógica',
    a.resumo = 'Configuração da IA usada no portal EAD (diretor).',
    a.corpo = '<p>A IA pedagógica apoia o aluno no Ascend, quando o plano inclui o recurso.</p><p><strong>Onde encontrar:</strong> Configurações → IA Pedagógica</p><h2>Passo a passo</h2><ol><li>Abra <strong>Configurações → IA Pedagógica</strong> (Diretor).</li><li>Ative/ajuste conforme as opções da tela.</li><li>Oriente o uso responsável com os alunos.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: IA Pedagógica', a.video_titulo),
    a.ordem = 40,
    a.publicado = 1
WHERE a.slug = 'ia-pedagogica';

-- Artigo: abrir-chamado-suporte
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Abrir chamado de suporte', 'abrir-chamado-suporte', 'Falar com a equipe CTI: categorias, anexos e status.', '<p>O módulo Suporte é o canal oficial com a equipe CTI.</p><p><strong>Onde encontrar:</strong> Menu → Suporte</p><h2>Passo a passo</h2><ol><li>Abra <strong>Suporte</strong> no menu.</li><li>Clique em novo chamado e escolha a categoria.</li><li>Descreva o problema com passos para reproduzir.</li><li>Anexe um print (imagem até 5 MB) se ajudar.</li><li>Acompanhe respostas na thread. Status resolvido/fechado encerra a conversa daquele chamado.</li></ol><h2>Dicas e cuidados</h2><ul><li>Número do chamado no formato CHM-ANO-00000.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Abrir chamado de suporte', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'suporte'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'abrir-chamado-suporte');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'suporte'
SET a.id_categoria = c.id,
    a.titulo = 'Abrir chamado de suporte',
    a.resumo = 'Falar com a equipe CTI: categorias, anexos e status.',
    a.corpo = '<p>O módulo Suporte é o canal oficial com a equipe CTI.</p><p><strong>Onde encontrar:</strong> Menu → Suporte</p><h2>Passo a passo</h2><ol><li>Abra <strong>Suporte</strong> no menu.</li><li>Clique em novo chamado e escolha a categoria.</li><li>Descreva o problema com passos para reproduzir.</li><li>Anexe um print (imagem até 5 MB) se ajudar.</li><li>Acompanhe respostas na thread. Status resolvido/fechado encerra a conversa daquele chamado.</li></ol><h2>Dicas e cuidados</h2><ul><li>Número do chamado no formato CHM-ANO-00000.</li></ul><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Abrir chamado de suporte', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'abrir-chamado-suporte';

-- Artigo: central-ajuda
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Como usar esta Central de Ajuda', 'central-ajuda', 'Navegar tutoriais e vídeos (quando disponíveis).', '<p>A Central de Ajuda reúne tutoriais por tema. Também existe versão pública em /ajuda.</p><p><strong>Onde encontrar:</strong> Menu → Ajuda</p><h2>Passo a passo</h2><ol><li>Abra <strong>Ajuda</strong> no menu ou /ajuda sem login.</li><li>Escolha a categoria e o artigo.</li><li>Quando houver vídeo, ele aparece no topo do artigo.</li><li>Se ainda faltar informação, abra um chamado em Suporte.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Como usar esta Central de Ajuda', 20, 1
FROM `platform_help_categorias` c WHERE c.slug = 'suporte'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'central-ajuda');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'suporte'
SET a.id_categoria = c.id,
    a.titulo = 'Como usar esta Central de Ajuda',
    a.resumo = 'Navegar tutoriais e vídeos (quando disponíveis).',
    a.corpo = '<p>A Central de Ajuda reúne tutoriais por tema. Também existe versão pública em /ajuda.</p><p><strong>Onde encontrar:</strong> Menu → Ajuda</p><h2>Passo a passo</h2><ol><li>Abra <strong>Ajuda</strong> no menu ou /ajuda sem login.</li><li>Escolha a categoria e o artigo.</li><li>Quando houver vídeo, ele aparece no topo do artigo.</li><li>Se ainda faltar informação, abra um chamado em Suporte.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Como usar esta Central de Ajuda', a.video_titulo),
    a.ordem = 20,
    a.publicado = 1
WHERE a.slug = 'central-ajuda';

-- Artigo: portal-ascend
INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `video_url`, `video_titulo`, `ordem`, `publicado`)
SELECT c.id, 'Portal Ascend (aluno)', 'portal-ascend', 'Login do aluno, cursos, finanças e ranking.', '<p>O Ascend é o portal do aluno para EAD e informações da escola.</p><p><strong>Onde encontrar:</strong> Portal Ascend + Painel → Cursos Online</p><h2>Passo a passo</h2><ol><li>O aluno acessa com as credenciais cadastradas pela escola.</li><li>Vê apenas matrículas EAD ativas em cursos publicados.</li><li>Pode continuar de onde parou, fazer atividades e acompanhar conquistas/ranking.</li><li>A área financeira mostra títulos quando houver carnês.</li><li>Problemas de login: a escola redefine no painel; falha da plataforma → Suporte.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>', NULL, 'Tutorial: Portal Ascend (aluno)', 10, 1
FROM `platform_help_categorias` c WHERE c.slug = 'portal-aluno'
AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'portal-ascend');
UPDATE `platform_help_artigos` a
INNER JOIN `platform_help_categorias` c ON c.slug = 'portal-aluno'
SET a.id_categoria = c.id,
    a.titulo = 'Portal Ascend (aluno)',
    a.resumo = 'Login do aluno, cursos, finanças e ranking.',
    a.corpo = '<p>O Ascend é o portal do aluno para EAD e informações da escola.</p><p><strong>Onde encontrar:</strong> Portal Ascend + Painel → Cursos Online</p><h2>Passo a passo</h2><ol><li>O aluno acessa com as credenciais cadastradas pela escola.</li><li>Vê apenas matrículas EAD ativas em cursos publicados.</li><li>Pode continuar de onde parou, fazer atividades e acompanhar conquistas/ranking.</li><li>A área financeira mostra títulos quando houver carnês.</li><li>Problemas de login: a escola redefine no painel; falha da plataforma → Suporte.</li></ol><p class="text-muted"><em>O vídeo deste tutorial será publicado em breve. Enquanto isso, use este guia escrito.</em></p>',
    a.video_titulo = IF(a.video_url IS NULL OR TRIM(a.video_url) = '', 'Tutorial: Portal Ascend (aluno)', a.video_titulo),
    a.ordem = 10,
    a.publicado = 1
WHERE a.slug = 'portal-ascend';

-- Fim dos tutoriais.
