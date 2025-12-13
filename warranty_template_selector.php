<?php
// Redirect automático para o novo local do arquivo
// Preserva query string (modal, etc)
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warranties/warranty_template_selector.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
