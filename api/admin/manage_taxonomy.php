<?php
header("Content-Type: application/json");
require_once '../../includes/db.php'; // Adjust path as necessary based on your project structure

$response = ['status' => 'error', 'message' => 'Invalid request'];

// Auto-migration: Create table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS taxonomy (
        id INT AUTO_INCREMENT PRIMARY KEY,
        unique_id VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        type ENUM('subject', 'category') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_name_type (name, type)
    )");
} catch (PDOException $e) {
    // Ideally log this, but proceed
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Auto-migration for mapping table
        $pdo->exec("CREATE TABLE IF NOT EXISTS category_subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            subject_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_cat_sub (category_id, subject_name),
            FOREIGN KEY (category_id) REFERENCES taxonomy(id) ON DELETE CASCADE
        )");

        $stmt = $pdo->query("SELECT * FROM taxonomy ORDER BY created_at DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch all mappings
        $stmtSub = $pdo->query("SELECT category_id, subject_name FROM category_subjects");
        $mappings = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
        $subjectMap = [];
        foreach ($mappings as $m) {
            $subjectMap[$m['category_id']][] = $m['subject_name'];
        }

        // Group by type and attach subjects
        $data = [
            'subject' => [],
            'category' => []
        ];
        
        foreach ($items as $item) {
            $item['subjects'] = $subjectMap[$item['id']] ?? [];
            if (isset($data[$item['type']])) {
                $data[$item['type']][] = $item;
            }
        }
        
        $response = ['status' => 'success', 'data' => $data];
    } catch (PDOException $e) {
        $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');
        
        if (empty($name) || empty($type)) {
            echo json_encode(['status' => 'error', 'message' => 'Name and Type are required']);
            exit;
        }
        
        if (!in_array($type, ['subject', 'category'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
            exit;
        }

        // Generate Unique ID
        $unique_id = 'TAX-' . strtoupper(substr($type, 0, 3)) . '-' . rand(1000, 9999) . '-' . time();
        
        try {
            $stmt = $pdo->prepare("INSERT INTO taxonomy (unique_id, name, type) VALUES (?, ?, ?)");
            $stmt->execute([$unique_id, $name, $type]);
            
            $response = [
                'status' => 'success', 
                'message' => ucfirst($type) . ' added successfully',
                'data' => [
                    'id' => $pdo->lastInsertId(),
                    'unique_id' => $unique_id,
                    'name' => $name,
                    'type' => $type,
                    'created_at' => date('Y-m-d H:i:s'),
                    'is_new' => true 
                ]
            ];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $response = ['status' => 'error', 'message' => 'This item already exists in ' . $type];
            } else {
                $response = ['status' => 'error', 'message' => 'Database add error: ' . $e->getMessage()];
            }
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        
        if (empty($id) || empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'ID and Name are required']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE taxonomy SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            
            if ($stmt->rowCount() > 0) {
                $response = ['status' => 'success', 'message' => 'Item updated successfully'];
            } else {
                $response = ['status' => 'error', 'message' => 'No changes made or item not found'];
            }
        } catch (PDOException $e) {
             if ($e->getCode() == 23000) {
                $response = ['status' => 'error', 'message' => 'An item with this name already exists'];
            } else {
                $response = ['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()];
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'ID is required']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM taxonomy WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                 $response = ['status' => 'success', 'message' => 'Item deleted successfully'];
            } else {
                 $response = ['status' => 'error', 'message' => 'Item not found'];
            }
        } catch (PDOException $e) {
            $response = ['status' => 'error', 'message' => 'Delete failed: ' . $e->getMessage()];
        }
    } elseif ($action === 'add_category_subjects') {
        $category_id = $_POST['category_id'] ?? '';
        $subjects = $_POST['subjects'] ?? []; // Expecting an array of strings
        
        if (empty($category_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Category ID is required']);
            exit;
        }

        // Create category_subjects table if not exists (Auto-migration)
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS category_subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_id INT NOT NULL,
                subject_name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_cat_sub (category_id, subject_name),
                FOREIGN KEY (category_id) REFERENCES taxonomy(id) ON DELETE CASCADE
            )");
        } catch (PDOException $e) {
            // Table creation error
        }

        try {
            $pdo->beginTransaction();

            // 1. Delete existing subjects for this category to perform a full sync
            $stmtDel = $pdo->prepare("DELETE FROM category_subjects WHERE category_id = ?");
            $stmtDel->execute([$category_id]);

            // 2. Insert new subjects
            if (!empty($subjects) && is_array($subjects)) {
                $stmtIns = $pdo->prepare("INSERT INTO category_subjects (category_id, subject_name) VALUES (?, ?)");
                foreach ($subjects as $subject) {
                    $subject = trim($subject);
                    if (empty($subject)) continue;
                    $stmtIns->execute([$category_id, $subject]);
                }
            }

            $pdo->commit();
            $response = ['status' => 'success', 'message' => 'Subjects updated successfully'];
        } catch (PDOException $e) {
            $pdo->rollBack();
            $response = ['status' => 'error', 'message' => 'Database sync error: ' . $e->getMessage()];
        }
    } elseif ($action === 'get_category_subjects') {
        $category_id = $_GET['category_id'] ?? '';

        if (empty($category_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Category ID is required']);
            exit;
        }

        try {
            // Ensure table exists first to avoid error on first run
             $pdo->exec("CREATE TABLE IF NOT EXISTS category_subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_id INT NOT NULL,
                subject_name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_cat_sub (category_id, subject_name),
                FOREIGN KEY (category_id) REFERENCES taxonomy(id) ON DELETE CASCADE
            )");

            $stmt = $pdo->prepare("SELECT subject_name FROM category_subjects WHERE category_id = ? ORDER BY subject_name ASC");
            $stmt->execute([$category_id]);
            $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $response = ['status' => 'success', 'data' => $subjects];
        } catch (PDOException $e) {
            $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_category_subjects') {
     // Handle GET request for subjects separately if needed, or merge with POST block logic above logic needs adjustment
     // Adjusted: moved get_category_subjects logic to inside the POST/GET split correctly or purely check action. 
     // Re-structuring slightly to handle GET action correctly.
}

// Re-evaluating structure:
// The code supports GET (list taxonomy) and POST (add/edit/delete/add_category_subjects).
// 'get_category_subjects' is a fetch operation, so ideally GET. 
// Let's add a explicit GET action check.

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_category_subjects') {
     $category_id = $_GET['category_id'] ?? '';

    if (empty($category_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Category ID is required']);
        exit;
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS category_subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            subject_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_cat_sub (category_id, subject_name),
            FOREIGN KEY (category_id) REFERENCES taxonomy(id) ON DELETE CASCADE
        )");

        // Select as 'name' to match frontend expects s.name
        $stmt = $pdo->prepare("SELECT subject_name as name FROM category_subjects WHERE category_id = ? ORDER BY subject_name ASC");
        $stmt->execute([$category_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $subjects]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode($response);
?>
