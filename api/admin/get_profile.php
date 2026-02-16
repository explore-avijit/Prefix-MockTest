<?php
ob_start();
session_start();

// Ensure no output before this
if (ob_get_length()) ob_clean();

require_once __DIR__ . '/../../includes/db.php';

// Clear buffer again just in case db.php outputted something
if (ob_get_length()) ob_clean();

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit; // Stop execution
}

try {
    $userId = $_SESSION['user_id'];
    
    // Fetch basic user details
    $stmt = $pdo->prepare("SELECT full_name, email, avatar, phone, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Sanitize output
        $data = [
            'name' => trim($user['full_name'] ?? ''),
            'email' => trim($user['email'] ?? ''),
            'avatar' => trim($user['avatar'] ?? ''),
            'phone' => trim($user['phone'] ?? ''),
            'role' => trim($user['role'] ?? 'admin')
        ];

        echo json_encode([
            'status' => 'success',
            'data' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
// End of file
