**SAÚDE ASSAÍ**

_Sistema Integrado de Gestão em Saúde Municipal_

─────────────────────────────────

**DOCUMENTO DE ESPECIFICAÇÃO TÉCNICA E FUNCIONAL**

Versão 1.0 • Março 2026

**FICHA TÉCNICA DO DOCUMENTO**

**Órgão Responsável:** Divisão de Ciência, Tecnologia e Inovação (SECTI) - Prefeitura de Assaí - PR

**Sistema:** Saúde Assaí - Sistema Integrado de Gestão em Saúde Municipal

**Versão do Documento:** 1.0 - Documento Inicial

**Data de Elaboração:** Março de 2026

**Integrações Previstas:** Gov.Assaí, e-SUS PEC (PostgreSQL), LEDI APS 7.3.3, DW PEC

**Conformidade Legal:** LGPD (Lei 13.709/2018), CFM, Portaria MS 2.436/2017, RDC ANVISA

# **Sumário**

_(Ver índice de seções abaixo)_

# **1\. Visão Geral do Sistema**

## **1.1 Apresentação**

O Saúde Assaí é uma plataforma digital integrada de gestão em saúde pública criada para o Município de Assaí - Paraná. Ele nasce da consolidação e expansão do ecossistema digital já iniciado pelo Gov.Assaí - sistema unificado municipal -, somando ao programa Remédio em Casa e ao banco de dados nacional do e-SUS PEC um conjunto de módulos que cobrem todos os pontos de atenção da rede municipal: portal público, UBSs, Farmácia Central e Hospital Municipal.

O sistema substitui integralmente o uso de papel nos processos assistenciais e administrativos, impõe rastreabilidade a cada ação dos servidores e oferece ao gestor municipal painéis de monitoramento em tempo real - indo ao encontro dos ODS 3, 11, 13 e 17 da ONU e dos marcos legais do SUS.

## **1.2 Objetivos Estratégicos**

- Digitalizar 100% dos processos de recepção, triagem, consulta, prescrição e dispensação de medicamentos.
- Integrar todos os fluxos ao banco de dados PostgreSQL do e-SUS PEC via LEDI APS 7.3.3 e DW PEC.
- Validar a residência municipal de cidadãos para controle fiscal e qualidade do gasto público.
- Garantir monitoramento de uso do sistema por servidores, possibilitando auditoria e gestão de desempenho.
- Substituir o prontuário de papel do Hospital Municipal por ficha eletrônica estruturada, com envio automático ao PEC.
- Operar o programa Remédio em Casa com prescrição digital, logística de entrega rastreada e frota elétrica.
- Disponibilizar portal público de informações de saúde com painel de transparência de dados municipais.

## **1.3 Princípios Norteadores**

| **Princípio**      | **Aplicação no Sistema**                                                                        |
| ------------------ | ----------------------------------------------------------------------------------------------- |
| Universalidade     | Acesso ao portal público sem login; atendimento de urgência independente de residência.         |
| Integralidade      | Histórico clínico unificado do cidadão em todos os módulos.                                     |
| Equidade           | Validação de residência restringe serviços eletivos a munícipes, protegendo os recursos locais. |
| LGPD               | Dados pessoais e de saúde tratados com pseudonimização, auditoria e consentimento documentado.  |
| Rastreabilidade    | Cada ação de servidor é registrada com timestamp, CPF funcional e origem de IP.                 |
| Interoperabilidade | Comunicação via LEDI APS (Apache Thrift/XML) com o PEC; API REST para Gov.Assaí.                |

## **1.4 Escopo Macro - Módulos do Sistema**

| **Módulo**                  | **Descrição Resumida**                                                             | **Usuários Principais**              |
| --------------------------- | ---------------------------------------------------------------------------------- | ------------------------------------ |
| **M1 - Portal Público**     | Informações de saúde, notícias, serviços e transparência para o cidadão            | Cidadão (sem login)                  |
| **M2 - Painel Admin**       | Gestão de conteúdo do portal, usuários, relatórios gerenciais                      | Administradores SECTI                |
| **M3 - Recepção UBS**       | Chegada do paciente, validação de cadastro, fila digital, envio ao PEC             | Recepcionistas UBS                   |
| **M4 - Triagem UBS**        | Coleta de sinais vitais, queixas, classificação de risco - envio SOAP ao PEC       | Enfermeiros / Técnicos               |
| **M5 - Remédio em Casa**    | Prescrição digital, fila de separação na farmácia, logística e rastreio de entrega | Médicos, Farmacêuticos, Motoboys     |
| **M6 - Farmácia Central**   | Dispensação com validação de residência, controle de estoque, monitoramento de uso | Farmacêuticos / Auxiliares           |
| **M7 - Hospital Municipal** | Prontuário eletrônico de emergência/consulta, substitui papel atual, envia ao PEC  | Médicos, Enfermeiros, Recepcionistas |
| **M8 - Painel Gestor**      | Dashboard KPIs, relatórios, auditoria de uso por servidor, mapas de atendimento    | Secretaria de Saúde / SECTI          |

# **2\. Arquitetura Técnica e Integrações**

## **2.1 Visão Arquitetural**

O Saúde Assaí opera em arquitetura de microsserviços, com um API Gateway central que roteia requisições para cada módulo. O banco de dados principal é PostgreSQL 15+ hospedado no servidor municipal. A camada de integração com o e-SUS PEC utiliza o protocolo definido pelo LEDI APS versão 7.3.3 (compatível com PEC e-SUS APS 5.4.20+), implementando tanto Apache Thrift quanto XML conforme o tipo de ficha a ser enviada. O DW PEC é consultado somente em modo leitura para relatórios gerenciais.

## **2.2 Pilha Tecnológica**

