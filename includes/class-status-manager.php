<?php
/**
 * Gerenciador de Status Customizados - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para gerenciar status personalizados de pedidos e produtos
 */
class MOVLIV_Status_Manager {

    /**
     * Instância única da classe
     * @var MOVLIV_Status_Manager
     */
    private static $instance = null;

    /**
     * Status de pedidos permitidos (usando nativos do WooCommerce)
     * @var array
     */
    public static $allowed_order_statuses = array(
        'wc-on-hold' => 'Aguardando',        // Status nativo "Aguardando" 
        'wc-processing' => 'Emprestado',     // Status nativo "Processando" → renomeado
        'wc-completed' => 'Devolvido',       // Status nativo "Concluído" → renomeado
        'wc-cancelled' => 'Cancelado'        // Status nativo "Cancelado" → mantido
    );

    /**
     * Status de produtos (cadeiras) customizados
     * @var array
     */
    public static $product_statuses = array(
        'pronta' => 'Pronta',
        'emprestado' => 'Emprestado',
        'em_avaliacao' => 'Em Avaliação',
        'em_manutencao' => 'Em Manutenção'
    );

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_Status_Manager();
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
        // Filtro mais agressivo e eficaz para status
        add_filter( 'wc_order_statuses', array( $this, 'filter_and_rename_statuses' ), 20 );
        
        // Filtro JavaScript como fallback
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Hook para transições automáticas de status
        add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_change' ), 10, 4 );
        
        // Adiciona metabox para status de produto
        add_action( 'add_meta_boxes', array( $this, 'add_product_status_metabox' ) );
        
        // Salva status do produto
        add_action( 'save_post', array( $this, 'save_product_status' ) );
        
        // Filtra produtos disponíveis para compra
        add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_purchasable_products' ), 10, 2 );
        
        // Redirecionamento após checkout
        add_action( 'woocommerce_thankyou', array( $this, 'redirect_to_emprestimo_form' ), 5 );
        
        // Handlers AJAX
        add_action( 'wp_ajax_movliv_check_plugin_order', array( $this, 'ajax_check_plugin_order' ) );
        
        // Force initializa produtos como cadeiras quando criados
        add_action( 'woocommerce_new_product', array( $this, 'init_product_as_cadeira' ) );
        
