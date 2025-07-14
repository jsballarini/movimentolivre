<?php
/**
 * Plugin Name: Movimento Livre
 * Plugin URI: https://github.com/jsballarini
 * Description: Sistema social de empréstimos gratuitos de cadeiras de rodas para o Instituto Bernardo Ferreira - Um Legado em Movimento. Transforma o WooCommerce em uma plataforma robusta de empréstimos, devoluções, avaliações e controle de estoque social.
 * Version: 0.0.2
 * Author: Juliano Ballarini e Leonardo Soares
 * Author URI: https://github.com/jsballarini
 * Text Domain: movimento-livre
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.3
 * Requires PHP: 8.0
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constantes do plugin
define( 'MOVLIV_VERSION', '0.0.2' );
define( 'MOVLIV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MOVLIV_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MOVLIV_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Classe principal do plugin Movimento Livre
 */
class MovimentoLivre {

    /**
     * Instância única da classe
     * @var MovimentoLivre
     */
    private static $instance = null;

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MovimentoLivre();
        }
        return self::$instance;
    }

    /**
     * Construtor privado para padrão Singleton
     */
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Inicializa o plugin
     */
    public function init() {
        // Verifica se WooCommerce está ativo
        if ( ! $this->is_woocommerce_active() ) {
            return;
        }

        // Carrega textdomain para traduções
        load_plugin_textdomain( 'movimento-livre', false, dirname( MOVLIV_PLUGIN_BASENAME ) . '/languages' );

        // Inclui arquivos necessários
        $this->includes();
        
        // Inicializa componentes
        $this->init_components();
    }

    /**
     * Inclui arquivos necessários
     */
    private function includes() {
        // Classes principais
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-status-manager.php';
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-cpf-validator.php';
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-pdf-generator.php';
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-product-status-handler.php';
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-order-hooks.php';
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-formularios.php';
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-admin-interface.php';
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-notifications.php';
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-permissions.php';
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-shortcodes.php';
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-reports.php';
    }

    /**
     * Inicializa componentes do plugin
     */
    private function init_components() {
        // Inicializa classes principais
        MOVLIV_Status_Manager::getInstance();
        MOVLIV_CPF_Validator::getInstance();
        MOVLIV_PDF_Generator::getInstance();
        MOVLIV_Product_Status_Handler::getInstance();
        MOVLIV_Order_Hooks::getInstance();
        MOVLIV_Formularios::getInstance();
        MOVLIV_Admin_Interface::getInstance();
        MOVLIV_Notifications::getInstance();
        MOVLIV_Permissions::getInstance();
        MOVLIV_Shortcodes::getInstance();
        MOVLIV_Reports::getInstance();
    }

    /**
     * Verifica se WooCommerce está ativo
     */
    private function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Mostra avisos no admin
     */
    public function admin_notices() {
        if ( ! $this->is_woocommerce_active() ) {
            echo '<div class="notice notice-error"><p>';
            _e( 'Movimento Livre requer o WooCommerce para funcionar. Por favor, instale e ative o WooCommerce.', 'movimento-livre' );
            echo '</p></div>';
        }
    }

    /**
     * Ações na ativação do plugin
     */
    public function activate() {
        // Verifica requisitos mínimos
        if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
            deactivate_plugins( MOVLIV_PLUGIN_BASENAME );
            wp_die( __( 'Movimento Livre requer PHP 8.0 ou superior.', 'movimento-livre' ) );
        }

        if ( ! $this->is_woocommerce_active() ) {
            deactivate_plugins( MOVLIV_PLUGIN_BASENAME );
            wp_die( __( 'Movimento Livre requer o WooCommerce instalado e ativo.', 'movimento-livre' ) );
        }

        // Cria diretório para uploads de PDFs
        $upload_dir = wp_upload_dir();
        $movliv_dir = $upload_dir['basedir'] . '/movliv/';
        
        if ( ! file_exists( $movliv_dir ) ) {
            wp_mkdir_p( $movliv_dir );
            
            // Cria arquivo .htaccess para proteger diretório
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents( $movliv_dir . '.htaccess', $htaccess_content );
        }

        // Inclui classe de permissões e configura roles
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-permissions.php';
        MOVLIV_Permissions::create_roles();

        // Registra status customizados
        require_once MOVLIV_PLUGIN_PATH . 'includes/class-status-manager.php';
        MOVLIV_Status_Manager::register_custom_order_statuses();

        // ✅ NOVO: Inicializa produtos existentes como cadeiras
        // Isso garante que produtos já criados sejam configurados corretamente
        $status_manager = MOVLIV_Status_Manager::getInstance();
        $status_manager->init_existing_products();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Adiciona opção de versão
        add_option( 'movliv_version', MOVLIV_VERSION );
    }

    /**
     * Ações na desativação do plugin
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Inicializa o plugin
MovimentoLivre::getInstance(); 
