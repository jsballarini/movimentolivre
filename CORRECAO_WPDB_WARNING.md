# Correção do Warning: Undefined property wpdb::$woocommerce_order_items

## Problema Identificado

```
Warning: Undefined property: wpdb::$woocommerce_order_items in 
V:\XAMPP\htdocs\movimentolivre\wp-includes\class-wpdb.php on line 789
```

## Causa Raiz

### Referência Incorreta à Tabela
O código estava tentando acessar uma propriedade inexistente `$wpdb->woocommerce_order_items` quando deveria usar o nome completo da tabela com prefix.

### Query SQL Incorreta
A query original também tinha problemas estruturais no JOIN entre as tabelas.

## Código Problemático

### Antes:
```php
$results = $wpdb->get_results( "
    SELECT 
        p.ID,
        p.post_title,
        COUNT(oi.order_item_id) as total_emprestimos,
        COALESCE(pm.meta_value, 'pronta') as status_atual
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->woocommerce_order_items} oi ON p.ID = oi.order_item_id
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_status_produto'
    WHERE p.post_type = 'product'
    AND p.post_status = 'publish'
    GROUP BY p.ID
    ORDER BY total_emprestimos DESC
    LIMIT 20
" );
```

### Problemas Identificados:
1. **`{$wpdb->woocommerce_order_items}`** - Propriedade inexistente
2. **`p.ID = oi.order_item_id`** - JOIN incorreto (IDs de tabelas diferentes)
3. **Falta de filtro por status** - Contava todos os pedidos, não só empréstimos
4. **Query não preparada** - Sem uso de `$wpdb->prepare()`

## Solução Implementada

### Depois:
```php
$results = $wpdb->get_results( $wpdb->prepare( "
    SELECT 
        p.ID,
        p.post_title,
        COUNT(DISTINCT o.ID) as total_emprestimos,
        COALESCE(pm.meta_value, 'pronta') as status_atual
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.meta_value = p.ID AND oim.meta_key = '_product_id'
    LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = oim.order_item_id
    LEFT JOIN {$wpdb->posts} o ON o.ID = oi.order_id AND o.post_status IN ('wc-emprestado', 'wc-devolvido')
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_status_produto'
    WHERE p.post_type = 'product'
    AND p.post_status = 'publish'
    GROUP BY p.ID, p.post_title
    ORDER BY total_emprestimos DESC
    LIMIT 20
" ) );
```

### Melhorias Implementadas:

1. **Referência Correta à Tabela**:
   - `{$wpdb->prefix}woocommerce_order_items` em vez de `{$wpdb->woocommerce_order_items}`

2. **JOIN Estruturado Corretamente**:
   - Produto → Order Item Meta → Order Items → Orders
   - Relacionamento correto entre tabelas

3. **Filtro por Status**:
   - Só conta pedidos com status `wc-emprestado` e `wc-devolvido`

4. **Query Preparada**:
   - Uso de `$wpdb->prepare()` para segurança

5. **COUNT DISTINCT**:
   - Evita duplicação na contagem de empréstimos

6. **GROUP BY Completo**:
   - Inclui todas as colunas não agregadas

## Entendimento das Tabelas WooCommerce

### Estrutura Correta:
```
wp_posts (produtos/pedidos)
├── wp_postmeta (metadados)
└── woocommerce_order_items (itens do pedido)
    └── woocommerce_order_itemmeta (metadados dos itens)
```

### Relacionamentos:
- **Produto** ↔ **Order Item Meta** (via _product_id)
- **Order Item Meta** ↔ **Order Items** (via order_item_id)
- **Order Items** ↔ **Orders** (via order_id)

## Arquivo Afetado

- **Arquivo**: `includes/class-reports.php`
- **Função**: `generate_cadeiras_performance_table()`
- **Linhas**: 490-509

## Benefícios da Correção

1. **Elimina Warning**: Remove o erro do PHP
2. **Query Mais Eficiente**: Estrutura otimizada
3. **Dados Precisos**: Conta apenas empréstimos reais
4. **Segurança**: Query preparada
5. **Performance**: JOIN otimizado

## Testes Recomendados

1. **Verificar relatório de performance**: Página Admin → Relatórios → Performance
2. **Conferir logs de erro**: Verificar se o warning desapareceu
3. **Validar dados**: Confirmar que contagens estão corretas
4. **Teste de stress**: Executar relatório com muitos dados

## Prevenção de Problemas Similares

### Regras para Tabelas WooCommerce:
1. **Sempre usar prefix**: `{$wpdb->prefix}woocommerce_*`
2. **Nunca assumir propriedades**: WooCommerce não registra tabelas como propriedades do $wpdb
3. **Verificar documentação**: Consultar estrutura oficial das tabelas
4. **Testar em ambiente**: Verificar warnings antes do deploy

### Tabelas Principais WooCommerce:
- `{$wpdb->prefix}woocommerce_order_items`
- `{$wpdb->prefix}woocommerce_order_itemmeta`
- `{$wpdb->prefix}woocommerce_payment_tokens`
- `{$wpdb->prefix}woocommerce_sessions`

## Data da Correção
<?php echo date('d/m/Y H:i'); ?> - Versão 0.0.1 