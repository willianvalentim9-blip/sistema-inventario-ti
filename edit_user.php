<?php
// Redirect automático para o novo local do arquivo
// Preserva query string se existir
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/users/edit_user.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
