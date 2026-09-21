-- ============================================
-- Hisaab feature update: Monthly budget limits per expense category
-- Run this once against an existing hisaab_db database.
-- ============================================
USE hisaab_db;

CREATE TABLE IF NOT EXISTS category_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    monthly_budget DECIMAL(12,2) NOT NULL,
    UNIQUE KEY uniq_user_budget_category (user_id, category_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;
