# Implementação do Fluxo de Checkout para Empréstimos Gratuitos

**Data:** 15/07/2025  
**Versão:** 0.0.1  
**Autor:** Juliano Ballarini  

## Resumo

Implementação completa do fluxo de checkout para empréstimos gratuitos de cadeiras de rodas, onde o usuário não precisa efetuar pagamento, o pedido é automaticamente processado com status "Aguardando" e o usuário é redirecionado para o formulário de empréstimo.

## Problema Identificado

O fluxo anterior não estava otimizado para empréstimos gratuitos:
- Usuários eram direcionados para gateways de pagamento desnecessariamente
- Não havia redirecionamento automático para o formulário de empréstimo
- Processo complexo e confuso para o usuário final

## Solução Implementada

### 1. Bypass do Gateway de Pagamento

**Arquivo:** `includes/class-order-hooks.php`  
**Método:** `disable_payment_for_free_loans()`

```php
public function disable_payment_for_free_loans( $needs_payment, $cart ) {
    // Verifica se é um pedido de empréstimo (valor zero)
    if ( $cart && $cart->get_total( 'edit' ) == 0 ) {
        // Verifica se tem produtos (cadeiras) no carrinho
        $has_products = false;
        foreach ( $cart->get_cart() as $cart_item ) {
            if ( isset( $cart_item['product_id'] ) ) {
                $has_products = true;
                break;
            }
        }
        
        if ( $has_products ) {
            return false; // Não precisa de pagamento
        }
    }
    
    return $needs_payment;
}
```

**Hook utilizado:** `woocommerce_cart_needs_payment`

### 2. Processamento Automático de Pedidos Gratuitos

**Arquivo:** `includes/class-order-hooks.php`  
**Método:** `process_free_orders()`

```php
public function process_free_orders() {
    // Só processa se for empréstimo gratuito
    $cart_total = WC()->cart->get_total( 'edit' );
    
    if ( $cart_total == 0 ) {
        // Verifica se tem CPF (obrigatório para empréstimos)
        $cpf = isset( $_POST['billing_cpf'] ) ? sanitize_text_field( $_POST['billing_cpf'] ) : '';
        
        if ( empty( $cpf ) ) {
            wc_add_notice( __( 'CPF é obrigatório para empréstimos de cadeiras de rodas.', 'movimento-livre' ), 'error' );
            return;
        }
        
        error_log( "MovLiv: Processando pedido gratuito de empréstimo para CPF: " . $cpf );
    }
}
```

**Hook utilizado:** `woocommerce_checkout_process`

### 3. Redirecionamento Automático

**Arquivo:** `includes/class-order-hooks.php`  
**Método:** `redirect_to_loan_form()`

```php
public function redirect_to_loan_form( $order_id ) {
    // Verifica se é um empréstimo (pedido gratuito com produtos)
    $is_loan = ( $order->get_total() == 0 );
    $has_products = count( $order->get_items() ) > 0;
    
    if ( ! $is_loan || ! $has_products ) {
        return; // Não é empréstimo
    }
    
    // Cria URL do formulário de empréstimo
    $form_url = add_query_arg( array(
        'movliv_action' => 'form_emprestimo',
        'order_id' => $order_id,
        'order_key' => $order->get_order_key()
    ), home_url() );
    
    // JavaScript para redirecionamento
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function() {
        setTimeout(function() {
            window.location.href = "<?php echo esc_url( $form_url ); ?>";
        }, 2000); // Aguarda 2 segundos
    });
    </script>
    <?php
}
```

**Hook utilizado:** `woocommerce_thankyou`

### 4. Exibição Automática do Formulário

**Arquivo:** `includes/class-shortcodes.php`  
**Método:** `auto_display_emprestimo_form()`

O sistema detecta automaticamente os parâmetros URL e substitui o conteúdo da página pelo formulário de empréstimo:

```php
public function auto_display_emprestimo_form() {
    // Verifica se tem parâmetros do plugin
    if ( ! isset( $_GET['movliv_action'] ) || $_GET['movliv_action'] !== 'form_emprestimo' ) {
        return;
    }
    
    $order_id = intval( $_GET['order_id'] ?? 0 );
    $order_key = sanitize_text_field( $_GET['order_key'] ?? '' );
    
    // Verifica se o pedido existe e a chave está correta
    $order = wc_get_order( $order_id );
    if ( ! $order || $order->get_order_key() !== $order_key ) {
        return;
    }
    
    // Adiciona filtro para exibir o formulário
    add_filter( 'the_content', array( $this, 'replace_content_with_emprestimo_form' ) );
    add_filter( 'the_title', array( $this, 'replace_title_with_emprestimo_title' ) );
}
```

### 5. Correção do CPF nos Formulários

