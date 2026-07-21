# Painel CTI

Sistema de gestão escolar multi-tenant (PHP MVC) para escolas de informática / educação tecnológica.

**Ambiente:** XAMPP (local) e Linux (produção)  
**Stack:** PHP 8.x · MySQL · Bootstrap 5 · jQuery · PHPMailer · Composer  
**DB local típico:** `cti_admin` (ver `.env`)

Documentação completa (módulos, SQL, roadmap, handoff para IA): **[`ARCHITECTURE.md`](ARCHITECTURE.md)**

---

## O que o sistema faz

| Área | Funcionalidades |
|------|-----------------|
| **Master** | `/master` — escolas, planos (valor mensal), assinaturas SaaS (PIX conta CTI) |
| **Pedagógico** | Alunos, responsáveis, trilhas/cursos, **Cursos Online (EAD)**, matrículas, certificados, modelo de contrato |
| **Financeiro** | Caixa, carnês (simples / PIX MP da escola), relatórios, **Assinatura** do painel (PIX CTI) |
| **CRM** | Leads (Kanban), tarefas, histórico, WA automático em mudança de status |
| **Agenda** | Laboratórios, horários, agendamentos, diário |
| **Comunicação** | SMTP por escola, campanhas e-mail, cobrança mensalidade alunos, WhatsApp (Evolution) |
| **Acesso** | Multi-escola (`id_admin`), permissões usuário ∩ módulos do plano/escola |

---

## Requisitos

- PHP 8+ com extensões: `pdo_mysql`, `openssl`, `mbstring`, `json`
- MySQL / MariaDB
- Composer
- Apache (XAMPP) com `mod_rewrite` (ou equivalente)

---

## Instalação rápida (local)

1. Clone / copie o projeto para `htdocs` (ex.: `C:\xampp\htdocs\pjt\painel-cti`)
2. `composer install`
3. Copie `.env.example` → `.env` e preencha
4. Acesse (ex.: `http://localhost/pjt/painel-cti/painel`)
5. Rode no phpMyAdmin os SQLs em `database/` (ver `ARCHITECTURE.md`)

### Variáveis `.env` essenciais

```env
URL=http://localhost/pjt/painel-cti
SITE=Painel CTI
TIMEZONE=America/Sao_Paulo
SYSTEM_TOKEN=token-secreto-do-sistema
APP_KEY=chave-longa-para-criptografia

DB_HOST=localhost
DB_NAME=cti_admin
DB_USER=root
DB_PASS=

# E-mail padrão do SISTEMA (recuperação / fallback)
SMTP_HOST=
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_FROM_EMAIL=
SMTP_FROM_NAME=
SMTP_ENCRYPTION=tls

# Quem acessa /master (vírgula)
MASTER_EMAILS=seu@email.com

# Evolution (WhatsApp) — global
EVOLUTION_URL=
EVOLUTION_API_KEY=
EVOLUTION_WEBHOOK_SECRET=

# Mercado Pago CTI — cobrança SaaS das escolas (não é o token do carnê do aluno)
MP_CTI_ACCESS_TOKEN=
MP_CTI_WEBHOOK_SECRET=
MP_CTI_WEBHOOK_TOKEN=
MP_CTI_PAYER_EMAIL=
```

> SMTP da escola: **Configurações → Comunicação**.  
> PIX dos alunos: **Configurações → Pagamentos** (token da escola).  
> PIX da assinatura CTI: credenciais `MP_CTI_*` no `.env`.

---

## Assinatura SaaS (resumo)

1. Master → Planos: definir **valor mensal**
2. Master → Escolas: vincular plano + dia de vencimento
3. Master → Assinaturas: **Gerar mês** (ou cron `worker/saas.php`)
4. Diretor → Financeiro → **Assinatura**: pagar PIX (QR + copia e cola)
5. Webhook CTI ou botão “Já paguei” / Master “marcar paga”
6. Após 5 dias do vencimento sem pagamento → escola `ativo=n` (login bloqueado)

SQL: `database/saas_assinatura.sql` e, se preciso, `database/saas_faturas_pix_qr.sql`.

---

## Estrutura resumida

```
painel-cti/
├── app/
│   ├── Common/          # Helpers, Gateways MP, SystemModules, SaasAssinaturaService
│   ├── Controller/      # Admin, Master, Webhook, Autentication, Api
│   ├── Model/Entity/
│   ├── Http/
│   └── Session/
├── database/            # Scripts SQL para phpMyAdmin
├── includes/app.php
├── resources/           # Views, JS, CSS
├── routes/              # admin + master + api
├── worker/              # campanhas.php, cobranca.php, saas.php
├── README.md
└── ARCHITECTURE.md
```

---

## Workers (cron)

```bash
php worker/campanhas.php          # fila e-mail (a cada 1 min)
php worker/cobranca.php           # mensalidades alunos (1x/dia)
php worker/saas.php               # assinatura SaaS escolas (1x/dia)
php worker/status-email.php       # diagnóstico (não envia)
```

---

## Convenções importantes

- Tenant = `id_admin` (`escolas_assinantes`)
- AJAX: sempre `url_base + rota` (`resources/js/url-base.js`)
- SQL novo: script em `database/` + usuário cola no phpMyAdmin
- Commits / push: só quando o usuário pedir
- Controllers/Entities com mesmo nome: usar alias
- `Response` + `application/json`: se o body já for string JSON, não re-encodar

Para agentes de IA: leia **`ARCHITECTURE.md` seção 8–12** (roadmap, armadilhas, handoff).

---

## Licença / créditos

Desenvolvido para o ecossistema CTI Educacional.  
Footer do painel: XDTEC.
