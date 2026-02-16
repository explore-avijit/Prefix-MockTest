<?php
require_once 'includes/db.php';
echo "USERS count: " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
echo "EXPERTS count: " . $pdo->query("SELECT COUNT(*) FROM experts")->fetchColumn() . "\n";
echo "ASPIRANTS count: " . $pdo->query("SELECT COUNT(*) FROM aspirants")->fetchColumn() . "\n";
?>
