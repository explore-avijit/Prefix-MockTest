<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT count(*) FROM questions");
echo "TOTAL_QUESTIONS_IN_DB: " . $stmt->fetchColumn() . "\n";
?>
