<?php
// Redirect automático para o novo local do arquivo
// Preserva query string (id, modal, etc)
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warranties/warranty_history_view.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
