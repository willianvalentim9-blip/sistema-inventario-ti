<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warranties/list_warranty_items.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
