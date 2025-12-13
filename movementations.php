<?php
// Redirect automático para o novo local do arquivo
// Preserva query string (type, item_type, page, etc)
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/movements/movementations.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
