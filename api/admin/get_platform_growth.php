<?php
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    // Default to 6 months if not specified
    $months = isset($_GET['months']) ? (int)$_GET['months'] : 6;
    if ($months <= 0) $months = 6;
    if ($months > 24) $months = 24; // Limit to 2 years max

    // Get growth data for the specified month range
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month_val,
                DATE_FORMAT(created_at, '%b %Y') as month_label,
                SUM(CASE WHEN role = 'expert' THEN 1 ELSE 0 END) as experts,
                SUM(CASE WHEN role IN ('aspirant', 'student') THEN 1 ELSE 0 END) as aspirants
            FROM users 
            WHERE created_at >= DATE_SUB(LAST_DAY(NOW()) + INTERVAL 1 DAY, INTERVAL $months MONTH)
            GROUP BY month_val
            ORDER BY month_val ASC";
    
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If we have fewer than 6 months (e.g. new platform), we might want to fill gaps
    // but for now let's just return what we have.
    
    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
