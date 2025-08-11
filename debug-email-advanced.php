<?php
/**
 * Debug Avançado de Email - Movimento Livre
 * Script para testar cada parte do processo de envio de email
 */

// Carrega o WordPress
$wp_load_path = '';
$current_dir = __DIR__;

// Procura pelo wp-load.php subindo os diretórios
while ($current_dir !== dirname($current_dir)) {
    if (file_exists($current_dir . '/wp-load.php')) {
        $wp_load_path = $current_dir . '/wp-load.php';
        break;
    }
    $current_dir = dirname($current_dir);
}

if (!$wp_load_path) {
    die('❌ wp-load.php não encontrado. Execute este script na raiz do WordPress.');
}

require_once $wp_load_path;

// Verifica se o plugin está ativo
if (!class_exists('MOVLIV_Notifications')) {
    die('❌ Plugin Movimento Livre não está ativo.');
}

// Habilita todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicia o teste
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Debug Avançado de Email - Movimento Livre</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        .code { background-color: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; }
        h1, h2, h3 { color: #333; }
        .back-link { margin: 20px 0; }
        .back-link a { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
    </style>
</head>
<body>";

echo "<div class='back-link'><a href='../wp-admin/admin.php?page=movimento-livre'>← Voltar ao Admin</a></div>";
echo "<h1>🔍 Debug Avançado de Email - Movimento Livre</h1>";

// 1. Teste de configurações básicas
echo "<div class='test-section info'>";
echo "<h2>📋 1. Configurações Básicas</h2>";
echo "<strong>Site URL:</strong> " . get_site_url() . "<br>";
echo "<strong>Admin Email:</strong> " . get_option('admin_email') . "<br>";
echo "<strong>From Email:</strong> " . get_bloginfo('name') . " <" . get_option('admin_email') . "><br>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>WordPress Version:</strong> " . get_bloginfo('version') . "<br>";
echo "</div>";

// 2. Teste de instância da classe
echo "<div class='test-section info'>";
echo "<h2>🏗️ 2. Status da Classe de Notificações</h2>";

try {
    $notifications = MOVLIV_Notifications::getInstance();
    echo "✅ Classe MOVLIV_Notifications encontrada<br>";
    echo "✅ Instância criada com sucesso<br>";
    echo "✅ Tipo da instância: " . get_class($notifications) . "<br>";
} catch (Exception $e) {
    echo "❌ Erro ao criar instância: " . $e->getMessage() . "<br>";
    return;
} catch (Error $e) {
    echo "❌ Erro fatal ao criar instância: " . $e->getMessage() . "<br>";
    return;
}
echo "</div>";

// 3. Teste de componentes individuais
echo "<div class='test-section info'>";
echo "<h2>🧩 3. Teste de Componentes Individuais</h2>";

// Teste get_bloginfo
try {
    $site_name = get_bloginfo('name');
    echo "✅ get_bloginfo('name'): " . htmlspecialchars($site_name) . "<br>";
} catch (Exception $e) {
    echo "❌ Erro em get_bloginfo('name'): " . $e->getMessage() . "<br>";
}

// Teste get_option
try {
    $admin_email = get_option('admin_email');
    echo "✅ get_option('admin_email'): " . htmlspecialchars($admin_email) . "<br>";
} catch (Exception $e) {
    echo "❌ Erro em get_option('admin_email'): " . $e->getMessage() . "<br>";
}

// Teste current_time
try {
    $current_time = current_time('d/m/Y H:i:s');
    echo "✅ current_time('d/m/Y H:i:s'): " . htmlspecialchars($current_time) . "<br>";
} catch (Exception $e) {
    echo "❌ Erro em current_time: " . $e->getMessage() . "<br>";
}

// Teste sprintf
try {
    $subject = sprintf('[%s] Teste de Notificação', get_bloginfo('name'));
    echo "✅ sprintf com get_bloginfo: " . htmlspecialchars($subject) . "<br>";
} catch (Exception $e) {
    echo "❌ Erro em sprintf: " . $e->getMessage() . "<br>";
}

echo "</div>";

// 4. Teste de construção de mensagem
echo "<div class='test-section info'>";
echo "<h2>📝 4. Teste de Construção de Mensagem</h2>";

try {
    $message = "<h2>Teste de Notificação</h2>";
    $message .= "<p>Este é um email de teste do sistema Movimento Livre.</p>";
    $message .= "<p>Data/Hora: " . current_time('d/m/Y H:i:s') . "</p>";
    
    echo "✅ Mensagem construída com sucesso<br>";
    echo "✅ Tamanho da mensagem: " . strlen($message) . " caracteres<br>";
    echo "✅ Conteúdo da mensagem:<br>";
    echo "<div class='code'>" . htmlspecialchars($message) . "</div>";
} catch (Exception $e) {
    echo "❌ Erro ao construir mensagem: " . $e->getMessage() . "<br>";
}
echo "</div>";

// 5. Teste de construção de headers
echo "<div class='test-section info'>";
echo "<h2>📋 5. Teste de Construção de Headers</h2>";

try {
    $default_headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );
    
    echo "✅ Headers construídos com sucesso<br>";
    echo "✅ Headers finais:<br>";
    echo "<div class='code'>";
    foreach ($default_headers as $header) {
        echo htmlspecialchars($header) . "<br>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "❌ Erro ao construir headers: " . $e->getMessage() . "<br>";
}
echo "</div>";

// 6. Teste direto do wp_mail com os mesmos parâmetros
echo "<div class='test-section info'>";
echo "<h2>📧 6. Teste Direto do wp_mail com Mesmos Parâmetros</h2>";

try {
    $to = get_option('admin_email');
    $subject = sprintf('[%s] Teste Direto wp_mail', get_bloginfo('name'));
    $message = "<h2>Teste Direto wp_mail</h2><p>Testando wp_mail com os mesmos parâmetros.</p>";
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );
    
    echo "Enviando email de teste para: " . htmlspecialchars($to) . "<br>";
    echo "Assunto: " . htmlspecialchars($subject) . "<br>";
    echo "Headers: " . htmlspecialchars(implode(', ', $headers)) . "<br>";
    
    $result = wp_mail($to, $subject, $message, $headers);
    
    if ($result) {
        echo "✅ wp_mail() funcionando com os mesmos parâmetros<br>";
    } else {
        echo "❌ wp_mail() falhou com os mesmos parâmetros<br>";
    }
} catch (Exception $e) {
    echo "❌ Exceção no teste direto: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "❌ Erro fatal no teste direto: " . $e->getMessage() . "<br>";
}
echo "</div>";

// 7. Teste de Reflection para acessar método privado
echo "<div class='test-section info'>";
echo "<h2>🔍 7. Teste de Reflection - Acesso ao Método Privado</h2>";

try {
    $reflection = new ReflectionClass($notifications);
    
    if ($reflection->hasMethod('send_email')) {
        echo "✅ Método send_email encontrado via Reflection<br>";
        
        $send_email_method = $reflection->getMethod('send_email');
        $send_email_method->setAccessible(true);
        
        echo "✅ Método send_email acessível via Reflection<br>";
        
        // Testa o método com os mesmos parâmetros
        $to = get_option('admin_email');
        $subject = sprintf('[%s] Teste Reflection', get_bloginfo('name'));
        $message = "<h2>Teste Reflection</h2><p>Testando método privado via Reflection.</p>";
        
        echo "Testando método send_email com Reflection...<br>";
        $result = $send_email_method->invoke($notifications, $to, $subject, $message);
        
        if ($result) {
            echo "✅ send_email() funcionando via Reflection<br>";
        } else {
            echo "❌ send_email() falhou via Reflection<br>";
        }
    } else {
        echo "❌ Método send_email não encontrado via Reflection<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro no teste de Reflection: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "❌ Erro fatal no teste de Reflection: " . $e->getMessage() . "<br>";
}
echo "</div>";

// 8. Teste de logs de erro
echo "<div class='test-section info'>";
echo "<h2>📋 8. Verificação de Logs de Erro</h2>";

$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    echo "✅ Arquivo de log encontrado: " . $log_file . "<br>";
    
    // Lê as últimas linhas do log
    $log_content = file_get_contents($log_file);
    $log_lines = explode("\n", $log_content);
    $last_lines = array_slice($log_lines, -20); // Últimas 20 linhas
    
    echo "✅ Últimas 20 linhas do log:<br>";
    echo "<div class='code'>";
    foreach ($last_lines as $line) {
        if (trim($line) && strpos($line, 'MovLiv:') !== false) {
            echo htmlspecialchars($line) . "<br>";
        }
    }
    echo "</div>";
} else {
    echo "❌ Arquivo de log não encontrado: " . $log_file . "<br>";
    echo "ℹ️ Verifique se WP_DEBUG_LOG está ativo no wp-config.php<br>";
}
echo "</div>";

// 9. Teste de hooks e filtros
echo "<div class='test-section info'>";
echo "<h2>🔗 9. Verificação de Hooks e Filtros</h2>";

// Verifica se há filtros no wp_mail
$wp_mail_filters = has_filter('wp_mail');
if ($wp_mail_filters) {
    echo "⚠️ Filtros encontrados no wp_mail: " . $wp_mail_filters . "<br>";
} else {
    echo "✅ Nenhum filtro encontrado no wp_mail<br>";
}

// Verifica se há ações no phpmailer_init
$phpmailer_actions = has_action('phpmailer_init');
if ($phpmailer_actions) {
    echo "⚠️ Ações encontradas no phpmailer_init: " . $phpmailer_actions . "<br>";
} else {
    echo "✅ Nenhuma ação encontrada no phpmailer_init<br>";
}

// Verifica se há ações no wp_mail_failed
$wp_mail_failed_actions = has_action('wp_mail_failed');
if ($wp_mail_failed_actions) {
    echo "⚠️ Ações encontradas no wp_mail_failed: " . $wp_mail_failed_actions . "<br>";
} else {
    echo "✅ Nenhuma ação encontrada no wp_mail_failed<br>";
}
echo "</div>";

// 10. Teste final com send_test_notification
echo "<div class='test-section info'>";
echo "<h2>🎯 10. Teste Final com send_test_notification</h2>";

try {
    echo "Executando send_test_notification...<br>";
    $result = $notifications->send_test_notification(get_option('admin_email'));
    
    if ($result) {
        echo "✅ send_test_notification() retornou true<br>";
    } else {
        echo "❌ send_test_notification() retornou false<br>";
    }
} catch (Exception $e) {
    echo "❌ Exceção em send_test_notification: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "❌ Erro fatal em send_test_notification: " . $e->getMessage() . "<br>";
}
echo "</div>";

echo "<div class='back-link'><a href='../wp-admin/admin.php?page=movimento-livre'>← Voltar ao Admin</a></div>";
echo "<p><strong>Teste executado em:</strong> " . current_time('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>
