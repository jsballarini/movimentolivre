# Correção do Status Inicial dos Pedidos de Empréstimo

**Versão:** 0.0.1  
**Autor:** Juliano Ballarini  

## Problema Identificado

Pedidos de empréstimo estavam sendo criados automaticamente com o status "Processando" (processing) quando deveriam ser criados com o status "Aguardando" (on-hold).

### Comportamento Incorreto
- ❌ **Checkout** → Pedido criado com status "Processando"
- ❌ **Fluxo interrompido** → Usuário não passava pelo formulário de empréstimo
- ❌ **Status incorreto** → Sistema pulava a etapa de validação

### Comportamento Correto Esperado
- ✅ **Checkout** → Pedido criado com status "Aguardando"
- ✅ **Formulário preenchido** → Status alterado para "Processando"
- ✅ **Fluxo completo** → Validação e documentação adequadas

## Causa Raiz

O WooCommerce possui automatismos que definem o status de pedidos com base no valor e forma de pagamento:

1. **Pedidos gratuitos**: Automaticamente considerados "processados"
2. **Hook `woocommerce_payment_complete_order_status`**: Define status final
3. **Prioridade de execução**: Hooks do WooCommerce executando antes dos nossos

## Solução Implementada

### 1. Hook de Controle de Status

**Arquivo:** `includes/class-order-hooks.php`  
**Hook:** `woocommerce_payment_complete_order_status`  
**Função:** `prevent_auto_processing_for_loans()`

```php
public function prevent_auto_processing_for_loans( $status, $order_id, $order ) {
    // Verifica se é um empréstimo (valor zero com produtos)
    $is_loan = ( $order->get_total() == 0 );
    $has_products = count( $order->get_items() ) > 0;
    
    if ( $is_loan && $has_products ) {
        error_log( "MovLiv: Prevenindo auto-processing para empréstimo {$order_id} - mantendo status 'on-hold'" );
        return 'on-hold'; // Força status "Aguardando"
    }
    
    return $status; // Mantém status original para outros tipos de pedido
}
```

### 2. Definição de Status Inicial

**Arquivo:** `includes/class-order-hooks.php`  
**Hook:** `woocommerce_checkout_order_created`  
**Função:** `set_initial_loan_status()`

```php
public function set_initial_loan_status( $order ) {
    // Verifica se é um empréstimo (valor zero com produtos)
    $is_loan = ( $order->get_total() == 0 );
    $has_products = count( $order->get_items() ) > 0;
    
    if ( $is_loan && $has_products ) {
        // Define status inicial como "Aguardando"
        $order->update_status( 'on-hold', __( 'Empréstimo aguardando preenchimento do formulário de retirada.', 'movimento-livre' ) );
        
        // Marca como empréstimo do Movimento Livre
        $order->update_meta_data( '_is_movimento_livre', 'yes' );
        
        // Define data prevista de devolução (30 dias)
        $data_prevista = date( 'Y-m-d', strtotime( '+30 days' ) );
        $order->update_meta_data( '_data_prevista_devolucao', $data_prevista );
        
        // Adiciona nota automática
        $order->add_order_note( 
            __( 'Pedido criado como empréstimo com status "Aguardando". Aguardando preenchimento do formulário de retirada.', 'movimento-livre' ),
            false
        );
        
        $order->save();
        
        error_log( "MovLiv: Status inicial do pedido {$order->get_id()} definido como 'Aguardando' (empréstimo)" );
    }
}
```

### 3. Garantia de Status Correto

**Arquivo:** `includes/class-order-hooks.php`  
**Hook:** `woocommerce_checkout_order_processed`  
**Função:** `force_loan_status()`  
**Prioridade:** 999 (executa por último)