**Arquivo:** `includes/class-formularios.php`  
**Método:** `get_user_cpf_from_order()`

```php
private function get_user_cpf_from_order( $order_id ) {
    $order = wc_get_order( $order_id );
    
    if ( ! $order ) {
        return '';
    }
    
    // Busca o CPF do usuário através do post_author -> usermeta
    $user_id = $order->get_user_id();
    
    if ( $user_id ) {
        $cpf = get_user_meta( $user_id, 'billing_cpf', true );
        if ( ! empty( $cpf ) ) {
            return $cpf;
        }
    }
    
    // Fallback: busca nas meta do pedido (compatibilidade)
    $cpf = get_post_meta( $order_id, '_cpf_solicitante', true );
    if ( ! empty( $cpf ) ) {
        return $cpf;
    }
    
    return '';
}
```

## Fluxo Completo do Usuário

### 1. Carrinho de Compras
- Usuário adiciona cadeira de rodas (valor R$ 0,00)
- Vai para o checkout

### 2. Checkout
- Preenche dados obrigatórios (incluindo CPF)
- Sistema detecta valor zero
- Bypass do gateway de pagamento
- Processamento automático

### 3. Confirmação
- Pedido criado com status "Aguardando"
- Mensagem de sucesso exibida
- Redirecionamento automático em 2 segundos

### 4. Formulário de Empréstimo
- Página substituída automaticamente
- Formulário pré-preenchido com dados do pedido
- Usuário completa informações específicas do empréstimo

### 5. Após Envio do Formulário
- PDF gerado automaticamente
- Status do pedido atualizado para "Emprestado"
- Status das cadeiras atualizado
- Notificações enviadas

## Validações de Segurança

### 1. Verificação de CPF
- CPF obrigatório para empréstimos
- Validação no frontend (JavaScript)
- Validação no backend (PHP)

### 2. Verificação de Pedido
- Uso de `order_key` para segurança
- Verificação de existência do pedido
- Prevenção de acesso não autorizado

### 3. Verificação de Status
- Apenas pedidos "Aguardando" podem acessar o formulário
- Prevenção de duplicação de formulários
- Verificação de metadados específicos

## Hooks Utilizados

| Hook | Prioridade | Função |
|------|-----------|---------|
| `woocommerce_cart_needs_payment` | 10 | Desabilita pagamento |
| `woocommerce_checkout_process` | 5 | Valida pedidos gratuitos |
| `woocommerce_thankyou` | 5 | Redireciona para formulário |
| `woocommerce_new_order` | 10,20 | Configura pedido inicial |
| `wp` | - | Detecta formulário na URL |

## Logs de Debug

O sistema gera logs detalhados em todas as etapas:

```
MovLiv: Pedido gratuito de empréstimo detectado - desabilitando gateway de pagamento
MovLiv: Processando pedido gratuito de empréstimo para CPF: 12345678901
MovLiv: Pedido 123 configurado como empréstimo com status Aguardando
MovLiv: Redirecionando pedido 123 para formulário: https://site.com/?movliv_action=form_emprestimo&order_id=123&order_key=wc_order_abc
```

## Testes Recomendados

### 1. Teste de Checkout Gratuito
- [ ] Adicionar cadeira ao carrinho
- [ ] Verificar bypass do pagamento
- [ ] Confirmar redirecionamento automático

### 2. Teste de Formulário
- [ ] Preenchimento correto dos campos
- [ ] Validação de campos obrigatórios
- [ ] Geração de PDF
- [ ] Atualização de status

### 3. Teste de Segurança
- [ ] Tentar acessar formulário com order_key inválido
- [ ] Verificar se CPF é obrigatório
- [ ] Confirmar que apenas pedidos "Aguardando" funcionam

## Compatibilidade

- **WooCommerce:** 8.0+
- **WordPress:** 6.0+
- **PHP:** 7.4+
- **Plugins:** WooCommerce + Plugin Brazilian Market

## Próximos Passos

1. **Testes em Produção:** Validar fluxo completo
2. **Otimização:** Melhorar performance do redirecionamento
3. **UX:** Adicionar indicadores visuais de progresso
4. **Notificações:** Implementar emails específicos para cada etapa

## Considerações Técnicas

### Performance
- Hooks executados apenas quando necessário
- Validações otimizadas para evitar consultas desnecessárias
- Logs condicionais para debug

### Manutenibilidade
- Código modular e bem documentado
- Hooks separados por responsabilidade
- Fallbacks para compatibilidade

### Segurança
- Sanitização de todos os inputs
- Verificação de nonce em formulários
- Validação de permissões e chaves

---

**Documentação gerada automaticamente pelo sistema Movimento Livre**  
*Instituto Bernardo Ferreira - Um Legado em Movimento* 
