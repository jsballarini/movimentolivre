<?php
/**
 * Hooks de Pedidos - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para gerenciar hooks específicos de pedidos/empréstimos
 */
class MOVLIV_Order_Hooks {

    /**
     * Instância única da classe
     * @var MOVLIV_Order_Hooks
     */
    private static $instance = null;

    /**
     * Construtor
     */
    private function __construct() {
        $this->register_hooks();
    }

    /**
     * Obtém instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_Order_Hooks();
        }
        return self::$instance;
    }

    /**
     * Inicializa hooks
     */
    public function init_hooks() {
        // ✅ NOVO: Hook para processar pedidos gratuitos automaticamente
        add_action( 'woocommerce_checkout_process', array( $this, 'process_free_orders' ), 5 );
        
        // ✅ NOVO: Hook para bypass de gateway de pagamento em pedidos gratuitos
        add_filter( 'woocommerce_cart_needs_payment', array( $this, 'disable_payment_for_free_loans' ), 10, 2 );
        
        // ✅ NOVO: Hook para controlar status de pedidos gratuitos
        add_action( 'woocommerce_payment_complete_order_status', array( $this, 'prevent_auto_processing_for_loans' ), 10, 3 );
        
        // ✅ NOVO: Hook no momento da criação do pedido no checkout
        add_action( 'woocommerce_checkout_order_created', array( $this, 'set_initial_loan_status' ), 10, 1 );
        
        // ✅ NOVO: Hook para redirecionamento após checkout bem-sucedido
        add_action( 'woocommerce_thankyou', array( $this, 'redirect_to_loan_form' ), 5, 1 );
        
        // Hooks do checkout - usar woocommerce_new_order ao invés de woocommerce_thankyou
        add_action( 'woocommerce_new_order', array( $this, 'setup_new_order' ), 10, 1 );
        
        // ✅ CORREÇÃO: Hook mais específico para garantir status correto
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'force_loan_status' ), 1, 1 );
        add_action( 'woocommerce_new_order', array( $this, 'after_order_created' ), 20, 1 );
        
        // ✅ NOVO: Hook para interceptar status logo após criação
        add_action( 'woocommerce_checkout_order_created', array( $this, 'force_loan_status_immediate' ), 1, 1 );
        
        // ✅ NOVO: Hook para interceptar mudanças de status em tempo real
        add_action( 'woocommerce_order_status_changed', array( $this, 'intercept_status_change' ), 5, 4 );
        
        // ✅ NOVO: Hook para interceptar status no momento da criação
        add_filter( 'woocommerce_new_order_status', array( $this, 'force_new_order_status' ), 999, 2 );
        
        // ✅ NOVO: Hook para prevenir mudanças automáticas de status
        add_filter( 'woocommerce_order_status_changed', array( $this, 'prevent_automatic_status_changes' ), 1, 4 );
        
        // ✅ NOVO: Hook para interceptar status no momento da criação
        add_filter( 'woocommerce_new_order_status', array( $this, 'force_new_order_status' ), 999, 2 );
        
        // ✅ NOVO: Hook para interceptar status após criação
        add_action( 'woocommerce_checkout_order_created', array( $this, 'ensure_loan_status_after_creation' ), 999, 1 );
        
        // Modifica labels no admin
        add_filter( 'gettext', array( $this, 'change_woocommerce_labels' ), 20, 3 );
        
        // Adiciona metaboxes customizados
        add_action( 'add_meta_boxes', array( $this, 'add_order_metaboxes' ) );
        
        // Adiciona campos extras ao pedido
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_order_extra_fields' ) );
        
        // Adiciona colunas na lista de pedidos
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_columns' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'display_order_column_content' ), 10, 2 );
        
        // Adiciona botões de ação rápida
        add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_order_actions' ), 10, 2 );
        add_action( 'wp_ajax_movliv_quick_action', array( $this, 'handle_quick_actions' ) );
        
        // Customiza emails
        add_filter( 'woocommerce_email_subject_customer_processing_order', array( $this, 'custom_email_subject' ), 10, 2 );
        add_filter( 'woocommerce_email_subject_customer_completed_order', array( $this, 'custom_email_subject' ), 10, 2 );
    }

    /**
     * ✅ CORREÇÃO: Força status "Aguardando" para empréstimos gratuitos
     */
    public function force_loan_status( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            error_log( "MovLiv: Pedido {$order_id} não encontrado para forçar status" );
            return;
        }
        
        // Verifica se é um empréstimo (valor zero com produtos)
        $is_loan = ( $order->get_total() == 0 );
        $has_products = count( $order->get_items() ) > 0;
        
        if ( ! $is_loan || ! $has_products ) {
            error_log( "MovLiv: Pedido {$order_id} não é empréstimo - status não alterado" );
            return;
        }
        
        // ✅ CORREÇÃO: Verifica se já tem formulário enviado
        $has_form = get_post_meta( $order_id, '_formulario_emprestimo_pdf', true ) || 
                   get_post_meta( $order_id, '_form_emprestimo_pdf', true );
        
        if ( $has_form ) {
            error_log( "MovLiv: Pedido {$order_id} já tem formulário enviado - permitindo status 'processing'" );
            return;
        }
        
        // ✅ CORREÇÃO: Força status "Aguardando" independente do status atual
        $current_status = $order->get_status();
        if ( $current_status !== 'on-hold' ) {
            error_log( "MovLiv: FORÇANDO status do pedido {$order_id} de '{$current_status}' para 'on-hold'" );
            
            // Força status "Aguardando" para empréstimos
            $order->update_status( 'on-hold', __( 'Empréstimo aguardando preenchimento do formulário de retirada.', 'movimento-livre' ) );
            
            // Marca como empréstimo do Movimento Livre
            update_post_meta( $order_id, '_is_movimento_livre', 'yes' );
            
            // Adiciona nota automática
            $order->add_order_note( 
                __( 'Status FORÇADO para "Aguardando" - empréstimo deve aguardar formulário antes de ser processado.', 'movimento-livre' ),
                false
            );
            
            error_log( "MovLiv: Status do pedido {$order_id} FORÇADO para 'on-hold' (empréstimo gratuito)" );
        } else {
            error_log( "MovLiv: Pedido {$order_id} já está com status Aguardando" );
        }
    }

    /**
     * ✅ NOVO: Força status "Aguardando" imediatamente após criação do pedido
     */
    public function force_loan_status_immediate( $order ) {
        error_log( "MovLiv: force_loan_status_immediate() chamado para pedido " . $order->get_id() );
        
        // Verifica se é um empréstimo (valor zero com produtos)
        $is_loan = ( $order->get_total() == 0 );
        $has_products = count( $order->get_items() ) > 0;
        
        if ( ! $is_loan || ! $has_products ) {
            error_log( "MovLiv: Pedido " . $order->get_id() . " não é empréstimo - status não alterado" );
            return;
        }
        
        error_log( "MovLiv: FORÇANDO status IMEDIATO para 'on-hold' no pedido " . $order->get_id() );
        
        // Força status "Aguardando" imediatamente
        $order->update_status( 'on-hold', __( 'Empréstimo aguardando preenchimento do formulário de retirada.', 'movimento-livre' ) );
        
        // Marca como empréstimo do Movimento Livre
        $order->update_meta_data( '_is_movimento_livre', 'yes' );
        
        // Define data prevista de devolução (30 dias)
        $data_prevista = date( 'Y-m-d', strtotime( '+30 days' ) );
        $order->update_meta_data( '_data_prevista_devolucao', $data_prevista );
        
        // Adiciona nota automática
        $order->add_order_note( 
            __( 'Status IMEDIATAMENTE forçado para "Aguardando" - empréstimo deve aguardar formulário antes de ser processado.', 'movimento-livre' ),
            false
        );
        
        $order->save();
        
        error_log( "MovLiv: Status IMEDIATO do pedido " . $order->get_id() . " forçado para 'on-hold' (empréstimo gratuito)" );
    }

    /**
     * Configurações após criação do pedido
     */
    public function after_order_created( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            error_log( "MovLiv: Pedido {$order_id} não encontrado" );
            return;
        }

        // Verifica se é um pedido de cadeira de rodas
        $has_cadeira = false;
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $product = wc_get_product( $product_id );
            
            if ( $product ) {
                $has_cadeira = true;
                break;
            }
        }

        // Se não tem cadeira, não aplica lógica do plugin
        if ( ! $has_cadeira ) {
            return;
        }

        // ✅ REMOÇÃO: Não define status aqui - será definido pela função force_loan_status()
        // Define status inicial como "Aguardando" (status nativo on-hold)
        // $order->update_status( 'on-hold', __( 'Empréstimo aguardando formulário de retirada.', 'movimento-livre' ) );
        
        // Adiciona nota automática
        $order->add_order_note( 
            __( 'Pedido identificado como empréstimo de cadeira de rodas.', 'movimento-livre' ),
            false
        );
        
        error_log( "MovLiv: Pedido {$order_id} identificado como empréstimo" );
    }

    /**
     * Configurações iniciais do novo pedido
     */
    public function setup_new_order( $order_id ) {
        // Define data prevista de devolução (30 dias)
        $data_prevista = date( 'Y-m-d', strtotime( '+30 days' ) );
        update_post_meta( $order_id, '_data_prevista_devolucao', $data_prevista );
        
        // Marca como empréstimo do Movimento Livre
        update_post_meta( $order_id, '_is_movimento_livre', 'yes' );
    }

    /**
     * ✅ CORREÇÃO: Define status inicial correto para empréstimos no momento da criação
     */
    public function set_initial_loan_status( $order ) {
        error_log( "MovLiv: set_initial_loan_status() chamado para pedido " . $order->get_id() );
        
        // Verifica se é um empréstimo (valor zero com produtos)
        $is_loan = ( $order->get_total() == 0 );
        $has_products = count( $order->get_items() ) > 0;
        
        error_log( "MovLiv: Pedido " . $order->get_id() . " - is_loan: " . ($is_loan ? 'true' : 'false') . ", has_products: " . ($has_products ? 'true' : 'false') );
        
        if ( $is_loan && $has_products ) {
            error_log( "MovLiv: Definindo status inicial como 'on-hold' para empréstimo " . $order->get_id() );
            
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
            
            error_log( "MovLiv: Status inicial do pedido " . $order->get_id() . " definido como 'on-hold' (empréstimo)" );
        } else {
            error_log( "MovLiv: Pedido " . $order->get_id() . " não é empréstimo - status não alterado" );
        }
    }

    /**
     * Altera labels do WooCommerce para contexto de empréstimo
     */
    public function change_woocommerce_labels( $translated_text, $text, $domain ) {
        if ( $domain !== 'woocommerce' ) {
            return $translated_text;
        }

        $replacements = array(
            'Orders' => 'Empréstimos',
            'Order' => 'Empréstimo', 
            'Product' => 'Cadeira',
            'Products' => 'Cadeiras',
            'Purchase' => 'Solicitar Empréstimo',
            'Add to cart' => 'Solicitar Cadeira',
            'Cart' => 'Solicitação',
            'Checkout' => 'Finalizar Solicitação',
            'Processing' => 'Emprestado',
            'Completed' => 'Devolvido',
            'On hold' => 'Aguardando'
        );

        return isset( $replacements[ $text ] ) ? $replacements[ $text ] : $translated_text;
    }

    /**
     * Adiciona metaboxes customizados
     */
    public function add_order_metaboxes() {
        add_meta_box(
            'movliv_emprestimo_info',
            __( 'Informações do Empréstimo', 'movimento-livre' ),
            array( $this, 'emprestimo_info_metabox' ),
            'shop_order',
            'normal',
            'high'
        );

        add_meta_box(
            'movliv_formularios',
            __( 'Formulários e Documentos', 'movimento-livre' ),
            array( $this, 'formularios_metabox' ),
            'shop_order',
            'side',
            'default'
        );
    }

    /**
     * Metabox com informações do empréstimo
     */
    public function emprestimo_info_metabox( $post ) {
        $order = wc_get_order( $post->ID );
        $cpf = get_post_meta( $post->ID, '_cpf_solicitante', true );
        $data_prevista = get_post_meta( $post->ID, '_data_prevista_devolucao', true );
        
        ?>
        <table class="wp-list-table widefat striped">
            <tbody>
                <tr>
                    <th style="width: 200px;"><?php _e( 'CPF do Solicitante:', 'movimento-livre' ); ?></th>
                    <td>
                        <?php 
                        if ( $cpf ) {
                            $cpf_validator = MOVLIV_CPF_Validator::getInstance();
                            echo esc_html( $cpf_validator->format_cpf( $cpf ) );
                            
                            // Link para histórico do CPF
                            printf(
                                ' <a href="#" onclick="alert(\'Implementar histórico do CPF\')" class="button button-small">%s</a>',
                                __( 'Ver Histórico', 'movimento-livre' )
                            );
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Data Prevista Devolução:', 'movimento-livre' ); ?></th>
                    <td>
                        <input type="date" name="movliv_data_prevista" value="<?php echo esc_attr( $data_prevista ); ?>" />
                        <button type="button" class="button button-small" onclick="this.previousElementSibling.value = '<?php echo date( 'Y-m-d', strtotime( '+30 days' ) ); ?>'">
                            <?php _e( '30 dias', 'movimento-livre' ); ?>
                        </button>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Status da Cadeira:', 'movimento-livre' ); ?></th>
                    <td>
                        <?php
                        foreach ( $order->get_items() as $item ) {
                            $product_id = $item->get_product_id();
                            $status = MOVLIV_Status_Manager::get_product_status( $product_id );
                            $label = MOVLIV_Status_Manager::get_product_status_label( $status );
                            
                            printf(
                                '<strong>%s:</strong> %s<br>',
                                esc_html( $item->get_name() ),
                                esc_html( $label )
                            );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Empréstimos Ativos (CPF):', 'movimento-livre' ); ?></th>
                    <td>
                        <?php
                        if ( $cpf ) {
                            $cpf_validator = MOVLIV_CPF_Validator::getInstance();
                            $ativos = $cpf_validator->count_active_loans( $cpf );
                            $max = $cpf_validator->get_max_loans();
                            
                            printf(
                                '<span style="color: %s;">%d / %d</span>',
                                $ativos >= $max ? 'red' : 'green',
                                $ativos,
                                $max
                            );
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <script>
        jQuery(document).ready(function($) {
            $('input[name="movliv_data_prevista"]').on('change', function() {
                var orderId = <?php echo $post->ID; ?>;
                var newDate = $(this).val();
                
                $.post(ajaxurl, {
                    action: 'movliv_update_data_prevista',
                    order_id: orderId,
                    data_prevista: newDate,
                    nonce: '<?php echo wp_create_nonce( 'movliv_nonce' ); ?>'
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Metabox com formulários
     */
    public function formularios_metabox( $post ) {
        // Suporta ambas as chaves salvas
        $emprestimo_pdf = get_post_meta( $post->ID, '_form_emprestimo_pdf', true );
        if ( empty( $emprestimo_pdf ) ) {
            $emprestimo_pdf = get_post_meta( $post->ID, '_formulario_emprestimo_pdf', true );
        }
        $devolucao_pdf = get_post_meta( $post->ID, '_form_devolucao_pdf', true );
        if ( empty( $devolucao_pdf ) ) {
            $devolucao_pdf = get_post_meta( $post->ID, '_formulario_devolucao_pdf', true );
        }
        
        ?>
        <div style="margin-bottom: 15px;">
            <strong><?php _e( 'Formulário de Empréstimo:', 'movimento-livre' ); ?></strong><br>
            <?php if ( $emprestimo_pdf && file_exists( $emprestimo_pdf ) ) : ?>
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=movliv_download_pdf&file=' . basename( $emprestimo_pdf ) ), 'download_pdf' ) ); ?>" class="button button-small">
                    <?php _e( 'Download PDF', 'movimento-livre' ); ?>
                </a>
            <?php else : ?>
                <span style="color: #999;"><?php _e( 'Não enviado', 'movimento-livre' ); ?></span>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 15px;">
            <strong><?php _e( 'Formulário de Devolução:', 'movimento-livre' ); ?></strong><br>
            <?php if ( $devolucao_pdf && file_exists( $devolucao_pdf ) ) : ?>
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=movliv_download_pdf&file=' . basename( $devolucao_pdf ) ), 'download_pdf' ) ); ?>" class="button button-small">
                    <?php _e( 'Download PDF', 'movimento-livre' ); ?>
                </a>
            <?php else : ?>
                <span style="color: #999;"><?php _e( 'Não enviado', 'movimento-livre' ); ?></span>
            <?php endif; ?>
        </div>

        <?php if ( current_user_can( 'movliv_manage_forms' ) ) : ?>
        <div style="border-top: 1px solid #ddd; padding-top: 15px;">
            <strong><?php _e( 'Ações Rápidas:', 'movimento-livre' ); ?></strong><br>
            <button type="button" class="button button-small" onclick="movlivSendFormLink(<?php echo $post->ID; ?>, 'emprestimo')">
                <?php _e( 'Enviar Link Empréstimo', 'movimento-livre' ); ?>
            </button><br><br>
            <button type="button" class="button button-small" onclick="movlivSendFormLink(<?php echo $post->ID; ?>, 'devolucao')">
                <?php _e( 'Enviar Link Devolução', 'movimento-livre' ); ?>
            </button>
        </div>
        <?php endif; ?>

        <script>
        function movlivSendFormLink(orderId, type) {
            if (confirm('Enviar link do formulário por email?')) {
                jQuery.post(ajaxurl, {
                    action: 'movliv_send_form_link',
                    order_id: orderId,
                    form_type: type,
                    nonce: '<?php echo wp_create_nonce( 'movliv_nonce' ); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Link enviado com sucesso!');
                    } else {
                        alert('Erro ao enviar link: ' + response.data);
                    }
                });
            }
        }
        </script>
        <?php
    }

    /**
     * Exibe campos extras no admin do pedido
     */
    public function display_order_extra_fields( $order ) {
        $is_movimento_livre = get_post_meta( $order->get_id(), '_is_movimento_livre', true );
        
        if ( $is_movimento_livre === 'yes' ) {
            echo '<div style="background: #e1f5fe; padding: 10px; margin: 10px 0; border-left: 4px solid #0277bd;">';
            echo '<strong>🦽 ' . __( 'Empréstimo do Movimento Livre', 'movimento-livre' ) . '</strong>';
            echo '</div>';
            
            // ✅ NOVO: Exibe CPF do Solicitante
            $cpf_solicitante = get_post_meta( $order->get_id(), '_cpf_solicitante', true );
            if ( ! empty( $cpf_solicitante ) ) {
                echo '<div style="margin: 15px 0; padding: 10px; background: #f0f0f1; border-radius: 3px;">';
                echo '<h4 style="margin: 0 0 10px 0;">👤 Dados do Solicitante</h4>';
                echo '<p><strong>CPF:</strong> ' . esc_html( $this->format_cpf( $cpf_solicitante ) ) . '</p>';
                echo '</div>';
            }
            
            // Exibe dados do Padrinho/Responsável, se existirem
            $padrinho_nome = get_post_meta( $order->get_id(), '_movliv_padrinho_nome', true );
            
            // ✅ DEBUG: Log para verificar dados
            error_log( "MovLiv: Verificando dados do padrinho para pedido " . $order->get_id() . ":" );
            error_log( "MovLiv: - _movliv_padrinho_nome: " . ( $padrinho_nome ?: 'VAZIO' ) );
            error_log( "MovLiv: - _movliv_padrinho_cpf: " . ( get_post_meta( $order->get_id(), '_movliv_padrinho_cpf', true ) ?: 'VAZIO' ) );
            
            if ( ! empty( $padrinho_nome ) ) {
                echo '<div style="margin: 15px 0; padding: 10px; background: #f0f0f1; border-radius: 3px;">';
                echo '<h4 style="margin: 0 0 10px 0;">📋 Dados do Padrinho/Responsável</h4>';
                
                $p_fields = array(
                    '_movliv_padrinho_nome' => __( 'Nome', 'movimento-livre' ),
                    '_movliv_padrinho_cpf' => __( 'CPF', 'movimento-livre' ),
                    '_movliv_padrinho_endereco' => __( 'Endereço', 'movimento-livre' ),
                    '_movliv_padrinho_numero' => __( 'Número', 'movimento-livre' ),
                    '_movliv_padrinho_complemento' => __( 'Complemento', 'movimento-livre' ),
                    '_movliv_padrinho_cidade' => __( 'Cidade', 'movimento-livre' ),
                    '_movliv_padrinho_estado' => __( 'Estado', 'movimento-livre' ),
                    '_movliv_padrinho_cep' => __( 'CEP', 'movimento-livre' ),
                    '_movliv_padrinho_telefone' => __( 'Telefone', 'movimento-livre' )
                );
                
                foreach ( $p_fields as $key => $label ) {
                    $val = get_post_meta( $order->get_id(), $key, true );
                    if ( $val !== '' ) {
                        echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $val ) . '</p>';
                    }
                }
                echo '</div>';
            }
                }
    }
    
    /**
     * ✅ NOVO: Formata CPF para exibição
     */
    private function format_cpf( $cpf ) {
        // Remove caracteres não numéricos
        $cpf = preg_replace( '/[^0-9]/', '', $cpf );
        
        // Verifica se tem 11 dígitos
        if ( strlen( $cpf ) != 11 ) {
            return $cpf; // Retorna original se não for válido
        }
        
        // Formata: 000.000.000-00
        return substr( $cpf, 0, 3 ) . '.' . 
               substr( $cpf, 3, 3 ) . '.' . 
               substr( $cpf, 6, 3 ) . '-' . 
               substr( $cpf, 9, 2 );
    }
    
    /**
     * Adiciona colunas na lista de pedidos
     */
    public function add_order_columns( $columns ) {
        $new_columns = array();
        
        foreach ( $columns as $key => $column ) {
            $new_columns[ $key ] = $column;
            
            if ( $key === 'order_status' ) {
                $new_columns['movliv_cadeira'] = __( 'Cadeira', 'movimento-livre' );
                $new_columns['movliv_devolucao'] = __( 'Devolução', 'movimento-livre' );
            }
        }
        
        return $new_columns;
    }

    /**
     * Exibe conteúdo das colunas customizadas
     */
    public function display_order_column_content( $column, $order_id ) {
        $order = wc_get_order( $order_id );
        
        switch ( $column ) {
            case 'movliv_cadeira':
                foreach ( $order->get_items() as $item ) {
                    $product = $item->get_product();
                    if ( $product ) {
                        echo esc_html( $product->get_sku() );
                        break;
                    }
                }
                break;
                
            case 'movliv_devolucao':
                $data_prevista = get_post_meta( $order_id, '_data_prevista_devolucao', true );
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
                break;
        }
    }

    /**
     * Adiciona botões de ação rápida
     */
    public function add_order_actions( $actions, $order ) {
        $is_movimento_livre = get_post_meta( $order->get_id(), '_is_movimento_livre', true );
        
        if ( $is_movimento_livre === 'yes' ) {
            $actions['movliv_send_reminder'] = array(
                'url' => wp_nonce_url( 
                    admin_url( 'admin-ajax.php?action=movliv_send_reminder&order_id=' . $order->get_id() ), 
                    'send_reminder' 
                ),
                'name' => __( 'Lembrete Devolução', 'movimento-livre' ),
                'action' => 'movliv_send_reminder'
            );
        }
        
        return $actions;
    }

    /**
     * Manipula ações rápidas
     */
    public function handle_quick_actions() {
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'send_reminder' ) ) {
            wp_die( 'Ação não autorizada.' );
        }
        
        $order_id = intval( $_GET['order_id'] );
        
        // Enviar lembrete
        do_action( 'movliv_send_devolucao_reminder', $order_id );
        
        wp_redirect( admin_url( 'edit.php?post_type=shop_order&reminder_sent=1' ) );
        exit;
    }

    /**
     * Customiza assuntos dos emails
     */
    public function custom_email_subject( $subject, $order ) {
        $is_movimento_livre = get_post_meta( $order->get_id(), '_is_movimento_livre', true );
        
        if ( $is_movimento_livre === 'yes' ) {
            $replacements = array(
                'pedido' => 'empréstimo',
                'Pedido' => 'Empréstimo',
                'compra' => 'empréstimo'
            );
            
            foreach ( $replacements as $search => $replace ) {
                $subject = str_replace( $search, $replace, $subject );
            }
        }
        
        return $subject;
    }

    /**
     * ✅ CORREÇÃO: Previne processamento automático para empréstimos
     */
    public function prevent_auto_processing_for_loans( $status, $order_id, $order ) {
        // Verifica se é um empréstimo (valor zero com produtos)
        $is_loan = ( $order->get_total() == 0 );
        $has_products = count( $order->get_items() ) > 0;
        
        if ( $is_loan && $has_products ) {
            // ✅ CORREÇÃO: Verifica se já tem formulário enviado
            $has_form = get_post_meta( $order_id, '_formulario_emprestimo_pdf', true ) || 
                       get_post_meta( $order_id, '_form_emprestimo_pdf', true );
            
            if ( ! $has_form ) {
                error_log( "MovLiv: PREVENINDO auto-processing para empréstimo {$order_id} - mantendo status 'on-hold' (sem formulário)" );
                return 'on-hold'; // Força status "Aguardando"
            } else {
                error_log( "MovLiv: Permitindo auto-processing para empréstimo {$order_id} - formulário já enviado" );
                return $status; // Permite status original
            }
        }
        
        return $status; // Mantém status original para outros tipos de pedido
    }

    /**
     * ✅ NOVO: Intercepta mudanças de status em tempo real
     */
    public function intercept_status_change( $order_id, $old_status, $new_status, $order ) {
        // Verifica se é um empréstimo (valor zero com produtos)
        $is_loan = ( $order->get_total() == 0 );
        $has_products = count( $order->get_items() ) > 0;
        
        if ( ! $is_loan || ! $has_products ) {
            return; // Não é empréstimo
        }
        
        error_log( "MovLiv: Interceptando mudança de status do pedido {$order_id}: {$old_status} -> {$new_status}" );
        
        // Se está tentando mudar para 'processing' sem formulário
        if ( $new_status === 'processing' ) {
            $has_form = get_post_meta( $order_id, '_formulario_emprestimo_pdf', true ) || 
                       get_post_meta( $order_id, '_form_emprestimo_pdf', true );
            
            if ( ! $has_form ) {
                error_log( "MovLiv: BLOQUEANDO mudança para 'processing' - pedido {$order_id} não tem formulário enviado" );
                
                // Força status de volta para 'on-hold'
                $order->update_status( 'on-hold', __( 'Status bloqueado: empréstimo deve aguardar formulário antes de ser processado.', 'movimento-livre' ) );
                
                // Adiciona nota explicativa
                $order->add_order_note( 
                    __( 'Mudança para "Emprestado" bloqueada automaticamente - aguardando formulário de retirada.', 'movimento-livre' ),
                    false
                );
                
                // Previne a mudança de status
                wp_die( __( 'Este empréstimo não pode ser marcado como "Emprestado" até que o formulário de retirada seja enviado.', 'movimento-livre' ) );
            } else {
                error_log( "MovLiv: Permitindo mudança para 'processing' - pedido {$order_id} tem formulário enviado" );
            }
        }
    }

    /**
     * ✅ NOVO: Desabilita gateway de pagamento para empréstimos gratuitos
     */
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
                error_log( "MovLiv: Pedido gratuito de empréstimo detectado - desabilitando gateway de pagamento" );
                return false; // Não precisa de pagamento
            }
        }
        
        return $needs_payment;
    }

    /**
     * ✅ NOVO: Processa pedidos gratuitos automaticamente
     */
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

    /**
     * Redireciona para formulário após checkout
     */
    public function redirect_to_loan_form( $order_id ) {
        if ( ! $order_id ) {
            return;
        }
        
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            error_log( "MovLiv: Pedido {$order_id} não encontrado para redirecionamento" );
            return;
        }
        
        // Verifica se é um empréstimo (pedido gratuito com produtos)
        $is_loan = ( $order->get_total() == 0 );
        $has_products = count( $order->get_items() ) > 0;
        
        if ( ! $is_loan || ! $has_products ) {
            error_log( "MovLiv: Pedido {$order_id} não é empréstimo - redirecionamento ignorado" );
            return;
        }
        
        // Busca página do formulário
        $form_page = get_page_by_path( 'formulario-de-emprestimo' );
        if ( ! $form_page ) {
            error_log( "MovLiv: Página do formulário não encontrada" );
            return;
        }
        
        // Constrói URL do formulário
        $redirect_url = home_url( '/formulario-de-emprestimo/' );
        $redirect_url = add_query_arg(
            array(
                'movliv_action' => 'form_emprestimo',
                'order_id' => $order_id,
                'order_key' => $order->get_order_key()
            ),
            $redirect_url
        );

        // Faz o redirecionamento
        $redirect_url = str_replace( array('&amp;', '#038;'), '&', $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }



    /**
     * ✅ NOVO: Previne mudanças automáticas de status para empréstimos
     * Executa com prioridade 1 para interceptar antes de outros hooks
     */
    public function prevent_automatic_status_changes( $order_id, $old_status, $new_status, $order ) {
        // Verifica se é um empréstimo (valor zero com produtos)
        $is_loan = ( $order->get_total() == 0 );
        $has_products = count( $order->get_items() ) > 0;
        
        if ( ! $is_loan || ! $has_products ) {
            return; // Não é empréstimo
        }
        
        error_log( "MovLiv: Verificando mudança automática de status: {$old_status} -> {$new_status} para empréstimo {$order_id}" );
        
        // Se está tentando mudar para 'processing' automaticamente (sem formulário)
        if ( $new_status === 'processing' && $old_status === 'on-hold' ) {
            $has_form = get_post_meta( $order_id, '_formulario_emprestimo_pdf', true ) || 
                       get_post_meta( $order_id, '_form_emprestimo_pdf', true );
            
            if ( ! $has_form ) {
                error_log( "MovLiv: BLOQUEANDO mudança automática para 'processing' - empréstimo {$order_id} não tem formulário" );
                
                // Força status de volta para 'on-hold'
                $order->update_status( 'on-hold', __( 'Status bloqueado automaticamente: empréstimo deve aguardar formulário antes de ser processado.', 'movimento-livre' ) );
                
                // Adiciona nota explicativa
                $order->add_order_note( 
                    __( 'Mudança automática para "Emprestado" bloqueada - aguardando formulário de retirada.', 'movimento-livre' ),
                    false
                );
                
                // Previne a mudança de status retornando false
                return false;
            } else {
                error_log( "MovLiv: Permitindo mudança para 'processing' - empréstimo {$order_id} tem formulário enviado" );
            }
        }
        
        // Se está tentando mudar para qualquer status que não seja 'on-hold' sem formulário
        if ( $new_status !== 'on-hold' && $old_status === 'on-hold' ) {
            $has_form = get_post_meta( $order_id, '_formulario_emprestimo_pdf', true ) || 
                       get_post_meta( $order_id, '_form_emprestimo_pdf', true );
            
            if ( ! $has_form ) {
                error_log( "MovLiv: BLOQUEANDO mudança de 'on-hold' para '{$new_status}' - empréstimo {$order_id} não tem formulário" );
                
                // Força status de volta para 'on-hold'
                $order->update_status( 'on-hold', __( 'Status bloqueado: empréstimo deve aguardar formulário antes de qualquer mudança.', 'movimento-livre' ) );
                
                // Adiciona nota explicativa
                $order->add_order_note( 
                    __( 'Mudança de status bloqueada automaticamente - aguardando formulário de retirada.', 'movimento-livre' ),
                    false
                );
                
                // Previne a mudança de status
                return false;
            }
        }
    }

    /**
     * ✅ NOVO: Hook para interceptar status no momento da criação
     */
    public function force_new_order_status( $status, $order ) {
        // Verifica se é um empréstimo (valor zero com produtos)
        $is_loan = ( $order->get_total() == 0 );
        $has_products = count( $order->get_items() ) > 0;
        
        if ( $is_loan && $has_products ) {
            error_log( "MovLiv: FORÇANDO status inicial 'on-hold' para empréstimo " . $order->get_id() . " (hook woocommerce_new_order_status)" );
            return 'on-hold'; // Força status "Aguardando"
        }
        
        return $status; // Mantém status original para outros tipos de pedido
    }

    /**
     * ✅ NOVO: Hook para interceptar status após criação
     */
    public function ensure_loan_status_after_creation( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            error_log( "MovLiv: Pedido {$order_id} não encontrado para garantir status" );
            return;
        }

        // Verifica se é um empréstimo (valor zero com produtos)
        $is_loan = ( $order->get_total() == 0 );
        $has_products = count( $order->get_items() ) > 0;
        
        if ( $is_loan && $has_products ) {
            error_log( "MovLiv: Garantindo status 'on-hold' para empréstimo " . $order->get_id() . " após criação" );
            
            // Força status "Aguardando"
            $order->update_status( 'on-hold', __( 'Empréstimo aguardando preenchimento do formulário de retirada.', 'movimento-livre' ) );
            
            // Marca como empréstimo do Movimento Livre
            $order->update_meta_data( '_is_movimento_livre', 'yes' );
            
            // Define data prevista de devolução (30 dias)
            $data_prevista = date( 'Y-m-d', strtotime( '+30 days' ) );
            $order->update_meta_data( '_data_prevista_devolucao', $data_prevista );
            
            // Adiciona nota automática
            $order->add_order_note( 
                __( 'Status garantido como "Aguardando" após criação do empréstimo.', 'movimento-livre' ),
                false
            );
            
            $order->save();
            
            error_log( "MovLiv: Status do pedido " . $order->get_id() . " garantido como 'on-hold' (empréstimo)" );
        }
    }

    /**
     * Registra hooks do WooCommerce
     */
    public function register_hooks() {
        // Hook para redirecionamento após checkout
        add_action( 'woocommerce_thankyou', array( $this, 'redirect_to_loan_form' ), 5 );
        
        // Outros hooks...
    }
} 