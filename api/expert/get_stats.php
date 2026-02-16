<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_name'])) {
        throw new Exception('User not logged in');
    }

    $userName = $_SESSION['user_name'];
    $uniqueId = $_SESSION['unique_id'] ?? '';
    
    // Construct the search pattern based on how we save added_by
    // Logic: If we have a unique ID, we search for "Name (ID)". If not, just "Name".
    // Alternatively, since we store "Name (ID)", we can use LIKE to be safe or exact match if we are consistent.
    // Best Approach: 
    // If uniqueId is present, the stored format is "$userName ($uniqueId)".
    // If uniqueId is empty (Admin/Legacy), it is just "$userName".
    
    if ($uniqueId && $uniqueId !== 'ADMIN') {
        $searchParam = "$userName ($uniqueId)";
    } else {
        $searchParam = $userName;
    }

    // 1. Total Questions (Platform Wide)
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM questions");
    $totalQuestions = $stmtTotal->fetchColumn();

    // 2. My Submissions
    $stmtMySubmissions = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE added_by = ?");
    $stmtMySubmissions->execute([$searchParam]);
    $mySubmissions = $stmtMySubmissions->fetchColumn();

    // 3. Live/Approved
    $stmtApproved = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE added_by = ? AND LOWER(status) = 'approved'");
    $stmtApproved->execute([$searchParam]);
    $myApproved = $stmtApproved->fetchColumn();

    // 4. Queue
    $stmtQueue = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE added_by = ? AND LOWER(status) = 'pending'");
    $stmtQueue->execute([$searchParam]);
    $myQueue = $stmtQueue->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_questions' => (int)$totalQuestions,
            'my_submissions' => (int)$mySubmissions,
            'my_approved' => (int)$myApproved,
            'my_queue' => (int)$myQueue
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
