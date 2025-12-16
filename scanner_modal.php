<?php
/**
 * REDIRETOR AUTOMÁTICO: scanner_modal.php
 * Este arquivo redireciona para a nova localização em modules/barcode/
 */

// Obter parâmetros da query string
$query_string = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';

// Obter protocolo e host
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['REQUEST_URI']);

// Redirecionar para o novo local (URL completa)
$redirect_url = $protocol . $host . $path . '/modules/barcode/scanner_modal.php' . $query_string;
header('Location: ' . $redirect_url, true, 301);
exit;
