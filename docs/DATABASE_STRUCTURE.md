# 🗂️ DATABASE_STRUCTURE.md - Movimento Livre

Este documento define a estrutura de dados necessária para o funcionamento completo do plugin **Movimento Livre**, utilizando a base do WooCommerce com extensões via `postmeta` e tabelas auxiliares personalizadas para rastreio e histórico.

---

## 🧱 Estrutura Base Utilizada

O plugin utiliza as tabelas padrão do WordPress e WooCommerce:

- `wp_posts`

  - Tipo `product` = Cadeira
  - Tipo `shop_order` = Empréstimo

- `wp_postmeta`

  - Armazena os campos personalizados de cada pedido ou produto (ver abaixo)

- `wp_users` + `wp_usermeta`

  - Utilizado caso a ONG queira permitir login para acompanhamento de empréstimos

---

## 🧾 Estrutura de Pedido (Empréstimo)

### Tipo: `shop_order`

#### Campos personalizados (armazenados em `wp_postmeta`)

| Meta Key                   | Tipo   | Descrição                                      |
| -------------------------- | ------ | ---------------------------------------------- |
| `_cpf_solicitante`         | string | CPF do usuário responsável pelo empréstimo     |
| `_form_emprestimo_pdf`     | string | Caminho/URL do PDF do formulário de empréstimo |
| `_form_devolucao_pdf`      | string | Caminho/URL do PDF do formulário de devolução  |
| `_data_prevista_devolucao` | date   | Data limite estimada para devolução            |
| `_status_empr_custom`      | string | Aguardando / Emprestado / Devolvido            |

---

## 🪑 Estrutura de Produto (Cadeira)

### Tipo: `product`

#### Campos personalizados (armazenados em `wp_postmeta`)

| Meta Key              | Tipo   | Descrição                                       |
| --------------------- | ------ | ----------------------------------------------- |
| `_tag_sku`            | string | TAG física da cadeira (mesmo valor do SKU Woo)  |
| `_status_produto`     | string | Pronta, Emprestado, Em Avaliação, Em Manutenção |
| `_avaliacoes_produto` | array  | Histórico de avaliações (serializado em JSON)   |

##### Estrutura interna de `_avaliacoes_produto` (JSON)

```json
[
  {
    "data": "2025-07-10",
    "avaliador": "Maria Santos",
    "resultado": "Aprovado",
    "observacoes": "Cadeira em boas condições."
  },
  {
    "data": "2025-08-05",
    "avaliador": "João Almeida",
    "resultado": "Reprovado",
    "observacoes": "Roda danificada, enviado para manutenção."
  }
]
```

---

## 📋 Tabela Auxiliar: Histórico de Formulários

Para maior organização, pode ser criada a tabela `wp_movliv_formularios` para registrar todos os formulários (PDFs gerados).

### Estrutura sugerida

| Campo            | Tipo        | Descrição                                  |
| ---------------- | ----------- | ------------------------------------------ |
| `id`             | int         | Chave primária                             |
| `pedido_id`      | bigint      | ID do pedido (nullable se for avaliação)   |
| `produto_id`     | bigint      | ID do produto (nullable se for empréstimo) |
| `cpf`            | varchar(20) | CPF vinculado                              |
| `tipo`           | varchar(20) | emprestimo, devolucao, avaliacao           |
| `url_arquivo`    | text        | Caminho/URL para o PDF gerado              |
| `nome_avaliador` | varchar(80) | Preenchido apenas se for avaliação interna |
| `data_criacao`   | datetime    | Data e hora do envio                       |

---

## 🔐 Considerações de Segurança

- Todos os dados sensíveis como CPF devem ser armazenados com acesso restrito no admin
- Logs e formulários devem ser associados com IDs de autor para rastreabilidade
- PDFs devem ser protegidos contra acesso público direto (ex: usando diretórios `uploads/movliv/` com .htaccess)

---

## 📌 Observações Finais

- A estrutura se apoia majoritariamente no sistema de `postmeta` para manter compatibilidade total com WooCommerce
- A criação de uma tabela auxiliar é opcional, recomendada para projetos com muitos registros ou uso extensivo de formulários
- Todos os relacionamentos devem estar documentados no admin com interface amigável

Este esquema de banco de dados garante escalabilidade, rastreabilidade e compatibilidade total com ferramentas padrão do WordPress.

