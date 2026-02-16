<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT id, name, type FROM taxonomy");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT);
?>
