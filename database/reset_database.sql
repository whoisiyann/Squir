-- SQUIR - RESET DATABASE
-- Empties every table and resets AUTO_INCREMENT back to 1.

-- WARNING: ALL data in squirdb will be deleted.

USE squirdb;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE activity_logs;
TRUNCATE TABLE favorites;
TRUNCATE TABLE vault_tags;
TRUNCATE TABLE tags;
TRUNCATE TABLE vault;
TRUNCATE TABLE notes;
TRUNCATE TABLE tasks;
TRUNCATE TABLE folders;
TRUNCATE TABLE user_world_clocks;
TRUNCATE TABLE user_pins;
TRUNCATE TABLE password_resets;
TRUNCATE TABLE pin_resets;
TRUNCATE TABLE email_changes;
TRUNCATE TABLE admin_password_resets;
TRUNCATE TABLE admin_email_changes;
TRUNCATE TABLE users;
TRUNCATE TABLE admins;

-- World clock catalog (countries/cities) is kept by default.
-- Uncomment these two lines if you also want them emptied
-- (then re-run world_clocks.sql afterwards):

-- TRUNCATE TABLE cities;
-- TRUNCATE TABLE countries;

SET FOREIGN_KEY_CHECKS = 1;


--   email: squirvault@gmail.com
--   pass : adminvault123
INSERT INTO admins (full_name, username, email, password_hash)
VALUES ('Admin', 'Admin', 'squirvault@gmail.com', '$2y$10$KWQz1tjq6CX3D5VhsWKdRuidaN390QjVy7BL3Iohezdq8sCxYz9hi');