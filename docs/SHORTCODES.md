# 🔤 SHORTCODES.md - Movimento Livre

Este documento descreve todos os shortcodes disponíveis no plugin **Movimento Livre**, com instruções de uso e exemplos. Os shortcodes permitem que funcionalidades essenciais sejam incorporadas em páginas, widgets e templates de forma simples e modular.

---

## 🧾 [movliv_form_emprestimo]

### Exibe o formulário de empréstimo (retirada), preenchido pelo usuário após a solicitação de uma cadeira.

**Uso:**
```[movliv_form_emprestimo pedido_id="123"]```

**Parâmetros:**
- `pedido_id` (obrigatório): ID do pedido WooCommerce do tipo `shop_order`

**Funções automáticas:**
- Gera PDF do formulário preenchido
- Atualiza status do pedido para **Emprestado**
- Atualiza status do produto para **Emprestado**
- Reduz o estoque do produto

---

## 🔁 [movliv_form_devolucao]

### Exibe o formulário de devolução da cadeira, preenchido pelo usuário na hora da devolução.

**Uso:**
```[movliv_form_devolucao pedido_id="123"]```

**Parâmetros:**
- `pedido_id` (obrigatório): ID do pedido que será marcado como devolvido

**Funções automáticas:**
- Gera PDF do formulário preenchido
- Atualiza status do pedido para **Devolvido**
- Atualiza status da cadeira para **Em Avaliação**
- Gera automaticamente um formulário de avaliação interna vinculado ao produto

---

## 🧪 [movliv_form_avaliacao]

### Exibe o formulário interno de avaliação da cadeira, preenchido por um colaborador após devolução.

**Uso:**
```[movliv_form_avaliacao produto_id="456"]```

**Parâmetros:**
- `produto_id` (obrigatório): ID do produto (cadeira) a ser avaliado

**Funções automáticas:**
- Gera PDF do formulário preenchido
- Armazena avaliação no histórico do produto (array JSON)
- Atualiza status do produto para:
  - **Pronta**, se aprovada
  - **Em Manutenção**, se reprovada
- Se reprovada, gera novo formulário de avaliação

**Restrito a:** usuários administradores ou com permissão `movliv_avaliador`

---

## 📋 [movliv_formularios_produto]

### Lista todos os formulários de avaliação associados a uma cadeira específica.

**Uso:**
```[movliv_formularios_produto produto_id="456"]```

**Parâmetros:**
- `produto_id`: ID da cadeira (product)

**Saída:**
- Tabela com nome, data, status e link para download de cada avaliação

---

## 📋 [movliv_lista_cadeiras]

### Lista as cadeiras disponíveis para empréstimo, agrupadas por modelo.

**Uso:**
```[movliv_lista_cadeiras]```

**Requisitos para Exibição:**
- Status da cadeira deve ser "Pronta"
- Estoque deve ser maior que 0
- Produto deve estar publicado e ativo

**Funcionalidades:**
- Mostra apenas cadeiras disponíveis para empréstimo
- Agrupa cadeiras idênticas, mostrando apenas uma de cada modelo
- Exibe o estoque atual de cada modelo
- Inclui imagem e descrição da cadeira
- Botão "Solicitar Empréstimo" que adiciona ao carrinho

**Exemplo de Saída:**
```
Cadeira de Rodas 90kg
Disponíveis: 1 unidade
[Imagem]
[Descrição]
[Botão Solicitar]

Cadeira de Rodas 120kg
Disponíveis: 1 unidade
[Imagem]
[Descrição]
[Botão Solicitar]
```

**Verificações de Disponibilidade:**
1. Status = "Pronta"
2. Estoque > 0
3. Produto publicado
4. Gerenciamento de estoque ativo

---

## 🔎 [movliv_historico_cpf]

### Exibe o histórico de empréstimos e devoluções de um CPF.

**Uso:**
```[movliv_historico_cpf cpf="12345678900"]```

**Parâmetros:**
- `cpf`: Número do CPF (somente números)

**Saída:**
- Lista com os pedidos vinculados a esse CPF
- Status atual de cada pedido
- Data de retirada e devolução

**Restrito a:** admin ou operadores autorizados

---

## 🔐 Considerações Técnicas

- Todos os shortcodes são registrados via `add_shortcode()`
- Os formulários são gerados usando templates localizados em `/templates`
- PDFs são salvos em diretórios privados
- Verificações de permissão são obrigatórias para shortcodes administrativos

---

Este documento deve ser mantido atualizado conforme novos shortcodes forem criados no plugin Movimento Livre.

