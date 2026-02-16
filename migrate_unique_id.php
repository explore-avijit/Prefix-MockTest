<?php
require_once 'includes/db.php';

try {
    echo "Starting database update...\n";

    // 1. Add unique_id to users table if not exists
    $cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('unique_id', $cols)) {
        echo "Adding unique_id to users table...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN unique_id VARCHAR(50) AFTER id"); // initially nullable to allow population
        $pdo->exec("ALTER TABLE users ADD UNIQUE INDEX (unique_id)");
    } else {
        echo "unique_id already in users table.\n";
    }

    // 2. Populate unique_id from child tables
    echo "Migrating unique_ids from experts...\n";
    $pdo->exec("UPDATE users u JOIN experts e ON u.id = e.user_id SET u.unique_id = e.unique_id WHERE u.unique_id IS NULL");
    
    echo "Migrating unique_ids from aspirants...\n";
    $pdo->exec("UPDATE users u JOIN aspirants a ON u.id = a.user_id SET u.unique_id = a.unique_id WHERE u.unique_id IS NULL");

    // 3. Ensure Foreign Keys
    // Check if FK exists for experts
    $hasFK = false;
    $createExperts = $pdo->query("SHOW CREATE TABLE experts")->fetchColumn(1);
    if (strpos($createExperts, 'CONSTRAINT') !== false && strpos($createExperts, 'FOREIGN KEY') !== false) {
        $hasFK = true;
    }
    
    if (!$hasFK) {
         echo "Adding Foreign Key to experts...\n";
         $pdo->exec("ALTER TABLE experts ADD CONSTRAINT fk_experts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
    } else {
        echo "Foreign Key on experts likely exists.\n";
    }

    // Check FK for aspirants
    $hasFK = false;
    $createAsp = $pdo->query("SHOW CREATE TABLE aspirants")->fetchColumn(1);
    if (strpos($createAsp, 'CONSTRAINT') !== false && strpos($createAsp, 'FOREIGN KEY') !== false) {
        $hasFK = true;
    }
    
    if (!$hasFK) {
         echo "Adding Foreign Key to aspirants...\n";
         $pdo->exec("ALTER TABLE aspirants ADD CONSTRAINT fk_aspirants_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
    } else {
        echo "Foreign Key on aspirants likely exists.\n";
    }

    echo "Database update completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
