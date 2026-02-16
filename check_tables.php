<?php
require_once 'includes/db.php';
try {
    $tables = ['users', 'experts', 'aspirants'];
    foreach ($tables as $t) {
        $res = $pdo->query("SHOW CREATE TABLE $t")->fetch(PDO::FETCH_ASSOC);
        echo "TABLE: $t\n";
        echo $res['Create Table'] . "\n\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
