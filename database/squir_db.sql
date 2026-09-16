CREATE DATABASE IF NOT EXISTS squir_db;
USE squir_db;


-- 1. USERS
CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status        ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



-- 2. FOLDERS
CREATE TABLE folders (
    folder_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    folder_name VARCHAR(100) NOT NULL,
    color       VARCHAR(20) NOT NULL DEFAULT 'brown',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_folders_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT uq_folder_user_name UNIQUE (user_id, folder_name)
);



-- 3. VAULT (saved credentials)
CREATE TABLE vault (
    vault_id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    folder_id         INT NULL,
    title             VARCHAR(100) NOT NULL,
    account_username  VARCHAR(100) NULL,
    account_password  VARCHAR(500) NOT NULL,
    website_url       VARCHAR(255) NULL,
    tags              VARCHAR(255) NULL DEFAULT NULL, -- comma-separated, max 5 tags per item (e.g. "dev,cloud")
    notes             TEXT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_vault_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_vault_folder
        FOREIGN KEY (folder_id)
        REFERENCES folders(folder_id)
        ON DELETE SET NULL
);


-- 4. NOTES
CREATE TABLE notes (
    note_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    folder_id  INT NULL,
    title      VARCHAR(100) NOT NULL,
    content    TEXT NULL,
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



-- 5. TASKS
CREATE TABLE tasks (
    task_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(100) NOT NULL,
    description TEXT NULL,
    status      ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    priority    ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    due_date    DATE NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tasks_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);



-- 6. FAVORITES (folders, vault items, notes, or tasks)
CREATE TABLE favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    folder_id   INT NULL,
    vault_id    INT NULL,
    note_id     INT NULL,
    task_id     INT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_favorites_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_favorites_folder
        FOREIGN KEY (folder_id)
        REFERENCES folders(folder_id)
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

    -- Ensures exactly one of the four item types is set per row
    CONSTRAINT chk_favorite_item CHECK (
        (folder_id IS NOT NULL AND vault_id IS NULL AND note_id IS NULL AND task_id IS NULL) OR
        (folder_id IS NULL AND vault_id IS NOT NULL AND note_id IS NULL AND task_id IS NULL) OR
        (folder_id IS NULL AND vault_id IS NULL AND note_id IS NOT NULL AND task_id IS NULL) OR
        (folder_id IS NULL AND vault_id IS NULL AND note_id IS NULL AND task_id IS NOT NULL)
    )
);



-- 7. ACTIVITY LOGS
CREATE TABLE activity_logs (
    log_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NULL, -- NULL if the acting user was later deleted
    action      VARCHAR(100) NOT NULL, -- e.g. 'USER_LOGIN', 'VAULT_ITEM_ADDED', 'ACCOUNT_SUSPENDED'
    description TEXT NOT NULL,
    ip_address  VARCHAR(45) NULL,
    user_agent  VARCHAR(255) NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_activity_logs_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);



-- 8. PASSWORD RESETS
CREATE TABLE password_resets (
    reset_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at    TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);



-- 9. INDEXES
CREATE INDEX idx_logs_user            ON activity_logs(user_id);
CREATE INDEX idx_logs_created         ON activity_logs(created_at);
CREATE INDEX idx_vault_user_folder    ON vault(user_id, folder_id);
CREATE INDEX idx_notes_user_folder    ON notes(user_id, folder_id);
CREATE INDEX idx_tasks_user_status    ON tasks(user_id, status);
CREATE INDEX idx_tasks_due_date       ON tasks(due_date);
CREATE INDEX idx_password_resets_user ON password_resets(user_id);



-- -- 10. SEED: ADMIN ACCOUNT
-- INSERT INTO users (
--     full_name,
--     username,
--     email,
--     password_hash,
--     role,
--     status
-- )
-- VALUES (
--     'System Administrator',
--     'admin',
--     'adminSquir@gmail.com',
--     'pass', --php -r "echo password_hash('PasswordngAdmin', PASSWORD_DEFAULT);"
--     'admin',
--     'active'
-- );

--Email: adminSquir@gmail.com
--Password: Admin@123