<?php
require_once 'config.php';

try {
    $pdo = getConnection();
    
    // Adicionar coluna
    $pdo->exec("ALTER TABLE warehouse ADD COLUMN IF NOT EXISTS warranty_template_id INT(11) NULL COMMENT 'ID do template de garantia' AFTER warranty_supplier_id");
    
    // Verificar
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='warehouse' AND COLUMN_NAME='warranty_template_id'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ Sucesso! Coluna warranty_template_id foi adicionada à tabela warehouse.\n";
        echo "Você pode fechar esta página agora.";
    } else {
        echo "⚠️ Aviso: Coluna pode não ter sido criada corretamente.";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
    exit(1);
}
?>
