<?php
require_once '../../config.php';

$pdo = getConnection();

// SQL para criar a view de status de garantia
$sql_view = "
CREATE OR REPLACE VIEW vw_warranty_status_summary AS
SELECT 
    p.id as product_id,
    p.id,
    p.name,
    p.product_code,
    p.warranty_end_date,
    p.warranty_start_date,
    p.warranty_supplier_id,
    p.warranty_provider,
    DATEDIFF(p.warranty_end_date, CURDATE()) as days_remaining,
    CASE 
        WHEN p.warranty_end_date IS NULL THEN NULL
        WHEN p.warranty_end_date < CURDATE() THEN 1
        WHEN p.warranty_end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1
        WHEN p.warranty_end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 2
        WHEN p.warranty_end_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 3
        ELSE 4
    END as alert_level
FROM products p
WHERE p.has_warranty = 1 AND (p.is_deleted = FALSE OR p.is_deleted IS NULL)
";

try {
    $pdo->exec($sql_view);
    echo "✓ View 'vw_warranty_status_summary' criada com sucesso!";
} catch (PDOException $e) {
    echo "✗ Erro ao criar view: " . $e->getMessage();
}
?>
