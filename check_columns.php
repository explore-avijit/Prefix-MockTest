<?php
require_once 'includes/db.php';
echo "USERS:\n";
$stmt = $pdo->query("DESCRIBE users");
while($r=$stmt->fetch()) echo $r['Field']."\n";

echo "\nEXPERTS:\n";
$stmt = $pdo->query("DESCRIBE experts");
while($r=$stmt->fetch()) echo $r['Field']."\n";
?>
