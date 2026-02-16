<?php
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get params
$role = $_GET['role'] ?? 'expert'; // Default to expert
$filter = $_GET['status'] ?? 'all'; // using 'status' param as per frontend call
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

try {
    // Build Query
    // Note: User wanted 'users' table approach. 
    $sql = "SELECT id, full_name, email, phone, role, verification_status, account_status, specialization, average_score, created_type, remark, joined_date, avatar, created_at FROM users WHERE 1=1";
    $params = [];

    // Role Filter
    if ($role === 'expert') {
        $sql .= " AND role = 'expert'";
    } elseif ($role === 'aspirant') {
        $sql .= " AND (role = 'aspirant' OR role = 'student')"; // Aspirant tab usually includes students too in this context? Let's check logic. Prompt says "EXPERTS" and "ASPIRANTS". Student might be a sub-type of Aspirant.
    } elseif ($role === 'admin') {
        $sql .= " AND role = 'admin'";
    }
    // If 'all', no role filter? Use with caution.

    // Status Filter logic matching prompt
    // ACTIVE USERS → verification_status = approved AND account_status = active
    // PENDING USERS → verification_status = pending
    // SUSPENDED USERS → account_status = suspended
    // DECLINED USERS → verification_status = declined
    
    switch ($filter) {
        case 'active':
        case 'approved': // Frontend might send 'approved'
            $sql .= " AND verification_status = 'approved' AND account_status = 'active'";
            break;
        case 'pending':
            $sql .= " AND verification_status = 'pending'";
            break;
        case 'suspended':
            $sql .= " AND account_status = 'suspended'";
            break;
        case 'declined':
        case 'rejected':
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

    // Count Total
    // Regex replace to ensure we get a cleaner COUNT query regardless of selected columns
    $countSql = preg_replace('/SELECT .*? FROM users/i', 'SELECT COUNT(*) FROM users', $sql, 1);
    
    // Fallback if regex fails (though unlikely with this structure)
    if ($countSql === null || $countSql === $sql) {
         $countSql = "SELECT COUNT(*) FROM users WHERE 1=1" . substr($sql, strpos($sql, 'WHERE 1=1') + 9);
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Sorting - Newest First (Using ID is more robust for insertion order)
    // Sorting - Newest First (Using ID is more robust for insertion order)
    if ($limit == -1) {
        $sql .= " ORDER BY id DESC"; // No LIMIT/OFFSET
    } else {
        $sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Normalize status for frontend compatibility if needed
    foreach ($users as &$user) {
        // Map DB fields to frontend 'status' expectation if simpler
        if ($user['verification_status'] === 'pending') $user['status'] = 'pending';
        elseif ($user['verification_status'] === 'declined') $user['status'] = 'rejected';
        elseif ($user['account_status'] === 'suspended') $user['status'] = 'suspended';
        elseif ($user['account_status'] === 'active') $user['status'] = 'active';
        else $user['status'] = 'unknown';
    }

    echo json_encode([
        'status' => 'success',
        'data' => $users,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ($limit == -1) ? 1 : ceil($total / $limit)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
