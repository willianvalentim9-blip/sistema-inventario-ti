<?php
/**
 * Estatísticas Avançadas para Dashboard
 * Sistema de Estoque de TI
 */

require_once 'config.php';

/**
 * Obtém estatísticas gerais do sistema
 */
function getDashboardStats() {
    $pdo = getConnection();
    $stats = [];
    
    try {
        // Estatísticas de produtos
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
        $stats['total_products'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE quantity < min_quantity AND min_quantity > 0");
        $stats['low_stock_products'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE quantity = 0");
        $stats['out_of_stock_products'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT SUM(quantity * COALESCE(price, 0)) as total FROM products WHERE price IS NOT NULL");
        $stats['total_inventory_value'] = $stmt->fetch()['total'] ?? 0;
        
        // Estatísticas de máquinas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines");
        $stats['total_machines'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines WHERE status = 'ready'");
        $stats['ready_machines'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT SUM(quantity * COALESCE(sale_price, 0)) as total FROM ready_machines WHERE sale_price IS NOT NULL");
        $stats['total_machines_value'] = $stmt->fetch()['total'] ?? 0;
        
        // Estatísticas de usuários (apenas para admin)
        if (isAdmin()) {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
            $stats['total_users'] = $stmt->fetch()['total'];
        }
        
        // Atividade recente (últimos 30 dias)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['recent_products'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ready_machines WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['recent_machines'] = $stmt->fetch()['total'];
        
    } catch (PDOException $e) {
        error_log("Erro ao obter estatísticas: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Obtém produtos mais movimentados
 */
function getMostMovedProducts($limit = 10) {
    $pdo = getConnection();
    
    try {
        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.category,
                    COALESCE(SUM(po.quantity_out), 0) as total_moved
                FROM products p
                LEFT JOIN product_outputs po ON p.id = po.product_id
                WHERE po.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) OR po.created_at IS NULL
                GROUP BY p.id, p.name, p.category
                ORDER BY total_moved DESC
                LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erro ao obter produtos mais movimentados: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém estatísticas de saídas por período
 */
function getOutputStatsByPeriod($days = 30) {
    $pdo = getConnection();
    
    try {
        // Saídas de produtos por dia
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count,
                    SUM(quantity_out) as total_quantity
                FROM product_outputs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$days]);
        $product_outputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Saídas de máquinas por dia
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count,
                    SUM(quantity_out) as total_quantity
                FROM machine_outputs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$days]);
        $machine_outputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'product_outputs' => $product_outputs,
            'machine_outputs' => $machine_outputs
        ];
        
    } catch (PDOException $e) {
        error_log("Erro ao obter estatísticas de saídas: " . $e->getMessage());
        return ['product_outputs' => [], 'machine_outputs' => []];
    }
}

/**
 * Obtém distribuição de produtos por categoria
 */
function getProductsByCategory() {
    $pdo = getConnection();
    
    try {
        $sql = "SELECT 
                    category,
                    COUNT(*) as count,
                    SUM(quantity) as total_quantity,
                    AVG(COALESCE(price, 0)) as avg_price
                FROM products 
                WHERE category IS NOT NULL AND category != ''
                GROUP BY category
                ORDER BY count DESC";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erro ao obter produtos por categoria: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém distribuição de máquinas por status
 */
function getMachinesByStatus() {
    $pdo = getConnection();
    
    try {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(quantity) as total_quantity
                FROM ready_machines 
                GROUP BY status
                ORDER BY count DESC";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erro ao obter máquinas por status: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém alertas do sistema
 */
function getSystemAlerts() {
    $pdo = getConnection();
    $alerts = [];
    
    try {
        // Produtos com estoque baixo
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE quantity <= min_quantity AND min_quantity > 0");
        $low_stock_count = $stmt->fetch()['count'];
        if ($low_stock_count > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fas fa-exclamation-triangle',
                'title' => 'Estoque Baixo',
                'message' => "{$low_stock_count} produto(s) com estoque baixo",
                'action' => 'products.php?status=low_stock'
            ];
        }
        
        // Produtos sem estoque
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE quantity = 0");
        $out_stock_count = $stmt->fetch()['count'];
        if ($out_stock_count > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fas fa-times-circle',
                'title' => 'Sem Estoque',
                'message' => "{$out_stock_count} produto(s) sem estoque",
                'action' => 'products.php?status=out_of_stock'
            ];
        }
        
        // Máquinas em manutenção
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM ready_machines WHERE status = 'maintenance'");
        $maintenance_count = $stmt->fetch()['count'];
        if ($maintenance_count > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'fas fa-wrench',
                'title' => 'Manutenção',
                'message' => "{$maintenance_count} máquina(s) em manutenção",
                'action' => 'ready_machines.php?status=maintenance'
            ];
        }
        
        // Atividade recente alta
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_outputs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $recent_outputs = $stmt->fetch()['count'];
        if ($recent_outputs > 10) {
            $alerts[] = [
                'type' => 'success',
                'icon' => 'fas fa-chart-line',
                'title' => 'Alta Atividade',
                'message' => "{$recent_outputs} saída(s) nas últimas 24h",
                'action' => 'product_outputs_log.php'
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Erro ao obter alertas: " . $e->getMessage());
    }
    
    return $alerts;
}

/**
 * Obtém dados para gráfico de linha temporal
 */
function getTimelineData($days = 30) {
    $pdo = getConnection();
    
    try {
        $dates = [];
        $products_data = [];
        $machines_data = [];
        
        // Gerar array de datas
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = $date;
            $products_data[$date] = 0;
            $machines_data[$date] = 0;
        }
        
        // Buscar dados de produtos
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM products 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$days]);
        $product_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($product_results as $row) {
            if (isset($products_data[$row['date']])) {
                $products_data[$row['date']] = (int)$row['count'];
            }
        }
        
        // Buscar dados de máquinas
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM ready_machines 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$days]);
        $machine_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($machine_results as $row) {
            if (isset($machines_data[$row['date']])) {
                $machines_data[$row['date']] = (int)$row['count'];
            }
        }
        
        return [
            'dates' => $dates,
            'products' => array_values($products_data),
            'machines' => array_values($machines_data)
        ];
        
    } catch (PDOException $e) {
        error_log("Erro ao obter dados de timeline: " . $e->getMessage());
        return ['dates' => [], 'products' => [], 'machines' => []];
    }
}
?>

