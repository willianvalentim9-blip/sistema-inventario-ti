<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $image_path = $data['image_path'] ?? '';
    $item_id = intval($data['item_id'] ?? 0);
    $item_type = $data['item_type'] ?? ''; // 'product' ou 'machine'

    if (empty($image_path) || $item_id <= 0 || !in_array($item_type, ['product', 'machine'])) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit();
    }

    $full_path = '';
    $table = '';

    if ($item_type === 'product') {
        $full_path = __DIR__ . '/uploads/products/' . basename($image_path);
        $table = 'products';
    } elseif ($item_type === 'machine') {
        $full_path = __DIR__ . '/uploads/machines/' . basename($image_path);
        $table = 'ready_machines';
    }

    if (file_exists($full_path) && is_file($full_path)) {
        if (unlink($full_path)) {
            try {
                $pdo = getConnection();
                $stmt = $pdo->prepare("UPDATE {$table} SET image = NULL WHERE id = ?");
                $stmt->execute([$item_id]);
                echo json_encode(['success' => true, 'message' => 'Imagem removida com sucesso.']);
                exit();
            } catch (PDOException $e) {
                error_log("Erro ao atualizar DB após remover imagem: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o banco de dados.']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Não foi possível remover o arquivo.']);
            exit();
        }
    } else {
        // Se o arquivo não existe, mas o DB ainda referencia, apenas atualiza o DB
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE {$table} SET image = NULL WHERE id = ?");
            $stmt->execute([$item_id]);
            echo json_encode(['success' => true, 'message' => 'Referência da imagem removida do banco de dados.']);
            exit();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar DB (arquivo não encontrado): " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o banco de dados.']);
            exit();
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    exit();
}
?>

