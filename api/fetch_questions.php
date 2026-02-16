<?php
header("Content-Type: application/json");
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $lang = $_GET['lang'] ?? '';
        $role = $_GET['role'] ?? '';
        $target = $_GET['target'] ?? ''; // Class name or Category name
        $subject = $_GET['subject'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        if (empty($role) || empty($target)) {
            echo json_encode(['status' => 'error', 'message' => 'Role and Target (Class/Category) are required.']);
            exit;
        }

        // DEBUG: Log the request
        $logEntry = date('Y-m-d H:i:s') . " | Request: lang=$lang, role=$role, target=$target, subject=$subject\n";
        file_put_contents('debug_log.txt', $logEntry, FILE_APPEND);

        // Build Query - Allow approved OR pending for initial testing visibility?
        // Let's keep approved but add a fallback or just search for both if desired.
        // For now, let's keep approved but make the search case-insensitive for target/subject.
        $sql = "SELECT unique_id as id, question_text as text, option_a as a, option_b as b, option_c as c, option_d as d, correct_answer as ans, explanation, difficulty 
                FROM questions 
                WHERE role = ? AND (status = 'approved' OR status = 'pending')"; 
        $params = [$role];

        // Language Filter
        if (!empty($lang)) {
            $sql .= " AND language = ?";
            $params[] = $lang;
        }

        // Role-Specific Mapping - Case Insensitive check
        if ($role === 'student') {
            $sql .= " AND LOWER(academic_level) = LOWER(?)";
            $params[] = $target;
        } else {
            $sql .= " AND LOWER(category) = LOWER(?)";
            $params[] = $target;
        }

        // Subject Filter
        if (!empty($subject) && $subject !== 'all') {
            $sql .= " AND LOWER(subject) = LOWER(?)";
            $params[] = $subject;
        }

        // Randomized selection - Use integer directly in SQL to avoid binding issues with strings
        $sql .= " ORDER BY RAND() LIMIT " . (int)$limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // DEBUG: Log result count
        $logResult = "Result: " . count($questions) . " questions found\n---\n";
        file_put_contents('debug_log.txt', $logResult, FILE_APPEND);

        if (count($questions) === 0) {
            // Check if ANY questions exist at all to give better error
            $checkStmt = $pdo->prepare("SELECT count(*) FROM questions WHERE role = ?");
            $checkStmt->execute([$role]);
            $count = $checkStmt->fetchColumn();
            
            if ($count > 0) {
                echo json_encode(['status' => 'error', 'message' => "Found $count potential questions for this role, but none match your exact Language/Target/Subject filters."]);
            } else {
                echo json_encode(['status' => 'error', 'message' => "No questions exist in the database for the '$role' role."]);
            }
            exit;
        }

        echo json_encode(['status' => 'success', 'data' => $questions]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
