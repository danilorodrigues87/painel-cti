-- Central de documentação / ajuda da plataforma (editável no Master)
-- Cole no phpMyAdmin

CREATE TABLE IF NOT EXISTS `platform_help_categorias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `ordem` INT NOT NULL DEFAULT 0,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_help_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `platform_help_artigos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_categoria` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `resumo` VARCHAR(500) NULL DEFAULT NULL,
  `corpo` MEDIUMTEXT NULL,
  `video_url` VARCHAR(1000) NULL DEFAULT NULL,
  `video_titulo` VARCHAR(200) NULL DEFAULT NULL,
  `ordem` INT NOT NULL DEFAULT 0,
  `publicado` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_help_art_slug` (`slug`),
  KEY `idx_help_art_cat` (`id_categoria`, `publicado`, `ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Primeiros passos', 'primeiros-passos', 10, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'primeiros-passos');

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Redes sociais', 'redes-sociais', 20, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'redes-sociais');

INSERT INTO `platform_help_categorias` (`titulo`, `slug`, `ordem`, `ativo`)
SELECT 'Portal do aluno', 'portal-aluno', 30, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `platform_help_categorias` WHERE `slug` = 'portal-aluno');

INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `ordem`, `publicado`)
SELECT c.id,
  'Bem-vindo ao Painel CTI',
  'bem-vindo',
  'Visão geral do painel e permissões.',
  '<p>Use o menu lateral conforme as permissões liberadas pelo diretor. Aceite os Termos de Uso (LGPD) no primeiro acesso. Em caso de dúvida, consulte os artigos desta central ou fale com o suporte CTI.</p>',
  10, 1
FROM `platform_help_categorias` c
WHERE c.slug = 'primeiros-passos'
  AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'bem-vindo');

INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `ordem`, `publicado`)
SELECT c.id,
  'Conectar Facebook e Instagram',
  'conectar-meta',
  'OAuth Meta e publicação.',
  '<p>Em <strong>Configurações → Conexão Meta</strong> (diretor), conecte a Página e o Instagram Professional. Depois use <strong>Redes sociais</strong> para agendar Feed, Story, Reel ou Carrossel.</p>',
  10, 1
FROM `platform_help_categorias` c
WHERE c.slug = 'redes-sociais'
  AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'conectar-meta');

INSERT INTO `platform_help_artigos` (`id_categoria`, `titulo`, `slug`, `resumo`, `corpo`, `ordem`, `publicado`)
SELECT c.id,
  'Portal Ascend (aluno)',
  'portal-ascend',
  'Acesso dos alunos aos cursos online.',
  '<p>O portal do aluno (Ascend) usa a API <code>/api/v1/student</code>. Branding e logo são gerenciados no Master. Alunos fazem login com as credenciais cadastradas na escola.</p>',
  10, 1
FROM `platform_help_categorias` c
WHERE c.slug = 'portal-aluno'
  AND NOT EXISTS (SELECT 1 FROM `platform_help_artigos` WHERE `slug` = 'portal-ascend');
