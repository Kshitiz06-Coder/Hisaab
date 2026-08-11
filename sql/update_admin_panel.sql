-- ============================================
-- Hissab - Admin Panel Migration
-- Run this in phpMyAdmin on your existing hisaab_db
-- Safe to run once; adds new tables/columns only.
-- ============================================

USE hisaab_db;

-- ---------- Admins (separate login from regular users) ----------
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Warning log (audit trail, shown on user detail page) ----------
CREATE TABLE IF NOT EXISTS user_warnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NULL,
    reason VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Ban/warning fields on users ----------
-- Run each ADD COLUMN separately if your MySQL version doesn't like
-- multiple ADD COLUMN clauses with IF NOT EXISTS in one statement.
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) NOT NULL DEFAULT 0 AFTER is_verified;
ALTER TABLE users ADD COLUMN IF NOT EXISTS ban_reason VARCHAR(255) NULL AFTER is_banned;
ALTER TABLE users ADD COLUMN IF NOT EXISTS banned_at TIMESTAMP NULL AFTER ban_reason;
ALTER TABLE users ADD COLUMN IF NOT EXISTS warning_count INT NOT NULL DEFAULT 0 AFTER banned_at;
