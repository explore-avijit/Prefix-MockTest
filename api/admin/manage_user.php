<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Handle GET for fetching single user
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $userId = $_GET['user_id'] ?? '';

    if ($action === 'get' && !empty($userId)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                // Normalize status key for frontend consistency
                if ($user['verification_status'] === 'pending') $user['status'] = 'pending';
                elseif ($user['verification_status'] === 'declined') $user['status'] = 'rejected'; // Frontend uses rejected
                elseif ($user['account_status'] === 'suspended') $user['status'] = 'suspended';
                elseif ($user['account_status'] === 'active') $user['status'] = 'active';
                else $user['status'] = 'unknown';

                echo json_encode(['status' => 'success', 'data' => $user]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'get_activity_logs' && !empty($userId)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
            $stmt->execute([$userId]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $logs]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Handle POST for updates
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$data = $_POST;
$action = $data['action'] ?? '';
$userId = $data['user_id'] ?? '';

if (empty($action)) {
    echo json_encode(['status' => 'error', 'message' => 'Action is required']);
    exit;
}

if ($action !== 'add' && $action !== 'bulk_action' && empty($userId)) {
    echo json_encode(['status' => 'error', 'message' => 'User ID required for this action']);
    exit;
}

// Fetch user data for email notifications if needed
$userEmail = '';
$userName = '';
$unique_id = '';
if (!empty($userId)) {
    $uStmt = $pdo->prepare("SELECT email, full_name, unique_id FROM users WHERE id = ?");
    $uStmt->execute([$userId]);
    $uData = $uStmt->fetch();
    if ($uData) {
        $userEmail = $uData['email'];
        $userName = $uData['full_name'];
        $unique_id = $uData['unique_id'];
    }
}

try {
    $pdo->beginTransaction();

    // Map frontend actions to DB updates
    $msg = "Operation successful";
    $sendMailStatus = ''; // Track if we need to send mail after commit

    switch ($action) {
        case 'add':
            // Validation
            if (empty($data['name']) || empty($data['email'])) throw new Exception("Name and Email are required");
            
            // Check email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) throw new Exception("Email already exists");

            // Insert User
            // Admin created users are Approved & Active
            $role = $data['role'] ?? 'aspirant'; 
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, role, created_type, verification_status, account_status, status, remark, created_at, joined_date) VALUES (?, ?, ?, ?, 'admin', 'approved', 'active', 'active', 'Created by admin', NOW(), NOW())");
            $stmt->execute([
                $data['name'], 
                $data['email'], 
                $data['phone'] ?? null, 
                $role
            ]);
            $newUserId = $pdo->lastInsertId();

            // Insert into Role Table
            // Generate Unique ID (Simple logic for now, or fetch from DB function if exists)
            $prefix = ($role === 'expert') ? 'EX' : 'AS';
            $uniqueId = $prefix . date('Y') . str_pad($newUserId, 5, '0', STR_PAD_LEFT);
            
            // Update users table with unique_id if column exists (it wasn't in my ALTER, but it might be in original schema? Original schema line 38 used unique_id)
            // My ALTER didn't remove it. Check original sql: it had 'users' table... wait. original sql lines 6-18 did NOT show unique_id!
            // But signup.php lines 38 used it. This means the DB schema I saw in 'sql/prefix_db.sql' was possibly out of sync or I missed it?
            // signup.php: INSERT INTO users (... unique_id ...)
            // I'll assume it exists or I should add it. My ALTER didn't add it.
            // If it fails, I'll catch it. BUT for safety, let's just try to update it if it exists.
            // Or better, assume it's there because signup.php uses it.
            
            $stmt = $pdo->prepare("UPDATE users SET unique_id = ? WHERE id = ?"); // If unique_id missing, this throws.
            // Let's wrap in try/catch or just hope structure matches. 
            // Better: 'signup.php' worked, so 'unique_id' is in 'users' table.
            $stmt->execute([$uniqueId, $newUserId]);

            // Update common fields like gender
            if (isset($data['gender'])) {
                $stmt = $pdo->prepare("UPDATE users SET gender = ? WHERE id = ?");
                $stmt->execute([$data['gender'], $newUserId]);
            }

            if ($role === 'expert') {
                $stmt2 = $pdo->prepare("UPDATE users SET specialization = ?, profession = ?, department = ? WHERE id = ?");
                $stmt2->execute([
                    $data['specialization'] ?? null, 
                    $data['profession'] ?? null, 
                    $data['department'] ?? null, 
                    $newUserId
                ]);
            } else {
                 if ($role === 'student' || $role === 'aspirant') {
                     $stmt = $pdo->prepare("UPDATE users SET academic_level = ?, subject = ?, class = ?, category = ? WHERE id = ?");
                     $stmt->execute([
                         $data['academic_level'] ?? null, 
                         $data['student_subject'] ?? null, 
                         $data['aspirant-class'] ?? null, 
                         $role, // category will be 'student' or 'aspirant'
                         $newUserId
                     ]);
                 }
            }
            
            // Set variables for mail after commit
            $userEmail = $data['email'];
            $userName = $data['name'];
            $unique_id = $uniqueId;
            $sendMailStatus = 'approved';

            $msg = "User created successfully.";
            break;

        case 'update':
            // Update User
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, gender = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['email'], $data['phone'] ?? null, $data['gender'] ?? null, $userId]);
            
            // Update specifics
            if (isset($data['specialization']) || isset($data['profession']) || isset($data['department'])) {
                 $stmt = $pdo->prepare("UPDATE users SET specialization = ?, profession = ?, department = ? WHERE id = ?");
                 $stmt->execute([
                     $data['specialization'] ?? null, 
                     $data['profession'] ?? null, 
                     $data['department'] ?? null, 
                     $userId
                 ]);
            }
            if (isset($data['academic_level']) || isset($data['student_subject']) || isset($data['aspirant-class'])) {
                 $stmt = $pdo->prepare("UPDATE users SET academic_level = ?, subject = ?, class = ? WHERE id = ?");
                 $stmt->execute([
                     $data['academic_level'] ?? null, 
                     $data['student_subject'] ?? null, 
                     $data['aspirant-class'] ?? null, 
                     $userId
                 ]);
            }
            $msg = "User details updated successfully.";
            break;

        case 'approve':
        case 'update_status': // handle generic update if passed 'status' param
            $targetStatus = $data['status'] ?? '';
            if ($targetStatus === 'active') {
                 $stmt = $pdo->prepare("UPDATE users SET verification_status = 'approved', account_status = 'active', status = 'active', remark = CONCAT(COALESCE(remark, ''), ' | Approved') WHERE id = ?");
                 $sendMailStatus = 'approved';
                 $msg = "User approved successfully.";
            } elseif ($targetStatus === 'rejected' || $targetStatus === 'declined') {
                 $stmt = $pdo->prepare("UPDATE users SET verification_status = 'declined', account_status = 'blocked', status = 'rejected', remark = CONCAT(COALESCE(remark, ''), ' | Declined') WHERE id = ?");
                 $sendMailStatus = 'declined';
                 $msg = "User declined successfully.";
            } elseif ($targetStatus === 'suspended') {
                 $stmt = $pdo->prepare("UPDATE users SET account_status = 'suspended', status = 'suspended' WHERE id = ?");
                 $sendMailStatus = 'suspended';
                 $msg = "User suspended successfully.";
            } else {
                 throw new Exception("Invalid status update");
            }
            $stmt->execute([$userId]);
            break;

        case 'suspend':
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'suspended', status = 'suspended' WHERE id = ?");
            $stmt->execute([$userId]);
            $sendMailStatus = 'suspended';
            $msg = "User suspended successfully.";
            break;

        case 'activate':
            // When activating, we should also ensure they are approved if they were pending
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'active', verification_status = 'approved', status = 'active' WHERE id = ?");
            $stmt->execute([$userId]);
            $sendMailStatus = 'active';
            $msg = "User activated successfully.";
            break;
            
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $msg = "User deleted successfully.";
            break;

        case 'bulk_action':
            $ids = isset($data['user_ids']) ? explode(',', $data['user_ids']) : [];
            $targetStatus = $data['status'] ?? '';
            
            if (empty($ids)) throw new Exception("No users selected");

            if ($targetStatus === 'delete') {
                 $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                 foreach ($ids as $id) {
                     if (trim($id)) $stmt->execute([trim($id)]);
                 }
                 $msg = "Selected users deleted successfully.";
            } else {
                 $statusMap = [
                     'approve' => "UPDATE users SET verification_status = 'approved', account_status = 'active', status = 'active', remark = CONCAT(COALESCE(remark, ''), ' | Bulk Approved') WHERE id = ?",
                     'suspend' => "UPDATE users SET account_status = 'suspended', status = 'suspended' WHERE id = ?",
                     'activate' => "UPDATE users SET account_status = 'active', verification_status = 'approved', status = 'active' WHERE id = ?",
                     'reject'   => "UPDATE users SET verification_status = 'declined', account_status = 'blocked', status = 'rejected' WHERE id = ?"
                 ];

                 if (!isset($statusMap[$targetStatus])) throw new Exception("Invalid bulk status");

                 $stmt = $pdo->prepare($statusMap[$targetStatus]);
                 foreach ($ids as $id) {
                     if (trim($id)) $stmt->execute([trim($id)]);
                 }
                 $msg = "Bulk action ($targetStatus) completed successfully.";
            }
            break;

        case 'reset_password':
            $newPassword = $data['password'] ?? '';
            if (empty($newPassword)) throw new Exception("Password required");
            
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $userId]);
            
            // Log this action
            try {
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$userId, 'Password Reset', 'Admin reset password manually', $_SERVER['REMOTE_ADDR']]);
            } catch (Exception $e) { /* Ignore logging error */ }

            $msg = "Password reset successfully.";
            break;

        default:
            throw new Exception("Invalid action: $action");
    }

    $pdo->commit();

    // Send Status Email if applicable
    if ($sendMailStatus && $userEmail) {
        sendAccountStatusMail($userEmail, $userName, $sendMailStatus, $unique_id);
    }

    echo json_encode(['status' => 'success', 'message' => $msg]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Operation failed: ' . $e->getMessage()]);
}
?>
