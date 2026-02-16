<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
try {
    $stmt = $pdo->query("SELECT 1");
    echo json_encode(['status' => 'success', 'message' => 'DB Connected']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
