<?php
// ========================================
// SCRIPT DE EXCLUSÃO DE PRODUTO (COM LOG DE ADMIN)
// ========================================
// Este script processa a exclusão de um produto via requisição AJAX

require_once 'config.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado e tem permissão de administrador
requireLogin();
if ($_SESSION['user_role'] !== 'admin') {
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

    // **NOVO: Busca os dados do produto ANTES de deletar para o log**
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product_to_delete = $stmt->fetch();
    
    if (!$product_to_delete) {
        throw new Exception('Produto não encontrado.');
    }

    $product_image = $product_to_delete['image'];

    // Exclui o produto
    $delete_stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $delete_stmt->execute([$product_id]);

    if ($delete_stmt->rowCount() > 0) {
        // Se a exclusão do banco de dados foi bem-sucedida, tenta remover o arquivo de imagem
        if (!empty($product_image)) {
            $image_path = 'uploads/products/' . $product_image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // **NOVO: Registra a exclusão no log de administrador com os dados antigos**
        logAdminActivity($_SESSION["user_id"], "DELETE", "products", $product_id, $product_to_delete, null);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Produto excluído com sucesso.']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Produto não encontrado ou não pôde ser excluído.']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao excluir produto: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao excluir produto.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>