# Guia Passo a Passo: Painel de Controle de Borda da Farmácia
*(Escrito em linguagem simples e sem termos técnicos para o Administrador da Saúde)*

Bem-vindo(a) ao seu novo **Painel de Controle de Borda** do Saúde Assaí! 

Este manual foi feito para ajudar você a controlar e auditar a entrega de medicamentos no município de forma simples e rápida. 

---

## 🌟 1. O que é o "Controle de Borda"?

Para que um cidadão retire medicamentos na Farmácia Central, a lei exige que ele tenha o cadastro regularizado (conta do **Gov.Assaí no Nível 2** ou validação do agente de saúde **ACS**).

Porém, as entregas de medicamentos são registradas em um sistema externo chamado **Betha**. O **Controle de Borda** é a ferramenta que cruza os dados desse sistema da Betha com o nosso sistema Saúde Assaí para descobrir:
1. **Quem pegou remédio corretamente** (dentro das regras).
2. **Quem conseguiu retirar remédio sem ter o cadastro obrigatório** (o que chamamos de **Bypass** ou "Desvio").

---

## 📍 2. Como Entrar no Painel?

1. Abra o sistema Saúde Assaí no seu computador.
2. No menu que fica no lado esquerdo da tela, clique em **"Admin - Controle de Borda"** (ele tem o ícone de um globo terrestre 🌐).
3. Pronto! Você já está na tela principal de auditoria.

---

## 📊 3. Entendendo a Tela (Os Painéis Coloridos)

Logo no topo da tela, você verá cinco cartões coloridos que mostram o resumo do período selecionado:

*   **Total Analisado (Cinza):** É a quantidade total de remédios entregues no período.
*   **Fluxo Regular (Verde):** A quantidade de entregas que foram feitas corretamente para pessoas com cadastro em dia.
*   **Bypass Detectado (Vermelho):** **Atenção!** É a quantidade de entregas feitas para pessoas que **não** tinham o cadastro obrigatório.
*   **Cidadãos Bloqueados (Azul):** Quantos cidadãos tiveram a retirada de medicamentos travada no período.
*   **Conformidade de Borda (Barra Colorida):** É a nota de saúde da farmácia. 
    *   **Verde (Acima de 90%):** Excelente! Quase todo mundo está retirando com cadastro regular.
    *   **Amarela (Entre 75% e 89%):** Alerta! Muitas pessoas estão retirando sem cadastro.
    *   **Vermelha (Abaixo de 75%):** Perigo! Há um alto número de desvios que precisam ser corrigidos.

---

## 🔍 4. Como Usar os Filtros para Investigar (Busca Inteligente)

Você pode personalizar a sua busca usando os campos de filtro. Depois de preencher, basta clicar no botão verde **"Filtrar"**:

1.  **Data Início e Data Fim:** Escolha o período que você quer analisar (ex: do dia 01 ao dia 15 do mês).
2.  **Buscar Cidadão:** Digite o **Nome** ou o **CPF** de um paciente para ver o histórico dele.
3.  **Buscar Medicamento:** Digite o nome de um remédio (ex: *Dipirona*) para ver quem o retirou.
4.  **Caixinha "Apenas Bypass":** Se marcar esta caixinha, a tela esconderá as entregas corretas e mostrará **somente as pessoas que retiraram remédio de forma irregular**.
5.  **Caixinha "Apenas Alto Custo 💎":** Se marcar esta caixinha, a tela mostrará **apenas os medicamentos caros e controlados pela DIRES** (como Insulinas, Enoxaparina, Quetiapina, etc.), facilitando a fiscalização de gastos elevados.

*Para limpar tudo e voltar ao início, basta clicar no botão branco **"Limpar"**.*

---

## 🔒 5. O Botão Mais Importante: Bloquear e Desbloquear

Na tabela de registros, você verá uma coluna com botões de cadeado para cada paciente. Esse botão serve para travar ou liberar o paciente na farmácia com **apenas 1 clique**:

*   **Cadeado Vermelho (`Bloqueado 🔒`):** Significa que este paciente **não pode** retirar nenhum medicamento na farmácia. O sistema de entrega travará o nome dele.
*   **Cadeado Verde (`Desbloqueado 🔓`):** Significa que o paciente está **liberado** para retirar medicamentos.

### Como usar isso no dia a dia?
1.  **Bloqueio Automático:** Se o sistema importar dados da Betha e descobrir que alguém sem cadastro pegou remédio, o sistema **bloqueará essa pessoa automaticamente** (o botão ficará vermelho).
2.  **Como Liberar o Paciente:** Se o paciente for até a Secretaria de Saúde, atualizar o cadastro dele no **Gov.Assaí (Nível 2)** ou for aprovado pelo agente de saúde (ACS), você pode ir no painel, buscar o nome dele e clicar no botão vermelho escrito `Bloqueado 🔒`. Ele mudará na hora para `Desbloqueado 🔓` e o paciente poderá voltar a retirar seus medicamentos imediatamente!

---

## 📈 6. A Evolução Diária (Lista da Esquerda)

No lado esquerdo, há uma lista organizada por datas. Ela mostra dia a dia como foi o movimento:
*   Quantos remédios foram entregues naquele dia.
*   Quantos foram desvios (Bypass).
*   Uma nota em porcentagem daquele dia específico. 
*   *Dica:* Dias com notas vermelhas ou amarelas merecem uma fiscalização mais de perto para ver quais remédios foram entregues sem cadastro.

---

## 📥 7. Como Baixar Relatórios para o Computador (Excel)

Se você precisar enviar os dados para a diretoria ou abrir os registros em uma planilha no computador:
1.  Ajuste os filtros da tela como você preferir (ex: marque *Apenas Alto Custo*).
2.  No topo direito da tela, clique no botão cinza **"Exportar Relatório (CSV)"**.
3.  O sistema baixará um arquivo automaticamente para o seu computador.
4.  Ao abrir esse arquivo no Excel ou LibreOffice, você terá uma planilha completa e organizada com nomes, remédios, datas e os CPFs dos pacientes prontos para serem analisados ou impressos!

---

## 💡 Exemplos Práticos de Uso

### Caso A: "Quero ver se entregaram remédio caro para pessoas sem cadastro hoje"
1. Defina a data de hoje nos filtros de data.
2. Marque a caixinha **"Apenas Bypass"**.
3. Marque a caixinha **"Apenas Alto Custo 💎"**.
4. Clique em **"Filtrar"**. 
*O painel mostrará apenas os desvios de medicamentos de alto custo ocorridos no dia!*

### Caso B: "Um paciente reclama que não consegue pegar remédio na farmácia pois está bloqueado. Ele já regularizou o cadastro. Como resolvo?"
1. No painel, digite o CPF ou o nome do paciente no campo **"Buscar Cidadão"** e clique em **"Filtrar"**.
2. O nome dele aparecerá na tabela com o botão vermelho escrito `Bloqueado 🔒`.
3. Clique uma vez em cima do botão `Bloqueado 🔒`. Ele se tornará verde escrito `Desbloqueado 🔓`.
*Pronto! O paciente já pode ir até o balcão da Farmácia Central retirar o medicamento.*
