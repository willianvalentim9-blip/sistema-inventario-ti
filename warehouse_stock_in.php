<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warehouse/warehouse_stock_in.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
