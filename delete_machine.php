<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/machines/delete_machine.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
