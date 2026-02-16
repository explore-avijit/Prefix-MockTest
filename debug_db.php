<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT role, language, academic_level, category, subject, status FROM questions LIMIT 20");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT);
?>
