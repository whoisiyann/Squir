CREATE DATABASE IF NOT EXISTS squirdb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE squirdb;



-- 1. USERS 

CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    username      VARCHAR(50)  NOT NULL,
    email         VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,   -- bcrypt (password_hash)
    status        ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    last_login_at DATETIME  NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email    UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_users_status ON users (status);


-- 1b. ADMINS — administrator accounts (separate entity from users)
CREATE TABLE admins (
    admin_id      INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    username      VARCHAR(50)  NOT NULL,
    email         VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,   -- bcrypt (password_hash)
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME  NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_admins_username UNIQUE (username),
    CONSTRAINT uq_admins_email    UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- 2. USER_PINS 
CREATE TABLE user_pins (
    user_id          INT NOT NULL PRIMARY KEY,
    pin_hash         VARCHAR(255)     NOT NULL,
    failed_attempts  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until     DATETIME NULL,   -- temporary lockout matapos ang maling PIN
    last_verified_at DATETIME NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_pins_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- 2b. PASSWORD_RESETS — forgot-password verification codes
CREATE TABLE password_resets (
    reset_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    code_hash   VARCHAR(255) NOT NULL,
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at  DATETIME NOT NULL,
    verified_at DATETIME NULL,
    used_at     DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_password_resets_user ON password_resets (user_id);




-- 2c. PIN_RESETS — forgot-PIN verification codes
CREATE TABLE pin_resets (
    reset_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    code_hash   VARCHAR(255) NOT NULL,
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at  DATETIME NOT NULL,
    verified_at DATETIME NULL,
    used_at     DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pin_resets_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_pin_resets_user ON pin_resets (user_id);





-- 3. FOLDERS — 
CREATE TABLE folders (
    folder_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    folder_name VARCHAR(100) NOT NULL,
    folder_type ENUM('passwords','notes') NOT NULL DEFAULT 'passwords',
    color       ENUM('beige','blue','brown','coralRed','creamYellow',
                     'darkGreen','green','lavender','magenta','orange',
                     'pink','purple','red','skyBlue','teal','yellow')
                NOT NULL DEFAULT 'brown',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_folders_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT uq_folders_user_name_type
        UNIQUE (user_id, folder_name, folder_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_folders_user_type ON folders (user_id, folder_type);



-- 4. VAULT — naka-encrypt na account credentials
CREATE TABLE vault (
    vault_id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    folder_id        INT NULL,
    title            VARCHAR(100) NOT NULL,
    account_username VARCHAR(100) NULL,
    account_password VARCHAR(500) NOT NULL,
    website_url      VARCHAR(255) NULL,
    notes            TEXT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_vault_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_vault_folder
        FOREIGN KEY (folder_id) REFERENCES folders(folder_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_vault_user_folder ON vault (user_id, folder_id);
CREATE INDEX idx_vault_user_title  ON vault (user_id, title);



-- 5. NOTES — personal notes (rich text)
CREATE TABLE notes (
    note_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    folder_id  INT NULL,
    title      VARCHAR(100) NOT NULL DEFAULT '',
    content    TEXT NULL,          -- sanitized HTML
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                       ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_notes_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_notes_folder
        FOREIGN KEY (folder_id) REFERENCES folders(folder_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_notes_user_folder  ON notes (user_id, folder_id);
CREATE INDEX idx_notes_user_updated ON notes (user_id, updated_at);



-- 6. TAGS — 1NF
CREATE TABLE tags (
    tag_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    tag_name   VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_tags_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT uq_tags_user_name UNIQUE (user_id, tag_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




-- 7. VAULT_TAGS — bridge/associative entity (M:N resolution)
CREATE TABLE vault_tags (
    vault_id   INT NOT NULL,
    tag_id     INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (vault_id, tag_id),

    CONSTRAINT fk_vault_tags_vault
        FOREIGN KEY (vault_id) REFERENCES vault(vault_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_vault_tags_tag
        FOREIGN KEY (tag_id) REFERENCES tags(tag_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_vault_tags_tag ON vault_tags (tag_id);



-- 8. TASKS — Kanban board (To Do / In Progress / Done)
CREATE TABLE tasks (
    task_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    title        VARCHAR(100) NOT NULL,
    description  TEXT NULL,
    status       ENUM('todo','in_progress','done') NOT NULL DEFAULT 'todo',
    priority     ENUM('low','medium','high')       NOT NULL DEFAULT 'medium',
    due_date     DATE NULL,
    position     INT UNSIGNED NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tasks_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_tasks_board    ON tasks (user_id, status, position);
CREATE INDEX idx_tasks_due_date ON tasks (user_id, due_date);




-- 9. FAVORITES 
CREATE TABLE favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    folder_id   INT NULL,
    vault_id    INT NULL,
    note_id     INT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_favorites_user
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_folder
        FOREIGN KEY (folder_id) REFERENCES folders(folder_id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_vault
        FOREIGN KEY (vault_id) REFERENCES vault(vault_id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_note
        FOREIGN KEY (note_id) REFERENCES notes(note_id) ON DELETE CASCADE,

    CONSTRAINT chk_favorites_one_item CHECK (
        (folder_id IS NOT NULL) +
        (vault_id  IS NOT NULL) +
        (note_id   IS NOT NULL) = 1
    ),


    CONSTRAINT uq_favorites_folder UNIQUE (user_id, folder_id),
    CONSTRAINT uq_favorites_vault  UNIQUE (user_id, vault_id),
    CONSTRAINT uq_favorites_note   UNIQUE (user_id, note_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




-- 10. ACTIVITY_LOGS — 
CREATE TABLE activity_logs (
    log_id      BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NULL,
    admin_id    INT NULL,
    action      VARCHAR(60)  NOT NULL,
    entity_type ENUM('user','admin','folder','vault','note','task',
                     'favorite','tag','system') NULL,
    entity_id   INT NULL,
    description VARCHAR(255) NOT NULL,
    ip_address  VARCHAR(45)  NULL,
    user_agent  VARCHAR(255) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_activity_logs_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL,

    CONSTRAINT fk_activity_logs_admin
        FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_logs_user_created  ON activity_logs (user_id, created_at);
CREATE INDEX idx_logs_admin_created ON activity_logs (admin_id, created_at);
CREATE INDEX idx_logs_created      ON activity_logs (created_at);
CREATE INDEX idx_logs_action       ON activity_logs (action);




-- -- 11. PASSWORD_RESETS — one-time reset tokens
-- CREATE TABLE password_resets (
--     reset_id   INT AUTO_INCREMENT PRIMARY KEY,
--     user_id    INT NOT NULL,
--     token_hash VARCHAR(255) NOT NULL,
--     expires_at DATETIME NOT NULL,
--     used_at    DATETIME NULL,
--     created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

--     CONSTRAINT fk_password_resets_user
--         FOREIGN KEY (user_id) REFERENCES users(user_id)
--         ON DELETE CASCADE,

--     CONSTRAINT uq_password_resets_token UNIQUE (token_hash)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE INDEX idx_password_resets_user ON password_resets (user_id, expires_at);




-- 12. SEED: ADMINISTRATOR ACCOUNT
--   php database/create_admin.php "System Administrator" admin adminsquir@gmail.com "Admin@123"
--
-- O manual: 

--   php -r "echo password_hash('Admin@123', PASSWORD_DEFAULT), PHP_EOL;"

-- INSERT INTO admins (full_name, username, email, password_hash)
-- VALUES ('System Administrator', 'admin', 'adminsquir@gmail.com', 'PALITAN_NG_HASH');