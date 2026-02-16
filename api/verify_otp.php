<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$email = $_POST['email'] ?? '';
$otp = $_POST['otp'] ?? '';
$role = $_POST['role'] ?? 'aspirant';

if (empty($email) || empty($otp)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and OTP are required']);
    exit;
}

try {
    $currentTime = date('Y-m-d H:i:s');
    
    // Unified query using the users table which now contains all necessary fields
    $stmt = $pdo->prepare("SELECT id, full_name, verification_status, account_status, unique_id FROM users WHERE email = ? AND role = ? AND otp_code = ? AND otp_expiry > ?");
    $stmt->execute([$email, $role, $otp, $currentTime]);
    $user = $stmt->fetch();

    if ($user) {
        // Multi-level Status Check to match login.php and admin logic
        if ($user['verification_status'] !== 'approved') {
            $msg = ($user['verification_status'] === 'declined') ? 'Your account was declined.' : 'Your account is under review by admin.';
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit;
        }
        
        if ($user['account_status'] === 'suspended') {
            echo json_encode(['status' => 'error', 'message' => 'Your account has been suspended. Please contact admin.']);
            exit;
        }

        if ($user['account_status'] !== 'active') {
            echo json_encode(['status' => 'error', 'message' => 'Your account is not active. Status: ' . ($user['account_status'] ?: 'Inactive')]);
            exit;
        }

        // Clear OTP after successful use
        $stmt = $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL, is_verified = 1 WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['unique_id'] = $user['unique_id'] ?: (($role === 'admin') ? 'ADMIN' : 'EX_PENDING');
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $role;

        // Determine redirect URL
        $redirect = 'student_dashboard/student-dashboard.html';
        if ($role === 'admin') $redirect = 'admin_dashboard/admin-dashboard.html';
        if ($role === 'expert') $redirect = 'expert_dashboard/expert-dashboard.html';

        echo json_encode([
            'status' => 'success', 
            'message' => 'Login successful',
            'redirect' => $redirect,
            'user' => [
                'name' => $user['full_name'],
                'role' => $role,
                'unique_id' => $_SESSION['unique_id']
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Verification failed: ' . $e->getMessage()]);
}
?>
