-- squir_db.sql



-- =========================================================
-- SQUIR: Personal Digital Vault and Productivity Companion
-- Official Database Schema (Optimized for MySQL / XAMPP)
-- Implementation: Native PHP (OOP) with Strict Foreign Keys
-- =========================================================

CREATE DATABASE IF NOT EXISTS squir_db;
USE squir_db;


-- =========================================================
-- 1. USERS TABLE
-- Nagtatago ng impormasyon ng mga regular users at admins.
-- =========================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active', -- 'suspended' para sa Admin Actions
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================================
-- 2. FOLDERS TABLE
-- Lalagyan para sa organisadong vault items at notes (Feature 5).
-- =========================================================
CREATE TABLE folders (
    folder_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    folder_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_folders_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);


-- =========================================================
-- 3. VAULT TABLE
-- Personal Password Vault (Feature 2).
-- (dating "passwords" table -- pinalitan ng "vault")
-- =========================================================
CREATE TABLE vault (
    vault_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    folder_id INT NULL, -- Pwedeng walang folder (Uncategorized)
    title VARCHAR(100) NOT NULL,
    account_username VARCHAR(100) NULL,
    account_password VARCHAR(500) NOT NULL, -- I-e-encrypt gamit ang PHP bago i-save
    website_url VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_vault_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_vault_folder
        FOREIGN KEY (folder_id)
        REFERENCES folders(folder_id)
        ON DELETE SET NULL
);


-- =========================================================
-- 4. NOTES TABLE
-- Secure Personal Notes Management (Feature 3).
-- =========================================================
CREATE TABLE notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    folder_id INT NULL,
    title VARCHAR(100) NOT NULL,
    content TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_notes_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_notes_folder
        FOREIGN KEY (folder_id)
        REFERENCES folders(folder_id)
        ON DELETE SET NULL
);


-- =========================================================
-- 5. TASKS TABLE
-- Productivity Companion: Tagapamahala ng Daily Tasks (Feature 4).
-- =========================================================
CREATE TABLE tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NULL,
    status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tasks_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);


-- =========================================================
-- 6. FAVORITES TABLE
-- Explicit foreign keys (hindi polymorphic) para sa tunay na
-- referential integrity — kapag na-delete ang item, awtomatikong
-- mawawala rin ang favorite record niya (ON DELETE CASCADE).
--
-- PAALALA: Ang UNIQUE constraint sa ibaba ay hindi sapat mag-isa
-- para pigilan ang duplicate favorites (dahil sa NULL behavior ng
-- MySQL sa mga unique index). I-CHECK ITO SA PHP CODE MO: bago
-- mag-INSERT sa Favorite model, mag-SELECT muna kung mayroon nang
-- existing na favorite ang parehong user para sa parehong item.
-- =========================================================
CREATE TABLE favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vault_id INT NULL,
    note_id INT NULL,
    task_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_favorites_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_favorites_vault
        FOREIGN KEY (vault_id)
        REFERENCES vault(vault_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_favorites_note
        FOREIGN KEY (note_id)
        REFERENCES notes(note_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_favorites_task
        FOREIGN KEY (task_id)
        REFERENCES tasks(task_id)
        ON DELETE CASCADE,

    -- Siguraduhin na isa lang sa tatlong aytem ang mai-fa-favorite bawat hilera
    CONSTRAINT chk_favorite_item CHECK (
        (vault_id IS NOT NULL AND note_id IS NULL AND task_id IS NULL) OR
        (vault_id IS NULL AND note_id IS NOT NULL AND task_id IS NULL) OR
        (vault_id IS NULL AND note_id IS NULL AND task_id IS NOT NULL)
    )
);


-- =========================================================
-- 7. ACTIVITY LOGS TABLE
-- Security Vault Audit Trail: Binabantayan ng Administrator.
-- =========================================================
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- NULL kapag ang aktibidad ay ginawa ng nabura nang user
    action VARCHAR(100) NOT NULL, -- hal. 'USER_LOGIN', 'VAULT_ITEM_ADDED', 'ACCOUNT_SUSPENDED'
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_activity_logs_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- Optimization indexes para mabilis i-load ng Admin Dashboard
CREATE INDEX idx_logs_user ON activity_logs(user_id);
CREATE INDEX idx_logs_created ON activity_logs(created_at);


-- =========================================================
-- DEFAULT ADMIN SEEDER
-- Email: adminSquir@gmail.com
-- Password (plain, bago i-hash): adminSquir123
--
-- PAALALA: Huwag i-run ang INSERT na ito nang direkta. Buuin
-- muna ang totoong bcrypt hash gamit ang PHP:
--
--     <?php echo password_hash('adminSquir123', PASSWORD_DEFAULT); ?>
--
-- Kopyahin ang output ($2y$...) at ipalit sa
-- REPLACE_WITH_PASSWORD_HASH sa baba bago i-execute.
-- =========================================================
INSERT INTO users (
    full_name,
    username,
    email,
    password_hash,
    role,
    status
)
VALUES (
    'System Administrator',
    'admin',
    'adminSquir@gmail.com',
    'REPLACE_WITH_PASSWORD_HASH',
    'admin',
    'active'
);






-- ============================================================
-- Migration: magdagdag ng "tags" column sa vault table
-- I-run mo ito sa phpMyAdmin / mysql client (isang beses lang).
-- ============================================================
 
USE squir_db;
 
ALTER TABLE vault
    ADD COLUMN tags VARCHAR(255) NULL DEFAULT NULL AFTER website_url;
 
-- Tandaan: comma-separated storage lang ito (hal. "dev,cloud"),
-- max 5 tags per item, dahil doon din naman naka-design yung
-- Tags input sa create/edit modal ("dev, finance (max 5)").
-- Hindi na kailangan ng hiwalay na tags table para dito.
 