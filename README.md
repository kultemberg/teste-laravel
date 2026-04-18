# 💼 Sistema Financeiro — API REST com Laravel 13

API RESTful para gestão financeira de clientes, contratos, cobranças e ordens de serviço, com sistema de crédito automatizado, dashboard analítico e autenticação via Sanctum.

---

## �️ Como rodar localmente

### Pré-requisitos

Certifique-se de ter instalado na sua máquina:

- [PHP](https://www.php.net/) >= 8.3 (com extensões `pdo_sqlite`, `mbstring`, `xml`, `curl`)
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) >= 18 + npm

---

### Passo a passo

**1. Clone o repositório**

```bash
git clone <url-do-repositorio>
cd teste-laravel
```

**2. Configure o ambiente**

```bash
cp .env.example .env
```

> Edite o `.env` se necessário. Por padrão, o projeto usa **SQLite** — nenhuma configuração adicional de banco é necessária.

**3. Execute a instalação completa**

```bash
composer run setup
```

Esse único comando instala dependências, gera a chave da aplicação, cria o banco de dados e executa todas as migrations automaticamente.

**4. (Opcional) Popule o banco com dados de demonstração**

```bash
php artisan db:seed --class=DashboardDemoSeeder
```

Cria um usuário de teste e dados financeiros para explorar a API:
- **E-mail:** `financeiro.seeder@teste.com`
- **Senha:** `123456`

**5. Inicie o servidor**

```bash
composer run dev
```

A API estará disponível em: **`http://localhost:8000/api/v1`**

---

### Verificando se está funcionando

Faça uma requisição de login para confirmar que tudo está rodando:

```bash
curl -s -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"financeiro.seeder@teste.com","password":"123456"}' \
  | python3 -m json.tool
```

Se retornar um `token`, a API está pronta para uso. ✅

---

## �🚀 Tecnologias

| Tecnologia             | Versão    |
|------------------------|-----------|
| PHP                    | ^8.3      |
| Laravel Framework      | ^13.0     |
| Laravel Sanctum        | ^4.3      |
| SQLite (dev)           | —         |
| Laravel Queues (Jobs)  | —         |
| Cache (file driver)    | —         |

---

## 📦 Requisitos

- PHP >= 8.3
- Composer
- Node.js + npm
- SQLite (ou outro banco configurado no `.env`)

---

## ⚙️ Instalação

### Instalação rápida (via script `setup`)

```bash
composer run setup
```

Esse comando executa automaticamente:
1. `composer install`
2. Copia `.env.example` para `.env` (se não existir)
3. Gera a `APP_KEY`
4. Executa as migrations
5. `npm install` + `npm run build`

### Instalação manual

```bash
# 1. Instalar dependências PHP
composer install

# 2. Copiar o arquivo de ambiente
cp .env.example .env

# 3. Gerar a chave da aplicação
php artisan key:generate

# 4. Executar as migrations
php artisan migrate

# 5. Instalar dependências JS e buildar assets
npm install
npm run build
```

---

## ▶️ Executando o projeto

Para iniciar todos os processos em paralelo (servidor, fila, logs e Vite):

```bash
composer run dev
```

Isso sobe simultaneamente:
- `php artisan serve` — servidor HTTP
- `php artisan queue:listen` — processamento de jobs
- `php artisan pail` — visualizador de logs em tempo real
- `npm run dev` — build de assets com hot-reload

---

## 🌱 Seed de demonstração

Para popular o banco com dados de demonstração para testar o dashboard:

```bash
php artisan db:seed --class=DashboardDemoSeeder
```

Dados criados:
- 1 usuário (`financeiro.seeder@teste.com` / senha: `123456`)
- 2 clientes com contratos e itens
- Cobranças com status variados (`pago_parcial`, `inadimplente`)
- 3 ordens de serviço com prioridades e status distintos

---

## 🔑 Autenticação

A API utiliza **Laravel Sanctum** com tokens de acesso simples (adequado para APIs REST stateless).

### Login

```http
POST /api/v1/login
Content-Type: application/json

{
  "email": "financeiro.seeder@teste.com",
  "password": "123456"
}
```

**Resposta:**
```json
{
  "token": "seu-token-aqui",
  "usuario": { ... }
}
```

