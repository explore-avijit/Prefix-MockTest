<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get params
$role = $_GET['role'] ?? 'all'; // experts, aspirants, all
$filter = $_GET['filter'] ?? 'all'; // all, active, pending, suspended, declined
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

try {
    // Build Query
    $sql = "SELECT id, full_name, email, phone, role, verification_status, account_status, specialization, average_score, created_type, remark, joined_date, avatar FROM users WHERE 1=1";
    $params = [];

    // Role Filter
    if ($role === 'experts') {
        $sql .= " AND role = 'expert'";
    } elseif ($role === 'aspirants') {
        $sql .= " AND role = 'aspirant'";
    } elseif ($role === 'admins') {
        $sql .= " AND role = 'admin'";
    }

    // Status Filter logic
    switch ($filter) {
        case 'active':
            $sql .= " AND verification_status = 'approved' AND account_status = 'active'";
            break;
        case 'pending':
            $sql .= " AND verification_status = 'pending'";
            break;
        case 'suspended':
            $sql .= " AND account_status = 'suspended'";
            break;
        case 'declined':
            $sql .= " AND verification_status = 'declined'";
            break;
        case 'all':
        default:
            // No extra filter
            break;
    }

    // Search Filter
    if (!empty($search)) {
        $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Count Total (for pagination)
    $countSql = str_replace("SELECT id, full_name, email, phone, role, verification_status, account_status, specialization, average_score, created_type, remark, joined_date, avatar", "SELECT COUNT(*)", $sql);
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Sorting and Pagination
    $sql .= " ORDER BY joined_date DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $users,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
