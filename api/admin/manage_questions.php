<?php
header("Content-Type: application/json");
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? 'create';

        // --- CREATE ACTION ---
        if ($action === 'create') {
            // ... (Existing Creation Logic) ...
            $lang = $_POST['question_lang'] ?? '';
            $role = $_POST['target_role'] ?? '';
            $qText = trim($_POST['question_text'] ?? '');
            $correct = $_POST['correct_answer'] ?? '';
            
            $optA = trim($_POST['option_a'] ?? '');
            $optB = trim($_POST['option_b'] ?? '');
            $optC = trim($_POST['option_c'] ?? '');
            $optD = trim($_POST['option_d'] ?? '');
            
            // Basic fields - Strict validation
            if (empty($qText) || empty($correct) || empty($optA) || empty($optB) || empty($optC) || empty($optD)) {
                echo json_encode(['status' => 'error', 'message' => 'Question text, all 4 options, and correct answer are required.']);
                exit;
            }
    
            // Role-specific validation & ID Generation
            $level = null;
            $subject = null;
            $categoryStr = null;
            $uniqueId = '';
            $langCode = ($lang === 'Bengali') ? 'BN' : 'EN';
    
            if ($role === 'student') {
                $level = $_POST['student_class'] ?? '';
                $subject = $_POST['student_subject'] ?? '';
                
                if (empty($level) || empty($subject)) {
                    echo json_encode(['status' => 'error', 'message' => 'Class Level and Subject are mandatory for Students.']);
                    exit;
                }
                $roleCode = 'STU';
                $classNum = preg_replace('/[^0-9]/', '', $level);
                $classCode = str_pad($classNum, 2, '0', STR_PAD_LEFT); // Ensure 09, 10
                $subCode = strtoupper(substr($subject, 0, 3));
                if (strlen($subject) > 3 && strtoupper(substr($subject, 0, 4)) === 'MATH') $subCode = 'MATH';
                
                $randomNum = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $uniqueId = "{$langCode}{$roleCode}{$classCode}{$subCode}{$randomNum}";
                // Example: BN-STU-09-MAT-12345 (Too long? Remove hyphens for compact ID? User used hyphens before)
                // Reverting to hyphenated for readability: BN-STU-09-MAT-12345
                $uniqueId = "{$langCode}-{$roleCode}-{$classCode}-{$subCode}-{$randomNum}";

            } elseif ($role === 'aspirant') {
                $category = $_POST['aspirant_exam_category'] ?? '';
                $subject = $_POST['aspirant_subject'] ?? '';
                
                if (empty($category) || empty($subject)) {
                    echo json_encode(['status' => 'error', 'message' => 'Exam Category and Subject are mandatory for Aspirants.']);
                    exit;
                }
                
                $categoryStr = $category;
                $level = null; // No class for aspirants
                
                $roleCode = 'ASP';
                // Create short code for Category (e.g. WBCS -> WBC, RAILWAY -> RLY?)
                // Simple First 3 letters for now
                $catCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category), 0, 3));
                $subCode = strtoupper(substr($subject, 0, 3));
                
                $randomNum = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $uniqueId = "{$langCode}-{$roleCode}-{$catCode}-{$subCode}-{$randomNum}";

            } else {
                 echo json_encode(['status' => 'error', 'message' => 'Invalid Target Role.']);
                 exit;
            }
    
            // Database Insertion
            $stmt = $pdo->prepare("INSERT INTO questions 
                (unique_id, language, role, academic_level, subject, category, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty, status, added_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?)");
            
            $difficulty = $_POST['difficulty'] ?? 'Medium';
            $explanation = trim($_POST['explanation'] ?? '');

            session_start();
            $user_name = $_SESSION['user_name'] ?? 'Admin';
            $user_id = $_SESSION['unique_id'] ?? ''; // Should be 'ADMIN' or similar for admins
            
            $added_by = ($user_id && $user_id !== 'ADMIN') ? "$user_name ($user_id)" : $user_name;
            
            $stmt->execute([
                $uniqueId, $lang, $role, $level, $subject, $categoryStr, 
                $qText, $optA, $optB, $optC, $optD, $correct, $explanation, $difficulty, $added_by
            ]);
    
            echo json_encode(['status' => 'success', 'message' => 'Question created successfully!', 'id' => $uniqueId]);

        } 
        // --- DELETE ACTION ---
        elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if (empty($id)) { echo json_encode(['status' => 'error', 'message' => 'ID required']); exit; }

            $stmt = $pdo->prepare("DELETE FROM questions WHERE unique_id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Question deleted successfully']);
        }
        // --- UPDATE STATUS (Approve, Reject, Suspend) ---
        elseif ($action === 'update_status') {
            $id = $_POST['id'] ?? '';
            $status = $_POST['status'] ?? ''; 
            
            if (empty($id) || !in_array($status, ['approved', 'rejected', 'suspended', 'pending'])) {
                 echo json_encode(['status' => 'error', 'message' => 'Valid ID and Status required']); 
                 exit; 
            }

            $stmt = $pdo->prepare("UPDATE questions SET status = ? WHERE unique_id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['status' => 'success', 'message' => "Question status updated to $status"]);
        }
        // --- EDIT ACTION (Simplified for now - can be expanded) ---
        elseif ($action === 'edit') {
             // For a full edit, we'd need to handle all fields similar to create. 
             // Implementing this would require populating the form first.
             // For now, let's just assume we might update text/options.
             // (User asked for "make all button workable", implying simpler actions first or full edit later. 
             // Given the context, let's just stub this or implement basic text update if needed, 
             // but usually Edit requires pre-filling a form. I'll leave a placeholder or basic implementation)
             
            $id = $_POST['id'] ?? '';
            $qText = trim($_POST['question_text'] ?? '');
            
            if (empty($id) || empty($qText)) {
                echo json_encode(['status' => 'error', 'message' => 'ID and Question Text required for edit']);
                exit;
            }
            
            // Full update logic would go here. For now, let's just update common fields
            $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=?, explanation=?, difficulty=? WHERE unique_id = ?");
             $stmt->execute([
                $qText, 
                $_POST['option_a'], $_POST['option_b'], $_POST['option_c'], $_POST['option_d'], 
                $_POST['correct_answer'], $_POST['explanation'], $_POST['difficulty'],
                $id
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Question updated successfully']);
        }

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { 
             echo json_encode(['status' => 'error', 'message' => 'ID Collision or Constraint Error']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
