<?php
/**
 * Debug de Email - Movimento Livre
 * Script para testar e diagnosticar problemas de email
 */

// Carrega o WordPress
$root = __DIR__;
for ($i = 0; $i < 10; $i++) {
    if (file_exists($root . '/wp-load.php')) {
        require_once $root . '/wp-load.php';
        break;
    }
    $root = dirname($root);
}
if (!defined('ABSPATH')) {
    die('Não foi possível localizar wp-load.php');
}

// Verifica se o plugin está ativo
if (!class_exists('MOVLIV_Notifications')) {
    die('Plugin Movimento Livre não está ativo');
}

// Função para testar cada parte individualmente
function test_individual_parts() {
    echo "<h3>🔍 Teste Individual de Cada Parte</h3>";
    
    // Teste 1: get_bloginfo
    echo "<h4>1. Teste get_bloginfo()</h4>";
    try {
        $blog_name = get_bloginfo('name');
        echo "✅ get_bloginfo('name'): " . htmlspecialchars($blog_name) . "<br>";
    } catch (Exception $e) {
        echo "❌ Erro em get_bloginfo('name'): " . $e->getMessage() . "<br>";
    }
    
    // Teste 2: get_option
    echo "<h4>2. Teste get_option('admin_email')</h4>";
    try {
        $admin_email = get_option('admin_email');
        echo "✅ get_option('admin_email'): " . htmlspecialchars($admin_email) . "<br>";
    } catch (Exception $e) {
        echo "❌ Erro em get_option('admin_email'): " . $e->getMessage() . "<br>";
    }
    
    // Teste 3: current_time
    echo "<h4>3. Teste current_time()</h4>";
    try {
        $current_time = current_time('d/m/Y H:i:s');
        echo "✅ current_time('d/m/Y H:i:s'): " . htmlspecialchars($current_time) . "<br>";
    } catch (Exception $e) {
        echo "❌ Erro em current_time(): " . $e->getMessage() . "<br>";
    }
    
    // Teste 4: sprintf com __()
    echo "<h4>4. Teste sprintf com __()</h4>";
    try {
        $subject = sprintf( 
            __('[%s] Teste de Notificação', 'movimento-livre'),
            get_bloginfo('name')
        );
        echo "✅ sprintf com __(): " . htmlspecialchars($subject) . "<br>";
    } catch (Exception $e) {
        echo "❌ Erro em sprintf com __(): " . $e->getMessage() . "<br>";
    }
    
    // Teste 5: Construção da mensagem
    echo "<h4>5. Teste construção da mensagem</h4>";
    try {
        $message = "<h2>Teste de Notificação</h2>";
        $message .= "<p>Este é um email de teste do sistema Movimento Livre.</p>";
        $message .= "<p>Data/Hora: " . current_time('d/m/Y H:i:s') . "</p>";
        echo "✅ Mensagem construída com sucesso<br>";
        echo "Tamanho da mensagem: " . strlen($message) . " caracteres<br>";
    } catch (Exception $e) {
        echo "❌ Erro na construção da mensagem: " . $e->getMessage() . "<br>";
    }
    
    // Teste 6: Headers padrão
    echo "<h4>6. Teste construção dos headers</h4>";
    try {
        $default_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        echo "✅ Headers construídos com sucesso<br>";
        foreach ($default_headers as $header) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;Header: " . htmlspecialchars($header) . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erro na construção dos headers: " . $e->getMessage() . "<br>";
    }
}

// Função para testar o método send_email diretamente
function test_send_email_directly() {
    echo "<h3>📧 Teste Direto do Método send_email</h3>";
    
    try {
        $notifications = MOVLIV_Notifications::getInstance();
        
        // Teste com parâmetros simples
        $to = get_option('admin_email');
        $subject = 'Teste Direto - ' . date('Y-m-d H:i:s');
        $message = '<h2>Teste Direto</h2><p>Testando método send_email diretamente.</p>';
        
        echo "Enviando email de teste para: " . htmlspecialchars($to) . "<br>";
        echo "Assunto: " . htmlspecialchars($subject) . "<br>";
        
        // Usar reflexão para acessar método privado
        $reflection = new ReflectionClass($notifications);
        $send_email_method = $reflection->getMethod('send_email');
        $send_email_method->setAccessible(true);
        
        $result = $send_email_method->invoke($notifications, $to, $subject, $message);
        
        if ($result) {
            echo "✅ Email enviado com sucesso via send_email()<br>";
        } else {
            echo "❌ Falha no envio via send_email()<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro no teste direto: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Função para testar com error_reporting ativo
function test_with_error_reporting() {
    echo "<h3>🐛 Teste com Error Reporting Ativo</h3>";
    
    // Ativa error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    try {
        $notifications = MOVLIV_Notifications::getInstance();
        $result = $notifications->send_test_notification(get_option('admin_email'));
        
        if ($result) {
            echo "✅ send_test_notification() retornou true<br>";
        } else {
            echo "❌ send_test_notification() retornou false<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Exceção capturada: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    } catch (Error $e) {
        echo "❌ Erro fatal capturado: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug de Email - Movimento Livre</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h3 { color: #34495e; margin-top: 30px; }
        h4 { color: #7f8c8d; margin-top: 20px; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .info { color: #3498db; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; }
        .back-btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug de Email - Movimento Livre</h1>
        
        <div class="info">
            <strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?><br>
            <strong>Site:</strong> <?php echo get_site_url(); ?><br>
            <strong>Admin Email:</strong> <?php echo get_option('admin_email'); ?>
        </div>

        <hr>

        <?php
        // Executa todos os testes
        test_individual_parts();
        echo "<hr>";
        test_send_email_directly();
        echo "<hr>";
        test_with_error_reporting();
        ?>

        <a href="<?php echo admin_url(); ?>" class="back-btn">← Voltar ao Admin</a>
    </div>
</body>
</html>
