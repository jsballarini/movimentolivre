<?php
/**
 * Gerador de PDFs - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para geração de documentos PDF
 */
class MOVLIV_PDF_Generator {

    /**
     * Instância única da classe
     * @var MOVLIV_PDF_Generator
     */
    private static $instance = null;

    /**
     * Diretório para salvamento dos PDFs
     * @var string
     */
    private $upload_dir;

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_PDF_Generator();
        }
        return self::$instance;
    }

    /**
     * Construtor privado
     */
    private function __construct() {
        $this->setup_upload_dir();
        add_action( 'init', array( $this, 'init_hooks' ) );
    }

    /**
     * Configura diretório de upload
     */
    private function setup_upload_dir() {
        $upload_dir = wp_upload_dir();
        $this->upload_dir = $upload_dir['basedir'] . '/movliv/';
        
        // Cria diretório se não existir
        if ( ! file_exists( $this->upload_dir ) ) {
            wp_mkdir_p( $this->upload_dir );
            
            // Adiciona proteção ao diretório
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents( $this->upload_dir . '.htaccess', $htaccess_content );
        }
    }

    /**
     * Inicializa hooks
     */
    public function init_hooks() {
        // Hook para geração de PDFs via ação
        add_action( 'movliv_gerar_pdf_emprestimo', array( $this, 'generate_emprestimo_pdf' ), 10, 2 );
        add_action( 'movliv_gerar_pdf_devolucao', array( $this, 'generate_devolucao_pdf' ), 10, 2 );
        add_action( 'movliv_gerar_pdf_avaliacao', array( $this, 'generate_avaliacao_pdf' ), 10, 2 );
    }

    /**
     * Gera PDF do formulário de empréstimo
     */
    public function generate_emprestimo_pdf( $order_id, $form_data ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }

        $cpf = get_post_meta( $order_id, '_cpf_solicitante', true );
        $html = $this->get_emprestimo_html( $order, $cpf, $form_data );
        
        $filename = 'emprestimo_' . $order_id . '_' . date( 'Y-m-d_H-i-s' ) . '.pdf';
        $pdf_path = $this->generate_pdf( $html, $filename );
        
        if ( $pdf_path ) {
            update_post_meta( $order_id, '_form_emprestimo_pdf', $pdf_path );
            return $pdf_path;
        }
        
        return false;
    }

    /**
     * Gera PDF do formulário de devolução
     */
    public function generate_devolucao_pdf( $order_id, $form_data ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }

        $cpf = get_post_meta( $order_id, '_cpf_solicitante', true );
        $html = $this->get_devolucao_html( $order, $cpf, $form_data );
        
        $filename = 'devolucao_' . $order_id . '_' . date( 'Y-m-d_H-i-s' ) . '.pdf';
        $pdf_path = $this->generate_pdf( $html, $filename );
        
        if ( $pdf_path ) {
            update_post_meta( $order_id, '_form_devolucao_pdf', $pdf_path );
            return $pdf_path;
        }
        
        return false;
    }

    /**
     * Gera PDF do formulário de avaliação
     */
    public function generate_avaliacao_pdf( $product_id, $form_data ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return false;
        }

        $html = $this->get_avaliacao_html( $product, $form_data );
        
        $filename = 'avaliacao_' . $product_id . '_' . date( 'Y-m-d_H-i-s' ) . '.pdf';
        $pdf_path = $this->generate_pdf( $html, $filename );
        
        if ( $pdf_path ) {
            // Adiciona ao histórico de avaliações
            $avaliacoes = get_post_meta( $product_id, '_avaliacoes_produto', true );
            if ( ! is_array( $avaliacoes ) ) {
                $avaliacoes = array();
            }
            
            $avaliacoes[] = array(
                'data' => date( 'Y-m-d H:i:s' ),
                'avaliador' => $form_data['avaliador'] ?? wp_get_current_user()->display_name,
                'resultado' => $form_data['resultado'] ?? '',
                'observacoes' => $form_data['observacoes'] ?? '',
                'pdf_path' => $pdf_path
            );
            
            update_post_meta( $product_id, '_avaliacoes_produto', $avaliacoes );
            return $pdf_path;
        }
        
        return false;
    }

    /**
     * Gera PDF a partir do HTML
     */
    private function generate_pdf( $html, $filename ) {
        try {
            // ✅ CORREÇÃO: Implementação simples e funcional
            // Se dompdf estiver disponível, usa ele
            if ( $this->check_pdf_library() ) {
                return $this->generate_pdf_with_dompdf( $html, $filename );
            }
            
            // ✅ FALLBACK: Salva como HTML com extensão PDF (temporário)
            // Permite que o sistema funcione mesmo sem biblioteca PDF
            $pdf_path = $this->upload_dir . $filename;
            
            // Converte HTML para formato mais limpo
            $clean_html = $this->clean_html_for_pdf( $html );
            
            if ( file_put_contents( $pdf_path, $clean_html ) ) {
                error_log( "MovLiv: PDF salvo como HTML em {$pdf_path}" );
                return $pdf_path;
            }
            
        } catch ( Exception $e ) {
            error_log( 'MovLiv PDF Error: ' . $e->getMessage() );
        }
        
        return false;
    }

    /**
     * ✅ NOVO: Gera PDF usando dompdf quando disponível
     */
    private function generate_pdf_with_dompdf( $html, $filename ) {
        try {
            require_once $this->get_pdf_library_path();
            
            // Usando dompdf
            $dompdf = new Dompdf\Dompdf();
            $dompdf->loadHtml( $html );
            $dompdf->setPaper( 'A4', 'portrait' );
            $dompdf->render();
            
            $output = $dompdf->output();
            $pdf_path = $this->upload_dir . $filename;
            
            if ( file_put_contents( $pdf_path, $output ) ) {
                error_log( "MovLiv: PDF real gerado em {$pdf_path}" );
                return $pdf_path;
            }
            
        } catch ( Exception $e ) {
            error_log( 'MovLiv Dompdf Error: ' . $e->getMessage() );
            // Fallback para HTML
            return $this->generate_pdf_as_html( $html, $filename );
        }
        
        return false;
    }

    /**
     * ✅ NOVO: Gera documento como HTML (fallback)
     */
    private function generate_pdf_as_html( $html, $filename ) {
        $html_filename = str_replace( '.pdf', '.html', $filename );
        $pdf_path = $this->upload_dir . $html_filename;
        
        $clean_html = $this->clean_html_for_pdf( $html );
        
        if ( file_put_contents( $pdf_path, $clean_html ) ) {
            error_log( "MovLiv: Documento salvo como HTML em {$pdf_path}" );
            return $pdf_path;
        }
        
        return false;
    }

    /**
     * ✅ NOVO: Limpa HTML para melhor exibição
     */
    private function clean_html_for_pdf( $html ) {
        // Adiciona meta para melhor exibição
        $html = str_replace( '<head>', '<head><meta name="viewport" content="width=device-width, initial-scale=1.0">', $html );
        
        // Adiciona estilo para impressão
        $print_style = '
        <style media="print">
            @media print {
                body { margin: 0; padding: 20px; }
                .footer { position: fixed; bottom: 0; }
            }
        </style>';
        
        $html = str_replace( '</head>', $print_style . '</head>', $html );
        
        return $html;
    }

    /**
     * Verifica se biblioteca PDF está disponível
     */
    private function check_pdf_library() {
        return class_exists( 'Dompdf\Dompdf' ) || file_exists( WP_PLUGIN_DIR . '/dompdf/autoload.inc.php' );
    }

    /**
     * Obtém caminho da biblioteca PDF
     */
    private function get_pdf_library_path() {
        if ( file_exists( WP_PLUGIN_DIR . '/dompdf/autoload.inc.php' ) ) {
            return WP_PLUGIN_DIR . '/dompdf/autoload.inc.php';
        }
        
        // Caminho padrão se instalado via Composer
        return ABSPATH . 'vendor/autoload.php';
    }

    /**
     * Gera HTML do formulário de empréstimo
     */
    private function get_emprestimo_html( $order, $cpf, $form_data ) {
        $cpf_validator = MOVLIV_CPF_Validator::getInstance();
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .title { font-size: 18px; font-weight: bold; color: #333; }
                .subtitle { font-size: 14px; color: #666; margin-top: 10px; }
                .content { margin: 20px 0; }
                .field { margin: 10px 0; }
                .label { font-weight: bold; display: inline-block; width: 150px; }
                .signature-area { margin-top: 40px; border-top: 1px solid #333; padding-top: 20px; }
                .footer { position: fixed; bottom: 30px; left: 0; right: 0; text-align: center; font-size: 10px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">TERMO DE EMPRÉSTIMO GRATUITO</div>
                <div class="subtitle">Instituto Bernardo Ferreira - Um Legado em Movimento</div>
                <div class="subtitle">Movimento Livre</div>
            </div>
            
            <div class="content">
                <div class="field">
                    <span class="label">Nome Completo:</span> ' . esc_html( $form_data['nome'] ?? '' ) . '
                </div>
                <div class="field">
                    <span class="label">CPF:</span> ' . esc_html( $cpf_validator->format_cpf( $cpf ) ) . '
                </div>
                <div class="field">
                    <span class="label">Telefone:</span> ' . esc_html( $form_data['telefone'] ?? '' ) . '
                </div>
                <div class="field">
                    <span class="label">Endereço:</span> ' . esc_html( $form_data['endereco'] ?? '' ) . '
                </div>
                <div class="field">
                    <span class="label">Data de Retirada:</span> ' . date( 'd/m/Y' ) . '
                </div>
                <div class="field">
                    <span class="label">Pedido Nº:</span> #' . $order->get_id() . '
                </div>';
        
        // Adiciona informações dos produtos
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( $product ) {
                $html .= '
                <div class="field">
                    <span class="label">Cadeira (TAG/SKU):</span> ' . esc_html( $product->get_sku() ) . '
                </div>
                <div class="field">
                    <span class="label">Modelo:</span> ' . esc_html( $product->get_name() ) . '
                </div>';
            }
        }
        
        $html .= '
                <div class="field">
                    <span class="label">Data Prevista Devolução:</span> ' . esc_html( $form_data['data_prevista_devolucao'] ?? '' ) . '
                </div>
                
                <div style="margin-top: 30px;">
                    <h3>TERMO DE RESPONSABILIDADE</h3>
                    <p>Declaro estar ciente de que:</p>
                    <ul>
                        <li>A cadeira de rodas é concedida em regime de empréstimo gratuito;</li>
                        <li>Comprometo-me a devolvê-la nas mesmas condições em que foi recebida;</li>
                        <li>Responsabilizo-me por eventuais danos durante o período de uso;</li>
                        <li>A devolução deve ser feita até a data prevista;</li>
                        <li>Em caso de dúvidas, entrarei em contato com o Instituto.</li>
                    </ul>
                </div>
                
                <div class="signature-area">
                    <div style="float: left; width: 45%;">
                        <div style="border-top: 1px solid #333; margin-top: 60px; text-align: center;">
                            Assinatura do Solicitante
                        </div>
                    </div>
                    <div style="float: right; width: 45%;">
                        <div style="border-top: 1px solid #333; margin-top: 60px; text-align: center;">
                            Responsável pelo Atendimento<br>
                            <small>' . esc_html( $form_data['responsavel_atendimento'] ?? '' ) . '</small>
                        </div>
                    </div>
                    <div style="clear: both;"></div>
                </div>
            </div>
            
            <div class="footer">
                Documento gerado em ' . date( 'd/m/Y H:i:s' ) . ' pelo sistema Movimento Livre
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Gera HTML do formulário de devolução
     */
    private function get_devolucao_html( $order, $cpf, $form_data ) {
        $cpf_validator = MOVLIV_CPF_Validator::getInstance();
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .title { font-size: 18px; font-weight: bold; color: #333; }
                .subtitle { font-size: 14px; color: #666; margin-top: 10px; }
                .content { margin: 20px 0; }
                .field { margin: 10px 0; }
                .label { font-weight: bold; display: inline-block; width: 150px; }
                .signature-area { margin-top: 40px; border-top: 1px solid #333; padding-top: 20px; }
                .footer { position: fixed; bottom: 30px; left: 0; right: 0; text-align: center; font-size: 10px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">COMPROVANTE DE DEVOLUÇÃO</div>
                <div class="subtitle">Instituto Bernardo Ferreira - Um Legado em Movimento</div>
                <div class="subtitle">Movimento Livre</div>
            </div>
            
            <div class="content">
                <div class="field">
                    <span class="label">Nome Completo:</span> ' . esc_html( $form_data['nome'] ?? '' ) . '
                </div>
                <div class="field">
                    <span class="label">CPF:</span> ' . esc_html( $cpf_validator->format_cpf( $cpf ) ) . '
                </div>
                <div class="field">
                    <span class="label">Data de Devolução:</span> ' . date( 'd/m/Y' ) . '
                </div>
                <div class="field">
                    <span class="label">Pedido Nº:</span> #' . $order->get_id() . '
                </div>';
        
        // Adiciona informações dos produtos
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( $product ) {
                $html .= '
                <div class="field">
                    <span class="label">Cadeira Devolvida (TAG):</span> ' . esc_html( $product->get_sku() ) . '
                </div>';
            }
        }
        
        $html .= '
                <div class="field">
                    <span class="label">Estado da Devolução:</span> ' . esc_html( $form_data['estado_devolucao'] ?? 'Conforme recebida' ) . '
                </div>
                
                <div style="margin-top: 20px;">
                    <span class="label">Observações:</span><br>
                    ' . esc_html( $form_data['observacoes'] ?? 'Nenhuma observação.' ) . '
                </div>
                
                <div style="margin-top: 30px;">
                    <p><strong>Declaração:</strong> Declaro que a cadeira de rodas foi devolvida nas condições descritas acima, cumprindo com os termos do empréstimo gratuito.</p>
                </div>
                
                <div class="signature-area">
                    <div style="text-align: center;">
                        <div style="border-top: 1px solid #333; margin-top: 60px; width: 300px; margin: 60px auto 0;">
                            Assinatura do Responsável pela Entrega<br>
                            <small>' . esc_html( $form_data['responsavel_devolucao'] ?? '' ) . '</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                Documento gerado em ' . date( 'd/m/Y H:i:s' ) . ' pelo sistema Movimento Livre
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Gera HTML do formulário de avaliação
     */
    private function get_avaliacao_html( $product, $form_data ) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .title { font-size: 18px; font-weight: bold; color: #333; }
                .subtitle { font-size: 14px; color: #666; margin-top: 10px; }
                .content { margin: 20px 0; }
                .field { margin: 10px 0; }
                .label { font-weight: bold; display: inline-block; width: 150px; }
                .checklist { margin: 20px 0; }
                .checklist-item { margin: 5px 0; }
                .signature-area { margin-top: 40px; border-top: 1px solid #333; padding-top: 20px; }
                .footer { position: fixed; bottom: 30px; left: 0; right: 0; text-align: center; font-size: 10px; color: #666; }
                .resultado { padding: 10px; margin: 20px 0; border: 2px solid; text-align: center; font-weight: bold; }
                .aprovada { background: #d4edda; border-color: #28a745; color: #155724; }
                .reprovada { background: #f8d7da; border-color: #dc3545; color: #721c24; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">FICHA TÉCNICA DE AVALIAÇÃO DA CADEIRA</div>
                <div class="subtitle">Instituto Bernardo Ferreira - Um Legado em Movimento</div>
                <div class="subtitle">Movimento Livre</div>
            </div>
            
            <div class="content">
                <div class="field">
                    <span class="label">TAG/SKU da Cadeira:</span> ' . esc_html( $product->get_sku() ) . '
                </div>
                <div class="field">
                    <span class="label">Modelo:</span> ' . esc_html( $product->get_name() ) . '
                </div>
                <div class="field">
                    <span class="label">Data da Avaliação:</span> ' . date( 'd/m/Y H:i' ) . '
                </div>
                <div class="field">
                    <span class="label">Avaliador:</span> ' . esc_html( $form_data['avaliador'] ?? '' ) . '
                </div>
                
                <div class="checklist">
                    <h3>CHECKLIST DE AVALIAÇÃO</h3>';
        
        $checklist_items = array(
            'rodas' => 'Rodas e Pneus',
            'freios' => 'Sistema de Freios',
            'estofamento' => 'Estofamento e Assentos',
            'estrutura' => 'Estrutura Metálica',
            'encosto' => 'Encosto',
            'apoios' => 'Apoios para Braços e Pés',
            'funcionamento' => 'Funcionamento Geral'
        );
        
        foreach ( $checklist_items as $key => $label ) {
            $status = $form_data[ $key ] ?? 'OK';
            $html .= '
                    <div class="checklist-item">
                        <strong>' . $label . ':</strong> ' . esc_html( $status ) . '
                    </div>';
        }
        
        $resultado = $form_data['resultado'] ?? '';
        $resultado_class = $resultado === 'Aprovada' ? 'aprovada' : 'reprovada';
        
        $html .= '
                </div>
                
                <div class="field">
                    <span class="label">Observações Técnicas:</span><br>
                    ' . nl2br( esc_html( $form_data['observacoes'] ?? '' ) ) . '
                </div>
                
                <div class="resultado ' . $resultado_class . '">
                    RESULTADO DA AVALIAÇÃO: ' . esc_html( $resultado ) . '
                </div>';
        
        if ( $resultado === 'Reprovada' ) {
            $html .= '
                <div style="margin-top: 20px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7;">
                    <strong>Ação Necessária:</strong> Cadeira encaminhada para manutenção. Nova avaliação será realizada após o conserto.
                </div>';
        }
        
        $html .= '
                <div class="signature-area">
                    <div style="text-align: center;">
                        <div style="border-top: 1px solid #333; margin-top: 60px; width: 300px; margin: 60px auto 0;">
                            Assinatura do Avaliador Técnico<br>
                            <small>' . esc_html( $form_data['avaliador'] ?? '' ) . '</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                Documento gerado em ' . date( 'd/m/Y H:i:s' ) . ' pelo sistema Movimento Livre
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Obtém URL para download do PDF
     */
    public function get_pdf_download_url( $pdf_path ) {
        if ( ! file_exists( $pdf_path ) ) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $pdf_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $pdf_path );
        
        return $pdf_url;
    }

    /**
     * Remove PDF antigo
     */
    public function delete_pdf( $pdf_path ) {
        if ( file_exists( $pdf_path ) ) {
            return unlink( $pdf_path );
        }
        
        return false;
    }
} 