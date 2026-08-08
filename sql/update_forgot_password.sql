-- ============================================
-- Hissab — migration: forgot-password OTP support
-- Import this via phpMyAdmin on your EXISTING hissab_db
-- (do not re-import the full hissab.sql, this just adds one table)
-- ============================================

USE hisaab_db;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
