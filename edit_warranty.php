<?php
// Redirect automático para o novo local do arquivo
// Preserva query string (id, modal, etc)
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warranties/edit_warranty.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
