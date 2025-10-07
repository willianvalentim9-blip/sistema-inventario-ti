<?php
require_once 'config.php';
requireLogin();

$machine_id = intval($_GET['id'] ?? 0);
$machine = null;
$error_message = '';

if ($machine_id > 0) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM ready_machines WHERE id = ?");
        $stmt->execute([$machine_id]);
        $machine = $stmt->fetch();
        if (!$machine) {
            $error_message = 'Máquina não encontrada.';
        }
    } catch (PDOException $e) {
        $error_message = 'Erro ao buscar máquina: ' . $e->getMessage();
    }
} else {
    $error_message = 'ID da máquina inválido.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $machine) {
    // A lógica de POST será tratada via AJAX na página principal,
    // este arquivo é apenas para exibir o formulário.
    // No entanto, podemos manter a lógica aqui para o caso de um submit direto.
    // ...
}
?>

<?php if ($error_message): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php elseif ($machine): ?>
    <form id="stockOutForm" action="process_stock_out.php" method="POST">
        <input type="hidden" name="item_id" value="<?php echo $machine['id']; ?>">
        <input type="hidden" name="item_type" value="machine">

        <div class="mb-3">
            <label class="form-label">Máquina:</label>
            <p><strong><?php echo htmlspecialchars($machine['name']); ?></strong></p>
        </div>

        <div class="mb-3">
            <label for="reason" class="form-label">Motivo da Baixa*</label>
            <select class="form-select" id="reason" name="reason" required>
                <option value="">Selecione um motivo...</option>
                <option value="Venda">Venda</option>
                <option value="Descarte por defeito">Descarte por defeito</option>
                <option value="Uso interno">Uso interno</option>
                <option value="Devolução ao fornecedor">Devolução ao fornecedor</option>
                <option value="Perda ou roubo">Perda ou roubo</option>
                <option value="Outro">Outro (especificar nas notas)</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="notes" class="form-label">Notas Adicionais</label>
            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Detalhes adicionais, como nome do cliente, número da nota fiscal, etc."></textarea>
        </div>
        
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">Confirmar Baixa</button>
        </div>
    </form>
<?php endif; ?>