| **Camada**                   | **Tecnologia / Detalhe**                                           |
| ---------------------------- | ------------------------------------------------------------------ |
| Back-end API                 | Node.js 20+ (NestJS) com TypeScript - REST + WebSocket             |
| Banco de Dados Principal     | PostgreSQL 15 - servidor municipal existente                       |
| Banco de Dados PEC (leitura) | PostgreSQL do e-SUS PEC - acesso DW via schema dw\_\*              |
| Integração PEC (escrita)     | LEDI APS 7.3.3 - Apache Thrift via TCP (porta padrão PEC) ou XML   |
| Front-end Web                | React 18 + TypeScript + Tailwind CSS - PWA                         |
| Autenticação                 | JWT (RS256) + 2FA para servidores de saúde; SSO via Gov.Assaí      |
| Mensageria / Filas           | Redis + Bull (filas de prescrição, entregas, notificações)         |
| Notificações                 | WhatsApp Business API (agendamentos, confirmações, status entrega) |
| Monitoramento Infraestrutura | Prometheus + Grafana                                               |
| Logs de Auditoria            | Tabela audit_log imutável + rotação para arquivo comprimido mensal |
| Assinatura Digital           | ICP-Brasil (médicos) via biblioteca Node.js node-pkijs             |
| Armazenamento de Documentos  | MinIO (S3-compatível) hospedado localmente - prontuários PDF       |

## **2.3 Integração com o Gov.Assaí**

O Gov.Assaí é o sistema unificado municipal que já detém o cadastro georreferenciado de cidadãos, validação de residência e histórico de serviços. A integração ocorre via API REST autenticada com OAuth 2.0 (Client Credentials). Os principais fluxos de dados são:

- Consulta de residência do cidadão (CPF → endereço validado → flag is_residente_assai).
- Recuperação do histórico de serviços municipais utilizados (para o prontuário unificado).
- Push de novos atendimentos de saúde para o histórico do cidadão no Gov.Assaí.
- Autenticação SSO de servidores municipais com perfis já cadastrados no Gov.Assaí.

## **2.4 Integração com o e-SUS PEC - LEDI APS**

O Layout e-SUS APS de Dados e Interface (LEDI APS) define os contratos de envio de fichas ao PEC. O Saúde Assaí implementa os seguintes tipos de ficha conforme LEDI APS 7.3.3:

| **Ficha LEDI APS**               | **Módulo Saúde Assaí**      | **Gatilho de Envio**                |
| -------------------------------- | --------------------------- | ----------------------------------- |
| **FichaAtendimentoIndividual**   | M3/M4 Recepção+Triagem UBS  | Após finalização da triagem         |
| **FichaAtendimentoOdontologico** | M7 Hospital (se houver)     | Após consulta odontológica          |
| **FichaCadastroDomiciliar**      | M3 Recepção (novo paciente) | Ao cadastrar novo cidadão           |
| **FichaCadastroIndividual**      | M3 Recepção                 | Atualização de dados cadastrais     |
| **FichaProcedimento**            | M6 Farmácia / M7 Hospital   | Ao registrar procedimento           |
| **FichaConsumoAlimentar**        | -                           | Reservado para ACS futuro           |
| **FichaAtividadeColetiva**       | -                           | Reservado para campanhas            |
| **FichaAvaliacaoElegibilidade**  | M5 Remédio em Casa          | Validação para entrega domiciliar   |
| **RAC (Resumo Alta Clínica)**    | M7 Hospital                 | Alta ou encerramento de atendimento |

O envio ao PEC ocorre de forma assíncrona via fila Redis/Bull. Em caso de falha de conectividade, as fichas ficam na fila com retry automático (backoff exponencial, máximo 5 tentativas). Um painel de status no M8 exibe fichas pendentes, enviadas e rejeitadas pelo PEC.

## **2.5 Leitura do DW PEC**

O Data Warehouse do e-SUS PEC (DW PEC, versão 7.3.7) é acessado em modo somente-leitura pelo módulo M8 (Painel Gestor). As tabelas Fatos e Dimensões permitem cruzar dados históricos de atendimentos para geração de indicadores como: cobertura ESF, procedimentos realizados, CIDs mais frequentes e adesão a programas prioritários. A credencial de acesso ao schema dw\_\* é configurada por variável de ambiente, com permissão SELECT apenas.

# **3\. Perfis de Usuário e Controle de Acesso**

## **3.1 Perfis e Permissões**

O sistema define perfis hierárquicos com controle RBAC (Role-Based Access Control). Cada ação sensível é registrada na tabela audit_log com: user_id, profile, module, action, entity_id, timestamp_utc, ip_address, user_agent.

| **Perfil**                       | **Módulos com Acesso**   | **Capacidades Principais**                                                                                    |
| -------------------------------- | ------------------------ | ------------------------------------------------------------------------------------------------------------- |
| **Cidadão**                      | M1 - Portal Público      | Consultar informações, notícias, localização de UBSs, serviços disponíveis. Sem login.                        |
| **Administrador SECTI**          | M1, M2, M8               | Gerenciar conteúdo do portal, criar/bloquear usuários, acessar todos os relatórios e auditoria de servidores. |
| **Gestor de Saúde**              | M2 (somente leitura), M8 | Visualizar dashboards, exportar relatórios, acompanhar KPIs. Não edita conteúdo nem dados clínicos.           |
| **Recepcionista UBS**            | M3                       | Receber paciente, validar CPF/CNS/Gov.Assaí, criar/atualizar cadastro, gerar senha de fila digital.           |
| **Enfermeiro / Técnico**         | M4                       | Registrar triagem completa (sinais vitais, queixas, nível de consciência, comorbidades), classificar risco.   |
| **Médico UBS**                   | M4 (leitura triagem), M5 | Ver histórico do paciente, emitir prescrição digital, solicitar exames, acionar Remédio em Casa.              |
| **Médico Hospital**              | M7                       | Preencher prontuário eletrônico de emergência/consulta, prescrever, registrar CID, assinar digitalmente.      |
| **Farmacêutico / Aux. Farmácia** | M5, M6                   | Separar medicamentos, dispensar com validação de residência, gerenciar estoque, confirmar entregas.           |
| **Entregador (Motoboy)**         | M5 - App Entregador      | Confirmar retirada, registrar localização de entrega, coletar assinatura digital do paciente.                 |
| **Auditor**                      | M8 - somente leitura     | Consultar todos os logs de auditoria, gerar relatórios de conformidade e desempenho de servidores.            |

## **3.2 Regras de Monitoramento de Uso pelos Servidores**

Um dos requisitos centrais do sistema é garantir que os servidores utilizem efetivamente as ferramentas - especialmente na Farmácia Central e no Hospital. Para isso, são implementadas as seguintes regras:

