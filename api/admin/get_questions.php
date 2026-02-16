<?php
header("Content-Type: application/json");
require_once '../../includes/db.php';

try {
    $id = $_GET['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE unique_id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
             echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Question not found']);
        }
    } else {
        // Build Query with Filters
        $sql = "SELECT * FROM questions WHERE 1=1";
        $params = [];

        if (!empty($_GET['search'])) {
            $sql .= " AND (question_text LIKE ? OR unique_id LIKE ?)";
            $params[] = '%' . $_GET['search'] . '%';
            $params[] = '%' . $_GET['search'] . '%';
        }

        if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $_GET['status'];
        }

        if (!empty($_GET['role']) && $_GET['role'] !== 'all') {
            $role = $_GET['role'];
            $sql .= " AND role = ?";
            $params[] = $role;
            
            // Student Specific Filters
            if ($role === 'student') {
                if (!empty($_GET['class'])) {
                    // Map class '9' -> '9' or 'Class 9', DB stores 'Madhyamik'/'HS' or '9'/'10'/'11'/'12'
                    // Assuming updated manage_questions.php stores clean numbers or strings.
                    // Let's use LIKE or direct match if we are sure.
                    // Given previous step updates, it stores raw string from select: '9', '10', '11', '12'
                    $sql .= " AND academic_level = ?";
                    $params[] = $_GET['class'];
                }
                if (!empty($_GET['subject'])) {
                    $sql .= " AND subject = ?";
                    $params[] = $_GET['subject'];
                }
            }
            // Aspirant Specific Filters
            elseif ($role === 'aspirant') {
                if (!empty($_GET['exam_category'])) {
                    $sql .= " AND category LIKE ?"; // Use LIKE to match if stored as comma-sep string or just one
                    $params[] = '%' . $_GET['exam_category'] . '%';
                }
                
                if (!empty($_GET['subject'])) {
                    $subject = $_GET['subject'];
                    if ($subject === 'Combined') {
                        // "If user selects “Combined”, system will fetch questions from all subjects under that exam category."
                        // So we DO NOT add a subject filter here.
                        // Implied: Filter only by Exam Category (already added above)
                    } else {
                        $sql .= " AND subject = ?";
                        $params[] = $subject;
                    }
                }
            }
        }

        if (!empty($_GET['difficulty']) && $_GET['difficulty'] !== 'all') {
            $sql .= " AND difficulty = ?";
            $params[] = $_GET['difficulty'];
        }

        if (!empty($_GET['language']) && $_GET['language'] !== 'all') {
            $sql .= " AND language = ?";
            $params[] = $_GET['language'];
        }

        if (!empty($_GET['added_by']) && $_GET['added_by'] !== 'all') {
            $sql .= " AND added_by = ?";
            $params[] = $_GET['added_by'];
        }

        $sql .= " ORDER BY created_at DESC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats Logic - Recalculate based on current filters? Or global?
        // Usually stats cards show global counts, but maybe filtered counts makes sense? 
        // Existing code showed global stats. Let's keep it global for now unless requested otherwise.
        $statsSql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended
            FROM questions";
        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->execute();
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'data' => $questions,
            'meta' => [
                'total' => $stats['total'],
                'approved' => $stats['approved'],
                'pending' => $stats['pending'],
                'suspended' => $stats['suspended']
            ]
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
