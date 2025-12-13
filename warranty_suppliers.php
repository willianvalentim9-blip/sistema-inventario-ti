<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warranties/warranty_suppliers.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