**3.2.1 Indicadores de Uso por Servidor**

- Número de dispensações registradas no sistema por turno e por servidor (Farmácia).
- Número de atendimentos registrados no sistema por plantão e por profissional (Hospital/UBS).
- Tempo médio entre chegada do paciente e primeiro registro digital.
- Percentual de prescrições emitidas digitalmente vs. prescrições físicas não registradas.
- Taxa de envio bem-sucedido de fichas ao PEC por unidade e por profissional.

**3.2.2 Alertas Automáticos**

- Se um servidor da farmácia fizer menos de X dispensações por turno (configurável), o sistema gera alerta para o gestor da unidade.
- Se houver intervalo superior a 4 horas sem nenhum registro em uma UBS com agenda ativa, o sistema notifica o gestor.
- Se um profissional de saúde não fizer login em turno em que está escalado (integração com escala, fase 2), o sistema registra ocorrência.

**3.2.3 Relatório de Conformidade de Uso**

O módulo M8 oferece relatório mensal consolidado com: nome do servidor, unidade, total de registros por tipo, percentual de cobertura digital vs. atendimentos realizados (extraído do PEC), e índice de conformidade. Este relatório é exportável em PDF e Excel para uso em reuniões de gestão.

# **4\. Especificação Funcional dos Módulos**

## **4.1 Módulo M1 - Portal Público do Cidadão**

O portal público é a face do sistema para a população. Acessível sem login em qualquer dispositivo, funciona como PWA (Progressive Web App) - podendo ser instalado no celular como aplicativo. O administrador SECTI gerencia o conteúdo através do M2.

### **4.1.1 Funcionalidades do Portal Público**

- Página inicial com banners editáveis, notícias de saúde e avisos de campanhas.
- Localização de UBSs e Hospital Municipal no mapa (integrado OpenStreetMap/Leaflet).
- Agenda de horários de funcionamento de cada unidade, atualizada pelo admin.
- Consulta de medicamentos disponíveis na Farmácia Central (lista REMUME simplificada).
- Informações sobre o Programa Remédio em Casa - como funciona, quem tem direito, como solicitar.
- Dashboard público de transparência: número de atendimentos do mês, entregas realizadas, CO₂ não emitido (integrado ao Gov.Assaí).
- Avisos de vacinação e campanhas de saúde.
- Canal de ouvidoria digital (formulário com protocolo de resposta em até 5 dias úteis).
- Acessibilidade: modo de alto contraste, tamanho de fonte ajustável, compatibilidade com leitores de tela (WCAG 2.1 AA).

### **4.1.2 Painel de Administração do Portal (M2)**

- Editor de conteúdo WYSIWYG para notícias, banners e avisos.
- Upload de arquivos (PDF de campanhas, boletins epidemiológicos).
- Gerenciamento de horários de funcionamento por unidade.
- Gestão de usuários do sistema (criar, editar perfil, redefinir senha, bloquear).
- Visualização de logs de auditoria de todos os módulos.
- Exportação de relatórios em PDF e Excel (atalho rápido para os principais indicadores do M8).

## **4.2 Módulo M3 - Recepção das UBSs**

A recepção digital substitui completamente o registro em papel ou planilhas na chegada do paciente às Unidades Básicas de Saúde. O processo é desenhado para ser rápido (meta: menos de 2 minutos para um paciente já cadastrado) e alimentar automaticamente o PEC.

### **4.2.1 Fluxo de Recepção**

- Recepcionista abre a tela de recepção no computador/tablet da UBS.
- Busca o paciente por CPF, CNS (Cartão Nacional de Saúde) ou nome completo + data nascimento.
- Sistema consulta o Gov.Assaí para validar residência e retorna status: RESIDENTE / NÃO RESIDENTE / PENDENTE.
- Para serviços eletivos, sistema exibe alerta se status for NÃO RESIDENTE (atendimento de urgência não é bloqueado).
- Se paciente não cadastrado, recepcionista preenche FichaCadastroIndividual e FichaCadastroDomiciliar conforme campos do LEDI APS.
- Sistema gera número de senha de fila digital com horário de chegada, tipo de atendimento (consulta, retorno, curativo, vacina etc.) e prioridade (normal / prioritário / urgência).
- Paciente é inserido na fila digital visível em painel de chamada (TV da sala de espera via websocket).
- Ficha de recepção é transmitida ao PEC via LEDI APS - FichaAtendimentoIndividual (campos de identificação e tipo de atendimento).

### **4.2.2 Campos da Tela de Recepção**

| **Campo**                      | **Origem / Validação**                                                              |
| ------------------------------ | ----------------------------------------------------------------------------------- |
| CPF do Paciente                | Digitado; valida formato e dígitos verificadores; busca Gov.Assaí                   |
| Cartão Nacional de Saúde (CNS) | Digitado ou recuperado do PEC - 15 dígitos, validado conforme algoritmo MS          |
| Nome Completo                  | Recuperado do cadastro ou digitado                                                  |
| Data de Nascimento             | Recuperado ou digitado - calcula idade automaticamente                              |
| Sexo / Gênero                  | Recuperado ou selecionado em lista (M/F/Outro)                                      |
| Endereço Atual                 | Recuperado do Gov.Assaí; pode ser atualizado                                        |
| Status de Residência           | Calculado pelo Gov.Assaí - RESIDENTE / NÃO RESIDENTE / PENDENTE                     |
| Tipo de Atendimento            | Seleção: Consulta Médica, Retorno, Enfermagem, Vacinação, Curativo, Urgência, Outro |
| Motivo Resumido                | Texto livre, máx. 200 caracteres                                                    |
| É Acidente de Trabalho?        | Checkbox com código SAT/NAT (campo obrigatório no LEDI APS)                         |
| Profissional Destino           | Seleção da agenda do dia, integrada ao PEC                                          |

## **4.3 Módulo M4 - Triagem nas UBSs**

A triagem é executada por enfermeiros ou técnicos de enfermagem. O módulo é a transição entre a chegada administrativa (M3) e o atendimento médico, capturando dados clínicos iniciais que estruturam a ficha de atendimento no PEC.

### **4.3.1 Formulário de Triagem - Lado Esquerdo (Dados do Paciente)**

