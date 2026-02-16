<?php
require_once 'includes/db.php';
$columns = ["academic_level", "subject", "class", "specialization", "gender", "profession", "department", "category"];
$stmt = $pdo->query("DESCRIBE users");
$existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($columns as $col) {
    if (in_array($col, $existing)) {
        echo "$col exists\n";
    } else {
        echo "$col MISSING\n";
    }
}
?>
