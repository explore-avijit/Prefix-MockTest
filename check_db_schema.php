<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['Field'] === 'avatar') {
            echo "Column: avatar, Type: " . $col['Type'] . "\n";
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
