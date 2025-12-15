<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/machines/machine_history_view.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
