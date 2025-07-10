# 🦽 Movimento Livre - Plugin de Empréstimo Solidário de Cadeiras de Rodas

**Movimento Livre** é um plugin WordPress que transforma o WooCommerce em um sistema social de **empréstimos gratuitos de cadeiras de rodas**, criado para o Instituto Bernardo Ferreira - *Um Legado em Movimento*.

Com adaptação completa da interface do WooCommerce, o plugin elimina termos comerciais como "venda" e "produto", e os substitui por uma linguagem adequada ao contexto social e humanitário do projeto.

---

## 🎯 Propósito Social

Oferecer **autonomia e dignidade** por meio de um sistema confiável de **empréstimo gratuito de cadeiras de rodas**, com controle por CPF, geração de comprovantes e formulários digitais.

---

## ⚙️ Funcionalidades Principais

### 🛒 WooCommerce como Sistema de Empréstimo

- Cada **cadeira de rodas** é cadastrada como uma **Cadeira** (produto WooCommerce renomeado)
- A **TAG física da cadeira** é usada como **SKU**, permitindo rastreabilidade
- Utilização dos **Empréstimos** (Pedidos WooCommerce renomeados) como registros oficiais
- Controle de estoque automático (saída na retirada, retorno na devolução)

---

### 🔁 Status Personalizados

Apenas **três status são utilizados**, refletindo o fluxo real de um empréstimo:

- 🟡 `Aguardando`: Pedido feito, aguardando envio do formulário de retirada
- 🟢 `Emprestado`: Formulário de retirada recebido, cadeira entregue
- ✅ `Devolvido`: Formulário de devolução enviado e cadeira devolvida ao estoque

**Transições automáticas:**

- Ao enviar o **formulário de retirada**, o status muda para **Emprestado**
- Ao enviar o **formulário de devolução**, o status muda para **Devolvido**, e o item retorna ao estoque

---

### 👥 Controle por CPF (Multicliente)

- Cada pessoa identificada por CPF pode ter **até 2 empréstimos simultâneos**
- Sistema impede novos empréstimos caso o limite esteja ativo
- Histórico completo de empréstimos por CPF

---

### 📄 Formulários e Comprovantes

- **Formulário de Retirada (PDF)**:
  - Dados do solicitante
  - TAG da cadeira
  - Termo de responsabilidade
  - Campo para assinatura (manual ou digital)

- **Formulário de Devolução**:
  - Avaliação do estado da cadeira
  - Observações e condições de retorno
  - Documento anexado diretamente ao Empréstimo (pedido Woo)

---

### 📊 Relatórios Gerenciais

- Filtros por:
  - CPF
  - TAG/SKU da cadeira
  - Período e status (Emprestado, Atrasado, Devolvido)
- Exportação para CSV
- Histórico por beneficiário

---

### 🧾 Renomeação da Interface do WooCommerce

Para refletir o propósito do projeto, a interface do WooCommerce é adaptada:

| Original WooCommerce | Movimento Livre        |
|----------------------|------------------------|
| Produtos             | Cadeiras               |
| Pedidos              | Empréstimos            |
| Processando          | Emprestado             |
| Concluído            | Devolvido              |
| On-hold              | Aguardando             |

---

## 🧱 Módulos Ativos

- 📄 **Formulários** – geração de PDFs de retirada e devolução
- 🔒 **Regras de CPF** – limite por CPF simultâneo
- 🧮 **Relatórios** – visão gerencial por status, CPF e TAG
- 📎 **Anexos no Empréstimo** – formulários vinculados ao histórico
- 🔔 **Notificações (opcional)** – e-mails ou WhatsApp para lembretes

---

## 🧰 Requisitos Técnicos

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+
- Extensão `dompdf` ou `TCPDF` para gerar PDFs

---

## 🤝 Apoio Social

> “O Movimento Livre nasceu para levar liberdade, mobilidade e respeito às pessoas. Com ele, a solidariedade ganha forma, registro e estrutura.”  
> — Instituto Bernardo Ferreira

---

## 📄 Licença

Licenciado sob a licença MIT.

---

## 🙋 Como Contribuir

Este é um projeto social e de código aberto. Contribuições são bem-vindas em:

- Código e testes
- UI/UX acessível
- Integrações com redes públicas de saúde
- Traduções e suporte a ONGs locais
