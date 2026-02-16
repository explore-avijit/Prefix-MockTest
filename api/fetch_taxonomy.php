<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['type'])) {
    echo json_encode(['error' => 'Type is required']);
    exit;
}

$type = $_GET['type'];
// Alias mapping: 'class' in frontend refers to 'subject' in taxonomy table for students
$dbType = $type;
if ($type === 'class') $dbType = 'subject';

$allowedTypes = ['subject', 'category'];

if (!in_array($dbType, $allowedTypes)) {
    echo json_encode(['error' => 'Invalid type']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM taxonomy WHERE type = ? ORDER BY name ASC");
    $stmt->execute([$dbType]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