| **Grupo**               | **Campo**                      | **Tipo / Detalhe**                              |
| ----------------------- | ------------------------------ | ----------------------------------------------- |
| Dados do Atendimento    | **Data e Hora**                | Auto-preenchidos; editáveis                     |
| Dados do Atendimento    | **Acidente de Trabalho**       | Checkbox (Sim/Não/NAT)                          |
| Sinais e Sintomas       | **Histórico de Enfermagem**    | Texto livre (SOAP - S: Subjetivo)               |
| Doenças Correlacionadas | **DM - Diabetes Mellitus**     | Checkbox                                        |
| Doenças Correlacionadas | **HAS - Hipertensão Arterial** | Checkbox                                        |
| Doenças Correlacionadas | **Cardiopatia**                | Checkbox                                        |
| Doenças Correlacionadas | **DPOC**                       | Checkbox                                        |
| Doenças Correlacionadas | **Alergias**                   | Checkbox + campo texto detalhamento             |
| Doenças Correlacionadas | **Cirurgias Anteriores**       | Checkbox + campo texto                          |
| Doenças Correlacionadas | **Outras comorbidades**        | Campo texto livre                               |
| Nível de Consciência    | **Lúcido e Orientado**         | Radio button                                    |
| Nível de Consciência    | **Sonolento**                  | Radio button                                    |
| Nível de Consciência    | **Confuso / Desorientado**     | Radio button                                    |
| Nível de Consciência    | **Inconsciente**               | Radio button - aciona alerta urgência           |
| Sinais Vitais           | **P.A. (Pressão Arterial)**    | mmHg - sistólica/diastólica; alerta se >180/120 |
| Sinais Vitais           | **Temperatura (T)**            | °C; alerta se >37,8 ou <35,0                    |
| Sinais Vitais           | **Frequência Cardíaca (FC)**   | bpm; alerta se &lt;50 ou &gt;120                |
| Sinais Vitais           | **Saturação de O₂ (SpO₂)**     | %; alerta se <94%                               |
| Sinais Vitais           | **HGT - Glicemia Capilar**     | mg/dL; alerta se &lt;70 ou &gt;300              |
| Sinais Vitais           | **Peso**                       | kg - usado no cálculo de doses e BMI            |

### **4.3.2 Classificação de Risco Automática**

Com base nos sinais vitais e nível de consciência, o sistema aplica a Escala de Manchester simplificada de forma automática:

| **Cor**         | **Classificação** | **Critérios Automáticos**                                                  |
| --------------- | ----------------- | -------------------------------------------------------------------------- |
| **🔴 Vermelho** | **Emergência**    | Inconsciente OU SpO₂ &lt;90% OU PA sistólica <80 OU FC &gt;150 OU HGT <50  |
| **🟠 Laranja**  | **Muito Urgente** | SpO₂ 90-93% OU PA sistólica &lt;90 OU FC 130-150 OU HGT 50-70 OU T&gt;39°C |
| **🟡 Amarelo**  | **Urgente**       | PA >180/120 OU FC >120 OU T >38°C OU nível confuso                         |
| **🟢 Verde**    | **Pouco Urgente** | Sinais vitais normais, lúcido, queixa não urgente                          |
| **🔵 Azul**     | **Não Urgente**   | Sem sinais de alerta, consulta eletiva de retorno                          |

Ao concluir a triagem, a FichaAtendimentoIndividual é completada e enviada ao PEC com todos os dados de sinais vitais e classificação de risco. O paciente avança automaticamente na fila digital com sua cor de prioridade.

## **4.4 Módulo M5 - Remédio em Casa (Prescrição Digital e Logística)**

O módulo Remédio em Casa é a digitalização completa do programa já em operação em Assaí. Ele conecta médico → farmácia → entregador → paciente, com rastreio em tempo real de cada etapa.

### **4.4.1 Fluxo Completo do Remédio em Casa**

- Médico acessa o prontuário do paciente e emite prescrição digital - seleciona medicamento da REMUME, dosagem, frequência, duração e observações.
- Sistema verifica automaticamente se o paciente tem status RESIDENTE no Gov.Assaí (requisito para entrega domiciliar).
- Prescrição é assinada digitalmente pelo médico (ICP-Brasil) e gera FichaProcedimento no PEC.
- Farmácia Central recebe notificação em tempo real - prescrição aparece na fila de separação com prioridade calculada.
- Farmacêutico/auxiliar confirma separação item a item, registra lote e validade de cada medicamento dispensado.
- Sistema gera ordem de entrega com endereço georreferenciado (Gov.Assaí) e atribui ao entregador disponível.
- Entregador recebe no app mobile: nome do paciente, endereço no mapa, medicamentos e observações especiais.
- Ao entregar, entregador coleta assinatura digital do paciente/responsável no app e registra foto da fachada (opcional, para auditoria).
- Sistema notifica o médico e o paciente (WhatsApp) sobre a entrega realizada.
- Dados de entrega (horário, assinatura, GPS) são gravados e ligados ao prontuário do paciente.

### **4.4.2 Tela de Prescrição Digital**

| **Campo**                   | **Detalhe**                                                  |
| --------------------------- | ------------------------------------------------------------ |
| Paciente                    | Nome + CPF + CNS - auto-carregado do prontuário              |
| Unidade / Médico            | Auto-preenchidos - CRM registrado no sistema                 |
| Data da Prescrição          | Auto - timestamp do servidor                                 |
| Medicamento                 | Busca na REMUME municipal (código + nome DCI)                |
| Apresentação / Concentração | Seleção da forma disponível no estoque                       |
| Posologia                   | Dose, frequência (6/6h, 8/8h, 12/12h, 24/24h, SOS)           |
| Duração do Tratamento       | Número de dias; calcula quantidade total automaticamente     |
| Via de Administração        | VO, IV, IM, SC, Tópica, Inalatória, etc.                     |
| Observações Especiais       | Texto livre - alergias, cuidados, orientações                |
| Tipo de Entrega             | Retirada na farmácia / Entrega domiciliar (Remédio em Casa)  |
| Assinatura Digital          | Botão que aciona certificado ICP-Brasil do médico            |
| Envio ao PEC                | Automático após assinatura - FichaProcedimento + RAC parcial |

### **4.4.3 App do Entregador**

Aplicativo mobile (React Native / PWA) com as seguintes funcionalidades:

