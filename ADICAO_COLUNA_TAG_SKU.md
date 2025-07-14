# Adição: Coluna TAG (SKU) na Performance das Cadeiras

## Funcionalidade Implementada

Adicionada nova coluna **"TAG"** na tabela de **Performance das Cadeiras** localizada em:
**WP Admin → Movimento Livre → Relatórios → Cadeiras → Performance das Cadeiras**

### Características da Coluna:
- **Nome**: TAG
- **Conteúdo**: SKU do produto (WooCommerce)
- **Posição**: Primeira coluna (antes de "Cadeira")
- **Formatação**: Texto em negrito
- **Fallback**: Exibe "-" se SKU estiver vazio

## Implementação Técnica

### 1. Modificação na Query SQL

#### Adições na SELECT:
```sql
SELECT 
    p.ID,
    p.post_title,
    COALESCE(pm_sku.meta_value, '') as sku,  -- ✅ NOVO
    COUNT(DISTINCT o.ID) as total_emprestimos,
    COALESCE(pm.meta_value, 'pronta') as status_atual
```

#### Novo JOIN para SKU:
```sql
LEFT JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
```

#### GROUP BY Atualizado:
```sql
GROUP BY p.ID, p.post_title, pm_sku.meta_value
```

### 2. Modificação na Estrutura da Tabela

#### Novo Cabeçalho:
```php
$html .= '<th>' . __( 'TAG', 'movimento-livre' ) . '</th>';      // ✅ NOVO
$html .= '<th>' . __( 'Cadeira', 'movimento-livre' ) . '</th>';
$html .= '<th>' . __( 'Total Empréstimos', 'movimento-livre' ) . '</th>';
// ...
```

#### Nova Coluna de Dados:
```php
$sku = ! empty( $result->sku ) ? $result->sku : '-';
$html .= '<td><strong>' . esc_html( $sku ) . '</strong></td>';   // ✅ NOVO
```

## Estrutura Final da Tabela

| Posição | Coluna | Conteúdo | Fonte |
|---------|--------|----------|--------|
| 1 | **TAG** | SKU do produto | `_sku` meta |
| 2 | Cadeira | Nome do produto | `post_title` |
| 3 | Total Empréstimos | Contador | Query agregada |
| 4 | Status Atual | Status do produto | `_status_produto` meta |
| 5 | Ações | Links de ação | Gerado dinamicamente |

## Código Implementado

### Query SQL Completa:
```sql
SELECT 
    p.ID,
    p.post_title,
    COALESCE(pm_sku.meta_value, '') as sku,
    COUNT(DISTINCT o.ID) as total_emprestimos,
    COALESCE(pm.meta_value, 'pronta') as status_atual
FROM {$wpdb->posts} p
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.meta_value = p.ID AND oim.meta_key = '_product_id'
LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = oim.order_item_id
LEFT JOIN {$wpdb->posts} o ON o.ID = oi.order_id AND o.post_status IN ('wc-emprestado', 'wc-devolvido')
LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_status_produto'
LEFT JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
WHERE p.post_type = 'product'
AND p.post_status = 'publish'
GROUP BY p.ID, p.post_title, pm_sku.meta_value
ORDER BY total_emprestimos DESC
LIMIT 20
```

### Renderização da Coluna:
```php
foreach ( $results as $result ) {
    $status_label = MOVLIV_Status_Manager::$product_statuses[ $result->status_atual ] ?? $result->status_atual;
    $sku = ! empty( $result->sku ) ? $result->sku : '-';
    
    $html .= '<tr>';
    $html .= '<td><strong>' . esc_html( $sku ) . '</strong></td>';
    $html .= '<td><a href="' . admin_url( 'post.php?post=' . $result->ID . '&action=edit' ) . '">' . esc_html( $result->post_title ) . '</a></td>';
    // ... outras colunas
    $html .= '</tr>';
}
```

## Benefícios da Funcionalidade

1. **Identificação Rápida**: SKU facilita identificação única dos produtos
2. **Gestão de Inventário**: Permite rastreamento baseado em códigos internos
3. **Integração**: Compatível com sistemas externos que usam SKU
4. **Organização**: Melhora organização visual da tabela
5. **Usabilidade**: Primeira coluna para referência rápida

## Casos de Uso

### 1. Gestão de Estoque:
- Identificar rapidamente qual cadeira teve mais empréstimos por SKU
- Correlacionar dados com sistemas de inventário externos

### 2. Relatórios Executivos:
- Apresentar dados de performance usando códigos únicos
- Facilitar comunicação entre equipes usando referências padrão

### 3. Auditoria:
- Rastrear histórico de empréstimos por identificador único
- Validar dados contra registros físicos

## Validação e Testes

### ✅ Cenários Testados:
1. **Produto com SKU definido**: Exibe SKU em negrito
2. **Produto sem SKU**: Exibe "-" como fallback
3. **Ordenação**: Mantém ordenação por total de empréstimos
4. **Performance**: Query otimizada com JOINs adequados
5. **Escape de dados**: SKU escapado com `esc_html()`

### 📊 Exemplo de Resultado:
```
| TAG     | Cadeira              | Total Empréstimos | Status Atual | Ações      |
|---------|---------------------|-------------------|--------------|------------|
| CAD-001 | Cadeira de Rodas A  | 15               | Emprestado   | Ver Detalhes |
| CAD-002 | Cadeira de Rodas B  | 12               | Pronta       | Ver Detalhes |
| -       | Cadeira sem SKU     | 8                | Em Avaliação | Ver Detalhes |
```

## Arquivo Modificado

- **Arquivo**: `includes/class-reports.php`
- **Função**: `generate_cadeiras_performance_table()`
- **Linhas**: 490-534
- **Tipo**: Melhoria funcional

## Compatibilidade

- **WooCommerce**: Utiliza meta_key padrão `_sku`
- **WordPress**: Compatível com estrutura de postmeta
- **Performance**: JOIN otimizado sem impacto significativo
- **Segurança**: Dados escapados adequadamente

## Considerações Futuras

1. **Ordenação por SKU**: Possibilidade de ordenar tabela por TAG
2. **Filtro por SKU**: Adicionar filtro de busca por SKU
3. **Exportação**: Incluir TAG em exportações CSV
4. **API**: Disponibilizar SKU em endpoints de relatórios

## Data da Implementação
<?php echo date('d/m/Y H:i'); ?> - Versão 0.0.1 