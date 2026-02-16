<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$email = $_POST['email'] ?? '';
$otp = $_POST['otp'] ?? '';

if (empty($email) || empty($otp)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and OTP are required']);
    exit;
}

try {
    $currentTime = date('Y-m-d H:i:s');
    
    // Check OTP and is_verified status
    $stmt = $pdo->prepare("SELECT id, full_name, role, unique_id FROM users WHERE email = ? AND otp_code = ? AND otp_expiry > ? AND is_verified = 0");
    $stmt->execute([$email, $otp, $currentTime]);
    $user = $stmt->fetch();

    if ($user) {
        $role = $user['role'];
        $userId = $user['id'];
        
        // Determination of initial account state after email verification
        $vStatus = 'approved';
        $aStatus = 'active';
        $legacyStatus = 'active';

        // Experts must wait for admin approval even after email verification
        if ($role === 'expert') {
            $vStatus = 'pending';
            $aStatus = 'inactive';
            $legacyStatus = 'pending';
        }

        // Mark as verified and set initial statuses
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_status = ?, account_status = ?, status = ?, otp_code = NULL, otp_expiry = NULL WHERE id = ?");
        $stmt->execute([$vStatus, $aStatus, $legacyStatus, $userId]);

        // Send Welcome Mail
        sendWelcomeMail($email, $user['full_name'], $role, $user['unique_id']);

        // Auto-login if approved
        if ($vStatus === 'approved') {
            $_SESSION['user_id'] = $userId;
            $_SESSION['unique_id'] = $user['unique_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $role;
            
            $redirect = ($role === 'expert') ? 'expert_dashboard/expert-dashboard.html' : 'student_dashboard/student-dashboard.html';

            echo json_encode([
                'status' => 'success', 
                'message' => 'Identity Verified! Welcome to Prefix.',
                'redirect' => $redirect,
                'auto_login' => true
            ]);
        } else {
            // Experts case
            echo json_encode([
                'status' => 'success', 
                'message' => 'Email verified! Your expert profile is now under admin review. We will notify you once approved.',
                'auto_login' => false
            ]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Verification failed: ' . $e->getMessage()]);
}
?>
