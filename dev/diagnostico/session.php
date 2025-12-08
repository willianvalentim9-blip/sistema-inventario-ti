<?php
// ========================================
// DIAGNÓSTICO: SESSÕES
// ========================================

echo '<h4 class="mb-3"><i class="fas fa-user-lock me-2"></i>Informações de Sessão</h4>';

echo '<table class="table table-bordered">';

// 1. Status da sessão
echo '<tr>';
echo '<td width="30%"><strong>Status da Sessão</strong></td>';
if (session_status() === PHP_SESSION_ACTIVE) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Ativa</span></td>';
} else {
    echo '<td><span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>Inativa</span></td>';
}
echo '</tr>';

// 2. Session ID
echo '<tr>';
echo '<td><strong>Session ID</strong></td>';
echo '<td><code>' . (session_id() ?: 'Nenhum') . '</code></td>';
echo '</tr>';

// 3. Save Path
echo '<tr>';
echo '<td><strong>Session Save Path</strong></td>';
echo '<td><code>' . session_save_path() . '</code></td>';
echo '</tr>';

// 4. Cookie Lifetime
echo '<tr>';
echo '<td><strong>Cookie Lifetime</strong></td>';
echo '<td>' . ini_get('session.cookie_lifetime') . ' segundos</td>';
echo '</tr>';

// 5. GC Max Lifetime
echo '<tr>';
echo '<td><strong>GC Max Lifetime</strong></td>';
echo '<td>' . ini_get('session.gc_maxlifetime') . ' segundos</td>';
echo '</tr>';

echo '</table>';

// Teste de gravação
echo '<h5 class="mt-4 mb-3"><i class="fas fa-vial me-2"></i>Teste de Gravação</h5>';

$test_key = 'diagnostic_test_' . time();
$test_value = 'teste_' . mt_rand(1000, 9999);

$_SESSION[$test_key] = $test_value;

if (isset($_SESSION[$test_key]) && $_SESSION[$test_key] === $test_value) {
    echo '<div class="alert alert-success">';
    echo '<i class="fas fa-check-circle me-2"></i>';
    echo '<strong>Teste OK!</strong> Gravação e leitura de sessão funcionando.<br>';
    echo '<small>Valor gravado: <code>' . htmlspecialchars($test_value) . '</code></small>';
    echo '</div>';
    unset($_SESSION[$test_key]);
} else {
    echo '<div class="alert alert-danger">';
    echo '<i class="fas fa-times-circle me-2"></i>';
    echo '<strong>Erro!</strong> Não foi possível gravar/ler da sessão.';
    echo '</div>';
}

// Variáveis da sessão atual
if (!empty($_SESSION)) {
    echo '<h5 class="mt-4 mb-3"><i class="fas fa-list me-2"></i>Variáveis de Sessão Atuais</h5>';
    echo '<table class="table table-sm table-bordered">';
    echo '<thead><tr><th>Chave</th><th>Valor</th></tr></thead><tbody>';
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'diagnostic_test_') === 0) continue; // Pula testes
        echo '<tr>';
        echo '<td><code>' . htmlspecialchars($key) . '</code></td>';
        echo '<td><code>' . htmlspecialchars(substr(print_r($value, true), 0, 100)) . '</code></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-info mt-4">';
    echo '<i class="fas fa-info-circle me-2"></i>';
    echo 'Nenhuma variável de sessão definida.';
    echo '</div>';
}
?>
