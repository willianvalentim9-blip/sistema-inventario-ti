<?php
/**
 * TESTE DE BACKUP SQL
 * 
 * Este arquivo testa se o backup SQL está funcionando corretamente
 * Acesso: http://localhost/sistema4/test_backup.php
 */

require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Cria diretório de backups
    $backup_dir = __DIR__ . '/backups';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = $backup_dir . '/backup_' . $timestamp . '.sql';
    
    // Obtém lista de tabelas
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    // Cria conteúdo do backup
    $sql_content = "-- Backup do banco de dados: " . DB_NAME . "\n";
    $sql_content .= "-- Data: " . date('Y-m-d H:i:s') . "\n";
    $sql_content .= "-- Host: " . DB_HOST . "\n";
    $sql_content .= "-- Tabelas: " . count($tables) . "\n\n";
    $sql_content .= "SET NAMES utf8mb4;\n";
    $sql_content .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    // Para cada tabela, faz dump
    foreach ($tables as $table) {
        $sql_content .= "\n-- =====================================\n";
        $sql_content .= "-- Estrutura da tabela: $table\n";
        $sql_content .= "-- =====================================\n";
        $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Get CREATE TABLE
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $create_table = $stmt->fetch(PDO::FETCH_NUM)[1];
        $sql_content .= $create_table . ";\n";
        
        // Get dados
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $sql_content .= "\n-- Dados da tabela: $table\n";
            $sql_content .= "INSERT INTO `$table` VALUES\n";
            $values = [];
            foreach ($rows as $row) {
                $row_values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $row_values[] = 'NULL';
                    } else {
                        $row_values[] = $pdo->quote($value);
                    }
                }
                $values[] = '(' . implode(',', $row_values) . ')';
            }
            $sql_content .= implode(",\n", $values) . ";\n";
        }
    }
    
    $sql_content .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
    $sql_content .= "\n-- Fim do backup\n";
    
    // Salva arquivo
    $bytes_written = file_put_contents($backup_file, $sql_content);
    
    if ($bytes_written === false) {
        throw new Exception('Erro ao salvar arquivo de backup');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup realizado com sucesso!',
        'file' => basename($backup_file),
        'size' => formatBytes(filesize($backup_file)),
        'tables' => count($tables),
        'timestamp' => $timestamp
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao fazer backup: ' . $e->getMessage()
    ]);
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
