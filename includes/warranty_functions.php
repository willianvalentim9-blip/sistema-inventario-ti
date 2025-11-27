<?php
/**
 * Funções Auxiliares para Sistema de Garantias
 * 
 * Este arquivo contém todas as funções helper para o sistema
 * de garantias, incluindo cálculos, formatação e validação.
 * 
 * @package sistema3
 * @category Garantias
 * @version 1.0
 */

// ===========================
// 1. FUNÇÕES DE STATUS
// ===========================

/**
 * Obtém o status da garantia baseado na data final
 * 
 * @param string $end_date Data de término da garantia (YYYY-MM-DD)
 * @return array Status com label, classe CSS e cor
 * 
 * Exemplo:
 * $status = getWarrantyStatus('2025-12-31');
 * // retorna: ['status' => 'Ativa', 'class' => 'warning', 'color' => 'warning', 'label' => 'fa-hourglass-end']
 */
function getWarrantyStatus($end_date) {
    if (empty($end_date)) {
        return [
            'status' => 'Sem Garantia',
            'class' => 'secondary',
            'color' => 'secondary',
            'label' => 'fa-ban',
            'days' => 0
        ];
    }

    $today = new DateTime();
    $end = new DateTime($end_date);
    $diff = $today->diff($end);
    $days = intval($diff->format('%r%a'));

    // Expirada
    if ($days < 0) {
        return [
            'status' => 'Expirada',
            'class' => 'danger',
            'color' => 'danger',
            'label' => 'fa-x',
            'days' => $days
        ];
    }

    // Crítica (até 7 dias)
    if ($days <= 7 && $days > 0) {
        return [
            'status' => 'Crítica',
            'class' => 'danger',
            'color' => 'danger',
            'label' => 'fa-circle-exclamation',
            'days' => $days
        ];
    }

    // Próximo de vencer (8 a 30 dias)
    if ($days <= 30 && $days > 7) {
        return [
            'status' => 'Vencendo',
            'class' => 'warning',
            'color' => 'warning',
            'label' => 'fa-exclamation-triangle',
            'days' => $days
        ];
    }

    // Ativa
    return [
        'status' => 'Ativa',
        'class' => 'success',
        'color' => 'success',
        'label' => 'fa-check-circle',
        'days' => $days
    ];
}

/**
 * Obtém o nível de alerta da garantia (1-4)
 * 
 * @param int $days_remaining Dias restantes
 * @return int Nível de alerta (1=crítica, 2=atenção, 3=próximas semanas, 4=ativa)
 * 
 * Níveis:
 * 1 = Crítica (vencida ou até 7 dias)
 * 2 = Atenção (8-30 dias)
 * 3 = Próximas 4 semanas (31-90 dias)
 * 4 = Ativa (>90 dias)
 */
function getAlertLevel($days_remaining) {
    if ($days_remaining <= 7) {
        return 1; // Crítica
    }
    if ($days_remaining <= 30) {
        return 2; // Atenção
    }
    if ($days_remaining <= 90) {
        return 3; // Próximas 4 semanas
    }
    return 4; // Ativa
}

/**
 * Obtém o label do nível de alerta
 * 
 * @param int $level Nível de alerta (1-4)
 * @return array Label, classe CSS e cor
 */
function getAlertLevelLabel($level) {
    $levels = [
        1 => ['label' => 'Crítica', 'class' => 'danger', 'icon' => 'fa-circle-exclamation'],
        2 => ['label' => 'Atenção', 'class' => 'warning', 'icon' => 'fa-exclamation-triangle'],
        3 => ['label' => 'Próximas 4 Semanas', 'class' => 'info', 'icon' => 'fa-hourglass-end'],
        4 => ['label' => 'Ativa', 'class' => 'success', 'icon' => 'fa-check-circle']
    ];

    return $levels[$level] ?? $levels[4];
}

// ===========================
// 2. FUNÇÕES DE CÁLCULO
// ===========================

/**
 * Calcula dias restantes até expiração
 * 
 * @param string $end_date Data de término (YYYY-MM-DD)
 * @return int Dias restantes (negativo = expirada)
 * 
 * Exemplo:
 * $dias = calculateDaysRemaining('2025-12-31');
 * // retorna: 45 (se faltam 45 dias)
 */
function calculateDaysRemaining($end_date) {
    if (empty($end_date)) {
        return 0;
    }

    $today = new DateTime();
    $end = new DateTime($end_date);
    $diff = $today->diff($end);
    
    return intval($diff->format('%r%a'));
}

