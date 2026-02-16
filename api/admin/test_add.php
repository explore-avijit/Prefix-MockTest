<?php
require_once '../../includes/db.php';

$_POST['action'] = 'add';
$_POST['name'] = 'Test Expert ' . mt_rand(100, 999);
$_POST['email'] = 'test.' . mt_rand(100, 999) . '@example.com';
$_POST['phone'] = '1234567890';
$_POST['role'] = 'expert';
$_POST['status'] = 'active';
$_POST['specialization'] = 'Mathematics';
$_POST['profession'] = 'Professor';
$_POST['department'] = 'Math Dept';
$_POST['gender'] = 'male';

ob_start();
include 'manage_user.php';
$output = ob_get_clean();
echo "RESULT: " . $output . "\n";
?>
