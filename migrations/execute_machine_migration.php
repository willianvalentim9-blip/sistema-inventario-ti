<?php
/**
 * Script para executar a migração de criação da tabela machine_products
 * Esta tabela é essencial para o sistema de componentes de máquinas
 */

require_once 'config.php';

// Verifica se o usuário é admin
requireLogin();

// Apenas admins podem executar migrações
if ($_SESSION['role'] !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar migrações.');
}

$result = [
    'success' => false,
    'message' => '',
    'details' => []
];

try {
    $pdo = getConnection();
    
    // Lê o arquivo de migração
    $migrationFile = __DIR__ . '/migrations/2025-12-02-machine-products-table.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Arquivo de migração não encontrado: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Separa os comandos SQL (ignorando comentários e linhas vazias)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
    );
    
    // Executa cada statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement . ';');
            $result['details'][] = 'Comando executado com sucesso';
        }
    }
    
    // Verifica se a tabela foi criada
    $check = $pdo->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'machine_products'");
    
    if ($check->fetch()) {
        $result['success'] = true;
        $result['message'] = 'Migração executada com sucesso! Tabela machine_products criada.';
        
        // Log da atividade
        logAdminActivity($_SESSION['user_id'], 'EXECUTE_MIGRATION', 'machine_products', 0, 
            'Migration 2025-12-02-machine-products-table executed successfully');
    } else {
        throw new Exception('A tabela machine_products não foi criada');
    }
    
} catch (Exception $e) {
    $result['message'] = 'Erro ao executar migração: ' . $e->getMessage();
    error_log("Erro na migração: " . $e->getMessage());
    
    // Log do erro
    if (isset($_SESSION['user_id'])) {
        logAdminActivity($_SESSION['user_id'], 'MIGRATION_ERROR', 'machine_products', 0, 
            'Erro: ' . $e->getMessage());
    }
}

// Se for chamado via AJAX, retorna JSON
if (!empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Caso contrário, exibe resultado em HTML
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executar Migração</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-<?php echo $result['success'] ? 'success' : 'danger'; ?> text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-<?php echo $result['success'] ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                            <?php echo $result['success'] ? 'Migração Executada' : 'Erro na Migração'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3"><?php echo htmlspecialchars($result['message']); ?></p>
                        
                        <?php if (!empty($result['details'])): ?>
                            <div class="alert alert-info">
                                <strong>Detalhes:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($result['details'] as $detail): ?>
                                        <li><?php echo htmlspecialchars($detail); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="dashboard.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-home me-1"></i>Voltar ao Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
