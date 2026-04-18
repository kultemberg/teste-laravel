
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

##  Paginação

Utilizada paginação padrão do Laravel para:
- evitar sobrecarga
- melhorar performance

---

##  Rate Limit

Aplicado no endpoint de cobranças para controle de uso.

---

##  Considerações finais

As decisões foram tomadas visando:
- simplicidade
- clareza
- consistência de dados
- facilidade de manutenção