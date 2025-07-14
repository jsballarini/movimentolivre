# 🧠 TECHNICAL_OVERVIEW.md - Movimento Livre

Este documento técnico descreve a arquitetura do plugin **Movimento Livre**, desenvolvido em WordPress, com integração ao WooCommerce, para gerenciar o sistema de **empréstimos solidários de cadeiras de rodas**.

---

## 🔧 Estrutura Técnica Geral

- Baseado no WooCommerce e WordPress (sem alterar núcleo)
- Utiliza os **Pedidos** como Empréstimos
- Utiliza os **Produtos** como Cadeiras físicas
- Implementa lógica de status personalizados para **Pedidos** e **Produtos**
- Gera **formulários em PDF** com campos dinâmicos
- Armazena histórico de ações (avaliações, manutenções)

---

## 📦 Custom Post Types e Custom Fields

### CPTs Utilizados
- Não é necessário criar novos CPTs — utiliza `shop_order` e `product`

### Campos Personalizados (Pedidos / Produtos)
- **Pedido (Empréstimo):**
  - `cpf_solicitante`
  - `formulario_emprestimo_pdf`
  - `formulario_devolucao_pdf`
  - `data_prevista_devolucao`
  - `status_personalizado` (Aguardando, Emprestado, Devolvido)

- **Produto (Cadeira):**
  - `tag_sku`
  - `status_cadeira` (Pronta, Emprestado, Em Avaliacao, Em Manutencao)
  - `formulario_avaliacao[]` (array de formulários com nome, data, resultado, observações)

---

## 🔁 Status Personalizados

### Pedidos (WooCommerce Orders)
- `wc-on-hold` → **Aguardando**
- `wc-processing` → **Emprestado**
- `wc-completed` → **Devolvido**

### Produtos (Cadeiras)
- `pronta`
- `emprestado`
- `em_avaliacao`
- `em_manutencao`

Esses status serão armazenados como **meta fields**, pois o WooCommerce não possui nativamente status de produto personalizados.

---

## 🔄 Fluxo Automatizado

### Criação do Pedido
- Usuário faz o pedido
- Status inicial: `Aguardando`
- Bloqueio de pedidos adicionais se CPF tiver 2 empréstimos ativos

### Após envio do Formulário de Empréstimo
- Status do pedido → `Emprestado`
- Status do produto → `Emprestado`
- Estoque reduzido automaticamente

### Após envio do Formulário de Devolução
- Status do pedido → `Devolvido`
- Status do produto → `Em Avaliação`
- Estoque **não é alterado ainda**
- Geração de formulário de avaliação técnica

### Após Avaliação Interna
- Se **Aprovada**:
  - Produto → `Pronta`
  - Produto volta ao estoque
- Se **Reprovada**:
  - Produto → `Em Manutenção`
  - Gera novo formulário de avaliação

### Após nova avaliação técnica
- Se **Aprovada**: Produto → `Pronta` → retorna ao estoque
- Se **Reprovada novamente**: permanece como `Em Manutenção`

---

## 🧠 Lógica de Disponibilidade para Empréstimo

Um produto só pode ser adicionado ao carrinho se:
- Seu `status_cadeira = pronta`
- Seu estoque > 0

Filtro: `woocommerce_is_purchasable` e `woocommerce_variation_is_purchasable`

---

## 📎 Anexos e Histórico

- Todos os formulários gerados (empréstimo, devolução, avaliação) são salvos em PDF
- Formulários de **empréstimo e devolução** são anexados ao Pedido (post_meta)
- Formulários de **avaliação** são anexados ao Produto (post_meta com array)
- Cada avaliação contém:
  - Nome do avaliador
  - Data
  - Observações
  - Resultado (aprovado/reprovado)

---

## 🔒 Validação de CPF

- Antes de concluir o pedido:
  - Verificar pedidos ativos com status `Aguardando` ou `Emprestado`
  - Se >= 2 pedidos, bloqueia novo empréstimo
- Implementado via filtro: `woocommerce_checkout_process`

---

## 🔔 Notificações

Hooks para integração opcional:
- Lembretes de devolução via e-mail ou WhatsApp
- Confirmações de retirada e devolução
- Aviso interno de necessidade de avaliação

---

## 🧪 Tecnologias Complementares

- Geração de PDFs: `dompdf/dompdf`
- Shortcodes personalizados para:
  - Formulário de Empréstimo
  - Formulário de Devolução
  - Formulário de Avaliação Interna (uso restrito ao admin)

---

## 📁 Organização de Arquivos

```
/movimento-livre/
├── includes/
│   ├── class-status-mapper.php
│   ├── class-cpf-validator.php
│   ├── class-pdf-generator.php
│   ├── class-product-status-handler.php
│   └── class-order-hooks.php
├── templates/
│   ├── form-emprestimo.php
│   ├── form-devolucao.php
│   └── form-avaliacao.php
├── assets/
│   ├── js/
│   └── css/
├── movimento-livre.php
└── readme.txt
```

---

## ✅ Próximos Passos

- Definir os campos exatos dos formulários
- Implementar os metaboxes no admin do produto e do pedido
- Implementar lógica de bloqueio por CPF e transição de status
- Criar os templates dos formulários em PDF
- Estilizar front-end com acessibilidade

---

Este documento servirá como guia base para desenvolvimento, testes e manutenção do plugin.

