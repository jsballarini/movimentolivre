<?php
/**
 * Configurações para Correção de Emails - Movimento Livre
 * 
 * Adicione estas linhas ao seu wp-config.php para corrigir problemas de email
 */

// Configurações de Email
define( 'SMTP_HOST', 'localhost' );
define( 'SMTP_AUTH', false );
define( 'SMTP_PORT', 25 );
define( 'SMTP_SECURE', '' );
define( 'SMTP_USERNAME', '' );
define( 'SMTP_PASSWORD', '' );

// Força uso de SMTP local
add_action( 'phpmailer_init', 'movliv_smtp_config' );

function movliv_smtp_config( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host = SMTP_HOST;
    $phpmailer->SMTPAuth = SMTP_AUTH;
    $phpmailer->Port = SMTP_PORT;
    $phpmailer->SMTPSecure = SMTP_SECURE;
    
    if ( SMTP_AUTH ) {
        $phpmailer->Username = SMTP_USERNAME;
        $phpmailer->Password = SMTP_PASSWORD;
    }
    
    // Configurações adicionais para melhorar entrega
    $phpmailer->SMTPKeepAlive = true;
    $phpmailer->Timeout = 30;
    
    // Log para debug
    if ( WP_DEBUG ) {
        $phpmailer->SMTPDebug = 2;
    }
}

// Configurações alternativas para servidores que não suportam SMTP
if ( ! function_exists( 'wp_mail' ) ) {
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        // Fallback para função mail() nativa do PHP
        if ( is_array( $headers ) ) {
            $headers = implode( "\r\n", $headers );
        }
        
        return mail( $to, $subject, $message, $headers );
    }
}

// Habilita debug de email
if ( WP_DEBUG ) {
    add_action( 'wp_mail_failed', 'movliv_log_mailer_errors', 10, 1 );
    
    function movliv_log_mailer_errors( $wp_error ) {
        $fn = ABSPATH . '/wp-content/mail-errors.log';
        $fp = fopen( $fn, 'a' );
        fputs( $fp, "Mailer Error: " . $wp_error->get_error_message() . "\n" );
        fclose( $fp );
    }
}

// Configurações específicas para o plugin Movimento Livre
add_filter( 'wp_mail_content_type', 'movliv_set_html_content_type' );

function movliv_set_html_content_type() {
    return 'text/html';
}

// Força headers corretos para emails
add_filter( 'wp_mail_headers', 'movliv_email_headers' );

function movliv_email_headers( $headers ) {
    $site_name = get_bloginfo( 'name' );
    $admin_email = get_option( 'admin_email' );
    
    $default_headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . $admin_email . '>',
        'Reply-To: ' . $admin_email,
        'X-Mailer: WordPress/' . get_bloginfo( 'version' ) . ' - Movimento Livre'
    );
    
    if ( is_array( $headers ) ) {
        $headers = array_merge( $default_headers, $headers );
    } else {
        $headers = $default_headers;
    }
    
    return $headers;
}

// Teste de email automático na ativação
register_activation_hook( __FILE__, 'movliv_test_email_on_activation' );

function movliv_test_email_on_activation() {
    $admin_email = get_option( 'admin_email' );
    $subject = 'Teste de Email - Movimento Livre Ativado';
    $message = '<h2>Teste de Email</h2><p>O plugin Movimento Livre foi ativado e o sistema de email está funcionando.</p>';
    
    wp_mail( $admin_email, $subject, $message );
}

echo "Configurações de email aplicadas com sucesso!";
?>
