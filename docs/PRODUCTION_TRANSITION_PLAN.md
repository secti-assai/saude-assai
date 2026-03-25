# Transicao MVP -> Producao (Saude Assai)

## Entregas implementadas nesta etapa

### 1) UX/UI global
- Mascara de CPF global (000.000.000-00) em todos os campos contendo cpf.
- Validacao de digito verificador no blur do campo CPF.
- Toasts globais para feedback de sucesso/erro.
- Estado de loading em submits de formulario (botao desabilitado + spinner).

### 2) Automacao Gov.Assai em background
- Job `SyncCitizenFromGovAssaiJob` implementado com:
  - `tries = 5`
  - `backoff` exponencial em passos [5, 15, 30, 60, 120].
- Disparo do job integrado em:
  - Recepcao
  - Hospital
  - Prescricoes
- Objetivo: manter dados de cidadao atualizados sem acao manual.

### 3) ACL inicial e visao central x unidade
- Dashboard agora separa escopo:
  - perfis centrais (`admin_secti`, `gestor`, `auditor`) veem consolidado municipal.
  - perfis de unidade veem apenas dados da propria `health_unit_id`.

### 4) Estabilidade e excecoes
- Handler global de excecoes configurado em `bootstrap/app.php`:
  - logging estruturado para excecoes nao tratadas.
  - pagina fallback de erro em producao para requests web.

## Proximas etapas (roadmap curto)

### Multi-tenant completo (banco)
1. Adicionar `health_unit_id` direto nas tabelas assistenciais sem FK direta atual.
2. Criar indices compostos por unidade para consultas de alto volume.
3. Aplicar escopo de unidade via middleware/repository em todos os modulos.

### ACL avancada
1. Polices por recurso (triagem, prescricao, dispensacao, entrega).
2. Permissoes granulares por acao (view/create/update/export).
3. Perfil "gestor_unidade" dedicado.

### Integracao em tempo real
1. Definir eventos de webhook para mudancas de status clinico e logistica.
2. Fila Redis em producao com workers dedicados por dominio (`gov_assai`, `ledi`, `logistica`).
3. DLQ (fila de falhas) e alertas operacionais.

#### Endpoints de integracao implementados
- `GET /api/saude/cidadaos/cpf/{cpf}` (middleware `api.key`)
- `POST /api/integracoes/gov-assai/webhooks/cidadaos.updated` (middlewares `api.key` + `webhook.govassai`)

#### Workers recomendados (imediato)
- `php artisan queue:work redis --queue=gov_assai --sleep=2 --tries=5 --backoff=5`
- `php artisan queue:work redis --queue=ledi --sleep=2 --tries=5 --backoff=5`
- `php artisan queue:work redis --queue=default --sleep=2 --tries=3`

### Observabilidade
1. Integrar Sentry/Bugsnag para erros de runtime.
2. Dashboard de filas (Horizon) e taxa de falha por job.
3. Metricas de SLA por modulo (recepcao, triagem, farmacia, entregas).

## Checklist de deploy
1. Definir `APP_ENV=production` e `APP_DEBUG=false`.
2. Configurar Redis e `QUEUE_CONNECTION=redis`.
3. Rodar migrations e cache de configuracao/rotas/views.
4. Subir workers (`queue:work` ou Horizon) com restart supervisionado.
5. Validar jobs do Gov.Assai e LEDI em homologacao com carga.
