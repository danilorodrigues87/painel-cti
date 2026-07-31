# OpenClaw ↔ Painel CTI (Agent API)

> **Legado / opcional.** O caminho principal do Assistente é o **agente Telegram nativo** no painel (`docs/OPERACAO_TELEGRAM_AGENT.md`). OpenClaw na VPS continua suportado via Agent API + espelho `llm_*`.

## Pré-requisitos

1. SQL: `database/agent_api.sql` + `database/agent_escola_config.sql`
2. Liberar módulo `assistente_ia` no plano da escola
3. **Diretor** em Configurações → **Configurações de IA**: credenciais + Telegram
4. **Master** em `/master/agent-api`: gerar Agent API da escola + ativar (só se for usar OpenClaw externo)

## Credenciais de IA

| Config | Onde | Uso |
|--------|------|-----|
| Credenciais compartilhadas | `/painel/config/ia` (`escola_integracoes.ai_*`) | EAD, Assistente nativo, variação WA |
| Espelho LLM OpenClaw | `agent_escola_config.llm_*` (preenchido ao salvar) | Colar no OpenClaw na VPS |

Para o bot nativo no Telegram, veja `docs/OPERACAO_TELEGRAM_AGENT.md`.

## Auth Agent API

```http
Authorization: Bearer cti_ak_...
```

- Master gera a chave da escola (o Diretor **não** gera).
- Escola só funciona se `agent_ativo=1` (toggle no Master).

Base: `{URL}/api/v1/agent`

## Endpoints (read-only)

Ver `SKILL.md`.
