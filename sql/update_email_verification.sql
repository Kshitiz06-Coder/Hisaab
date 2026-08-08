-- ============================================
-- Hissab — migration: registration email verification
-- Import this via phpMyAdmin on your EXISTING hissab_db
-- (Only needed if you already ran hissab.sql or update_forgot_password.sql before.
--  Skip this file if you're setting up the database fresh from the latest hissab.sql.)
-- ============================================

USE try_db;

ALTER TABLE users
  ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER password;

ALTER TABLE password_resets
  ADD COLUMN purpose ENUM('register','reset') NOT NULL DEFAULT 'reset' AFTER otp_code;

-- Don't lock out accounts that were created before this feature existed
UPDATE users SET is_verified = 1;
