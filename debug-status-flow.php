<?php
/**
 * Debug do Fluxo de Status - Movimento Livre
 * 
 * Este arquivo pode ser usado para testar e debugar o fluxo de status dos pedidos.
 * Coloque-o na raiz do WordPress e acesse via navegador para ver os logs.
 */

// Carrega WordPress
require_once( dirname( __FILE__ ) . '/wp-load.php' );

// Verifica se é admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Acesso negado' );
}

echo '<h1>Debug do Fluxo de Status - Movimento Livre</h1>';

// Verifica se o plugin está ativo
if ( ! class_exists( 'MovimentoLivre' ) ) {
    echo '<p style="color: red;">Plugin Movimento Livre não está ativo!</p>';
    exit;
}

echo '<h2>Status do Plugin</h2>';
echo '<p>Plugin ativo: <strong>Sim</strong></p>';
echo '<p>Versão: <strong>' . MOVLIV_VERSION . '</strong></p>';

// Verifica hooks registrados
echo '<h2>Hooks Registrados</h2>';

// Verifica se os hooks estão registrados
global $wp_filter;

$hooks_to_check = array(
    'woocommerce_checkout_order_created',
    'woocommerce_checkout_order_processed',
    'woocommerce_new_order',
    'woocommerce_order_status_changed',
    'woocommerce_payment_complete_order_status'
);

foreach ( $hooks_to_check as $hook ) {
    if ( isset( $wp_filter[ $hook ] ) ) {
        echo "<p>✅ Hook <code>{$hook}</code> está registrado</p>";
        
        // Mostra as funções registradas
        foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $callback ) {
                if ( is_array( $callback['function'] ) && is_object( $callback['function'][0] ) ) {
                    $class_name = get_class( $callback['function'][0] );
                    echo "<p style='margin-left: 20px;'>- Prioridade {$priority}: {$class_name}::{$callback['function'][1]}</p>";
                }
            }
        }
    } else {
        echo "<p>❌ Hook <code>{$hook}</code> NÃO está registrado</p>";
    }
}

// Verifica pedidos recentes
echo '<h2>Pedidos Recentes</h2>';

$orders = wc_get_orders( array(
    'limit' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
) );

if ( empty( $orders ) ) {
    echo '<p>Nenhum pedido encontrado.</p>';
} else {
    echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Status</th>';
    echo '<th>Total</th>';
    echo '<th>Produtos</th>';
    echo '<th>Meta MovLiv</th>';
    echo '<th>Formulário</th>';
    echo '</tr>';
    
    foreach ( $orders as $order ) {
        $order_id = $order->get_id();
        $status = $order->get_status();
        $total = $order->get_total();
        $items = $order->get_items();
        $is_movliv = get_post_meta( $order_id, '_is_movimento_livre', true );
        $has_form = get_post_meta( $order_id, '_formulario_emprestimo_pdf', true ) || 
                   get_post_meta( $order_id, '_form_emprestimo_pdf', true );
        
        echo '<tr>';
        echo '<td>' . $order_id . '</td>';
        echo '<td>' . $status . '</td>';
        echo '<td>' . $total . '</td>';
        echo '<td>' . count( $items ) . '</td>';
        echo '<td>' . ( $is_movliv ? 'Sim' : 'Não' ) . '</td>';
        echo '<td>' . ( $has_form ? 'Sim' : 'Não' ) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
}

// Verifica logs de erro
echo '<h2>Logs de Erro Recentes</h2>';

$log_file = WP_CONTENT_DIR . '/debug.log';
if ( file_exists( $log_file ) ) {
    $logs = file( $log_file );
    $movliv_logs = array();
    
    // Filtra logs do Movimento Livre
    foreach ( $logs as $log ) {
        if ( strpos( $log, 'MovLiv:' ) !== false ) {
            $movliv_logs[] = $log;
        }
    }
    
    if ( empty( $movliv_logs ) ) {
        echo '<p>Nenhum log do Movimento Livre encontrado.</p>';
    } else {
        echo '<p>Últimos logs do Movimento Livre:</p>';
        echo '<pre style="background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto;">';
        
        // Mostra os últimos 20 logs
        $recent_logs = array_slice( $movliv_logs, -20 );
        foreach ( $recent_logs as $log ) {
            echo htmlspecialchars( $log );
        }
        
        echo '</pre>';
    }
} else {
    echo '<p>Arquivo de log não encontrado.</p>';
}

// Botão para limpar logs
echo '<h2>Ações</h2>';
echo '<form method="post">';
echo '<input type="submit" name="clear_logs" value="Limpar Logs" style="background: #dc3232; color: white; padding: 10px 20px; border: none; cursor: pointer;">';
echo '</form>';

// Processa limpeza de logs
if ( isset( $_POST['clear_logs'] ) ) {
    if ( file_exists( $log_file ) ) {
        file_put_contents( $log_file, '' );
        echo '<p style="color: green;">Logs limpos com sucesso!</p>';
        echo '<script>location.reload();</script>';
    }
}

echo '<hr>';
echo '<p><em>Este arquivo deve ser removido após o debug.</em></p>';
?>
