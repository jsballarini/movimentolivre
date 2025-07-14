# 🦽 Movimento Livre - Plugin de Empréstimo Solidário de Cadeiras de Rodas

**Movimento Livre** é um plugin WordPress que transforma o WooCommerce em um sistema social de **empréstimos gratuitos de cadeiras de rodas**, desenvolvido para o Instituto Bernardo Ferreira - *Um Legado em Movimento*.

Totalmente adaptado para a realidade do terceiro setor, o plugin transforma a lógica de produtos e pedidos do WooCommerce em uma estrutura robusta de **empréstimos, devoluções, avaliações e controle de estoque social**.

---

## 🎯 Propósito Social

Oferecer **autonomia, dignidade e mobilidade** a pessoas com deficiência ou mobilidade reduzida por meio de um sistema gratuito, digital e seguro de empréstimo de cadeiras de rodas.

---

## 🛠️ Como Funciona

### 🔁 Fluxo Geral

1. **Solicitação**
   - O usuário solicita uma cadeira (produto).
   - O pedido entra como `Aguardando`.
   - O sistema exige o preenchimento do **Formulário de Empréstimo**.
   - Ao enviar o formulário:
     - Status do Pedido = **Emprestado**
     - Estoque reduzido
     - Status da Cadeira = **Emprestado**

2. **Devolução**
   - O usuário preenche o **Formulário de Devolução**.
   - Ao enviar o formulário:
     - Status do Pedido = **Concluído**
     - Estoque **não retorna ainda**
     - Status da Cadeira = **Em Avaliação**
     - É gerado um **Formulário de Avaliação Interna**

3. **Avaliação Interna**
   - Um colaborador preenche o **Formulário de Avaliação** com:
     - Estado da cadeira
     - Observações técnicas
     - Nome de quem avaliou
     - Data da avaliação
   - Com base no resultado da avaliação:

#### ✔️ Se **Aprovada**:
- Status da Cadeira = **Pronta**
- Cadeira **retorna ao estoque**

#### ❌ Se **Reprovada**:
- Status da Cadeira = **Em Manutenção**
- Gera um **novo formulário de avaliação** após o conserto

> O novo formulário também será preenchido por um colaborador, reiniciando o processo de verificação:

##### ✔️ Se **Aprovada** na reavaliação:
- Status da Cadeira = **Pronta**
- Cadeira retorna ao estoque

##### ❌ Se **Reprovada novamente**:
- A cadeira permanece com status **Em Manutenção**
- Um novo ciclo de manutenção e avaliação pode ser iniciado

---

## 🧾 Relacionamento entre Entidades

| Entidade         | Formulário Vinculado           | Observações                              |
|------------------|-------------------------------|------------------------------------------|
| Pedido (Empréstimo) | Empréstimo + Devolução         | Vinculados ao CPF e à TAG da cadeira     |
| Produto (Cadeira) | Avaliações Internas (histórico) | Com nome, data e resultado de quem avaliou ou reparou |

---

## 🔒 Controle por CPF

- Cada CPF pode ter no máximo **2 empréstimos ativos simultaneamente**
- Novas solicitações são bloqueadas enquanto esse limite estiver ativo
- Todo o histórico de empréstimos e devoluções é vinculado ao CPF

---

## 🛒 Adaptação do WooCommerce

### Renomeações

| Original WooCommerce | Movimento Livre        |
|----------------------|------------------------|
| Produtos             | Cadeiras               |
| Pedidos              | Empréstimos            |
| On Hold              | Aguardando             |
| Processando          | Emprestado             |
| Concluído            | Devolvido              |

### Novos Status de Produto (Cadeira)

| Status da Cadeira   | Significado                                                                 |
|---------------------|-----------------------------------------------------------------------------|
| Pronta              | Disponível para empréstimo                                                  |
| Emprestado          | Está vinculada a um pedido ativo                                            |
| Em Avaliação        | Devolvida, aguardando avaliação técnica                                     |
| Em Manutenção       | Reprovada na avaliação, aguardando novo formulário após conserto           |

### 🔁 Status Personalizados do Pedido (Empréstimo)

Apenas **três status são utilizados**, refletindo o fluxo real de um empréstimo:

- 🟡 `Aguardando`: Pedido feito, aguardando envio do formulário de retirada
- 🟢 `Emprestado`: Formulário de retirada recebido, cadeira entregue
- ✅ `Devolvido`: Formulário de devolução enviado e cadeira devolvida ao estoque

**Transições automáticas:**

- Ao enviar o **formulário de retirada**, o status muda para **Emprestado**
- Ao enviar o **formulário de devolução**, o status muda para **Devolvido**

---

### 🔁 Status Personalizados do Produto (Cadeira)

- 🟢 `Pronta`: Quando a Cadeira está pronta para ser emprestada. (Permite Fazer Empréstimo (Pedido)
- 🔵 `Emprestado`: Quando a Cadeira está Emprestada para um CPF. (Não permite fazer Empréstimo (Pedido) da Cadeira com Esse Status)
- 🟡 `Em Avaliaçao`: Quando a cadeira é devolvida, gera o formulário de avaliaçao interno. (Não permite fazer Empréstimo (Pedido) da Cadeira com Esse Status)
- 🔴 `Em Manutençao`: Quando o formulário de avaliação não aprova a cadeira para ser emprestada, a cadeira vai para manutenção e gera um novo formulário de avaliação (Não permite fazer Empréstimo (Pedido) da Cadeira com Esse Status)

## 📄 Formulários e Geração de Documentos

- **Formulário de Empréstimo**: preenchido pelo usuário
- **Formulário de Devolução**: preenchido pelo usuário
- **Formulário de Avaliação Interna**: preenchido por colaborador (com histórico)
- Todos os formulários são salvos como PDF e vinculados ao respectivo Pedido ou Produto
- O histórico de avaliações fica armazenado no Produto com nome, data e observações

---

## 📊 Relatórios e Gerenciamento

- Relatórios por:
  - CPF
  - TAG (SKU)
  - Status do Pedido
  - Status da Cadeira
  - Período

- Exportação em CSV

---

## 🔔 Notificações

- Envio automático de:
  - Lembretes de devolução
  - Confirmações de status
  - Solicitações internas de avaliação/manutenção

---

## 📦 Requisitos Técnicos

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+
- Extensão para geração de PDFs (`dompdf` ou `TCPDF`)

---

## 📄 Licença

Licenciado sob a licença MIT.

---

## ❤️ Frase do Instituto

> “Mobilidade é liberdade. E liberdade é dignidade. O Movimento Livre nasceu para garantir que ninguém fique para trás.”  
> — Instituto Bernardo Ferreira

---

## 🙋 Como Contribuir

Projeto de código aberto e impacto social. Contribuições são bem-vindas em:

- Código e testes
- Integração com redes públicas de saúde
- Design acessível
- Manual para replicação por outras ONGs
