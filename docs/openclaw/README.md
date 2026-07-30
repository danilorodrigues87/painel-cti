# OpenClaw ↔ Painel CTI (Agent API)

## Pré-requisitos

1. SQL: `database/agent_api.sql` + `database/agent_escola_config.sql`
2. Liberar módulo `assistente_ia` no plano da escola
3. **Diretor** em Configurações → Assistente IA: LLM OpenClaw + Telegram
4. **Master** em `/master/agent-api`: gerar Agent API da escola + ativar

## Dois tipos de chave de IA (não misturar)

| Config | Onde | Uso |
|--------|------|-----|
| IA Pedagógica | `/painel/config/ia` | Tutor/roleplay no portal EAD |
| LLM OpenClaw | `/painel/config/assistente` | Agente na VPS / Telegram |

Podem usar a mesma key Gemini/OpenAI; o painel trata como configs separadas.

## Auth Agent API

```http
Authorization: Bearer cti_ak_...
```

- Master gera a chave da escola (o Diretor **não** gera).
- Escola só funciona se `agent_ativo=1` (toggle no Master).

Base: `{URL}/api/v1/agent`

## Endpoints (read-only)

Ver `SKILL.md`. Exemplos:

```bash
# Master
curl -H "Authorization: Bearer MASTER" ".../api/v1/agent/escolas/1/resumo"

# Escola
curl -H "Authorization: Bearer ESCOLA" ".../api/v1/agent/resumo"
```

## Fluxo operacional

1. Escola cadastra LLM + bot Telegram no painel  
2. Master abre a escola → **Revelar segredos** + **Gerar Agent API**  
3. Master cola no OpenClaw: Agent key, LLM key, `botToken`, binding `escola_{id}`  
4. Pairing no Telegram  

Ver `openclaw.example.json5`.
