<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // Fetch user details from `users` and `experts` tables
    // We assume `experts` table has `user_id`, `profession`, `specialization`, `department`
    // If user is just in `users` table, we fetch basic info.
    
    $stmt = $pdo->prepare("
        SELECT 
            u.full_name, 
            u.email,
            u.phone_number,
            u.gender,
            COALESCE(e.profession, 'Expert') as profession,
            COALESCE(e.specialization, 'General') as specialization,
            COALESCE(e.department, '') as department
        FROM users u 
        LEFT JOIN experts e ON u.id = e.user_id 
        WHERE u.id = ?
    ");
    
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => $user['phone_number'],
                'gender' => $user['gender'],
                'role' => $user['profession'], // e.g. Senior Professor
                'specialization' => $user['specialization'], // e.g. Chemistry
                'department' => $user['department']
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
