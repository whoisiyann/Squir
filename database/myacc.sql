

USE squirdb;

DELETE FROM users WHERE email = 'ianchristopherjunio19@gmail.com';

INSERT INTO users (full_name, username, email, password_hash, status, last_login_at)
VALUES ('Ian Christopher Junio', '_whoisiyann', 'ianchristopherjunio19@gmail.com', '$2y$10$Z5wkcMphYaDWHrsZ2CN5X.ltR.G7m0wE8nk5OOk4d1FzWhwZDhde.', 'active', '2026-10-18 09:00:00');
SET @uid = LAST_INSERT_ID();

INSERT INTO user_pins (user_id, pin_hash) VALUES (@uid, '$2y$10$ho1tTXf.p9KbqfcGpIYS/OTTDt7QvmIjNofytoWLztpfL5B7baa8G');

-- FOLDERS
INSERT INTO folders (user_id, folder_name, folder_type, color) VALUES
(@uid, 'Social Media', 'passwords', 'blue'),
(@uid, 'Games', 'passwords', 'green'),
(@uid, 'Streaming', 'passwords', 'red'),
(@uid, 'Personal Accounts', 'passwords', 'brown'),
(@uid, 'School', 'notes', 'purple'),
(@uid, 'Personal', 'notes', 'orange');

-- VAULT (16 entries: sample passwords, AES-256-CBC encrypted)
INSERT INTO vault (user_id, folder_id, title, account_username, account_password, website_url, notes) VALUES
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Social Media' AND folder_type = 'passwords'), 'Facebook', 'ianchristopherjunio19@gmail.com', '2jPyNpIWTgw3782z4seUVAO2ug21W0KGBWj70zaJmgw=', 'https://www.facebook.com', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Social Media' AND folder_type = 'passwords'), 'Instagram', 'ian.junio', '6Jn1Z6OLnYvmYGhzVlkVHkMZNX6B9U5t04VqOICKEOE=', 'https://www.instagram.com', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Social Media' AND folder_type = 'passwords'), 'TikTok', 'ianjunio19', 'Y0GfwHomirljloVYE86xw33W7AgfFux/s+QuaKm+fxs=', 'https://www.tiktok.com', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Games' AND folder_type = 'passwords'), 'Call of Duty Mobile (Main)', 'ianjunio19', '0YVasU1tkP1hM89vq050V+/0ATGZE1qRzW9mgqeqRmI=', 'https://www.garena.com', 'Main account'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Games' AND folder_type = 'passwords'), 'Call of Duty Mobile (Alt)', 'ianjunio.alt', 'B/4Z/MEczclgQET4IZjJL44857PS+hbF52g3LQNepTg=', 'https://www.garena.com', 'Second account'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Games' AND folder_type = 'passwords'), 'Mobile Legends', 'ianjunio19', 'Ia9cNJmRBsw2CLNy/xt1h7D0qcR2SFT3RRTUxeJS0+k=', 'https://www.mobilelegends.com', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Games' AND folder_type = 'passwords'), 'Roblox', 'ianjunio19', '9OYBqjdKjQZUZPbRR0kz5fNioa6k7i5XRXWk1TSeAgw=', 'https://www.roblox.com', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Games' AND folder_type = 'passwords'), 'Clash of Clans', 'ianjunio19', '10056nVFcqsMY8PSy8VknkgisMPa7JIr6pmbXSqIE1w=', 'https://supercell.com/en/games/clashofclans/', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Games' AND folder_type = 'passwords'), 'Minecraft', 'ianjunio19', 'crVYBbZlnJ6Ta5UpN0cpXWFMrtST5YJirTvZIajbjkg=', 'https://www.minecraft.net', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Streaming' AND folder_type = 'passwords'), 'YouTube (Main)', 'ianchristopherjunio19@gmail.com', 'wpIFg4SOdIjxRg/+hphb0CCF5ESPOkoEpAtckTUTF94=', 'https://www.youtube.com', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Streaming' AND folder_type = 'passwords'), 'YouTube (Second Channel)', 'ianjunio.alt@gmail.com', 'fhLPx1EchK4UPiQ4pc7ovG5ZRHV1MbszvjH6OcTjwzE=', 'https://www.youtube.com', 'Second channel'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Streaming' AND folder_type = 'passwords'), 'Spotify', 'ianchristopherjunio19@gmail.com', 'w/AmG7+ROtW8oVPCnI3dBu7RLaMd1e+M+uw6stjhZJ4=', 'https://www.spotify.com', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Streaming' AND folder_type = 'passwords'), 'Netflix', 'ianchristopherjunio19@gmail.com', 'm5BcI64wjAtPLWN+UgnjHB3kcAumfWlwINfuErzAxEs=', 'https://www.netflix.com', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Personal Accounts' AND folder_type = 'passwords'), 'Google Account', 'ianchristopherjunio19@gmail.com', 'RYVvHkUKmYdltU7Q9bVzbEQFkQvoXhEo4E5SG4soY3o=', 'https://accounts.google.com', NULL),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Personal Accounts' AND folder_type = 'passwords'), 'GitHub', 'ianjunio19', 'INT140grAml7f2IbEE1GvTz1Wjj6IpXqv3T6Uyujobs=', 'https://github.com', 'Squir project repo'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Personal Accounts' AND folder_type = 'passwords'), 'Home WiFi', 'Home WiFi', '+LIgmJ8MbM7ks/5y89Vm6ioJxXrG0MQirnKWWdurzz9gmRlogpCUDcT8hrf8uxP7', NULL, 'Router password');

