---
description: Regras arquiteturais, de segurança (LGPD) e de negócio para o projeto Saúde Assaí. Garante o uso de Services/Actions, Logs de Auditoria, UUIDs e regras do e-SUS PEC.
applyTo:
  - "app/**/*.php"
  - "database/migrations/**/*.php"
  - "resources/js/**/*.{tsx,js,ts}"
---

# Diretrizes do Projeto Saúde Assaí

Ao gerar, refatorar ou revisar código para o projeto Saúde Assaí, siga estritamente as regras abaixo baseadas no Documento de Especificação Técnica e Funcional:

## 1. Arquitetura Laravel (Back-end)
- **Controllers Enxutos:** Nunca insira lógica de negócio complexa em Controllers. Toda lógica de processamento, integração e validação de regras deve ser delegada para **Services** ou **Actions**.
- **Filas e Assincronismo:** Interações com o e-SUS PEC (LEDI APS) e rotinas de envio de notificações devem ser implementadas em Jobs gerenciados pelo **Laravel Horizon**. Configure sempre: `public $tries = 5;` e adicione backoff exponencial para falhas de conectividade.

## 2. Banco de Dados e Models
- **Chaves Primárias:** Utilize sempre UUID (`$table->uuid('id')->primary();`) em novas tabelas.
- **LGPD (Soft Deletes):** É estritamente proibido realizar *hard-delete*. Inclua `$table->softDeletes();` em todas as migrations e adicione a trait `SoftDeletes` nos Models associados.

## 3. Conformidade e LGPD
- **Auditoria Universal:** Qualquer model que sofra alterações (Create, Update, Delete) em dados relevantes deve implementar o `spatie/laravel-activitylog` para registrar quem fez a ação (incluindo IP e CPF logado, inseridos no contexto da aplicação).
- **Criptografia de Dados Sensíveis:** Dados sensíveis de saúde e identificação (ex: `cpf`, `cns`, prontuários) devem ser criptografados no banco (utilize casts nativos como `encrypted` do Laravel 10+ associados à cifra AES-256).

## 4. Regras de Negócio e Integrações
- **Validação de Residência (Gov.Assaí):** Em fluxos de agendamento eletivo, Farmácia Central e Remédio em Casa, o código deve prever a checagem da flag `is_residente_assai`.
- **Fichas LEDI APS:** Ao concluir módulos de Triagem, Atendimento ou Dispensação, preveja os gatilhos/eventos para disparo da Ficha correspondente (FichaAtendimentoIndividual, RAC, FichaProcedimento) para o PostgreSQL do PEC.

## 5. Front-end (Inertia + React)
- Siga as diretrizes de acessibilidade (WCAG 2.1 AA) para componentes visuais focados no cidadão (Módulo 1 - Portal).
- Consuma as APIs respeitando os tipos e validando os retornos baseados nos enums do e-SUS (ex: Cores de Triagem Manchester, status PENDENTE/RESIDENTE).