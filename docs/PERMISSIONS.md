# 🔐 PERMISSIONS.md - Movimento Livre

Este documento descreve as permissões (capabilities) utilizadas e atribuídas pelo plugin **Movimento Livre** para controlar com precisão o acesso às funcionalidades sensíveis do sistema.

As permissões são associadas a roles customizadas, mas também podem ser atribuídas manualmente por plugins como Members, User Role Editor ou diretamente via código.

---

## 🧾 Permissões Personalizadas do Plugin

| Capability                 | Descrição                                                      |
| -------------------------- | -------------------------------------------------------------- |
| `movliv_view_orders`       | Visualizar todos os pedidos (empréstimos) no admin             |
| `movliv_manage_forms`      | Gerar e anexar formulários PDF                                 |
| `movliv_submit_evaluation` | Preencher e enviar formulário de avaliação técnica             |
| `movliv_manage_status`     | Alterar status de produtos (cadeiras) manual ou via formulário |
| `movliv_view_reports`      | Acessar página de relatórios e exportar CSV                    |
| `movliv_manage_settings`   | Gerenciar configurações do plugin                              |
| `movliv_manage_roles`      | Atribuir permissões e funções aos usuários                     |
| `movliv_view_cadeiras`     | Visualizar a lista de produtos (cadeiras) no admin             |
| `movliv_manage_emails`     | Personalizar templates e notificações por e-mail               |

---

## 🎯 Mapeamento de Roles e Permissões

| Role                 | Permissões Atribuídas                                                                                           |
| -------------------- | --------------------------------------------------------------------------------------------------------------- |
| `movliv_colaborador` | `movliv_view_orders`, `movliv_manage_forms`, `movliv_view_cadeiras`                                             |
| `movliv_avaliador`   | Todas as de `movliv_colaborador` + `movliv_submit_evaluation`, `movliv_manage_status`                           |
| `movliv_admin`       | Todas as acima + `movliv_view_reports`, `movliv_manage_settings`, `movliv_manage_roles`, `movliv_manage_emails` |

---

## 📌 Permissões WooCommerce Utilizadas

O plugin respeita e reutiliza permissões padrão do WooCommerce quando aplicável:

- `edit_shop_orders` → usada por `movliv_admin`
- `edit_products` → para criar/editar cadeiras

---

## 🔧 Implementação Técnica

As permissões são registradas na ativação do plugin:

```php
add_role('movliv_colaborador', 'Colaborador', [
  'movliv_view_orders' => true,
  'movliv_manage_forms' => true,
  'movliv_view_cadeiras' => true,
]);
```

Verificações no código são feitas com:

```php
if (current_user_can('movliv_submit_evaluation')) {
    // Exibe botão ou formulário de avaliação
}
```

---

## 🧠 Boas Práticas

- Nunca conceder permissões administrativas a usuários padrão
- Verificar permissões em todos os shortcodes e endpoints do plugin
- Separar claramente permissões de leitura, edição e administração

---

Este sistema de permissões garante segurança, controle granular e extensibilidade para o plugin Movimento Livre, adaptando-se a realidades institucionais com múltiplos colaboradores.

