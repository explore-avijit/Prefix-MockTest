<?php
require_once 'includes/db.php';

// Disable output buffering
if (ob_get_level()) ob_end_clean();

echo "Starting Database Migration...\n";

try {
    $sql_file = __DIR__ . '/sql/update_users_table.sql';
    
    if (!file_exists($sql_file)) {
        die("Error: SQL file not found at $sql_file\n");
    }

    $sql = file_get_contents($sql_file);
    
    // PDO doesn't support multiple statements in checking mode sometimes, but for migration details it usually works 
    // IF we are not inside a transaction block or if the driver supports it.
    // However, DELIMITER syntax is client-side (mysql cli). PDO cannot execute DELIMITER //
    // So we must remove delimiters and split if complex, OR just run simple alters.
    
    // Since my SQL uses DELIMITER for the PROCEDURE, I need to handle that.
    // Actually, creating procedures via PDO is tricky due to delimiter parsing.
    // A better approach for this script is to just run the ALTERs one by one handling errors, 
    // OR simpy use the simple IF NOT EXISTS syntax if MySQL 8.0+, but older doesn't support IF NOT EXISTS in ALTER.
    
    // SIMPLIFIED APPROACH: Parsing logic or just raw execution if the driver allows.
    // Let's try to run it raw first, but DELIMITER keyword will fail in PDO.
    
    // NOTE: Re-writing the SQL logic in PHP is safer than parsing complex SQL files with Delimiters.
    
    echo "Updating schema via PHP logic to avoid Delimiter issues...\n";
    
    $cols_to_add = [
        'created_type' => "ENUM('admin', 'self') DEFAULT 'self'",
        'remark' => "TEXT",
        'verification_status' => "ENUM('pending', 'approved', 'declined') DEFAULT 'pending'",
        'account_status' => "ENUM('active', 'suspended', 'blocked', 'inactive') DEFAULT 'inactive'",
        'specialization' => "VARCHAR(255)",
        'average_score' => "DECIMAL(5,2) DEFAULT 0.00",
        'avatar' => "VARCHAR(255)",
        'joined_date' => "DATETIME DEFAULT CURRENT_TIMESTAMP"
    ];
    
    foreach ($cols_to_add as $col => $def) {
        // Check if column exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
        $stmt->execute([$col]);
        if ($stmt->fetchColumn() == 0) {
            echo "Adding column $col...\n";
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
        } else {
            echo "Column $col already exists.\n";
        }
    }
    
    // Update existing records
    echo "Migrating data...\n";
    $pdo->exec("UPDATE users SET created_type = 'admin', verification_status = 'approved', account_status = 'active', remark = 'System Admin' WHERE role = 'admin'");
    $pdo->exec("UPDATE users SET account_status = 'active', verification_status = 'approved' WHERE status = 'active' AND role != 'admin'");
    $pdo->exec("UPDATE users SET verification_status = 'pending', account_status = 'inactive' WHERE status = 'pending'");
    
    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    die("Migration Failed: " . $e->getMessage() . "\n");
}
?>
