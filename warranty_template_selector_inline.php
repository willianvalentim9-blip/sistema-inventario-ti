<?php
// Redirect automático para o novo local do arquivo
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'modules/warranties/warranty_template_selector_inline.php' . ($query ? '?' . $query : '');
header('Location: ' . $location);
exit();
