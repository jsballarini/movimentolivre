<?php
/**
 * Teste de Geradores de PDF - Movimento Livre
 * Script para verificar quais geradores de PDF estão disponíveis no servidor
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

// Habilita todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicia o teste
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Teste de Geradores de PDF - Movimento Livre</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        .code { background-color: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; }
        h1, h2, h3 { color: #333; }
        .back-link { margin: 20px 0; }
        .back-link a { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .status-available { background: #28a745; color: white; }
        .status-unavailable { background: #dc3545; color: white; }
        .status-partial { background: #ffc107; color: #212529; }
        .command-output { background: #000; color: #00ff00; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 11px; max-height: 200px; overflow-y: auto; }
        .pdf-lib-info { background: #e9ecef; padding: 10px; border-radius: 3px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<div class='back-link'><a href='../wp-admin/admin.php?page=movimento-livre'>← Voltar ao Admin</a></div>";
echo "<h1>📄 Teste de Geradores de PDF - Movimento Livre</h1>";

// 1. Informações do sistema
echo "<div class='test-section info'>";
echo "<h2>🖥️ 1. Informações do Sistema</h2>";
echo "<strong>Sistema Operacional:</strong> " . PHP_OS . "<br>";
echo "<strong>Versão do PHP:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Arquitetura:</strong> " . (PHP_INT_SIZE * 8) . " bits<br>";
echo "<strong>Servidor Web:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "<br>";
echo "<strong>Diretório de Trabalho:</strong> " . getcwd() . "<br>";
echo "</div>";

// 2. Verificação de extensões PHP
echo "<div class='test-section info'>";
echo "<h2>🔧 2. Extensões PHP Relacionadas a PDF</h2>";

$pdf_extensions = array(
    'gd' => 'GD Graphics Library (para imagens)',
    'imagick' => 'ImageMagick (para conversão de imagens)',
    'mbstring' => 'Multibyte String (para caracteres especiais)',
    'iconv' => 'Iconv (para conversão de caracteres)',
    'curl' => 'cURL (para downloads)',
    'openssl' => 'OpenSSL (para criptografia)',
    'zip' => 'ZIP (para arquivos compactados)',
    'fileinfo' => 'Fileinfo (para detecção de tipos de arquivo)'
);

foreach ($pdf_extensions as $ext => $description) {
    $status = extension_loaded($ext) ? 'available' : 'unavailable';
    $status_text = extension_loaded($ext) ? 'Disponível' : 'Não disponível';
    echo "<span class='status status-{$status}'>{$status_text}</span> <strong>{$ext}:</strong> {$description}<br>";
}
echo "</div>";

// 3. Verificação de bibliotecas de PDF
echo "<div class='test-section info'>";
echo "<h2>📚 3. Bibliotecas de PDF Disponíveis</h2>";

// TCPDF
if (class_exists('TCPDF')) {
    echo "<span class='status status-available'>Disponível</span> <strong>TCPDF:</strong> Biblioteca PHP para geração de PDFs<br>";
    echo "<div class='pdf-lib-info'>";
    echo "<strong>Versão:</strong> " . (defined('TCPDF_VERSION') ? TCPDF_VERSION : 'N/A') . "<br>";
    echo "<strong>Classe:</strong> " . get_class(new TCPDF()) . "<br>";
    echo "</div>";
} else {
    echo "<span class='status status-unavailable'>Não disponível</span> <strong>TCPDF:</strong> Biblioteca PHP para geração de PDFs<br>";
}

// FPDF
if (class_exists('FPDF')) {
    echo "<span class='status status-available'>Disponível</span> <strong>FPDF:</strong> Biblioteca PHP para geração de PDFs<br>";
    echo "<div class='pdf-lib-info'>";
    echo "<strong>Classe:</strong> " . get_class(new FPDF()) . "<br>";
    echo "</div>";
} else {
    echo "<span class='status status-unavailable'>Não disponível</span> <strong>FPDF:</strong> Biblioteca PHP para geração de PDFs<br>";
}

// mPDF
if (class_exists('Mpdf\Mpdf')) {
    echo "<span class='status status-available'>Disponível</span> <strong>mPDF:</strong> Biblioteca PHP para geração de PDFs<br>";
    echo "<div class='pdf-lib-info'>";
    echo "<strong>Classe:</strong> Mpdf\\Mpdf<br>";
    echo "</div>";
} else {
    echo "<span class='status status-unavailable'>Não disponível</span> <strong>mPDF:</strong> Biblioteca PHP para geração de PDFs<br>";
}

// Dompdf
if (class_exists('Dompdf\Dompdf')) {
    echo "<span class='status status-available'>Disponível</span> <strong>Dompdf:</strong> Biblioteca PHP para conversão HTML para PDF<br>";
    echo "<div class='pdf-lib-info'>";
    echo "<strong>Classe:</strong> Dompdf\\Dompdf<br>";
    echo "</div>";
} else {
    echo "<span class='status status-unavailable'>Não disponível</span> <strong>Dompdf:</strong> Biblioteca PHP para conversão HTML para PDF<br>";
}

// Tenta carregar autoloads comuns do Composer para disponibilizar Dompdf
$autoload_candidates = array(
    // vendor dentro do plugin
    __DIR__ . '/vendor/autoload.php',
    // vendor no wp-content
    WP_CONTENT_DIR . '/vendor/autoload.php',
    // vendor na raiz do WP
    ABSPATH . 'vendor/autoload.php',
);
$dompdf_autoload_used = '';
foreach ($autoload_candidates as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        $dompdf_autoload_used = $autoload;
        break;
    }
}

// wkhtmltopdf (via exec)
echo "<span class='status status-unavailable'>Não disponível</span> <strong>wkhtmltopdf:</strong> Conversor HTML para PDF (via linha de comando)<br>";
echo "</div>";

// 4. Verificação de comandos do sistema
echo "<div class='test-section info'>";
echo "<h2>💻 4. Comandos do Sistema para PDF</h2>";

$commands = array(
    'wkhtmltopdf' => 'Conversor HTML para PDF',
    'weasyprint' => 'Conversor HTML para PDF (Python)',
    'pandoc' => 'Conversor de documentos',
    'prince' => 'Conversor HTML para PDF (comercial)',
    'chrome' => 'Google Chrome (para conversão)',
    'firefox' => 'Mozilla Firefox (para conversão)',
    'phantomjs' => 'PhantomJS (para conversão)'
);

// Cross-platform: where (Windows) / which (Unix)
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$disable_functions = ini_get('disable_functions');
$exec_available = function_exists('exec') && (empty($disable_functions) || stripos($disable_functions, 'exec') === false);

if (! $exec_available) {
    echo "<div class='pdf-lib-info'><strong>Aviso:</strong> A função exec() está desabilitada neste servidor. Verificação de comandos do sistema foi ignorada (hospedagem compartilhada costuma bloquear).</div>";
    foreach ($commands as $command => $description) {
        echo "<span class='status status-unavailable'>Indisponível</span> <strong>{$command}:</strong> {$description} (exec desabilitado)<br>";
    }
} else {
    foreach ($commands as $command => $description) {
        $output = array();
        $return_var = 0;

        if ($is_windows) {
            @exec("where {$command} 2>nul", $output, $return_var);
        } else {
            @exec("which {$command} 2>/dev/null", $output, $return_var);
        }

        if ($return_var === 0 && !empty($output)) {
            $status = 'available';
            $status_text = 'Disponível';
            $path = $output[0];

            // Tenta obter a versão
            $version_output = array();
            if ($is_windows) {
                @exec("{$command} --version 2>nul", $version_output, $version_return);
            } else {
                @exec("{$command} --version 2>/dev/null", $version_output, $version_return);
            }
            $version = ($version_return === 0 && !empty($version_output)) ? $version_output[0] : 'N/A';

            echo "<span class='status status-{$status}'>{$status_text}</span> <strong>{$command}:</strong> {$description}<br>";
            echo "<div class='pdf-lib-info'>";
            echo "<strong>Caminho:</strong> {$path}<br>";
            echo "<strong>Versão:</strong> " . htmlspecialchars($version) . "<br>";
            echo "</div>";
        } else {
            echo "<span class='status status-unavailable'>Não disponível</span> <strong>{$command}:</strong> {$description}<br>";
        }
    }
}
echo "</div>";

// 5. Teste de permissões de diretório
echo "<div class='test-section info'>";
echo "<h2>📁 5. Permissões de Diretórios</h2>";

$upload_dir = wp_upload_dir();
$movliv_dir = $upload_dir['basedir'] . '/movliv/';

echo "<strong>Diretório de uploads:</strong> " . $upload_dir['basedir'] . "<br>";
echo "<strong>Diretório MovLiv:</strong> " . $movliv_dir . "<br>";

// Verifica se o diretório MovLiv existe
if (file_exists($movliv_dir)) {
    echo "<span class='status status-available'>Disponível</span> Diretório MovLiv existe<br>";
} else {
    echo "<span class='status status-unavailable'>Não existe</span> Diretório MovLiv não existe<br>";
}

// Verifica permissões
if (is_dir($upload_dir['basedir'])) {
    $upload_writable = is_writable($upload_dir['basedir']);
    $status = $upload_writable ? 'available' : 'unavailable';
    $status_text = $upload_writable ? 'Gravável' : 'Não gravável';
    echo "<span class='status status-{$status}'>{$status_text}</span> Diretório de uploads<br>";
}

if (is_dir($movliv_dir)) {
    $movliv_writable = is_writable($movliv_dir);
    $status = $movliv_writable ? 'available' : 'unavailable';
    $status_text = $movliv_writable ? 'Gravável' : 'Não gravável';
    echo "<span class='status status-{$status}'>{$status_text}</span> Diretório MovLiv<br>";
}
echo "</div>";

// 6. Teste de geração de PDF simples
echo "<div class='test-section info'>";
echo "<h2>🧪 6. Teste de Geração de PDF</h2>";

$test_pdf_path = $movliv_dir . 'test-pdf-generation.pdf';
$pdf_generated = false;
$error_message = '';

try {
    // Garante diretório de uploads/movliv criado
    if (!file_exists($movliv_dir)) {
        @wp_mkdir_p($movliv_dir);
    }

    // Tenta usar TCPDF se disponível
    if (class_exists('TCPDF')) {
        echo "Testando geração com TCPDF...<br>";
        
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Teste de Geração de PDF - Movimento Livre', 0, 1, 'C');
        $pdf->Cell(0, 10, 'Data/Hora: ' . current_time('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Cell(0, 10, 'Este é um teste de geração de PDF.', 0, 1, 'L');
        
        $pdf->Output($test_pdf_path, 'F');
        
        if (file_exists($test_pdf_path)) {
            $pdf_generated = true;
            echo "<span class='status status-available'>Sucesso</span> PDF gerado com TCPDF<br>";
            echo "<strong>Arquivo:</strong> " . $test_pdf_path . "<br>";
            echo "<strong>Tamanho:</strong> " . number_format(filesize($test_pdf_path)) . " bytes<br>";
        } else {
            $error_message = 'Falha ao gerar PDF com TCPDF';
        }
    }
    // Se TCPDF não funcionou, tenta FPDF
    elseif (class_exists('FPDF') && !$pdf_generated) {
        echo "Testando geração com FPDF...<br>";
        
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, 'Teste de Geração de PDF');
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 10, 'Data/Hora: ' . current_time('d/m/Y H:i:s'));
        
        $pdf->Output($test_pdf_path, 'F');
        
        if (file_exists($test_pdf_path)) {
            $pdf_generated = true;
            echo "<span class='status status-available'>Sucesso</span> PDF gerado com FPDF<br>";
            echo "<strong>Arquivo:</strong> " . $test_pdf_path . "<br>";
            echo "<strong>Tamanho:</strong> " . number_format(filesize($test_pdf_path)) . " bytes<br>";
        } else {
            $error_message = 'Falha ao gerar PDF com FPDF';
        }
    }
    // Se não funcionou ainda, tenta Dompdf se disponível
    elseif (class_exists('Dompdf\\Dompdf') && !$pdf_generated) {
        echo "Testando geração com Dompdf...<br>";

        // Prepara diretório temporário para Dompdf
        $tmp_dir = trailingslashit($movliv_dir) . 'tmp/';
        if (!file_exists($tmp_dir)) {
            @wp_mkdir_p($tmp_dir);
        }

        try {
            $options = new Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('tempDir', $tmp_dir);

            $dompdf = new Dompdf\Dompdf($options);

            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:DejaVu Sans, sans-serif;margin:24px} h1{color:#333} .box{border:1px solid #ccc;padding:12px;margin-top:12px}</style></head><body>'
                . '<h1>Teste Dompdf</h1>'
                . '<div class="box">Data/Hora: ' . current_time('d/m/Y H:i:s') . '</div>'
                . '<div class="box">Autoload: ' . htmlspecialchars($dompdf_autoload_used ?: 'não utilizado (já carregado)') . '</div>'
                . '<div class="box">TempDir gravável: ' . (is_writable($tmp_dir) ? 'sim' : 'não') . ' (' . $tmp_dir . ')</div>'
                . '</body></html>';

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();

            if (file_put_contents($test_pdf_path, $output)) {
                $pdf_generated = true;
                echo "<span class='status status-available'>Sucesso</span> PDF gerado com Dompdf<br>";
                echo "<strong>Arquivo:</strong> " . $test_pdf_path . "<br>";
                echo "<strong>Tamanho:</strong> " . number_format(filesize($test_pdf_path)) . " bytes<br>";
            } else {
                $error_message = 'Falha ao salvar o PDF gerado pelo Dompdf';
            }
        } catch (Exception $e) {
            $error_message = 'Exceção Dompdf: ' . $e->getMessage();
        }
    }
    // Se nenhuma biblioteca PHP funcionou, tenta comando do sistema
    elseif (!$pdf_generated) {
        echo "Testando geração com comandos do sistema...<br>";
        
        // Só tenta wkhtmltopdf se exec() estiver disponível
        if ($exec_available) {
            // Tenta wkhtmltopdf se disponível
            $test_html = $movliv_dir . 'test.html';
            $html_content = '<html><body><h1>Teste de Geração de PDF</h1><p>Data/Hora: ' . current_time('d/m/Y H:i:s') . '</p></body></html>';

            if (file_put_contents($test_html, $html_content)) {
                $output = array();
                if ($is_windows) {
                    @exec("wkhtmltopdf \"{$test_html}\" \"{$test_pdf_path}\" 2>nul", $output, $return_var);
                } else {
                    @exec("wkhtmltopdf {$test_html} {$test_pdf_path} 2>/dev/null", $output, $return_var);
                }

                if ($return_var === 0 && file_exists($test_pdf_path)) {
                    $pdf_generated = true;
                    echo "<span class='status status-available'>Sucesso</span> PDF gerado com wkhtmltopdf<br>";
                    echo "<strong>Arquivo:</strong> " . $test_pdf_path . "<br>";
                    echo "<strong>Tamanho:</strong> " . number_format(filesize($test_pdf_path)) . " bytes<br>";
                } else {
                    $error_message = 'Falha ao gerar PDF com wkhtmltopdf';
                }

                // Remove arquivo HTML de teste
                @unlink($test_html);
            }
        } else {
            echo "<span class='status status-unavailable'>Ignorado</span> Teste com wkhtmltopdf não pôde ser executado (exec desabilitado no servidor).<br>";
        }
    }
    
    if (!$pdf_generated) {
        echo "<span class='status status-unavailable'>Falha</span> Não foi possível gerar PDF<br>";
        if ($error_message) {
            echo "<strong>Erro:</strong> " . htmlspecialchars($error_message) . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<span class='status status-unavailable'>Erro</span> Exceção durante geração: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "<span class='status status-unavailable'>Erro Fatal</span> Erro durante geração: " . $e->getMessage() . "<br>";
}
echo "</div>";

// 7. Recomendações
echo "<div class='test-section warning'>";
echo "<h2>💡 7. Recomendações</h2>";

if (class_exists('TCPDF')) {
    echo "<strong>✅ Recomendado:</strong> TCPDF está disponível e é uma excelente opção para geração de PDFs<br>";
} elseif (class_exists('FPDF')) {
    echo "<strong>✅ Recomendado:</strong> FPDF está disponível e é uma boa opção para PDFs simples<br>";
} elseif (class_exists('Mpdf\Mpdf')) {
    echo "<strong>✅ Recomendado:</strong> mPDF está disponível e é excelente para conversão HTML para PDF<br>";
} elseif (class_exists('Dompdf\Dompdf')) {
    echo "<strong>⚠️ Alternativa:</strong> Dompdf está disponível, mas pode ter limitações com CSS complexo<br>";
} else {
    echo "<strong>❌ Crítico:</strong> Nenhuma biblioteca PHP de PDF está disponível<br>";
    echo "<strong>Sugestão:</strong> Instale TCPDF ou mPDF via Composer<br>";
}

if (!extension_loaded('gd') && !extension_loaded('imagick')) {
    echo "<strong>⚠️ Aviso:</strong> Extensões de imagem não disponíveis (GD ou Imagick)<br>";
}

if (!is_writable($upload_dir['basedir'])) {
    echo "<strong>❌ Crítico:</strong> Diretório de uploads não é gravável<br>";
    echo "<strong>Solução:</strong> Ajuste as permissões do diretório<br>";
}
echo "</div>";

// 8. Informações do plugin
echo "<div class='test-section info'>";
echo "<h2>🔌 8. Status do Plugin Movimento Livre</h2>";

if (class_exists('MOVLIV_PDF_Generator')) {
    echo "<span class='status status-available'>Disponível</span> Classe MOVLIV_PDF_Generator encontrada<br>";
    
    try {
        $pdf_generator = MOVLIV_PDF_Generator::getInstance();
        echo "<span class='status status-available'>Funcional</span> Instância criada com sucesso<br>";
        
        // Verifica métodos disponíveis
        $reflection = new ReflectionClass($pdf_generator);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        echo "<strong>Métodos públicos disponíveis:</strong><br>";
        foreach ($methods as $method) {
            echo "• " . $method->getName() . "()<br>";
        }
        
    } catch (Exception $e) {
        echo "<span class='status status-unavailable'>Erro</span> Falha ao criar instância: " . $e->getMessage() . "<br>";
    }
} else {
    echo "<span class='status status-unavailable'>Não disponível</span> Classe MOVLIV_PDF_Generator não encontrada<br>";
}
echo "</div>";

echo "<div class='back-link'><a href='../wp-admin/admin.php?page=movimento-livre'>← Voltar ao Admin</a></div>";
echo "<p><strong>Teste executado em:</strong> " . current_time('Y-m-d H:i:s') . "</p>";
echo "</div>";
echo "</body></html>";
?>
