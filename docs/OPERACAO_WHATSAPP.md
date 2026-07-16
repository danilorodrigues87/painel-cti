# WhatsApp / Evolution API — operação

## Variáveis no `.env`

```env
EVOLUTION_URL=https://sua-evolution.exemplo.com
EVOLUTION_API_KEY=sua_chave
EVOLUTION_WEBHOOK_SECRET=gere_um_segredo_longo
```

## SQL

1. Bloco **WhatsApp / Evolution** em `ARCHITECTURE.md` (se ainda não rodou)
2. Bloco **WhatsApp inbox + setores + chatbot (Fase 3b)**

## Plano / módulo

- Slug: `whatsapp` · Label: `WhatsApp`
- Diretor: acesso automático
- Funcionários: marcar **WhatsApp** nas permissões + liberar slug na escola se `modulos_liberados` for lista explícita

Exemplo (escola com lista explícita):

```sql
-- Ajuste o JSON conforme os módulos já liberados
UPDATE escolas_assinantes
SET modulos_liberados = JSON_ARRAY_APPEND(COALESCE(modulos_liberados, JSON_ARRAY()), '$', 'whatsapp')
WHERE id = SEU_ID_ADMIN;
```

(Se `modulos_liberados` for `NULL`, todos os módulos já estão liberados.)

## Fluxo

1. **Comunicação** → conectar QR do número da escola  
2. **WhatsApp** → aba *Setores e atendentes* → vincular usuários  
3. Cliente manda mensagem → bot envia menu (1 Comercial, 2 Financeiro…)  
4. Cliente escolhe → conversa vai para a **fila do setor**  
5. Atendente **Assumir** → responde no inbox  

## Multi-número (futuro)

Tabela `whatsapp_numeros` já existe; hoje sincroniza 1 registro *Principal* ao conectar.
