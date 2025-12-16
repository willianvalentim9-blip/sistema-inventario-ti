<?php
// ========================================
// PÁGINA DE MOVIMENTAÇÃO DE PRODUTOS
// ========================================
// Esta página permite registrar entradas e saídas de produtos

// Inclui o arquivo de configuração
require_once '../../config.php';

// Verifica se o usuário está logado
requireLogin();

// Define variáveis para o template
$page_title = 'Movimentação de Produtos';

// Variáveis para controle de mensagens
$error_message = '';
$success_message = '';

// ========================================
// PROCESSAMENTO DO FORMULÁRIO
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_movement') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $movement_type = $_POST['movement_type'] ?? '';
        $quantity = intval($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        
        // Validações
        if ($product_id <= 0) {
            $error_message = 'Produto inválido.';
        } elseif (!in_array($movement_type, ['entrada', 'saida', 'ajuste'])) {
            $error_message = 'Tipo de movimentação inválido.';
        } elseif ($quantity <= 0) {
            $error_message = 'Quantidade deve ser maior que zero.';
        } else {
            try {
                $pdo = getConnection();
                $pdo->beginTransaction();
                
                // Busca o produto atual
                $stmt = $pdo->prepare("SELECT name, quantity, min_quantity, max_quantity FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if (!$product) {
                    throw new Exception('Produto não encontrado.');
                }
                
                $previous_quantity = $product['quantity'];
                
                // Calcula nova quantidade
                switch ($movement_type) {
                    case 'entrada':
                        $new_quantity = $previous_quantity + $quantity;
                        break;
                    case 'saida':
                        $new_quantity = $previous_quantity - $quantity;
                        if ($new_quantity < 0) {
                            throw new Exception('Quantidade insuficiente em estoque.');
                        }
                        break;
                    case 'ajuste':
                        $new_quantity = $quantity; // Quantidade absoluta
                        $quantity = $new_quantity - $previous_quantity; // Diferença para o histórico
                        break;
                }
                
                // Verifica limites
                if ($new_quantity > $product['max_quantity']) {
                    throw new Exception("Quantidade excede o limite máximo de {$product['max_quantity']} unidades.");
                }
                
                // Atualiza a quantidade do produto
                $stmt = $pdo->prepare("UPDATE products SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$new_quantity, $product_id]);
                
                // Registra a movimentação
                $stmt = $pdo->prepare("
                    INSERT INTO product_movements (product_id, movement_type, quantity, previous_quantity, new_quantity, reason, user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $product_id, 
                    $movement_type, 
                    abs($quantity), 
                    $previous_quantity, 
                    $new_quantity, 
                    $reason, 
                    $_SESSION['user_id']
                ]);
                
                $pdo->commit();
                
                $movement_label = [
                    'entrada' => 'Entrada',
                    'saida' => 'Saída',
                    'ajuste' => 'Ajuste'
                ][$movement_type];
                
                $success_message = "{$movement_label} registrada com sucesso! Produto: {$product['name']}";
                
                // Verifica alertas de estoque
                if ($new_quantity <= $product['min_quantity']) {
                    $success_message .= " <strong>ATENÇÃO:</strong> Estoque baixo ({$new_quantity} unidades).";
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = 'Erro ao registrar movimentação: ' . $e->getMessage();
            }
        }
    }
}

// ========================================
// CARREGA DADOS PARA A PÁGINA
// ========================================
try {
    $pdo = getConnection();
    
    // Lista de produtos para o select
    $products_stmt = $pdo->query("
        SELECT id, name, quantity, min_quantity, max_quantity 
        FROM products 
        ORDER BY name
    ");
    $products = $products_stmt->fetchAll();
    
    // Histórico recente de movimentações
    $movements_stmt = $pdo->query("
        SELECT 
            pm.*,
            p.name as product_name,
            u.username
        FROM product_movements pm
        LEFT JOIN products p ON pm.product_id = p.id
        LEFT JOIN users u ON pm.user_id = u.id
        ORDER BY pm.created_at DESC
        LIMIT 20
    ");
    $recent_movements = $movements_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = 'Erro ao carregar dados: ' . $e->getMessage();
    $products = [];
    $recent_movements = [];
}
?>

<?php include 'includes/header.php'; ?>

<!-- ========================================
     CABEÇALHO DA PÁGINA
     ======================================== -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 text-primary-custom">
        <i class="fas fa-exchange-alt me-2"></i>
        Movimentação de Produtos
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group">
            <a href="products.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar aos Produtos
            </a>
        </div>
    </div>
</div>

<!-- ========================================
     MENSAGENS DE FEEDBACK
     ======================================== -->
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-custom" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-custom" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<div class="row">
    <!-- ========================================
         FORMULÁRIO DE MOVIMENTAÇÃO
         ======================================== -->
    <div class="col-lg-6">
        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <i class="fas fa-plus-circle me-2"></i>
                Nova Movimentação
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_movement">
                    
                    <!-- Seleção do produto -->
                    <div class="mb-3">
                        <label for="product_id" class="form-label form-label-custom">
                            <i class="fas fa-box me-1"></i>
                            Produto
                        </label>
                        <select class="form-select form-control-custom" id="product_id" name="product_id" required onchange="updateProductInfo()">
                            <option value="">Selecione um produto</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-quantity="<?php echo $product['quantity']; ?>"
                                        data-min="<?php echo $product['min_quantity']; ?>"
                                        data-max="<?php echo $product['max_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> 
                                    (Estoque: <?php echo $product['quantity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Informações do produto selecionado -->
                    <div id="product-info" class="mb-3 p-3 bg-light rounded" style="display: none;">
                        <h6 class="mb-2">Informações do Produto:</h6>
                        <div class="row">
                            <div class="col-4">
                                <small class="text-muted">Estoque Atual:</small><br>
                                <span id="current-stock" class="fw-bold">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Mínimo:</small><br>
                                <span id="min-stock" class="text-warning">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Máximo:</small><br>
                                <span id="max-stock" class="text-info">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tipo de movimentação -->
                    <div class="mb-3">
                        <label for="movement_type" class="form-label form-label-custom">
                            <i class="fas fa-arrows-alt me-1"></i>
                            Tipo de Movimentação
                        </label>
                        <select class="form-select form-control-custom" id="movement_type" name="movement_type" required>
                            <option value="">Selecione o tipo</option>
                            <option value="entrada">
                                <i class="fas fa-arrow-up"></i> Entrada (Adicionar ao estoque)
                            </option>
                            <option value="saida">
                                <i class="fas fa-arrow-down"></i> Saída (Remover do estoque)
                            </option>
                            <option value="ajuste">
                                <i class="fas fa-edit"></i> Ajuste (Definir quantidade exata)
                            </option>
                        </select>
                    </div>
                    
                    <!-- Quantidade -->
                    <div class="mb-3">
                        <label for="quantity" class="form-label form-label-custom">
                            <i class="fas fa-hashtag me-1"></i>
                            Quantidade
                        </label>
                        <input type="number" 
                               class="form-control form-control-custom" 
                               id="quantity" 
                               name="quantity" 
                               min="1" 
                               required>
                        <div class="form-text">
                            <span id="quantity-help">Digite a quantidade para a movimentação</span>
                        </div>
                    </div>
                    
                    <!-- Motivo/Observação -->
                    <div class="mb-3">
                        <label for="reason" class="form-label form-label-custom">
                            <i class="fas fa-comment me-1"></i>
                            Motivo/Observação
                        </label>
                        <textarea class="form-control form-control-custom" 
                                  id="reason" 
                                  name="reason" 
                                  rows="3" 
                                  placeholder="Descreva o motivo da movimentação (opcional)"></textarea>
                    </div>
                    
                    <!-- Botão de envio -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>
                            Registrar Movimentação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ========================================
         HISTÓRICO RECENTE
         ======================================== -->
    <div class="col-lg-6">
        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <i class="fas fa-history me-2"></i>
                Movimentações Recentes
            </div>
            <div class="card-body">
                <?php if (empty($recent_movements)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Nenhuma movimentação registrada ainda.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Tipo</th>
                                    <th>Qtd</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_movements as $movement): ?>
                                    <tr>
                                        <td>
                                            <small><?php echo htmlspecialchars($movement['product_name']); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $type_icons = [
                                                'entrada' => '<i class="fas fa-arrow-up text-success"></i>',
                                                'saida' => '<i class="fas fa-arrow-down text-danger"></i>',
                                                'ajuste' => '<i class="fas fa-edit text-warning"></i>'
                                            ];
                                            echo $type_icons[$movement['movement_type']] ?? '';
                                            ?>
                                        </td>
                                        <td>
                                            <small><?php echo number_format($movement['quantity']); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo date('d/m H:i', strtotime($movement['created_at'])); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="movement_history.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list me-1"></i>
                            Ver Histórico Completo
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================
     JAVASCRIPT ESPECÍFICO DA PÁGINA
     ======================================== -->
<script>
function updateProductInfo() {
    const select = document.getElementById('product_id');
    const infoDiv = document.getElementById('product-info');
    const currentStock = document.getElementById('current-stock');
    const minStock = document.getElementById('min-stock');
    const maxStock = document.getElementById('max-stock');
    
    if (select.value) {
        const option = select.options[select.selectedIndex];
        const quantity = option.dataset.quantity;
        const min = option.dataset.min;
        const max = option.dataset.max;
        
        currentStock.textContent = quantity;
        minStock.textContent = min;
        maxStock.textContent = max;
        
        // Aplica cores baseadas no estoque
        if (parseInt(quantity) <= parseInt(min)) {
            currentStock.className = 'fw-bold text-danger';
        } else if (parseInt(quantity) >= parseInt(max)) {
            currentStock.className = 'fw-bold text-warning';
        } else {
            currentStock.className = 'fw-bold text-success';
        }
        
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}

// Atualiza texto de ajuda baseado no tipo de movimentação
document.getElementById('movement_type').addEventListener('change', function() {
    const quantityHelp = document.getElementById('quantity-help');
    const quantityInput = document.getElementById('quantity');
    
    switch (this.value) {
        case 'entrada':
            quantityHelp.textContent = 'Quantidade a ser adicionada ao estoque';
            quantityInput.placeholder = 'Ex: 10';
            break;
        case 'saida':
            quantityHelp.textContent = 'Quantidade a ser removida do estoque';
            quantityInput.placeholder = 'Ex: 5';
            break;
        case 'ajuste':
            quantityHelp.textContent = 'Quantidade total que deve ficar no estoque';
            quantityInput.placeholder = 'Ex: 25';
            break;
        default:
            quantityHelp.textContent = 'Digite a quantidade para a movimentação';
            quantityInput.placeholder = '';
    }
});
</script>

<?php include 'includes/footer.php'; ?>

