<?php
// ========================================
// DIAGNÓSTICO: UPLOAD DE ARQUIVOS
// ========================================

echo '<h4 class="mb-3"><i class="fas fa-upload me-2"></i>Configurações de Upload</h4>';

echo '<table class="table table-bordered">';

// 1. Upload habilitado
$file_uploads = ini_get('file_uploads');
echo '<tr>';
echo '<td width="30%"><strong>File Uploads</strong></td>';
if ($file_uploads) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Habilitado</span></td>';
} else {
    echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Desabilitado</span></td>';
}
echo '</tr>';

// 2. Tamanho máximo
echo '<tr>';
echo '<td><strong>Upload Max Filesize</strong></td>';
echo '<td>' . ini_get('upload_max_filesize') . '</td>';
echo '</tr>';

echo '<tr>';
echo '<td><strong>Post Max Size</strong></td>';
echo '<td>' . ini_get('post_max_size') . '</td>';
echo '</tr>';

echo '<tr>';
echo '<td><strong>Max File Uploads</strong></td>';
echo '<td>' . ini_get('max_file_uploads') . ' arquivo(s)</td>';
echo '</tr>';

// 3. Extensão FileInfo
$fileinfo_ok = extension_loaded('fileinfo');
echo '<tr>';
echo '<td><strong>Extensão FileInfo</strong></td>';
if ($fileinfo_ok) {
    echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Ativa</span><br>';
    echo '<small class="text-muted">Validação MIME disponível</small></td>';
} else {
    echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Inativa</span><br>';
    echo '<small class="text-muted">Descomente extension=fileinfo no php.ini</small></td>';
}
echo '</tr>';

echo '</table>';

// Verificar pastas de upload
echo '<h5 class="mt-4 mb-3"><i class="fas fa-folder me-2"></i>Pastas de Upload</h5>';

$upload_folders = [
    'uploads/' => 'Raiz de uploads',
    'uploads/products/' => 'Imagens de produtos',
    'uploads/machines/' => 'Imagens de máquinas',
    'uploads/avatars/' => 'Avatares de usuários'
];

echo '<table class="table table-bordered">';
echo '<thead class="table-light"><tr><th>Pasta</th><th>Existe</th><th>Permissão Escrita</th></tr></thead>';
echo '<tbody>';

$all_ok = true;
foreach ($upload_folders as $folder => $description) {
    $full_path = realpath(__DIR__ . '/../' . $folder);

    echo '<tr>';
    echo '<td><strong>' . htmlspecialchars($description) . '</strong><br><small class="text-muted">' . $folder . '</small></td>';

    if (is_dir($full_path)) {
        echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Sim</span></td>';

        if (is_writable($full_path)) {
            echo '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Gravável</span></td>';
        } else {
            $all_ok = false;
            echo '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Sem permissão</span></td>';
        }
    } else {
        $all_ok = false;
        echo '<td><span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>Não</span></td>';
        echo '<td>-</td>';
    }
    echo '</tr>';
}
echo '</tbody></table>';

// Teste de validação MIME
if ($fileinfo_ok) {
    echo '<h5 class="mt-4 mb-3"><i class="fas fa-vial me-2"></i>Teste de Validação MIME</h5>';

    $test_cases = [
        'test.jpg' => ['image/jpeg', 'Imagem JPEG'],
        'test.png' => ['image/png', 'Imagem PNG'],
        'test.pdf' => ['application/pdf', 'Documento PDF'],
        'test.txt' => ['text/plain', 'Texto Simples']
    ];

    echo '<table class="table table-sm table-bordered">';
    echo '<thead><tr><th>Tipo de Arquivo</th><th>MIME Esperado</th><th>Status</th></tr></thead><tbody>';

    foreach ($test_cases as $filename => $data) {
        list($expected_mime, $description) = $data;

        echo '<tr>';
        echo '<td>' . htmlspecialchars($description) . '<br><small class="text-muted">' . $filename . '</small></td>';
        echo '<td><code>' . $expected_mime . '</code></td>';
        echo '<td><span class="badge bg-info"><i class="fas fa-check me-1"></i>Suportado</span></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

// Resultado final
if ($all_ok && $file_uploads && $fileinfo_ok) {
    echo '<div class="alert alert-success mt-4">';
    echo '<i class="fas fa-check-circle me-2"></i>';
    echo '<strong>Sistema de Upload OK!</strong> Todas as configurações estão corretas.';
    echo '</div>';
} else {
    echo '<div class="alert alert-warning mt-4">';
    echo '<i class="fas fa-exclamation-triangle me-2"></i>';
    echo '<strong>Atenção!</strong> Alguns recursos de upload podem não funcionar corretamente.';
    echo '</div>';
}
?>
