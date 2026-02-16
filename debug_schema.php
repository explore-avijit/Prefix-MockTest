<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE users");
    echo "USERS TABLE:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
    $stmt = $pdo->query("DESCRIBE experts");
    echo "\nEXPERTS TABLE:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
