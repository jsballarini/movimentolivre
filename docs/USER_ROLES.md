# 👥 USER_ROLES.md - Movimento Livre

Este documento descreve os papéis (roles) de usuário definidos ou estendidos pelo plugin **Movimento Livre**, além de suas permissões específicas. O objetivo é organizar e proteger o acesso às funcionalidades administrativas e operacionais da plataforma.

---

## 🧑‍⚕️ 1. movliv_colaborador

### Descrição:
Colaborador do Instituto com acesso restrito a recursos operacionais (sem acesso administrativo completo).

### Permissões:
- Visualizar lista de pedidos (empréstimos)
- Visualizar e anexar formulários de devolução
- Visualizar cadeiras (produtos)
- Acessar formulários de avaliação (somente leitura)

---

## 🧪 2. movliv_avaliador

### Descrição:
Usuário com permissão para realizar avaliações técnicas de cadeiras devolvidas.

### Permissões:
- Tudo que `movliv_colaborador` tem
- Preencher formulários de avaliação técnica
- Alterar status do produto (Pronta, Em Avaliação, Em Manutenção)
- Visualizar histórico de avaliações por produto

---

## 🛠️ 3. movliv_admin

### Descrição:
Administrador completo do sistema Movimento Livre (geralmente o gestor do projeto ou responsável técnico da ONG).

### Permissões:
- Tudo que `movliv_avaliador` tem
- Gerenciar pedidos WooCommerce (empréstimos)
- Criar/editar/excluir produtos (cadeiras)
- Exportar relatórios por CPF/SKU/período
- Configurar opções do plugin
- Gerenciar documentos PDF e templates de e-mail
- Gerenciar usuários e permissões

---

## 👤 4. Cliente WooCommerce (usuário padrão)

### Descrição:
Usuário que realiza o empréstimo da cadeira via frontend (pedido WooCommerce).

### Permissões:
- Visualizar seus próprios pedidos (empréstimos)
- Preencher formulário de empréstimo
- Preencher formulário de devolução
- Receber e-mails de confirmação/alerta

---

## 🔐 Controle de Acesso

- Atribuições são feitas via `add_role()` e `add_cap()` na ativação do plugin
- Os formulários protegidos por shortcodes respeitam os níveis de permissão automaticamente
- As rotas de admin do plugin exigem verificação por `current_user_can()`

---

## 📋 Sugestão de Interface

- Menu "Movimento Livre" aparece apenas para `movliv_admin` e `movliv_avaliador`
- Avaliações são acessíveis somente por usuários com a role `movliv_avaliador`
- Clientes enxergam seus formulários apenas vinculados ao seu pedido

---

Este controle de papéis garante segurança e organização no uso do sistema, separando funções administrativas, operacionais e do público beneficiado.

