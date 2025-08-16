<?php
/**
 * Interface Administrativa - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para gerenciar a interface administrativa
 */
class MOVLIV_Admin_Interface {

    /**
     * Instância única da classe
     * @var MOVLIV_Admin_Interface
     */
    private static $instance = null;

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_Admin_Interface();
        }
        return self::$instance;
    }

    /**
     * Construtor privado
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_movliv_dashboard_stats', array( $this, 'ajax_dashboard_stats' ) );
        
        // Adiciona colunas customizadas nas listagens de pedidos
        add_filter( 'manage_shop_order_posts_columns', array( $this, 'add_order_columns' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'populate_order_columns' ), 10, 2 );
    }

    /**
     * Adiciona menu administrativo
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Movimento Livre', 'movimento-livre' ),
            __( 'Movimento Livre', 'movimento-livre' ),
            'manage_woocommerce',
            'movimento-livre',
            array( $this, 'admin_dashboard_page' ),
            'dashicons-heart',
            25
        );

        add_submenu_page(
            'movimento-livre',
            __( 'Dashboard', 'movimento-livre' ),
            __( 'Dashboard', 'movimento-livre' ),
            'manage_woocommerce',
            'movimento-livre',
            array( $this, 'admin_dashboard_page' )
        );

        add_submenu_page(
            'movimento-livre',
            __( 'Gestão de Cadeiras', 'movimento-livre' ),
            __( 'Gestão de Cadeiras', 'movimento-livre' ),
            'movliv_colaborador',
            'movimento-livre-cadeiras',
            array( $this, 'admin_cadeiras_page' )
        );

        add_submenu_page(
            'movimento-livre',
            __( 'Empréstimos Ativos', 'movimento-livre' ),
            __( 'Empréstimos Ativos', 'movimento-livre' ),
            'movliv_colaborador',
            'movimento-livre-emprestimos',
            array( $this, 'admin_emprestimos_page' )
        );

        add_submenu_page(
            'movimento-livre',
            __( 'Configurações', 'movimento-livre' ),
            __( 'Configurações', 'movimento-livre' ),
            'manage_options',
            'movimento-livre-config',
            array( $this, 'admin_config_page' )
        );
    }

    /**
     * Carrega scripts e estilos do admin
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'movimento-livre' ) === false ) {
            return;
        }

        // Sempre carrega o CSS
        wp_enqueue_style(
            'movliv-admin',
            MOVLIV_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MOVLIV_VERSION
        );

        // Carrega JavaScript seletivamente baseado na página
        $current_page = isset( $_GET['page'] ) ? $_GET['page'] : '';
        
        // Páginas que precisam de gráficos e JavaScript completo
        $pages_with_charts = array(
            'movimento-livre-relatorios',
            // Adicione outras páginas que precisam de gráficos aqui
        );
        
        // Dashboard principal não recebe JavaScript para evitar interferência
        $dashboard_pages = array(
            'movimento-livre'
        );
        
        if ( in_array( $current_page, $pages_with_charts ) ) {
            // Habilita Chart.js e JavaScript completo para páginas de relatórios
            wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true );
            
            wp_enqueue_script(
                'movliv-admin',
                MOVLIV_PLUGIN_URL . 'assets/js/admin.js',
                array( 'jquery', 'chart-js' ),
                MOVLIV_VERSION,
                true
            );

            wp_localize_script( 'movliv-admin', 'movliv_admin', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'movliv_admin_nonce' ),
                'strings' => array(
                    'confirmar_exclusao' => __( 'Tem certeza que deseja excluir este item?', 'movimento-livre' ),
                    'erro_generico' => __( 'Ocorreu um erro. Tente novamente.', 'movimento-livre' ),
                    'sucesso' => __( 'Operação realizada com sucesso!', 'movimento-livre' )
                )
            ) );
        } elseif ( in_array( $current_page, $dashboard_pages ) ) {
            // Dashboard principal: APENAS CSS, SEM JavaScript
            // Mantém estatísticas funcionando sem interferência
            
            // Adiciona comentário no HTML para debug
            add_action( 'admin_footer', function() {
                echo '<!-- Movimento Livre: Dashboard sem JavaScript (por segurança) -->';
            } );
        }
        
        // Outras páginas podem receber JavaScript básico se necessário no futuro
    }

    /**
     * Página principal do dashboard
     */
    public function admin_dashboard_page() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Dashboard - Movimento Livre', 'movimento-livre' ); ?></h1>
            
            <div class="movliv-dashboard">
                <div class="movliv-stats-grid">
                    <div class="movliv-stat-card">
                        <h3><?php _e( 'Cadeiras Disponíveis', 'movimento-livre' ); ?></h3>
                        <span class="movliv-stat-number"><?php echo $stats['cadeiras_disponiveis']; ?></span>
                    </div>
                    
                    <div class="movliv-stat-card">
                        <h3><?php _e( 'Empréstimos Ativos', 'movimento-livre' ); ?></h3>
                        <span class="movliv-stat-number"><?php echo $stats['emprestimos_ativos']; ?></span>
                    </div>
                    
                    <div class="movliv-stat-card">
                        <h3><?php _e( 'Aguardando Avaliação', 'movimento-livre' ); ?></h3>
                        <span class="movliv-stat-number"><?php echo $stats['aguardando_avaliacao']; ?></span>
                    </div>
                    
                    <div class="movliv-stat-card">
                        <h3><?php _e( 'Em Manutenção', 'movimento-livre' ); ?></h3>
                        <span class="movliv-stat-number"><?php echo $stats['em_manutencao']; ?></span>
                    </div>
                </div>

                <div class="movliv-recent-activity">
                    <h3><?php _e( 'Atividades Recentes', 'movimento-livre' ); ?></h3>
                    <?php $this->render_recent_activity(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Página de gestão de cadeiras
     */
    public function admin_cadeiras_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Gestão de Cadeiras', 'movimento-livre' ); ?></h1>
            
            <div class="movliv-filters">
                <select id="filter-status">
                    <option value=""><?php _e( 'Todos os Status', 'movimento-livre' ); ?></option>
                    <?php foreach ( MOVLIV_Status_Manager::$product_statuses as $status => $label ): ?>
                        <option value="<?php echo esc_attr( $status ); ?>">
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" id="search-cadeiras" placeholder="<?php _e( 'Buscar cadeiras...', 'movimento-livre' ); ?>">
                <button type="button" class="button" id="apply-filters"><?php _e( 'Filtrar', 'movimento-livre' ); ?></button>
            </div>

            <div id="cadeiras-list">
                <?php $this->render_cadeiras_list(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Página de empréstimos ativos
     */
    public function admin_emprestimos_page() {
        $emprestimos = $this->get_emprestimos_ativos();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Empréstimos Ativos', 'movimento-livre' ); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Pedido', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Cliente', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'CPF', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Cadeira', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Data Empréstimo', 'movimento-livre' ); ?></th>
                        <th><?php _e( 'Dias', 'movimento-livre' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $emprestimos as $emprestimo ): ?>
                        <tr>
                            <td><a href="<?php echo admin_url( 'post.php?post=' . $emprestimo['order_id'] . '&action=edit' ); ?>">#<?php echo $emprestimo['order_id']; ?></a></td>
                            <td><?php echo esc_html( $emprestimo['cliente'] ); ?></td>
                            <td><?php echo esc_html( $emprestimo['cpf'] ); ?></td>
                            <td><?php echo esc_html( $emprestimo['cadeira'] ); ?></td>
                            <td><?php echo esc_html( $emprestimo['data_emprestimo'] ); ?></td>
                            <td><?php echo $emprestimo['dias_emprestimo']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Página de configurações
     */
    public function admin_config_page() {
        if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['movliv_config_nonce'], 'movliv_config_save' ) ) {
            $this->save_config();
            echo '<div class="notice notice-success"><p>' . __( 'Configurações salvas!', 'movimento-livre' ) . '</p></div>';
        }

        $config = get_option( 'movliv_config', array() );
        ?>
        <div class="wrap">
            <h1><?php _e( 'Configurações - Movimento Livre', 'movimento-livre' ); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'movliv_config_save', 'movliv_config_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Limite de Empréstimos por CPF', 'movimento-livre' ); ?></th>
                        <td>
                            <input type="number" name="limite_emprestimos" value="<?php echo esc_attr( $config['limite_emprestimos'] ?? 2 ); ?>" min="1" max="10">
                            <p class="description"><?php _e( 'Número máximo de empréstimos simultâneos por CPF', 'movimento-livre' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Email de Notificações', 'movimento-livre' ); ?></th>
                        <td>
                            <input type="email" name="email_notificacoes" value="<?php echo esc_attr( $config['email_notificacoes'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text">
                            <p class="description"><?php _e( 'Email que receberá notificações do sistema', 'movimento-livre' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Senha da Lista de Cadeiras', 'movimento-livre' ); ?></th>
                        <td>
                            <input type="password" name="senha_shortcode" value="" class="regular-text" autocomplete="new-password">
                            <p class="description">
                                <?php 
                                if ( ! empty( $config['senha_shortcode_hash'] ) ) {
                                    echo '<span style="color: #28a745;">✓ Senha configurada</span><br>';
                                } else {
                                    echo '<span style="color: #dc3545;">✗ Nenhuma senha configurada (acesso liberado)</span><br>';
                                }
                                _e( 'Deixe em branco para liberar acesso sem senha. Esta senha protege a listagem de cadeiras no site.', 'movimento-livre' ); 
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // ... métodos auxiliares privados seguem abaixo

    private function save_config() {
        $config = array(
            'limite_emprestimos' => intval( $_POST['limite_emprestimos'] ),
            'email_notificacoes' => sanitize_email( $_POST['email_notificacoes'] )
        );
        
        // Processa senha do shortcode
        if ( ! empty( $_POST['senha_shortcode'] ) ) {
            // Se foi fornecida uma nova senha, criptografa e salva
            $config['senha_shortcode_hash'] = wp_hash_password( $_POST['senha_shortcode'] );
        } elseif ( isset( $_POST['senha_shortcode'] ) && $_POST['senha_shortcode'] === '' ) {
            // Se o campo foi enviado vazio, remove a senha existente
            $config['senha_shortcode_hash'] = '';
        }
        // Se o campo não foi enviado, mantém a senha existente
        
        update_option( 'movliv_config', $config );
    }

    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array(
            'cadeiras_disponiveis' => 0,
            'emprestimos_ativos' => 0,
            'aguardando_avaliacao' => 0,
            'em_manutencao' => 0
        );
        
        // Contar total de produtos (cadeiras)
        $total_cadeiras = $wpdb->get_var( "
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'product' 
            AND post_status = 'publish'
        " );
        
        // Contar cadeiras por status (incluindo produtos sem meta definida)
        $cadeiras_stats = $wpdb->get_results( "
            SELECT COALESCE(pm.meta_value, 'pronta') as status, COUNT(*) as count 
            FROM {$wpdb->posts} p 
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_status_produto'
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish' 
            GROUP BY COALESCE(pm.meta_value, 'pronta')
        " );
        
        foreach ( $cadeiras_stats as $stat ) {
            switch ( $stat->status ) {
                case 'pronta':
                case '':
                case null:
                    $stats['cadeiras_disponiveis'] += $stat->count;
                    break;
                case 'emprestado':
                    $stats['emprestimos_ativos'] += $stat->count;
                    break;
                case 'em_avaliacao':
                    $stats['aguardando_avaliacao'] += $stat->count;
                    break;
                case 'em_manutencao':
                    $stats['em_manutencao'] += $stat->count;
                    break;
            }
        }
        
        // Contar empréstimos ativos por status de pedidos (backup)
        $emprestimos_pedidos = $wpdb->get_var( "
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'shop_order' 
            AND post_status = 'wc-processing'
        " );
        
        // Use o maior valor entre produtos emprestados e pedidos ativos
        $stats['emprestimos_ativos'] = max( $stats['emprestimos_ativos'], $emprestimos_pedidos );
        
        return $stats;
    }

    private function render_cadeiras_list() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 20,
            'post_status' => 'publish'
        );
        
        $products = get_posts( $args );
        
        echo '<div class="movliv-cadeiras-grid">';
        foreach ( $products as $product ) {
            $status = get_post_meta( $product->ID, '_status_produto', true ) ?: 'pronta';
            $status_label = MOVLIV_Status_Manager::$product_statuses[ $status ] ?? $status;
            
            echo '<div class="movliv-cadeira-card">';
            echo '<h4>' . esc_html( $product->post_title ) . '</h4>';
            echo '<p><strong>Status:</strong> <span class="status-' . esc_attr( $status ) . '">' . esc_html( $status_label ) . '</span></p>';
            echo '<div class="movliv-cadeira-actions">';
            echo '<a href="' . admin_url( 'post.php?post=' . $product->ID . '&action=edit' ) . '" class="button button-small">' . __( 'Editar', 'movimento-livre' ) . '</a>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    private function render_recent_activity() {
        $orders = wc_get_orders( array(
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ) );
        
        if ( empty( $orders ) ) {
            echo '<div class="movliv-activity-list">';
            echo '<div class="movliv-no-activity">' . __( 'Nenhuma atividade recente encontrada.', 'movimento-livre' ) . '</div>';
            echo '</div>';
            return;
        }
        
        echo '<div class="movliv-activity-list">';
        foreach ( $orders as $order ) {
            $status = $order->get_status();
            
            // Aplicar renomeação dos status para o contexto de empréstimos
            switch ( $status ) {
                case 'processing':
                    $status_label = __( 'Emprestado', 'movimento-livre' );
                    $status_class = 'emprestado';
                    break;
                case 'completed':
                    $status_label = __( 'Devolvido', 'movimento-livre' );
                    $status_class = 'devolvido';
                    break;
                case 'on-hold':
                    $status_label = __( 'Aguardando', 'movimento-livre' );
                    $status_class = 'aguardando';
                    break;
                case 'cancelled':
                    $status_label = __( 'Cancelado', 'movimento-livre' );
                    $status_class = 'cancelado';
                    break;
                default:
                    $status_label = wc_get_order_status_name( $status );
                    $status_class = $status;
            }
            
            // Obter informações do cliente
            $cliente_nome = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $cpf = get_post_meta( $order->get_id(), '_cpf_solicitante', true );
            
            echo '<div class="movliv-activity-item2 status2-' . esc_attr( $status_class ) . '">';
            echo '<div class="activity-header">&nbsp;';
            echo '<strong>#' . $order->get_id() . '</strong>&nbsp;&nbsp;&nbsp;';
            echo '<span class="status-badge">' . esc_html( $status_label ) . '</span>&nbsp;&nbsp;&nbsp;';
            echo '<span class="movliv-activity-date">' . $order->get_date_created()->format( 'd/m/Y H:i' ) . '</span>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;';
            echo '</div>';
            
            if ( $cliente_nome && trim( $cliente_nome ) !== '' ) {
                echo '<div class="activity-details">';
                echo '<span class="cliente">' . esc_html( trim( $cliente_nome ) ) . '</span>&nbsp;&nbsp;&nbsp;';
                if ( $cpf ) {
                    echo ' | <span class="cpf">CPF: ' . esc_html( $cpf ) . '</span>&nbsp;&nbsp;&nbsp;';
                }
                echo '</div>';
            }
            
            echo '</div>';
        }
        echo '</div>';
        
        // Adicionar CSS inline para formatação
        echo '<style>
        .movliv-activity-item2 {
            border-left: 4px solid #ddd;
            padding: 10px;
            margin-bottom: 8px;
            background: #f9f9f9;
            display: flex;
            justify-content: flex-start;
        }
        .movliv-activity-item2.status2-emprestado { border-left-color: #007cba; }
        .movliv-activity-item2.status2-aguardando { border-left-color: #ffb900; }
        .movliv-activity-item2.status2-devolvido { border-left-color: #00a32a; }
        .movliv-activity-item2.status2-cancelado { border-left-color: #d63638; }
        .activity-header {
            /* display: flex; */
            /* justify-content: space-between; */
            align-items: center;
            /* margin-bottom: 5px; */
        }
        .status-badge {
            background: #e1e1e1;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        .activity-details {
            font-size: 13px;
            color: #666;
        }
        .movliv-activity-date {
            color: #666;
            font-size: 12px;
        }
        .movliv-no-activity {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        </style>';
    }

    private function get_emprestimos_ativos() {
        $orders = wc_get_orders( array(
            'status' => 'processing', // Status nativo para "Emprestado"
            'limit' => -1
        ) );
        
        $emprestimos = array();
        foreach ( $orders as $order ) {
            $cpf = get_post_meta( $order->get_id(), '_cpf_solicitante', true );
            $data_emprestimo = $order->get_date_modified() ?: $order->get_date_created();
            $dias = $data_emprestimo->diff( new DateTime() )->days;
            
            foreach ( $order->get_items() as $item ) {
                $emprestimos[] = array(
                    'order_id' => $order->get_id(),
                    'cliente' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'cpf' => $cpf,
                    'cadeira' => $item->get_name(),
                    'data_emprestimo' => $data_emprestimo->format( 'd/m/Y' ),
                    'dias_emprestimo' => $dias
                );
            }
        }
        
        return $emprestimos;
    }

    // AJAX handlers
    public function ajax_dashboard_stats() {
        check_ajax_referer( 'movliv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Sem permissão' );
        }
        
        wp_send_json_success( $this->get_dashboard_stats() );
    }

    // Colunas customizadas para listagens
    public function add_order_columns( $columns ) {
        $new_columns = array();
        
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            
            if ( $key === 'order_status' ) {
                $new_columns['movliv_cpf'] = __( 'CPF', 'movimento-livre' );
                $new_columns['movliv_dias'] = __( 'Dias', 'movimento-livre' );
            }
        }
        
        return $new_columns;
    }

    public function populate_order_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'movliv_cpf':
                $cpf = get_post_meta( $post_id, '_cpf_solicitante', true );
                echo $cpf ? esc_html( $cpf ) : '-';
                break;
                
            case 'movliv_dias':
                $order = wc_get_order( $post_id );
                if ( $order && $order->get_status() === 'processing' ) {
                    $data_emprestimo = $order->get_date_modified() ?: $order->get_date_created();
                    $dias = $data_emprestimo->diff( new DateTime() )->days;
                    echo $dias . ' ' . __( 'dias', 'movimento-livre' );
                } else {
                    echo '-';
                }
                break;
        }
    }


} 