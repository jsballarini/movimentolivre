# 🧩 MODULES.md - Movimento Livre

Este documento descreve os módulos principais que compõem o plugin **Movimento Livre**, suas responsabilidades, interações e possibilidades de reutilização em outros plugins ou projetos sociais baseados em WordPress/WooCommerce.

---

## 🔌 Visão Geral dos Módulos

O Movimento Livre é estruturado em módulos independentes e interligáveis, seguindo o princípio da modularidade e reutilização. Cada módulo é responsável por uma parte crítica do fluxo de empréstimo de cadeiras de rodas.

---

## 📄 1. Módulo de Formulários

### Objetivo:
Gerar, exibir e armazenar os formulários essenciais do fluxo de empréstimo e avaliação, em formato PDF.

### Formulários contemplados:
- Formulário de Empréstimo (vinculado ao Pedido)
- Formulário de Devolução (vinculado ao Pedido)
- Formulário de Avaliação Interna (vinculado ao Produto)

### Funcionalidades:
- Shortcodes para exibição dos formulários no frontend e backend
- Geração de PDFs salvos em diretórios protegidos
- Anexos salvos via `post_meta` e/ou tabela auxiliar
- Histórico de formulários acessível no admin

---

## 🔁 2. Módulo de Status e Transições

### Objetivo:
Gerenciar os status personalizados de Pedidos (Empréstimos) e Produtos (Cadeiras), além das transições automáticas entre eles.

### Aplicações:
- Substituição dos status padrão do WooCommerce
- Controle de status da Cadeira (produto)
- Lógica condicional para permitir ou bloquear empréstimos

### Status de Pedido (Empréstimo):
- Aguardando → Emprestado → Devolvido

### Status de Produto (Cadeira):
- Pronta → Emprestado → Em Avaliação → Em Manutenção

### Interações:
- Integração com Módulo de Formulários para acionar transições
- Hooks e filtros para modificar comportamento nativo do WooCommerce

---

## 🔒 3. Módulo de Regras de CPF

### Objetivo:
Controlar a quantidade máxima de empréstimos ativos por CPF.

### Funcionalidades:
- Validação no checkout: máximo de 2 empréstimos por CPF
- Contagem baseada nos pedidos com status Aguardando ou Emprestado
- Mensagens de erro amigáveis para usuários que atingirem o limite

---

## 🧮 4. Módulo de Relatórios

### Objetivo:
Oferecer uma visão gerencial completa para o Instituto, com possibilidade de exportação e filtros.

### Tipos de Relatórios:
- Por CPF
- Por TAG/SKU da Cadeira
- Por status de Empréstimo ou status da Cadeira
- Por período (data de retirada ou devolução)

### Funcionalidades:
- Filtros combináveis (status + data + SKU)
- Exportação em CSV
- Atalho no painel WooCommerce > Empréstimos

---

## 📎 5. Módulo de Anexos

### Objetivo:
Controlar e exibir todos os arquivos PDF anexados aos pedidos e produtos.

### Aplicações:
- Exibição dos anexos no admin de Pedido e Produto
- Relacionamento entre arquivos e formulários gerados
- Visualização ou download direto pelo painel admin

---

## 🔔 6. Módulo de Notificações (Opcional)

### Objetivo:
Enviar alertas automáticos por e-mail ou WhatsApp para usuários e colaboradores.

### Tipos de Notificações:
- Lembrete de devolução (X dias antes da data prevista)
- Confirmação de retirada ou devolução
- Aviso interno de nova avaliação necessária

### Integrações:
- WooCommerce Emails
- APIs externas de envio (WhatsApp, SMTP, SendGrid etc.)

---

## 🔐 7. Módulo de Segurança e Acessos

### Objetivo:
Garantir que os dados sensíveis estejam protegidos e acessíveis apenas a quem de direito.

### Funcionalidades:
- Restrições por nível de usuário (admin, colaborador, solicitante)
- Proteção de diretórios de arquivos
- Filtragem de exibição de formulários conforme status e permissão

---

## ♻️ Modularidade e Reuso

- Cada módulo pode ser extraído e adaptado para outros plugins com foco social
- O sistema é pensado para ONGs, hospitais, associações ou grupos de assistência que desejem um sistema similar para empréstimos de outros itens (muletas, andadores, etc.)

---

Este documento deve ser usado como referência para desenvolvimento, manutenção e extensão do plugin Movimento Livre.

