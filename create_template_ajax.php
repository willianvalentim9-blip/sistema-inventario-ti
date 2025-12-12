<?php
require_once 'config.php';
require_once 'modules/logs/log_functions.php';
requireLogin();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_name = trim($_POST['template_name'] ?? '');
    $warranty_period_value = intval($_POST['warranty_period_value'] ?? 0);

    if (empty($template_name) || $warranty_period_value <= 0) {
        $response['message'] = '✗ Preencha todos os campos obrigatórios';
        echo json_encode($response);
        exit;
    }

    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id FROM warranty_templates WHERE name = ?");
    $stmt->execute([$template_name]);
    if ($stmt->fetch()) {
        $response['message'] = '✗ Já existe um template com este nome.';
        echo json_encode($response);
        exit;
    }

    try {
        $description = trim($_POST['description'] ?? '');
        $warranty_provider = trim($_POST['warranty_provider'] ?? '');
        $warranty_period_unit = $_POST['warranty_period_unit'] ?? 'months';
        $warranty_notes = trim($_POST['warranty_notes'] ?? '');

        $stmt = $pdo->prepare("
            INSERT INTO warranty_templates (
                name, description, warranty_provider,
                period_value, period_unit, warranty_notes,
                is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$template_name, $description, $warranty_provider, $warranty_period_value, $warranty_period_unit, $warranty_notes]);
        
        logSystemAction('create_warranty_template', "Template: $template_name");
        
        $response['success'] = true;
        $response['message'] = '✓ Template criado com sucesso!';
        $_SESSION['flash_message'] = $response['message'];
        $_SESSION['flash_type'] = 'success';

    } catch (PDOException $e) {
        $response['message'] = '✗ Erro ao criar template: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
