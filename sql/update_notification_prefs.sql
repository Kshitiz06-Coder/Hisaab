-- ============================================
-- Hissab - Email Notification Preferences Migration
-- Run this in phpMyAdmin on your existing hisaab_db
-- ============================================

USE hisaab_db;

ALTER TABLE users ADD COLUMN IF NOT EXISTS notify_daily TINYINT(1) NOT NULL DEFAULT 0 AFTER currency;
ALTER TABLE users ADD COLUMN IF NOT EXISTS notify_weekly TINYINT(1) NOT NULL DEFAULT 0 AFTER notify_daily;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_daily_report_at DATETIME NULL AFTER notify_weekly;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_weekly_report_at DATETIME NULL AFTER last_daily_report_at;
