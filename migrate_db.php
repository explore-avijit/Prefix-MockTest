<?php
require_once 'includes/db.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS experts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        unique_id VARCHAR(10) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        specialization VARCHAR(100),
        degree VARCHAR(100),
        profession VARCHAR(100),
        department VARCHAR(100),
        status ENUM('active', 'pending', 'suspended', 'rejected') DEFAULT 'pending',
        otp_code VARCHAR(10),
        otp_expiry DATETIME,
        is_verified TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS aspirants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        unique_id VARCHAR(10) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        category ENUM('student', 'aspirant') DEFAULT 'aspirant',
        status ENUM('active', 'pending', 'suspended', 'rejected') DEFAULT 'active',
        otp_code VARCHAR(10),
        otp_expiry DATETIME,
        is_verified TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $q) {
    try {
        $pdo->exec($q);
        echo "Executed: " . substr($q, 0, 50) . "...\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
