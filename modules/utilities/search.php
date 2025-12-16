<?php
require_once '../../config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'results' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? '';

    if (!empty($code)) {
        $conn = getDbConnection();

        // Busca em produtos
        $stmt = $conn->prepare("SELECT *, 'product' as type FROM products WHERE serial_number = ? OR barcode = ? OR qr_code = ?");
        $stmt->bind_param("sss", $code, $code, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['view_url'] = 'product_details.php?id=' . $row['id'];
            $response['results'][] = $row;
        }
        $stmt->close();

        // Busca em máquinas
        $stmt = $conn->prepare("SELECT *, 'machine' as type FROM machines WHERE serial_number = ? OR barcode = ? OR qr_code = ?");
        $stmt->bind_param("sss", $code, $code, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['view_url'] = 'machine_details.php?id=' . $row['id'];
            $response['results'][] = $row;
        }
        $stmt->close();

        $conn->close();

        if (!empty($response['results'])) {
            $response['success'] = true;
        }
    }
}

echo json_encode($response);
?>

