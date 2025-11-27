<?php
/**
 * TESTE: VERIFICAÇÃO DO SISTEMA DE GARANTIA EM MÁQUINAS
 * 
 * Este arquivo testa se o sistema está funcionando corretamente
 * Acesso: http://localhost/sistema4/test_warranty_machines.php
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Sistema de Garantia em Máquinas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f5f5f5; padding: 20px; }
        .test-card { background: white; border-left: 4px solid #0d6efd; }
        .test-pass { border-left-color: #28a745; }
        .test-fail { border-left-color: #dc3545; }
        .test-warning { border-left-color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <h1 class="mb-4"><i class="fas fa-shield-alt me-2"></i>Teste - Garantia em Máquinas</h1>

                <?php
                $tests = [];
                $all_passed = true;

                // =======================
                // TESTE 1: Conexão com BD
                // =======================
                try {
                    $pdo = getConnection();
                    $tests[] = [
                        'name' => 'Conexão com Banco de Dados',
                        'status' => 'pass',
                        'message' => 'Conexão estabelecida com sucesso',
                        'icon' => 'check-circle'
                    ];
                } catch (Exception $e) {
                    $tests[] = [
                        'name' => 'Conexão com Banco de Dados',
                        'status' => 'fail',
                        'message' => 'Erro: ' . $e->getMessage(),
                        'icon' => 'times-circle'
                    ];
                    $all_passed = false;
                }

                // =======================
                // TESTE 2: Tabela Existe
                // =======================
                try {
                    $stmt = $pdo->query("SHOW TABLES LIKE 'ready_machines'");
                    $table_exists = $stmt->rowCount() > 0;
                    $tests[] = [
                        'name' => 'Tabela ready_machines',
                        'status' => $table_exists ? 'pass' : 'fail',
                        'message' => $table_exists ? 'Tabela encontrada' : 'Tabela não encontrada',
                        'icon' => $table_exists ? 'check-circle' : 'times-circle'
                    ];
                    if (!$table_exists) $all_passed = false;
                } catch (Exception $e) {
                    $tests[] = [
                        'name' => 'Tabela ready_machines',
                        'status' => 'fail',
                        'message' => 'Erro: ' . $e->getMessage(),
                        'icon' => 'times-circle'
                    ];
                    $all_passed = false;
                }

                // =======================
                // TESTE 3: Colunas de Garantia
                // =======================
                try {
                    $stmt = $pdo->query("SHOW COLUMNS FROM ready_machines");
                    $columns = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $columns[] = $row['Field'];
                    }
                    
                    $warranty_cols = ['has_warranty', 'warranty_provider', 'warranty_period_value', 'warranty_period_unit'];
                    $missing = array_diff($warranty_cols, $columns);
                    
                    if (empty($missing)) {
                        $tests[] = [
                            'name' => 'Colunas de Garantia',
                            'status' => 'pass',
                            'message' => 'Todas as 4 colunas encontradas',
                            'icon' => 'check-circle'
                        ];
                    } else {
                        $tests[] = [
                            'name' => 'Colunas de Garantia',
                            'status' => 'fail',
                            'message' => 'Colunas faltantes: ' . implode(', ', $missing),
                            'icon' => 'times-circle'
                        ];
                        $all_passed = false;
                    }
                } catch (Exception $e) {
                    $tests[] = [
                        'name' => 'Colunas de Garantia',
                        'status' => 'fail',
                        'message' => 'Erro: ' . $e->getMessage(),
                        'icon' => 'times-circle'
                    ];
                    $all_passed = false;
                }

                // =======================
                // TESTE 4: Arquivo edit_machine.php
                // =======================
                if (file_exists('edit_machine.php')) {
                    $content = file_get_contents('edit_machine.php');
                    $has_warranty_code = strpos($content, 'has_warranty') !== false;
                    $has_form_fields = strpos($content, 'machine_warranty_provider') !== false;
                    $has_javascript = strpos($content, 'updateWarrantySummaryMachine') !== false;
                    
                    if ($has_warranty_code && $has_form_fields && $has_javascript) {
                        $tests[] = [
                            'name' => 'Arquivo edit_machine.php',
                            'status' => 'pass',
                            'message' => 'Código de garantia implementado',
                            'icon' => 'check-circle'
                        ];
                    } else {
                        $missing_parts = [];
                        if (!$has_warranty_code) $missing_parts[] = 'código de garantia';
                        if (!$has_form_fields) $missing_parts[] = 'campos do formulário';
                        if (!$has_javascript) $missing_parts[] = 'JavaScript';
                        $tests[] = [
                            'name' => 'Arquivo edit_machine.php',
                            'status' => 'fail',
                            'message' => 'Faltam: ' . implode(', ', $missing_parts),
                            'icon' => 'times-circle'
                        ];
                        $all_passed = false;
                    }
                } else {
                    $tests[] = [
                        'name' => 'Arquivo edit_machine.php',
                        'status' => 'fail',
                        'message' => 'Arquivo não encontrado',
                        'icon' => 'times-circle'
                    ];
                    $all_passed = false;
                }

                // =======================
                // TESTE 5: Arquivo view_machine.php
                // =======================
                if (file_exists('view_machine.php')) {
                    $content = file_get_contents('view_machine.php');
                    $has_warranty_display = strpos($content, 'has_warranty') !== false && strpos($content, 'warranty_provider') !== false;
                    
                    if ($has_warranty_display) {
                        $tests[] = [
                            'name' => 'Arquivo view_machine.php',
                            'status' => 'pass',
                            'message' => 'Exibição de garantia implementada',
                            'icon' => 'check-circle'
                        ];
                    } else {
                        $tests[] = [
                            'name' => 'Arquivo view_machine.php',
                            'status' => 'fail',
                            'message' => 'Exibição de garantia não encontrada',
                            'icon' => 'times-circle'
                        ];
                        $all_passed = false;
                    }
                } else {
                    $tests[] = [
                        'name' => 'Arquivo view_machine.php',
                        'status' => 'fail',
                        'message' => 'Arquivo não encontrado',
                        'icon' => 'times-circle'
                    ];
                    $all_passed = false;
                }

                // =======================
                // TESTE 6: Dados de Exemplo
                // =======================
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ready_machines");
                    $result = $stmt->fetch();
                    $machine_count = $result['count'];
                    $tests[] = [
                        'name' => 'Dados de Máquinas',
                        'status' => 'pass',
                        'message' => "Total de máquinas: $machine_count",
                        'icon' => 'check-circle'
                    ];
                } catch (Exception $e) {
                    $tests[] = [
                        'name' => 'Dados de Máquinas',
                        'status' => 'warning',
                        'message' => 'Aviso: ' . $e->getMessage(),
                        'icon' => 'exclamation-circle'
                    ];
                }

                // =======================
                // EXIBIÇÃO DOS RESULTADOS
                // =======================
                ?>

                <div class="alert <?php echo $all_passed ? 'alert-success' : 'alert-danger'; ?> mb-4">
                    <h4 class="alert-heading">
                        <i class="fas fa-<?php echo $all_passed ? 'check' : 'times'; ?>-circle me-2"></i>
                        <?php echo $all_passed ? 'Todos os testes passaram!' : 'Alguns testes falharam!'; ?>
                    </h4>
                    <p class="mb-0">
                        <?php echo $all_passed ? 'Sistema de garantia em máquinas operacional.' : 'Verifique os erros abaixo.'; ?>
                    </p>
                </div>

                <?php foreach ($tests as $test): ?>
                <div class="card test-card test-<?php echo $test['status']; ?> mb-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-<?php echo $test['icon']; ?> me-2"></i>
                            <?php echo $test['name']; ?>
                        </h5>
                        <p class="card-text text-muted mb-0"><?php echo $test['message']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="mt-4 text-center">
                    <a href="ready_machines.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i>Voltar para Máquinas
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
