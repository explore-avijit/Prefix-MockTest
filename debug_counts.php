<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT status, count(*) as count FROM questions GROUP BY status");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT);
?>
