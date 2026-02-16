USE prefix_mocktest_db;

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

-- Add Password column to users if not exists (handling if it was missed in previous dumps)
-- We can't use IF NOT EXISTS for columns in standard MySQL easily without procedure, but for this environment, let's assume we can try to add it or fail gracefully.
-- However, since I can't easily check column existence via SQL directly in one go without a procedure, I'll just run an ALTER command. If it fails (exists), it's fine.
-- Actually, a safer way is to specific ALTER commands.
ALTER TABLE users ADD COLUMN password VARCHAR(255) AFTER email;
