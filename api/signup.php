<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$full_name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$role = $_POST['role'] ?? 'aspirant'; // student, aspirant, or expert

if (empty($full_name) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Name and Email are required']);
    exit;
}

try {
    // 1. Check if email already exists in 'users'
    $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['is_verified']) {
            echo json_encode(['status' => 'error', 'message' => 'Email already registered and verified. Please login.']);
            exit;
        } else {
            // Allow resending OTP for unverified account
            $otp = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
            $stmt->execute([$otp, $expiry, $existing['id']]);
            
            if (sendOTPMail($email, $otp)) {
                echo json_encode(['status' => 'otp_sent', 'message' => 'Verification code resent to your email.']);
            } else {
                echo json_encode(['status' => 'otp_sent', 'message' => 'Verification code resent (Demo Mode: ' . $otp . ')', 'debug_otp' => $otp]);
            }
            exit;
        }
    }

    $pdo->beginTransaction();

    // 2. Generate Unique ID
    $table = ($role === 'expert') ? 'experts' : 'aspirants';
    $prefix = ($role === 'expert') ? 'EX' : 'AS';
    // generateUniqueID now checks checking 'users' table, which is correct
    $unique_id = generateUniqueID($pdo, $table, $prefix);

    // 3. Insert into 'users' table with Unique ID and Remark
    // Add OTP fields
    $otp = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, role, unique_id, remark, status, verification_status, account_status, is_verified, otp_code, otp_expiry) VALUES (?, ?, ?, ?, ?, 'created by self', 'pending', 'pending', 'inactive', 0, ?, ?)");
    $stmt->execute([$full_name, $email, $phone, $role, $unique_id, $otp, $expiry]);
    $user_id = $pdo->lastInsertId();

    if ($role === 'expert') {
        $specialization = $_POST['specialization'] ?? '';
        $degree = $_POST['degree'] ?? '';
        $profession = $_POST['profession'] ?? '';
        $department = $_POST['dept'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO experts (user_id, unique_id, specialization, degree, profession, department) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $unique_id, $specialization, $degree, $profession, $department]);
    } else {
        $category = ($role === 'student') ? 'student' : 'aspirant';
        $stmt = $pdo->prepare("INSERT INTO aspirants (user_id, unique_id, category) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $unique_id, $category]);
    }

    $pdo->commit();

    // 4. Send OTP Email (Reuse the existing OTP mail template)
    if (sendOTPMail($email, $otp)) {
        echo json_encode(['status' => 'otp_sent', 'message' => 'Registration OTP sent to your email.']);
    } else {
        // Fallback for demo
        echo json_encode(['status' => 'otp_sent', 'message' => 'OTP sent (Demo Mode: ' . $otp . ')', 'debug_otp' => $otp]);
    }

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Signup Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
}
?>
