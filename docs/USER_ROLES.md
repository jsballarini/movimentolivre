# 👥 USER_ROLES.md - Movimento Livre

Este documento descreve os papéis (roles) de usuário definidos ou estendidos pelo plugin **Movimento Livre**, além de suas permissões específicas. O objetivo é organizar e proteger o acesso às funcionalidades administrativas e operacionais da plataforma.

---

## 🧑‍⚕️ 1. movliv_colaborador

### Descrição:
Colaborador do Instituto com acesso a recursos operacionais incluindo avaliação e devolução de cadeiras.

### Permissões:
- Visualizar lista de pedidos (empréstimos)
- Visualizar e anexar formulários de devolução
- Visualizar cadeiras (produtos)
- **NOVO:** Preencher formulários de avaliação técnica
- **NOVO:** Alterar status do produto (Pronta, Em Avaliação, Em Manutenção)
- **NOVO:** Visualizar histórico de avaliações por produto

---

## 🧪 2. movliv_avaliador

### Descrição:
Usuário com permissão para realizar avaliações técnicas de cadeiras devolvidas (mantido para compatibilidade).

### Permissões:
- **Mesmas permissões** que `movliv_colaborador`
- Role mantido para compatibilidade com instalações existentes
- **NOTA:** Não há diferença funcional entre Colaborador e Avaliador

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
- **Colaboradores** têm acesso completo a avaliações e devoluções
- **Avaliadores** mantêm as mesmas permissões (compatibilidade)
- **Administradores** têm acesso total ao sistema
- Menu "Movimento Livre" aparece para `movliv_colaborador`, `movliv_avaliador` e `movliv_admin`
- Avaliações são acessíveis por usuários com role `movliv_colaborador` ou superior

---

## 📋 Sugestão de Interface

- Menu "Movimento Livre" aparece apenas para `movliv_admin` e `movliv_avaliador`
- Avaliações são acessíveis somente por usuários com a role `movliv_avaliador`
- Clientes enxergam seus formulários apenas vinculados ao seu pedido

---

Este controle de papéis garante segurança e organização no uso do sistema, separando funções administrativas, operacionais e do público beneficiado.

