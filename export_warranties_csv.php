<?php
// Redirect automático para o novo local do arquivo
// Preserva query string (type, format, etc)
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warranties/export_warranties_csv.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
