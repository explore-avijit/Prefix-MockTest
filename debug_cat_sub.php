<?php
require_once 'includes/db.php';
$stmt = $pdo->prepare("SELECT subject_name FROM category_subjects WHERE category_id = ?");
$stmt->execute([28]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
?>
