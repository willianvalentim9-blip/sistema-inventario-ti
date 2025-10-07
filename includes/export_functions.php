<?php
/**
 * Funções de Exportação de Dados
 * Sistema de Estoque de TI
 * 
 * Este arquivo contém funções para exportar dados em diferentes formatos
 */

require_once __DIR__ . '/../config.php';

/**
 * Exporta dados para CSV
 * 
 * @param array $data Dados para exportar
 * @param array $headers Cabeçalhos das colunas
 * @param string $filename Nome do arquivo
 */
function exportToCSV($data, $headers, $filename) {
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
    
    $output = fopen("php://output", "w");
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalhos
    fputcsv($output, array_values($headers), ";");
    
    // Dados
    foreach ($data as $row) {
        $csvRow = [];
        foreach ($headers as $key => $header) {
            $value = $row[$key] ?? "";
            
            // Formatação especial para alguns campos
            if (in_array($key, ["price", "sale_price"]) && is_numeric($value)) {
                $value = "R$ " . number_format($value, 2, ",", ".");
            } elseif (in_array($key, ["created_at", "updated_at"]) && !empty($value)) {
                $value = date("d/m/Y H:i", strtotime($value));
            } elseif (is_bool($value)) {
                $value = $value ? "Sim" : "Não";
            }
            
            $csvRow[] = $value;
        }
        fputcsv($output, $csvRow, ";");
    }
    
    fclose($output);
    exit;
}

/**
 * Exporta produtos para CSV
 */
