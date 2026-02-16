<?php
require_once 'includes/db.php';

try {
    $sql = "ALTER TABLE users 
            ADD COLUMN academic_level VARCHAR(255) DEFAULT NULL,
            ADD COLUMN subject VARCHAR(255) DEFAULT NULL,
            ADD COLUMN class VARCHAR(255) DEFAULT NULL,
            ADD COLUMN gender ENUM('male', 'female', 'other') DEFAULT NULL,
            ADD COLUMN profession VARCHAR(255) DEFAULT NULL,
            ADD COLUMN department VARCHAR(255) DEFAULT NULL,
            ADD COLUMN category VARCHAR(255) DEFAULT NULL";
            
    $pdo->exec($sql);
    echo "Schema updated successfully!";
} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>