- Lista de entregas do dia com status: PENDENTE / EM ROTA / ENTREGUE / FALHA.
- Mapa com rota otimizada (múltiplas entregas - algoritmo TSP básico).
- Detalhe da entrega: nome do paciente, endereço, medicamentos, observações.
- Botão de confirmação com coleta de assinatura digital na tela.
- Registro automático de GPS no momento da confirmação.
- Botão de falha na entrega com motivo selecionável (endereço não encontrado, sem morador, recusou).
- Histórico do dia com total de entregas e km percorridos (indicador para relatório de CO₂ não emitido).

## **4.5 Módulo M6 - Farmácia Central**

A Farmácia Central é o ponto físico onde munícipes retiram medicamentos com prescrição. O módulo garante controle de estoque, validação de residência e - criticamente - rastreabilidade de quem fez cada dispensação e quando.

### **4.5.1 Validação de Residência na Farmácia**

Todo atendimento na Farmácia Central obrigatoriamente passa pela validação de residência:

- Servidor solicita CPF do cidadão.
- Sistema consulta Gov.Assaí em tempo real.
- Se RESIDENTE → prossegue com a dispensação.
- Se NÃO RESIDENTE → sistema bloqueia dispensação eletiva e exibe mensagem padronizada. Caso emergencial pode ser liberado com justificativa registrada.
- Se PENDENTE (cadastro incompleto) → servidor pode acionar atualização cadastral integrada.

### **4.5.2 Tela de Dispensação**

- Busca da prescrição por CPF do paciente, número da prescrição ou QR Code impresso.
- Exibição da prescrição com dados do médico, data, medicamentos, dosagem e se é para retirada ou entrega domiciliar.
- Confirmação item a item com registro de lote e validade.
- Alerta automático se medicamento próximo do vencimento (< 30 dias).
- Alerta de estoque crítico (< 20 unidades) com notificação automática ao gestor.
- Registro automático de deducão do estoque.
- Geração de comprovante de dispensação em PDF (pode ser enviado por WhatsApp).
- Envio de FichaProcedimento ao PEC com código de dispensação.

### **4.5.3 Monitoramento de Uso pelos Servidores da Farmácia**

Este é um requisito explícito do sistema. As seguintes métricas são capturadas por servidor:

| **Métrica**                              | **Como é Coletada**                                | **Uso no Relatório M8**              |
| ---------------------------------------- | -------------------------------------------------- | ------------------------------------ |
| **Dispensações por turno**               | Contagem de registros com user_id + timestamp      | Gráfico de barras por servidor/turno |
| **Tempo médio por dispensação**          | (hora fim - hora início) de cada atendimento       | Identifica gargalos operacionais     |
| **Taxa de validação de residência**      | % de atendimentos com consulta Gov.Assaí realizada | Alerta se taxa < 100%                |
| **Recusas registradas (não residentes)** | Contagem de atendimentos negados com justificativa | Relatório de economia fiscal         |
| **Erros de dispensação registrados**     | Campo de ocorrência preenchido pelo servidor       | Indicador de qualidade               |
| **Horário de primeiro e último login**   | Coluna first_login / last_logout por turno         | Conformidade de jornada              |

## **4.6 Módulo M7 - Hospital Municipal (Prontuário Eletrônico)**

O módulo M7 substitui integralmente o formulário de papel utilizado hoje no Hospital Municipal. A ficha eletrônica foi desenhada para mapear fielmente todos os campos do papel descrito, adicionando validações, alertas e envio automático ao PEC.

### **4.6.1 Tela do Prontuário Eletrônico - Estrutura Espelhada no Papel Atual**

A interface é dividida em dois painéis laterais, replicando a lógica do formulário físico:

**PAINEL ESQUERDO - TRIAGEM E HISTÓRICO DE ENFERMAGEM**

| **Seção**               | **Campo**                              | **Tipo / Regra de Negócio**                                       |
| ----------------------- | -------------------------------------- | ----------------------------------------------------------------- |
| Dados do Atendimento    | **Data**                               | Auto - data do servidor (dd/mm/aaaa)                              |
| Dados do Atendimento    | **Hora de Chegada**                    | Auto - timestamp; editável pelo enfermeiro                        |
| Dados do Atendimento    | **Acidente de Trabalho**               | Enum: Sim / Não / NAT (campo obrigatório LEDI APS)                |
| Histórico Enfermagem    | **Descrição dos sintomas e histórico** | Texto livre - min. 20 caracteres para finalizar triagem           |
| Doenças Correlacionadas | **DM (Diabetes)**                      | Checkbox - persistido no perfil do paciente                       |
| Doenças Correlacionadas | **HAS (Hipertensão)**                  | Checkbox - persistido no perfil do paciente                       |
| Doenças Correlacionadas | **Cardiopatias**                       | Checkbox                                                          |
| Doenças Correlacionadas | **DPOC**                               | Checkbox                                                          |
| Doenças Correlacionadas | **Alergias**                           | Checkbox + campo texto com lista CID-10 de alergias               |
| Doenças Correlacionadas | **Cirurgias Anteriores**               | Checkbox + campo texto livre                                      |
| Doenças Correlacionadas | **Outras comorbidades**                | Campo texto livre                                                 |
| Nível de Consciência    | **Lúcido/Orientado**                   | Radio - padrão inicial                                            |
| Nível de Consciência    | **Sonolento**                          | Radio                                                             |
| Nível de Consciência    | **Confuso**                            | Radio - aciona alerta amarelo                                     |
| Nível de Consciência    | **Inconsciente**                       | Radio - aciona alerta vermelho imediato e notificação             |
| Sinais Vitais           | **P.A. (mmHg)**                        | Dois campos numéricos (sistólica/diastólica) - alerta se >180/120 |
| Sinais Vitais           | **Temperatura (°C)**                   | Campo numérico - alerta se >37,8                                  |
| Sinais Vitais           | **FC (bpm)**                           | Campo numérico - alerta se &lt;50 ou &gt;120                      |
| Sinais Vitais           | **SpO₂ (%)**                           | Campo numérico - alerta crítico se <94%                           |
| Sinais Vitais           | **HGT (mg/dL)**                        | Campo numérico - alerta se &lt;70 ou &gt;300                      |
| Sinais Vitais           | **Peso (kg)**                          | Campo numérico - usado em cálculo de dose                         |

