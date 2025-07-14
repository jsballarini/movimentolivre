# 🧩 BACKEND.md - Movimento Livre

Este documento detalha a estrutura, lógica de funcionamento e os principais componentes do backend do plugin **Movimento Livre**, focado no gerenciamento de empréstimos, controle de estoque de cadeiras, geração de formulários e lógica de transição de status.

---

## 🧠 Estrutura Geral

- Desenvolvido como plugin WordPress nativo
- Integrado ao WooCommerce como camada de empréstimo
- Utiliza postmeta, usermeta e opções customizadas
- Baseado em post types existentes: `product`, `shop_order`

---

## ⚙️ Componentes Principais

### 1. Registro de Status Customizados

- Pedido:

  - `wc-aguardando`
  - `wc-emprestado`
  - `wc-devolvido`

- Produto (meta personalizada):

  - `pronta`
  - `emprestado`
  - `em_avaliacao`
  - `em_manutencao`

### 2. Formulários

- Armazenados como CPT (`movliv_formulario`)
- Ligação por postmeta com `shop_order` ou `product`
- Tipos:
  - `emprestimo`
  - `devolucao`
  - `avaliacao`

### 3. Controle por CPF

- Usado para limitar empréstimos simultâneos
- Armazenado via usermeta e validado via hook no checkout

### 4. Geração de Documentos (PDF)

- DomPDF como lib principal
- Salvos em `/uploads/movliv/`
- Cada envio gera um documento com carimbo de tempo, nome do responsável e assinatura (se aplicável)

### 5. Transições de Status

- Hooks automáticos que:
  - Mudam status do pedido após envio de formulário
  - Mudam status da cadeira com base na avaliação

### 6. Shortcodes

- Todos os formulários disponíveis por shortcode
- Shortcodes seguros com verificação de login e permissão

---

## 🔐 Segurança

- Nonces em todos os formulários
- Verificação de autorização por CPF e role do usuário
- Permissões personalizadas para cada tipo de formulário e ação

---

## 🧰 Admin UI

- Submenus no WooCommerce:

  - Empréstimos
  - Cadeiras (produtos)
  - Relatórios
  - Avaliações

- Campos adicionais no admin do produto:

  - Status atual da cadeira (drop-down)
  - Histórico de avaliações (readonly)

- Interface de histórico por CPF e exportação CSV

---

## 🧪 Testes e Logs

- Logs ativados com `WP_DEBUG_LOG`
- Funções com prefácio `movliv_` organizadas em arquivos por módulo
- Testes manuais com cadeiras fictícias e CPFs de exemplo

---

## 🚧 Estrutura Modular (pastas)

```
/movimento-livre/
|-- includes/
|   |-- class-formularios.php
|   |-- class-status.php
|   |-- class-restricoes.php
|   |-- class-cadeiras.php
|   |-- class-pdf.php
|   |-- class-notificacoes.php
|
|-- assets/
|   |-- js/
|   |-- css/
|
|-- templates/
|   |-- pdf/
|   |-- emails/
|
|-- shortcodes/
|   |-- form-emprestimo.php
|   |-- form-devolucao.php
|   |-- form-avaliacao.php
```

---

Este documento orienta o desenvolvimento técnico do backend e deve ser atualizado a cada alteração estrutural ou lógica no plugin.