```php
public function force_loan_status( $order_id ) {
    $order = wc_get_order( $order_id );
    
    // Verifica se é um empréstimo (valor zero com produtos)
    $is_loan = ( $order->get_total() == 0 );
    $has_products = count( $order->get_items() ) > 0;
    
    if ( ! $is_loan || ! $has_products ) {
        return;
    }
    
    // Verifica se já está no status correto
    if ( $order->get_status() === 'on-hold' ) {
        return;
    }
    
    // ✅ FORÇA status "Aguardando" para empréstimos
    $order->update_status( 'on-hold', __( 'Empréstimo aguardando preenchimento do formulário de retirada.', 'movimento-livre' ) );
    
    // Marca como empréstimo do Movimento Livre
    update_post_meta( $order_id, '_is_movimento_livre', 'yes' );
    
    // Adiciona nota automática
    $order->add_order_note( 
        __( 'Status corrigido para "Aguardando" - empréstimo deve aguardar formulário antes de ser processado.', 'movimento-livre' ),
        false
    );
    
    error_log( "MovLiv: Status do pedido {$order_id} FORÇADO para 'Aguardando' (empréstimo gratuito)" );
}
```

### 4. Alteração no Formulário de Empréstimo

**Arquivo:** `includes/class-formularios.php`  
**Função:** `handle_emprestimo_form()`  
**Alteração:** Mantido o comportamento de alterar status para "Processando" após envio do formulário

```php
// Atualiza status do pedido para "Emprestado" (status nativo processing)
$order->update_status( 'processing', __( 'Formulário de empréstimo recebido. Cadeira emprestada.', 'movimento-livre' ) );
```

## Fluxo Correto Implementado

### 1. Checkout
- Usuário finaliza pedido de empréstimo (R$ 0,00)
- Sistema detecta empréstimo gratuito
- **Status definido:** "Aguardando" (on-hold)

### 2. Redirecionamento
- Usuário é redirecionado para formulário de empréstimo
- Sistema valida acesso com `order_key`
- Formulário exibido automaticamente

### 3. Preenchimento do Formulário
- Usuário preenche dados do empréstimo
- Sistema valida informações
- PDF gerado automaticamente

### 4. Finalização
- **Status alterado:** "Aguardando" → "Processando"
- Status das cadeiras atualizado
- Estoque reduzido
- Notificações enviadas

## Hooks Utilizados

| Hook | Prioridade | Função | Objetivo |
|------|-----------|---------|----------|
| `woocommerce_checkout_order_created` | 10 | `set_initial_loan_status()` | Define status inicial |
| `woocommerce_payment_complete_order_status` | 10 | `prevent_auto_processing_for_loans()` | Previne auto-processing |
| `woocommerce_checkout_order_processed` | 999 | `force_loan_status()` | Força status correto |

## Logs de Debug

O sistema agora gera logs específicos para rastreamento:

```
MovLiv: Status inicial do pedido 123 definido como 'Aguardando' (empréstimo)
MovLiv: Prevenindo auto-processing para empréstimo 123 - mantendo status 'on-hold'
MovLiv: Status do pedido 123 FORÇADO para 'Aguardando' (empréstimo gratuito)
```

## Validação

### Cenários Testados
1. **Empréstimo gratuito** → Status "Aguardando" ✅
2. **Formulário preenchido** → Status "Processando" ✅
3. **Pedido pago** → Status normal do WooCommerce ✅
4. **Pedido sem produtos** → Não afetado pelo plugin ✅

### Casos de Borda
- Pedidos com valor zero mas sem produtos
- Pedidos com produtos pagos
- Pedidos cancelados/reembolsados

## Compatibilidade

- **WooCommerce:** 8.0+
- **WordPress:** 6.0+
- **HPOS:** Compatível
- **Outros plugins:** Não interfere

## Resultado Final

✅ **Status inicial correto:** Pedidos de empréstimo criados com status "Aguardando"  
✅ **Fluxo respeitado:** Formulário obrigatório antes de processar  
✅ **Transição controlada:** "Aguardando" → "Processando" apenas após formulário  
✅ **Logs detalhados:** Rastreamento completo do processo  
✅ **Compatibilidade:** Não afeta outros tipos de pedido  

---

**Documentação gerada automaticamente pelo sistema Movimento Livre**  
*Instituto Bernardo Ferreira - Um Legado em Movimento* 