        // ✅ NOVO: Inicializa produtos existentes na ativação do plugin
        add_action( 'movliv_init_existing_products', array( $this, 'init_existing_products' ) );
    }

    /**
     * Filtro principal - Remove status indesejados e renomeia
     * Aplica para TODOS os pedidos na tela de edição para máxima eficácia
     * Compatível com interface antiga (post.php) e nova (HPOS - admin.php?page=wc-orders)
     */
    public function filter_and_rename_statuses( $order_statuses ) {
        global $post, $pagenow;
        
        $is_order_edit = false;
        
        // Verifica interface antiga (post.php)
        if ( $pagenow === 'post.php' && $post && $post->post_type === 'shop_order' ) {
            $is_order_edit = true;
            error_log( "MovLiv: Interface antiga detectada - pedido {$post->ID}" );
        }
        
        // Verifica nova interface HPOS (admin.php?page=wc-orders&action=edit)
        if ( $pagenow === 'admin.php' && 
             isset( $_GET['page'] ) && $_GET['page'] === 'wc-orders' && 
             isset( $_GET['action'] ) && $_GET['action'] === 'edit' &&
             isset( $_GET['id'] ) ) {
            $is_order_edit = true;
            $order_id = intval( $_GET['id'] );
            error_log( "MovLiv: Nova interface HPOS detectada - pedido {$order_id}" );
        }
        
        // Aplica filtro se estiver editando um pedido (qualquer interface)
        if ( $is_order_edit ) {
            
            error_log( "MovLiv: Aplicando filtro de status para interface de pedidos" );
            
            // Remove status indesejados SEMPRE (melhor ter menos opções do que confundir)
            $unwanted_statuses = array(
                'wc-pending',       // Pagamento Pendente
                'wc-refunded',      // Reembolsado  
                'wc-failed',        // Malsucedido
                'wc-checkout-draft' // Rascunho
            );
            
            foreach ( $unwanted_statuses as $status ) {
                if ( isset( $order_statuses[ $status ] ) ) {
                    unset( $order_statuses[ $status ] );
                    error_log( "MovLiv: Removido status {$status}" );
                }
            }
            
            // Renomeia labels para contexto de empréstimo
            if ( isset( $order_statuses['wc-processing'] ) ) {
                $order_statuses['wc-processing'] = 'Emprestado';
                error_log( "MovLiv: Renomeado 'Processando' para 'Emprestado'" );
            }
            if ( isset( $order_statuses['wc-completed'] ) ) {
                $order_statuses['wc-completed'] = 'Devolvido';
                error_log( "MovLiv: Renomeado 'Concluído' para 'Devolvido'" );
            }
            
            error_log( "MovLiv: Status filtrados e renomeados. Restantes: " . implode( ', ', array_keys( $order_statuses ) ) );
        }
        
        return $order_statuses;
    }

    /**
     * Remove status indesejados do WooCommerce para pedidos do plugin (MÉTODO LEGACY)
     */
    public function filter_unwanted_statuses( $order_statuses ) {
        // Este método agora é legacy - a lógica foi movida para filter_and_rename_statuses
        return $order_statuses;
    }

    /**
     * Renomeia labels dos status do WooCommerce para pedidos do plugin (MÉTODO LEGACY)
     */
    public function rename_order_statuses( $order_statuses ) {
        // Este método agora é legacy - a lógica foi movida para filter_and_rename_statuses
        return $order_statuses;
    }

    /**
     * Carrega scripts apenas nas páginas necessárias
     * Compatível com interface antiga (post.php) e nova (HPOS - admin.php?page=wc-orders)
     */
    public function enqueue_admin_scripts() {
        global $pagenow, $post;
        
        $should_enqueue = false;
        
        // Interface antiga (post.php) - edição de pedidos
        if ( $pagenow === 'post.php' && $post && $post->post_type === 'shop_order' ) {
            $should_enqueue = true;
            error_log( "MovLiv: Carregando scripts para interface antiga" );
        }
        
        // Nova interface HPOS (admin.php?page=wc-orders&action=edit)
        if ( $pagenow === 'admin.php' && 
             isset( $_GET['page'] ) && $_GET['page'] === 'wc-orders' && 
             isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
            $should_enqueue = true;
            error_log( "MovLiv: Carregando scripts para interface HPOS" );
        }
        
        if ( ! $should_enqueue ) {
            return;
        }
        
        wp_enqueue_script(
            'movliv-admin-order-status-filter',
            MOVLIV_PLUGIN_URL . 'assets/js/admin-order-status-filter.js',
            array( 'jquery' ),
            MOVLIV_VERSION,
            true
        );

        wp_localize_script(
            'movliv-admin-order-status-filter',
            'movliv_admin_order_status_filter',
            array(
                'allowed_statuses' => array_keys( self::$allowed_order_statuses ),
                'product_statuses' => self::$product_statuses,
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'movliv_admin_order_status_filter_nonce' )
            )
        );
        
        error_log( "MovLiv: Scripts carregados com sucesso" );
    }
    
    /**
     * Handler AJAX para verificar se o pedido é do plugin
     */
    public function ajax_check_plugin_order() {
        // Verifica nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'movliv_admin_order_status_filter_nonce' ) ) {
            wp_die( 'Nonce inválido' );
        }
        
        $order_id = intval( $_POST['order_id'] );
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Pedido não encontrado' ) );
        }
        
        $is_plugin_order = $this->is_plugin_order( $order );
        
        wp_send_json_success( array( 
            'is_plugin_order' => $is_plugin_order,
            'order_id' => $order_id
        ) );
    }
    
    /**
     * Verifica se é um pedido do plugin (contém cadeiras)
     */
    private function is_plugin_order( $order ) {
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $product = wc_get_product( $product_id );
            
            if ( $product ) {
                // Verifica se tem o meta _status_produto (indicativo de ser uma cadeira)
                $status_produto = get_post_meta( $product_id, '_status_produto', true );
                if ( ! empty( $status_produto ) ) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Manipula mudanças de status de pedidos
     */
    public function handle_order_status_change( $order_id, $old_status, $new_status, $order ) {
        error_log( "MovLiv: Status changed from {$old_status} to {$new_status} for order {$order_id}" );

        // Apenas para pedidos do plugin
        if ( ! $this->is_plugin_order( $order ) ) {
            return;
        }

        switch ( $new_status ) {
            case 'processing': // Emprestado
                $this->handle_emprestado_status( $order );
                break;
            
            case 'completed': // Devolvido
                $this->handle_devolvido_status( $order );
                break;
        }
    }

    /**
     * Manipula status "processing" (emprestado) - reduz estoque e atualiza status da cadeira
     */
    private function handle_emprestado_status( $order ) {
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $product = wc_get_product( $product_id );
            
            if ( $product ) {
                // Atualiza status da cadeira para "emprestado"
                update_post_meta( $product_id, '_status_produto', 'emprestado' );
                
                error_log( "MovLiv: Produto {$product_id} marcado como emprestado" );
            }
        }
    }

    /**
     * Manipula status "completed" (devolvido) - aumenta estoque e atualiza status da cadeira
     */
    private function handle_devolvido_status( $order ) {
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $product = wc_get_product( $product_id );
            
            if ( $product ) {
                // Atualiza status da cadeira para "pronta"
                update_post_meta( $product_id, '_status_produto', 'pronta' );
                
                // Aumenta estoque
                $product->increase_stock( $item->get_quantity() );
                
                error_log( "MovLiv: Produto {$product_id} devolvido e estoque restaurado" );
            }
        }
    }

    /**
     * Adiciona metabox para status de produto
     */
    public function add_product_status_metabox() {
        add_meta_box(
            'movliv_product_status',
            'Status da Cadeira de Rodas',
            array( $this, 'product_status_metabox_callback' ),
            'product',
            'side',
            'high'
        );
    }

    /**
     * Callback do metabox de status do produto
     */
    public function product_status_metabox_callback( $post ) {
        wp_nonce_field( 'movliv_product_status_nonce', 'movliv_product_status_nonce' );
        
        $current_status = get_post_meta( $post->ID, '_status_produto', true );
        if ( empty( $current_status ) ) {
            $current_status = 'pronta';
        }
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="movliv_status_produto">Status Atual:</label></th>';
        echo '<td>';
        echo '<select name="movliv_status_produto" id="movliv_status_produto" class="regular-text">';
        
        foreach ( self::$product_statuses as $status => $label ) {
            $selected = selected( $current_status, $status, false );
            echo "<option value=\"{$status}\" {$selected}>{$label}</option>";
        }
        
        echo '</select>';
        echo '<p class="description">Define o status atual desta cadeira de rodas.</p>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        
        // Informações de empréstimo atual
        $this->display_loan_info( $post->ID );
    }

    /**
     * Exibe informações do empréstimo atual
     */
    private function display_loan_info( $product_id ) {
        global $wpdb;
        
        // Busca pedidos ativos com esta cadeira
        $query = $wpdb->prepare( "
            SELECT p.ID, p.post_status, pm.meta_value as billing_first_name,
                   pm2.meta_value as billing_last_name, p.post_date
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_billing_first_name'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_billing_last_name'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-on-hold', 'wc-processing')
            AND oim.meta_key = '_product_id'
            AND oim.meta_value = %d
            ORDER BY p.post_date DESC
            LIMIT 1
        ", $product_id );
        
        $loan = $wpdb->get_row( $query );
        
        if ( $loan ) {
            echo '<div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 3px;">';
            echo '<h4 style="margin: 0 0 10px 0;">📋 Empréstimo Atual</h4>';
            echo '<p><strong>Pedido:</strong> #' . $loan->ID . '</p>';
            echo '<p><strong>Cliente:</strong> ' . $loan->billing_first_name . ' ' . $loan->billing_last_name . '</p>';
            echo '<p><strong>Data:</strong> ' . date( 'd/m/Y H:i', strtotime( $loan->post_date ) ) . '</p>';
            echo '<p><strong>Status:</strong> ' . ( $loan->post_status === 'wc-on-hold' ? 'Aguardando' : 'Emprestado' ) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Salva status do produto
     */
    public function save_product_status( $post_id ) {
        if ( ! isset( $_POST['movliv_product_status_nonce'] ) || 
             ! wp_verify_nonce( $_POST['movliv_product_status_nonce'], 'movliv_product_status_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['movliv_status_produto'] ) ) {
            $status = sanitize_text_field( $_POST['movliv_status_produto'] );
            
            if ( array_key_exists( $status, self::$product_statuses ) ) {
                update_post_meta( $post_id, '_status_produto', $status );
            }
        }
    }

    /**
     * Filtra produtos disponíveis para compra baseado no status
     */
    public function filter_purchasable_products( $is_purchasable, $product ) {
        if ( ! $product ) {
            return $is_purchasable;
        }

        $status = get_post_meta( $product->get_id(), '_status_produto', true );
        
        // Apenas produtos "prontos" podem ser emprestados
        if ( ! empty( $status ) && $status !== 'pronta' ) {
            return false;
        }

        return $is_purchasable;
    }

    /**
     * ✅ CORREÇÃO: Redireciona para o formulário de empréstimo após o checkout
     */
    public function redirect_to_emprestimo_form( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Verifica se é um pedido do plugin
        if ( ! $this->is_plugin_order( $order ) ) {
            return;
        }

        // ✅ CORREÇÃO: Verifica se o status é "on-hold" (aguardando) - conforme fluxo
        if ( $order->get_status() !== 'on-hold' ) {
            error_log( "MovLiv: Pedido {$order_id} não está em status 'on-hold' para redirecionamento" );
            return;
        }

        // ✅ CORREÇÃO: Busca página específica para formulário de empréstimo
        $emprestimo_page_id = $this->get_emprestimo_page_id();
        
        if ( $emprestimo_page_id ) {
            // Redireciona para página específica do formulário
            $emprestimo_page_url = add_query_arg( 
                array( 
                    'order_id' => $order_id,
                    'order_key' => $order->get_order_key(),
                    'action' => 'emprestimo'
                ), 
                get_permalink( $emprestimo_page_id )
            );
            
            error_log( "MovLiv: Redirecionando pedido {$order_id} para página de empréstimo: {$emprestimo_page_url}" );
            
            // ✅ CORREÇÃO: Usa JavaScript para redirecionamento mais seguro
            echo '<script type="text/javascript">
                setTimeout(function() {
                    window.location.href = "' . esc_url( $emprestimo_page_url ) . '";
                }, 2000);
            </script>';
            
            // ✅ CORREÇÃO: Mensagem informativa para o usuário
            echo '<div class="woocommerce-message" style="margin: 20px 0; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">
                <p><strong>🎉 Pedido realizado com sucesso!</strong></p>
                <p>Você será redirecionado para preencher o formulário de empréstimo em alguns segundos...</p>
                <p>Se o redirecionamento não funcionar, <a href="' . esc_url( $emprestimo_page_url ) . '">clique aqui</a>.</p>
            </div>';
            
            return;
        }
    }

    /**
     * ✅ NOVO: Busca página específica para formulário de empréstimo
     */
    private function get_emprestimo_page_id() {
        // Busca página que contém o shortcode do formulário
        $pages = get_pages( array(
            'meta_query' => array(
                array(
                    'key' => '_wp_page_template',
                    'value' => 'emprestimo',
                    'compare' => 'LIKE'
                )
            )
        ) );
        
        if ( ! empty( $pages ) ) {
            return $pages[0]->ID;
        }
        
        // Busca página por slug
        $page = get_page_by_path( 'formulario-emprestimo' );
        if ( $page ) {
            return $page->ID;
        }
        
        // Busca página por conteúdo com shortcode
        $pages = get_pages();
        foreach ( $pages as $page ) {
            if ( strpos( $page->post_content, '[movliv_form_emprestimo' ) !== false ) {
                return $page->ID;
            }
        }
        
        return false;
    }

    /**
     * Inicializa produto como cadeira quando criado
     */
    public function init_product_as_cadeira( $product_id ) {
        // Define status inicial para novos produtos
        $current_status = get_post_meta( $product_id, '_status_produto', true );
        if ( empty( $current_status ) ) {
            update_post_meta( $product_id, '_status_produto', 'pronta' );
            error_log( "MovLiv: Produto {$product_id} inicializado como cadeira" );
        }
    }

    /**
     * Inicializa produtos existentes como cadeiras
     */
    public function init_existing_products() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_status_produto',
                    'value' => '', // Todos os produtos que não têm o meta _status_produto
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        $products = get_posts( $args );

        if ( ! empty( $products ) ) {
            foreach ( $products as $product ) {
                $this->init_product_as_cadeira( $product->ID );
            }
            error_log( "MovLiv: Inicializados " . count( $products ) . " produtos existentes como cadeiras." );
        } else {
            error_log( "MovLiv: Nenhum produto existente encontrado para inicialização." );
        }
    }

    /**
     * Métodos estáticos para compatibilidade
     */
    public static function get_product_status( $product_id ) {
        return get_post_meta( $product_id, '_status_produto', true );
    }

    public static function get_product_status_label( $status ) {
        return isset( self::$product_statuses[ $status ] ) ? self::$product_statuses[ $status ] : $status;
    }

    public static function update_product_status( $product_id, $new_status ) {
        if ( array_key_exists( $new_status, self::$product_statuses ) ) {
            update_post_meta( $product_id, '_status_produto', $new_status );
            return true;
        }
        return false;
    }

    /**
     * ✅ NOVO: Registra status customizados (método estático para ativação)
     */
    public static function register_custom_order_statuses() {
        // Este método é chamado durante a ativação do plugin
        // Os status customizados são gerenciados pelo filtro wc_order_statuses
        // que é definido no método init_hooks()
        
        error_log( "MovLiv: Status customizados registrados na ativação do plugin" );
        
        // Força limpeza do cache de status
        wp_cache_delete( 'wc_order_statuses', 'woocommerce' );
        
        return true;
    }
} 