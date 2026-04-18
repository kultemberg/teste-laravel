
---

#  DECISOES.md 

```md
# Decisões Técnicas

##  Estrutura geral
A aplicação foi organizada seguindo princípios de separação de responsabilidades:
- Controllers → camada HTTP
- Services → regras de negócio
- Models → persistência
- Jobs → processamento assíncrono

---

##  Modelagem do banco

### Tabelas em português
Optou-se por nomes em português (`clientes`, `cobrancas`, etc.) para melhor alinhamento com o domínio do negócio.

---

### Usuários
Foi criada a tabela `usuarios` ao invés da padrão `users`, para manter consistência com o domínio.

---

### Contratos
Não possuem valor total armazenado, sendo calculado dinamicamente através dos itens de contrato, evitando redundância.

---

### Cobranças
Funcionam como snapshot financeiro do período:
- armazenam valores independentes do contrato
- permitem histórico consistente mesmo com mudanças contratuais

---

##  Sistema de crédito

Foi adotado modelo híbrido:
- saldo atual armazenado em `clientes`
- histórico completo em `transacoes_credito`

Isso permite:
- performance nas consultas
- rastreabilidade total

---

##  Idempotência do Job

O job `AplicarCreditoPendente` garante:
- não reaplicar crédito já utilizado
- uso de `lockForUpdate` para evitar concorrência
- verificação de saldo e valor em aberto antes de aplicar

---

##  Dashboard

O dashboard foi implementado via Service dedicado, contendo:
- faturamento mensal
- valores em aberto
- inadimplência
- top clientes
- distribuição de ordens de serviço

---

## ⚡ Cache

Utilizado `Cache::remember` com TTL de 5 minutos.

Invalidação ocorre em:
- alteração de status da cobrança
- aplicação de crédito (manual ou automática)

---

##  Autenticação

Implementado com Laravel Sanctum:
- tokens simples
- adequado para APIs REST

---

## 📄 Paginação

Adotada a paginação padrão do Laravel (`paginate()`) no endpoint `GET /api/v1/cobrancas`.

**Por que paginação por offset (padrão) e não cursor?**

| Critério | Offset | Cursor |
|---|---|---|
| Permite saltar páginas arbitrárias | ✅ Sim | ❌ Não |
| Suporta filtros e ordenação variados | ✅ Sim | ⚠️ Limitado |
| Performance em tabelas grandes | ⚠️ Degrada | ✅ Excelente |
| Complexidade de implementação | ✅ Simples | ⚠️ Maior |

O endpoint suporta filtros dinâmicos, múltiplas ordenações e o volume esperado é controlado. A paginação por offset é a escolha mais adequada para esse contexto, pois permite navegação arbitrária de páginas e compatibilidade total com os filtros.

Parâmetros disponíveis:
- `por_pagina` — quantidade de itens por página (padrão: 15, máximo: 100)
- A resposta inclui `total`, `current_page`, `last_page`, `next_page_url`, `prev_page_url`

---

## 🔒 Rate Limit

Aplicado no endpoint `GET /api/v1/cobrancas` usando o rate limiter nativo do Laravel (`RateLimiter::for`), registrado no `AppServiceProvider`.

- **Limite:** 20 requisições por minuto
- **Chave:** `id` do usuário autenticado (ou IP como fallback)
- **Middleware:** `throttle:cobrancas` aplicado diretamente na rota
- **Resposta ao exceder:** HTTP `429 Too Many Requests` (comportamento nativo do Laravel)

Optou-se pelo rate limiter nativo ao invés de middleware customizado para aproveitar a integração com os headers de resposta (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`).

---

## 🔐 Whitelist de Ordenação

O parâmetro `ordenar_por` aceita apenas os campos definidos explicitamente na `ListarCobrancasRequest`:

```
id, data_referencia, data_vencimento, valor, status, created_at
```

Isso impede que o cliente passe campos arbitrários (ex: subconsultas SQL injetadas via parâmetro), protegendo contra SQL injection por ordenação.

---

##  Considerações finais

As decisões foram tomadas visando:
- simplicidade
- clareza
- consistência de dados
- facilidade de manutenção