**PAINEL DIREITO - AVALIAÇÃO MÉDICA**

| **Seção**              | **Campo**                               | **Tipo / Regra de Negócio**                                                                             |
| ---------------------- | --------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| Motivo do Atendimento  | **Exame Clínico / SOAP - Objetivo (O)** | Texto estruturado ou livre - obrigatório para finalizar                                                 |
| Motivo do Atendimento  | **Avaliação Clínica (A) - SOAP**        | Campo texto com área expandível                                                                         |
| Diagnóstico            | **Descrição do Diagnóstico**            | Texto livre                                                                                             |
| Diagnóstico            | **Código CID-10**                       | Busca por texto ou código - autocomplete - campo obrigatório para encerrar atendimento                  |
| Diagnóstico            | **CIDs Secundários**                    | Campo multi-seleção, até 5 CIDs adicionais                                                              |
| Procedimentos e Exames | **Procedimentos Realizados**            | Campo multi-seleção (SIGTAP) + texto livre                                                              |
| Procedimentos e Exames | **Exames Solicitados**                  | Campo multi-seleção + texto livre; gera ordem de exame impressa                                         |
| Prescrição Médica      | **Medicamentos**                        | Igual ao M5: busca REMUME, dose, frequência, duração                                                    |
| Prescrição Médica      | **Orientações ao Paciente**             | Texto livre - aparece impresso na via do paciente                                                       |
| Prescrição Médica      | **Tipo de saída**                       | Enum: Alta / Internação / Transferência / Óbito                                                         |
| Rodapé - Assinaturas   | **Assinatura Digital do Médico**        | Botão ICP-Brasil - obrigatório para encerrar e enviar ao PEC                                            |
| Rodapé - Assinaturas   | **Assinatura do Paciente/Responsável**  | Coletar via tablet touchscreen ou campo de aceite eletrônico                                            |
| Rodapé - Assinaturas   | **Impressão Digital (Polegar)**         | Campo para integração com leitor biométrico externo (fase 2); na fase 1, checkbox de confirmação verbal |
| Rodapé - Assinaturas   | **CRM / Carimbo Médico**                | Auto-preenchido do perfil do profissional logado                                                        |

### **4.6.2 Envio ao PEC após Atendimento Hospitalar**

Ao encerrar o atendimento (tipo de saída selecionado + assinatura digital do médico), o sistema:

- Monta a FichaAtendimentoIndividual com todos os campos preenchidos.
- Se houver prescrição, gera também FichaProcedimento com código SIGTAP correspondente.
- Se saída = Alta, gera Resumo de Alta Clínica (RAC) com CID principal, diagnóstico e orientações.
- Envia pacote ao PEC via LEDI APS de forma assíncrona - resultado aparece no painel M8.
- PDF do prontuário é gerado e armazenado no MinIO com referência no banco de dados.
- Notificação WhatsApp pode ser enviada ao paciente com orientações de alta.

### **4.6.3 Barra Lateral de Histórico do Paciente**

Durante o atendimento, o médico tem acesso a uma barra lateral retrátil com:

- Últimos 10 atendimentos (data, unidade, CID, médico).
- Prescrições ativas (medicamentos em uso atual com posologia).
- Alergias registradas (destacadas em vermelho no topo).
- Exames recentes (disponíveis no DW PEC).
- Comorbidades crônicas (DM, HAS, etc.) marcadas nos atendimentos anteriores.

## **4.7 Módulo M8 - Painel Gestor e Auditoria**

O M8 é o centro de inteligência do sistema. Acessível para Administradores SECTI, Gestores de Saúde e Auditores, consolida dados de todos os módulos e do DW PEC.

### **4.7.1 Dashboard Principal - KPIs em Tempo Real**

| **Indicador**                        | **Fonte dos Dados**        | **Frequência de Atualização** |
| ------------------------------------ | -------------------------- | ----------------------------- |
| Atendimentos do dia por unidade      | M3, M4, M7                 | Tempo real (WebSocket)        |
| Pacientes em fila por UBS            | M3                         | Tempo real                    |
| Entregas do Remédio em Casa          | M5                         | Tempo real                    |
| Taxa de uso do sistema por servidor  | audit_log                  | Atualizado a cada 15 min      |
| Fichas enviadas ao PEC / pendentes   | LEDI APS queue             | Tempo real                    |
| Estoque crítico - Farmácia Central   | M6                         | Tempo real                    |
| CIDs mais frequentes do mês          | M7 + DW PEC                | Diário (ETL 0h)               |
| CO₂ não emitido (frota elétrica)     | M5 - km percorridos        | Diário                        |
| Dispensações por servidor (farmácia) | M6 + audit_log             | Atualizado a cada hora        |
| NPS - Satisfação dos usuários        | Formulário pós-atendimento | Semanal                       |

### **4.7.2 Relatório de Conformidade de Uso por Servidor**

Este relatório é o mecanismo central de monitoramento exigido pelo sistema. É gerado mensalmente de forma automática e pode ser solicitado a qualquer momento pelo gestor. Contém:

- Nome completo do servidor, matrícula, unidade de lotação e cargo.
- Total de registros por módulo (dispensações, triagens, atendimentos, prescrições).
- Dias trabalhados com registro no sistema vs. dias com escala (fase 2: integração com RH).
- Índice de conformidade digital = registros no sistema / atendimentos esperados × 100%.
- Fichas com erro ou rejeição pelo PEC de responsabilidade do servidor.
- Alertas de dias sem nenhum registro em turno ativo.
- Comparativo com a média da unidade e com o mês anterior.

O relatório é exportável em PDF (com assinatura eletrônica do gestor para fins de RH) e em Excel. Pode ser enviado automaticamente por e-mail ao gestor da unidade todo dia 1º do mês seguinte.

### **4.7.3 Auditoria de Ações**

Toda ação relevante no sistema é imutavelmente registrada na tabela audit_log. O módulo M8 oferece interface de busca e filtro por: servidor, módulo, tipo de ação, período, IP de origem. Ações auditadas incluem:

