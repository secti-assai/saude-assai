# Saude Assai (MVP Laravel)

MVP funcional do sistema **Saude Assai** implementado em Laravel 13, cobrindo os modulos M1-M8 com foco em fluxo operacional ponta a ponta.

## Modulos implementados

- M1 Portal Publico: noticias, avisos e indicadores publicos.
- M2 Admin: gestao de usuarios e conteudo do portal.
- M3 Recepcao UBS: cadastro/validacao de cidadao e fila digital.
- M4 Triagem UBS: sinais vitais e classificacao automatica Manchester simplificada.
- M5 Remedio em Casa: prescricao digital e controle de entrega.
- M6 Farmacia Central: dispensacao com validacao de residencia e baixa de estoque.
- M7 Hospital Municipal: prontuario eletronico com CID e desfecho.
- M8 Painel Gestor: KPIs e relatorio de conformidade de uso por servidor.

## Arquitetura MVP

- Framework: Laravel 13 + Blade + Breeze (auth/session).
- Persistencia: Eloquent com schema dedicado para atendimento, farmacia, hospital e auditoria.
- Filas: `LediQueue` + job `DispatchLediRecord` para envio assincrono simulado ao PEC/LEDI.
- Integracoes simuladas:
  - Gov.Assai: `App\Services\GovAssaiService`
  - Manchester: `App\Services\ManchesterRiskService`
  - Auditoria: `App\Services\AuditService`

## Perfis RBAC

Middleware `role` aplicado por rota.

Perfis suportados:

- `admin_secti`
- `gestor`
- `recepcionista`
- `enfermeiro`
- `medico_ubs`
- `medico_hospital`
- `farmaceutico`
- `entregador`
- `auditor`

## Rotas principais

- `/` Portal Publico
- `/dashboard` Painel Gestor
- `/recepcao`
- `/triagem`
- `/prescricoes`
- `/farmacia`
- `/hospital`
- `/entregas`
- `/admin/usuarios`
- `/relatorios/conformidade`

## Setup

1. Instalar dependencias:

```bash
composer install
npm install
```

2. Configurar `.env` (DB e fila).

3. Gerar chave:

```bash
php artisan key:generate
```

4. Migrar e semear:

```bash
php artisan migrate --seed
```

5. Rodar app:

```bash
composer run dev
```

## Usuario inicial

- Email: `admin@saudeassai.local`
- Senha: `password`

## Observacao sobre ambiente local

O ambiente atual retornou erro de driver SQLite ausente (`could not find driver`), impedindo `migrate`/`test` neste host.

Para validar localmente, instale o driver PDO SQLite ou configure outro banco no `.env`.