function exportProductsToCSV($filters = []) {
    $pdo = getConnection();
    
    $where_conditions = [];
    $params = [];
    
    // Aplicar filtros
    if (!empty($filters['search'])) {
        $where_conditions[] = "(name LIKE ? OR model LIKE ? OR manufacturer LIKE ? OR description LIKE ?)";
        $search_param = "%{$filters['search']}%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    if (!empty($filters['category'])) {
        $where_conditions[] = "category = ?";
        $params[] = $filters['category'];
    }
    if (!empty($filters['status'])) {
        $where_conditions[] = "status = ?";
        $params[] = $filters['status'];
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT * FROM products $where_clause ORDER BY name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $headers = [
        'name' => 'Nome',
        'description' => 'Descrição',
        'category' => 'Categoria',
        'manufacturer' => 'Fabricante',
        'model' => 'Modelo',
        'serial_number' => 'Número de Série',
        'barcode' => 'Código de Barras',
        'qr_code' => 'Código QR',
        'quantity' => 'Quantidade',
        'min_quantity' => 'Quantidade Mínima',
        'max_quantity' => 'Quantidade Máxima',
        'price' => 'Preço',
        'location' => 'Localização',
        'status' => 'Status',
        'created_at' => 'Data de Criação',
        'updated_at' => 'Última Atualização'
    ];
    
    $filename = 'produtos_' . date('Y-m-d_H-i-s') . '.csv';
    exportToCSV($products, $headers, $filename);
}

/**
 * Exporta máquinas para CSV
 */
function exportMachinesToCSV($filters = []) {
    $pdo = getConnection();
    
    $where_conditions = [];
    $params = [];
    
    // Aplicar filtros
    if (!empty($filters['search'])) {
        $where_conditions[] = "(name LIKE ? OR processor LIKE ? OR description LIKE ? OR specifications LIKE ?)";
        $search_param = "%{$filters['search']}%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    if (!empty($filters['status'])) {
        $where_conditions[] = "status = ?";
        $params[] = $filters['status'];
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT * FROM ready_machines $where_clause ORDER BY name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $headers = [
        'name' => 'Nome',
        'description' => 'Descrição',
        'type' => 'Tipo',
        'processor' => 'Processador',
        'ram' => 'Memória RAM',
        'storage' => 'Armazenamento',
        'graphics_card' => 'Placa de Vídeo',
        'specifications' => 'Especificações',
        'serial_number' => 'Número de Série',
        'barcode' => 'Código de Barras',
        'qr_code' => 'Código QR',
        'quantity' => 'Quantidade',
        'sale_price' => 'Preço de Venda',
        'windows_10_compatible' => 'Windows 10 Compatível',
        'windows_11_compatible' => 'Windows 11 Compatível',
        'status' => 'Status',
        'created_at' => 'Data de Criação',
        'updated_at' => 'Última Atualização'
    ];
    
    $filename = 'maquinas_' . date('Y-m-d_H-i-s') . '.csv';
    exportToCSV($machines, $headers, $filename);
}

/**
 * Exporta histórico de saídas de produtos para CSV
 */
function exportProductOutputsToCSV($filters = []) {
    $pdo = getConnection();
    
    $where_conditions = [];
    $params = [];
    
    // Aplicar filtros de data se fornecidos
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(po.created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(po.created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT 
                po.id,
                p.name as product_name,
                p.category,
                p.manufacturer,
                p.model,
                po.quantity_out,
                po.reason,
                po.notes,
                po.created_at,
                u.username as user_name
            FROM product_outputs po
            JOIN products p ON po.product_id = p.id
            LEFT JOIN users u ON po.user_id = u.id
            $where_clause
            ORDER BY po.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $outputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $headers = [
        'id' => 'ID',
        'product_name' => 'Produto',
        'category' => 'Categoria',
        'manufacturer' => 'Fabricante',
        'model' => 'Modelo',
        'quantity_out' => 'Quantidade Saída',
        'reason' => 'Motivo',
        'notes' => 'Observações',
        'created_at' => 'Data/Hora',
        'user_name' => 'Usuário'
    ];
    
    $filename = 'saidas_produtos_' . date('Y-m-d_H-i-s') . '.csv';
    exportToCSV($outputs, $headers, $filename);
}

/**
 * Exporta histórico de saídas de máquinas para CSV
 */
function exportMachineOutputsToCSV($filters = []) {
    $pdo = getConnection();
    
    $where_conditions = [];
    $params = [];
    
    // Aplicar filtros de data se fornecidos
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(mo.created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(mo.created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT 
                mo.id,
                m.name as machine_name,
                m.type,
                m.processor,
                m.ram,
                m.storage,
                mo.quantity_out,
                mo.reason,
                mo.notes,
                mo.created_at,
                u.username as user_name
            FROM machine_outputs mo
            JOIN ready_machines m ON mo.machine_id = m.id
            LEFT JOIN users u ON mo.user_id = u.id
            $where_clause
            ORDER BY mo.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $outputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $headers = [
        'id' => 'ID',
        'machine_name' => 'Máquina',
        'type' => 'Tipo',
        'processor' => 'Processador',
        'ram' => 'RAM',
        'storage' => 'Armazenamento',
        'quantity_out' => 'Quantidade Saída',
        'reason' => 'Motivo',
        'notes' => 'Observações',
        'created_at' => 'Data/Hora',
        'user_name' => 'Usuário'
    ];
    
    $filename = 'saidas_maquinas_' . date('Y-m-d_H-i-s') . '.csv';
    exportToCSV($outputs, $headers, $filename);
}

/**
 * Gera relatório PDF usando HTML/CSS
 */
function generatePDFReport($title, $data, $template = 'default') {
    require_once 'vendor/autoload.php'; // Assumindo que há uma biblioteca PDF instalada
    
    // Esta função seria implementada com uma biblioteca como TCPDF ou mPDF
    // Por enquanto, retorna um erro informativo
    throw new Exception('Funcionalidade de PDF não implementada. Use exportação CSV.');
}

/**
 * Exporta dados baseado no tipo e filtros
 */
function handleExport() {
    if (!isset($_GET['type']) || !isset($_GET['format'])) {
        http_response_code(400);
        die('Parâmetros inválidos para exportação.');
    }
    
    $type = $_GET['type'];
    $format = $_GET['format'];
    $filters = $_GET;
    
    try {
        switch ($type) {
            case 'products':
                if ($format === 'csv') {
                    exportProductsToCSV($filters);
                }
                break;
                
            case 'machines':
                if ($format === 'csv') {
                    exportMachinesToCSV($filters);
                }
                break;
                
            case 'product_outputs':
                if ($format === 'csv') {
                    exportProductOutputsToCSV($filters);
                }
                break;
                
            case 'machine_outputs':
                if ($format === 'csv') {
                    exportMachineOutputsToCSV($filters);
                }
                break;
                
            default:
                throw new Exception('Tipo de exportação não suportado.');
        }
    } catch (Exception $e) {
        http_response_code(500);
        die('Erro na exportação: ' . $e->getMessage());
    }
}

// Se este arquivo for chamado diretamente, processar exportação
if (basename($_SERVER['PHP_SELF']) === 'export_functions.php') {
    requireLogin();
    handleExport();
}
?>

