<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warranties/get_warranty_suppliers.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
