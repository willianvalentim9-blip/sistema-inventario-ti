<?php
// Redirect automático para o novo local do arquivo
// Preserva query string se existir
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/movements/movementations_saida.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
