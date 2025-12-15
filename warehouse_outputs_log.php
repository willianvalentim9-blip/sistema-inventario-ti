<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warehouse/warehouse_outputs_log.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
