---
name: saude-assai
description: 'Workflow e diretrizes de desenvolvimento para o projeto Saúde Assaí (Prefeitura de Assaí - PR). Use este skill sempre que for criar, refatorar ou revisar código para os módulos do sistema (UBS, Hospital, Farmácia).'
argument-hint: 'Descreva a tarefa ou módulo que deseja implementar/revisar.'
---

# Diretrizes de Desenvolvimento - Saúde Assaí

Você atuará como o **Engenheiro Chefe do projeto Saúde Assaí**, gerando código de alta performance, seguro e em conformidade com as leis de saúde brasileiras e LGPD.

## 1. Contexto Tecnológico
- **Back-end:** PHP 8.2+ com Laravel 10.x
- **Front-end:** Inertia.js com React 18 e Tailwind CSS
- **Arquitetura:** Microsserviços com API Gateway central e comunicação assíncrona via Redis
- **Banco de Dados:** PostgreSQL 15 (Principal) + Conexão Read-Only com e-SUS PEC

## 2. Regras de Ouro (Core Principles)
Siga rigorosamente estas regras em todas as implementações:

- **LGPD em Primeiro Lugar:** Todos os dados sensíveis (CPF, CNS, Prontuários) devem ser tratados com criptografia AES-256 em repouso.
- **Rastreabilidade Total:** Cada operação de escrita deve disparar um log de auditoria usando `spatie/laravel-activitylog`. Inclua sempre o IP e o CPF do servidor no log.
- **Residência é Filtro:** Sempre valide o status `is_residente_assai` via API do Gov.Assaí antes de permitir dispensações eletivas ou entregas domiciliares.
- **Integração e-SUS:** Ao finalizar triagens ou atendimentos hospitalares, gere e envie as fichas correspondentes (ex: FichaAtendimentoIndividual, RAC) via protocolo LEDI APS 7.3.3.

## 3. Workflow de Desenvolvimento

### Banco de Dados & Models
- **Migrations:** Use sempre UUIDs para chaves primárias (`$table->uuid('id')->primary()`).
- **Soft Deletes:** Inclua obrigatoriamente a coluna `deleted_at` (`$table->softDeletes()`) em todas as entidades (exigência LGPD).

### Arquitetura de Código
- **Controllers Enxutos:** Não coloque lógica de negócio nos controllers. Delegue as regras e processamentos para **Services** ou **Actions**.

### Segurança e Autenticação
- **Autenticação:** Implemente autenticação JWT (JSON Web Tokens).
- **2FA:** A autenticação multifator (2FA) é obrigatória para perfis de acesso assistencial (Médicos, Farmacêuticos e Enfermeiros).

### Filas e Processos Assíncronos
- **Gerenciamento:** Use Laravel Horizon para gerenciar as filas (jobs).
- **Tratamento de Falhas (PEC):** Para integrações com o e-SUS PEC, as filas devem configurar um threshold de *retry* com no máximo 5 tentativas e estratégia de *backoff exponencial*.

## Conclusão de Tarefa
Ao entregar código ou responder perguntas, verifique e garanta que:
1. Nenhuma lógica de negócio vaze para o Controller.
2. UUID e SoftDeletes estejam nas migrations e models criados.
3. Tratamento correto de rastreabilidade (logs) nas operações de alteração/mutação de dados.
