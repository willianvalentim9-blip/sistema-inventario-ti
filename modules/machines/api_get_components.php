<?php
/**
 * ========================================
 * API: Buscar Componentes com Filtros
 * ========================================
 * Arquivo: modules/machines/api_get_components.php
 * Retorna componentes de uma categoria com filtros aplicados
 * e compatibilidade com seleções anteriores
 */

require_once '../../config.php';
require_once '../../includes/machine_components_functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $category = trim($input['category'] ?? '');
    $filters = $input['filters'] ?? [];
    $selectedComponents = $input['selectedComponents'] ?? [];
    
    if (empty($category)) {
        throw new Exception('Categoria não especificada');
    }
    
    $pdo = getConnection();
    
    // 1. Buscar ID da categoria
    $stmt = $pdo->prepare("SELECT id FROM component_categories WHERE slug = ?");
    $stmt->execute([$category]);
    $categoryRow = $stmt->fetch();
    
    if (!$categoryRow) {
        throw new Exception('Categoria não encontrada');
    }
    
    $categoryId = $categoryRow['id'];
    
    // 2. Construir query dinâmica
    $query = "
        SELECT 
            p.id,
            p.name,
            p.description,
            p.price,
            p.image_path,
            p.manufacturer,
            p.model,
            cs.*,
            cc.name as category_name
        FROM products p
        INNER JOIN component_specs cs ON p.id = cs.product_id
        INNER JOIN component_categories cc ON cs.component_category_id = cc.id
        WHERE cs.component_category_id = ?
        AND p.is_deleted = FALSE
    ";
    
    $params = [$categoryId];
    
    // 3. Aplicar filtros
    if (!empty($filters['manufacturer'])) {
        $query .= " AND p.manufacturer = ?";
        $params[] = $filters['manufacturer'];
    }
    
    if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
        $query .= " AND p.price >= ?";
        $params[] = (float)$filters['min_price'];
    }
    
    if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
        $query .= " AND p.price <= ?";
        $params[] = (float)$filters['max_price'];
    }
    
    // Filtros específicos por categoria
    switch ($category) {
        case 'cpu':
            if (!empty($filters['socket'])) {
                $query .= " AND cs.cpu_socket = ?";
                $params[] = $filters['socket'];
            }
            if (!empty($filters['min_cores']) && is_numeric($filters['min_cores'])) {
                $query .= " AND cs.cpu_cores >= ?";
                $params[] = (int)$filters['min_cores'];
            }
            $query .= " ORDER BY cs.cpu_boost_clock DESC, p.price ASC";
            break;
            
        case 'motherboard':
            if (!empty($filters['socket'])) {
                $query .= " AND cs.motherboard_socket = ?";
                $params[] = $filters['socket'];
            }
            if (!empty($filters['chipset'])) {
                $query .= " AND cs.motherboard_chipset = ?";
                $params[] = $filters['chipset'];
            }
            if (!empty($filters['form_factor'])) {
                $query .= " AND cs.motherboard_form_factor = ?";
                $params[] = $filters['form_factor'];
            }
            $query .= " ORDER BY p.price ASC";
            break;
            
        case 'ram':
            if (!empty($filters['type'])) {
                $query .= " AND cs.ram_type = ?";
                $params[] = $filters['type'];
            }
            if (!empty($filters['min_capacity']) && is_numeric($filters['min_capacity'])) {
                $query .= " AND cs.ram_capacity >= ?";
                $params[] = (int)$filters['min_capacity'];
            }
            $query .= " ORDER BY cs.ram_speed DESC, cs.ram_capacity ASC, p.price ASC";
            break;
            
        case 'gpu':
            if (!empty($filters['min_vram']) && is_numeric($filters['min_vram'])) {
                $query .= " AND cs.gpu_vram >= ?";
                $params[] = (int)$filters['min_vram'];
            }
            $query .= " ORDER BY cs.gpu_vram DESC, p.price ASC";
            break;
            
        case 'storage':
            if (!empty($filters['type'])) {
                $query .= " AND cs.storage_type = ?";
                $params[] = $filters['type'];
            }
            if (!empty($filters['min_capacity']) && is_numeric($filters['min_capacity'])) {
                $query .= " AND cs.storage_capacity >= ?";
                $params[] = (int)$filters['min_capacity'];
            }
            $query .= " ORDER BY cs.storage_read_speed DESC, p.price ASC";
            break;
            
        case 'psu':
            if (!empty($filters['min_wattage']) && is_numeric($filters['min_wattage'])) {
                $query .= " AND cs.psu_wattage >= ?";
                $params[] = (int)$filters['min_wattage'];
            }
            $query .= " ORDER BY cs.psu_wattage ASC, p.price ASC";
            break;
            
        case 'cooler':
            if (!empty($filters['type'])) {
                $query .= " AND cs.cooler_type = ?";
                $params[] = $filters['type'];
            }
            $query .= " ORDER BY cs.cooler_tdp_capability DESC, p.price ASC";
            break;
            
        default:
            $query .= " ORDER BY p.name ASC";
    }
    
    $query .= " LIMIT 50";
    
    // 4. Executar query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $components = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Validar compatibilidade com seleções anteriores
    $compatibility = [];
    if (!empty($selectedComponents)) {
        foreach ($components as &$component) {
            $compat = validateComponentCompatibility(
                $component['id'],
                $selectedComponents,
                $pdo
            );
            
            $component['compatibility'] = $compat['status'];
            $component['compatibility_message'] = $compat['message'];
            $component['compatibility_warnings'] = $compat['warnings'];
            $component['is_recommended'] = $compat['is_recommended'];
        }
    }
    
    // 6. Retornar resultado
    echo json_encode([
        'success' => true,
        'count' => count($components),
        'category' => $category,
        'components' => $components
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