-- TAGS
INSERT INTO tags (user_id, tag_name) VALUES (@uid, 'social account'), (@uid, 'games'), (@uid, 'dev');

-- VAULT_TAGS
INSERT INTO vault_tags (vault_id, tag_id) VALUES
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Facebook'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'social account')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Instagram'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'social account')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'TikTok'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'social account')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'YouTube (Main)'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'social account')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'YouTube (Second Channel)'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'social account')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'GitHub'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'dev')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Call of Duty Mobile (Main)'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'games')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Call of Duty Mobile (Alt)'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'games')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Mobile Legends'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'games')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Roblox'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'games')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Clash of Clans'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'games')),
((SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Minecraft'), (SELECT tag_id FROM tags WHERE user_id = @uid AND tag_name = 'games'));

-- NOTES (8)
INSERT INTO notes (user_id, folder_id, title, content) VALUES
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'School' AND folder_type = 'notes'), 'Squir Defense Checklist', '<p>1. Normalized tables<br>2. PK and FK<br>3. SQL joins<br>4. Prepared statements<br>5. CRUD with validation<br>6. Report<br>7. Populated data</p>'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'School' AND folder_type = 'notes'), 'SQL Joins Reviewer', '<p>INNER JOIN: matching rows only.<br>LEFT JOIN: all rows from the left table.<br>Use joins instead of PHP loops.</p>'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'School' AND folder_type = 'notes'), 'Networking Notes', '<p>Review VLANs, subnetting and the Packet Tracer topology.</p>'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'School' AND folder_type = 'notes'), 'Project Ideas', '<p>1. Inventory system<br>2. Student portal<br>3. Task tracker</p>'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Personal' AND folder_type = 'notes'), 'Weekly Goals', '<p>Finish project documentation, review for exams, exercise 3x a week.</p>'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Personal' AND folder_type = 'notes'), 'Grocery List', '<p>Rice, eggs, milk, coffee, bread.</p>'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Personal' AND folder_type = 'notes'), 'Gaming Schedule', '<p>Ranked games on weekends only. Study first on weekdays.</p>'),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Personal' AND folder_type = 'notes'), 'Birthday Gift Ideas', '<p>Headphones, wallet, notebook set.</p>');

-- TASKS (5) - dates are relative to the day you import this file
INSERT INTO tasks (user_id, title, description, status, priority, due_date, position, completed_at) VALUES
(@uid, 'Finish Squir database seed', 'Run reset and seed scripts, then test.', 'in_progress', 'high', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 0, NULL),
(@uid, 'Prepare Squir defense', 'Review checklist and practice the demo.', 'todo', 'high', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 0, NULL),
(@uid, 'Review SQL joins', 'Practice INNER and LEFT JOIN queries.', 'todo', 'medium', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 1, NULL),
(@uid, 'Submit project documentation', 'Final documentation and ERD.', 'todo', 'medium', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 2, NULL),
(@uid, 'Test login and CRUD flow', 'Checked login, add, edit and delete.', 'done', 'low', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 0, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '18:30:00'));

-- FAVORITES (4 credentials, 2 notes, 1 folder)
INSERT INTO favorites (user_id, folder_id, vault_id, note_id) VALUES
(@uid, NULL, (SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Facebook'), NULL),
(@uid, NULL, (SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Google Account'), NULL),
(@uid, NULL, (SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'GitHub'), NULL),
(@uid, NULL, (SELECT vault_id FROM vault WHERE user_id = @uid AND title = 'Mobile Legends'), NULL),
(@uid, NULL, NULL, (SELECT note_id FROM notes WHERE user_id = @uid AND title = 'Squir Defense Checklist')),
(@uid, NULL, NULL, (SELECT note_id FROM notes WHERE user_id = @uid AND title = 'SQL Joins Reviewer')),
(@uid, (SELECT folder_id FROM folders WHERE user_id = @uid AND folder_name = 'Games' AND folder_type = 'passwords'), NULL, NULL);

-- ACTIVITY LOGS
INSERT INTO activity_logs (user_id, action, entity_type, description, ip_address, device, created_at) VALUES
(@uid, 'logged_in', NULL, 'Logged in', '127.0.0.1', 'Windows (Chrome)', '2026-10-18 09:00:00'),
(@uid, 'vault_unlocked', NULL, 'Vault unlocked', '127.0.0.1', 'Windows (Chrome)', '2026-10-18 09:01:00'),
(@uid, 'task_completed', 'task', 'Completed task', '127.0.0.1', 'Windows (Chrome)', '2026-10-17 18:30:00');