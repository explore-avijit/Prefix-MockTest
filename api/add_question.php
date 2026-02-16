<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get Input Data
$input = json_decode(file_get_contents('php://input'), true);

// Fallback to POST if not JSON
if (!$input) {
    $input = $_POST;
}

// Validation
$required_fields = ['language', 'role', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Extract Variables
// ... (Validation and variable extraction remain the same) ...

$language = $input['language'];
$role = $input['role'];
$question_text = $input['question_text'];
$option_a = $input['option_a'];
$option_b = $input['option_b'];
$option_c = $input['option_c'];
$option_d = $input['option_d'];
$correct_answer = $input['correct_answer'];
$explanation = $input['explanation'] ?? '';

// ID Generation Logic (Ported from Admin)
$unique_id = '';
$langCode = ($language === 'Bengali') ? 'BN' : 'EN';
$academic_level = null;
$category = null;
$subject = null;

if ($role === 'student') {
    if (empty($input['class']) || empty($input['subject'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Class and Subject are required for Student role']);
        exit;
    }
    $academic_level = $input['class']; // e.g. "Class 10" or "10"
    $subject = $input['subject'];
    $category = 'School'; // Default category

    $roleCode = 'STU';
    // Extract number from Class if possible, or pad
    $classNum = preg_replace('/[^0-9]/', '', $academic_level);
    $classCode = str_pad($classNum, 2, '0', STR_PAD_LEFT);
    
    $subCode = strtoupper(substr($subject, 0, 3));
    if (strlen($subject) > 3 && strtoupper(substr($subject, 0, 4)) === 'MATH') $subCode = 'MATH';

    $randomNum = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $unique_id = "{$langCode}-{$roleCode}-{$classCode}-{$subCode}-{$randomNum}";

} elseif ($role === 'aspirant') {
    if (empty($input['exam_category']) || empty($input['subject'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Exam Category and Subject are required for Aspirant role']);
        exit;
    }
    $category = $input['exam_category'];
    $subject = $input['subject'];
    $academic_level = 'Competitive';

    $roleCode = 'ASP';
    $catCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category), 0, 3));
    $subCode = strtoupper(substr($subject, 0, 3));

    $randomNum = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $unique_id = "{$langCode}-{$roleCode}-{$catCode}-{$subCode}-{$randomNum}";

} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Role']);
    exit;
}

try {
    session_start();
    $user_name = $_SESSION['user_name'] ?? 'Expert';
    $user_id = $_SESSION['unique_id'] ?? '';
    
    // Format: "Name (ID)" or just "Name"
    $added_by = $user_id && $user_id !== 'ADMIN' ? "$user_name ($user_id)" : $user_name;

    $stmt = $pdo->prepare("INSERT INTO questions 
        (unique_id, language, role, academic_level, category, subject, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, status, created_at, added_by) 
        VALUES 
        (:unique_id, :language, :role, :academic_level, :category, :subject, :question_text, :option_a, :option_b, :option_c, :option_d, :correct_answer, :explanation, 'pending', NOW(), :added_by)");

    $stmt->execute([
        ':unique_id' => $unique_id,
        ':language' => $language,
        ':role' => $role,
        ':academic_level' => $academic_level,
        ':category' => $category,
        ':subject' => $subject,
        ':question_text' => $question_text,
        ':option_a' => $option_a,
        ':option_b' => $option_b,
        ':option_c' => $option_c,
        ':option_d' => $option_d,
        ':correct_answer' => $correct_answer,
        ':explanation' => $explanation,
        ':added_by' => $added_by
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Question added successfully', 'id' => $unique_id]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
