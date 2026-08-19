-- ============================================
-- Hisaab feature update
--   1. First-login category picker (income & expense chosen separately)
--   2. Low-balance email alerts
--   3. Supports the dashboard Savings Overview card (no schema change needed,
--      it's computed from income/expenses, but the alert throttle lives here)
-- Run this once against an existing hisaab_db database.
-- ============================================
use hisaab_db;
ALTER TABLE users
  ADD COLUMN onboarded TINYINT(1) NOT NULL DEFAULT 0 AFTER avatar_color,
  ADD COLUMN notify_low_balance TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_weekly,
  ADD COLUMN low_balance_threshold DECIMAL(12,2) NOT NULL DEFAULT 500.00 AFTER notify_low_balance,
  ADD COLUMN last_low_balance_alert_at DATETIME NULL AFTER low_balance_threshold;

-- Existing users already know their way around — don't interrupt them
-- with the new category picker on their next login.
UPDATE users SET onboarded = 1;

-- ---------- Per-user category selection ----------
-- Lets each user choose which shared default categories they want to use,
-- picked separately for income and expense (a row here = "this user has
-- this default category turned on"). Custom categories a user creates for
-- themselves (categories.user_id = that user) are always visible and don't
-- need a row here.
CREATE TABLE IF NOT EXISTS user_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_category (user_id, category_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Backfill: give every existing user every default category, active,
-- so nothing they were already using disappears from Income/Expenses.
INSERT IGNORE INTO user_categories (user_id, category_id, is_active)
SELECT u.id, c.id, 1 FROM users u CROSS JOIN categories c WHERE c.user_id IS NULL;
