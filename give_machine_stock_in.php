<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/machines/give_machine_stock_in.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
