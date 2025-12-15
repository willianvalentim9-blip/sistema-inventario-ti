<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warehouse/warehouse.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
