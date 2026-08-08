-- ============================================
-- Hissab - Sustainable Finance System
-- Database Schema for XAMPP / MySQL
-- ============================================

CREATE DATABASE IF NOT EXISTS hisaab_db;
USE hisaab_db;

-- ---------- Users ----------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'Rs',
    avatar_color VARCHAR(10) DEFAULT '#16A34A',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Categories (shared defaults + user custom) ----------
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,                       -- NULL = default/system category
    name VARCHAR(60) NOT NULL,
    type ENUM('income','expense') NOT NULL,
    icon VARCHAR(10) DEFAULT '💰',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Income ----------
CREATE TABLE IF NOT EXISTS income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NULL,
    source VARCHAR(150) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    entry_date DATE NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Expenses ----------
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NULL,
    title VARCHAR(150) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    entry_date DATE NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Savings Goals ----------
CREATE TABLE IF NOT EXISTS savings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    goal_name VARCHAR(150) NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    saved_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    deadline DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Password Reset OTPs ----------
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose ENUM('register','reset') NOT NULL DEFAULT 'reset',
    expires_at DATETIME NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Default categories ----------
INSERT INTO categories (user_id, name, type, icon) VALUES
(NULL, 'Salary', 'income', '💼'),
(NULL, 'Freelance', 'income', '🧑‍💻'),
(NULL, 'Business', 'income', '🏪'),
(NULL, 'Investment', 'income', '📈'),
(NULL, 'Gift', 'income', '🎁'),
(NULL, 'Other Income', 'income', '💰'),
(NULL, 'Food & Dining', 'expense', '🍔'),
(NULL, 'Transportation', 'expense', '🚌'),
(NULL, 'Housing & Rent', 'expense', '🏠'),
(NULL, 'Utilities', 'expense', '💡'),
(NULL, 'Shopping', 'expense', '🛍️'),
(NULL, 'Healthcare', 'expense', '🩺'),
(NULL, 'Education', 'expense', '📚'),
(NULL, 'Entertainment', 'expense', '🎬'),
(NULL, 'Other Expense', 'expense', '🧾');
