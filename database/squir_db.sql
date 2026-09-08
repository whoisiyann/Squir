-- squir_db.sql
-- =========================================================
-- SQUIR: Personal Digital Vault and Productivity Companion
-- Database Schema (MySQL / XAMPP)
-- =========================================================

CREATE DATABASE IF NOT EXISTS squir_db;
USE squir_db;


-- =========================================================
-- 1. USERS TABLE
-- Stores account info for regular users and admins.
-- =========================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================================
-- 2. FOLDERS TABLE
-- Organizes vault items and notes for each user.
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
-- Stores saved account credentials per user.
-- account_password must be encrypted (not hashed) in PHP,
-- since it needs to be retrievable, unlike users.password_hash.
-- =========================================================
CREATE TABLE vault (
    vault_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    folder_id INT NULL,
    title VARCHAR(100) NOT NULL,
    account_username VARCHAR(100) NULL,
    account_password VARCHAR(500) NOT NULL,
    website_url VARCHAR(255) NULL,
    tags VARCHAR(255) NULL DEFAULT NULL, -- comma-separated, max 5 tags per item (e.g. "dev,cloud")
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
-- Personal notes management.
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
-- Daily task management.
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
-- Uses explicit nullable FKs (instead of a polymorphic
-- item_id/item_type pair) so foreign key integrity is
-- enforced by MySQL, not just application code.
--
-- NOTE: The CHECK constraint below is not fully reliable
-- against duplicate favorites, since MySQL unique indexes
-- treat NULLs as distinct. Always SELECT to check for an
-- existing favorite before INSERT in the PHP model.
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

    -- Ensures exactly one of the three item types is set per row
    CONSTRAINT chk_favorite_item CHECK (
        (vault_id IS NOT NULL AND note_id IS NULL AND task_id IS NULL) OR
        (vault_id IS NULL AND note_id IS NOT NULL AND task_id IS NULL) OR
        (vault_id IS NULL AND note_id IS NULL AND task_id IS NOT NULL)
    )
);


-- =========================================================
-- 7. ACTIVITY LOGS TABLE
-- Audit trail viewable by the administrator.
-- =========================================================
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- NULL if the acting user was later deleted
    action VARCHAR(100) NOT NULL, -- e.g. 'USER_LOGIN', 'VAULT_ITEM_ADDED', 'ACCOUNT_SUSPENDED'
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_activity_logs_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);

CREATE INDEX idx_logs_user ON activity_logs(user_id);
CREATE INDEX idx_logs_created ON activity_logs(created_at);


-- =========================================================
-- DEFAULT ADMIN SEEDER
-- Email: adminSquir@gmail.com
-- Password (plain, before hashing): adminSquir123
--
-- Do not run this INSERT directly. First generate the real
-- bcrypt hash in PHP:
--
--     <?php echo password_hash('adminSquir123', PASSWORD_DEFAULT); ?>
--
-- Copy the output ($2y$...) and replace REPLACE_WITH_PASSWORD_HASH
-- below before executing.
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