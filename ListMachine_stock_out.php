<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/machines/ListMachine_stock_out.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
