<?php
/**
 * Validador de CPF e Controle de Empréstimos - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para validação de CPF e controle de limite de empréstimos
 */
class MOVLIV_CPF_Validator {

    /**
     * Instância única da classe
     * @var MOVLIV_CPF_Validator
     */
    private static $instance = null;

    /**
     * Limite máximo de empréstimos por CPF
     * @var int
     */
    private $max_emprestimos = 2;

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_CPF_Validator();
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
     * Inicializa hooks para checkout clássico do WooCommerce
     */
    public function init_hooks() {
        // Valida CPF no checkout (usando campo do plugin)
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_cpf_checkout' ) );
        
        // Salva CPF no pedido (copia do billing_cpf para _cpf_solicitante)
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_cpf_order_meta' ) );
        
        // Hook adicional para salvar CPF (compatibilidade HPOS)
        add_action( 'woocommerce_checkout_create_order', array( $this, 'save_cpf_order_object' ), 10, 2 );
        
        // Exibe CPF no admin do pedido
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_cpf_admin_order' ) );
        
        // Adiciona coluna CPF na lista de pedidos
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_cpf_column_orders_list' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'display_cpf_column_content' ), 10, 2 );
        
        // Torna campo CPF obrigatório no plugin
        add_filter( 'woocommerce_billing_fields', array( $this, 'make_cpf_required' ) );
        
        // Adiciona validação customizada via JavaScript
        add_action( 'wp_footer', array( $this, 'add_cpf_validation_script' ) );
        
        // Debug - Log quando hooks são executados
        add_action( 'woocommerce_checkout_init', array( $this, 'debug_checkout_init' ) );
    }

    /**
     * Torna o campo CPF obrigatório no plugin
     */
    public function make_cpf_required( $fields ) {
        error_log( "MovLiv: make_cpf_required executado" );
        
        if ( isset( $fields['billing_cpf'] ) ) {
            $fields['billing_cpf']['required'] = true;
            $fields['billing_cpf']['label'] = __( 'CPF *', 'movimento-livre' );
            error_log( "MovLiv: Campo billing_cpf tornado obrigatório" );
        }
        
        return $fields;
    }