/**
 * Calcula data final baseado em período
 * 
 * @param string $start_date Data de início (YYYY-MM-DD)
 * @param int $value Valor do período
 * @param string $unit Unidade (days, months, years)
 * @return string Data final (YYYY-MM-DD)
 * 
 * Exemplo:
 * $fim = calculateEndDate('2025-01-01', 12, 'months');
 * // retorna: '2026-01-01'
 */
function calculateEndDate($start_date, $value, $unit = 'months') {
    if (empty($start_date) || $value <= 0) {
        return '';
    }

    $date = new DateTime($start_date);

    switch ($unit) {
        case 'days':
            $date->modify("+{$value} days");
            break;
        case 'months':
            $date->modify("+{$value} months");
            break;
        case 'years':
            $date->modify("+{$value} years");
            break;
        default:
            return '';
    }

    return $date->format('Y-m-d');
}

/**
 * Valida se as datas de garantia são consistentes
 * 
 * @param string $start_date Data de início
 * @param string $end_date Data de fim
 * @param int $period_value Valor do período
 * @param string $period_unit Unidade do período
 * @return array ['valid' => bool, 'message' => string]
 */
function validateWarrantyDates($start_date, $end_date, $period_value, $period_unit) {
    $errors = [];

    // Validar datas vazias
    if (empty($start_date)) {
        $errors[] = 'Data de início é obrigatória';
    }
    if (empty($end_date)) {
        $errors[] = 'Data de término é obrigatória';
    }

    if (empty($errors)) {
        // Validar que fim > início
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);

        if ($end <= $start) {
            $errors[] = 'Data de término deve ser posterior à data de início';
        }

        // Validar consistência com período
        $calculated_end = calculateEndDate($start_date, $period_value, $period_unit);
        $calculated = new DateTime($calculated_end);
        $actual = new DateTime($end_date);

        // Permite 1 dia de diferença (para fusos horários)
        $diff = $actual->diff($calculated)->days;
        if ($diff > 1) {
            $errors[] = "Data de término não corresponde ao período informado (esperado: $calculated_end, fornecido: $end_date)";
        }
    }

    return [
        'valid' => empty($errors),
        'message' => implode('; ', $errors)
    ];
}

// ===========================
// 3. FUNÇÕES DE FORMATAÇÃO
// ===========================

/**
 * Formata garantia para exibição
 * 
 * @param string $start_date Data de início
 * @param string $end_date Data de fim
 * @param int $period_value Valor do período
 * @param string $period_unit Unidade do período
 * @return string Garantia formatada
 * 
 * Exemplo:
 * formatWarrantyDisplay('2025-01-01', '2026-01-01', 12, 'months');
 * // retorna: '12 meses (01/01/2025 até 01/01/2026)'
 */
function formatWarrantyDisplay($start_date, $end_date, $period_value, $period_unit) {
    if (empty($start_date) || empty($end_date)) {
        return 'Sem garantia';
    }

    $start_formatted = date('d/m/Y', strtotime($start_date));
    $end_formatted = date('d/m/Y', strtotime($end_date));

    // Traduzir unidade
    $units = [
        'days' => 'dias',
        'months' => 'meses',
        'years' => 'anos'
    ];
    $unit_label = $units[$period_unit] ?? $period_unit;

    return "{$period_value} {$unit_label} ({$start_formatted} até {$end_formatted})";
}

/**
 * Formata data para português
 * 
 * @param string $date Data (YYYY-MM-DD)
 * @param bool $include_time Incluir hora?
 * @return string Data formatada (DD/MM/YYYY ou DD/MM/YYYY HH:MM:SS)
 */
function formatDatePT($date, $include_time = false) {
    if (empty($date)) {
        return '--';
    }

    if ($include_time) {
        return date('d/m/Y H:i:s', strtotime($date));
    }

    return date('d/m/Y', strtotime($date));
}

/**
 * Formata diferença de dias para exibição legível
 * 
 * @param int $days Número de dias
 * @return string Texto formatado
 * 
 * Exemplo:
 * formatDaysRemaining(45);
 * // retorna: '45 dias restantes'
 * 
 * formatDaysRemaining(-5);
 * // retorna: '5 dias atrás'
 */
function formatDaysRemaining($days) {
    if ($days == 0) {
        return 'Vence hoje';
    }

    if ($days > 0) {
        return abs($days) . ' dia' . (abs($days) > 1 ? 's' : '') . ' restante' . (abs($days) > 1 ? 's' : '');
    }

    return abs($days) . ' dia' . (abs($days) > 1 ? 's' : '') . ' atrás';
}

