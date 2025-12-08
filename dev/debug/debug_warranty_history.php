<?php
/**
 * DEBUG: Verificar dados de histórico de garantia
 */
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = getConnection();
    
    echo "<h2>🔍 Debug - Histórico de Garantia</h2>";
    echo "<hr>";
    
    // 1. Total de registros
    echo "<h4>1️⃣ Total de Registros</h4>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranty_history");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p><strong>Total:</strong> $total registros</p>";
    
    // 2. Por tipo
    echo "<h4>2️⃣ Por Tipo</h4>";
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN product_id IS NOT NULL THEN 1 ELSE 0 END) as produtos,
            SUM(CASE WHEN machine_id IS NOT NULL THEN 1 ELSE 0 END) as maquinas,
            SUM(CASE WHEN warehouse_id IS NOT NULL THEN 1 ELSE 0 END) as armazem
        FROM warranty_history
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Produtos:</strong> " . ($counts['produtos'] ?? 0) . "</p>";
    echo "<p><strong>Máquinas:</strong> " . ($counts['maquinas'] ?? 0) . "</p>";
    echo "<p><strong>Armazém:</strong> " . ($counts['armazem'] ?? 0) . "</p>";
    
    // 3. Últimos 10 registros
    echo "<h4>3️⃣ Últimos 10 Registros</h4>";
    $stmt = $pdo->query("
        SELECT 
            id,
            product_id,
            machine_id,
            warehouse_id,
            action_type,
            created_at,
            user_id
        FROM warranty_history
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "<p style='color: red;'><strong>❌ Nenhum registro encontrado!</strong></p>";
    } else {
        echo "<table border='1' cellpadding='10' style='width: 100%;'>";
        echo "<tr><th>ID</th><th>Type</th><th>ID Item</th><th>Ação</th><th>Data</th></tr>";
        foreach ($records as $r) {
            $type = '';
            $item_id = '';
            if ($r['product_id']) { $type = 'PRODUTO'; $item_id = $r['product_id']; }
            elseif ($r['machine_id']) { $type = 'MÁQUINA'; $item_id = $r['machine_id']; }
            elseif ($r['warehouse_id']) { $type = 'ARMAZÉM'; $item_id = $r['warehouse_id']; }
            
            echo "<tr>";
            echo "<td>" . $r['id'] . "</td>";
            echo "<td><strong>" . $type . "</strong></td>";
            echo "<td>" . $item_id . "</td>";
            echo "<td>" . $r['action_type'] . "</td>";
            echo "<td>" . $r['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Testar getWarrantyHistory para máquina 15
    echo "<h4>4️⃣ Testando getWarrantyHistory para Máquina ID=15</h4>";
    require_once 'includes/warranty_functions.php';
    
    $history = getWarrantyHistory($pdo, 15, 100, 'machine');
    echo "<p><strong>Registros retornados:</strong> " . count($history) . "</p>";
    
    if (!empty($history)) {
        echo "<pre style='background: #f0f0f0; padding: 10px;'>";
        print_r($history);
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ Nenhum registro encontrado para máquina 15</strong></p>";
    }
    
    // 5. Verificar se máquina 15 existe
    echo "<h4>5️⃣ Verificar Máquina ID=15</h4>";
    $stmt = $pdo->prepare("SELECT id, name FROM ready_machines WHERE id = ?");
    $stmt->execute([15]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($machine) {
        echo "<p style='color: green;'>✅ <strong>Máquina encontrada:</strong> " . htmlspecialchars($machine['name']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>Máquina ID 15 NÃO existe</strong></p>";
    }
    
    // 6. Testar INSERT manual
    echo "<h4>6️⃣ Testando INSERT Manual</h4>";
    try {
        $stmt = $pdo->prepare("
            INSERT INTO warranty_history (machine_id, user_id, action_type, change_description, created_at)
            VALUES (?, ?, 'UPDATE', 'Teste de histórico', NOW())
        ");
        $stmt->execute([15, $_SESSION['user_id']]);
        echo "<p style='color: green;'>✅ <strong>Insert bem-sucedido!</strong></p>";
        
        // Verificar se foi inserido
        $stmt = $pdo->prepare("
            SELECT id FROM warranty_history 
            WHERE machine_id = ? AND action_type = 'UPDATE' AND change_description = 'Teste de histórico'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([15]);
        $check = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($check) {
            echo "<p style='color: green;'>✅ <strong>Verificação bem-sucedida!</strong> Registro ID: " . $check['id'] . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>❌ Erro no INSERT:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
