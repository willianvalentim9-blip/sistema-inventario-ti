<?php
require 'config.php';
$pdo = getConnection();

echo "\n=== VERIFICAÇÃO DE TABELAS DE HISTÓRICO ===\n\n";

$tables = [
    'machine_movements' => 'Movimentações de Máquina',
    'machine_inputs' => 'Entradas de Máquina',
    'machine_outputs' => 'Saídas de Máquina',
    'machine_products' => 'Componentes de Máquina'
];

foreach ($tables as $table => $label) {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
    $result = $stmt->fetch();
    echo "✅ [$table] $label: " . $result['cnt'] . " registros\n";
}

echo "\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM admin_logs WHERE table_name='ready_machines'");
$result = $stmt->fetch();
echo "✅ [admin_logs] Registros para ready_machines: " . $result['cnt'] . " registros\n";

echo "\n✅ TODAS AS TABELAS ESTÃO ACESSÍVEIS!\n";
echo "\n=== VERIFICAÇÃO CONCLUÍDA ===\n";
