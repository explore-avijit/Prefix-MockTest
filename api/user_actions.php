<?php
require_once '../includes/db.php';
require_once '../includes/functions.php'; // Assuming clean_input or similar might be here, but using PDO params is safe.

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    // Try POST vars if valid JSON not sent
    $data = $_POST;
}

$action = $data['action'] ?? '';
$userId = $data['user_id'] ?? '';
$adminId = $data['admin_id'] ?? 1; // Default to 1 (Admin) for this mock if not authenticated via session

if (empty($action) || empty($userId)) {
    echo json_encode(['status' => 'error', 'message' => 'Action and User ID required']);
    exit;
}

try {
    $pdo->beginTransaction();

    switch ($action) {
        case 'approve':
            $stmt = $pdo->prepare("UPDATE users SET verification_status = 'approved', account_status = 'active', status = 'active', remark = CONCAT(COALESCE(remark, ''), ' | Approved by admin') WHERE id = ?");
            $stmt->execute([$userId]);
            $msg = "User approved successfully.";
            break;

        case 'decline':
            $stmt = $pdo->prepare("UPDATE users SET verification_status = 'declined', account_status = 'blocked', status = 'rejected', remark = CONCAT(COALESCE(remark, ''), ' | Declined by admin') WHERE id = ?");
            $stmt->execute([$userId]);
            $msg = "User declined successfully.";
            break;

        case 'suspend':
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'suspended', status = 'suspended' WHERE id = ?");
            $stmt->execute([$userId]);
            $msg = "User suspended successfully.";
            break;

        case 'activate':
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'active', status = 'active' WHERE id = ?");
            $stmt->execute([$userId]);
            $msg = "User activated successfully.";
            break;
            
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $stmt2 = $pdo->prepare("DELETE FROM experts WHERE user_id = ?"); // Cleanup related if needed
            $stmt2->execute([$userId]);
            $stmt3 = $pdo->prepare("DELETE FROM aspirants WHERE user_id = ?");
            $stmt3->execute([$userId]);
            $msg = "User deleted successfully.";
            break;

        default:
            throw new Exception("Invalid action");
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => $msg]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Operation failed: ' . $e->getMessage()]);
}
?>
