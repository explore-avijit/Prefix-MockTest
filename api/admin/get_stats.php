<?php
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    // Basic role counts from users table
    $expertsTotal = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'expert'")->fetchColumn();
    $aspirantsTotal = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('aspirant', 'student')")->fetchColumn();
    $adminsCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

    // Pending counts from users table
    $expertsPending = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'expert' AND verification_status = 'pending'")->fetchColumn();
    $aspirantsPending = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('aspirant', 'student') AND verification_status = 'pending'")->fetchColumn();
    
    $totalUsers = $expertsTotal + $aspirantsTotal + $adminsCount;
    $totalPending = $expertsPending + $aspirantsPending;

    // Questions count
    $questionsTotal = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_users' => (int)$totalUsers,
            'experts' => (int)$expertsTotal,
            'aspirants' => (int)$aspirantsTotal,
            'pending_total' => (int)$totalPending,
            'pending_experts' => (int)$expertsPending,
            'pending_aspirants' => (int)$aspirantsPending,
            'total_questions' => (int)$questionsTotal,
            'active_exams' => 12 // Mocked for now
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
