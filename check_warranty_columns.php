<?php
/**
 * VERIFICAÇÃO: Estrutura da Tabela Products
 * 
 * Valida se todas as colunas de garantia existem no banco de dados
 */

require_once 'config.php';
requireLogin();

try {
    $pdo = getConnection();
    
    // Obter informações sobre a tabela
    $stmt = $pdo->prepare("DESCRIBE products");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $warranty_columns = [
        'has_warranty',
        'warranty_provider',
        'warranty_period_value',
        'warranty_period_unit',
        'warranty_start_date',
        'warranty_end_date',
        'invoice_number',
        'warranty_notes'
    ];
    
    $found_columns = [];
    $missing_columns = [];
    
    foreach ($columns as $column) {
        $col_name = $column['Field'];
        if (in_array($col_name, $warranty_columns)) {
            $found_columns[$col_name] = $column;
        }
    }
    
    foreach ($warranty_columns as $col) {
        if (!isset($found_columns[$col])) {
            $missing_columns[] = $col;
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Verificação da Tabela Products</title>
        <style>
            body { font-family: Arial; padding: 20px; }
            .ok { color: green; font-weight: bold; }
            .error { color: red; font-weight: bold; }
            table { border-collapse: collapse; width: 100%; margin: 20px 0; }
            th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
            th { background-color: #f0f0f0; }
            .found { background-color: #e8f5e9; }
            .missing { background-color: #ffebee; }
        </style>
    </head>
    <body>
        <h1>🔍 Verificação da Tabela Products</h1>
        
        <?php if (empty($missing_columns)): ?>
            <div style="padding: 15px; background-color: #e8f5e9; border-left: 4px solid green; margin-bottom: 20px;">
                <span class="ok">✅ TUDO OK!</span> Todas as colunas de garantia foram encontradas.
            </div>
        <?php else: ?>
            <div style="padding: 15px; background-color: #ffebee; border-left: 4px solid red; margin-bottom: 20px;">
                <span class="error">❌ PROBLEMA DETECTADO!</span>
                <p>Faltam as seguintes colunas:</p>
                <ul>
                    <?php foreach ($missing_columns as $col): ?>
                        <li><strong><?php echo htmlspecialchars($col); ?></strong></li>
                    <?php endforeach; ?>
                </ul>
                <p>Execute as migrações de banco de dados para criar essas colunas.</p>
            </div>
        <?php endif; ?>
        
        <h2>Colunas de Garantia:</h2>
        <table>
            <tr>
                <th>Coluna</th>
                <th>Tipo</th>
                <th>Null</th>
                <th>Padrão</th>
                <th>Status</th>
            </tr>
            <?php foreach ($warranty_columns as $col): ?>
                <?php $col_info = $found_columns[$col] ?? null; ?>
                <tr class="<?php echo $col_info ? 'found' : 'missing'; ?>">
                    <td><strong><?php echo htmlspecialchars($col); ?></strong></td>
                    <td><?php echo $col_info ? htmlspecialchars($col_info['Type']) : 'N/A'; ?></td>
                    <td><?php echo $col_info ? htmlspecialchars($col_info['Null']) : 'N/A'; ?></td>
                    <td><?php echo $col_info ? htmlspecialchars($col_info['Default'] ?? '-') : 'N/A'; ?></td>
                    <td><?php echo $col_info ? '✅ Encontrada' : '❌ Faltando'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>Todas as Colunas da Tabela:</h2>
        <table>
            <tr>
                <th>Coluna</th>
                <th>Tipo</th>
                <th>Null</th>
                <th>Chave</th>
                <th>Padrão</th>
            </tr>
            <?php foreach ($columns as $col): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($col['Field']); ?></strong></td>
                    <td><?php echo htmlspecialchars($col['Type']); ?></td>
                    <td><?php echo htmlspecialchars($col['Null']); ?></td>
                    <td><?php echo htmlspecialchars($col['Key']); ?></td>
                    <td><?php echo htmlspecialchars($col['Default'] ?? '-'); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    echo '<h1>❌ Erro ao conectar ao banco</h1>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
?>
