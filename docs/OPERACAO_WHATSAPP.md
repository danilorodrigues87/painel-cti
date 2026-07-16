# WhatsApp / Evolution API — operação

## Variáveis no `.env` (servidor)

```env
EVOLUTION_URL=https://sua-evolution.exemplo.com
EVOLUTION_API_KEY=sua_chave
EVOLUTION_WEBHOOK_SECRET=gere_um_segredo_longo
```

Não commitar o `.env`. Em produção, use as mesmas chaves do servidor Evolution.

## SQL (phpMyAdmin)

Execute o bloco **WhatsApp / Evolution** em `ARCHITECTURE.md` (colunas em `escola_integracoes` + tabelas `whatsapp_conversas` / `whatsapp_mensagens`).

## Uso no painel

1. Diretor → **Comunicação**
2. Bloco **WhatsApp — Evolution API**
3. **Conectar / QR** → escanear no celular
4. **Enviar teste** com DDD + número
5. **Salvar limites** (intervalo / máx. por hora — usados na Fase 4 de campanhas)

## Webhook

URL gerada automaticamente:

`{URL}/webhook/evolution/{id_admin}/{token}`

- Em **localhost**, a Evolution na nuvem **não alcança** o XAMPP. Status/QR/envio de teste funcionam; inbox via webhook exige túnel (ngrok) ou servidor público.
- Em produção com HTTPS, o webhook grava conversas/mensagens nas tabelas.

## Instância

Nome padrão: `escola_{id_admin}` (uma por escola).