/**
 * Formata unidade de período para português
 * 
 * @param string $unit Unidade (days, months, years)
 * @return string Unidade em português
 */
function formatPeriodUnit($unit) {
    $units = [
        'days' => 'dias',
        'months' => 'meses',
        'years' => 'anos'
    ];

    return $units[$unit] ?? $unit;
}

// ===========================
// 4. FUNÇÕES DE TEMPLATE
// ===========================

/**
 * Obtém dados de um template de garantia
 * 
 * @param PDO $pdo Conexão com banco
 * @param int $template_id ID do template
 * @return array Dados do template ou array vazio
 */
function getTemplateDefaults($pdo, $template_id) {
    if (empty($template_id)) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                name,
                description,
                warranty_provider,
                warranty_period_value,
                warranty_period_unit,
                invoice_number,
                warranty_notes
            FROM warranty_templates
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($template) {
            return [
                'warranty_provider' => $template['warranty_provider'] ?? '',
                'warranty_period_value' => $template['warranty_period_value'] ?? 12,
                'warranty_period_unit' => $template['warranty_period_unit'] ?? 'months',
                'warranty_notes' => $template['warranty_notes'] ?? '',
                'template_name' => $template['name']
            ];
        }
    } catch (PDOException $e) {
        error_log("Erro ao obter template: " . $e->getMessage());
    }

    return [];
}

/**
 * Obtém lista de templates ativos
 * 
 * @param PDO $pdo Conexão com banco
 * @return array Lista de templates
 */
function getActiveTemplates($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                name,
                description,
                period_value,
                period_unit,
                warranty_provider,
                warranty_notes,
                created_at
            FROM warranty_templates
            WHERE is_active = 1
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao obter templates: " . $e->getMessage());
        return [];
    }
}

// ===========================
// 5. FUNÇÕES DE HISTÓRICO
// ===========================

/**
 * Registra mudança no histórico de garantia
 * 
 * @param PDO $pdo Conexão com banco
 * @param int $product_id ID do produto
 * @param string $action Ação (CREATE, UPDATE, DELETE, CLAIM)
 * @param array $old_values Valores antigos
 * @param array $new_values Valores novos
 * @param int $user_id ID do usuário
 * @return bool Sucesso ou falha
 */
