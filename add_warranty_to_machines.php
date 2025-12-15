<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/machines/add_warranty_to_machines.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
