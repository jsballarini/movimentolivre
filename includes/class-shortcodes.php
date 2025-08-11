<?php
/**
 * Gerenciador de Shortcodes - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para gerenciar shortcodes do sistema
 */
class MOVLIV_Shortcodes {

    /**
     * Instância única da classe
     * @var MOVLIV_Shortcodes
     */
    private static $instance = null;

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_Shortcodes();
        }
        return self::$instance;
    }

    /**
     * Construtor privado
     */
    private function __construct() {
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        
        // Detecta e exibe formulário automaticamente baseado em parâmetros URL
        add_action( 'wp', array( $this, 'auto_display_emprestimo_form' ) );
    }

    /**
     * Registra todos os shortcodes
     */
    public function register_shortcodes() {
        add_shortcode( 'movliv_form_emprestimo', array( $this, 'shortcode_form_emprestimo' ) );
        add_shortcode( 'movliv_form_devolucao', array( $this, 'shortcode_form_devolucao' ) );
        add_shortcode( 'movliv_form_avaliacao', array( $this, 'shortcode_form_avaliacao' ) );
        add_shortcode( 'movliv_formularios_produto', array( $this, 'shortcode_formularios_produto' ) );
        add_shortcode( 'movliv_historico_cpf', array( $this, 'shortcode_historico_cpf' ) );
        add_shortcode( 'movliv_lista_cadeiras', array( $this, 'shortcode_lista_cadeiras' ) );
        add_shortcode( 'movliv_avaliacoes_pendentes', array( $this, 'shortcode_avaliacoes_pendentes' ) );
        add_shortcode( 'movliv_dashboard', array( $this, 'shortcode_dashboard' ) );
        add_shortcode( 'movliv_debug_status', array( $this, 'shortcode_debug_status' ) );

    }

    /**
     * Shortcode para formulário de empréstimo
     * [movliv_form_emprestimo pedido_id="123"]
     */
    public function shortcode_form_emprestimo( $atts ) {
        $atts = shortcode_atts( array(
            'pedido_id' => 0
        ), $atts, 'movliv_form_emprestimo' );

        // ✅ CORREÇÃO: Verifica parâmetros da URL primeiro
        $order_id = 0;
        
        // Se tem parâmetros na URL, usa eles
        if ( isset( $_GET['order_id'] ) && isset( $_GET['order_key'] ) ) {
            $order_id = intval( $_GET['order_id'] );
            $order_key = sanitize_text_field( $_GET['order_key'] );
            
            // Verifica se o pedido existe e a chave está correta
            $order = wc_get_order( $order_id );
            if ( ! $order || $order->get_order_key() !== $order_key ) {
                return '<div class="woocommerce-error">' . 
                    __( 'Pedido não encontrado ou chave inválida.', 'movimento-livre' ) . 
                    '</div>';
            }
        } 
        // Se não tem parâmetros na URL, usa o atributo do shortcode
        else {
            $order_id = intval( $atts['pedido_id'] );
        }
        
        if ( ! $order_id ) {
            return '<div class="woocommerce-error">' . 
                __( 'ID do pedido é obrigatório.', 'movimento-livre' ) . 
                '</div>';
        }

        $formularios = MOVLIV_Formularios::getInstance();
        return $formularios->get_emprestimo_form_html( $order_id );
    }

    public function shortcode_form_devolucao( $atts ) {
        $atts = shortcode_atts( array(
            'pedido_id' => 0,
        ), $atts, 'movliv_form_devolucao' );
    
        $order_id = function_exists( 'absint' ) ? absint( $atts['pedido_id'] ) : intval( $atts['pedido_id'] );
    
        // 1) Tenta pelos endpoints padrão do WooCommerce
        if ( ! $order_id ) {
            foreach ( array( 'view-order', 'order-pay', 'order-received' ) as $ep ) {
                $maybe = function_exists( 'absint' ) ? absint( get_query_var( $ep ) ) : intval( get_query_var( $ep ) );
                if ( $maybe ) { $order_id = $maybe; break; }
            }
        }
    
        // 2) Tenta pela order key (?key=wc_order_...)
        $resolved_by_key = false;
        if ( ! $order_id && isset( $_GET['key'] ) ) {
            $key = function_exists( 'wc_clean' ) ? wc_clean( function_exists( 'wp_unslash' ) ? wp_unslash( $_GET['key'] ) : stripslashes( $_GET['key'] ) ) : sanitize_text_field( function_exists( 'wp_unslash' ) ? wp_unslash( $_GET['key'] ) : stripslashes( $_GET['key'] ) );
            $maybe = function_exists( 'wc_get_order_id_by_order_key' ) ? wc_get_order_id_by_order_key( $key ) : $this->get_order_id_by_key_fallback( $key );
            if ( $maybe ) {
                $order_id = function_exists( 'absint' ) ? absint( $maybe ) : intval( $maybe );
                $resolved_by_key = true;
            }
        }
    
        // 3) Parâmetros de conveniência (?pedido=123, ?order_id=123, ?order=123)
        if ( ! $order_id ) {
            foreach ( array( 'pedido', 'order_id', 'order' ) as $param ) {
                if ( isset( $_GET[ $param ] ) ) {
                    $order_id = function_exists( 'absint' ) ? absint( $_GET[ $param ] ) : intval( $_GET[ $param ] );
                    if ( $order_id ) break;
                }
            }
        }
    
        // (Opcional) 4) Suporte a número sequencial, se você usar plugin específico
        // Ex.: ?order_number=2025-000123 (ajuste a integração conforme o plugin que você usa)
        /*
        if ( ! $order_id && isset( $_GET['order_number'] ) && function_exists( 'wc_sequential_order_numbers' ) ) {
            $seq = wc_clean( wp_unslash( $_GET['order_number'] ) );
            $maybe = wc_sequential_order_numbers()->find_order_by_order_number( $seq );
            if ( $maybe ) $order_id = absint( $maybe );
        }
        */
    
        if ( ! $order_id ) {
            return $this->get_cadeiras_emprestadas_list();
        }
    
        $order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
        if ( ! $order ) {
            return $this->get_cadeiras_emprestadas_list();
        }
    
        // Segurança: só o dono do pedido (ou admin). Se veio por "key", valida a key.
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            if ( $resolved_by_key ) {
                $provided_key = isset( $key ) ? $key : ( function_exists( 'wc_clean' ) ? wc_clean( function_exists( 'wp_unslash' ) ? wp_unslash( $_GET['key'] ?? '' ) : stripslashes( $_GET['key'] ?? '' ) ) : sanitize_text_field( function_exists( 'wp_unslash' ) ? wp_unslash( $_GET['key'] ?? '' ) : stripslashes( $_GET['key'] ?? '' ) ) );
                if ( $order->get_order_key() !== $provided_key ) {
                    return '<p>' . ( function_exists( 'esc_html__' ) ? esc_html__( 'Chave do pedido inválida.', 'movimento-livre' ) : __( 'Chave do pedido inválida.', 'movimento-livre' ) ) . '</p>';
                }
            } else {
                $user_id = get_current_user_id();
                if ( ! $user_id ) {
                    return '<p>' . ( function_exists( 'esc_html__' ) ? esc_html__( 'Faça login para acessar este pedido.', 'movimento-livre' ) : __( 'Faça login para acessar este pedido.', 'movimento-livre' ) ) . '</p>';
                }
                if ( (int) $order->get_user_id() !== (int) $user_id ) {
                    return '<p>' . ( function_exists( 'esc_html__' ) ? esc_html__( 'Este pedido não pertence à sua conta.', 'movimento-livre' ) : __( 'Este pedido não pertence à sua conta.', 'movimento-livre' ) ) . '</p>';
                }
            }
        }
    
        $formularios = MOVLIV_Formularios::getInstance();
        return $formularios->get_devolucao_form_html( $order_id );
    }

    /**
     * Lista cadeiras emprestadas do usuário atual
     */
    private function get_cadeiras_emprestadas_list() {
        $user_id = get_current_user_id();
        
        if ( ! $user_id ) {
            return '<div class="movliv-error">' . 
                '<p>' . ( function_exists( 'esc_html__' ) ? esc_html__( 'Faça login para visualizar suas cadeiras emprestadas.', 'movimento-livre' ) : __( 'Faça login para visualizar suas cadeiras emprestadas.', 'movimento-livre' ) ) . '</p>' .
                '<p><a href="' . esc_url( get_permalink( function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'myaccount' ) : get_option( 'woocommerce_myaccount_page_id' ) ) ) . '" class="button">' . 
                ( function_exists( 'esc_html__' ) ? esc_html__( 'Fazer Login', 'movimento-livre' ) : __( 'Fazer Login', 'movimento-livre' ) ) . '</a></p>' .
                '</div>';
        }

        // Verifica se é um administrador
        $is_admin = current_user_can( 'manage_woocommerce' ) || current_user_can( 'administrator' );
        
        // Busca pedidos emprestados
        $args = array(
            'status' => array( 'processing' ), // Status "Emprestado"
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Se não for admin, busca apenas pedidos do usuário atual
        if ( ! $is_admin ) {
            $args['customer_id'] = $user_id;
        }

        // Verifica se a função wc_get_orders está disponível (WooCommerce 3.0+)
        if ( function_exists( 'wc_get_orders' ) ) {
            $orders = wc_get_orders( $args );
        } else {
            // Fallback para versões antigas do WooCommerce
            $orders = $this->get_orders_fallback( $user_id );
        }
        
        if ( empty( $orders ) ) {
            if ( $is_admin ) {
                return '<div class="movliv-info">' . 
                    '<p>' . ( function_exists( 'esc_html__' ) ? esc_html__( 'Não há cadeiras emprestadas no momento.', 'movimento-livre' ) : __( 'Não há cadeiras emprestadas no momento.', 'movimento-livre' ) ) . '</p>' .
                    '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=shop_order' ) ) . '" class="button">' . 
                    ( function_exists( 'esc_html__' ) ? esc_html__( 'Ver Todos os Pedidos', 'movimento-livre' ) : __( 'Ver Todos os Pedidos', 'movimento-livre' ) ) . '</a></p>' .
                    '</div>';
            } else {
                return '<div class="movliv-info">' . 
                    '<p>' . ( function_exists( 'esc_html__' ) ? esc_html__( 'Você não possui cadeiras emprestadas no momento.', 'movimento-livre' ) : __( 'Você não possui cadeiras emprestadas no momento.', 'movimento-livre' ) ) . '</p>' .
                    '<p><a href="' . esc_url( get_permalink( function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : get_option( 'woocommerce_shop_page_id' ) ) ) . '" class="button">' . 
                    ( function_exists( 'esc_html__' ) ? esc_html__( 'Solicitar Cadeira', 'movimento-livre' ) : __( 'Solicitar Cadeira', 'movimento-livre' ) ) . '</a></p>' .
                    '</div>';
            }
        }

        ob_start();
        ?>
        <div class="movliv-cadeiras-emprestadas">
            <h3><?php 
                if ( $is_admin ) {
                    ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Todas as Cadeiras Emprestadas', 'movimento-livre' ) : _e( 'Todas as Cadeiras Emprestadas', 'movimento-livre' ) );
                } else {
                    ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Suas Cadeiras Emprestadas', 'movimento-livre' ) : _e( 'Suas Cadeiras Emprestadas', 'movimento-livre' ) );
                }
            ?></h3>
            <p><?php 
                if ( $is_admin ) {
                    ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Selecione uma cadeira para processar devolução:', 'movimento-livre' ) : _e( 'Selecione uma cadeira para processar devolução:', 'movimento-livre' ) );
                } else {
                    ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Selecione uma cadeira para devolver:', 'movimento-livre' ) : _e( 'Selecione uma cadeira para devolver:', 'movimento-livre' ) );
                }
            ?></p>
            
            <div class="movliv-cadeiras-grid">
                <?php foreach ( $orders as $order ) : ?>
                    <?php 
                    $data_emprestimo = $order->get_date_created();
                    $data_prevista = get_post_meta( $order->get_id(), '_data_prevista_devolucao', true );
                    $items = $order->get_items();
                    ?>
                    
                    <?php foreach ( $items as $item ) : ?>
                        <?php 
                        $product = function_exists( 'wc_get_product' ) ? $item->get_product() : null;
                        if ( ! $product ) continue;
                        ?>
                        
                        <div class="movliv-cadeira-item">
                            <div class="cadeira-info">
                                <h4><?php echo esc_html( $product->get_name() ); ?></h4>
                                <p class="cadeira-tag">
                                    <strong><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'TAG:', 'movimento-livre' ) : _e( 'TAG:', 'movimento-livre' ) ); ?></strong> 
                                    <?php echo esc_html( $product->get_sku() ); ?>
                                </p>
                                <p class="pedido-info">
                                    <strong><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Pedido:', 'movimento-livre' ) : _e( 'Pedido:', 'movimento-livre' ) ); ?></strong> 
                                    #<?php echo esc_html( $order->get_order_number() ); ?>
                                </p>
                                <?php if ( $is_admin ) : ?>
                                <p class="cliente-info">
                                    <strong><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Cliente:', 'movimento-livre' ) : _e( 'Cliente:', 'movimento-livre' ) ); ?></strong> 
                                    <?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?>
                                    (<?php echo esc_html( $order->get_billing_email() ); ?>)
                                </p>
                                <?php endif; ?>
                                <p class="data-emprestimo">
                                    <strong><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Data do Empréstimo:', 'movimento-livre' ) : _e( 'Data do Empréstimo:', 'movimento-livre' ) ); ?></strong> 
                                    <?php echo esc_html( $data_emprestimo ? ( function_exists( 'date_i18n' ) ? $data_emprestimo->date_i18n( 'd/m/Y' ) : $data_emprestimo->format( 'd/m/Y' ) ) : 'N/A' ); ?>
                                </p>
                                <?php if ( $data_prevista ) : ?>
                                    <p class="data-prevista">
                                        <strong><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Data Prevista Devolução:', 'movimento-livre' ) : _e( 'Data Prevista Devolução:', 'movimento-livre' ) ); ?></strong> 
                                        <?php echo esc_html( function_exists( 'date_i18n' ) ? date_i18n( 'd/m/Y', strtotime( $data_prevista ) ) : date( 'd/m/Y', strtotime( $data_prevista ) ) ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cadeira-actions">
                                <a href="?pedido=<?php echo esc_attr( $order->get_id() ); ?>" 
                                   class="button button-primary">
                                    <?php 
                                        if ( $is_admin ) {
                                            ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Processar Devolução', 'movimento-livre' ) : _e( 'Processar Devolução', 'movimento-livre' ) );
                                        } else {
                                            ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Devolver Cadeira', 'movimento-livre' ) : _e( 'Devolver Cadeira', 'movimento-livre' ) );
                                        }
                                    ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="movliv-info-extra">
                <p><strong><?php 
                    if ( $is_admin ) {
                        ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Como processar devolução:', 'movimento-livre' ) : _e( 'Como processar devolução:', 'movimento-livre' ) );
                    } else {
                        ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Como devolver:', 'movimento-livre' ) : _e( 'Como devolver:', 'movimento-livre' ) );
                    }
                ?></strong></p>
                <ol>
                    <?php if ( $is_admin ) : ?>
                        <li><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Clique em "Processar Devolução" na cadeira desejada', 'movimento-livre' ) : _e( 'Clique em "Processar Devolução" na cadeira desejada', 'movimento-livre' ) ); ?></li>
                        <li><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Preencha o formulário de devolução em nome do cliente', 'movimento-livre' ) : _e( 'Preencha o formulário de devolução em nome do cliente', 'movimento-livre' ) ); ?></li>
                        <li><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Aguarde a avaliação técnica', 'movimento-livre' ) : _e( 'Aguarde a avaliação técnica', 'movimento-livre' ) ); ?></li>
                    <?php else : ?>
                        <li><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Clique em "Devolver Cadeira" na cadeira desejada', 'movimento-livre' ) : _e( 'Clique em "Devolver Cadeira" na cadeira desejada', 'movimento-livre' ) ); ?></li>
                        <li><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Preencha o formulário de devolução', 'movimento-livre' ) : _e( 'Preencha o formulário de devolução', 'movimento-livre' ) ); ?></li>
                        <li><?php ( function_exists( 'esc_html_e' ) ? esc_html_e( 'Aguarde a avaliação técnica', 'movimento-livre' ) : _e( 'Aguarde a avaliação técnica', 'movimento-livre' ) ); ?></li>
                    <?php endif; ?>
                </ol>
            </div>
        </div>
        
        <style>
        .movliv-cadeiras-emprestadas {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .movliv-cadeiras-emprestadas h3 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .movliv-cadeiras-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .movliv-cadeira-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .cadeira-info h4 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
        }
        
        .cadeira-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .cadeira-tag {
            background: #e7f3ff;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .cliente-info {
            background: #fff3cd;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            border-left: 3px solid #ffc107;
        }
        
        .cadeira-actions .button {
            background: #2c5aa0;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        
        .cadeira-actions .button:hover {
            background: #1e3f6b;
            color: white;
        }
        
        .movliv-info-extra {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2c5aa0;
        }
        
        .movliv-info-extra ol {
            margin: 10px 0 0 20px;
        }
        
        .movliv-info-extra li {
            margin-bottom: 8px;
        }
        
        .movliv-error, .movliv-info {
            text-align: center;
            padding: 30px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .movliv-error {
            border-color: #dc3232;
            background: #fef7f7;
        }
        
        .movliv-info {
            border-color: #46b450;
            background: #f7fef7;
        }
        
        @media (max-width: 768px) {
            .movliv-cadeira-item {
                flex-direction: column;
                text-align: center;
            }
            
            .cadeira-actions {
                width: 100%;
            }
            
            .cadeira-actions .button {
                width: 100%;
                text-align: center;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Fallback para buscar pedidos em versões antigas do WooCommerce
     */
    private function get_orders_fallback( $user_id ) {
        global $wpdb;
        
        $orders = array();
        
        // Verifica se é um administrador
        $is_admin = current_user_can( 'manage_woocommerce' ) || current_user_can( 'administrator' );
        
        if ( $is_admin ) {
            // Para administradores, busca todos os pedidos com status 'processing'
            $query = "
                SELECT p.ID, p.post_date, p.post_status
                FROM {$wpdb->posts} p
                WHERE p.post_type = 'shop_order'
                AND p.post_status = 'wc-processing'
                ORDER BY p.post_date DESC
            ";
        } else {
            // Para usuários comuns, busca apenas pedidos do usuário
            $query = $wpdb->prepare( "
                SELECT p.ID, p.post_date, p.post_status
                FROM {$wpdb->posts} p
                WHERE p.post_type = 'shop_order'
                AND p.post_status = 'wc-processing'
                AND p.post_author = %d
                ORDER BY p.post_date DESC
            ", $user_id );
        }
        
        $order_ids = $wpdb->get_col( $query );
        
        foreach ( $order_ids as $order_id ) {
            $order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
            if ( $order ) {
                $orders[] = $order;
            }
        }
        
        return $orders;
    }

    /**
     * Fallback para buscar ID do pedido pela chave em versões antigas do WooCommerce
     */
    private function get_order_id_by_key_fallback( $order_key ) {
        global $wpdb;
        
        $query = $wpdb->prepare( "
            SELECT post_id
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_order_key'
            AND meta_value = %s
            LIMIT 1
        ", $order_key );
        
        return $wpdb->get_var( $query );
    }
    

    /**
     * Shortcode para formulário de avaliação
     * [movliv_form_avaliacao produto_id="456"]
     */
    public function shortcode_form_avaliacao( $atts ) {
        $atts = shortcode_atts( array(
            'produto_id' => 0
        ), $atts, 'movliv_form_avaliacao' );

        $product_id = intval( $atts['produto_id'] );
        
        // Verifica permissões - usuários com role de Colaborador ou superior podem acessar
        if ( ! MOVLIV_Permissions::can_submit_evaluations() ) {
            return '<p>' . __( 'Você não tem permissão para acessar esta página. Apenas usuários com role de Colaborador ou superior podem realizar avaliações técnicas.', 'movimento-livre' ) . '</p>';
        }

        // Se não foi fornecido produto_id, mostra lista de cadeiras que precisam ser avaliadas
        if ( ! $product_id ) {
            return $this->get_cadeiras_para_avaliacao_list();
        }

        $formularios = MOVLIV_Formularios::getInstance();
        return $formularios->get_avaliacao_form_html( $product_id );
    }

    /**
     * Obtém lista de cadeiras que precisam ser avaliadas
     */
    private function get_cadeiras_para_avaliacao_list() {
        // Verifica se o usuário está logado
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Você precisa estar logado para acessar esta página.', 'movimento-livre' ) . '</p>';
        }

        // Verifica se uma avaliação foi completada
        $avaliacao_completed = isset( $_GET['avaliacao_completed'] ) && $_GET['avaliacao_completed'] === '1';
        
        $handler = MOVLIV_Product_Status_Handler::getInstance();
        $products_avaliacao = $handler->get_products_pending_evaluation();
        $products_reavaliacao = $handler->get_products_pending_reevaluation();

        // ✅ CORREÇÃO: Busca alternativa por status para cadeiras em manutenção
        $products_manutencao_por_status = $this->get_products_by_status( 'em_manutencao' );
        
        // Debug para desenvolvimento
        if ( current_user_can( 'administrator' ) ) {
            error_log( "MovLiv Debug - Cadeiras que precisam de avaliação: " . count( $products_avaliacao ) );
            error_log( "MovLiv Debug - Cadeiras que precisam de reavaliação: " . count( $products_reavaliacao ) );
            error_log( "MovLiv Debug - Cadeiras com status 'em_manutencao': " . count( $products_manutencao_por_status ) );
        }

        ob_start();
        ?>
        <div class="movliv-cadeiras-avaliacao">
            <?php if ( $avaliacao_completed ) : ?>
                <div class="avaliacao-success" style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 15px; margin-bottom: 20px; color: #155724;">
                    <h4 style="margin: 0 0 10px 0; color: #155724;">✅ <?php _e( 'Avaliação Concluída com Sucesso!', 'movimento-livre' ); ?></h4>
                    <p style="margin: 0; color: #155724;">
                        <?php _e( 'A avaliação técnica foi enviada e processada. A cadeira foi atualizada conforme o resultado da avaliação.', 'movimento-livre' ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <h3><?php _e( 'Cadeiras que Precisam de Avaliação Técnica', 'movimento-livre' ); ?></h3>
            
            <?php if ( ! empty( $products_avaliacao ) ) : ?>
                <div class="avaliacao-section">
                    <h4><?php _e( '🔄 Cadeiras Devolvidas - Aguardando Avaliação', 'movimento-livre' ); ?></h4>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'TAG/SKU', 'movimento-livre' ); ?></th>
                                <th><?php _e( 'Modelo', 'movimento-livre' ); ?></th>
                                <th><?php _e( 'Data da Devolução', 'movimento-livre' ); ?></th>
                                <th><?php _e( 'Ação', 'movimento-livre' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $products_avaliacao as $post ) : 
                                $product = function_exists( 'wc_get_product' ) ? wc_get_product( $post->ID ) : null;
                                if ( ! $product ) continue;
                                
                                $data_devolucao = get_post_meta( $post->ID, '_data_devolucao', true );
                                $order_id = get_post_meta( $post->ID, '_order_devolucao', true );
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $product->get_sku() ); ?></strong></td>
                                    <td><?php echo esc_html( $product->get_name() ); ?></td>
                                    <td>
                                        <?php 
                                        if ( $data_devolucao ) {
                                            echo esc_html( function_exists( 'date_i18n' ) ? date_i18n( 'd/m/Y H:i', strtotime( $data_devolucao ) ) : date( 'd/m/Y H:i', strtotime( $data_devolucao ) ) );
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url( add_query_arg( 'avaliar_produto', $post->ID ) ); ?>" class="button button-primary">
                                            <?php _e( 'Avaliar Cadeira', 'movimento-livre' ); ?>
                                        </a>
                                        <?php if ( $order_id ) : ?>
                                            <br><small><?php printf( __( 'Pedido #%s', 'movimento-livre' ), esc_html( $order_id ) ); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $products_reavaliacao ) || ! empty( $products_manutencao_por_status ) ) : ?>
                <div class="reavaliacao-section" style="margin-top: 30px;">
                    <h4><?php _e( '🔧 Cadeiras em Manutenção - Aguardando Reavaliação', 'movimento-livre' ); ?></h4>
                    
                    <?php 
                    // Combina as duas fontes de dados
                    $todas_manutencao = array_merge( $products_reavaliacao, $products_manutencao_por_status );
                    // Remove duplicatas
                    $todas_manutencao = array_unique( $todas_manutencao, SORT_REGULAR );
                    ?>
                    
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'TAG/SKU', 'movimento-livre' ); ?></th>
                                <th><?php _e( 'Modelo', 'movimento-livre' ); ?></th>
                                <th><?php _e( 'Data de Entrada em Manutenção', 'movimento-livre' ); ?></th>
                                <th><?php _e( 'Status', 'movimento-livre' ); ?></th>
                                <th><?php _e( 'Ação', 'movimento-livre' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $todas_manutencao as $post ) : 
                                $product = function_exists( 'wc_get_product' ) ? wc_get_product( $post->ID ) : null;
                                if ( ! $product ) continue;
                                
                                $data_manutencao = get_post_meta( $post->ID, '_data_entrada_manutencao', true );
                                $data_manutencao_alt = get_post_meta( $post->ID, '_data_manutencao', true );
                                $status_produto = get_post_meta( $post->ID, '_status_produto', true );
                                
                                // Usa a data mais recente ou disponível
                                $data_final = $data_manutencao ?: $data_manutencao_alt;
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $product->get_sku() ); ?></strong></td>
                                    <td><strong><?php echo esc_html( $product->get_name() ); ?></strong></td>
                                    <td>
                                        <?php 
                                        if ( $data_final ) {
                                            echo esc_html( function_exists( 'date_i18n' ) ? date_i18n( 'd/m/Y H:i', strtotime( $data_final ) ) : date( 'd/m/Y H:i', strtotime( $data_final ) ) );
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                            <?php echo esc_html( $status_produto ?: 'Em Manutenção' ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url( add_query_arg( 'avaliar_produto', $post->ID ) ); ?>" class="button button-primary">
                                            <?php _e( 'Reavaliar Cadeira', 'movimento-livre' ); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if ( empty( $products_avaliacao ) && empty( $products_reavaliacao ) && empty( $products_manutencao_por_status ) ) : ?>
                <div class="no-avaliacoes" style="text-align: center; padding: 40px 20px; background: #f9f9f9; border-radius: 8px;">
                    <p style="font-size: 18px; color: #666; margin-bottom: 10px;">🎉</p>
                    <p style="font-size: 16px; color: #333; margin: 0;">
                        <?php _e( 'Nenhuma cadeira precisa de avaliação no momento!', 'movimento-livre' ); ?>
                    </p>
                    <p style="font-size: 14px; color: #666; margin-top: 10px;">
                        <?php _e( 'Todas as cadeiras devolvidas já foram avaliadas e estão prontas para uso.', 'movimento-livre' ); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if ( isset( $_GET['avaliar_produto'] ) ) : ?>
                <div style="margin-top: 30px; border-top: 2px solid #ddd; padding-top: 20px;">
                    <h4><?php _e( 'Formulário de Avaliação Técnica', 'movimento-livre' ); ?></h4>
                    <?php echo do_shortcode( '[movliv_form_avaliacao produto_id="' . intval( $_GET['avaliar_produto'] ) . '"]' ); ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .movliv-cadeiras-avaliacao {
            max-width: 100%;
            margin: 20px 0;
        }
        .avaliacao-success {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .movliv-cadeiras-avaliacao h3 {
            color: #333;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .movliv-cadeiras-avaliacao h4 {
            color: #555;
            margin: 20px 0 15px 0;
            padding: 10px;
            background: #f5f5f5;
            border-left: 4px solid #0073aa;
        }
        .avaliacao-section h4 {
            border-left-color: #ffc107;
        }
        .reavaliacao-section h4 {
            border-left-color: #dc3545;
        }
        .movliv-cadeiras-avaliacao table {
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .movliv-cadeiras-avaliacao th {
            background: #f8f9fa;
            font-weight: 600;
            padding: 12px 8px;
        }
        .movliv-cadeiras-avaliacao td {
            padding: 10px 8px;
            vertical-align: middle;
        }
        .movliv-cadeiras-avaliacao .button {
            margin: 2px;
        }
        .movliv-cadeiras-avaliacao small {
            color: #666;
            font-style: italic;
        }
        .no-avaliacoes {
            border: 1px solid #e0e0e0;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * ✅ NOVO: Busca produtos por status específico
     */
    private function get_products_by_status( $status ) {
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
        
        return get_posts( $args );
    }

    /**
     * Shortcode para listar formulários de um produto
     * [movliv_formularios_produto produto_id="456"]
     */
    public function shortcode_formularios_produto( $atts ) {
        $atts = shortcode_atts( array(
            'produto_id' => 0
        ), $atts, 'movliv_formularios_produto' );

        $product_id = intval( $atts['produto_id'] );
        
        if ( ! $product_id ) {
            return '<p>' . __( 'ID do produto é obrigatório.', 'movimento-livre' ) . '</p>';
        }

        // Verifica permissões
        if ( ! MOVLIV_Permissions::can_view_cadeiras() ) {
            return '<p>' . __( 'Você não tem permissão para acessar esta informação.', 'movimento-livre' ) . '</p>';
        }

        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
        if ( ! $product ) {
            return '<p>' . __( 'Produto não encontrado.', 'movimento-livre' ) . '</p>';
        }

        $avaliacoes = get_post_meta( $product_id, '_avaliacoes_produto', true );
        
        if ( empty( $avaliacoes ) || ! is_array( $avaliacoes ) ) {
            return '<p>' . __( 'Nenhuma avaliação encontrada para esta cadeira.', 'movimento-livre' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="movliv-formularios-produto">
            <h3><?php printf( __( 'Histórico de Avaliações - %s (TAG: %s)', 'movimento-livre' ), 
                esc_html( $product->get_name() ), 
                esc_html( $product->get_sku() ) 
            ); ?></h3>
            
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Data', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Avaliador', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Resultado', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Observações', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Documento', 'movimento-livre' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( array_reverse( $avaliacoes ) as $avaliacao ) : ?>
                        <tr>
                            <td><?php echo esc_html( date( 'd/m/Y H:i', strtotime( $avaliacao['data'] ?? '' ) ) ); ?></td>
                            <td><?php echo esc_html( $avaliacao['avaliador'] ?? '' ); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr( strtolower( $avaliacao['resultado'] ?? '' ) ); ?>">
                                    <?php echo esc_html( $avaliacao['resultado'] ?? '' ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $avaliacao['observacoes'] ?? '' ); ?></td>
                            <td>
                                <?php if ( ! empty( $avaliacao['pdf_path'] ) && file_exists( $avaliacao['pdf_path'] ) ) : ?>
                                    <a href="<?php echo esc_url( wp_nonce_url( 
                                        admin_url( 'admin-ajax.php?action=movliv_download_pdf&file=' . basename( $avaliacao['pdf_path'] ) ), 
                                        'download_pdf' 
                                    ) ); ?>" class="button button-small">
                                        <?php _e( 'Download PDF', 'movimento-livre' ); ?>
                                    </a>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }
        .status-aprovada { background: #28a745; }
        .status-reprovada { background: #dc3545; }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode para histórico de empréstimos por CPF
     * [movliv_historico_cpf cpf="12345678900"]
     */
    public function shortcode_historico_cpf( $atts ) {
        $atts = shortcode_atts( array(
            'cpf' => ''
        ), $atts, 'movliv_historico_cpf' );

        $cpf = sanitize_text_field( $atts['cpf'] );
        
        if ( empty( $cpf ) ) {
            return '<p>' . __( 'CPF é obrigatório.', 'movimento-livre' ) . '</p>';
        }

        // Verifica permissões ou se é o próprio usuário
        if ( ! MOVLIV_Permissions::can_view_orders() ) {
            return '<p>' . __( 'Você não tem permissão para acessar esta informação.', 'movimento-livre' ) . '</p>';
        }

        $cpf_validator = MOVLIV_CPF_Validator::getInstance();
        $orders = $cpf_validator->get_orders_by_cpf( $cpf );
        
        if ( empty( $orders ) ) {
            return '<p>' . __( 'Nenhum empréstimo encontrado para este CPF.', 'movimento-livre' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="movliv-historico-cpf">
            <h3><?php printf( __( 'Histórico de Empréstimos - CPF: %s', 'movimento-livre' ), 
                esc_html( $cpf_validator->format_cpf( $cpf ) ) 
            ); ?></h3>
            
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Pedido', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Cadeira', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Data Pedido', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Status', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Data Devolução Prevista', 'movimento-livre' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $orders as $post ) : 
                        $order = wc_get_order( $post->ID );
                        if ( ! $order ) continue;
                        
                        $data_prevista = get_post_meta( $post->ID, '_data_prevista_devolucao', true );
                        ?>
                        <tr>
                            <td>#<?php echo esc_html( $order->get_id() ); ?></td>
                            <td>
                                <?php 
                                foreach ( $order->get_items() as $item ) {
                                    $product = function_exists( 'wc_get_product' ) ? $item->get_product() : null;
                                    if ( $product ) {
                                        echo esc_html( $product->get_sku() );
                                        break;
                                    }
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html( $order->get_date_created()->date( 'd/m/Y' ) ); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr( $order->get_status() ); ?>">
                                    <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ( $data_prevista ) {
                                    $today = date( 'Y-m-d' );
                                    $color = $data_prevista < $today ? 'red' : 'green';
                                    printf(
                                        '<span style="color: %s;">%s</span>',
                                        $color,
                                        date( 'd/m/Y', strtotime( $data_prevista ) )
                                    );
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode para listar cadeiras disponíveis
     * [movliv_lista_cadeiras]
     */
    public function shortcode_lista_cadeiras( $atts ) {
        global $wpdb;

        // Busca cadeiras prontas com estoque disponível
        $results = $wpdb->get_results("
            SELECT 
                p.ID,
                p.post_title as nome,
                MIN(pm_sku.meta_value) as sku,
                COUNT(*) as total_disponiveis
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = '_status_produto'
            LEFT JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
            LEFT JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock'
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND (pm_status.meta_value = 'pronta' OR (pm_status.meta_value IS NULL))
            AND (pm_stock.meta_value > 0 OR pm_stock.meta_value IS NULL)
            GROUP BY p.post_title
            HAVING total_disponiveis > 0
            ORDER BY p.post_title ASC
        ");

        if ( empty( $results ) ) {
            return '<p>' . __( 'Nenhuma cadeira disponível no momento.', 'movimento-livre' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="movliv-lista-cadeiras">
            <h3><?php _e( 'Cadeiras Disponíveis', 'movimento-livre' ); ?></h3>
            
            <div class="cadeiras-grid">
                <?php foreach ( $results as $item ) : 
                    $product = wc_get_product( $item->ID );
                    if ( ! $product || ! $product->is_in_stock() ) continue;
                    ?>
                    <div class="cadeira-item">
                        <div class="cadeira-info">
                            <h4><?php echo esc_html( $item->nome ); ?></h4>
                            <p class="disponibilidade">
                                <strong><?php _e( 'Disponíveis:', 'movimento-livre' ); ?></strong> 
                                <?php 
                                printf(
                                    _n( '%d unidade', '%d unidades', $item->total_disponiveis, 'movimento-livre' ),
                                    $item->total_disponiveis
                                );
                                ?>
                            </p>
                            
                            <?php if ( $product->get_image_id() ) : ?>
                                <div class="cadeira-image">
                                    <?php echo wp_get_attachment_image( $product->get_image_id(), 'thumbnail' ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( $product->get_description() ) : ?>
                                <div class="cadeira-description">
                                    <?php echo wp_kses_post( $product->get_short_description() ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="cadeira-action">
                                <?php 
                                // Redireciona direto para a página de checkout adicionando o produto ao carrinho
                                $checkout_url = wc_get_checkout_url();
                                $checkout_add_url = add_query_arg( 'add-to-cart', $product->get_id(), $checkout_url );
                                ?>
                                <a href="<?php echo esc_url( $checkout_add_url ); ?>" class="button">
                                    <?php _e( 'Solicitar Empréstimo', 'movimento-livre' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .cadeiras-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .cadeira-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
        }
        .cadeira-item h4 {
            margin-top: 0;
            color: #333;
        }
        .cadeira-image {
            margin: 10px 0;
        }
        .cadeira-description {
            margin: 10px 0;
            color: #666;
        }
        .cadeira-action {
            margin-top: 15px;
        }
        .disponibilidade {
            color: #28a745;
            font-size: 0.9em;
            margin: 5px 0;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode para listar avaliações pendentes
     * [movliv_avaliacoes_pendentes]
     */
    public function shortcode_avaliacoes_pendentes( $atts ) {
        // Verifica permissões
        if ( ! MOVLIV_Permissions::can_submit_evaluations() ) {
            return '<p>' . __( 'Você não tem permissão para acessar esta página.', 'movimento-livre' ) . '</p>';
        }

        $handler = MOVLIV_Product_Status_Handler::getInstance();
        $products_avaliacao = $handler->get_products_pending_evaluation();
        $products_reavaliacao = $handler->get_products_pending_reevaluation();

        ob_start();
        ?>
        <div class="movliv-avaliacoes-pendentes">
            <h3><?php _e( 'Avaliações Pendentes', 'movimento-livre' ); ?></h3>
            
            <?php if ( ! empty( $products_avaliacao ) ) : ?>
                <h4><?php _e( 'Cadeiras para Primeira Avaliação', 'movimento-livre' ); ?></h4>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'TAG', 'movimento-livre' ); ?></th>
                            <th><?php _e( 'Modelo', 'movimento-livre' ); ?></th>
                            <th><?php _e( 'Data Devolução', 'movimento-livre' ); ?></th>
                            <th><?php _e( 'Ação', 'movimento-livre' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $products_avaliacao as $post ) : 
                            $product = wc_get_product( $post->ID );
                            if ( ! $product ) continue;
                            
                            $data_devolucao = get_post_meta( $post->ID, '_data_devolucao', true );
                            ?>
                            <tr>
                                <td><?php echo esc_html( $product->get_sku() ); ?></td>
                                <td><?php echo esc_html( $product->get_name() ); ?></td>
                                <td>
                                    <?php 
                                    if ( $data_devolucao ) {
                                        echo esc_html( date( 'd/m/Y H:i', strtotime( $data_devolucao ) ) );
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( add_query_arg( 'avaliar_produto', $post->ID ) ); ?>" class="button button-primary">
                                        <?php _e( 'Avaliar', 'movimento-livre' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <?php if ( ! empty( $products_reavaliacao ) ) : ?>
                <h4><?php _e( 'Cadeiras para Reavaliação (Pós-Manutenção)', 'movimento-livre' ); ?></h4>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'TAG', 'movimento-livre' ); ?></th>
                            <th><?php _e( 'Modelo', 'movimento-livre' ); ?></th>
                            <th><?php _e( 'Data Manutenção', 'movimento-livre' ); ?></th>
                            <th><?php _e( 'Ação', 'movimento-livre' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $products_reavaliacao as $post ) : 
                            $product = wc_get_product( $post->ID );
                            if ( ! $product ) continue;
                            
                            $data_manutencao = get_post_meta( $post->ID, '_data_manutencao', true );
                            ?>
                            <tr>
                                <td><?php echo esc_html( $product->get_sku() ); ?></td>
                                <td><?php echo esc_html( $product->get_name() ); ?></td>
                                <td>
                                    <?php 
                                    if ( $data_manutencao ) {
                                        echo esc_html( date( 'd/m/Y H:i', strtotime( $data_manutencao ) ) );
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( add_query_arg( 'avaliar_produto', $post->ID ) ); ?>" class="button button-primary">
                                        <?php _e( 'Reavaliar', 'movimento-livre' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <?php if ( empty( $products_avaliacao ) && empty( $products_reavaliacao ) ) : ?>
                <p><?php _e( 'Nenhuma avaliação pendente no momento.', 'movimento-livre' ); ?></p>
            <?php endif; ?>
            
            <?php if ( isset( $_GET['avaliar_produto'] ) ) : ?>
                <div style="margin-top: 30px; border-top: 2px solid #ddd; padding-top: 20px;">
                    <?php echo do_shortcode( '[movliv_form_avaliacao produto_id="' . intval( $_GET['avaliar_produto'] ) . '"]' ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode para dashboard resumido
     * [movliv_dashboard]
     */
    public function shortcode_dashboard( $atts ) {
        // Verifica permissões
        if ( ! MOVLIV_Permissions::can_view_reports() ) {
            return '<p>' . __( 'Você não tem permissão para acessar esta página.', 'movimento-livre' ) . '</p>';
        }

        $handler = MOVLIV_Product_Status_Handler::getInstance();
        $stats = $handler->get_status_statistics();
        
        // Estatísticas de empréstimos
        $emprestimos_ativos = wp_count_posts( 'shop_order' );
        $aguardando = $emprestimos_ativos->{'wc-on-hold'} ?? 0;
        $emprestado = $emprestimos_ativos->{'wc-processing'} ?? 0;
        $devolvido = $emprestimos_ativos->{'wc-completed'} ?? 0;

        ob_start();
        ?>
        <div class="movliv-dashboard">
            <h3><?php _e( 'Dashboard - Movimento Livre', 'movimento-livre' ); ?></h3>
            
            <div class="dashboard-grid">
                <div class="dashboard-section">
                    <h4><?php _e( 'Status das Cadeiras', 'movimento-livre' ); ?></h4>
                    <div class="stats-grid">
                        <?php foreach ( $stats as $status => $data ) : ?>
                            <div class="stat-item status-<?php echo esc_attr( $status ); ?>">
                                <div class="stat-number"><?php echo esc_html( $data['count'] ); ?></div>
                                <div class="stat-label"><?php echo esc_html( $data['label'] ); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="dashboard-section">
                    <h4><?php _e( 'Status dos Empréstimos', 'movimento-livre' ); ?></h4>
                    <div class="stats-grid">
                        <div class="stat-item status-aguardando">
                            <div class="stat-number"><?php echo esc_html( $aguardando ); ?></div>
                            <div class="stat-label"><?php _e( 'Aguardando', 'movimento-livre' ); ?></div>
                        </div>
                        <div class="stat-item status-emprestado">
                            <div class="stat-number"><?php echo esc_html( $emprestado ); ?></div>
                            <div class="stat-label"><?php _e( 'Emprestado', 'movimento-livre' ); ?></div>
                        </div>
                        <div class="stat-item status-devolvido">
                            <div class="stat-number"><?php echo esc_html( $devolvido ); ?></div>
                            <div class="stat-label"><?php _e( 'Devolvido', 'movimento-livre' ); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-actions">
                <h4><?php _e( 'Ações Rápidas', 'movimento-livre' ); ?></h4>
                <div class="actions-grid">
                    <a href="<?php echo admin_url( 'edit.php?post_type=product' ); ?>" class="action-button">
                        <?php _e( 'Gerenciar Cadeiras', 'movimento-livre' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'edit.php?post_type=shop_order' ); ?>" class="action-button">
                        <?php _e( 'Gerenciar Empréstimos', 'movimento-livre' ); ?>
                    </a>
                    <?php if ( MOVLIV_Permissions::can_submit_evaluations() ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'page', 'avaliacoes' ) ); ?>" class="action-button">
                            <?php _e( 'Avaliações Pendentes', 'movimento-livre' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 20px 0;
        }
        .dashboard-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            border-radius: 6px;
            color: white;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
        }
        .status-pronta { background: #28a745; }
        .status-emprestado, .status-emprestado { background: #007bff; }
        .status-em_avaliacao, .status-aguardando { background: #ffc107; color: #333; }
        .status-em_manutencao { background: #dc3545; }
        .status-devolvido { background: #28a745; }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .action-button {
            display: block;
            text-align: center;
            padding: 15px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
        }
        .action-button:hover {
            background: #005a87;
            color: white;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * ✅ NOVO: Shortcode de debug para administradores
     * [movliv_debug_status]
     */
    public function shortcode_debug_status( $atts ) {
        // Apenas administradores podem usar
        if ( ! current_user_can( 'administrator' ) ) {
            return '<p>' . __( 'Acesso negado. Apenas administradores podem usar este shortcode.', 'movimento-livre' ) . '</p>';
        }

        $handler = MOVLIV_Product_Status_Handler::getInstance();
        $products_avaliacao = $handler->get_products_pending_evaluation();
        $products_reavaliacao = $handler->get_products_pending_reevaluation();
        
        // Busca por status
        $products_em_avaliacao = $this->get_products_by_status( 'em_avaliacao' );
        $products_em_manutencao = $this->get_products_by_status( 'em_manutencao' );
        $products_pronta = $this->get_products_by_status( 'pronta' );
        $products_emprestado = $this->get_products_by_status( 'emprestado' );

        ob_start();
        ?>
        <div class="movliv-debug-status" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3>🔍 Debug de Status das Cadeiras (Movimento Livre)</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
                    <h4>✅ Pronta</h4>
                    <p style="font-size: 24px; margin: 0; color: #28a745;"><?php echo count( $products_pronta ); ?></p>
                </div>
                
                <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff;">
                    <h4>🔵 Emprestado</h4>
                    <p style="font-size: 24px; margin: 0; color: #007bff;"><?php echo count( $products_emprestado ); ?></p>
                </div>
                
                <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
                    <h4>🟡 Em Avaliação</h4>
                    <p style="font-size: 24px; margin: 0; color: #ffc107;"><?php echo count( $products_em_avaliacao ); ?></p>
                </div>
                
                <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;">
                    <h4>🔴 Em Manutenção</h4>
                    <p style="font-size: 24px; margin: 0; color: #dc3545;"><?php echo count( $products_em_manutencao ); ?></p>
                </div>
            </div>

            <h4>📊 Metas de Avaliação:</h4>
            <ul style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <li><strong>_precisa_avaliacao = 'sim':</strong> <?php echo count( $products_avaliacao ); ?> produtos</li>
                <li><strong>_precisa_reavaliacao = 'sim':</strong> <?php echo count( $products_reavaliacao ); ?> produtos</li>
            </ul>

            <?php if ( ! empty( $products_em_manutencao ) ) : ?>
                <h4>🔧 Cadeiras com Status "Em Manutenção":</h4>
                <table class="wp-list-table widefat striped" style="background: white; border-radius: 5px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>TAG/SKU</th>
                            <th>Nome</th>
                            <th>Status</th>
                            <th>Meta _precisa_reavaliacao</th>
                            <th>Meta _data_entrada_manutencao</th>
                            <th>Meta _data_manutencao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $products_em_manutencao as $post ) : 
                            $product = function_exists( 'wc_get_product' ) ? wc_get_product( $post->ID ) : null;
                            if ( ! $product ) continue;
                            
                            $status = get_post_meta( $post->ID, '_status_produto', true );
                            $precisa_reavaliacao = get_post_meta( $post->ID, '_precisa_reavaliacao', true );
                            $data_entrada_manutencao = get_post_meta( $post->ID, '_data_entrada_manutencao', true );
                            $data_manutencao = get_post_meta( $post->ID, '_data_manutencao', true );
                        ?>
                            <tr>
                                <td><?php echo $post->ID; ?></td>
                                <td><strong><?php echo esc_html( $product->get_sku() ); ?></strong></td>
                                <td><?php echo esc_html( $product->get_name() ); ?></td>
                                <td>
                                    <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                        <?php echo esc_html( $status ?: 'N/A' ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $precisa_reavaliacao ?: 'N/A' ); ?></td>
                                <td><?php echo esc_html( $data_entrada_manutencao ?: 'N/A' ); ?></td>
                                <td><?php echo esc_html( $data_manutencao ?: 'N/A' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px; border-left: 4px solid #007bff;">
                <h4>💡 Informações de Debug:</h4>
                <p><strong>Este shortcode mostra:</strong></p>
                <ul>
                    <li>Contagem de cadeiras por status</li>
                    <li>Metas de avaliação pendente</li>
                    <li>Detalhes das cadeiras em manutenção</li>
                    <li>Verificação de inconsistências</li>
                </ul>
                <p><strong>Use apenas em desenvolvimento!</strong></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Detecta parâmetros URL e exibe formulário automaticamente
     */
    public function auto_display_emprestimo_form() {
        // Apenas na parte pública do site
        if ( is_admin() ) {
            return;
        }
        
        // Verifica se tem parâmetros do plugin
        if ( ! isset( $_GET['movliv_action'] ) || $_GET['movliv_action'] !== 'form_emprestimo' ) {
            return;
        }
        
        // ✅ CORREÇÃO: Decodifica parâmetros da URL
        $order_id = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
        $order_key = isset( $_GET['order_key'] ) ? urldecode( sanitize_text_field( $_GET['order_key'] ) ) : '';
        
        if ( ! $order_id || ! $order_key ) {
            error_log( "MovLiv: Parâmetros inválidos na URL do formulário - order_id: {$order_id}, key: {$order_key}" );
            return;
        }
        
        // Verifica se o pedido existe e a chave está correta
        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_order_key() !== $order_key ) {
            error_log( "MovLiv: Pedido {$order_id} não encontrado ou chave inválida" );
            return;
        }
        
        // Adiciona filtro para exibir o formulário no conteúdo da página
        add_filter( 'the_content', array( $this, 'replace_content_with_emprestimo_form' ) );
        add_filter( 'the_title', array( $this, 'replace_title_with_emprestimo_title' ) );
    }
    
    /**
     * Substitui o conteúdo da página pelo formulário de empréstimo
     */
    public function replace_content_with_emprestimo_form( $content ) {
        if ( ! is_main_query() || ! in_the_loop() ) {
            return $content;
        }
        
        $order_id = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
        
        $new_content = '<div class="movliv-emprestimo-redirect">';
        $new_content .= '<div class="woocommerce-message">';
        $new_content .= sprintf( 
            __( '🎉 Solicitação #%d recebida com sucesso! Complete o processo preenchendo o formulário abaixo:', 'movimento-livre' ),
            $order_id
        );
        $new_content .= '</div>';
        $new_content .= do_shortcode( "[movliv_form_emprestimo pedido_id=\"{$order_id}\"]" );
        $new_content .= '</div>';
        
        return $new_content;
    }
    
    /**
     * Substitui o título da página
     */
    public function replace_title_with_emprestimo_title( $title ) {
        if ( ! is_main_query() || ! in_the_loop() ) {
            return $title;
        }
        
        $order_id = intval( $_GET['order_id'] ?? 0 );
        return sprintf( __( 'Formulário de Empréstimo - Pedido #%d', 'movimento-livre' ), $order_id );
    }


} 