function registerWarrantyHistory($pdo, $product_id, $action, $old_values = [], $new_values = [], $user_id = null) {
    global $userId; // Fallback se $user_id não for fornecido

    try {
        $user_id = $user_id ?? $userId ?? 0;

        $stmt = $pdo->prepare("
            INSERT INTO warranty_history (
                product_id,
                action_type,
                old_values,
                new_values,
                user_id,
                change_description,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $old_json = json_encode($old_values);
        $new_json = json_encode($new_values);
        
        // Descrição padrão baseada no tipo de ação
        $descriptions = [
            'CREATE' => 'Garantia criada',
            'UPDATE' => 'Garantia atualizada automaticamente',
            'DELETE' => 'Garantia removida',
            'CLAIM' => 'Acionamento de garantia registrado'
        ];
        $description = $descriptions[$action] ?? 'Alteração registrada';

        return $stmt->execute([
            $product_id,
            $action,
            $old_json,
            $new_json,
            $user_id,
            $description
        ]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar histórico: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtém histórico de garantia de um produto
 * 
 * @param PDO $pdo Conexão com banco
 * @param int $product_id ID do produto
 * @param int $limit Limite de registros
 * @return array Histórico ordenado por data decrescente
 */
function getWarrantyHistory($pdo, $product_id, $limit = 50) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                h.id,
                h.action_type as action,
                h.old_values,
                h.new_values,
                h.created_at,
                h.change_description,
                u.name as user_name,
                u.email as user_email
            FROM warranty_history h
            LEFT JOIN users u ON h.user_id = u.id
            WHERE h.product_id = ?
            ORDER BY h.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$product_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao obter histórico: " . $e->getMessage());
        return [];
    }
}

// ===========================
// 6. FUNÇÕES DE ALERTAS
// ===========================

/**
 * Obtém resumo de alertas de garantia
 * 
 * @param PDO $pdo Conexão com banco
 * @return array Resumo com contagens por nível
 */
function getWarrantyAlertsSummary($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                alert_level,
                COUNT(*) as count
            FROM vw_warranty_status_summary
            WHERE alert_level IS NOT NULL
            GROUP BY alert_level
            ORDER BY alert_level ASC
        ");
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary = [
            'critical' => 0,    // Nível 1
            'warning' => 0,     // Nível 2
            'attention' => 0,   // Nível 3
            'active' => 0       // Nível 4
        ];

        foreach ($results as $row) {
            switch ($row['alert_level']) {
                case 1:
                    $summary['critical'] = $row['count'];
                    break;
                case 2:
                    $summary['warning'] = $row['count'];
                    break;
                case 3:
                    $summary['attention'] = $row['count'];
                    break;
                case 4:
                    $summary['active'] = $row['count'];
                    break;
            }
        }

        return $summary;
    } catch (PDOException $e) {
        error_log("Erro ao obter resumo de alertas: " . $e->getMessage());
        return [
            'critical' => 0,
            'warning' => 0,
            'attention' => 0,
            'active' => 0
        ];
    }
}

/**
 * Obtém produtos com garantia vencendo
 * 
 * @param PDO $pdo Conexão com banco
 * @param int $alert_level Nível de alerta (1-4)
 * @param int $limit Limite de resultados
 * @return array Produtos com garantia vencendo
 */
function getProductsWithExpiringWarranty($pdo, $alert_level = null, $limit = 10) {
    try {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.model,
                p.product_code,
                ws.warranty_start_date,
                ws.warranty_end_date,
                ws.days_remaining,
                ws.alert_level,
                ws.warranty_provider
            FROM vw_warranty_status_summary ws
            INNER JOIN products p ON ws.product_id = p.id
            WHERE p.status = 'Ativo'
        ";

        if ($alert_level !== null) {
            $sql .= " AND ws.alert_level = ?";
        } else {
            $sql .= " AND ws.alert_level IN (1, 2, 3)"; // Não incluir ativas
        }

        $sql .= " ORDER BY ws.days_remaining ASC, p.name ASC LIMIT ?";

        $stmt = $pdo->prepare($sql);
        
        if ($alert_level !== null) {
            $stmt->execute([$alert_level, $limit]);
        } else {
            $stmt->execute([$limit]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao obter produtos com garantia vencendo: " . $e->getMessage());
        return [];
    }
}

// ===========================
// 7. FUNÇÕES UTILITÁRIAS
// ===========================

/**
 * Valida se um período de garantia é válido
 * 
 * @param int $value Valor do período
 * @param string $unit Unidade do período
 * @return array ['valid' => bool, 'message' => string]
 */
function validateWarrantyPeriod($value, $unit) {
    $errors = [];

    if (empty($value) || $value <= 0) {
        $errors[] = 'Período deve ser maior que 0';
    }

    if (!in_array($unit, ['days', 'months', 'years'])) {
        $errors[] = 'Unidade de período inválida';
    }

    return [
        'valid' => empty($errors),
        'message' => implode('; ', $errors)
    ];
}

/**
 * Obtém todas as unidades de período disponíveis
 * 
 * @return array Unidades com labels
 */
function getWarrantyPeriodUnits() {
    return [
        'days' => 'Dias',
        'months' => 'Meses',
        'years' => 'Anos'
    ];
}

/**
 * Verifica se uma garantia está próxima de vencer
 * 
 * @param string $end_date Data de término
 * @param int $days_threshold Quantos dias antes considerar "próximo" (padrão: 30)
 * @return bool
 */
function isWarrantyExpiring($end_date, $days_threshold = 30) {
    if (empty($end_date)) {
        return false;
    }

    $days_remaining = calculateDaysRemaining($end_date);
    return $days_remaining > 0 && $days_remaining <= $days_threshold;
}

/**
 * Verifica se uma garantia está expirada
 * 
 * @param string $end_date Data de término
 * @return bool
 */
function isWarrantyExpired($end_date) {
    if (empty($end_date)) {
        return false;
    }

    return calculateDaysRemaining($end_date) < 0;
}

/**
 * Verifica se uma garantia está ativa
 * 
 * @param string $start_date Data de início
 * @param string $end_date Data de término
 * @return bool
 */
function isWarrantyActive($start_date, $end_date) {
    if (empty($start_date) || empty($end_date)) {
        return false;
    }

    $today = new DateTime();
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);

    return $today >= $start && $today <= $end;
}

/**
 * Obtém cor Bootstrap baseado no status
 * 
 * @param string $status Status da garantia
 * @return string Classe Bootstrap (success, warning, danger, etc)
 */
function getBootstrapColorByStatus($status) {
    $colors = [
        'Ativa' => 'success',
        'Vencendo' => 'warning',
        'Expirada' => 'danger',
        'Crítica' => 'danger',
        'Sem Garantia' => 'secondary'
    ];

    return $colors[$status] ?? 'secondary';
}

?>
