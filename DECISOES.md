# DECISOES.md

## 1. Valor do contrato
O valor total do contrato não foi persistido na tabela `contratos`, pois o enunciado exige que ele seja sempre calculável a partir dos itens do contrato, sem redundância. O total será obtido pela soma de `quantidade * valor_unitario` dos registros de `itens_contrato`.

## 2. Valor da cobrança
A tabela `cobrancas` armazena o valor da cobrança no momento da sua geração. Essa decisão foi tomada para preservar o histórico financeiro mensal, evitando que alterações futuras nos itens do contrato modifiquem cobranças já emitidas.

## 3. Data de vencimento
Foi adotado o campo `dia_vencimento` em `contratos`. A data de vencimento de cada cobrança mensal será gerada com base nesse dia. Quando o mês não possuir o dia informado, será utilizado o último dia do mês.

## 4. Data de referência da cobrança
O campo `data_referencia` representa o mês de competência da cobrança, isto é, o período ao qual ela se refere, independentemente da data de vencimento.

## 5. Saldo de crédito do cliente
O saldo de crédito foi modelado com abordagem híbrida: um campo consolidado em `clientes.saldo_credito` para leitura rápida e uma tabela `transacoes_credito` para rastrear alterações, auditoria e origem das movimentações.

## 6. Usuários do sistema
Foi criada a tabela `usuarios` necessária para representar o responsável interno das ordens de serviço, registrar quem alterou status e controlar a role `financeiro` exigida no endpoint de aplicação manual de crédito.

## 7. Status
Os status serão armazenados como string no banco de dados e representados por enums no PHP, para manter a lógica de domínio desacoplada da persistência e facilitar manutenção e testes.

## 8. Autenticação da API
Foi utilizada autenticação via Laravel Sanctum em vez de JWT. A escolha foi feita por oferecer integração nativa com o ecossistema Laravel, menor complexidade de configuração e aderência suficiente ao escopo do teste, que exige proteção de endpoints e controle de autorização por role, sem demandar uma arquitetura de autenticação mais complexa.

## 9. Representação de status  
Os status de cobrança foram armazenados como string no banco e representados por enum no PHP, para manter a persistência simples e concentrar a lógica de domínio na aplicação.

## 10. Exceções de domínio
Foram criadas exceções específicas para representar regras de negócio inválidas, como transições de status de cobrança proibidas. Isso melhora a clareza do código e facilita o tratamento de erros.

## 11. Service para gerenciamento de regras de negócio de cobrança
A lógica de ciclo de vida das cobranças foi centralizada em um service de domínio, responsável por validar transições, aplicar crédito automaticamente, exigir motivo em cancelamentos e garantir atomicidade com transactions.