- Login / Logout / Tentativa de acesso negada.
- Criação, edição e exclusão de registros de pacientes.
- Dispensação de medicamentos (com medicamento, lote, paciente).
- Emissão e cancelamento de prescrições.
- Envio e reenvio de fichas ao PEC.
- Alterações de configuração do sistema.
- Exportação de relatórios com dados pessoais.
- Tentativas de acesso a dados de outro paciente sem permissão.

# **5\. Modelo de Dados Principal**

## **5.1 Entidades Centrais do Banco de Dados**

O banco de dados PostgreSQL do Saúde Assaí é separado do banco do PEC e se comunica com ele via LEDI APS (escrita) e DW PEC (leitura). As principais tabelas são:

### **5.1.1 Tabela: cidadao**

| **Coluna**                 | **Tipo**          | **Descrição**                                                 |
| -------------------------- | ----------------- | ------------------------------------------------------------- |
| **id**                     | UUID              | Chave primária - gerada internamente                          |
| **cpf**                    | VARCHAR(11)       | CPF sem pontuação - único; criptografado em repouso (AES-256) |
| **cns**                    | VARCHAR(15)       | Cartão Nacional de Saúde - validado pelo algoritmo MS         |
| **nome_completo**          | VARCHAR(200)      | Nome civil completo                                           |
| **nome_social**            | VARCHAR(200) NULL | Nome social (LGPD e CFM)                                      |
| **data_nascimento**        | DATE              | Data de nascimento                                            |
| **sexo**                   | CHAR(1)           | M/F/I (indeterminado)                                         |
| **genero**                 | VARCHAR(50) NULL  | Identidade de gênero - campo opcional                         |
| **raca_cor**               | SMALLINT          | Conforme tabela IBGE - código numérico                        |
| **gov_assai_id**           | UUID NULL         | ID de referência no Gov.Assaí                                 |
| **is_residente_assai**     | BOOLEAN           | Calculado pela última consulta ao Gov.Assaí                   |
| **residencia_validada_em** | TIMESTAMPTZ NULL  | Timestamp da última validação de residência                   |
| **telefone**               | VARCHAR(20) NULL  | WhatsApp preferencial                                         |
| **email**                  | VARCHAR(200) NULL | E-mail (criptografado)                                        |
| **created_at**             | TIMESTAMPTZ       | Timestamp de criação                                          |
| **updated_at**             | TIMESTAMPTZ       | Timestamp de última atualização                               |
| **deleted_at**             | TIMESTAMPTZ NULL  | Soft delete (LGPD - direito ao esquecimento controlado)       |

### **5.1.2 Principais Tabelas do Sistema**

| **Tabela**            | **Propósito**                                                     |
| --------------------- | ----------------------------------------------------------------- |
| cidadao               | Cadastro unificado do cidadão (ver acima)                         |
| servidor              | Perfis de usuários servidores com perfil RBAC                     |
| unidade_saude         | Cadastro das UBSs, Hospital e Farmácia Central                    |
| atendimento           | Registro de cada atendimento - qualquer módulo                    |
| triagem               | Dados de triagem (sinais vitais, comorbidades, nível consciência) |
| prescricao            | Prescrições emitidas (cabeçalho)                                  |
| prescricao_item       | Itens de cada prescrição (medicamento, dose, frequência)          |
| dispensacao           | Registro de dispensação pela farmácia                             |
| dispensacao_item      | Itens dispensados com lote e validade                             |
| entrega               | Controle de entregas do Remédio em Casa                           |
| estoque_medicamento   | Posição de estoque por medicamento/lote/unidade                   |
| medicamento           | Catálogo REMUME municipal com código e apresentações              |
| prontuario_hospitalar | Prontuário do Hospital - referencia atendimento + laudos          |
| ledi_queue            | Fila de fichas pendentes de envio ao PEC                          |
| ledi_log              | Histórico de envios com status e resposta do PEC                  |
| audit_log             | Log imutável de todas as ações dos servidores                     |
| conteudo_portal       | Textos, imagens e banners do portal público                       |
| notificacao           | Histórico de mensagens WhatsApp enviadas                          |
| alerta_sinais_vitais  | Alertas gerados automaticamente por parâmetros fora do limite     |

# **6\. Conformidade Legal e Segurança**

## **6.1 LGPD - Lei 13.709/2018**

O Saúde Assaí trata dados pessoais e dados de saúde (dados sensíveis, Art. 11 da LGPD). O sistema implementa as seguintes salvaguardas:

### **6.1.1 Bases Legais Utilizadas**

- Art. 7º, III - execução de políticas públicas: embasamento para todos os módulos de atendimento SUS.
- Art. 11, II, b - tutela da saúde em procedimento médico: base para prontuário e prescrições.
- Art. 7º, VI - legítimo interesse: para o programa Remédio em Casa e validação de residência.

### **6.1.2 Medidas Técnicas**

- Criptografia AES-256 em repouso para CPF, CNS, e-mail e dados de saúde.
- TLS 1.3 em todas as comunicações entre cliente e servidor.
- Pseudonimização nos logs de auditoria exportados para terceiros.
- Soft delete com campo deleted_at - dados não são destruídos imediatamente, mas ficam inacessíveis.
- Solicitação de exclusão de dados (Art. 18, VI) - processo documentado no M2, com prazo de 15 dias úteis.
- Relatório de Impacto à Proteção de Dados (RIPD) - a ser elaborado antes do go-live.

## **6.2 Segurança da Informação**

- Autenticação com JWT RS256 + tempo de expiração de 8 horas (renovável com refresh token).
- 2FA obrigatório para perfis médico, farmacêutico, administrador e gestor.
- Controle de sessão simultânea: máximo 2 sessões ativas por servidor.
- Bloqueio automático após 5 tentativas de login inválidas - desbloqueio apenas pelo admin.
- Backups diários automáticos do PostgreSQL com retenção de 90 dias.
- Ambiente de produção e homologação separados - dados de produção nunca vão para ambiente de teste.

## **6.3 Conformidade com o CFM e Regulamentações de Saúde**

- Prescrição digital com assinatura ICP-Brasil - conforme Resolução CFM 2.299/2021.
- Prontuário eletrônico com auditoria completa - conforme Resolução CFM 1.821/2007.
- Notificação compulsória de doenças - fluxo integrado ao SINAN via módulo futuro.
- REMUME alinhada à RENAME vigente - atualizável pelo farmacêutico responsável.

