<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? 'aspirant';

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

try {
    // Optimized: Single query for user data and status
    $stmt = $pdo->prepare("SELECT id, role, verification_status, account_status FROM users WHERE email = ? AND role = ?");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'No account found with this email as ' . ucfirst($role)]);
        exit;
    }

    // Role-based Access Control
    if ($user['role'] !== 'admin') {
        if ($user['verification_status'] !== 'approved') {
             if ($user['verification_status'] === 'declined') {
                 echo json_encode(['status' => 'error', 'message' => 'Your registration was declined.']);
                 exit;
             }
             echo json_encode(['status' => 'error', 'message' => 'Your account is under review by admin.']);
             exit;
        }
        
        if ($user['account_status'] === 'suspended') {
            echo json_encode(['status' => 'error', 'message' => 'Your account has been suspended. Please contact admin.']);
            exit;
        }
        
        if ($user['account_status'] === 'blocked') {
             echo json_encode(['status' => 'error', 'message' => 'Your account is blocked.']);
             exit;
        }
        
        if ($user['account_status'] !== 'active') {
             echo json_encode(['status' => 'error', 'message' => 'Your account is not active.']);
             exit;
        }
    }

    // Generate 4-digit OTP
    $otp = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Update OTP in 'users' table
    $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
    $stmt->execute([$otp, $expiry, $user['id']]);

    // Send Mail
    if (sendOTPMail($email, $otp)) {
        echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully to your email']);
    } else {
        // Fallback for demo if mail server is not configured
        echo json_encode(['status' => 'success', 'message' => 'OTP sent (Demo Mode: ' . $otp . ')', 'debug_otp' => $otp]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Login failed: ' . $e->getMessage()]);
}
?>
