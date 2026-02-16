-- Prefix Mock Test Database Structure
CREATE DATABASE IF NOT EXISTS prefix_mocktest_db;
USE prefix_mocktest_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_id VARCHAR(50),
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255),
    role ENUM('aspirant', 'student', 'expert', 'admin') NOT NULL DEFAULT 'aspirant',
    
    -- Status Fields
    verification_status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
    account_status ENUM('active', 'suspended', 'blocked', 'inactive') DEFAULT 'active',
    status ENUM('active', 'pending', 'suspended', 'rejected') GENERATED ALWAYS AS (
        CASE 
            WHEN verification_status = 'pending' THEN 'pending'
            WHEN verification_status = 'declined' THEN 'rejected'
            WHEN account_status = 'suspended' THEN 'suspended'
            WHEN account_status = 'active' THEN 'active'
            ELSE 'pending'
        END
    ) VIRTUAL,
    
    -- Expert Specifics
    specialization VARCHAR(100),
    
    -- Aspirant Specifics
    category VARCHAR(50),
    class VARCHAR(20),
    average_score FLOAT DEFAULT 0,
    
    -- Security
    otp_code VARCHAR(6),
    otp_expiry DATETIME,
    is_verified TINYINT(1) DEFAULT 0,
    remark TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    joined_date DATETIME
);

-- Expert Profiles Table (Extended data for experts - Legacy/Optional if merged to users)
CREATE TABLE IF NOT EXISTS expert_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    degree VARCHAR(100),
    specialization VARCHAR(100),
    profession VARCHAR(100),
    department VARCHAR(100),
    bio TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Initial Admin Record
INSERT INTO users (full_name, email, role, verification_status, account_status, is_verified, password) 
VALUES ('Avijit Sri', 'avijit.sri@gmail.com', 'admin', 'approved', 'active', 1, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- Password: password

-- Taxonomy Table (Subjects & Categories)
CREATE TABLE IF NOT EXISTS taxonomy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_id VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    type ENUM('subject', 'category') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name_type (name, type)
);

-- Category Subjects Mapping Table
CREATE TABLE IF NOT EXISTS category_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cat_sub (category_id, subject_name),
    FOREIGN KEY (category_id) REFERENCES taxonomy(id) ON DELETE CASCADE
);

-- Initial Taxonomy Data
INSERT INTO taxonomy (unique_id, name, type) VALUES 
('TAX-SUB-001', 'Physics', 'subject'),
('TAX-SUB-002', 'Chemistry', 'subject'),
('TAX-SUB-003', 'Mathematics', 'subject'),
('TAX-SUB-004', 'Biology', 'subject'),
('TAX-SUB-005', 'Reasoning', 'subject'),
('TAX-SUB-006', 'General Knowledge', 'subject'),
('TAX-CAT-001', 'WBCS', 'category'),
('TAX-CAT-002', 'SSC', 'category'),
('TAX-CAT-003', 'Railway', 'category'),
('TAX-CAT-004', 'Banking', 'category'),
('TAX-CAT-005', 'JEE Mains', 'category'),
('TAX-CAT-006', 'NEET', 'category')
ON DUPLICATE KEY UPDATE name=name;

-- Initial Mappings (Optional sample data)
-- Fetching IDs for mapping might be tricky in raw SQL if they change, 
-- but we can use subqueries if needed for the first few.
INSERT IGNORE INTO category_subjects (category_id, subject_name) 
SELECT id, 'Mathematics' FROM taxonomy WHERE name = 'JEE Mains' AND type = 'category'
UNION ALL
SELECT id, 'Physics' FROM taxonomy WHERE name = 'JEE Mains' AND type = 'category'
UNION ALL
SELECT id, 'Chemistry' FROM taxonomy WHERE name = 'JEE Mains' AND type = 'category';

-- Questions Table
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_id VARCHAR(50) NOT NULL UNIQUE,
    question_text TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_answer ENUM('a', 'b', 'c', 'd') NOT NULL,
    explanation TEXT,
    role ENUM('student', 'aspirant') NOT NULL,
    academic_level VARCHAR(50), -- Class 9, 10, etc.
    category VARCHAR(100),      -- Exam Category
    subject VARCHAR(100),
    difficulty ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Medium',
    language VARCHAR(50) DEFAULT 'English',
    status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sample Questions
INSERT INTO questions (unique_id, question_text, option_a, option_b, option_c, option_d, correct_answer, role, subject, academic_level, difficulty, status) 
VALUES ('Q-STU-001', 'What is the powerhouse of the cell?', 'Nucleus', 'Mitochondria', 'Ribosome', 'Golgi body', 'b', 'student', 'Life Science', '9', 'Easy', 'approved');

INSERT INTO questions (unique_id, question_text, option_a, option_b, option_c, option_d, correct_answer, role, category, subject, difficulty, status) 
VALUES ('Q-ASP-001', 'Who was the first Prime Minister of India?', 'Mahatma Gandhi', 'Jawaharlal Nehru', 'Sardar Patel', 'B.R. Ambedkar', 'b', 'aspirant', 'WBCS', 'General Knowledge', 'Easy', 'approved');
