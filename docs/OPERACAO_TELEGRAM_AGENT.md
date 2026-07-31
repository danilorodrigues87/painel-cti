# Agente Telegram nativo — operação

## O que faz

Bot por escola que responde no Telegram com:
- IA compartilhada (`Configurações de IA`)
- Dados somente leitura (`AgentAnalyticsHelper`: resumo, agenda, inadimplentes, CRM, etc.)
- Allowlist de Chat ID (obrigatório)

Não dá baixa, não matricula, não envia WhatsApp.

## SQL

```sql
-- Após agent_escola_config.sql
-- database/telegram_agent_nativo.sql
```

## Configuração (Diretor)

1. Plano com módulo `assistente_ia`
2. **Configurações → Configurações de IA**
3. Credenciais de IA + **Ativar Assistente**
4. Token do `@BotFather` + **Chat ID autorizado** (obrigatório)
5. Salvar
6. **Enviar teste** para validar
7. Produção HTTPS: **Ativar webhook**
8. Local XAMPP (sem HTTPS): worker long-poll

## Produção (webhook)

URL: `{URL}/webhook/telegram/{id_admin}/{token}`

O token é derivado de `APP_KEY` (não precisa colar no painel).

Após **Ativar webhook**, o Telegram envia updates para o painel.

## Local / sem HTTPS (worker)

```bash
php worker/telegram_agent.php
# ou uma escola:
php worker/telegram_agent.php 123
```

Cron sugerido (a cada minuto):

```cron
* * * * * php /caminho/painel-cti/worker/telegram_agent.php
```

Se o webhook estiver ativo, `getUpdates` falha — use **Remover webhook** antes do poll.

## Segurança

- Isolamento por `id_admin` na URL do webhook
- Token HMAC na URL
- Só chats listados em `telegram_chat_id` (vírgula = vários)
- Máx. 30 mensagens de usuário / hora / chat
- Histórico curto (8 msgs) em `agent_telegram_mensagens`

## OpenClaw

Continua opcional (Agent API Master). O bot nativo **não** depende da Agent API.
