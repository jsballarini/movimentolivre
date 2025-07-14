# 📡 API\_REFERENCE.md - Movimento Livre

Este documento descreve os endpoints REST disponíveis no plugin **Movimento Livre** para integração com sistemas externos, aplicativos ou painéis personalizados.

---

## 🔐 Autenticação

- Baseado em REST API do WordPress
- Requer autenticação via token JWT ou cookie WordPress
- Apenas usuários com permissões adequadas podem acessar cada endpoint

---

## 📚 Endpoints Disponíveis

### 1. `GET /wp-json/movliv/v1/status/cadeiras`

**Descrição:** Lista o status atual de todas as cadeiras cadastradas

**Parâmetros:**

- `status` (opcional): `pronta`, `emprestado`, `em_avaliacao`, `em_manutencao`

**Resposta:**

```json
[
  {
    "id": 123,
    "sku": "TAG001",
    "status": "pronta",
    "avaliacoes": [
      {
        "data": "2025-07-01",
        "responsavel": "Maria Lima",
        "resultado": "Aprovada"
      }
    ]
  },
  ...
]
```

---

### 2. `GET /wp-json/movliv/v1/historico/cpf/{cpf}`

**Descrição:** Retorna o histórico completo de empréstimos por CPF

**Parâmetros:**

- `cpf` (obrigatório): Somente números (ex: 12345678900)

**Resposta:**

```json
{
  "cpf": "12345678900",
  "emprestimos": [
    {
      "pedido_id": 321,
      "status": "devolvido",
      "cadeira": "TAG002",
      "data_emprestimo": "2025-06-10",
      "data_devolucao": "2025-06-28"
    },
    ...
  ]
}
```

---

### 3. `POST /wp-json/movliv/v1/formulario/{tipo}`

**Descrição:** Envia um novo formulário (emprestimo, devolucao, avaliacao)

**Autenticação obrigatória**

**Body:**

```json
{
  "pedido_id": 123,
  "produto_id": 456,
  "cpf": "12345678900",
  "dados": {
    "nome": "João Silva",
    "observacoes": "Cadeira em bom estado",
    "assinatura_base64": "..."
  }
}
```

**Resposta:**

```json
{
  "success": true,
  "mensagem": "Formulário salvo com sucesso",
  "pdf_url": "/wp-content/uploads/movliv/form_321.pdf"
}
```

---

## 📥 Upload e Assinatura

- Formulários podem incluir assinatura em imagem base64
- Validação automática de campos obrigatórios

---

## 🔐 Permissões por Role

- `movliv_admin`: acesso total a todos os endpoints
- `movliv_avaliador`: acesso a `/formulario/avaliacao`
- `subscriber`: acesso apenas a `/formulario/emprestimo` e `/formulario/devolucao`

---

## 🚧 Futuros Endpoints

- Consulta de relatórios via API
- Dashboard mobile
- Exportação CSV via API

---

Este documento serve como referência técnica para desenvolvedores que desejam integrar o Movimento Livre a outros sistemas de gestão ou aplicativos externos.

