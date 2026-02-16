<?php
require_once 'includes/db.php';

$_POST['action'] = 'add';
$_POST['name'] = 'Test Expert';
$_POST['email'] = 'test.expert@example.com';
$_POST['phone'] = '1234567890';
$_POST['role'] = 'expert';
$_POST['status'] = 'active';
$_POST['specialization'] = 'Mathematics';
$_POST['profession'] = 'Professor';
$_POST['department'] = 'Math Dept';
$_POST['gender'] = 'male';

ob_start();
include 'api/admin/manage_user.php';
$output = ob_get_clean();
echo "RESULT: " . $output . "\n";

// Check if inserted
$stmt = $pdo->query("SELECT * FROM users WHERE email = 'test.expert@example.com'");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    echo "User Inserted: " . $user['id'] . "\n";
    $stmt = $pdo->query("SELECT * FROM experts WHERE user_id = " . $user['id']);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($profile) {
        echo "Profile Inserted: " . $profile['unique_id'] . "\n";
    } else {
        echo "Profile NOT Inserted\n";
    }
} else {
    echo "User NOT Inserted\n";
}
?>
