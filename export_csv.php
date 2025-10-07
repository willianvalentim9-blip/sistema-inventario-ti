<?php
// ========================================
// EXPORTADOR DE DADOS CSV
// ========================================
// Esta página permite exportar diferentes tipos de dados em formato CSV

// Inclui o arquivo de configuração
require_once 'config.php';

// Verifica se o usuário está logado
requireLogin();

// Obtém o tipo de exportação
$export_type = $_GET['type'] ?? '';
$filename = '';
$headers = [];
$data = [];

try {
    $pdo = getConnection();
    
    switch ($export_type) {
        case 'products':
            $filename = 'produtos_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'ID', 'Nome', 'Categoria', 'Fabricante', 'Modelo', 'Número de Série',
                'Código de Barras', 'Quantidade', 'Qtd Mínima', 'Qtd Máxima', 'Preço',
                'Localização', 'Status', 'Criado em', 'Atualizado em'
            ];
            
            $stmt = $pdo->query("
                SELECT 
                    id, name, category, manufacturer, model, serial_number,
                    barcode, quantity, min_quantity, max_quantity, price,
                    location, status, created_at, updated_at
                FROM products 
                ORDER BY name
            ");
            
            while ($row = $stmt->fetch()) {
                $data[] = [
                    $row['id'],
                    $row['name'],
                    $row['category'],
                    $row['manufacturer'] ?? '',
                    $row['model'] ?? '',
                    $row['serial_number'] ?? '',
                    $row['barcode'] ?? '',
                    $row['quantity'],
                    $row['min_quantity'] ?? 0,
                    $row['max_quantity'] ?? 0,
                    $row['price'] ? 'R$ ' . number_format($row['price'], 2, ',', '.') : '',
                    $row['location'] ?? '',
                    $row['status'],
                    date('d/m/Y H:i', strtotime($row['created_at'])),
                    date('d/m/Y H:i', strtotime($row['updated_at']))
                ];
            }
            break;
            
        case 'products_by_category':
            $filename = 'produtos_por_categoria_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Categoria', 'Quantidade de Produtos', 'Estoque Total', 'Valor Total'
            ];
            
            $stmt = $pdo->query("
                SELECT 
                    category,
                    COUNT(*) as product_count,
                    SUM(quantity) as total_stock,
                    SUM(quantity * COALESCE(price, 0)) as total_value
                FROM products 
                GROUP BY category
                ORDER BY category
            ");
            
            while ($row = $stmt->fetch()) {
                $data[] = [
                    $row['category'],
                    $row['product_count'],
                    $row['total_stock'],
                    'R$ ' . number_format($row['total_value'], 2, ',', '.')
                ];
            }
            break;
            
        case 'movements':
            $filename = 'movimentacoes_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'ID', 'Produto', 'Tipo', 'Quantidade', 'Estoque Anterior',
                'Estoque Atual', 'Motivo', 'Usuário', 'Data/Hora'
            ];
            
            // Aplica filtros se fornecidos
            $where_conditions = [];
            $params = [];
            
            if (!empty($_GET['product'])) {
                $where_conditions[] = "p.name LIKE ?";
                $params[] = "%{$_GET['product']}%";
            }
            
            if (!empty($_GET['type'])) {
                $where_conditions[] = "pm.movement_type = ?";
                $params[] = $_GET['type'];
            }
            
            if (!empty($_GET['date_from'])) {
                $where_conditions[] = "DATE(pm.created_at) >= ?";
                $params[] = $_GET['date_from'];
            }
            
            if (!empty($_GET['date_to'])) {
                $where_conditions[] = "DATE(pm.created_at) <= ?";
                $params[] = $_GET['date_to'];
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "
                SELECT 
                    pm.id,
                    p.name as product_name,
                    pm.movement_type,
                    pm.quantity,
                    pm.previous_quantity,
                    pm.new_quantity,
                    pm.reason,
                    u.username,
                    pm.created_at
                FROM product_movements pm
                LEFT JOIN products p ON pm.product_id = p.id
                LEFT JOIN users u ON pm.user_id = u.id
                {$where_clause}
                ORDER BY pm.created_at DESC
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            while ($row = $stmt->fetch()) {
                $type_labels = [
                    'entrada' => 'Entrada',
                    'saida' => 'Saída',
                    'ajuste' => 'Ajuste'
                ];
                
                $data[] = [
                    $row['id'],
                    $row['product_name'],
                    $type_labels[$row['movement_type']] ?? $row['movement_type'],
                    $row['quantity'],
                    $row['previous_quantity'],
                    $row['new_quantity'],
                    $row['reason'] ?? '',
                    $row['username'] ?? 'Sistema',
                    date('d/m/Y H:i:s', strtotime($row['created_at']))
                ];
            }
            break;
            
        case 'stock_summary':
            $filename = 'resumo_estoque_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Produto', 'Categoria', 'Estoque Atual', 'Estoque Mínimo',
                'Estoque Máximo', 'Status do Estoque', 'Valor Unitário', 'Valor Total'
            ];
            
            $stmt = $pdo->query("
                SELECT 
                    name, category, quantity, min_quantity, max_quantity, price
                FROM products 
                ORDER BY category, name
            ");
            
            while ($row = $stmt->fetch()) {
                $stock_status = 'Normal';
                if ($row['quantity'] <= $row['min_quantity']) {
                    $stock_status = 'Baixo';
                } elseif ($row['quantity'] >= $row['max_quantity']) {
                    $stock_status = 'Alto';
                }
                
                $unit_price = $row['price'] ? floatval($row['price']) : 0;
                $total_value = $unit_price * $row['quantity'];
                
                $data[] = [
                    $row['name'],
                    $row['category'],
                    $row['quantity'],
                    $row['min_quantity'] ?? 0,
                    $row['max_quantity'] ?? 0,
                    $stock_status,
                    $unit_price ? 'R$ ' . number_format($unit_price, 2, ',', '.') : '',
                    'R$ ' . number_format($total_value, 2, ',', '.')
                ];
            }
            break;
            
        case 'low_stock':
            $filename = 'estoque_baixo_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Produto', 'Categoria', 'Estoque Atual', 'Estoque Mínimo',
                'Diferença', 'Localização', 'Status'
            ];
            
            $stmt = $pdo->query("
                SELECT 
                    name, category, quantity, min_quantity, location, status
                FROM products 
                WHERE quantity <= min_quantity
                ORDER BY (quantity - min_quantity), name
            ");
            
            while ($row = $stmt->fetch()) {
                $difference = $row['quantity'] - $row['min_quantity'];
                
                $data[] = [
                    $row['name'],
                    $row['category'],
                    $row['quantity'],
                    $row['min_quantity'],
                    $difference,
                    $row['location'] ?? '',
                    $row['status']
                ];
            }
            break;
            
        default:
            throw new Exception('Tipo de exportação inválido.');
    }
    
    // Registra a ação no log de administrador
    if (isAdmin()) {
        $log_stmt = $pdo->prepare("
            INSERT INTO admin_logs (user_id, action, table_name, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $log_stmt->execute([
            $_SESSION['user_id'],
            'EXPORT',
            $export_type,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    die('Erro ao gerar exportação: ' . $e->getMessage());
}

// Se não há dados, retorna erro
if (empty($data)) {
    http_response_code(404);
    die('Nenhum dado encontrado para exportação.');
}

// Define headers para download do CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Abre output para escrita
$output = fopen('php://output', 'w');

// Adiciona BOM para UTF-8 (para Excel reconhecer acentos)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Escreve cabeçalhos
fputcsv($output, $headers, ';');

// Escreve dados
foreach ($data as $row) {
    fputcsv($output, $row, ';');
}

// Fecha output
fclose($output);
exit;
?>

