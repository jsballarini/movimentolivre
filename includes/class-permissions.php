<?php
/**
 * Gerenciador de Permissões - Movimento Livre
 *
 * @package MovimentoLivre
 * @since 0.0.1
 */

// Impede acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe para gerenciar roles e permissões customizadas
 */
class MOVLIV_Permissions {

    /**
     * Instância única da classe
     * @var MOVLIV_Permissions
     */
    private static $instance = null;

    /**
     * Permissões customizadas do plugin
     * @var array
     */
    public static $custom_capabilities = array(
        'movliv_view_orders' => 'Visualizar todos os pedidos (empréstimos) no admin',
        'movliv_manage_forms' => 'Gerar e anexar formulários PDF',
        'movliv_submit_evaluation' => 'Preencher e enviar formulário de avaliação técnica',
        'movliv_manage_status' => 'Alterar status de produtos (cadeiras) manual ou via formulário',
        'movliv_view_reports' => 'Acessar página de relatórios e exportar CSV',
        'movliv_manage_settings' => 'Gerenciar configurações do plugin',
        'movliv_manage_roles' => 'Atribuir permissões e funções aos usuários',
        'movliv_view_cadeiras' => 'Visualizar a lista de produtos (cadeiras) no admin',
        'movliv_manage_emails' => 'Personalizar templates e notificações por e-mail'
    );

    /**
     * Roles customizadas do plugin
     * @var array
     */
    public static $custom_roles = array(
        'movliv_colaborador' => array(
            'display_name' => 'Colaborador',
            'capabilities' => array(
                'read' => true,
                'movliv_view_orders' => true,
                'movliv_manage_forms' => true,
                'movliv_view_cadeiras' => true
            )
        ),
        'movliv_avaliador' => array(
            'display_name' => 'Avaliador',
            'capabilities' => array(
                'read' => true,
                'movliv_view_orders' => true,
                'movliv_manage_forms' => true,
                'movliv_view_cadeiras' => true,
                'movliv_submit_evaluation' => true,
                'movliv_manage_status' => true
            )
        ),
        'movliv_admin' => array(
            'display_name' => 'Administrador Movimento Livre',
            'capabilities' => array(
                'read' => true,
                'edit_shop_orders' => true,
                'edit_products' => true,
                'movliv_view_orders' => true,
                'movliv_manage_forms' => true,
                'movliv_view_cadeiras' => true,
                'movliv_submit_evaluation' => true,
                'movliv_manage_status' => true,
                'movliv_view_reports' => true,
                'movliv_manage_settings' => true,
                'movliv_manage_roles' => true,
                'movliv_manage_emails' => true
            )
        )
    );

    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new MOVLIV_Permissions();
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
        // Adiciona permissões aos administradores existentes
        add_action( 'admin_init', array( $this, 'add_caps_to_administrator' ) );
        
