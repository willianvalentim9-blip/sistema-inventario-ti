<?php
// ========================================
// SCRIPT DE EXCLUSÃO DE PRODUTO COM SOFT DELETE
// ========================================
// Este script processa a exclusão (soft delete) de um produto via requisição AJAX

require_once 'config.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado e tem permissão de administrador
requireLogin();
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrativo') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem excluir produtos.']);
    exit;
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do produto inválido.']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Inicia uma transação
    $pdo->beginTransaction();

    // Busca os dados do produto ANTES de deletar para o log
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $stmt->execute([$product_id]);
    $product_to_delete = $stmt->fetch();
    
    if (!$product_to_delete) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Produto não encontrado ou já foi deletado.']);
        exit;
    }

    // Realiza soft delete (marca como deletado)
    $result = softDeleteProduct($product_id, $_SESSION['user_id']);
    
    if ($result) {
        // Registra a ação
        logAdminActivity($_SESSION["user_id"], "DELETE", "products", $product_id, $product_to_delete, null);
        
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Produto excluído com sucesso. (Dados preservados para recuperação)',
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Não foi possível excluir o produto.']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao excluir produto: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao excluir produto.']);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>