### Usuário autenticado

```http
GET /api/v1/me
Authorization: Bearer {token}
```

### Logout

```http
POST /api/v1/logout
Authorization: Bearer {token}
```

---

## 📡 Endpoints da API

Todos os endpoints autenticados exigem o header:
```
Authorization: Bearer {token}
```

### Dashboard Financeiro

```http
GET /api/v1/dashboard
```

Retorna um resumo financeiro com:
- Faturamento mensal
- Valor total em aberto
- Taxa de inadimplência
- Top clientes por valor
- Distribuição de status das ordens de serviço

> ⚡ Resultado cacheado por **5 minutos** para melhor performance. O cache é invalidado automaticamente ao alterar cobranças ou aplicar créditos.

---

### Cobranças

```http
GET /api/v1/cobrancas
```

Suporta filtros e paginação via query string:

| Parâmetro              | Tipo       | Descrição                                      |
|------------------------|------------|------------------------------------------------|
| `status[]`             | array      | Filtrar por status (`aguardando_pagamento`, `pago_parcial`, `pago`, `inadimplente`) |
| `data_referencia_inicial` | date    | Data de referência inicial (`YYYY-MM-DD`)      |
| `data_referencia_final`   | date    | Data de referência final (`YYYY-MM-DD`)        |
| `busca`                | string     | Busca por nome ou documento do cliente         |
| `ordenar_por`          | string     | Campo de ordenação (padrão: `data_vencimento`) |
| `direcao`              | string     | `asc` ou `desc` (padrão: `asc`)                |
| `por_pagina`           | integer    | Itens por página (padrão: `15`)                |

> 🛡️ Este endpoint possui **rate limiting** configurado (`throttle:cobrancas`).

---

### Crédito do Cliente

```http
POST /api/v1/clientes/{cliente}/aplicar-credito
Authorization: Bearer {token}
```

Aplica crédito ao saldo de um cliente. O sistema despacha o job `AplicarCreditoPendente` de forma assíncrona para quitar cobranças pendentes automaticamente.

---

## ⚙️ Jobs e Processamento Assíncrono

### `AplicarCreditoPendente`

Job responsável por aplicar automaticamente o saldo de crédito do cliente em cobranças pendentes.

**Garantias de idempotência:**
- Verifica se já existe transação de crédito idêntica antes de processar
- Usa `lockForUpdate` para evitar condições de corrida
- Confere saldo e valor em aberto antes de qualquer operação

**Configuração de retentativas:**
- Tentativas: `4`
- Backoff progressivo: `60s → 300s → 1800s`

---

## 🗃️ Estrutura do Banco de Dados

> Tabelas nomeadas em português para alinhamento com o domínio do negócio.

```
usuarios          → Usuários do sistema (autenticação)
clientes          → Clientes com saldo de crédito
contratos         → Contratos por cliente (valor calculado dinamicamente)
itens_contrato    → Itens e valores que compõem o contrato
cobrancas         → Cobranças mensais (snapshot financeiro do período)
ordens_servico    → Ordens de serviço vinculadas a contratos
historico_status_ordens → Histórico de mudanças de status das OS
transacoes_credito → Histórico completo de movimentações de crédito
```

### Modelo de crédito (híbrido)

| Onde           | O que armazena                        |
|----------------|---------------------------------------|
| `clientes`     | Saldo atual (rápido para consultas)   |
| `transacoes_credito` | Histórico completo (auditoria) |

---

## 🏗️ Arquitetura da Aplicação

```
app/
├── Http/
│   ├── Controllers/Api/   → Camada HTTP (AuthController, CobrancaController, etc.)
│   └── Requests/          → Form Requests para validação
├── Models/                → Eloquent Models (persistência)
├── Services/              → Regras de negócio (Clientes, Cobranças, Dashboard)
├── Jobs/                  → Processamento assíncrono (AplicarCreditoPendente)
└── Enums/                 → Enumerações tipadas
```

---

## 🧪 Testes

```bash
composer run test
```

Ou diretamente:
```bash
php artisan test
```

---

## 📋 Decisões Técnicas

Consulte o arquivo [DECISOES.md](./DECISOES.md) para entender as principais decisões de arquitetura e modelagem do projeto.

---

