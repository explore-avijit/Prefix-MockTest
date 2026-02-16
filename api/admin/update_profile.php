<?php
ob_start();
session_start();
require_once __DIR__ . '/../../includes/db.php';
// Clear output buffer on success to send pure json
if (ob_get_length()) ob_clean();

header('Content-Type: application/json; charset=utf-8');


// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$data = $_POST;
$action = $data['action'] ?? '';

try {
    if ($action === 'update_profile') {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $avatar = $data['avatar'] ?? null; // Can be base64 or URL

        if (empty($name) || empty($email)) {
            throw new Exception("Name and email are required");
        }

        // Handle Avatar Upload (Base64 -> File)
        if ($avatar && preg_match('/^data:image\/(\w+);base64,/', $avatar, $type)) {
            $data = substr($avatar, strpos($avatar, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif
            
            if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png', 'webp' ])) {
                throw new Exception('Invalid image type');
            }
            $data = base64_decode($data);
            if ($data === false) {
                throw new Exception('Base64 decode failed');
            }
            
            $uploadDir = __DIR__ . '/../../uploads/avatars/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filename = 'admin_' . $userId . '_' . time() . '.' . $type;
            if (file_put_contents($uploadDir . $filename, $data)) {
                 // Return path relative to admin-dashboard/
                 $avatar = '../uploads/avatars/' . $filename;
            } else {
                 throw new Exception('Failed to save image file');
            }
        } elseif (empty($avatar)) {
            $avatar = null; // Clear avatar
        }

        // Update DB
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $avatar, $userId]);

        // Update session
        $_SESSION['user_name'] = $name;

        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
