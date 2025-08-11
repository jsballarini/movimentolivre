<?php
/**
 * Sistema de Notificações - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para gerenciar notificações por email
 */
class MOVLIV_Notifications {

    /**
     * Instância única da classe
     * @var MOVLIV_Notifications
     */
    private static $instance = null;

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_Notifications();
        }
        return self::$instance;
    }

    /**
     * Construtor privado
     */
    private function __construct() {
        // Hooks para notificações automáticas
        add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_notifications' ), 10, 4 );
        add_action( 'movliv_produto_precisa_avaliacao', array( $this, 'notify_avaliacao_pendente' ), 10, 2 );
        add_action( 'movliv_emprestimo_vencendo', array( $this, 'notify_emprestimo_vencendo' ), 10, 2 );
        
        // Personaliza emails do WooCommerce
        add_filter( 'woocommerce_email_subject_new_order', array( $this, 'custom_email_subject' ), 10, 2 );
        add_filter( 'woocommerce_email_subject_customer_processing_order', array( $this, 'custom_email_subject' ), 10, 2 );
        
        // ✅ NOVO: Desabilita emails nativos do WooCommerce para evitar duplicação
        add_filter( 'woocommerce_email_enabled_new_order', array( $this, 'disable_woocommerce_emails' ), 10, 2 );
        add_filter( 'woocommerce_email_enabled_customer_processing_order', array( $this, 'disable_woocommerce_emails' ), 10, 2 );
        add_filter( 'woocommerce_email_enabled_customer_completed_order', array( $this, 'disable_woocommerce_emails' ), 10, 2 );
        
        // Agenda verificações periódicas
        add_action( 'movliv_check_emprestimos_vencendo', array( $this, 'check_emprestimos_vencendo' ) );
        if ( ! wp_next_scheduled( 'movliv_check_emprestimos_vencendo' ) ) {
            wp_schedule_event( time(), 'daily', 'movliv_check_emprestimos_vencendo' );
        }
    }

    /**
     * Manipula notificações baseadas em mudanças de status de pedidos
     */
    public function handle_order_status_notifications( $order_id, $old_status, $new_status, $order ) {
        // ✅ NOVO: Controle de duplicação - verifica se já foi processado
        $formulario_processado = get_post_meta( $order_id, '_movliv_formulario_processado', true );
        $formulario_devolucao_processado = get_post_meta( $order_id, '_movliv_formulario_devolucao_processado', true );
        
        switch ( $new_status ) {
            case 'on-hold': // Aguardando
                // Só envia se não foi processado manualmente
                if ( ! $formulario_processado ) {
                    $this->send_solicitacao_recebida( $order );
                    $this->notify_admin_nova_solicitacao( $order );
                }
                break;

            case 'processing': // Emprestado
                // Só envia se não foi processado manualmente
                if ( ! $formulario_processado ) {
                    $this->send_emprestimo_confirmado( $order );
                }
                break;

            case 'completed': // Devolvido
                // Só envia se não foi processado manualmente
                if ( ! $formulario_devolucao_processado ) {
                    $this->send_devolucao_confirmada( $order );
                    $this->notify_avaliadores_produto_devolvido( $order );
                }
                break;
        }
    }

    /**
     * Envia email de confirmação de solicitação recebida
     */
    public function send_solicitacao_recebida( $order ) {
        $to = $order->get_billing_email();
        $subject = sprintf( 
            __( '[%s] Solicitação de Empréstimo Recebida - Pedido #%s', 'movimento-livre' ),
            get_bloginfo( 'name' ),
            $order->get_order_number()
        );
        
        $message = $this->get_email_template( 'solicitacao_recebida', array(
            'order' => $order,
            'customer_name' => $order->get_billing_first_name(),
            'order_number' => $order->get_order_number(),
            'items' => $order->get_items()
        ) );
        
        $this->send_email( $to, $subject, $message );
        
        error_log( "MovLiv: Email de solicitação enviado para {$to}" );
    }

    /**
     * Envia email de confirmação de empréstimo
     */
    public function send_emprestimo_confirmado( $order ) {
        $to = $order->get_billing_email();
        $subject = sprintf( 
            __( '[%s] Empréstimo Confirmado - Pedido #%s', 'movimento-livre' ),
            get_bloginfo( 'name' ),
            $order->get_order_number()
        );

        $message = $this->get_email_template( 'emprestimo_confirmado', array(
            'order' => $order,
            'customer_name' => $order->get_billing_first_name(),
            'order_number' => $order->get_order_number(),
            'items' => $order->get_items(),
            'data_emprestimo' => $order->get_date_modified()->format( 'd/m/Y' )
        ) );

        // Anexa o PDF do formulário de empréstimo, se existir
        $pdf_path = get_post_meta( $order->get_id(), '_form_emprestimo_pdf', true );
        if ( empty( $pdf_path ) ) {
            $pdf_path = get_post_meta( $order->get_id(), '_formulario_emprestimo_pdf', true );
        }
        $attachments = array();
        if ( $pdf_path && file_exists( $pdf_path ) ) {
            $attachments[] = $pdf_path;
        }

        // Envia para o cliente
        $this->send_email( $to, $subject, $message, array(), $attachments );

        // Envia cópia para o admin configurado
        $config = get_option( 'movliv_config', array() );
        $admin_email = $config['email_notificacoes'] ?? get_option( 'admin_email' );
        if ( $admin_email ) {
            $admin_subject = sprintf( 
                __( '[%s] (Cópia) Empréstimo Confirmado - Pedido #%s', 'movimento-livre' ),
                get_bloginfo( 'name' ),
                $order->get_order_number()
            );
            $this->send_email( $admin_email, $admin_subject, $message, array(), $attachments );
        }

        error_log( "MovLiv: Email de empréstimo enviado para {$to}" );
    }

    /**
     * Envia email de confirmação de devolução
     */
    public function send_devolucao_confirmada( $order ) {
        $to = $order->get_billing_email();
        $subject = sprintf( 
            __( '[%s] Devolução Confirmada - Pedido #%s', 'movimento-livre' ),
            get_bloginfo( 'name' ),
            $order->get_order_number()
        );
        
        $message = $this->get_email_template( 'devolucao_confirmada', array(
            'order' => $order,
            'customer_name' => $order->get_billing_first_name(),
            'order_number' => $order->get_order_number(),
            'items' => $order->get_items(),
            'data_devolucao' => current_time( 'd/m/Y' )
        ) );
        
        $this->send_email( $to, $subject, $message );
        
        error_log( "MovLiv: Email de devolução enviado para {$to}" );
    }

    /**
     * Notifica administradores sobre nova solicitação
     */
    public function notify_admin_nova_solicitacao( $order ) {
        $config = get_option( 'movliv_config', array() );
        $admin_email = $config['email_notificacoes'] ?? get_option( 'admin_email' );
        
        $subject = sprintf( 
            __( '[%s] Nova Solicitação de Empréstimo - #%s', 'movimento-livre' ),
            get_bloginfo( 'name' ),
            $order->get_order_number()
        );
        
        $message = $this->get_email_template( 'admin_nova_solicitacao', array(
            'order' => $order,
            'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'customer_email' => $order->get_billing_email(),
            'customer_phone' => $order->get_billing_phone(),
            'cpf' => get_post_meta( $order->get_id(), '_billing_cpf', true ),
            'order_number' => $order->get_order_number(),
            'items' => $order->get_items(),
            'admin_url' => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' )
        ) );
        
        $this->send_email( $admin_email, $subject, $message );
        
        error_log( "MovLiv: Notificação admin enviada para {$admin_email}" );
    }

    /**
     * Notifica avaliadores sobre produto devolvido
     */
    public function notify_avaliadores_produto_devolvido( $order ) {
        // Busca tanto colaboradores quanto avaliadores (ambos podem fazer avaliações)
        $avaliadores = get_users( array( 
            'role__in' => array( 'movliv_colaborador', 'movliv_avaliador' )
        ) );
        
        if ( empty( $avaliadores ) ) {
            // Se não há colaboradores ou avaliadores, notifica admins
            $config = get_option( 'movliv_config', array() );
            $admin_email = $config['email_notificacoes'] ?? get_option( 'admin_email' );
            $avaliadores = array( (object) array( 'user_email' => $admin_email ) );
        }
        
        foreach ( $avaliadores as $avaliador ) {
            $subject = sprintf( 
                __( '[%s] Produto Precisa de Avaliação - Pedido #%s', 'movimento-livre' ),
                get_bloginfo( 'name' ),
                $order->get_order_number()
            );
            
            $message = $this->get_email_template( 'avaliacao_pendente', array(
                'order' => $order,
                'order_number' => $order->get_order_number(),
                'items' => $order->get_items(),
                'data_devolucao' => current_time( 'd/m/Y' ),
                'avaliacoes_url' => admin_url( 'admin.php?page=movimento-livre-avaliacoes' )
            ) );
            
            $this->send_email( $avaliador->user_email, $subject, $message );
        }
        
        error_log( "MovLiv: Notificação de avaliação enviada para " . count( $avaliadores ) . " colaboradores/avaliadores" );
    }

    /**
     * Notifica sobre empréstimo próximo do vencimento
     */
    public function notify_emprestimo_vencendo( $order_id, $dias_restantes ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }
        
        $to = $order->get_billing_email();
        $subject = sprintf( 
            __( '[%s] Lembrete: Empréstimo vence em %d dias - #%s', 'movimento-livre' ),
            get_bloginfo( 'name' ),
            $dias_restantes,
            $order->get_order_number()
        );
        
        $message = $this->get_email_template( 'emprestimo_vencendo', array(
            'order' => $order,
            'customer_name' => $order->get_billing_first_name(),
            'order_number' => $order->get_order_number(),
            'items' => $order->get_items(),
            'dias_restantes' => $dias_restantes,
            'data_emprestimo' => $order->get_date_modified()->format( 'd/m/Y' )
        ) );
        
        $this->send_email( $to, $subject, $message );
        
        error_log( "MovLiv: Notificação de vencimento enviada para {$to}" );
    }

    /**
     * Verifica empréstimos que estão vencendo
     */
    public function check_emprestimos_vencendo() {
        $orders = wc_get_orders( array(
            // Usa status nativo do WooCommerce para empréstimos ativos
            'status' => 'processing',
            'limit' => -1
        ) );
        
        $config = get_option( 'movliv_config', array() );
        $dias_aviso = $config['dias_aviso_vencimento'] ?? 7;
        
        foreach ( $orders as $order ) {
            $data_emprestimo = $order->get_date_modified() ?: $order->get_date_created();
            $dias_emprestimo = $data_emprestimo->diff( new DateTime() )->days;
            
            // Se empréstimo tem mais de 23 dias (avisa 7 dias antes dos 30)
            if ( $dias_emprestimo >= ( 30 - $dias_aviso ) && $dias_emprestimo < 30 ) {
                $dias_restantes = 30 - $dias_emprestimo;
                
                // Verifica se já foi enviado aviso para este pedido
                $aviso_enviado = get_post_meta( $order->get_id(), '_movliv_aviso_vencimento_enviado', true );
                
                if ( ! $aviso_enviado ) {
                    do_action( 'movliv_emprestimo_vencendo', $order->get_id(), $dias_restantes );
                    update_post_meta( $order->get_id(), '_movliv_aviso_vencimento_enviado', current_time( 'Y-m-d' ) );
                }
            }
        }
    }

    /**
     * ✅ NOVO: Desabilita emails nativos do WooCommerce para evitar duplicação
     */
    public function disable_woocommerce_emails( $enabled, $order ) {
        // Desabilita emails nativos para pedidos do Movimento Livre
        // As notificações personalizadas já cobrem esses casos
        return false;
    }

    /**
     * Customiza assuntos dos emails do WooCommerce
     */
    public function custom_email_subject( $subject, $order ) {
        if ( ! $order ) {
            return $subject;
        }
        
        // Personaliza baseado no status nativo do WooCommerce
        switch ( $order->get_status() ) {
            case 'on-hold':
                return sprintf( 
                    __( '[%s] Solicitação de Empréstimo Recebida - #%s', 'movimento-livre' ),
                    get_bloginfo( 'name' ),
                    $order->get_order_number()
                );
                
            case 'processing':
                return sprintf( 
                    __( '[%s] Empréstimo Ativo - #%s', 'movimento-livre' ),
                    get_bloginfo( 'name' ),
                    $order->get_order_number()
                );

            case 'completed':
                return sprintf( 
                    __( '[%s] Devolução Confirmada - #%s', 'movimento-livre' ),
                    get_bloginfo( 'name' ),
                    $order->get_order_number()
                );
        }
        
        return $subject;
    }

    /**
     * Obtém template de email
     */
    private function get_email_template( $template_name, $vars = array() ) {
        extract( $vars );
        
        $template_path = MOVLIV_PLUGIN_PATH . "templates/emails/{$template_name}.php";
        
        if ( file_exists( $template_path ) ) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        // Template padrão
        return $this->get_default_template( $template_name, $vars );
    }

    /**
     * Gera template padrão quando arquivo não existe
     */
    private function get_default_template( $template_name, $vars ) {
        extract( $vars );
        
        $site_name = get_bloginfo( 'name' );
        $header = "<h2>{$site_name} - Movimento Livre</h2>";
        $footer = "<p><small>Este é um email automático do sistema Movimento Livre.</small></p>";
        
        switch ( $template_name ) {
            case 'solicitacao_recebida':
                return $header . 
                       "<p>Olá {$customer_name},</p>" .
                       "<p>Recebemos sua solicitação de empréstimo #{$order_number}. Nossa equipe irá analisar e entrar em contato em breve.</p>" .
                       "<p><strong>Itens solicitados:</strong></p>" .
                       $this->format_order_items( $items ) .
                       $footer;
                       
            case 'emprestimo_confirmado':
                return $header . 
                       "<p>Olá {$customer_name},</p>" .
                       "<p>Seu empréstimo #{$order_number} foi confirmado em {$data_emprestimo}!</p>" .
                       "<p><strong>Itens emprestados:</strong></p>" .
                       $this->format_order_items( $items ) .
                       "<p>Lembre-se de devolver o equipamento em até 30 dias.</p>" .
                       $footer;
                       
            case 'devolucao_confirmada':
                return $header . 
                       "<p>Olá {$customer_name},</p>" .
                       "<p>A devolução do seu empréstimo #{$order_number} foi confirmada em {$data_devolucao}.</p>" .
                       "<p>Obrigado por utilizar nossos serviços!</p>" .
                       $footer;
                       
            case 'admin_nova_solicitacao':
                return $header . 
                       "<p>Nova solicitação de empréstimo recebida:</p>" .
                       "<p><strong>Cliente:</strong> {$customer_name}<br>" .
                       "<strong>Email:</strong> {$customer_email}<br>" .
                       "<strong>CPF:</strong> {$cpf}<br>" .
                       "<strong>Pedido:</strong> #{$order_number}</p>" .
                       "<p><strong>Itens solicitados:</strong></p>" .
                       $this->format_order_items( $items ) .
                       "<p><a href='{$admin_url}'>Ver pedido no admin</a></p>" .
                       $footer;
                       
            case 'avaliacao_pendente':
                return $header . 
                       "<p>Produto devolvido precisa de avaliação:</p>" .
                       "<p><strong>Pedido:</strong> #{$order_number}<br>" .
                       "<strong>Data Devolução:</strong> {$data_devolucao}</p>" .
                       "<p><strong>Itens para avaliar:</strong></p>" .
                       $this->format_order_items( $items ) .
                       "<p><a href='{$avaliacoes_url}'>Ir para avaliações</a></p>" .
                       $footer;
                       
            case 'emprestimo_vencendo':
                return $header . 
                       "<p>Olá {$customer_name},</p>" .
                       "<p>Seu empréstimo #{$order_number} vence em {$dias_restantes} dias.</p>" .
                       "<p><strong>Data do empréstimo:</strong> {$data_emprestimo}</p>" .
                       "<p><strong>Itens emprestados:</strong></p>" .
                       $this->format_order_items( $items ) .
                       "<p>Por favor, organize a devolução o quanto antes.</p>" .
                       $footer;
        }
        
        return $header . "<p>Notificação do sistema Movimento Livre.</p>" . $footer;
    }

    /**
     * Formata itens do pedido para email
     */
    private function format_order_items( $items ) {
        $html = '<ul>';
        foreach ( $items as $item ) {
            $html .= '<li>' . esc_html( $item->get_name() ) . ' (Qtd: ' . $item->get_quantity() . ')</li>';
        }
        $html .= '</ul>';
        
        return $html;
    }

    /**
     * Envia email com configurações apropriadas
     */
    private function send_email( $to, $subject, $message, $headers = array(), $attachments = array() ) {
        // Log para debug
        error_log( "MovLiv: Iniciando send_email para {$to}" );
        error_log( "MovLiv: Assunto: {$subject}" );
        error_log( "MovLiv: Tamanho da mensagem: " . strlen( $message ) );
        
        try {
            $default_headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>'
            );
            
            $headers = array_merge( $default_headers, $headers );
            
            error_log( "MovLiv: Headers finais: " . print_r( $headers, true ) );
            
            $result = wp_mail( $to, $subject, $message, $headers, $attachments );
            
            error_log( "MovLiv: Resultado do wp_mail: " . ( $result ? 'true' : 'false' ) );
            
            return $result;
            
        } catch ( Exception $e ) {
            error_log( "MovLiv: Exceção em send_email: " . $e->getMessage() );
            return false;
        } catch ( Error $e ) {
            error_log( "MovLiv: Erro fatal em send_email: " . $e->getMessage() );
            return false;
        }
    }

    /**
     * Envia notificação de teste (para debug)
     */
    public function send_test_notification( $email, $type = 'test' ) {
        // Log para debug
        error_log( "MovLiv: Iniciando send_test_notification para {$email}" );
        
        try {
            $subject = sprintf( 
                '[%s] Teste de Notificação',
                get_bloginfo( 'name' )
            );
            
            error_log( "MovLiv: Assunto criado: {$subject}" );
            
            $message = "<h2>Teste de Notificação</h2>";
            $message .= "<p>Este é um email de teste do sistema Movimento Livre.</p>";
            $message .= "<p>Data/Hora: " . current_time( 'd/m/Y H:i:s' ) . "</p>";
            
            error_log( "MovLiv: Mensagem criada, tamanho: " . strlen( $message ) );
            
            $result = $this->send_email( $email, $subject, $message );
            
            error_log( "MovLiv: Resultado do send_email: " . ( $result ? 'true' : 'false' ) );
            
            return $result;
            
        } catch ( Exception $e ) {
            error_log( "MovLiv: Exceção em send_test_notification: " . $e->getMessage() );
            return false;
        } catch ( Error $e ) {
            error_log( "MovLiv: Erro fatal em send_test_notification: " . $e->getMessage() );
            return false;
        }
    }
} 