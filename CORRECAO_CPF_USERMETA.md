# Correção da Localização do CPF nos Relatórios

## Problema Identificado

As consultas SQL dos relatórios estavam buscando o CPF na tabela `wp_postmeta` com a meta_key `_cpf_solicitante`, mas o CPF correto está armazenado na tabela `wp_usermeta` com a meta_key `billing_cpf`.

## Causa Raiz

O sistema estava usando uma meta_key personalizada inexistente em vez de utilizar o campo padrão do WooCommerce onde o CPF é realmente armazenado.

- ❌ **Errado**: `wp_postmeta` → `_cpf_solicitante` 
- ✅ **Correto**: `wp_usermeta` → `billing_cpf`

## Soluções Implementadas

### 1. Correção das Consultas SQL

#### Método `get_general_stats()` - 5 consultas corrigidas:

**Antes:**
```sql
FROM {$wpdb->posts} p
INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
WHERE pm.meta_key = '_cpf_solicitante'
```

**Depois:**
```sql  
FROM {$wpdb->posts} p
INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
WHERE um.meta_key = 'billing_cpf'
```

#### Método `generate_top_usuarios_table()`:

**Antes:**
```sql
INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_cpf_solicitante'
LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_billing_first_name'
LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_billing_last_name'
```

**Depois:**
```sql
INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
INNER JOIN {$wpdb->usermeta} um_cpf ON u.ID = um_cpf.user_id AND um_cpf.meta_key = 'billing_cpf'
LEFT JOIN {$wpdb->usermeta} um_fname ON u.ID = um_fname.user_id AND um_fname.meta_key = 'billing_first_name'
LEFT JOIN {$wpdb->usermeta} um_lname ON u.ID = um_lname.user_id AND um_lname.meta_key = 'billing_last_name'
```

### 2. Nova Função Helper

Criada função `get_user_cpf_from_order()` para centralizar a busca do CPF:

```php
private function get_user_cpf_from_order( $order ) {
    $user_id = $order->get_user_id();
    if ( ! $user_id ) {
        return '';
    }
    
    return get_user_meta( $user_id, 'billing_cpf', true );
}
```

### 3. Substituição de `get_post_meta`

**Antes:**
```php
$cpf = get_post_meta( $order->get_id(), '_cpf_solicitante', true );
```

**Depois:**
```php
$cpf = $this->get_user_cpf_from_order( $order );
```

## Métodos Corrigidos

### 📊 Consultas SQL Diretas:
1. **`get_general_stats()`** - Total de empréstimos
2. **`get_general_stats()`** - Empréstimos este mês  
3. **`get_general_stats()`** - Usuários únicos
4. **`get_general_stats()`** - Novos usuários este mês (2 consultas)
5. **`get_general_stats()`** - Taxa de devolução (2 consultas)
6. **`generate_top_usuarios_table()`** - Top usuários com empréstimos

### 🔄 Funções PHP:
7. **`generate_emprestimos_table()`** - Filtros e loop (3 ocorrências)
8. **`export_emprestimos_csv()`** - Filtros e loop (3 ocorrências)

## Estrutura de Relacionamento

```
wp_posts (pedidos)
    ↓ post_author = ID
wp_users (usuários) 
    ↓ ID = user_id
wp_usermeta (meta do usuário)
    ↓ meta_key = 'billing_cpf'
```

## Resultado

- ✅ **Dados corretos**: Relatórios agora capturam o CPF real dos usuários
- ✅ **Consultas otimizadas**: JOINs apropriados entre tabelas corretas
- ✅ **Código centralizado**: Função helper para reutilização
- ✅ **Padrão WooCommerce**: Uso dos campos nativos da plataforma

## Testes Realizados

- ✅ Verificação de que não há mais `_cpf_solicitante` no código
- ✅ Confirmação de que todas as consultas usam `billing_cpf`
- ✅ Validação dos JOINs com tabelas corretas
- ✅ Teste da função helper em diferentes contextos

## Data da Correção
**Data:** 02/01/2025  
**Desenvolvedor:** Juliano Ballarini  
**Versão:** 0.13.3

## Observações Técnicas

Esta correção resolve um problema fundamental de arquitetura onde o sistema buscava dados em local incorreto. Agora o plugin utiliza corretamente os campos padrão do WooCommerce, garantindo compatibilidade e dados precisos.

O uso da tabela `wp_usermeta` é o padrão do WooCommerce para armazenar informações de cobrança dos usuários, incluindo o CPF através do plugin WooCommerce Extra Checkout Fields for Brazil. 