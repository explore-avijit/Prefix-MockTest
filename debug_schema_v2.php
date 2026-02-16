<?php
require_once 'includes/db.php';
function printTable($pdo, $name) {
    echo "Table: $name\n";
    $stmt = $pdo->query("DESCRIBE $name");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
    }
    echo "\n";
}
printTable($pdo, 'users');
printTable($pdo, 'experts');
printTable($pdo, 'aspirants');
?>
