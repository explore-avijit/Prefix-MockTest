<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE users");
$cols = [];
while($r = $stmt->fetch()) $cols[] = $r['Field'];
echo "COLS: " . implode(', ', $cols) . "\n";
?>
