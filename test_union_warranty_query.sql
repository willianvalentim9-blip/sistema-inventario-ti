-- Teste de Query UNION com Máquinas e Produtos com Garantia

(SELECT id, name, category, model, manufacturer, warranty_provider, warranty_start_date, 
       warranty_end_date, status, 'product' as item_type, invoice_number
FROM products 
WHERE has_warranty = 1)
UNION ALL
(SELECT id, name, NULL as category, NULL as model, NULL as manufacturer, warranty_provider, 
       NULL as warranty_start_date, NULL as warranty_end_date, status, 'machine' as item_type, NULL as invoice_number
FROM ready_machines 
WHERE has_warranty = 1)
ORDER BY warranty_end_date ASC;
