<?php
/**
 * Sistema de Relatórios - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para gerenciar relatórios e estatísticas
 */
class MOVLIV_Reports {

    /**
     * Instância única da classe
     * @var MOVLIV_Reports
     */
    private static $instance = null;

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_Reports();
        }
        return self::$instance;
    }

    /**
     * Construtor privado
     */
    private function __construct() {
        add_action( 'wp_ajax_movliv_export_report', array( $this, 'ajax_export_report' ) );
        add_action( 'wp_ajax_movliv_generate_chart_data', array( $this, 'ajax_generate_chart_data' ) );
        
        // ✅ NOVO: Actions AJAX para filtros
        add_action( 'wp_ajax_movliv_filter_emprestimos', array( $this, 'ajax_filter_emprestimos' ) );
        add_action( 'wp_ajax_movliv_load_emprestimos_table', array( $this, 'ajax_load_emprestimos_table' ) );
        
        // Adiciona página de relatórios ao menu admin
        add_action( 'admin_menu', array( $this, 'add_reports_submenu' ), 99 );
        
        // ✅ NOVO: Enqueue scripts para relatórios
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_reports_scripts' ) );
    }

    /**
     * ✅ NOVO: Enqueue scripts para relatórios
     */
    public function enqueue_reports_scripts( $hook ) {
        if ( strpos( $hook, 'movimento-livre-relatorios' ) === false ) {
            return;
        }
        
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'wp-util' );
        
        // ✅ CORREÇÃO: Enqueue CSS corretamente
        wp_enqueue_style( 'admin-bar' ); // Enqueue um CSS existente como base
        wp_add_inline_style( 'admin-bar', '
            .movliv-reports-nav .nav-tab-wrapper {
                border-bottom: 1px solid #ccd0d4;
                margin-bottom: 20px;
            }
            
            .movliv-filter-form {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                align-items: center;
            }
            
            .movliv-filter-form label {
                font-weight: 600;
                margin-right: 5px;
            }
            
            .movliv-filter-form input[type="date"],
            .movliv-filter-form input[type="text"],
            .movliv-filter-form select {
                padding: 6px 10px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                font-size: 14px;
            }
            
            .movliv-filter-form .button {
                height: 36px;
                padding: 0 15px;
                margin-left: 10px;
            }
            
            .emprestimos-table {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                overflow: hidden;
            }
            
            .emprestimos-table .wp-list-table {
                border: none;
                margin: 0;
            }
            
            .emprestimos-table .wp-list-table th,
            .emprestimos-table .wp-list-table td {
                border-bottom: 1px solid #c3c4c7;
                padding: 12px;
            }
            
            .emprestimos-table .wp-list-table th {
                background: #f6f7f7;
                font-weight: 600;
            }
            
            .status-on-hold {
                color: #f59e0b;
                font-weight: 600;
            }
            
            .status-processing {
                color: #3b82f6;
                font-weight: 600;
            }
            
            .status-completed {
                color: #10b981;
                font-weight: 600;
            }
            
            .status-cancelled {
                color: #ef4444;
                font-weight: 600;
            }
            
            .loading {
                text-align: center;
                padding: 40px;
                color: #666;
                font-style: italic;
            }
            
            .error {
                background: #fef2f2;
                border: 1px solid #fecaca;
                color: #dc2626;
                padding: 15px;
                border-radius: 4px;
                text-align: center;
            }
            
            .movliv-dashboard-stats .movliv-stat-boxes {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .movliv-dashboard-stats .movliv-stat-box {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
            }
            
            .movliv-dashboard-stats .movliv-stat-box h3 {
                margin: 0 0 10px 0;
                color: #1d2327;
                font-size: 14px;
                font-weight: 600;
            }
            
            .movliv-dashboard-stats .stat-number {
                display: block;
                font-size: 32px;
                font-weight: 700;
                color: #2271b1;
                margin-bottom: 5px;
            }
            
            .movliv-dashboard-stats .stat-change {
                color: #666;
                font-size: 12px;
            }
            
            .tablenav.bottom {
                padding: 15px;
                background: #f6f7f7;
                border-top: 1px solid #c3c4c7;
            }
            
            .displaying-num {
                color: #646970;
                font-size: 14px;
            }
            
            @media (max-width: 768px) {
                .movliv-filter-form {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .movliv-filter-form input,
                .movliv-filter-form select {
                    width: 100%;
                }
                
                .movliv-dashboard-stats .movliv-stat-boxes {
                    grid-template-columns: 1fr;
                }
            }
        ' );
        
        // Script customizado para filtros AJAX
        wp_add_inline_script( 'jquery', '
            jQuery(document).ready(function($) {
                // Filtro AJAX para empréstimos
                $("#movliv-filter-emprestimos").on("submit", function(e) {
                    e.preventDefault();
                    
                    var formData = $(this).serialize();
                    $("#emprestimos-table-container").html("<div class=\"loading\">🔄 Carregando empréstimos...</div>");
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: formData + "&action=movliv_filter_emprestimos&nonce=" + movliv_admin.nonce,
                        success: function(response) {
                            if(response.success) {
                                $("#emprestimos-table-container").html(response.data.html);
                            } else {
                                $("#emprestimos-table-container").html("<div class=\"error\">❌ Erro ao carregar dados: " + (response.data || "Erro desconhecido") + "</div>");
                            }
                        },
                        error: function(xhr, status, error) {
                            $("#emprestimos-table-container").html("<div class=\"error\">❌ Erro na requisição: " + error + "</div>");
                        }
                    });
                });
                
                // Carrega tabela inicialmente
                $("#movliv-filter-emprestimos").trigger("submit");
            });
        ' );
        
        // Localize script para nonce
        wp_localize_script( 'jquery', 'movliv_admin', array(
            'nonce' => wp_create_nonce( 'movliv_admin_nonce' )
        ) );
    }

    /**
     * Adiciona submenu de relatórios
     */
    public function add_reports_submenu() {
        // Remove submenu que pode ter sido adicionado pela admin interface
        remove_submenu_page( 'movimento-livre', 'movimento-livre-relatorios' );
        
        add_submenu_page(
            'movimento-livre',
            __( 'Relatórios Detalhados', 'movimento-livre' ),
            __( 'Relatórios', 'movimento-livre' ),
            'manage_woocommerce',
            'movimento-livre-relatorios',
            array( $this, 'render_reports_page' )
        );
    }

    /**
     * Renderiza página de relatórios
     */
    public function render_reports_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Relatórios - Movimento Livre', 'movimento-livre' ); ?></h1>
            
            <div class="movliv-reports-nav">
                <nav class="nav-tab-wrapper">
                    <a href="#dashboard" class="nav-tab nav-tab-active"><?php _e( 'Dashboard', 'movimento-livre' ); ?></a>
                    <a href="#emprestimos" class="nav-tab"><?php _e( 'Empréstimos', 'movimento-livre' ); ?></a>
                    <a href="#cadeiras" class="nav-tab"><?php _e( 'Cadeiras', 'movimento-livre' ); ?></a>
                    <a href="#usuarios" class="nav-tab"><?php _e( 'Usuários', 'movimento-livre' ); ?></a>
                    <a href="#performance" class="nav-tab"><?php _e( 'Performance', 'movimento-livre' ); ?></a>
                </nav>
            </div>

            <div id="dashboard" class="movliv-report-section">
                <?php $this->render_dashboard_report(); ?>
            </div>

            <div id="emprestimos" class="movliv-report-section" style="display:none;">
                <?php $this->render_emprestimos_report(); ?>
            </div>

            <div id="cadeiras" class="movliv-report-section" style="display:none;">
                <?php $this->render_cadeiras_report(); ?>
            </div>

            <div id="usuarios" class="movliv-report-section" style="display:none;">
                <?php $this->render_usuarios_report(); ?>
            </div>

            <div id="performance" class="movliv-report-section" style="display:none;">
                <?php $this->render_performance_report(); ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                
                // Remove active class e esconde seções
                $('.nav-tab').removeClass('nav-tab-active');
                $('.movliv-report-section').hide();
                
                // Ativa tab clicada e mostra seção
                $(this).addClass('nav-tab-active');
                var target = $(this).attr('href');
                $(target).show();
            });
        });
        </script>
        <?php
    }

    /**
     * Renderiza relatório dashboard
     */
    private function render_dashboard_report() {
        $stats = $this->get_general_stats();
        ?>
        <div class="movliv-dashboard-stats">
            <div class="movliv-stat-boxes">
                <div class="movliv-stat-box">
                    <h3><?php _e( 'Total de Empréstimos', 'movimento-livre' ); ?></h3>
                    <span class="stat-number"><?php echo $stats['total_emprestimos']; ?></span>
                    <span class="stat-change">+<?php echo $stats['emprestimos_mes']; ?> este mês</span>
                </div>
                
                <div class="movliv-stat-box">
                    <h3><?php _e( 'Cadeiras Cadastradas', 'movimento-livre' ); ?></h3>
                    <span class="stat-number"><?php echo $stats['total_cadeiras']; ?></span>
                    <span class="stat-change"><?php echo $stats['cadeiras_ativas']; ?> ativas</span>
                </div>
                
                <div class="movliv-stat-box">
                    <h3><?php _e( 'Usuários Atendidos', 'movimento-livre' ); ?></h3>
                    <span class="stat-number"><?php echo $stats['usuarios_unicos']; ?></span>
                    <span class="stat-change"><?php echo $stats['novos_usuarios_mes']; ?> novos este mês</span>
                </div>
                
                <div class="movliv-stat-box">
                    <h3><?php _e( 'Taxa de Devolução', 'movimento-livre' ); ?></h3>
                    <span class="stat-number"><?php echo $stats['taxa_devolucao']; ?>%</span>
                    <span class="stat-change">Últimos 30 dias</span>
                </div>
            </div>

            <div class="movliv-charts-container">
                <div class="chart-box">
                    <h3><?php _e( 'Empréstimos por Mês', 'movimento-livre' ); ?></h3>
                    <canvas id="emprestimos-mensal-chart"></canvas>
                </div>
                
                <div class="chart-box">
                    <h3><?php _e( 'Status das Cadeiras', 'movimento-livre' ); ?></h3>
                    <canvas id="status-cadeiras-chart"></canvas>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza relatório de empréstimos
     */
    private function render_emprestimos_report() {
        ?>
        <div class="movliv-emprestimos-report">
            <div class="report-filters">
                <form method="post" class="movliv-filter-form" id="movliv-filter-emprestimos">
                    <input type="hidden" name="page" value="movimento-livre-relatorios">
                    
                    <label><?php _e( 'Período:', 'movimento-livre' ); ?></label>
                    <input type="date" name="data_inicio" value="<?php echo $_GET['data_inicio'] ?? date( 'Y-m-01' ); ?>">
                    <input type="date" name="data_fim" value="<?php echo $_GET['data_fim'] ?? date( 'Y-m-d' ); ?>">
                    
                    <label><?php _e( 'Status:', 'movimento-livre' ); ?></label>
                    <select name="status">
                        <option value=""><?php _e( 'Todos', 'movimento-livre' ); ?></option>
                        <option value="on-hold" <?php selected( $_GET['status'] ?? '', 'on-hold' ); ?>><?php _e( 'Aguardando', 'movimento-livre' ); ?></option>
                        <option value="processing" <?php selected( $_GET['status'] ?? '', 'processing' ); ?>><?php _e( 'Emprestado', 'movimento-livre' ); ?></option>
                        <option value="completed" <?php selected( $_GET['status'] ?? '', 'completed' ); ?>><?php _e( 'Devolvido', 'movimento-livre' ); ?></option>
                        <option value="cancelled" <?php selected( $_GET['status'] ?? '', 'cancelled' ); ?>><?php _e( 'Cancelado', 'movimento-livre' ); ?></option>
                    </select>
                    
                    <label><?php _e( 'CPF:', 'movimento-livre' ); ?></label>
                    <input type="text" name="cpf" placeholder="Buscar por CPF" value="<?php echo $_GET['cpf'] ?? ''; ?>">
                    
                    <button type="submit" class="button button-primary"><?php _e( 'Filtrar', 'movimento-livre' ); ?></button>
                    <button type="button" class="button" onclick="exportarEmprestimos()"><?php _e( 'Exportar CSV', 'movimento-livre' ); ?></button>
                </form>
            </div>

            <div class="emprestimos-table" id="emprestimos-table-container">
                <div class="loading">Carregando empréstimos...</div>
            </div>
        </div>
        
        <script>
        function exportarEmprestimos() {
            var form = document.getElementById('movliv-filter-emprestimos');
            var formData = new FormData(form);
            formData.append('action', 'movliv_export_report');
            formData.append('type', 'emprestimos');
            formData.append('nonce', movliv_admin.nonce);
            
            // Cria um form temporário para download
            var downloadForm = document.createElement('form');
            downloadForm.method = 'POST';
            downloadForm.action = ajaxurl;
            downloadForm.style.display = 'none';
            
            for (var pair of formData.entries()) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = pair[0];
                input.value = pair[1];
                downloadForm.appendChild(input);
            }
            
            document.body.appendChild(downloadForm);
            downloadForm.submit();
            document.body.removeChild(downloadForm);
        }
        </script>
        <?php
    }

    /**
     * Renderiza relatório de cadeiras
     */
    private function render_cadeiras_report() {
        $cadeiras_stats = $this->get_cadeiras_stats();
        ?>
        <div class="movliv-cadeiras-report">
            <div class="cadeiras-summary">
                <h3><?php _e( 'Resumo das Cadeiras', 'movimento-livre' ); ?></h3>
                
                <div class="status-grid">
                    <?php foreach ( MOVLIV_Status_Manager::$product_statuses as $status => $label ): ?>
                        <div class="status-item">
                            <span class="status-label"><?php echo esc_html( $label ); ?>:</span>
                            <span class="status-count"><?php echo $cadeiras_stats[ $status ] ?? 0; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cadeiras-performance">
                <h3><?php _e( 'Performance das Cadeiras', 'movimento-livre' ); ?></h3>
                <?php echo $this->generate_cadeiras_performance_table(); ?>
            </div>

            <div class="manutencao-historico">
                <h3><?php _e( 'Histórico de Manutenção', 'movimento-livre' ); ?></h3>
                <?php echo $this->generate_manutencao_table(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza relatório de usuários
     */
    private function render_usuarios_report() {
        ?>
        <div class="movliv-usuarios-report">
            <div class="usuarios-stats">
                <h3><?php _e( 'Estatísticas de Usuários', 'movimento-livre' ); ?></h3>
                <?php echo $this->generate_usuarios_stats(); ?>
            </div>

            <div class="top-usuarios">
                <h3><?php _e( 'Usuários Mais Ativos', 'movimento-livre' ); ?></h3>
                <?php echo $this->generate_top_usuarios_table(); ?>
            </div>

            <div class="usuarios-por-regiao">
                <h3><?php _e( 'Distribuição por Região', 'movimento-livre' ); ?></h3>
                <canvas id="usuarios-regiao-chart"></canvas>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza relatório de performance
     */
    private function render_performance_report() {
        $performance = $this->get_performance_stats();
        ?>
        <div class="movliv-performance-report">
            <div class="kpis-grid">
                <div class="kpi-item">
                    <h4><?php _e( 'Tempo Médio de Empréstimo', 'movimento-livre' ); ?></h4>
                    <span class="kpi-value"><?php echo $performance['tempo_medio_emprestimo']; ?> dias</span>
                </div>
                
                <div class="kpi-item">
                    <h4><?php _e( 'Taxa de Renovação', 'movimento-livre' ); ?></h4>
                    <span class="kpi-value"><?php echo $performance['taxa_renovacao']; ?>%</span>
                </div>
                
                <div class="kpi-item">
                    <h4><?php _e( 'Tempo Médio de Processamento', 'movimento-livre' ); ?></h4>
                    <span class="kpi-value"><?php echo $performance['tempo_processamento']; ?> horas</span>
                </div>
                
                <div class="kpi-item">
                    <h4><?php _e( 'Satisfação (Avaliações)', 'movimento-livre' ); ?></h4>
                    <span class="kpi-value"><?php echo $performance['satisfacao_media']; ?>/5</span>
                </div>
            </div>

            <div class="performance-charts">
                <div class="chart-container">
                    <h3><?php _e( 'Evolução Temporal', 'movimento-livre' ); ?></h3>
                    <canvas id="performance-timeline-chart"></canvas>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * ✅ CORREÇÃO: Obtém estatísticas gerais
     */
    private function get_general_stats() {
        global $wpdb;
    
        // Total de empréstimos (apenas pedidos do plugin com CPF registrado)
        $total_emprestimos = $wpdb->get_var("
            SELECT COUNT(DISTINCT o.id)
            FROM {$wpdb->prefix}wc_orders o
            INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = o.id
            WHERE pm.meta_key = '_is_movimento_livre' AND pm.meta_value = 'yes'
              AND EXISTS (
                  SELECT 1 FROM {$wpdb->postmeta} pm2
                  WHERE pm2.post_id = o.id AND pm2.meta_key = '_cpf_solicitante' AND pm2.meta_value != ''
              )
        ");
    
        // Empréstimos este mês
        $emprestimos_mes = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT o.id)
            FROM {$wpdb->prefix}wc_orders o
            INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = o.id
            WHERE pm.meta_key = '_is_movimento_livre' AND pm.meta_value = 'yes'
              AND MONTH(o.date_created_gmt) = %d
              AND YEAR(o.date_created_gmt) = %d
        ", date('n'), date('Y')));
    
        // Total de cadeiras
        $total_cadeiras = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'product' 
              AND post_status = 'publish'
        ");
    
        // Cadeiras ativas (não em manutenção)
        $cadeiras_ativas = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_status_produto'
            WHERE p.post_type = 'product' 
              AND p.post_status = 'publish'
              AND (pm.meta_value IS NULL OR pm.meta_value != 'em_manutencao')
        ");
    
        // Usuários únicos (baseado no CPF do solicitante em pedidos do plugin)
        $usuarios_unicos = $wpdb->get_var("
            SELECT COUNT(DISTINCT pm.meta_value)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->prefix}wc_orders o ON pm.post_id = o.id
            WHERE pm.meta_key = '_cpf_solicitante'
              AND pm.meta_value != ''
              AND EXISTS (
                  SELECT 1 FROM {$wpdb->postmeta} pm2
                  WHERE pm2.post_id = o.id AND pm2.meta_key = '_is_movimento_livre' AND pm2.meta_value = 'yes'
              )
        ");
    
        // Novos usuários este mês (cadastro de novos clientes pelo CPF do usuário, não do pedido)
        $novos_usuarios_mes = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = 'billing_cpf' AND um.meta_value != ''
            WHERE MONTH(u.user_registered) = %d AND YEAR(u.user_registered) = %d
        ", date('n'), date('Y')));
    
        // Empréstimos feitos nos últimos 30 dias (ativos ou devolvidos)
        $emprestados = $wpdb->get_var("
            SELECT COUNT(DISTINCT o.id)
            FROM {$wpdb->prefix}wc_orders o
            INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = o.id
            WHERE pm.meta_key = '_is_movimento_livre' AND pm.meta_value = 'yes'
              AND o.date_created_gmt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
    
        // Devoluções nos últimos 30 dias (pedidos movidos para devolvido no período)
        // Supondo que status 'wc-completed' = devolvido
        $devolvidos = $wpdb->get_var("
            SELECT COUNT(DISTINCT o.id)
            FROM {$wpdb->prefix}wc_orders o
            INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = o.id
            WHERE pm.meta_key = '_is_movimento_livre' AND pm.meta_value = 'yes'
              AND o.status = 'wc-completed'
              AND o.date_updated_gmt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
    
        $taxa_devolucao = ($emprestados + $devolvidos) > 0 ? round(($devolvidos / ($emprestados + $devolvidos)) * 100, 1) : 0;
    
        return array(
            'total_emprestimos'   => intval($total_emprestimos),
            'emprestimos_mes'     => intval($emprestimos_mes),
            'total_cadeiras'      => intval($total_cadeiras),
            'cadeiras_ativas'     => intval($cadeiras_ativas),
            'usuarios_unicos'     => intval($usuarios_unicos),
            'novos_usuarios_mes'  => intval($novos_usuarios_mes),
            'taxa_devolucao'      => $taxa_devolucao
        );
    }

    /**
     * Obtém estatísticas das cadeiras
     */
    private function get_cadeiras_stats() {
        global $wpdb;
        
        $results = $wpdb->get_results( "
            SELECT 
                COALESCE(pm.meta_value, 'pronta') as status,
                COUNT(*) as count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_status_produto'
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish'
            GROUP BY COALESCE(pm.meta_value, 'pronta')
        " );
        
        $stats = array();
        foreach ( $results as $result ) {
            $stats[ $result->status ] = $result->count;
        }
        
        return $stats;
    }

    /**
     * Obtém estatísticas de performance
     */
    private function get_performance_stats() {
        global $wpdb;
        
        // Tempo médio de empréstimo
        $tempo_medio = $wpdb->get_var( "
            SELECT AVG(DATEDIFF(
                COALESCE(p2.post_date, NOW()), 
                p1.post_date
            )) as media
            FROM {$wpdb->posts} p1
            LEFT JOIN {$wpdb->posts} p2 ON p1.ID = p2.post_parent
            WHERE p1.post_type = 'shop_order'
            AND p1.post_status = 'processing'
        " );
        
        return array(
            'tempo_medio_emprestimo' => round( $tempo_medio ?? 0 ),
            'taxa_renovacao' => 0, // TODO: Implementar
            'tempo_processamento' => 24, // TODO: Implementar
            'satisfacao_media' => 4.5 // TODO: Implementar sistema de avaliação
        );
    }

    /**
     * ✅ CORREÇÃO: Gera tabela de empréstimos
     */
    private function generate_emprestimos_table( $filters = array() ) {
        // ✅ CORREÇÃO: Status corretos do WooCommerce
        $default_status = array( 'on-hold', 'processing', 'completed', 'cancelled' );
        
        $args = array(
            'type' => 'shop_order',
            'status' => $default_status,
            'limit' => 100,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // ✅ CORREÇÃO: Aplica filtros do formulário
        if ( ! empty( $filters['data_inicio'] ) && ! empty( $filters['data_fim'] ) ) {
            $args['date_created'] = $filters['data_inicio'] . '...' . $filters['data_fim'];
        }
        
        if ( ! empty( $filters['status'] ) ) {
            $args['status'] = array( $filters['status'] );
        }
        
        $orders = wc_get_orders( $args );
        
        // ✅ CORREÇÃO: Filtra por CPF se fornecido
        if ( ! empty( $filters['cpf'] ) ) {
            $cpf_filter = preg_replace( '/[^0-9]/', '', $filters['cpf'] );
            $orders = array_filter( $orders, function( $order ) use ( $cpf_filter ) {
                $order_cpf = $this->get_user_cpf_from_order( $order );
                return strpos( $order_cpf, $cpf_filter ) !== false;
            } );
        }
        
        // ✅ CORREÇÃO: Filtra apenas pedidos do plugin (que têm CPF)
        $orders = array_filter( $orders, function( $order ) {
            $cpf = $this->get_user_cpf_from_order( $order );
            return ! empty( $cpf );
        } );
        
        $html = '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __( 'Pedido', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Cliente', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'CPF', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Cadeira(s)', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Status', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Data', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Ações', 'movimento-livre' ) . '</th>';
        $html .= '</tr></thead><tbody>';
        
        if ( empty( $orders ) ) {
            $html .= '<tr><td colspan="7" style="text-align: center; padding: 40px;">';
            $html .= '<strong>' . __( 'Nenhum empréstimo encontrado', 'movimento-livre' ) . '</strong><br>';
            $html .= __( 'Tente alterar os filtros ou verifique se existem pedidos com CPF cadastrado.', 'movimento-livre' );
            $html .= '</td></tr>';
        } else {
            foreach ( $orders as $order ) {
                // ✅ CORREÇÃO: Usa meta key correto do CPF
                $cpf = $this->get_user_cpf_from_order( $order );
                $cpf_formatted = $this->format_cpf( $cpf );
                
                // ✅ CORREÇÃO: Busca cadeiras do pedido
                $cadeiras = array();
                foreach ( $order->get_items() as $item ) {
                    $product = $item->get_product();
                    if ( $product ) {
                        $sku = $product->get_sku();
                        $cadeiras[] = ! empty( $sku ) ? $sku : $product->get_name();
                    }
                }
                $cadeiras_str = implode( ', ', $cadeiras );
                
                // ✅ CORREÇÃO: Status personalizado
                $status_labels = array(
                    'on-hold' => 'Aguardando',
                    'processing' => 'Emprestado',
                    'completed' => 'Devolvido',
                    'cancelled' => 'Cancelado'
                );
                
                $status = $order->get_status();
                $status_label = $status_labels[$status] ?? $status;
                
                // ✅ CORREÇÃO: Link compatível com HPOS
                $edit_link = $this->get_order_edit_link( $order->get_id() );
                
                $html .= '<tr>';
                $html .= '<td><strong>#' . $order->get_order_number() . '</strong></td>';
                $html .= '<td>' . esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) . '</td>';
                $html .= '<td>' . esc_html( $cpf_formatted ) . '</td>';
                $html .= '<td>' . esc_html( $cadeiras_str ) . '</td>';
                $html .= '<td><span class="status-' . esc_attr( $status ) . '">' . esc_html( $status_label ) . '</span></td>';
                $html .= '<td>' . $order->get_date_created()->format( 'd/m/Y H:i' ) . '</td>';
                $html .= '<td><a href="' . esc_url( $edit_link ) . '" class="button button-small">' . __( 'Ver Pedido', 'movimento-livre' ) . '</a></td>';
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody></table>';
        
        // ✅ CORREÇÃO: Adiciona informações de contagem
        $total_orders = count( $orders );
        $html .= '<div class="tablenav bottom">';
        $html .= '<div class="alignleft actions">';
        $html .= '<span class="displaying-num">' . sprintf( _n( '%d empréstimo', '%d empréstimos', $total_orders, 'movimento-livre' ), $total_orders ) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * ✅ NOVO: Obtém CPF do usuário do pedido
     */
    private function get_user_cpf_from_order( $order ) {
        $user_id = $order->get_user_id();
        if ( ! $user_id ) {
            return '';
        }
        
        return get_user_meta( $user_id, 'billing_cpf', true );
    }

    /**
     * ✅ NOVO: Formata CPF para exibição
     */
    private function format_cpf( $cpf ) {
        $cpf = preg_replace( '/[^0-9]/', '', $cpf );
        
        if ( strlen( $cpf ) === 11 ) {
            return preg_replace( '/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf );
        }
        
        return $cpf;
    }
    
    /**
     * ✅ NOVO: Obtém link de edição do pedido (compatível com HPOS)
     */
    private function get_order_edit_link( $order_id ) {
        // Verifica se está usando HPOS
        if ( class_exists( 'Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore' ) ) {
            return admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
        }
        
        // Fallback para interface antiga
        return admin_url( 'post.php?post=' . $order_id . '&action=edit' );
    }

    /**
     * Gera tabela de performance das cadeiras
     */
    private function generate_cadeiras_performance_table() {
        global $wpdb;
        
        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT 
                p.ID,
                p.post_title,
                COALESCE(pm_sku.meta_value, '') as sku,
                COUNT(DISTINCT o.ID) as total_emprestimos,
                COALESCE(pm.meta_value, 'pronta') as status_atual
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.meta_value = p.ID AND oim.meta_key = '_product_id'
            LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = oim.order_item_id
            LEFT JOIN {$wpdb->posts} o ON o.ID = oi.order_id AND o.post_status IN ('processing', 'completed')
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_status_produto'
            LEFT JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            GROUP BY p.ID, p.post_title, pm_sku.meta_value
            ORDER BY total_emprestimos DESC
            LIMIT 20
        " ) );
        
        $html = '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __( 'TAG', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Cadeira', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Total Empréstimos', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Status Atual', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Ações', 'movimento-livre' ) . '</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ( $results as $result ) {
            $status_label = MOVLIV_Status_Manager::$product_statuses[ $result->status_atual ] ?? $result->status_atual;
            $sku = ! empty( $result->sku ) ? $result->sku : '-';
            
            $html .= '<tr>';
            $html .= '<td><strong>' . esc_html( $sku ) . '</strong></td>';
            $html .= '<td><a href="' . admin_url( 'post.php?post=' . $result->ID . '&action=edit' ) . '">' . esc_html( $result->post_title ) . '</a></td>';
            $html .= '<td>' . $result->total_emprestimos . '</td>';
            $html .= '<td><span class="status-badge status-' . esc_attr( $result->status_atual ) . '">' . esc_html( $status_label ) . '</span></td>';
            $html .= '<td><a href="' . admin_url( 'post.php?post=' . $result->ID . '&action=edit' ) . '" class="button button-small">' . __( 'Ver Detalhes', 'movimento-livre' ) . '</a></td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }

    /**
     * Gera tabela de manutenção
     */
    private function generate_manutencao_table() {
        // TODO: Implementar histórico de manutenção
        return '<p>' . __( 'Histórico de manutenção será implementado em versões futuras.', 'movimento-livre' ) . '</p>';
    }

    /**
     * Gera estatísticas de usuários
     */
    private function generate_usuarios_stats() {
        // TODO: Implementar estatísticas detalhadas de usuários
        return '<p>' . __( 'Estatísticas detalhadas de usuários em desenvolvimento.', 'movimento-livre' ) . '</p>';
    }

    /**
     * ✅ CORREÇÃO: Gera tabela de top usuários
     */
    private function generate_top_usuarios_table() {
        global $wpdb;
        
        $results = $wpdb->get_results( "
            SELECT 
                um_cpf.meta_value as cpf,
                COALESCE(CONCAT(NULLIF(um_fname.meta_value, ''), ' ', NULLIF(um_lname.meta_value, '')), 'Nome não informado') as nome,
                COUNT(*) as total_emprestimos,
                MAX(p.post_date) as ultimo_emprestimo
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
            INNER JOIN {$wpdb->usermeta} um_cpf ON u.ID = um_cpf.user_id AND um_cpf.meta_key = 'billing_cpf'
            LEFT JOIN {$wpdb->usermeta} um_fname ON u.ID = um_fname.user_id AND um_fname.meta_key = 'billing_first_name'
            LEFT JOIN {$wpdb->usermeta} um_lname ON u.ID = um_lname.user_id AND um_lname.meta_key = 'billing_last_name'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('on-hold', 'processing', 'completed')
            AND um_cpf.meta_value != ''
            AND um_cpf.meta_value IS NOT NULL
            GROUP BY um_cpf.meta_value
            ORDER BY total_emprestimos DESC, ultimo_emprestimo DESC
            LIMIT 10
        " );
        
        if ( empty( $results ) ) {
           // return '<div class="error">Nenhum usuário encontrado com empréstimos registrados.</div>';
        }
        
        $html = '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __( 'Nome', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'CPF', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Total Empréstimos', 'movimento-livre' ) . '</th>';
        $html .= '<th>' . __( 'Último Empréstimo', 'movimento-livre' ) . '</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ( $results as $result ) {
            $cpf_formatted = $this->format_cpf( $result->cpf );
            $ultimo_emprestimo = date( 'd/m/Y', strtotime( $result->ultimo_emprestimo ) );
            
            $html .= '<tr>';
            $html .= '<td>' . esc_html( $result->nome ) . '</td>';
            $html .= '<td>' . esc_html( $cpf_formatted ) . '</td>';
            $html .= '<td><strong>' . $result->total_emprestimos . '</strong></td>';
            $html .= '<td>' . $ultimo_emprestimo . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }

    /**
     * ✅ NOVO: AJAX - Filtro de empréstimos
     */
    public function ajax_filter_emprestimos() {
        check_ajax_referer( 'movliv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Sem permissão para acessar relatórios' );
        }
        
        $filters = array();
        
        // Sanitiza filtros
        if ( ! empty( $_POST['data_inicio'] ) ) {
            $filters['data_inicio'] = sanitize_text_field( $_POST['data_inicio'] );
        }
        
        if ( ! empty( $_POST['data_fim'] ) ) {
            $filters['data_fim'] = sanitize_text_field( $_POST['data_fim'] );
        }
        
        if ( ! empty( $_POST['status'] ) ) {
            $filters['status'] = sanitize_text_field( $_POST['status'] );
        }
        
        if ( ! empty( $_POST['cpf'] ) ) {
            $filters['cpf'] = sanitize_text_field( $_POST['cpf'] );
        }
        
        // Gera a tabela com os filtros
        $html = $this->generate_emprestimos_table( $filters );
        
        wp_send_json_success( array(
            'html' => $html,
            'message' => __( 'Relatório atualizado com sucesso', 'movimento-livre' )
        ) );
    }
    
    /**
     * ✅ NOVO: AJAX - Carrega tabela de empréstimos
     */
    public function ajax_load_emprestimos_table() {
        check_ajax_referer( 'movliv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Sem permissão para acessar relatórios' );
        }
        
        // Carrega todos os empréstimos sem filtros
        $html = $this->generate_emprestimos_table();
        
        wp_send_json_success( array(
            'html' => $html
        ) );
    }

    /**
     * AJAX: Exporta relatório
     */
    public function ajax_export_report() {
        check_ajax_referer( 'movliv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Sem permissão' );
        }
        
        $type = sanitize_text_field( $_POST['type'] );
        
        switch ( $type ) {
            case 'emprestimos':
                $this->export_emprestimos_csv();
                break;
            default:
                wp_send_json_error( 'Tipo de relatório inválido' );
        }
    }

    /**
     * ✅ CORREÇÃO: Exporta relatório de empréstimos em CSV
     */
    private function export_emprestimos_csv() {
        // ✅ CORREÇÃO: Aplica os mesmos filtros do formulário
        $filters = array();
        
        if ( ! empty( $_POST['data_inicio'] ) ) {
            $filters['data_inicio'] = sanitize_text_field( $_POST['data_inicio'] );
        }
        
        if ( ! empty( $_POST['data_fim'] ) ) {
            $filters['data_fim'] = sanitize_text_field( $_POST['data_fim'] );
        }
        
        if ( ! empty( $_POST['status'] ) ) {
            $filters['status'] = sanitize_text_field( $_POST['status'] );
        }
        
        if ( ! empty( $_POST['cpf'] ) ) {
            $filters['cpf'] = sanitize_text_field( $_POST['cpf'] );
        }
        
        // ✅ CORREÇÃO: Status corretos do WooCommerce
        $default_status = array( 'on-hold', 'processing', 'completed', 'cancelled' );
        
        $args = array(
            'type' => 'shop_order',
            'status' => $default_status,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Aplica filtros
        if ( ! empty( $filters['data_inicio'] ) && ! empty( $filters['data_fim'] ) ) {
            $args['date_created'] = $filters['data_inicio'] . '...' . $filters['data_fim'];
        }
        
        if ( ! empty( $filters['status'] ) ) {
            $args['status'] = array( $filters['status'] );
        }
        
        $orders = wc_get_orders( $args );
        
        // ✅ CORREÇÃO: Filtra por CPF se fornecido
        if ( ! empty( $filters['cpf'] ) ) {
            $cpf_filter = preg_replace( '/[^0-9]/', '', $filters['cpf'] );
            $orders = array_filter( $orders, function( $order ) use ( $cpf_filter ) {
                $order_cpf = $this->get_user_cpf_from_order( $order );
                return strpos( $order_cpf, $cpf_filter ) !== false;
            } );
        }
        
        // ✅ CORREÇÃO: Filtra apenas pedidos do plugin (que têm CPF)
        $orders = array_filter( $orders, function( $order ) {
            $cpf = $this->get_user_cpf_from_order( $order );
            return ! empty( $cpf );
        } );
        
        $filename = 'emprestimos-' . date( 'Y-m-d' ) . '.csv';
        
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        
        $output = fopen( 'php://output', 'w' );
        
        // ✅ CORREÇÃO: Cabeçalhos em português
        fputcsv( $output, array(
            'Número do Pedido',
            'Data do Pedido',
            'Status',
            'Cliente',
            'CPF',
            'Telefone',
            'E-mail',
            'Cadeiras (TAG/SKU)',
            'Observações'
        ) );
        
        // ✅ CORREÇÃO: Status personalizados
        $status_labels = array(
            'on-hold' => 'Aguardando',
            'processing' => 'Emprestado',
            'completed' => 'Devolvido',
            'cancelled' => 'Cancelado'
        );
        
        foreach ( $orders as $order ) {
            // ✅ CORREÇÃO: Usa meta key correto do CPF
            $cpf = $this->get_user_cpf_from_order( $order );
            $cpf_formatted = $this->format_cpf( $cpf );
            
            // Busca cadeiras do pedido
            $cadeiras = array();
            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( $product ) {
                    $sku = $product->get_sku();
                    $cadeiras[] = ! empty( $sku ) ? $sku : $product->get_name();
                }
            }
            $cadeiras_str = implode( ', ', $cadeiras );
            
            $status = $order->get_status();
            $status_label = $status_labels[$status] ?? $status;
            
            fputcsv( $output, array(
                $order->get_order_number(),
                $order->get_date_created()->format( 'd/m/Y H:i' ),
                $status_label,
                $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                $cpf_formatted,
                $order->get_billing_phone(),
                $order->get_billing_email(),
                $cadeiras_str,
                $order->get_customer_note()
            ) );
        }
        
        fclose( $output );
        exit;
    }

    /**
     * AJAX: Gera dados para gráficos
     */
    public function ajax_generate_chart_data() {
        check_ajax_referer( 'movliv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Sem permissão' );
        }
        
        $chart_type = sanitize_text_field( $_POST['chart_type'] );
        
        switch ( $chart_type ) {
            case 'emprestimos_mensal':
                wp_send_json_success( $this->get_emprestimos_mensal_data() );
                break;
            case 'status_cadeiras':
                wp_send_json_success( $this->get_status_cadeiras_data() );
                break;
            default:
                wp_send_json_error( 'Tipo de gráfico inválido' );
        }
    }

    /**
     * Obtém dados para gráfico de empréstimos mensais
     */
    private function get_emprestimos_mensal_data() {
        global $wpdb;
        
        $results = $wpdb->get_results( "
            SELECT 
                DATE_FORMAT(post_date, '%Y-%m') as mes,
                COUNT(*) as total
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ('processing', 'completed')
            AND post_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(post_date, '%Y-%m')
            ORDER BY mes
        " );
        
        $labels = array();
        $data = array();
        
        foreach ( $results as $result ) {
            $labels[] = date( 'M/Y', strtotime( $result->mes . '-01' ) );
            $data[] = $result->total;
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __( 'Empréstimos', 'movimento-livre' ),
                    'data' => $data,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                )
            )
        );
    }

    /**
     * Obtém dados para gráfico de status das cadeiras
     */
    private function get_status_cadeiras_data() {
        $stats = $this->get_cadeiras_stats();
        
        $labels = array();
        $data = array();
        $colors = array();
        
        $color_map = array(
            'pronta' => '#28a745',
            'emprestado' => '#ffc107',
            'em_avaliacao' => '#17a2b8',
            'em_manutencao' => '#dc3545'
        );
        
        foreach ( MOVLIV_Status_Manager::$product_statuses as $status => $label ) {
            $labels[] = $label;
            $data[] = $stats[ $status ] ?? 0;
            $colors[] = $color_map[ $status ] ?? '#6c757d';
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    'backgroundColor' => $colors
                )
            )
        );
    }


} 