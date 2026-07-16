# ARCHITECTURE.md — Contexto completo do Painel CTI

> **Público-alvo:** desenvolvedores humanos e **agentes de IA** (Cursor, VS Code Copilot/Continue, etc.).  
> Leia este arquivo **antes** de alterar o código. Preferir seguir os padrões já existentes a inventar novos.

**Última atualização:** 2026-07-15  
**Repo:** `painel-cti`  
**Linguagem:** PHP (MVC próprio) · Ambiente: XAMPP local + Linux produção  
**Estilo:** segurança e performance · Migração de upload manual → Git em andamento

---

## 1. Visão geral do produto

Painel administrativo multi-tenant para **escolas** (assinantes). Cada escola é isolada por `id_admin`.  
Há um **Painel Mestre** planejado (ainda não implementado) para cadastrar escolas, planos e liberar módulos.

```
[Futuro] Painel Mestre
        ↓ libera módulos / planos
escolas_assinantes (tenant = id)
        ↓
usuarios (acesso JSON ∩ modulos_liberados da escola)
        ↓
dados pedagógicos / financeiros / CRM / agenda / comunicação
```

---

## 2. Stack e bootstrap

| Peça | Onde |
|------|------|
| Entrada | `index.php` → `includes/app.php` |
| Autoload | Composer PSR-4: `App\` → `app/` |
| Env | `App\Common\Environment` lê `.env` |
| DB | `App\Model\Db\Database` (PDO MySQL, `DB_*` do `.env`) |
| Views | `App\Utils\View` + templates em `resources/view/` |
| Rotas | `routes/admin.php` inclui arquivos em `routes/admin/*` |
| Sessão | `App\Session\User\Login` — chave `$_SESSION['usuario-mvc-1']` |
| Front | Bootstrap 5, jQuery, SweetAlert2, Font Awesome |

**Constante `URL`:** detectada em `includes/app.php` (prioriza host da requisição em local se diferir do `.env`).

**AJAX obrigatório:** sempre prefixar com `url_base` (`resources/js/url-base.js`). Sem isso, em subpastas XAMPP as rotas quebram.

---

## 3. Multi-tenant e permissões

### Tenant
- Tabela de escolas: **`escolas_assinantes`** (antes: `empresas`)
- Entity: `App\Model\Entity\EscolasAssinantes`
- Sessão: `['escola']` (não mais `['empresa']`)
- Helper: `TenantHelper::getIdAdmin()`, `pertenceEscola()`, etc.

### Módulos (Fase 0 — feita)
- Catálogo com **slugs** + labels: `App\Common\SystemModules`
- Interseção escola ∩ usuário: `App\Common\Helpers\ModuleGateHelper`
- Coluna `escolas_assinantes.modulos_liberados` (JSON de slugs). `NULL` = todos liberados
- Menu / `getPanel()` / checkboxes de funcionários / sync de sessão usam módulos efetivos
- Alias legado: label `Laboratório` → `Agendamentos`

### Níveis de usuário
- Exemplos: `Diretor`, `Financeiro`, `Cliente` (aluno), etc.
- Telas **Comunicação** e **Campanhas**: acesso automático para `Diretor` em `Page::getPanel`

### Preferências do produto (não quebrar)
- Diretor da escola **não** deve conseguir marcar permissões de módulos que a escola não contratou
- Painel mestre futuro usará os **slugs** de `SystemModules`, não labels livres

---

## 4. Padrão MVC / AJAX do projeto

### Fluxo típico de tela
1. `GET /painel/...` → Controller `index()` → View HTML + JS
2. JS faz `$.post(url_base + 'painel/...', { acao: '...' })`
3. Controller `getInfo()` / método dedicado retorna **JSON string** (`json_encode`)
4. `Response` padrão costuma ser `text/html` com body JSON (o jQuery parseia com `'json'`)

### Convenções
- Controllers em `app/Controller/Admin/`
- Entities em `app/Model/Entity/`
- Se controller e entity tiverem o **mesmo nome** (ex.: `Campanhas`), no controller usar:
  ```php
  use App\Model\Entity\Campanhas as EntityCampanhas;
  ```
- Views: `resources/view/admin/modules/<modulo>/`
- JS: `resources/js/<modulo>.js` com cache-bust `?v=YYYYMMDD`
- Rotas: um arquivo por domínio em `routes/admin/` + `include` em `routes/admin.php`
- SQL: usuário prefere **colar no phpMyAdmin**; evitar criar arquivos `.sql` no repo salvo pedido explícito
- **Não commitar** a menos que o usuário peça

### Segurança esperada
- Validar `id_admin` / `TenantHelper` em listagens e updates
- Senhas SMTP: `CryptoHelper` (AES-256-CBC, chave `APP_KEY` ou fallback `SYSTEM_TOKEN`)
- E-mails: `EmailValidator` bloqueia placeholders (`sem@email.com`, etc.)
- Nunca hardcodar tokens de Meta/WhatsApp (legado `Mensagens.php` está quebrado/inseguro)

---

## 5. Módulos principais (estado atual)

### 5.1 Pedagógico / usuários
- Alunos (`Clientes`), Responsáveis, Funcionários (`User`), Trilhas, Matrículas, Certificados
- Aluno = `usuarios.nivel = 'Cliente'`

### 5.2 Financeiro
- `caixa` — títulos de entrada/saída; carnê gera parcelas com `status` `Em aberto` / pago (`0`/`1` misturado no legado — tratar ambos)
- Carnês ligados a `matriculas` via `caixa.id_ref`
- Carrinho de pagamento + recibos
- Gateway Banco Inter (Pix) em `app/Common/Gateways/BancoInter/`

### 5.3 CRM
- `crm_leads` Kanban, funis, histórico, importação planilha
- Tarefas estilo Trello (`crm_tarefas_*`)
- WhatsApp no CRM hoje: link `wa.me` / TODOs Evolution — **não** Evolution completa

### 5.4 Agenda Laboratório v2 (feita)
Arquitetura:
```
laboratorios → horarios (laboratorio_id) → agenda_plano → agenda_aulas → presencas
```
- Controllers: `AgendaLaboratorios`, `AgendaHorarios`, `AgendaLaboratorio`, `AgendaDiario`
- Helper: `AgendaHelper`
- Menu: Laboratórios / Horários / Agendamentos / Diário
- Migração legado `agenda_aula` → `agenda_plano` no primeiro acesso

### 5.5 Comunicação / e-mail (Fases 1–2 + cobrança + validador — feitas)

#### SMTP
- `Email::sistema()` → `.env` (recovery de senha)
- `Email::escola($idAdmin)` → `escola_integracoes` se SMTP ativo; senão fallback sistema
- Tela: `/painel/config/comunicacao` (`ConfigComunicacao`)
- Entity: `EscolaIntegracoes` — senha criptografada

#### Campanhas
- Tabelas: `campanhas`, `campanha_fila`
- UI: `/painel/campanhas`
- Worker: `worker/campanhas.php` + botão “Processar fila”
- Segmentos (`CampanhaSegmentoHelper`): matriculados, ex-alunos, aniversariantes do mês, leads, inadimplentes
- Variáveis: `{nome}`, `{email}`, `{curso}`, `{escola}`

#### Cobrança automática de mensalidades
- Config na mesma tela Comunicação (dias antes / no dia / depois)
- Service: `CobrancaEmailService`
- Log anti-duplicidade: `email_cobranca_log` (UNIQUE caixa+tipo+dias)
- Worker: `worker/cobranca.php` (cron diário)
- **Simular hoje** usa dados do formulário e **não envia**; **Enviar agora** envia de verdade
- Destinatário: e-mail do aluno; se inválido/ausente, responsável (se habilitado)

#### Validador / auditoria
- `EmailValidator` — rejeita fakes (`sememail@email`, `sem@email.com`, domínios placeholder…)
- Aplicado em campanhas, cobrança, teste SMTP
- Botão **Auditar e-mails** → `EmailAuditoriaHelper` (alunos, responsáveis, leads)

### 5.6 WhatsApp / Evolution API (Fase 3)
- Credenciais: `EVOLUTION_URL`, `EVOLUTION_API_KEY`, `EVOLUTION_WEBHOOK_SECRET` no `.env`
- 1 número por escola hoje (`escola_{id_admin}`); tabela `whatsapp_numeros` preparada para multi
- Módulo/plano: slug `whatsapp` → label `WhatsApp` (Diretor liberado automático)
- Inbox: `/painel/whatsapp` — conversas, assumir, transferir setor, responder
- Setores + atendentes (Diretor na aba do inbox)
- Chatbot: menu numérico de setores → fila → humano (`WhatsappChatbotService`)
- Webhook grava msgs e dispara chatbot
- Ops: `docs/OPERACAO_WHATSAPP.md`

### 5.7 Validação de e-mail nos cadastros
- `EmailValidator` aplicado em: Alunos, Responsáveis, Funcionários, Perfil, Leads (form + planilha), Register
- Funcionários/perfil/register: e-mail **obrigatório** e válido
- Alunos/responsáveis/leads: e-mail **opcional**; se preenchido, não pode ser fake (`sem@email.com`, etc.)

---

## 6. Arquivos-chave (mapa rápido)

| Tema | Caminhos |
|------|----------|
| Bootstrap | `includes/app.php`, `index.php` |
| Menu / painel | `SystemModules.php`, `Page.php`, `ModuleGateHelper.php` |
| Login / sessão | `Session/User/Login.php`, middlewares `RequireAdminLogin` |
| Tenant | `TenantHelper.php`, `EscolasAssinantes.php` |
| E-mail | `Common/Communication/Email.php`, `EscolaIntegracoes.php`, `CryptoHelper.php` |
| Campanhas | `Controller/Admin/Campanhas.php`, `CampanhaWorker.php`, `CampanhaSegmentoHelper.php` |
| Cobrança | `CobrancaEmailService.php`, `EmailCobrancaLog.php`, `worker/cobranca.php` |
| WhatsApp | `EvolutionApiService.php`, `WhatsappEscolaService.php`, `Controller/Webhook/Evolution.php` |
| Validador | `EmailValidator.php`, `EmailAuditoriaHelper.php` |
| Agenda | `AgendaHelper.php`, controllers `Agenda*` |
| CRM | `CrmLeads.php`, `resources/js/crm.js` |
| URL AJAX | `resources/js/url-base.js` |

---

## 7. SQL — migrações relevantes (colar no phpMyAdmin)

### Já usadas no fluxo recente (confirmar se existem no banco)

```sql
-- Tenant / módulos
-- (se ainda for empresas:) RENAME TABLE empresas TO escolas_assinantes;
ALTER TABLE escolas_assinantes
  ADD COLUMN IF NOT EXISTS modulos_liberados JSON NULL;

-- SMTP por escola
CREATE TABLE IF NOT EXISTS escola_integracoes (
  id_admin INT UNSIGNED NOT NULL,
  smtp_host VARCHAR(255) DEFAULT NULL,
  smtp_port SMALLINT UNSIGNED NOT NULL DEFAULT 587,
  smtp_user VARCHAR(255) DEFAULT NULL,
  smtp_pass VARCHAR(512) DEFAULT NULL,
  smtp_from_email VARCHAR(255) DEFAULT NULL,
  smtp_from_name VARCHAR(255) DEFAULT NULL,
  smtp_encryption ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
  smtp_ativo TINYINT(1) NOT NULL DEFAULT 0,
  email_delay_segundos INT UNSIGNED NOT NULL DEFAULT 3,
  email_max_hora INT UNSIGNED NOT NULL DEFAULT 80,
  cobranca_ativo TINYINT(1) NOT NULL DEFAULT 0,
  cobranca_dias_antes VARCHAR(50) NOT NULL DEFAULT '3,5',
  cobranca_aviso_vencimento TINYINT(1) NOT NULL DEFAULT 1,
  cobranca_dias_depois VARCHAR(50) NOT NULL DEFAULT '1,3,7',
  cobranca_enviar_responsavel TINYINT(1) NOT NULL DEFAULT 1,
  cobranca_assunto_antes VARCHAR(255) DEFAULT NULL,
  cobranca_assunto_vencimento VARCHAR(255) DEFAULT NULL,
  cobranca_assunto_atraso VARCHAR(255) DEFAULT NULL,
  cobranca_msg_antes TEXT DEFAULT NULL,
  cobranca_msg_vencimento TEXT DEFAULT NULL,
  cobranca_msg_atraso TEXT DEFAULT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Se escola_integracoes já existia SEM colunas de cobrança:
-- ALTER TABLE escola_integracoes ADD COLUMN cobranca_ativo ... (ver histórico do chat)

CREATE TABLE IF NOT EXISTS campanhas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  canal ENUM('email','whatsapp') NOT NULL DEFAULT 'email',
  tipo VARCHAR(50) NOT NULL DEFAULT 'manual',
  titulo VARCHAR(200) NOT NULL,
  assunto VARCHAR(255) DEFAULT NULL,
  mensagem TEXT NOT NULL,
  segmento JSON DEFAULT NULL,
  status ENUM('rascunho','agendada','enviando','concluida','pausada','cancelada') NOT NULL DEFAULT 'rascunho',
  total INT UNSIGNED NOT NULL DEFAULT 0,
  enviados INT UNSIGNED NOT NULL DEFAULT 0,
  erros INT UNSIGNED NOT NULL DEFAULT 0,
  agendada_para DATETIME DEFAULT NULL,
  criada_por INT UNSIGNED NOT NULL,
  criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_campanhas_admin (id_admin),
  KEY idx_campanhas_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campanha_fila (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  campanha_id INT UNSIGNED NOT NULL,
  id_admin INT UNSIGNED NOT NULL,
  destinatario_tipo VARCHAR(30) NOT NULL,
  destinatario_id INT UNSIGNED DEFAULT NULL,
  nome VARCHAR(150) DEFAULT NULL,
  contato VARCHAR(255) NOT NULL,
  status ENUM('pendente','enviado','erro','cancelado') NOT NULL DEFAULT 'pendente',
  tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
  erro_msg VARCHAR(500) DEFAULT NULL,
  enviado_em DATETIME DEFAULT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_fila_campanha (campanha_id),
  KEY idx_fila_pendente (id_admin, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_cobranca_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  caixa_id INT UNSIGNED NOT NULL,
  tipo ENUM('antes','vencimento','atraso') NOT NULL,
  dias INT NOT NULL DEFAULT 0,
  email_destino VARCHAR(255) NOT NULL,
  enviado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_cobranca_envio (caixa_id, tipo, dias),
  KEY idx_admin_data (id_admin, enviado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### WhatsApp / Evolution (colar se ainda não existir)

```sql
-- Colunas Evolution em escola_integracoes (ignore erro se a coluna já existir)
ALTER TABLE escola_integracoes ADD COLUMN evolution_instance VARCHAR(100) NULL;
ALTER TABLE escola_integracoes ADD COLUMN evolution_status VARCHAR(40) NOT NULL DEFAULT 'disconnected';
ALTER TABLE escola_integracoes ADD COLUMN evolution_ativo TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE escola_integracoes ADD COLUMN evolution_numero VARCHAR(30) NULL;
ALTER TABLE escola_integracoes ADD COLUMN whatsapp_delay_segundos INT UNSIGNED NOT NULL DEFAULT 5;
ALTER TABLE escola_integracoes ADD COLUMN whatsapp_max_hora INT UNSIGNED NOT NULL DEFAULT 40;

CREATE TABLE IF NOT EXISTS whatsapp_conversas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  telefone VARCHAR(30) NOT NULL,
  nome_contato VARCHAR(150) DEFAULT NULL,
  status ENUM('aberta','em_atendimento','fechada') NOT NULL DEFAULT 'aberta',
  id_atendente INT UNSIGNED DEFAULT NULL,
  ultima_mensagem_em DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_wa_admin_tel (id_admin, telefone),
  KEY idx_wa_admin_status (id_admin, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_mensagens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  conversa_id INT UNSIGNED NOT NULL,
  direction ENUM('in','out') NOT NULL,
  tipo VARCHAR(30) NOT NULL DEFAULT 'text',
  corpo TEXT DEFAULT NULL,
  media_url TEXT DEFAULT NULL,
  wa_message_id VARCHAR(120) DEFAULT NULL,
  status VARCHAR(30) DEFAULT NULL,
  id_usuario INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_wa_msg_conversa (conversa_id),
  KEY idx_wa_msg_admin (id_admin),
  KEY idx_wa_msg_waid (wa_message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### WhatsApp inbox + setores + chatbot (Fase 3b)

```sql
-- Números (multi-ready; hoje 1 default por escola)
CREATE TABLE IF NOT EXISTS whatsapp_numeros (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  evolution_instance VARCHAR(100) NOT NULL,
  numero VARCHAR(30) DEFAULT NULL,
  apelido VARCHAR(80) DEFAULT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'disconnected',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_wa_instance (evolution_instance),
  KEY idx_wa_num_admin (id_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_setores (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  nome VARCHAR(80) NOT NULL,
  slug VARCHAR(40) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  ordem INT NOT NULL DEFAULT 0,
  mensagem_fila TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_wa_setor (id_admin, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_atendentes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_admin INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  setor_id INT UNSIGNED NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uk_wa_user_setor (usuario_id, setor_id),
  KEY idx_wa_at_setor (setor_id),
  KEY idx_wa_at_admin (id_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extensões nas conversas (ignore se já existir)
ALTER TABLE whatsapp_conversas ADD COLUMN numero_id INT UNSIGNED NULL;
ALTER TABLE whatsapp_conversas ADD COLUMN setor_id INT UNSIGNED NULL;
ALTER TABLE whatsapp_conversas ADD COLUMN chatbot_estado VARCHAR(40) NOT NULL DEFAULT 'novo';
ALTER TABLE whatsapp_conversas ADD COLUMN assigned_at DATETIME NULL;
```

> Agenda v2, CRM, etc. têm SQLs próprios já aplicados em ambientes de desenvolvimento — conferir banco antes de recriar.

---

## 8. Roadmap (planejado × feito)

### Feito
| Item | Status |
|------|--------|
| CRM Kanban + tarefas + histórico | Feito |
| Agenda laboratório v2 + diário | Feito |
| Sync de permissões em tempo real (sessão) | Feito |
| Fase 0 módulos por escola (`modulos_liberados`) | Feito |
| Remoção Parcerias / rename `empresas` → `escolas_assinantes` (código) | Feito (SQL rename pode estar pendente em alguns ambientes) |
| Fase 1 e-mail: SMTP escola + sistema | Feito |
| Fase 2 campanhas e-mail + worker | Feito |
| Cobrança automática mensalidade (antes/dia/atraso) | Feito |
| Validador + auditoria de e-mails | Feito |
| Remoção legado WhatsApp Meta + Gemini | Feito |
| Validação de e-mail nos cadastros | Feito |
| Operação e-mail (composer limpo, status-email, checklist) | Feito |
| Automação aniversariantes por e-mail | Feito |
| Evolution API: .env + QR/status/teste + webhook + tabelas | Feito (base Fase 3) |
| Inbox + setores + chatbot menu | Feito (Fase 3b) |

### Próximo (ordem recomendada)
| Fase | Escopo | Notas |
|------|--------|-------|
| **Fase 3c** | Multi-números na UI + distribuição avançada | Schema `whatsapp_numeros` pronto |
| **Fase 4** | WhatsApp **em massa** na mesma fila `campanhas` (`canal=whatsapp`) | Após inbox estável |
| **Fase 5** | Automações CRM (mensagem ao mudar status do lead) | |
| **Painel Mestre** | Cadastrar escolas, planos de assinatura, liberar slugs em `modulos_liberados` | Base Fase 0 pronta |
| **Dashboard** | Remover top vendedores fictícios; cards CRM/inadimplência reais | |
| **LGPD** | Link de descadastro em e-mail marketing | |

### Decisões de produto já alinhadas
- Cada escola configura **SMTP próprio** (Gmail/corporativo); sistema tem fallback `no-reply@...` no `.env`
- Envio em massa **nunca** síncrono na request web sem fila/limites
- Evolution: **instância por escola** (`escola_{id_admin}`); API key global no `.env`
- Worker: cron Linux em produção; botões manuais no painel para testes XAMPP

---

## 9. Como a próxima IA deve trabalhar

1. **Ler** este arquivo + `.cursorrules` + `README.md`
2. **Não** reinventar router/views/AJAX — copiar padrão de CRM/Campanhas/Comunicação
3. **Sempre** filtrar por `id_admin` / `TenantHelper`
4. Novas permissões: adicionar slug em `SystemModules` + item de menu se necessário
5. SQL: entregar script para o usuário colar no phpMyAdmin
6. Front: usar `url_base`; bump `?v=` no script ao mudar JS
7. Evitar conflito de nomes Controller/Entity (usar alias)
8. Não commitar / não push sem pedido explícito
9. Código legado WhatsApp: **não** “consertar Meta token”; migrar para Evolution quando for a vez
10. Comunicação com o usuário: português, direto, SQL quando necessário

### Armadilhas já encontradas (não repetir)
- `lastInsertId()` = 0 quando PK não é auto-increment (`escola_integracoes.id_admin`) → não usar `(bool)$db->insert()`
- AJAX sem `url_base` falha em subpasta XAMPP
- `use Entity\Campanhas` dentro de `Controller\Campanhas` causa “Cannot declare class… already in use”
- Status de caixa legado: misturar `"Em aberto"` e `0` / `1`
- Simular cobrança deve funcionar **sem** exigir `cobranca_ativo` salvo; não enviar e-mail na simulação

---

## 10. Workers e operação

```bash
# Fila de campanhas (produção: cron * * * * *)
php worker/campanhas.php [id_admin] [limite]

# Cobrança diária (produção: 0 8 * * *)
php worker/cobranca.php [id_admin]
```

Painel (Diretor):
- `/painel/config/comunicacao` — SMTP, cobrança, aniversário, WhatsApp (Evolution)
- `/painel/campanhas` — campanhas manuais + processar fila

Docs: `docs/OPERACAO_EMAIL.md`, `docs/OPERACAO_WHATSAPP.md`

---

## 11. Testes manuais sugeridos (e-mail)

1. SMTP escola: salvar Gmail (app password) + e-mail de **teste para você**
2. Campanha: preview público → salvar rascunho → iniciar → processar fila
3. Cobrança: **Simular hoje** (não envia) → só depois **Enviar agora**
4. Auditar e-mails → corrigir cadastros com fakes
5. Confirmar recovery de senha ainda usa `Email::sistema()`

---

## 12. Contato do contexto histórico

Trabalho recente (jul/2026) cobriu: Agenda v2, ModuleGate, rename escolas, comunicação e-mail completa (SMTP, campanhas, cobrança, validador).  
Transcript longo da sessão Cursor pode existir no ambiente do desenvolvedor; este arquivo deve ser a **fonte estável** daqui pra frente.

**Fim do documento.** Atualize este `ARCHITECTURE.md` sempre que concluir uma fase do roadmap.