        // Remove capacidades na desativação
        register_deactivation_hook( MOVLIV_PLUGIN_BASENAME, array( $this, 'remove_custom_roles' ) );
    }

    /**
     * Cria roles customizadas (chamado na ativação do plugin)
     */
    public static function create_roles() {
        foreach ( self::$custom_roles as $role_name => $role_data ) {
            // Remove role se já existir
            remove_role( $role_name );
            
            // Adiciona role
            add_role( 
                $role_name, 
                __( $role_data['display_name'], 'movimento-livre' ), 
                $role_data['capabilities']
            );
        }
        
        // Adiciona permissões ao administrador
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            foreach ( self::$custom_capabilities as $cap => $description ) {
                $admin_role->add_cap( $cap );
            }
        }
        
        error_log( 'MovLiv: Roles e permissões customizadas criadas' );
    }

    /**
     * Adiciona capacidades aos administradores
     */
    public function add_caps_to_administrator() {
        $admin_role = get_role( 'administrator' );
        
        if ( $admin_role ) {
            foreach ( self::$custom_capabilities as $cap => $description ) {
                if ( ! $admin_role->has_cap( $cap ) ) {
                    $admin_role->add_cap( $cap );
                }
            }
        }
    }

    /**
     * Remove roles customizadas (na desativação)
     */
    public function remove_custom_roles() {
        foreach ( self::$custom_roles as $role_name => $role_data ) {
            remove_role( $role_name );
        }
        
        // Remove capacidades do administrador
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            foreach ( self::$custom_capabilities as $cap => $description ) {
                $admin_role->remove_cap( $cap );
            }
        }
        
        error_log( 'MovLiv: Roles customizadas removidas' );
    }

    /**
     * Verifica se usuário atual pode visualizar pedidos
     */
    public static function can_view_orders() {
        return current_user_can( 'movliv_view_orders' ) || current_user_can( 'administrator' );
    }

    /**
     * Verifica se usuário atual pode gerenciar formulários
     */
    public static function can_manage_forms() {
        return current_user_can( 'movliv_manage_forms' ) || current_user_can( 'administrator' );
    }

    /**
     * Verifica se usuário atual pode fazer avaliações
     */
    public static function can_submit_evaluations() {
        return current_user_can( 'movliv_submit_evaluation' ) || current_user_can( 'administrator' );
    }

    /**
     * Verifica se usuário atual pode alterar status
     */
    public static function can_manage_status() {
        return current_user_can( 'movliv_manage_status' ) || current_user_can( 'administrator' );
    }

    /**
     * Verifica se usuário atual pode visualizar relatórios
     */
    public static function can_view_reports() {
        return current_user_can( 'movliv_view_reports' ) || current_user_can( 'administrator' );
    }

    /**
     * Verifica se usuário atual pode gerenciar configurações
     */
    public static function can_manage_settings() {
        return current_user_can( 'movliv_manage_settings' ) || current_user_can( 'administrator' );
    }

    /**
     * Verifica se usuário atual pode gerenciar roles
     */
    public static function can_manage_roles() {
        return current_user_can( 'movliv_manage_roles' ) || current_user_can( 'administrator' );
    }

    /**
     * Verifica se usuário atual pode visualizar cadeiras
     */
    public static function can_view_cadeiras() {
        return current_user_can( 'movliv_view_cadeiras' ) || current_user_can( 'administrator' );
    }

    /**
     * Verifica se usuário atual pode gerenciar emails
     */
    public static function can_manage_emails() {
        return current_user_can( 'movliv_manage_emails' ) || current_user_can( 'administrator' );
    }

    /**
     * Obtém usuários com uma determinada permissão
     */
    public static function get_users_with_capability( $capability ) {
        $users = get_users( array(
            'meta_query' => array(
                array(
                    'key' => 'wp_capabilities',
                    'value' => $capability,
                    'compare' => 'LIKE'
                )
            )
        ) );
        
        // Também busca administradores
        $admins = get_users( array( 'role' => 'administrator' ) );
        $users = array_merge( $users, $admins );
        
        // Remove duplicatas
        $unique_users = array();
        foreach ( $users as $user ) {
            $unique_users[ $user->ID ] = $user;
        }
        
        return array_values( $unique_users );
    }

    /**
     * Obtém todos os usuários com roles do Movimento Livre
     */
    public static function get_movimento_livre_users() {
        $users = array();
        
        foreach ( self::$custom_roles as $role_name => $role_data ) {
            $role_users = get_users( array( 'role' => $role_name ) );
            $users = array_merge( $users, $role_users );
        }
        
        return $users;
    }

    /**
     * Atribui role do Movimento Livre a um usuário
     */
    public static function assign_user_role( $user_id, $role ) {
        if ( ! array_key_exists( $role, self::$custom_roles ) ) {
            return false;
        }
        
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return false;
        }
        
        // Remove outras roles do Movimento Livre
        foreach ( self::$custom_roles as $role_name => $role_data ) {
            $user->remove_role( $role_name );
        }
        
        // Adiciona nova role
        $user->add_role( $role );
        
        return true;
    }

    /**
     * Remove role do Movimento Livre de um usuário
     */
    public static function remove_user_role( $user_id, $role ) {
        if ( ! array_key_exists( $role, self::$custom_roles ) ) {
            return false;
        }
        
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return false;
        }
        
        $user->remove_role( $role );
        
        return true;
    }

    /**
     * Verifica se usuário tem alguma role do Movimento Livre
     */
    public static function user_has_movimento_livre_role( $user_id ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return false;
        }
        
        foreach ( self::$custom_roles as $role_name => $role_data ) {
            if ( in_array( $role_name, $user->roles ) ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Obtém roles do Movimento Livre de um usuário
     */
    public static function get_user_movimento_livre_roles( $user_id ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return array();
        }
        
        $user_roles = array();
        
        foreach ( self::$custom_roles as $role_name => $role_data ) {
            if ( in_array( $role_name, $user->roles ) ) {
                $user_roles[] = array(
                    'role' => $role_name,
                    'display_name' => $role_data['display_name']
                );
            }
        }
        
        return $user_roles;
    }

    /**
     * Obtém lista de todas as permissões customizadas
     */
    public static function get_custom_capabilities() {
        return self::$custom_capabilities;
    }

    /**
     * Obtém lista de todas as roles customizadas
     */
    public static function get_custom_roles() {
        return self::$custom_roles;
    }

    /**
     * Verifica se uma role é do Movimento Livre
     */
    public static function is_movimento_livre_role( $role_name ) {
        return array_key_exists( $role_name, self::$custom_roles );
    }

    /**
     * Obtém permissões de uma role específica
     */
    public static function get_role_capabilities( $role_name ) {
        if ( ! array_key_exists( $role_name, self::$custom_roles ) ) {
            return array();
        }
        
        return self::$custom_roles[ $role_name ]['capabilities'];
    }

    /**
     * Adiciona permissão customizada a uma role existente
     */
    public static function add_capability_to_role( $role_name, $capability ) {
        $role = get_role( $role_name );
        
        if ( $role && array_key_exists( $capability, self::$custom_capabilities ) ) {
            $role->add_cap( $capability );
            return true;
        }
        
        return false;
    }

    /**
     * Remove permissão customizada de uma role existente
     */
    public static function remove_capability_from_role( $role_name, $capability ) {
        $role = get_role( $role_name );
        
        if ( $role && array_key_exists( $capability, self::$custom_capabilities ) ) {
            $role->remove_cap( $capability );
            return true;
        }
        
        return false;
    }
} 