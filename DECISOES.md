
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

## ⚡ Idempotência do Job `AplicarCreditoPendente`

O teste exige que o job seja idempotente — se disparado duas vezes ao mesmo tempo para o mesmo cliente, o segundo não pode causar dupla aplicação de crédito.

### Mecanismo adotado: `lockForUpdate` + verificação de transação existente

O job utiliza dois níveis de proteção combinados:

**Nível 1 — `lockForUpdate` (bloqueio pessimista)**

```php
$cliente = Cliente::query()->lockForUpdate()->find($clienteId);
$cobranca = Cobranca::query()->lockForUpdate()->first();
```

Ao iniciar a transação, client e cobrança são bloqueados no banco de dados. Se dois workers processarem o mesmo job simultaneamente, o segundo ficará aguardando o primeiro terminar. Quando o primeiro terminar e liberar o lock, o segundo verá o estado atualizado.

**Nível 2 — verificação de transação existente**

```php
$jaExisteTransacao = TransacaoCredito::query()
    ->where('cobranca_id', $cobranca->id)
    ->where('tipo', 'credito_aplicado_cobranca')
    ->where('valor', $valorAplicado)
    ->exists();

if ($jaExisteTransacao) { return; }
```

Mesmo que os dois jobs sejam executados sequencialmente (não em paralelo), o segundo verificará que já existe uma `TransacaoCredito` registrada para aquela cobrança com aquele valor e encerrará silenciosamente.

**Por que não usar `uniqueUntilProcessing` do Laravel?**

O `ShouldBeUnique` previne que o mesmo job entre na fila duas vezes, mas não cobre o caso de dois jobs já na fila sendo processados ao mesmo tempo por workers diferentes. A combinação `lockForUpdate + verificação de transação` é mais robusta e cobre todos os cenários.

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