# **7\. Plano de Implantação**

## **7.1 Fases de Implementação**

| **Fase**   | **Prazo Estimado** | **Módulos / Entregas**                                                                                | **Critério de Aceite**                          |
| ---------- | ------------------ | ----------------------------------------------------------------------------------------------------- | ----------------------------------------------- |
| **Fase 0** | Meses 1-2          | Infraestrutura, banco de dados, autenticação, API Gateway, integração Gov.Assaí (consulta residência) | Ambiente up, 100% testes unitários passando     |
| **Fase 1** | Meses 3-4          | M1 Portal Público + M2 Admin + M3 Recepção UBS                                                        | Portal ao ar, 1 UBS piloto em produção          |
| **Fase 2** | Meses 4-5          | M4 Triagem UBS + integração LEDI APS para FichaAtendimentoIndividual                                  | Fichas chegando ao PEC sem rejeição             |
| **Fase 3** | Meses 5-6          | M5 Remédio em Casa + App Entregador + M6 Farmácia Central                                             | Entregas rastreadas ponta a ponta               |
| **Fase 4** | Meses 7-8          | M7 Hospital Municipal - prontuário eletrônico completo                                                | Papel eliminado no Hospital, RAC enviada ao PEC |
| **Fase 5** | Meses 9-10         | M8 Painel Gestor, auditoria, relatório de conformidade de uso                                         | Gestor visualiza dados de todos os módulos      |
| **Fase 6** | Mês 11+            | Leitor biométrico (M7), integração escala RH, app paciente, notificações avançadas                    | A definir com gestão                            |

## **7.2 Capacitação dos Servidores**

A implantação de cada módulo é precedida por treinamento obrigatório. O plano de capacitação prevê:

- Treinamento presencial de 4h para recepcionistas e técnicos (M3, M4, M6).
- Treinamento presencial de 6h para médicos e enfermeiros (M4, M5, M7).
- Treinamento online com vídeos e quiz para todos os perfis - disponível no portal interno.
- Manual de uso por perfil em formato PDF acessível pelo sistema.
- Suporte técnico remoto via canal Slack/Teams da SECTI nos primeiros 60 dias pós-implantação.
- Índice de conformidade de uso como indicador de sucesso da capacitação: meta 80% no primeiro mês, 95% no terceiro.

# **8\. Glossário de Termos Técnicos**

| **Termo**  | **Definição**                                                                                    |
| ---------- | ------------------------------------------------------------------------------------------------ |
| LEDI APS   | Layout e-SUS APS de Dados e Interface - especificação de integração com o PEC e-SUS.             |
| PEC        | Prontuário Eletrônico do Cidadão - sistema federal do e-SUS instalado no município.              |
| DW PEC     | Data Warehouse do e-SUS PEC - estrutura multidimensional para relatórios.                        |
| Gov.Assaí  | Sistema único municipal de Assaí - integra dados de todos os serviços da prefeitura.             |
| REMUME     | Relação Municipal de Medicamentos Essenciais - lista de medicamentos disponíveis no município.   |
| CNS        | Cartão Nacional de Saúde - identificador único do cidadão no SUS.                                |
| CID-10     | Classificação Internacional de Doenças, 10ª revisão - padrão de codificação diagnóstica.         |
| SIGTAP     | Sistema de Gerenciamento da Tabela de Procedimentos - tabela de procedimentos do SUS.            |
| ICP-Brasil | Infraestrutura de Chaves Públicas Brasileira - padrão de certificado digital com validade legal. |
| SOAP       | Subjetivo, Objetivo, Avaliação, Plano - metodologia de registro clínico estruturado.             |
| RBAC       | Role-Based Access Control - controle de acesso baseado em perfis e papéis.                       |
| RAC        | Resumo de Alta Clínica - documento LEDI APS com dados do encerramento do atendimento.            |
| JWT        | JSON Web Token - padrão de autenticação stateless utilizado nas APIs.                            |
| ETL        | Extract, Transform, Load - processo de extração e carga de dados para o DW.                      |
| PWA        | Progressive Web App - aplicação web que funciona como app nativo no celular.                     |
| LGPD       | Lei Geral de Proteção de Dados (Lei 13.709/2018) - legislação brasileira de privacidade.         |
| ACS        | Agente Comunitário de Saúde - profissional da ESF que realiza visitas domiciliares.              |
| ESF        | Estratégia Saúde da Família - modelo de atenção primária com equipes por território.             |
| HGT        | Hemoglicoteste - medição de glicemia capilar no atendimento.                                     |
| SpO₂       | Saturação periférica de oxigênio - medida pelo oxímetro de pulso.                                |

# **9\. Próximos Passos Recomendados**

Com base nesta especificação, recomenda-se a seguinte sequência de ações para viabilizar o desenvolvimento do Saúde Assaí:

- Validação desta especificação com a Secretaria Municipal de Saúde, equipes das UBSs, Hospital Municipal e Farmácia Central - levantamento de ajustes e prioridades.
- Elaboração do Processo Licitatório ou instrumento adequado (Chamamento Público, Cotação Eletrônica) para contratação de empresa de desenvolvimento.
- Mapeamento detalhado do banco PostgreSQL do PEC instalado no município (versão atual, estrutura do DW, credenciais de acesso ao schema dw\_\*).
- Teste de conexão com o LEDI APS: enviar uma FichaAtendimentoIndividual de teste para o PEC usando o endpoint Thrift e validar recebimento.
- Elaboração do RIPD (Relatório de Impacto à Proteção de Dados) antes do go-live - exigência da LGPD para sistemas de saúde pública.
- Definição da infraestrutura de hospedagem: servidor municipal existente, data center regional ou nuvem pública com cláusula de soberania de dados.
- Criação de ambiente de homologação espelhando os sistemas Gov.Assaí e PEC para testes de integração.
- Capacitação inicial da equipe técnica da SECTI para gestão do sistema após implantação.

Este documento é propriedade da Divisão de Ciência, Tecnologia e Inovação (SECTI) da Prefeitura Municipal de Assaí - PR.

**Versão 1.0 - Março de 2026 - Saúde Assaí - Sistema Integrado de Gestão em Saúde Municipal**