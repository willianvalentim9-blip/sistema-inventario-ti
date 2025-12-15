<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warehouse/edit_warehouse.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
