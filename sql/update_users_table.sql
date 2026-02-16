-- Add columns to users table to support User Management Module
-- We use procedures to safely check if columns exist before adding them to avoid errors on multiple runs

DROP PROCEDURE IF EXISTS upgrade_users_table;
DELIMITER //

CREATE PROCEDURE upgrade_users_table()
BEGIN
    -- Add created_type
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'created_type') THEN
        ALTER TABLE users ADD COLUMN created_type ENUM('admin', 'self') DEFAULT 'self';
    END IF;

    -- Add remark
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'remark') THEN
        ALTER TABLE users ADD COLUMN remark TEXT;
    END IF;

    -- Add verification_status
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'verification_status') THEN
        ALTER TABLE users ADD COLUMN verification_status ENUM('pending', 'approved', 'declined') DEFAULT 'pending';
    END IF;

    -- Add account_status
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'account_status') THEN
        ALTER TABLE users ADD COLUMN account_status ENUM('active', 'suspended', 'blocked', 'inactive') DEFAULT 'inactive';
    END IF;

    -- Add specialization (for experts)
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'specialization') THEN
        ALTER TABLE users ADD COLUMN specialization VARCHAR(255);
    END IF;

    -- Add average_score (for aspirants)
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'average_score') THEN
        ALTER TABLE users ADD COLUMN average_score DECIMAL(5,2) DEFAULT 0.00;
    END IF;

    -- Add avatar
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar') THEN
        ALTER TABLE users ADD COLUMN avatar VARCHAR(255);
    END IF;

    -- Add joined_date
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'joined_date') THEN
        ALTER TABLE users ADD COLUMN joined_date DATETIME DEFAULT CURRENT_TIMESTAMP;
    END IF;

    -- Add created_by if needed (optional based on requirements, but useful)
    -- IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'created_by') THEN
    --    ALTER TABLE users ADD COLUMN created_by INT DEFAULT NULL;
    -- END IF;

    -- UPDATE EXISTING RECORDS
    -- Admin should be active and approved
    UPDATE users SET 
        created_type = 'admin',
        verification_status = 'approved',
        account_status = 'active', 
        remark = 'System Admin'
    WHERE role = 'admin';

    -- Migrate old status 'active' to new account_status 'active' and verification_status 'approved'
    UPDATE users SET account_status = 'active', verification_status = 'approved' WHERE status = 'active' AND role != 'admin';
    
    -- Migrate old status 'pending' to verification_status 'pending'
    UPDATE users SET verification_status = 'pending', account_status = 'inactive' WHERE status = 'pending';

END//

DELIMITER ;

CALL upgrade_users_table();
DROP PROCEDURE upgrade_users_table;

-- Ensure role column has all values (just in case)
ALTER TABLE users MODIFY COLUMN role ENUM('aspirant', 'student', 'expert', 'admin') NOT NULL DEFAULT 'aspirant';
