<?php
require_once 'includes/db.php';
echo "Question Status Breakdown:\n";
$stmt = $pdo->query("SELECT role, academic_level, category, subject, language, status, count(*) as count FROM questions GROUP BY role, academic_level, category, subject, language, status");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("- Role: %s | Class: %s | Cat: %s | Sub: %s | Lang: %s | Status: %s | Count: %d\n", 
        $r['role'], 
        $r['academic_level'] ?? 'N/A', 
        $r['category'] ?? 'N/A', 
        $r['subject'] ?? 'N/A', 
        $r['language'] ?? 'N/A', 
        $r['status'], 
        $r['count']
    );
}
?>
