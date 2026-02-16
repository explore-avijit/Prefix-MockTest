<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT * FROM questions ORDER BY id DESC LIMIT 50");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT);
?>
