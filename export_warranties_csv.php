<?php
/**
 * Export Warranties to CSV
 * Exporta garantias de produtos e máquinas para arquivo CSV
 * 
 * Usage: export_warranties_csv.php?type=products&format=csv
 * Usage: export_warranties_csv.php?type=machines&format=csv
 */

require_once 'config.php';
requireLogin();

$pdo = getConnection();
$type = $_GET['type'] ?? 'products'; // products ou machines
$format = $_GET['format'] ?? 'csv'; // csv é o único suportado agora

// Validação
if (!in_array($type, ['products', 'machines', 'templates', 'suppliers'])) {
    die('Tipo inválido');
}

if (!in_array($format, ['csv'])) {
    die('Formato inválido');
}

try {
    $data = [];
    $filename = '';

    // ==========================================
    // EXPORTAR GARANTIAS DE PRODUTOS
    // ==========================================
    if ($type === 'products') {
        $filename = 'garantias_produtos_' . date('Y-m-d_Hi') . '.csv';
        
        $sql = "
            SELECT
                p.id as 'ID Produto',
                p.name as 'Nome Produto',
                p.sku as 'SKU',
                COALESCE(wt.name, 'N/A') as 'Template Garantia',
                COALESCE(ws.name, 'N/A') as 'Fornecedor',
                CONCAT(wt.period_value, ' ', wt.period_unit) as 'Período',
                p.warranty_provider as 'Provedor',
                p.warranty_notes as 'Notas',
                p.created_at as 'Criado em'
            FROM products p
            LEFT JOIN warranty_templates wt ON p.warranty_template_id = wt.id
            LEFT JOIN warranty_suppliers ws ON p.warranty_supplier_id = ws.id
            WHERE p.has_warranty = 1 AND (p.is_deleted = FALSE OR p.is_deleted IS NULL)
            ORDER BY p.name ASC
        ";
    }

    // ==========================================
    // EXPORTAR GARANTIAS DE MÁQUINAS
    // ==========================================
    elseif ($type === 'machines') {
        $filename = 'garantias_maquinas_' . date('Y-m-d_Hi') . '.csv';
        
        $sql = "
            SELECT
                rm.id as 'ID Máquina',
                rm.serial_number as 'Serial',
                rm.name as 'Nome Máquina',
                rm.processor as 'Processador',
                rm.memory as 'Memória',
                rm.storage as 'Armazenamento',
                rm.status as 'Status',
                rm.created_at as 'Criado em'
            FROM ready_machines rm
            WHERE rm.has_warranty = 1 AND (rm.is_deleted = FALSE OR rm.is_deleted IS NULL)
            ORDER BY rm.name ASC
        ";
    }

    // ==========================================
    // EXPORTAR TEMPLATES
    // ==========================================
    elseif ($type === 'templates') {
        $filename = 'templates_garantia_' . date('Y-m-d_Hi') . '.csv';
        
        $sql = "
            SELECT 
                id as 'ID',
                name as 'Nome',
                description as 'Descrição',
                warranty_provider as 'Provedor',
                period_value as 'Período (Valor)',
                period_unit as 'Período (Unidade)',
                warranty_notes as 'Notas',
                CASE WHEN is_active = 1 THEN 'Ativo' ELSE 'Inativo' END as 'Status',
                created_at as 'Criado em',
                updated_at as 'Atualizado em'
            FROM warranty_templates
            ORDER BY name ASC
        ";
    }

    // ==========================================
    // EXPORTAR FORNECEDORES
    // ==========================================
    elseif ($type === 'suppliers') {
        $filename = 'fornecedores_garantia_' . date('Y-m-d_Hi') . '.csv';
        
        $sql = "
            SELECT 
                id as 'ID',
                name as 'Nome Fornecedor',
                cnpj as 'CNPJ',
                contact_person as 'Pessoa de Contato',
                email as 'Email',
                phone as 'Telefone',
                mobile as 'Celular',
                website as 'Website',
                address_city as 'Cidade',
                address_state as 'Estado',
                CASE WHEN is_active = 1 THEN 'Ativo' ELSE 'Inativo' END as 'Status',
                created_at as 'Criado em',
                updated_at as 'Atualizado em'
            FROM warranty_suppliers
            ORDER BY name ASC
        ";
    }

    // Executar query
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        die('Nenhum registro encontrado para exportar');
    }

    // ==========================================
    // GERAR CSV
    // ==========================================
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');

    // Abrir output como arquivo CSV
    $output = fopen('php://output', 'w');

    // BOM para UTF-8 (compatibilidade com Excel em português)
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalhos (primeiras chaves do primeiro registro)
    $headers = array_keys($rows[0]);
    fputcsv($output, $headers, ';');

    // Linhas de dados
    foreach ($rows as $row) {
        fputcsv($output, $row, ';');
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    die('Erro ao exportar: ' . htmlspecialchars($e->getMessage()));
}
?>