    /**
     * Adiciona validação customizada via JavaScript para o campo CPF do plugin
     */
    public function add_cpf_validation_script() {
        if ( ! is_checkout() ) {
            return;
        }
        
        static $script_added = false;
        if ( $script_added ) {
            return;
        }
        $script_added = true;
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('MovLiv: Validação CPF para plugin ativa');
            
            // Validação no envio do formulário para empréstimos
            $('form.checkout').on('submit', function(e) {
                // Verifica se é um empréstimo (valor 0) - só valida CPF para empréstimos
                const totalValue = $('.order-total .amount').text();
                const isLoan = totalValue.includes('0,00') || totalValue.includes('0.00');
                
                if (isLoan) {
                    const cpfInput = $('input[name="billing_cpf"]');
                    const cpf = cpfInput.val();
                    
                    if (!cpf || cpf.replace(/\D/g, '').length !== 11) {
                        alert('⚠️ CPF é obrigatório para empréstimos de cadeiras de rodas. Por favor, preencha um CPF válido com 11 dígitos.');
                        cpfInput.focus();
                        e.preventDefault();
                        return false;
                    }
                    
                    console.log('MovLiv: CPF validado para empréstimo:', cpf);
                }
            });
            
            // Adiciona indicação visual de que é obrigatório para empréstimos
            const cpfField = $('#billing_cpf_field');
            if (cpfField.length) {
                cpfField.append('<small style="color: #007cba; font-style: italic;">⚠️ Obrigatório para empréstimos de cadeiras de rodas</small>');
            }
        });
        </script>
        <?php
    }

    /**
     * Debug - log quando checkout é inicializado
     */
    public function debug_checkout_init() {
        error_log( "MovLiv: Checkout inicializado - hooks registrados (usando plugin CPF)" );
    }

    /**
     * Salva CPF no objeto do pedido (compatibilidade HPOS)
     */
    public function save_cpf_order_object( $order, $data ) {
        // Pega CPF do campo do plugin
        $cpf = '';
        if ( isset( $data['billing_cpf'] ) && ! empty( $data['billing_cpf'] ) ) {
            $cpf = sanitize_text_field( $data['billing_cpf'] );
        } elseif ( isset( $_POST['billing_cpf'] ) && ! empty( $_POST['billing_cpf'] ) ) {
            $cpf = sanitize_text_field( $_POST['billing_cpf'] );
        }
        
        if ( ! empty( $cpf ) ) {
            // Remove formatação e salva apenas números
            $cpf = preg_replace( '/[^0-9]/', '', $cpf );
            $order->update_meta_data( '_cpf_solicitante', $cpf );
            error_log( "MovLiv: CPF salvo via order object (do plugin): " . $cpf );
        }
    }

    /**
     * Valida CPF no checkout (usando campo do plugin)
     */
    public function validate_cpf_checkout() {
        error_log( "MovLiv: validate_cpf_checkout executado" );
        
        // Verifica se é um empréstimo (carrinho com valor 0)
        $cart_total = WC()->cart->get_total( 'edit' );
        $is_loan = ( $cart_total == 0 );
        
        if ( ! $is_loan ) {
            error_log( "MovLiv: Não é empréstimo (valor: " . $cart_total . ") - validação CPF ignorada" );
            return;
        }
        
        $cpf = isset( $_POST['billing_cpf'] ) ? sanitize_text_field( $_POST['billing_cpf'] ) : '';
        
        // Remove formatação do CPF
        $cpf = preg_replace( '/[^0-9]/', '', $cpf );
        
        // Verifica se CPF foi preenchido
        if ( empty( $cpf ) ) {
            wc_add_notice( __( 'CPF é obrigatório para empréstimos de cadeiras de rodas.', 'movimento-livre' ), 'error' );
            error_log( "MovLiv: Erro - CPF não preenchido para empréstimo" );
            return;
        }
        
        // Valida formato do CPF
        if ( ! $this->validate_cpf_format( $cpf ) ) {
            wc_add_notice( __( 'CPF inválido. Digite um CPF válido com 11 números.', 'movimento-livre' ), 'error' );
            error_log( "MovLiv: Erro - CPF inválido: " . $cpf );
            return;
        }
        
        // Verifica limite de empréstimos
        $active_loans = $this->count_active_loans( $cpf );
        if ( $active_loans >= $this->max_emprestimos ) {
            wc_add_notice( 
                sprintf( 
                    __( 'Este CPF já possui %d empréstimos ativos. Limite máximo: %d empréstimos por CPF. Para solicitar novo empréstimo, é necessário devolver as cadeiras em uso.', 'movimento-livre' ),
                    $active_loans,
                    $this->max_emprestimos
                ), 
                'error' 
            );
            error_log( "MovLiv: Erro - Limite de empréstimos excedido para CPF: " . $cpf . " (Ativos: " . $active_loans . ")" );
            return;
        }
        
        error_log( "MovLiv: CPF validado com sucesso para empréstimo: " . $cpf . " (Empréstimos ativos: " . $active_loans . ")" );
    }

    /**
     * Salva CPF no meta do pedido (do campo do plugin)
     */
    public function save_cpf_order_meta( $order_id ) {
        if ( isset( $_POST['billing_cpf'] ) && ! empty( $_POST['billing_cpf'] ) ) {
            $cpf = sanitize_text_field( $_POST['billing_cpf'] );
            // Remove formatação e salva apenas números
            $cpf = preg_replace( '/[^0-9]/', '', $cpf );
            update_post_meta( $order_id, '_cpf_solicitante', $cpf );
            error_log( "MovLiv: CPF salvo no pedido #" . $order_id . " (do plugin): " . $cpf );
        }
    }

    /**
     * Exibe CPF no admin do pedido
     */
    public function display_cpf_admin_order( $order ) {
        $cpf = $order->get_meta( '_cpf_solicitante' );
        if ( ! empty( $cpf ) ) {
            echo '<p><strong>' . __( 'CPF do Solicitante:', 'movimento-livre' ) . '</strong> ' . esc_html( $this->format_cpf( $cpf ) ) . '</p>';
        }
    }

    /**
     * Adiciona coluna CPF na lista de pedidos
     */
    public function add_cpf_column_orders_list( $columns ) {
        $new_columns = array();
        
        foreach ( $columns as $key => $column ) {
            $new_columns[ $key ] = $column;
            if ( $key === 'order_status' ) {
                $new_columns['cpf_solicitante'] = __( 'CPF Solicitante', 'movimento-livre' );
            }
        }
        
        return $new_columns;
    }

    /**
     * Exibe conteúdo da coluna CPF
     */
    public function display_cpf_column_content( $column, $order_id ) {
        if ( $column === 'cpf_solicitante' ) {
            $cpf = get_post_meta( $order_id, '_cpf_solicitante', true );
            echo ! empty( $cpf ) ? esc_html( $this->format_cpf( $cpf ) ) : '-';
        }
    }

    /**
     * Valida formato do CPF
     */
    public function validate_cpf_format( $cpf ) {
        // Remove caracteres não numéricos
        $cpf = preg_replace( '/[^0-9]/', '', $cpf );
        
        // Verifica se tem 11 dígitos
        if ( strlen( $cpf ) != 11 ) {
            return false;
        }
        
        // Verifica se não são todos os dígitos iguais
        if ( preg_match( '/(\d)\1{10}/', $cpf ) ) {
            return false;
        }
        
        // Validação do dígito verificador
        for ( $t = 9; $t < 11; $t++ ) {
            for ( $d = 0, $c = 0; $c < $t; $c++ ) {
                $d += $cpf[$c] * ( ( $t + 1 ) - $c );
            }
            $d = ( ( 10 * $d ) % 11 ) % 10;
            if ( $cpf[$c] != $d ) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Formata CPF para exibição
     */
    public function format_cpf( $cpf ) {
        $cpf = preg_replace( '/[^0-9]/', '', $cpf );
        if ( strlen( $cpf ) === 11 ) {
            return substr( $cpf, 0, 3 ) . '.' . substr( $cpf, 3, 3 ) . '.' . substr( $cpf, 6, 3 ) . '-' . substr( $cpf, 9, 2 );
        }
        return $cpf;
    }

    /**
     * Conta empréstimos ativos de um CPF
     */
    public function count_active_loans( $cpf ) {
        $active_statuses = array( 'processing', 'on-hold', 'pending' );
        $orders = $this->get_orders_by_cpf( $cpf, $active_statuses );
        return count( $orders );
    }

    /**
     * Obtém pedidos por CPF
     */
    public function get_orders_by_cpf( $cpf, $status = 'any' ) {
        $args = array(
            'meta_query' => array(
                array(
                    'key'     => '_cpf_solicitante',
                    'value'   => $cpf,
                    'compare' => '='
                )
            ),
            'limit' => -1
        );
        
        if ( $status !== 'any' ) {
            $args['status'] = $status;
        }
        
        return wc_get_orders( $args );
    }

    /**
     * Verifica se CPF pode fazer novo empréstimo
     */
    public function can_make_new_loan( $cpf ) {
        return $this->count_active_loans( $cpf ) < $this->max_emprestimos;
    }

    /**
     * Obtém CPF de um pedido
     */
    public static function get_order_cpf( $order_id ) {
        return get_post_meta( $order_id, '_cpf_solicitante', true );
    }

    /**
     * Define limite máximo de empréstimos
     */
    public function set_max_loans( $limit ) {
        $this->max_emprestimos = (int) $limit;
    }

    /**
     * Obtém limite máximo de empréstimos
     */
    public function get_max_loans() {
        return $this->max_emprestimos;
    }
} 