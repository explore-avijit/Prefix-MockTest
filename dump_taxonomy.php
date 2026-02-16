<?php
require_once 'includes/db.php';
try {
    foreach(['taxonomy'] as $t) {
        $stmt = $pdo->query("SHOW CREATE TABLE $t");
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "TABLE: $t\n";
        echo $res['Create Table'] . "\n\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
