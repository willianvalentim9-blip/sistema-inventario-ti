<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/products/delete_product.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
