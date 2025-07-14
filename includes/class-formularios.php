<?php
/**
 * Gerenciador de Formulários - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para gerenciar formulários do sistema
 */
class MOVLIV_Formularios {

    /**
     * Instância única da classe
     * @var MOVLIV_Formularios
     */
    private static $instance = null;

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_Formularios();
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
        // Processa formulários via AJAX
        add_action( 'wp_ajax_movliv_submit_emprestimo', array( $this, 'handle_emprestimo_form' ) );
        add_action( 'wp_ajax_nopriv_movliv_submit_emprestimo', array( $this, 'handle_emprestimo_form' ) );
        
        add_action( 'wp_ajax_movliv_submit_devolucao', array( $this, 'handle_devolucao_form' ) );
        add_action( 'wp_ajax_nopriv_movliv_submit_devolucao', array( $this, 'handle_devolucao_form' ) );
        
        add_action( 'wp_ajax_movliv_submit_avaliacao', array( $this, 'handle_avaliacao_form' ) );
        
        // Adiciona scripts e estilos
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        // Download de PDFs
        add_action( 'wp_ajax_movliv_download_pdf', array( $this, 'handle_pdf_download' ) );
        add_action( 'wp_ajax_nopriv_movliv_download_pdf', array( $this, 'handle_pdf_download' ) );
    }

    /**
     * Carrega scripts e estilos
     */
    public function enqueue_scripts() {
        // Carrega apenas em páginas com shortcodes
        global $post;
        
        if ( ! is_object( $post ) ) {
            return;
        }
        
        if ( has_shortcode( $post->post_content, 'movliv_form_emprestimo' ) ||
             has_shortcode( $post->post_content, 'movliv_form_devolucao' ) ||
             has_shortcode( $post->post_content, 'movliv_form_avaliacao' ) ) {
            
            wp_enqueue_script( 
                'movliv-forms', 
                MOVLIV_PLUGIN_URL . 'assets/js/forms.js', 
                array( 'jquery' ), 
                MOVLIV_VERSION, 
                true 
            );
            
            wp_enqueue_style( 
                'movliv-forms', 
                MOVLIV_PLUGIN_URL . 'assets/css/forms.css', 
                array(), 
                MOVLIV_VERSION 
            );
            
            wp_enqueue_style( 
                'movliv-frontend', 
                MOVLIV_PLUGIN_URL . 'assets/css/frontend.css', 
                array(), 
                MOVLIV_VERSION 
            );
            
            wp_enqueue_script( 
                'movliv-frontend', 
                MOVLIV_PLUGIN_URL . 'assets/js/frontend.js', 
                array( 'jquery' ), 
                MOVLIV_VERSION, 
                true 
            );
            
            wp_localize_script( 'movliv-forms', 'movliv_ajax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'movliv_form_nonce' ),
                'messages' => array(
                    'success' => __( 'Formulário enviado com sucesso!', 'movimento-livre' ),
                    'error' => __( 'Erro ao enviar formulário. Tente novamente.', 'movimento-livre' ),
                    'required' => __( 'Por favor, preencha todos os campos obrigatórios.', 'movimento-livre' )
                )
            ) );
        }
    }

    /**
     * ✅ CORREÇÃO: Função helper para buscar CPF do usuário correto
     */
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

    /**
     * Processa formulário de empréstimo
     */
    public function handle_emprestimo_form() {
        // Verifica nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'movliv_form_nonce' ) ) {
            wp_send_json_error( 'Token inválido.' );
        }

        $order_id = intval( $_POST['order_id'] );
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            wp_send_json_error( 'Pedido não encontrado.' );
        }

        // Valida dados do formulário
        $form_data = $this->validate_emprestimo_data( $_POST );
        
        if ( is_wp_error( $form_data ) ) {
            wp_send_json_error( $form_data->get_error_message() );
        }

        // ✅ NOVO: Salva todos os dados do formulário no banco
        update_post_meta( $order_id, '_movliv_emprestimo_nome', $form_data['nome'] );
        update_post_meta( $order_id, '_movliv_emprestimo_telefone', $form_data['telefone'] );
        update_post_meta( $order_id, '_movliv_emprestimo_endereco', $form_data['endereco'] );
        update_post_meta( $order_id, '_movliv_emprestimo_data_prevista', $form_data['data_prevista_devolucao'] );
        update_post_meta( $order_id, '_movliv_emprestimo_responsavel', $form_data['responsavel_atendimento'] );
        update_post_meta( $order_id, '_movliv_emprestimo_observacoes', $form_data['observacoes'] );
        update_post_meta( $order_id, '_movliv_emprestimo_data', current_time('mysql') );
        
        // ✅ NOVO: Salva dados do Padrinho
        update_post_meta( $order_id, '_movliv_padrinho_nome', $form_data['padrinho_nome'] );
        update_post_meta( $order_id, '_movliv_padrinho_cpf', $form_data['padrinho_cpf'] );
        update_post_meta( $order_id, '_movliv_padrinho_endereco', $form_data['padrinho_endereco'] );
        update_post_meta( $order_id, '_movliv_padrinho_numero', $form_data['padrinho_numero'] );
        update_post_meta( $order_id, '_movliv_padrinho_complemento', $form_data['padrinho_complemento'] );
        update_post_meta( $order_id, '_movliv_padrinho_cidade', $form_data['padrinho_cidade'] );
        update_post_meta( $order_id, '_movliv_padrinho_estado', $form_data['padrinho_estado'] );
        update_post_meta( $order_id, '_movliv_padrinho_cep', $form_data['padrinho_cep'] );
        update_post_meta( $order_id, '_movliv_padrinho_telefone', $form_data['padrinho_telefone'] );

        // ✅ CORREÇÃO: Gera PDF usando a classe PDF Generator
        $pdf_generator = MOVLIV_PDF_Generator::getInstance();
        $pdf_path = $pdf_generator->generate_emprestimo_pdf( $order_id, $form_data );
        
        if ( ! $pdf_path ) {
            wp_send_json_error( 'Erro ao gerar documento PDF.' );
        }

        // ✅ CORREÇÃO: Salva caminho do PDF no pedido
        update_post_meta( $order_id, '_formulario_emprestimo_pdf', $pdf_path );

        // ✅ NOVO: Atualiza status do pedido para "Processando" (Emprestado)
        $order->update_status( 'processing', __( 'Formulário de empréstimo recebido. Cadeira emprestada.', 'movimento-livre' ) );
        
        // ✅ NOVO: Atualiza status das cadeiras e reduz estoque
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $product = wc_get_product( $product_id );
            
            if ( $product ) {
                // Atualizar status da cadeira para "Emprestado"
                MOVLIV_Status_Manager::update_product_status( $product_id, 'emprestado' );
                
                // Reduzir estoque (apenas se ainda tem estoque)
                $current_stock = $product->get_stock_quantity();
                if ( $current_stock > 0 ) {
                    $product->set_stock_quantity( $current_stock - 1 );
                    $product->save();
                    
                    error_log( "MovLiv: Estoque da cadeira {$product_id} reduzido para " . ($current_stock - 1) );
                }
                
                // Salvar data de empréstimo
                update_post_meta( $product_id, '_data_emprestimo', current_time( 'mysql' ) );
                update_post_meta( $product_id, '_emprestado_pedido_id', $order_id );
                
                error_log( "MovLiv: Status da cadeira {$product_id} atualizado para 'emprestado'" );
            }
        }
        
        // Adiciona nota
        $order->add_order_note( 
            sprintf( 
                __( 'Formulário de empréstimo preenchido por %s. PDF salvo. Status das cadeiras atualizado para "Emprestado".', 'movimento-livre' ),
                $form_data['nome']
            )
        );

        wp_send_json_success( array(
            'message' => __( 'Formulário de empréstimo enviado com sucesso! Cadeira(s) emprestada(s).', 'movimento-livre' ),
            'redirect' => $this->get_success_page_url( 'emprestimo' )
        ) );
    }

    /**
     * Processa formulário de devolução
     */
    public function handle_devolucao_form() {
        // Verifica nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'movliv_form_nonce' ) ) {
            wp_send_json_error( 'Token inválido.' );
        }

        $order_id = intval( $_POST['order_id'] );
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            wp_send_json_error( 'Pedido não encontrado.' );
        }

        // Valida dados do formulário
        $form_data = $this->validate_devolucao_data( $_POST );
        
        if ( is_wp_error( $form_data ) ) {
            wp_send_json_error( $form_data->get_error_message() );
        }

        // ✅ CORREÇÃO: Gera PDF usando a classe PDF Generator
        $pdf_generator = MOVLIV_PDF_Generator::getInstance();
        $pdf_path = $pdf_generator->generate_devolucao_pdf( $order_id, $form_data );
        
        if ( ! $pdf_path ) {
            wp_send_json_error( 'Erro ao gerar documento PDF.' );
        }

        // ✅ CORREÇÃO: Salva caminho do PDF no pedido
        update_post_meta( $order_id, '_formulario_devolucao_pdf', $pdf_path );

        // Atualiza status do pedido para "Devolvido" (status nativo completed)
        $order->update_status( 'completed', __( 'Formulário de devolução recebido. Cadeira devolvida para avaliação.', 'movimento-livre' ) );
        
        // ✅ CORREÇÃO: Atualiza status das cadeiras para "Em Avaliação" e gera formulários de avaliação
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $product = wc_get_product( $product_id );
            
            if ( $product ) {
                // Atualizar status da cadeira para "Em Avaliação"
                MOVLIV_Status_Manager::update_product_status( $product_id, 'em_avaliacao' );
                
                // Marcar data de devolução
                update_post_meta( $product_id, '_data_devolucao', current_time( 'mysql' ) );
                
                // Limpar dados do empréstimo
                delete_post_meta( $product_id, '_emprestado_pedido_id' );
                delete_post_meta( $product_id, '_data_emprestimo' );
                
                // ✅ CORREÇÃO: Gerar formulário de avaliação pendente
                $handler = MOVLIV_Product_Status_Handler::getInstance();
                $handler->create_pending_evaluation( $product_id, $order_id );
                
                error_log( "MovLiv: Status da cadeira {$product_id} atualizado para 'em_avaliacao' e avaliação criada" );
            }
        }
        
        // Adiciona nota
        $order->add_order_note( 
            sprintf( 
                __( 'Formulário de devolução preenchido. PDF salvo. Cadeiras enviadas para avaliação. Observações: %s', 'movimento-livre' ),
                $form_data['observacoes'] ?? 'Nenhuma'
            )
        );

        wp_send_json_success( array(
            'message' => __( 'Formulário de devolução enviado com sucesso! Cadeira(s) enviada(s) para avaliação.', 'movimento-livre' ),
            'redirect' => $this->get_success_page_url( 'devolucao' )
        ) );
    }

    /**
     * Processa formulário de avaliação
     */
    public function handle_avaliacao_form() {
        // Verifica permissões
        if ( ! current_user_can( 'movliv_submit_evaluation' ) ) {
            wp_send_json_error( 'Sem permissão para avaliar cadeiras.' );
        }

        // Verifica nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'movliv_form_nonce' ) ) {
            wp_send_json_error( 'Token inválido.' );
        }

        $product_id = intval( $_POST['product_id'] );
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            wp_send_json_error( 'Cadeira não encontrada.' );
        }

        // Valida dados do formulário
        $form_data = $this->validate_avaliacao_data( $_POST );
        
        if ( is_wp_error( $form_data ) ) {
            wp_send_json_error( $form_data->get_error_message() );
        }

        // ✅ CORREÇÃO: Gera PDF usando a classe PDF Generator
        $pdf_generator = MOVLIV_PDF_Generator::getInstance();
        $pdf_path = $pdf_generator->generate_avaliacao_pdf( $product_id, $form_data );
        
        if ( ! $pdf_path ) {
            wp_send_json_error( 'Erro ao gerar documento PDF.' );
        }

        // ✅ CORREÇÃO: Salva avaliação no histórico do produto
        $avaliacoes = get_post_meta( $product_id, '_avaliacoes_produto', true );
        if ( ! is_array( $avaliacoes ) ) {
            $avaliacoes = array();
        }
        
        $avaliacao_data = array(
            'data' => current_time( 'mysql' ),
            'avaliador' => wp_get_current_user()->display_name,
            'resultado' => $form_data['resultado'],
            'observacoes' => $form_data['observacoes'],
            'pdf_path' => $pdf_path
        );
        
        $avaliacoes[] = $avaliacao_data;
        update_post_meta( $product_id, '_avaliacoes_produto', $avaliacoes );

        // ✅ CORREÇÃO: Lógica de aprovação/reprovação conforme fluxo
        if ( $form_data['resultado'] === 'Aprovada' ) {
            // Aprovada: volta ao estoque e status "Pronta"
            MOVLIV_Status_Manager::update_product_status( $product_id, 'pronta' );
            
            // Retornar ao estoque
            $current_stock = $product->get_stock_quantity();
            $product->set_stock_quantity( $current_stock + 1 );
            $product->save();
            
            // Limpar dados de avaliação pendente
            delete_post_meta( $product_id, '_data_devolucao' );
            delete_post_meta( $product_id, '_data_manutencao' );
            
            error_log( "MovLiv: Cadeira {$product_id} aprovada - voltou ao estoque (estoque: " . ($current_stock + 1) . ")" );
            
            $status_message = __( 'Cadeira aprovada e retornada ao estoque.', 'movimento-livre' );
            
        } else {
            // Reprovada: vai para manutenção
            MOVLIV_Status_Manager::update_product_status( $product_id, 'em_manutencao' );
            
            // Marcar data de entrada em manutenção
            update_post_meta( $product_id, '_data_manutencao', current_time( 'mysql' ) );
            
            // ✅ CORREÇÃO: Gerar novo formulário de avaliação (pós-manutenção)
            $handler = MOVLIV_Product_Status_Handler::getInstance();
            $handler->create_pending_reevaluation( $product_id );
            
            error_log( "MovLiv: Cadeira {$product_id} reprovada - enviada para manutenção" );
            
            $status_message = __( 'Cadeira reprovada e enviada para manutenção. Novo formulário de avaliação será gerado após conserto.', 'movimento-livre' );
        }
        
        // Marca como avaliado
        $handler = MOVLIV_Product_Status_Handler::getInstance();
        $handler->mark_product_evaluated( $product_id );

        wp_send_json_success( array(
            'message' => sprintf( 
                __( 'Avaliação enviada com sucesso! %s', 'movimento-livre' ),
                $status_message
            ),
            'redirect' => admin_url( 'admin.php?page=movimento-livre-cadeiras&avaliacao_completed=1' )
        ) );
    }

    /**
     * Valida dados do formulário de empréstimo
     */
    private function validate_emprestimo_data( $data ) {
        $errors = new WP_Error();
        
        // Campos obrigatórios
        $required_fields = array(
            'nome' => 'Nome completo',
            'telefone' => 'Telefone',
            'endereco' => 'Endereço',
            'data_prevista_devolucao' => 'Data prevista de devolução',
            'responsavel_atendimento' => 'Responsável pelo atendimento',
            'padrinho_nome' => 'Nome do Padrinho',
            'padrinho_cpf' => 'CPF do Padrinho',
            'padrinho_endereco' => 'Endereço do Padrinho',
            'padrinho_numero' => 'Número do Endereço do Padrinho',
            'padrinho_cidade' => 'Cidade do Padrinho',
            'padrinho_estado' => 'Estado do Padrinho',
            'padrinho_cep' => 'CEP do Padrinho',
            'padrinho_telefone' => 'Telefone do Padrinho'
        );
        
        foreach ( $required_fields as $field => $label ) {
            if ( empty( $data[ $field ] ) ) {
                $errors->add( 'required_field', sprintf( __( 'Campo "%s" é obrigatório.', 'movimento-livre' ), $label ) );
            }
        }
        
        if ( $errors->has_errors() ) {
            return $errors;
        }
        
        return array(
            'nome' => sanitize_text_field( $data['nome'] ),
            'telefone' => sanitize_text_field( $data['telefone'] ),
            'endereco' => sanitize_textarea_field( $data['endereco'] ),
            'data_prevista_devolucao' => sanitize_text_field( $data['data_prevista_devolucao'] ),
            'responsavel_atendimento' => sanitize_text_field( $data['responsavel_atendimento'] ),
            'observacoes' => sanitize_textarea_field( $data['observacoes'] ?? '' ),
            'padrinho_nome' => sanitize_text_field( $data['padrinho_nome'] ),
            'padrinho_cpf' => sanitize_text_field( $data['padrinho_cpf'] ),
            'padrinho_endereco' => sanitize_text_field( $data['padrinho_endereco'] ),
            'padrinho_numero' => sanitize_text_field( $data['padrinho_numero'] ),
            'padrinho_complemento' => sanitize_text_field( $data['padrinho_complemento'] ?? '' ),
            'padrinho_cidade' => sanitize_text_field( $data['padrinho_cidade'] ),
            'padrinho_estado' => sanitize_text_field( $data['padrinho_estado'] ),
            'padrinho_cep' => sanitize_text_field( $data['padrinho_cep'] ),
            'padrinho_telefone' => sanitize_text_field( $data['padrinho_telefone'] )
        );
    }

    /**
     * Valida dados do formulário de devolução
     */
    private function validate_devolucao_data( $data ) {
        $errors = new WP_Error();
        
        // Campos obrigatórios
        $required_fields = array(
            'nome' => 'Nome completo',
            'responsavel_devolucao' => 'Responsável pela devolução'
        );
        
        foreach ( $required_fields as $field => $label ) {
            if ( empty( $data[ $field ] ) ) {
                $errors->add( 'required_field', sprintf( __( 'Campo "%s" é obrigatório.', 'movimento-livre' ), $label ) );
            }
        }
        
        if ( $errors->has_errors() ) {
            return $errors;
        }
        
        return array(
            'nome' => sanitize_text_field( $data['nome'] ),
            'estado_devolucao' => sanitize_text_field( $data['estado_devolucao'] ?? 'Conforme recebida' ),
            'responsavel_devolucao' => sanitize_text_field( $data['responsavel_devolucao'] ),
            'observacoes' => sanitize_textarea_field( $data['observacoes'] ?? '' )
        );
    }

    /**
     * Valida dados do formulário de avaliação
     */
    private function validate_avaliacao_data( $data ) {
        $errors = new WP_Error();
        
        // Campos obrigatórios
        $required_fields = array(
            'avaliador' => 'Nome do avaliador',
            'resultado' => 'Resultado da avaliação'
        );
        
        foreach ( $required_fields as $field => $label ) {
            if ( empty( $data[ $field ] ) ) {
                $errors->add( 'required_field', sprintf( __( 'Campo "%s" é obrigatório.', 'movimento-livre' ), $label ) );
            }
        }
        
        // Valida resultado
        if ( ! in_array( $data['resultado'], array( 'Aprovada', 'Reprovada' ) ) ) {
            $errors->add( 'invalid_result', __( 'Resultado deve ser "Aprovada" ou "Reprovada".', 'movimento-livre' ) );
        }
        
        if ( $errors->has_errors() ) {
            return $errors;
        }
        
        $validated = array(
            'avaliador' => sanitize_text_field( $data['avaliador'] ),
            'resultado' => sanitize_text_field( $data['resultado'] ),
            'observacoes' => sanitize_textarea_field( $data['observacoes'] ?? '' )
        );
        
        // Checklist de avaliação
        $checklist_items = array( 'rodas', 'freios', 'estofamento', 'estrutura', 'encosto', 'apoios', 'funcionamento' );
        
        foreach ( $checklist_items as $item ) {
            if ( isset( $data[ $item ] ) ) {
                $validated[ $item ] = sanitize_text_field( $data[ $item ] );
            }
        }
        
        return $validated;
    }

    /**
     * Manipula download de PDFs
     */
    public function handle_pdf_download() {
        // Verifica nonce
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'download_pdf' ) ) {
            wp_die( 'Acesso negado.' );
        }

        $filename = sanitize_file_name( $_GET['file'] );
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/movliv/' . $filename;
        
        if ( ! file_exists( $file_path ) ) {
            wp_die( 'Arquivo não encontrado.' );
        }

        // Verifica permissões
        if ( ! current_user_can( 'movliv_manage_forms' ) && ! current_user_can( 'administrator' ) ) {
            wp_die( 'Sem permissão para acessar este arquivo.' );
        }

        // Headers para download
        header( 'Content-Type: application/pdf' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $file_path ) );
        
        readfile( $file_path );
        exit;
    }

    /**
     * Obtém URL da página de sucesso
     */
    private function get_success_page_url( $form_type ) {
        $pages = array(
            'emprestimo' => 'meus-emprestimos',
            'devolucao' => 'meus-emprestimos',
            'avaliacao' => admin_url( 'edit.php?post_type=product' )
        );
        
        if ( isset( $pages[ $form_type ] ) ) {
            return home_url( '/' . $pages[ $form_type ] );
        }
        
        return home_url();
    }

    /**
     * Obtém formulário de empréstimo HTML
     */
    public function get_emprestimo_form_html( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return '<p>' . __( 'Pedido não encontrado.', 'movimento-livre' ) . '</p>';
        }

        // Verifica se já foi preenchido
        $pdf_path = get_post_meta( $order_id, '_form_emprestimo_pdf', true );
        if ( $pdf_path && file_exists( $pdf_path ) ) {
            return '<p>' . __( 'Formulário de empréstimo já foi preenchido.', 'movimento-livre' ) . '</p>';
        }

        // ✅ CORREÇÃO: Usa função helper para buscar CPF correto
        $cpf = $this->get_user_cpf_from_order( $order_id );
        
        // ✅ NOVO: Busca dados do pedido para pré-preenchimento
        $nome = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $telefone = $order->get_billing_phone();
        $endereco = implode(", ", array_filter([
            $order->get_billing_address_1(),
            $order->get_billing_address_2(),
            $order->get_billing_city(),
            $order->get_billing_state(),
            $order->get_billing_postcode()
        ]));
        $rua = $order->get_billing_address_1();
        $complemento = $order->get_billing_address_2();
        $cidade = $order->get_billing_city();
        $estado = $order->get_billing_state();
        $cep = $order->get_billing_postcode();


        ob_start();
        ?>
        <div class="movliv-form-container">
            <h3><?php _e( 'Formulário de Empréstimo', 'movimento-livre' ); ?></h3>
            
            <form id="movliv-emprestimo-form" class="movliv-form">
                <input type="hidden" name="action" value="movliv_submit_emprestimo">
                <input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'movliv_form_nonce' ); ?>">
                
                <div class="form-row">
                    <label for="nome"><?php _e( 'Nome Completo:', 'movimento-livre' ); ?></label>
                    <input type="hidden" id="nome" name="nome" value="<?php echo esc_attr( $nome ); ?>" required><?php echo esc_attr( $nome ); ?>
                </div>
                
                <div class="form-row">
                    <label for="telefone"><?php _e( 'Telefone:', 'movimento-livre' ); ?></label>
                    <input type="hidden" id="telefone" name="telefone" value="<?php echo esc_attr( $telefone ); ?>" required><?php echo esc_attr( $telefone ); ?>
                </div>
                
                <div class="form-row">
                    <label for="endereco"><?php _e( 'Endereço Completo:', 'movimento-livre' ); ?></label><br>
                    <!-- textarea id="endereco" name="endereco" rows="3" required><?php // echo esc_textarea( $endereco ); ?></textarea> -->
                    <label for="rua">Rua: </label><?php echo esc_attr( $rua ); ?></br>
                    <label for="complemento">Complemento: </label><?php echo esc_attr( $complemento ); ?></br>
                    <label for="cidade">Cidade: </label><?php echo esc_attr( $cidade ); ?></br>
                    <label for="estado">Estado: </label><?php echo esc_attr( $estado ); ?></br>
                    <label for="cep">CEP: </label><?php echo esc_attr( $cep ); ?>
                    <input type="hidden" id="rua" name="rua" value="<?php echo esc_attr( $rua ); ?>" required><?php echo esc_attr( $rua ); ?>
                    <input type="hidden" id="complemento" name="complemento" value="<?php echo esc_attr( $complemento ); ?>" required><?php echo esc_attr( $complemento ); ?>
                    <input type="hidden" id="cidade" name="cidade" value="<?php echo esc_attr( $cidade ); ?>" required><?php echo esc_attr( $cidade ); ?>
                    <input type="hidden" id="estado" name="estado" value="<?php echo esc_attr( $estado ); ?>" required><?php echo esc_attr( $estado ); ?>
                    <input type="hidden" id="cep" name="cep" value="<?php echo esc_attr( $cep ); ?>" required><?php echo esc_attr( $cep ); ?>
                </div>
                
                <div class="form-row">
                    <label for="data_prevista_devolucao"><?php _e( 'Data Prevista para Devolução *', 'movimento-livre' ); ?></label>
                    <input type="date" id="data_prevista_devolucao" name="data_prevista_devolucao" 
                           value="<?php echo date( 'Y-m-d', strtotime( '+30 days' ) ); ?>" required>
                </div>
                
                <div class="form-row">
                    <label for="responsavel_atendimento"><?php _e( 'Responsável pelo Atendimento *', 'movimento-livre' ); ?></label>
                    <input type="text" id="responsavel_atendimento" name="responsavel_atendimento" required>
                </div>
                
                <div class="form-row">
                    <label for="observacoes"><?php _e( 'Observações', 'movimento-livre' ); ?></label>
                    <textarea id="observacoes" name="observacoes" rows="3"></textarea>
                </div>
                
                <div class="form-section">
                    <h4><?php _e( 'Dados do Padrinho/Responsável', 'movimento-livre' ); ?></h4>
                    <p class="form-description"><?php _e( 'O Padrinho é o responsável pelo usuário da cadeira de rodas', 'movimento-livre' ); ?></p>
                    
                    <div class="form-row">
                        <label for="padrinho_nome"><?php _e( 'Nome do Padrinho *', 'movimento-livre' ); ?></label>
                        <input type="text" id="padrinho_nome" name="padrinho_nome" required>
                    </div>
                    
                    <div class="form-row">
                        <label for="padrinho_cpf"><?php _e( 'CPF do Padrinho *', 'movimento-livre' ); ?></label>
                        <input type="text" id="padrinho_cpf" name="padrinho_cpf" maxlength="14" required>
                    </div>
                    
                    <div class="form-row">
                        <label for="padrinho_endereco"><?php _e( 'Endereço do Padrinho *', 'movimento-livre' ); ?></label>
                        <input type="text" id="padrinho_endereco" name="padrinho_endereco" required>
                    </div>
                    
                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="padrinho_numero"><?php _e( 'Número *', 'movimento-livre' ); ?></label>
                            <input type="text" id="padrinho_numero" name="padrinho_numero" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="padrinho_complemento"><?php _e( 'Complemento', 'movimento-livre' ); ?></label>
                            <input type="text" id="padrinho_complemento" name="padrinho_complemento">
                        </div>
                    </div>
                    
                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="padrinho_cidade"><?php _e( 'Cidade *', 'movimento-livre' ); ?></label>
                            <input type="text" id="padrinho_cidade" name="padrinho_cidade" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="padrinho_estado"><?php _e( 'Estado *', 'movimento-livre' ); ?></label>
                            <select id="padrinho_estado" name="padrinho_estado" required>
                                <option value=""><?php _e( 'Selecione...', 'movimento-livre' ); ?></option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="padrinho_cep"><?php _e( 'CEP *', 'movimento-livre' ); ?></label>
                            <input type="text" id="padrinho_cep" name="padrinho_cep" maxlength="9" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="padrinho_telefone"><?php _e( 'Telefone/WhatsApp *', 'movimento-livre' ); ?></label>
                            <input type="text" id="padrinho_telefone" name="padrinho_telefone" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-info">
                    <h4><?php _e( 'Informações do Empréstimo:', 'movimento-livre' ); ?></h4>
                    <p><strong><?php _e( 'Pedido:', 'movimento-livre' ); ?></strong> #<?php echo $order->get_id(); ?></p>
                    <p><strong><?php _e( 'CPF:', 'movimento-livre' ); ?></strong> 
                        <?php 
                        if ( $cpf ) {
                            $cpf_validator = MOVLIV_CPF_Validator::getInstance();
                            echo $cpf_validator->format_cpf( $cpf );
                        }
                        ?>
                    </p>
                    <p><strong><?php _e( 'Cadeira(s):', 'movimento-livre' ); ?></strong></p>
                    <ul>
                        <?php foreach ( $order->get_items() as $item ) : 
                            $product = $item->get_product(); ?>
                            <li><?php echo esc_html( $product->get_name() . ' (TAG: ' . $product->get_sku() . ')' ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="form-terms">
                    <label>
                        <input type="checkbox" name="aceita_termos" required>
                        <?php _e( 'Declaro estar ciente dos termos de responsabilidade do empréstimo *', 'movimento-livre' ); ?>
                    </label>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="button-primary">
                        <?php _e( 'Enviar Formulário', 'movimento-livre' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtém formulário de devolução HTML
     */
    public function get_devolucao_form_html( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return '<p>' . __( 'Pedido não encontrado.', 'movimento-livre' ) . '</p>';
        }

        // Verifica se está no status correto
        if ( $order->get_status() !== 'processing' ) {
            return '<p>' . __( 'Este empréstimo não está no status correto para devolução.', 'movimento-livre' ) . '</p>';
        }

        // Verifica se já foi preenchido
        $pdf_path = get_post_meta( $order_id, '_form_devolucao_pdf', true );
        if ( $pdf_path && file_exists( $pdf_path ) ) {
            return '<p>' . __( 'Formulário de devolução já foi preenchido.', 'movimento-livre' ) . '</p>';
        }

        // ✅ CORREÇÃO: Usa função helper para buscar CPF correto
        $cpf = $this->get_user_cpf_from_order( $order_id );
        
        ob_start();
        ?>
        <div class="movliv-form-container">
            <h3><?php _e( 'Formulário de Devolução', 'movimento-livre' ); ?></h3>
            
            <form id="movliv-devolucao-form" class="movliv-form">
                <input type="hidden" name="action" value="movliv_submit_devolucao">
                <input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'movliv_form_nonce' ); ?>">
                
                <div class="form-row">
                    <label for="nome"><?php _e( 'Nome Completo *', 'movimento-livre' ); ?></label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-row">
                    <label for="estado_devolucao"><?php _e( 'Estado da Cadeira na Devolução', 'movimento-livre' ); ?></label>
                    <select id="estado_devolucao" name="estado_devolucao">
                        <option value="Conforme recebida"><?php _e( 'Conforme recebida', 'movimento-livre' ); ?></option>
                        <option value="Com pequenos desgastes"><?php _e( 'Com pequenos desgastes', 'movimento-livre' ); ?></option>
                        <option value="Com avarias"><?php _e( 'Com avarias', 'movimento-livre' ); ?></option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="responsavel_devolucao"><?php _e( 'Responsável pela Devolução *', 'movimento-livre' ); ?></label>
                    <input type="text" id="responsavel_devolucao" name="responsavel_devolucao" required>
                </div>
                
                <div class="form-row">
                    <label for="observacoes"><?php _e( 'Observações sobre o uso da cadeira', 'movimento-livre' ); ?></label>
                    <textarea id="observacoes" name="observacoes" rows="4"></textarea>
                </div>
                
                <div class="form-info">
                    <h4><?php _e( 'Informações do Empréstimo:', 'movimento-livre' ); ?></h4>
                    <p><strong><?php _e( 'Pedido:', 'movimento-livre' ); ?></strong> #<?php echo $order->get_id(); ?></p>
                    <p><strong><?php _e( 'Data da Devolução:', 'movimento-livre' ); ?></strong> <?php echo date( 'd/m/Y' ); ?></p>
                    <?php foreach ( $order->get_items() as $item ) : 
                        $product = $item->get_product(); ?>
                        <p><strong><?php _e( 'Cadeira devolvida:', 'movimento-livre' ); ?></strong> <?php echo esc_html( $product->get_name() . ' (TAG: ' . $product->get_sku() . ')' ); ?></p>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="button-primary">
                        <?php _e( 'Confirmar Devolução', 'movimento-livre' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtém formulário de avaliação HTML
     */
    public function get_avaliacao_form_html( $product_id ) {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return '<p>' . __( 'Cadeira não encontrada.', 'movimento-livre' ) . '</p>';
        }

        // Verifica permissões
        if ( ! current_user_can( 'movliv_submit_evaluation' ) ) {
            return '<p>' . __( 'Você não tem permissão para avaliar cadeiras.', 'movimento-livre' ) . '</p>';
        }

        $current_user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="movliv-form-container">
            <h3><?php _e( 'Formulário de Avaliação Técnica', 'movimento-livre' ); ?></h3>
            
            <form id="movliv-avaliacao-form" class="movliv-form">
                <input type="hidden" name="action" value="movliv_submit_avaliacao">
                <input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'movliv_form_nonce' ); ?>">
                
                <div class="form-info">
                    <h4><?php _e( 'Informações da Cadeira:', 'movimento-livre' ); ?></h4>
                    <p><strong><?php _e( 'TAG/SKU:', 'movimento-livre' ); ?></strong> <?php echo esc_html( $product->get_sku() ); ?></p>
                    <p><strong><?php _e( 'Modelo:', 'movimento-livre' ); ?></strong> <?php echo esc_html( $product->get_name() ); ?></p>
                    <p><strong><?php _e( 'Data da Avaliação:', 'movimento-livre' ); ?></strong> <?php echo date( 'd/m/Y H:i' ); ?></p>
                </div>
                
                <div class="form-row">
                    <label for="avaliador"><?php _e( 'Nome do Avaliador *', 'movimento-livre' ); ?></label>
                    <input type="text" id="avaliador" name="avaliador" value="<?php echo esc_attr( $current_user->display_name ); ?>" required>
                </div>
                
                <div class="form-section">
                    <h4><?php _e( 'Checklist de Avaliação:', 'movimento-livre' ); ?></h4>
                    
                    <?php 
                    $checklist_items = array(
                        'rodas' => 'Rodas e Pneus',
                        'freios' => 'Sistema de Freios',
                        'estofamento' => 'Estofamento e Assentos',
                        'estrutura' => 'Estrutura Metálica',
                        'encosto' => 'Encosto',
                        'apoios' => 'Apoios para Braços e Pés',
                        'funcionamento' => 'Funcionamento Geral'
                    );
                    
                    foreach ( $checklist_items as $key => $label ) : ?>
                        <div class="form-row checklist-item">
                            <label><?php echo esc_html( $label ); ?>:</label>
                            <select name="<?php echo esc_attr( $key ); ?>">
                                <option value="OK"><?php _e( 'OK', 'movimento-livre' ); ?></option>
                                <option value="Desgaste Leve"><?php _e( 'Desgaste Leve', 'movimento-livre' ); ?></option>
                                <option value="Precisa Manutenção"><?php _e( 'Precisa Manutenção', 'movimento-livre' ); ?></option>
                                <option value="Danificado"><?php _e( 'Danificado', 'movimento-livre' ); ?></option>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-row">
                    <label for="observacoes"><?php _e( 'Observações Técnicas', 'movimento-livre' ); ?></label>
                    <textarea id="observacoes" name="observacoes" rows="4" placeholder="<?php _e( 'Descreva detalhes sobre o estado da cadeira, reparos necessários, etc.', 'movimento-livre' ); ?>"></textarea>
                </div>
                
                <div class="form-row">
                    <label for="resultado"><?php _e( 'Resultado da Avaliação *', 'movimento-livre' ); ?></label>
                    <select id="resultado" name="resultado" required>
                        <option value=""><?php _e( 'Selecione...', 'movimento-livre' ); ?></option>
                        <option value="Aprovada"><?php _e( 'Aprovada (Pronta para empréstimo)', 'movimento-livre' ); ?></option>
                        <option value="Reprovada"><?php _e( 'Reprovada (Enviar para manutenção)', 'movimento-livre' ); ?></option>
                    </select>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="button-primary">
                        <?php _e( 'Finalizar Avaliação', 'movimento-livre' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
} 