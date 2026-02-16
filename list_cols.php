<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE experts");
$cols = [];
while($r = $stmt->fetch()) $cols[] = $r['Field'];
echo "COLS: " . implode(', ', $cols) . "\n";
?>
