<?php
/**
 * ========================================
 * API: Validar Compatibilidade
 * ========================================
 * Arquivo: modules/machines/api_validate_compatibility.php
 * Valida compatibilidade entre componentes selecionados
 */

require_once '../../config.php';
require_once '../../includes/machine_components_functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $selectedComponents = $input['selectedComponents'] ?? [];
    
    if (empty($selectedComponents)) {
        throw new Exception('Nenhum componente selecionado');
    }
    
    $pdo = getConnection();
    $issues = [];
    $warnings = [];
    $totalPrice = 0;
    $totalTDP = 0;
    
    // 1. Validar regras de compatibilidade conhecidas
    $compatibilityRules = [
        'cpu-motherboard' => [
            'field1' => 'cpu_socket',
            'field2' => 'motherboard_socket',
            'type' => 'socket',
            'critical' => true
        ],
        'ram-motherboard' => [
            'field1' => 'ram_type',
            'field2' => 'motherboard_ram_type',
            'type' => 'ram_type',
            'critical' => true
        ],
        'cooler-cpu' => [
            'field1' => 'cooler_socket_compatibility',
            'field2' => 'cpu_socket',
            'type' => 'socket_array',
            'critical' => true
        ],
        'gpu-psu' => [
            'field1' => 'gpu_power_requirement',
            'field2' => 'psu_wattage',
            'type' => 'power',
            'critical' => true
        ],
        'gpu-motherboard' => [
            'field1' => 'gpu_pcie_gen',
            'field2' => 'motherboard_pcie_gen',
            'type' => 'pcie',
            'critical' => false
        ]
    ];
    
    // 2. Buscar especificações de todos os componentes
    $componentSpecs = [];
    foreach ($selectedComponents as $category => $productId) {
        $stmt = $pdo->prepare("
            SELECT cs.*, cc.name as category_name, p.price, p.name
            FROM component_specs cs
            INNER JOIN component_categories cc ON cs.component_category_id = cc.id
            INNER JOIN products p ON cs.product_id = p.id
            WHERE cs.product_id = ?
        ");
        $stmt->execute([$productId]);
        $specs = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($specs) {
            $componentSpecs[$category] = $specs;
            $totalPrice += (float)$specs['price'];
            
            // Somar TDP relevante
            if (!empty($specs['cpu_tdp'])) {
                $totalTDP += (int)$specs['cpu_tdp'];
            }
            if (!empty($specs['gpu_power_requirement'])) {
                $totalTDP += (int)$specs['gpu_power_requirement'];
            }
        }
    }
    
    // 3. Validar compatibilidades
    foreach ($compatibilityRules as $rule => $config) {
        list($comp1, $comp2) = explode('-', $rule);
        
        if (!isset($componentSpecs[$comp1]) || !isset($componentSpecs[$comp2])) {
            continue; // Ambos componentes não foram selecionados
        }
        
        $spec1 = $componentSpecs[$comp1];
        $spec2 = $componentSpecs[$comp2];
        
        switch ($config['type']) {
            case 'socket':
                $value1 = $spec1[$config['field1']] ?? null;
                $value2 = $spec2[$config['field2']] ?? null;
                
                if ($value1 && $value2 && $value1 !== $value2) {
                    $issue = [
                        'severity' => $config['critical'] ? 'danger' : 'warning',
                        'icon' => 'times-circle',
                        'title' => 'Incompatibilidade de Socket',
                        'message' => "CPU ({$value1}) incompatível com Placa Mãe ({$value2})",
                        'component1' => $spec1['name'],
                        'component2' => $spec2['name']
                    ];
                    
                    if ($config['critical']) {
                        $issues[] = $issue;
                    } else {
                        $warnings[] = $issue;
                    }
                }
                break;
                
            case 'ram_type':
                $value1 = $spec1[$config['field1']] ?? null;
                $value2 = $spec2[$config['field2']] ?? null;
                
                if ($value1 && $value2 && $value1 !== $value2) {
                    $issues[] = [
                        'severity' => 'danger',
                        'icon' => 'times-circle',
                        'title' => 'Tipo de RAM Incompatível',
                        'message' => "RAM ({$value1}) não é compatível com Placa Mãe ({$value2})",
                        'component1' => $spec1['name'],
                        'component2' => $spec2['name']
                    ];
                }
                break;
                
            case 'socket_array':
                $sockets = json_decode($spec1[$config['field1']], true);
                $cpuSocket = $spec2[$config['field2']];
                
                if (!in_array($cpuSocket, (array)$sockets)) {
                    $issues[] = [
                        'severity' => 'danger',
                        'icon' => 'times-circle',
                        'title' => 'Cooler Incompatível',
                        'message' => "Cooler não suporta socket {$cpuSocket}",
                        'component1' => $spec1['name'],
                        'component2' => $spec2['name']
                    ];
                }
                break;
                
            case 'power':
                $requiredPower = (int)$spec1[$config['field1']] ?? 0;
                $psuWattage = (int)$spec2[$config['field2']] ?? 0;
                
                // Recomenda 1.5x de margem
                $recommendedPSU = $requiredPower * 1.5;
                
                if ($psuWattage < $requiredPower) {
                    $issues[] = [
                        'severity' => 'danger',
                        'icon' => 'exclamation-triangle',
                        'title' => 'Fonte Insuficiente',
                        'message' => "Fonte de {$psuWattage}W é insuficiente. GPU requer {$requiredPower}W",
                        'component1' => $spec1['name'],
                        'component2' => $spec2['name']
                    ];
                } elseif ($psuWattage < $recommendedPSU) {
                    $warnings[] = [
                        'severity' => 'warning',
                        'icon' => 'lightbulb',
                        'title' => 'Margem de Potência Baixa',
                        'message' => "Recomenda-se fonte de pelo menos {$recommendedPSU}W",
                        'component1' => $spec1['name'],
                        'component2' => $spec2['name']
                    ];
                }
                break;
                
            case 'pcie':
                // Apenas aviso se houver diferença muito grande
                $gen1 = (int)substr($spec1[$config['field1']] ?? '0', 5, 1);
                $gen2 = (int)substr($spec2[$config['field2']] ?? '0', 5, 1);
                
                if ($gen1 > 0 && $gen2 > 0 && $gen1 > ($gen2 + 1)) {
                    $warnings[] = [
                        'severity' => 'info',
                        'icon' => 'info-circle',
                        'title' => 'PCIe Compatível com Limitação',
                        'message' => "GPU com PCIe {$gen1} funcionará em PCIe {$gen2} (mais lento)",
                        'component1' => $spec1['name'],
                        'component2' => $spec2['name']
                    ];
                }
                break;
        }
    }
    
    // 4. Validações adicionais
    if (isset($componentSpecs['motherboard']) && isset($componentSpecs['ram'])) {
        $maxRam = (int)$componentSpecs['motherboard']['motherboard_max_ram'] ?? 0;
        $ramCapacity = (int)$componentSpecs['ram']['ram_capacity'] ?? 0;
        
        if ($maxRam > 0 && $ramCapacity > $maxRam) {
            $warnings[] = [
                'severity' => 'warning',
                'icon' => 'exclamation-triangle',
                'title' => 'RAM Acima do Limite',
                'message' => "A placa mãe suporta no máximo {$maxRam}GB de RAM"
            ];
        }
    }
    
    // 5. Montar resumo
    $summary = [
        'compatible' => count($issues) === 0,
        'issues' => $issues,
        'warnings' => $warnings,
        'configuration' => [
            'component_count' => count($selectedComponents),
            'total_price' => $totalPrice,
            'estimated_tdp' => $totalTDP,
            'performance_level' => estimatePerformanceLevel($componentSpecs),
            'components' => $componentSpecs
        ]
    ];
    
    echo json_encode($summary);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Estima o nível de performance baseado nos componentes
 */
function estimatePerformanceLevel($specs) {
    $score = 0;
    
    // CPU score
    if (isset($specs['cpu']['cpu_boost_clock'])) {
        $score += (float)$specs['cpu']['cpu_boost_clock'] * 100;
    }
    if (isset($specs['cpu']['cpu_cores'])) {
        $score += (int)$specs['cpu']['cpu_cores'] * 50;
    }
    
    // GPU score
    if (isset($specs['gpu']['gpu_vram'])) {
        $score += (int)$specs['gpu']['gpu_vram'] * 200;
    }
    
    // RAM score
    if (isset($specs['ram']['ram_capacity'])) {
        $score += (int)$specs['ram']['ram_capacity'] * 20;
    }
    
    if ($score < 1000) return 'Budget';
    if ($score < 2000) return 'Intermediate';
    if ($score < 4000) return 'High-End';
    return 'Gaming/Workstation';
}
?>
