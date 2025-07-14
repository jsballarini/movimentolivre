<?php
/**
 * Manipulador de Status de Produtos - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para manipular ações quando status de produtos mudam
 */
class MOVLIV_Product_Status_Handler {

    /**
     * Instância única da classe
     * @var MOVLIV_Product_Status_Handler
     */
    private static $instance = null;

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_Product_Status_Handler();
        }
        return self::$instance;
    }

    /**
     * Construtor privado
     */
    private function __construct() {
        add_action( 'init', array( $this, 'init_hooks' ) );
    }

    /**
     * Inicializa hooks
     */
    public function init_hooks() {
        // Hook quando status do produto muda
        add_action( 'movliv_product_status_changed', array( $this, 'handle_product_status_change' ), 10, 3 );
        
        // Hook para geração de formulário de avaliação
        add_action( 'movliv_gerar_formulario_avaliacao', array( $this, 'handle_avaliacao_generation' ), 10, 2 );
        
        // Adiciona coluna de status na lista de produtos
        add_filter( 'manage_edit-product_columns', array( $this, 'add_status_column_products_list' ) );
        add_action( 'manage_product_posts_custom_column', array( $this, 'display_status_column_content' ), 10, 2 );
        
        // Adiciona filtro por status na lista de produtos
        add_action( 'restrict_manage_posts', array( $this, 'add_status_filter_products' ) );
        add_filter( 'parse_query', array( $this, 'filter_products_by_status' ) );
    }

    /**
     * Manipula mudanças de status do produto
     */
    public function handle_product_status_change( $product_id, $old_status, $new_status ) {
        error_log( "MovLiv: Status do produto {$product_id} mudou de {$old_status} para {$new_status}" );

        switch ( $new_status ) {
            case 'pronta':
                $this->handle_pronta_status( $product_id, $old_status );
                break;
                
            case 'emprestado':
                $this->handle_emprestado_status( $product_id, $old_status );
                break;
                
            case 'em_avaliacao':
                $this->handle_em_avaliacao_status( $product_id, $old_status );
                break;
                
            case 'em_manutencao':
                $this->handle_em_manutencao_status( $product_id, $old_status );
                break;
        }
    }

    /**
     * Manipula status "pronta" - cadeira disponível para empréstimo
     */
    private function handle_pronta_status( $product_id, $old_status ) {
        $product = wc_get_product( $product_id );
        
        if ( $product && $old_status === 'em_avaliacao' ) {
            // Retorna cadeira ao estoque se veio de avaliação
            $product->set_stock_quantity( 1 );
            $product->set_stock_status( 'instock' );
            $product->save();
            
            error_log( "MovLiv: Produto {$product_id} retornou ao estoque após avaliação" );
            
            // Notifica administradores
            do_action( 'movliv_notify_cadeira_disponivel', $product_id );
        }
    }

    /**
     * Manipula status "emprestado"
     */
    private function handle_emprestado_status( $product_id, $old_status ) {
        // Lógica já implementada na classe Status_Manager quando pedido muda para emprestado
        error_log( "MovLiv: Produto {$product_id} marcado como emprestado" );
    }

    /**
     * Manipula status "em_avaliacao" - gera formulário de avaliação
     */
    private function handle_em_avaliacao_status( $product_id, $old_status ) {
        // Notifica avaliadores que há nova avaliação pendente
        do_action( 'movliv_notify_avaliacao_pendente', $product_id );
        
        error_log( "MovLiv: Produto {$product_id} em avaliação - notificações enviadas" );
    }

    /**
     * Manipula status "em_manutencao" - gera novo formulário de avaliação
     */
    private function handle_em_manutencao_status( $product_id, $old_status ) {
        // Agenda nova avaliação após manutenção
        $this->schedule_reavaluation( $product_id );
        
        error_log( "MovLiv: Produto {$product_id} em manutenção - reavaliação agendada" );
    }

    /**
     * Agenda reavaliação de produto
     */
    private function schedule_reavaluation( $product_id ) {
        // Adiciona meta indicando que precisa de reavaliação
        update_post_meta( $product_id, '_precisa_reavaliacao', 'sim' );
        update_post_meta( $product_id, '_data_manutencao', current_time( 'mysql' ) );
        
        // Notifica equipe técnica
        do_action( 'movliv_notify_reavaliacacao_necessaria', $product_id );
    }

    /**
     * Manipula geração de formulário de avaliação
     */
    public function handle_avaliacao_generation( $product_id, $order_id = null ) {
        // Marca que precisa de avaliação
        update_post_meta( $product_id, '_precisa_avaliacao', 'sim' );
        update_post_meta( $product_id, '_order_devolucao', $order_id );
        update_post_meta( $product_id, '_data_devolucao', current_time( 'mysql' ) );
        
        error_log( "MovLiv: Formulário de avaliação gerado para produto {$product_id}" );
    }

    /**
     * Adiciona coluna de status na lista de produtos
     */
    public function add_status_column_products_list( $columns ) {
        $new_columns = array();
        
        foreach ( $columns as $key => $column ) {
            $new_columns[ $key ] = $column;
            
            if ( $key === 'name' ) {
                $new_columns['movliv_status'] = __( 'Status da Cadeira', 'movimento-livre' );
            }
        }
        
        return $new_columns;
    }

    /**
     * Exibe conteúdo da coluna de status
     */
    public function display_status_column_content( $column, $product_id ) {
        if ( $column === 'movliv_status' ) {
            $status = MOVLIV_Status_Manager::get_product_status( $product_id );
            $label = MOVLIV_Status_Manager::get_product_status_label( $status );
            
            // Define cores para cada status
            $colors = array(
                'pronta' => '#28a745',
                'emprestado' => '#007bff',
                'em_avaliacao' => '#ffc107',
                'em_manutencao' => '#dc3545'
            );
            
            $color = isset( $colors[ $status ] ) ? $colors[ $status ] : '#6c757d';
            
            printf(
                '<span style="background: %s; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">%s</span>',
                esc_attr( $color ),
                esc_html( $label )
            );
            
            // Mostra se precisa de avaliação
            if ( get_post_meta( $product_id, '_precisa_avaliacao', true ) === 'sim' ) {
                echo '<br><small style="color: #dc3545;">⚠️ Avaliação Pendente</small>';
            }
            
            // Mostra se precisa de reavaliação
            if ( get_post_meta( $product_id, '_precisa_reavaliacao', true ) === 'sim' ) {
                echo '<br><small style="color: #dc3545;">🔧 Reavaliação Necessária</small>';
            }
        }
    }

    /**
     * Adiciona filtro por status na lista de produtos
     */
    public function add_status_filter_products() {
        global $typenow;
        
        if ( $typenow === 'product' ) {
            $selected = isset( $_GET['movliv_status_filter'] ) ? $_GET['movliv_status_filter'] : '';
            
            echo '<select name="movliv_status_filter">';
            echo '<option value="">' . __( 'Todos os Status', 'movimento-livre' ) . '</option>';
            
            foreach ( MOVLIV_Status_Manager::$product_statuses as $status => $label ) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr( $status ),
                    selected( $selected, $status, false ),
                    esc_html( $label )
                );
            }
            
            echo '</select>';
        }
    }

    /**
     * Filtra produtos por status
     */
    public function filter_products_by_status( $query ) {
        global $pagenow, $typenow;
        
        if ( $pagenow === 'edit.php' && $typenow === 'product' && isset( $_GET['movliv_status_filter'] ) && ! empty( $_GET['movliv_status_filter'] ) ) {
            $meta_query = array(
                array(
                    'key' => '_status_produto',
                    'value' => sanitize_text_field( $_GET['movliv_status_filter'] ),
                    'compare' => '='
                )
            );
            
            $query->set( 'meta_query', $meta_query );
        }
    }

    /**
     * Obtém produtos que precisam de avaliação
     */
    public function get_products_pending_evaluation() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_precisa_avaliacao',
                    'value' => 'sim',
                    'compare' => '='
                )
            )
        );
        
        return get_posts( $args );
    }

    /**
     * Obtém produtos que precisam de reavaliação
     */
    public function get_products_pending_reevaluation() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_precisa_reavaliacao',
                    'value' => 'sim',
                    'compare' => '='
                )
            )
        );
        
        return get_posts( $args );
    }

    /**
     * Marca produto como avaliado
     */
    public function mark_product_evaluated( $product_id ) {
        delete_post_meta( $product_id, '_precisa_avaliacao' );
        delete_post_meta( $product_id, '_precisa_reavaliacao' );
        update_post_meta( $product_id, '_ultima_avaliacao', current_time( 'mysql' ) );
        
        error_log( "MovLiv: Produto {$product_id} marcado como avaliado" );
    }

    /**
     * ✅ NOVO: Cria avaliação pendente após devolução
     */
    public function create_pending_evaluation( $product_id, $order_id = null ) {
        // Marca que precisa de avaliação
        update_post_meta( $product_id, '_precisa_avaliacao', 'sim' );
        update_post_meta( $product_id, '_data_devolucao', current_time( 'mysql' ) );
        
        if ( $order_id ) {
            update_post_meta( $product_id, '_order_devolucao', $order_id );
        }
        
        // Notifica avaliadores
        do_action( 'movliv_notify_avaliacao_pendente', $product_id );
        
        error_log( "MovLiv: Avaliação pendente criada para produto {$product_id}" );
        
        return true;
    }

    /**
     * ✅ NOVO: Cria reavaliação pendente após reprovação em manutenção
     */
    public function create_pending_reevaluation( $product_id ) {
        // Marca que precisa de reavaliação
        update_post_meta( $product_id, '_precisa_reavaliacao', 'sim' );
        update_post_meta( $product_id, '_data_entrada_manutencao', current_time( 'mysql' ) );
        
        // Remove avaliação anterior se existir
        delete_post_meta( $product_id, '_precisa_avaliacao' );
        
        // Notifica equipe técnica
        do_action( 'movliv_notify_reavaliacacao_necessaria', $product_id );
        
        error_log( "MovLiv: Reavaliação pendente criada para produto {$product_id}" );
        
        return true;
    }

    /**
     * Obtém estatísticas de status dos produtos
     */
    public function get_status_statistics() {
        $stats = array();
        
        foreach ( MOVLIV_Status_Manager::$product_statuses as $status => $label ) {
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_status_produto',
                        'value' => $status,
                        'compare' => '='
                    )
                )
            );
            
            $products = get_posts( $args );
            $stats[ $status ] = array(
                'label' => $label,
                'count' => count( $products )
            );
        }
        
        return $stats;
    }
} 