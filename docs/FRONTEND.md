# 🌐 FRONTEND.md - Movimento Livre

Este documento descreve a estrutura e os componentes de frontend utilizados pelo plugin **Movimento Livre**, incluindo formulários, shortcodes, controles de acesso e recomendações de UX para um sistema acessível e intuitivo.

---

## 🧾 Páginas Essenciais no Site

### 1. Página de Solicitação de Empréstimo
**URL sugerida:** `/solicitar-emprestimo`

**Conteúdo:**
- Lista de cadeiras com status = `Pronta`
- Botão "Solicitar Empréstimo" que adiciona a cadeira ao carrinho
- Checkout WooCommerce padrão
- Exibe o formulário de empréstimo após confirmação do pedido

**Shortcode:**
```[movliv_form_emprestimo pedido_id="{ID_AUTOMATICO}"]```

---

### 2. Página de Devolução
**URL sugerida:** `/devolver-cadeira`

**Conteúdo:**
- Lista de pedidos com status = `Emprestado` vinculados ao usuário (via CPF)
- Link para preencher formulário de devolução

**Shortcode:**
```[movliv_form_devolucao pedido_id="{ID_AUTOMATICO}"]```

---

### 3. Página de Histórico do Usuário
**URL sugerida:** `/meus-emprestimos`

**Conteúdo:**
- Tabela com todos os pedidos do usuário
- Exibição dos formulários preenchidos (empréstimo + devolução)
- Status atual: Emprestado, Devolvido

**Shortcode:**
```[movliv_historico_cpf cpf="{cpf_autenticado}"]```

---

## 🧪 Acesso Interno (Colaboradores e Avaliadores)

### 4. Página de Avaliações Técnicas
**URL sugerida:** `/avaliacoes`

**Restrita a:** usuários com role `movliv_avaliador`

**Conteúdo:**
- Lista de cadeiras com status = `Em Avaliação` ou `Em Manutenção`
- Link direto para preencher formulário de avaliação

**Shortcode:**
```[movliv_form_avaliacao produto_id="{ID_DA_CADEIRA}"]```

---

## 📋 Página de Listagem de Avaliações (por cadeira)
**URL sugerida:** `/historico-avaliacoes`

**Restrita a:** usuários com role `movliv_avaliador` ou `movliv_admin`

**Conteúdo:**
- Consulta por SKU/TAG da cadeira
- Exibição das avaliações anteriores (data, avaliador, observações)

**Shortcode:**
```[movliv_formularios_produto produto_id="{ID_DA_CADEIRA}"]```

---

## 🧑‍🎨 Estilo e Acessibilidade

### Requisitos recomendados:
- Fonte sem serifa, tamanho mínimo 16px
- Botões grandes e legíveis, com ícones
- Altos contrastes para acessibilidade visual
- Campos de formulário bem espaçados e legíveis
- Feedback visual após envio de formulários (sucesso/erro)

---

## 🧰 Recursos Frontend Adicionais

### Scripts e estilos:
- Os assets do plugin são carregados condicionalmente apenas nas páginas com shortcodes ativos
- Arquivos localizados em `/assets/css/` e `/assets/js/`

### Proteção de rotas:
- Shortcodes verificam automaticamente:
  - Se o pedido/produto pertence ao usuário autenticado (no caso do CPF)
  - Se o usuário tem a role correta para acessar a página (admin, avaliador, etc.)

---

## 📌 Observações
- Todos os shortcodes e rotas devem estar mapeados no menu principal e no painel do usuário
- Recomenda-se o uso de um tema compatível com WooCommerce e com boa responsividade (ex: Astra)

Este documento orienta o desenvolvimento e estruturação do frontend do plugin Movimento Livre, garantindo acessibilidade, segurança e clareza na navegação.

