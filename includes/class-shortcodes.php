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

    /**
     * Shortcode para formulário de devolução
     * [movliv_form_devolucao pedido_id="123"]
     */
    public function shortcode_form_devolucao( $atts ) {
        $atts = shortcode_atts( array(
            'pedido_id' => 0
        ), $atts, 'movliv_form_devolucao' );

        $order_id = intval( $atts['pedido_id'] );
        
        if ( ! $order_id ) {
            return '<p>' . __( 'ID do pedido é obrigatório.', 'movimento-livre' ) . '</p>';
        }

        $formularios = MOVLIV_Formularios::getInstance();
        return $formularios->get_devolucao_form_html( $order_id );
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
        
        if ( ! $product_id ) {
            return '<p>' . __( 'ID do produto é obrigatório.', 'movimento-livre' ) . '</p>';
        }

        // Verifica permissões
        if ( ! MOVLIV_Permissions::can_submit_evaluations() ) {
            return '<p>' . __( 'Você não tem permissão para acessar esta página.', 'movimento-livre' ) . '</p>';
        }

        $formularios = MOVLIV_Formularios::getInstance();
        return $formularios->get_avaliacao_form_html( $product_id );
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

        $product = wc_get_product( $product_id );
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
                                    $product = $item->get_product();
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
                                <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="button">
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
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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