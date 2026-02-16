<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'prefix_mocktest_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $full_name = 'Test User ' . mt_rand(100, 999);
    $email = 'test' . mt_rand(100, 999) . '@example.com';
    $role = 'expert';

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, role, status, is_verified) VALUES (?, ?, ?, 'active', 1)");
    $stmt->execute([$full_name, $email, $role]);
    $user_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO experts (user_id, unique_id, specialization) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, 'EX'.mt_rand(1000,9999), 'Science']);
    
    $pdo->commit();
    echo "SUCCESS: User $user_id created\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
