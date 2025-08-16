# Correção da Redução Duplicada de Estoque

**Versão:** 0.0.1  
**Autor:** Juliano Ballarini  

## Problema Identificado

O estoque das cadeiras de rodas estava sendo reduzido duas vezes durante o processo de empréstimo, resultando em valores negativos incorretos.

### Comportamento Incorreto
- Estoque sendo reduzido no preenchimento do formulário
- Estoque sendo reduzido novamente na mudança de status
- Exemplo: Cadeira com estoque 1 → após empréstimo ficava com -1

## Causa Raiz

Identificamos duas funções realizando a redução de estoque:

1. **Formulário de Empréstimo** (`class-formularios.php`):
```php
// Reduzir estoque (apenas se ainda tem estoque)
$current_stock = $product->get_stock_quantity();
if ( $current_stock > 0 ) {
    $product->set_stock_quantity( $current_stock - 1 );
    $product->save();
}
```

2. **Gerenciador de Status** (`class-status-manager.php`):
```php
private function handle_emprestado_status( $order ) {
    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        $product = wc_get_product( $product_id );
        
        if ( $product ) {
            // Reduz estoque
            $product->reduce_stock( $item->get_quantity() );
        }
    }
}
```

## Solução Implementada

### 1. Remoção da Redução Duplicada

Removemos a redução de estoque do `handle_emprestado_status` em `class-status-manager.php`, mantendo apenas a redução que acontece no formulário de empréstimo.

**Motivos para manter a redução no formulário:**
1. Momento mais adequado (após confirmação do empréstimo)
2. Melhor controle de estoque (verifica se > 0)
3. Log mais detalhado da operação

### 2. Fluxo Correto de Estoque

Agora o fluxo de estoque funciona assim:
1. Checkout → Estoque não é alterado
2. Status "Aguardando" → Estoque não é alterado
3. Preenchimento do formulário → Estoque é reduzido UMA vez
4. Devolução → Estoque é aumentado após avaliação aprovada

## Testes Recomendados

1. **Empréstimo Normal**
   - Criar pedido de empréstimo
   - Preencher formulário
   - Verificar se estoque reduziu apenas 1 unidade

2. **Múltiplos Empréstimos**
   - Criar vários pedidos de empréstimo
   - Verificar se estoque está correto após cada um

3. **Devolução**
   - Devolver cadeira emprestada
   - Aprovar na avaliação
   - Verificar se estoque aumentou corretamente

## Logs de Debug

O sistema agora gera logs mais claros:
```
MovLiv: Estoque da cadeira {product_id} reduzido para X (apenas no formulário)
MovLiv: Produto {product_id} marcado como emprestado (apenas status)
``` 
