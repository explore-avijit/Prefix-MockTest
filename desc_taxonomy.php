<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESC taxonomy");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " " . $row['Type'] . "\n";
}
?>
