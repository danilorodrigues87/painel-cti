# Painel CTI

Sistema de gestão escolar multi-tenant (PHP MVC) para escolas de informática / educação tecnológica.

**Ambiente:** XAMPP (local) e Linux (produção)  
**Stack:** PHP 8.x · MySQL · Bootstrap 5 · jQuery · PHPMailer · Composer

---

## O que o sistema faz

| Área | Funcionalidades |
|------|-----------------|
| **Pedagógico** | Alunos, responsáveis, trilhas/cursos, matrículas, certificados |
| **Financeiro** | Entrada/saída de caixa, carnês, carrinho de pagamento, relatórios, Pix Banco Inter |
| **CRM** | Leads (Kanban), funis, tarefas (Trello-like), histórico |
| **Agenda** | Laboratórios, horários, agendamentos, diário de presença |
| **Comunicação** | SMTP por escola, campanhas de e-mail, cobrança automática de mensalidades |
| **Acesso** | Multi-escola (`id_admin`), permissões por usuário ∩ módulos liberados da escola |

---

## Requisitos

- PHP 8+ com extensões: `pdo_mysql`, `openssl`, `mbstring`, `json`
- MySQL / MariaDB
- Composer
- Apache (XAMPP) com `mod_rewrite` (ou equivalente)

---

## Instalação rápida (local)

1. Clone / copie o projeto para `htdocs` (ex.: `C:\xampp\htdocs\pjt\painel-cti`)
2. Instale dependências:
   ```bash
   composer install
   ```
3. Crie o arquivo `.env` na raiz (baseado nas variáveis abaixo)
4. Configure o virtual host ou acesse via subpasta (ex.: `http://localhost/pjt/painel-cti/painel`)
5. Rode no phpMyAdmin os SQLs pendentes descritos em [`ARCHITECTURE.md`](ARCHITECTURE.md) (seção SQL / migrações)

### Variáveis `.env` essenciais

```env
URL=http://localhost/pjt/painel-cti
SITE=Painel CTI
TIMEZONE=America/Sao_Paulo
SYSTEM_TOKEN=token-secreto-do-sistema
APP_KEY=chave-longa-para-criptografia-smtp

DB_HOST=localhost
DB_NAME=seu_banco
DB_USER=root
DB_PASS=

# E-mail padrão do SISTEMA (recuperação de senha / fallback)
SMTP_HOST=smtp.exemplo.com
SMTP_PORT=587
SMTP_USER=no-reply@ctieducacional.com.br
SMTP_PASS=
SMTP_CHARSET=UTF-8
SMTP_FROM_EMAIL=no-reply@ctieducacional.com.br
SMTP_FROM_NAME=CTI Educacional
SMTP_ENCRYPTION=tls
```

> Cada escola também pode configurar SMTP próprio em **Configurações → Comunicação**.

---

## Estrutura resumida

```
painel-cti/
├── app/
│   ├── Common/          # Helpers, Email, workers helpers, SystemModules
│   ├── Controller/      # Admin, Autentication, Api
│   ├── Model/Entity/    # Entities + Database
│   ├── Http/            # Router, Request, Response, Middlewares
│   └── Session/         # Login / sessão
├── includes/app.php     # Bootstrap (env, URL, middlewares)
├── resources/           # Views HTML, JS, CSS, assets
├── routes/              # Rotas admin + API
├── worker/              # CLI: campanhas.php, cobranca.php
├── index.php
├── README.md
└── ARCHITECTURE.md      # Documento completo para humanos e IAs
```

---

## Workers (cron)

```bash
# Diagnóstico (não envia e-mail)
php worker/status-email.php
php worker/status-email.php 1

# Campanhas de e-mail em fila (a cada 1 min em produção)
php worker/campanhas.php

# Cobrança automática de mensalidades (1x/dia, ex.: 08:00)
php worker/cobranca.php
```

Checklist completo de produção (cron Windows/Linux, testes seguros, critérios de pronto): **[`docs/OPERACAO_EMAIL.md`](docs/OPERACAO_EMAIL.md)**

No XAMPP, também há botões **Processar fila agora** / **Enviar agora** / **Simular hoje** nas telas do painel.

---

## Convenções importantes

- Tenant = `id_admin` (escola em `escolas_assinantes`)
- SQL novo: preferir colar no **phpMyAdmin** (não criar `.sql` no repo sem necessidade)
- Commits Git: só quando o usuário pedir
- AJAX sempre com `url_base + rota` (`resources/js/url-base.js`)
- Controllers e Entities com o mesmo nome: usar alias `EntityX` no controller

Documentação detalhada de arquitetura, módulos, SQLs e roadmap: **[`ARCHITECTURE.md`](ARCHITECTURE.md)**

---

## Licença / créditos

Desenvolvido para o ecossistema CTI Educacional.  
Footer do painel: XDTEC.
