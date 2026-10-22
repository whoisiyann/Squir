# 🐿️ Squir — Your Personal Digital Vault and Productivity Companion

![PHP](https://img.shields.io/badge/PHP-8+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=black)
![PHPMailer](https://img.shields.io/badge/PHPMailer-OTP_Email-blue)
![Dompdf](https://img.shields.io/badge/Dompdf-PDF_Export-orange)
![Composer](https://img.shields.io/badge/Composer-Dependency_Manager-885630?logo=composer&logoColor=white)
![Encryption](https://img.shields.io/badge/Encryption-AES--256-red)
![Status](https://img.shields.io/badge/Status-Active-brightgreen)
![License](https://img.shields.io/badge/License-Academic-blue)

Squir is a web-based system that helps individuals keep their passwords, notes, tasks, and personal information organized in one secure, easy-to-use platform. It combines an **encrypted password vault**, **notes**, **task management**, a **calendar**, and **folder organization** into a single dashboard, so users no longer need to juggle multiple apps to stay on top of their digital life. It also includes a separate **administrator side** for managing users and monitoring system activity.

---

## 📋 Table of Contents

- [Project Objectives](#-project-objectives)
- [Target Users](#-target-users)
- [Features](#-features)
- [Security](#-security)
- [Tech Stack](#-tech-stack)
- [Database](#-database)
- [Project Structure](#-project-structure)
- [Getting Started](#-getting-started)
- [Troubleshooting](#-troubleshooting)
- [Team](#-team)

---

## 🎯 Project Objectives

1. To develop a web-based system for organizing and managing personal information.
2. To allow users to store and manage passwords, notes, and tasks in one platform.
3. To help users organize their information through folders and favorites.
4. To provide a simple dashboard for easier access to stored information.
5. To provide a search feature that helps users find their stored information quickly.

## 👥 Target Users

| User Type | Description |
|---|---|
| **Primary Users** | Students and individuals who need a simple system for organizing personal information and daily tasks. |
| **Secondary Users** | Working individuals who want to manage their notes, passwords, and tasks in one platform. |
| **Administrators** | System administrators who manage user accounts and monitor activity. |

---

## ✨ Features

### 🔐 Account & Access
- **User Registration & Login** — Create an account and log in securely (hashed passwords). New users pick a unique username right after their first login.
- **Forgot Password (OTP-based)** — Reset a forgotten account password using a 6-digit code emailed to the user, with expiry, resend cooldown, and attempt limits.
- **Vault PIN Lock** — A separate 6-digit PIN gate protects access to the vault, independent of the account password.
- **Forgot PIN (OTP-based)** — Users who forget their vault PIN can verify their account email and reset the PIN without needing the old one.
- **Email Change Verification** — Changing the account email requires a verification code sent to the new address.

### 🔑 Password Vault
- Add, view, edit, and delete saved account credentials (site/app, username, password, URL, notes).
- Saved passwords are **encrypted at rest** (AES-256-CBC) and only decrypted for display.
- Revealing or copying a password asks for the **vault PIN**.
- **Clipboard auto-clear** — a copied password is automatically cleared from the clipboard after **30 seconds**.
- Organize credentials with **folders** and **tags**, and filter by either.
- Mark credentials as **favorites** for quick access.
- Live search suggestions while typing in the vault search bar.

### 📝 Notes Management
- Create, view, edit, and delete personal notes.
- Organize notes into folders alongside vault items.

### ✅ Task Management
- Create tasks with due dates and priorities.
- Drag-and-drop task board grouped by status: **To Do → In Progress → Done**.
- Pending-task badge in the sidebar and due-date indicators.

### 📅 Calendar
- Tasks that have a due date are shown on a calendar view.

### 📁 Folder Organization
- Create custom, color-coded folders to group related passwords and notes together.
- Move items between folders.

### ⭐ Favorites
- Mark important vault items, notes, or folders for one-click access.

### 📊 Dashboard
- At-a-glance counts of saved passwords, notes, tasks, and folders.
- Recently updated items and task overview.
- **World Clock widget** — track the current time across multiple countries/cities.

### 🔍 Search
- Global search bar in the top bar to quickly find saved items, plus dedicated search inside the vault.

### ⚙️ Account Settings
- Edit profile information and personalize the **theme** (light / dark / auto) and **accent color**.
- Change account password.
- Reset vault PIN (with the "Forgot PIN" recovery flow above).
- **Activity Log** — a timeline of account actions (logins, password/PIN changes, edits, exports, etc.), with the option to clear it from the user's view.
- **Export My Data** — export your data as **CSV, Excel (.xlsx), Word (.docx), or PDF**, gated behind PIN re-verification.
- Delete account.

### 🛠️ Administrator Side
Administrators log in from the same login page and are redirected to their own panel.
- **Dashboard** — system-wide statistics and overview.
- **Users** — view, add, edit, activate / deactivate / suspend, force logout, and delete user accounts.
- **User Details** — per-user profile and activity.
- **Activity Logs** — system-wide audit trail with filtering and export (CSV, Excel, Word, PDF).
- **Settings** — admin profile, password change, email change (with verification), and user summary / log exports.

---

## 🛡️ Security

- Passwords are hashed with PHP's `password_hash()` (bcrypt) — never stored in plain text.
- Vault credentials are encrypted using **AES-256-CBC** before being saved to the database. The key lives in `.env`, never in source code.
- The vault is gated by a separate numeric PIN, checked against weak/common PIN patterns on creation.
- Password, PIN, and email-change verification all use **hashed, time-limited, single-use codes** with a maximum attempt count before a new code is required.
- CSRF tokens are required on state-changing requests.
- Sensitive actions (reveal/copy password, data export, account deletion) require re-authentication via PIN or password.
- Copied passwords are cleared from the clipboard after 30 seconds.
- Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` over HTTPS.
- `.htaccess` blocks direct access to `.env`, `.sql`, `.log`, source folders (`app`, `config`, `includes`, `vendor`, ...), and every `.php` file except `index.php`.
- PHP/database errors are logged to `logs/php-error.log` and never shown to users.
- All account and admin activity is recorded in an audit trail (`activity_logs`).

---

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8+ (custom lightweight MVC — routes / controllers / models / views) |
| Database | MySQL / MariaDB (PDO, prepared statements) |
| Frontend | HTML5, custom CSS, vanilla JavaScript, [Tabler Icons](https://tabler.io/icons) |
| Mail | PHPMailer (SMTP, used for verification-code emails) |
| Exports | Dompdf (PDF), PHPWord (Word), built-in CSV and XLSX builders |
| Dependency Management | Composer |
| Local Server | XAMPP (Apache + MySQL) |

---

## 🗄️ Database

Squir uses a normalized **MySQL** database (3NF). Users and administrators are modeled as **separate entities**, each with their own credentials and access scope.

| Group | Tables |
|---|---|
| Accounts | `users`, `admins`, `user_pins` |
| Verification codes | `password_resets`, `admin_password_resets`, `pin_resets`, `email_changes`, `admin_email_changes` |
| Vault & content | `folders`, `vault`, `notes`, `tags`, `vault_tags`, `tasks`, `favorites` |
| Auditing | `activity_logs` |
| World Clock | `countries`, `cities`, `user_world_clocks` |

SQL files in `database/`:

| File | Purpose |
|---|---|
| `squirdb.sql` | **Main schema.** Creates the `squirdb` database, all tables, the World Clock city catalog, and the default administrator account. |
| `seed_data.sql` | *(Optional)* Sample data: 30 users with vaults, notes, tasks, and logs. Every sample user has the login password `123456789` and the vault PIN `123456`. |
| `reset_database.sql` | Empties every table and resets auto-increment. **Deletes all data.** |
| `myacc.sql` | Developer helper that inserts a personal test account with sample folders. |

The entity-relationship diagram is included in the repository (`ERD_diagram.drawio`, `squirdb.vuerd.json`).

---

## 📂 Project Structure

```
Squir/
├── app/
│   ├── controllers/      # Business logic (Auth, Vault, Notes, Tasks, Calendar, Settings, ...)
│   │   └── admin/        # Admin controllers (Auth, Dashboard, Users, Activity Logs, Settings)
│   ├── models/           # Database models (User, Vault, Note, Task, Folder, Favorite, WorldClock, ...)
│   │   └── admin/        # Admin models (Admin, AdminEmailChange, AdminPasswordReset)
│   ├── routes/           # Route handlers, mapped from index.php
│   │   └── admin/
│   └── views/            # PHP view templates, one folder per module
│       ├── admin/        # Admin pages and export templates
│       ├── auth/         # Login, register, PIN, username, reset pages
│       ├── calendar/  dashboard/  favorites/  folders/  notes/
│       ├── settings/  tasks/  vault/  partials/
├── assets/
│   ├── css/              # Page stylesheets (+ admin/)
│   ├── js/               # Page scripts (+ admin/)
│   └── images/           # Logos, mascot images, folder color icons
├── config/               # App configuration (timezone, code expiry, SMTP, error logging)
├── database/             # SQL schema, sample data, reset script
├── dist/assets/          # Tabler Icons fonts and other static template assets
├── includes/             # Bootstrap, DB connection, auth, mailer, .env loader, layout partials,
│                         #   DocxBuilder / XlsxBuilder (+ admin/ layout)
├── logs/                 # php-error.log (created automatically, not committed)
├── vendor/               # Composer packages (created by `composer install`)
├── .env.example          # Template for your private .env
├── .htaccess             # URL rewriting + blocking of sensitive files
├── composer.json
├── index.php             # Front controller / router (?route=...)
└── structure.txt         # Full, detailed folder tree
```

> 📄 The complete file-by-file tree is in [`structure.txt`](structure.txt).

---

## 🚀 Getting Started

These steps use **XAMPP on Windows**. Laragon or WAMP work the same way.

### Prerequisites

- **PHP 8.0 or higher** (comes with XAMPP) with these extensions enabled in `php.ini`: `pdo_mysql`, `openssl`, `mbstring`, `zip`, `gd`, `xml`, `curl`
- **MySQL / MariaDB** (comes with XAMPP)
- **[Composer](https://getcomposer.org/download/)**
- **Apache with `mod_rewrite`** enabled and `AllowOverride All` for `htdocs` (default in XAMPP)
- A **Gmail account with an App Password** (for emailing verification codes)
- Node.js is **not required** to run the app.

### Installation

1. **Get the project**
   ```bash
   git clone https://github.com/<your-username>/squir.git
   ```
   Place the folder inside XAMPP's web root so it ends up at `C:\xampp\htdocs\squir`.

2. **Start XAMPP**
   Open the XAMPP Control Panel and start **Apache** and **MySQL**.

3. **Install PHP dependencies**
   ```bash
   cd C:\xampp\htdocs\squir
   composer install
   ```

4. **Create the database**
   - Open phpMyAdmin at `http://localhost/phpmyadmin`.
   - Go to the **Import** tab, choose `database/squirdb.sql`, and click **Import**. This creates the `squirdb` database with all tables, the World Clock data, and the default admin account.
   - *(Optional)* Import `database/seed_data.sql` afterward to load sample users and data.

   Or from the command line:
   ```bash
   mysql -u root -p < database/squirdb.sql
   mysql -u root -p squirdb < database/seed_data.sql   # optional sample data
   ```

5. **Create your `.env` file**
   Copy `.env.example` to `.env`:
   ```bash
   copy .env.example .env
   ```
   Then fill it in. Write each value on the same line as its name and do not add comments after a value.

   | Variable | Description |
   |---|---|
   | `APP_ENV` | `local` for XAMPP, `production` for real hosting |
   | `VAULT_ENCRYPTION_KEY` | 64 random hex characters (see step 6) |
   | `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Your MySQL settings (XAMPP default: `localhost`, `3306`, `squirdb`, `root`, empty password) |
   | `SMTP_USERNAME` | Your Gmail address |
   | `SMTP_PASSWORD` | A [Google App Password](https://myaccount.google.com/apppasswords), not your normal Gmail password |
   | `MAIL_DEV_FALLBACK` | `true` shows verification codes on screen when email fails. Works only with `APP_ENV=local` on your own computer. Keep `false` elsewhere. |
   | `MAIL_DEBUG` | `true` logs the SMTP conversation to `includes/logs/mail-debug.log`. Local use only. |

6. **Generate the vault encryption key**
   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```
   Paste the output as the value of `VAULT_ENCRYPTION_KEY` in `.env`.
   > ⚠️ Never change this key after users have saved vault passwords — they would become unreadable. Never commit `.env`.

7. **Open the app**
   ```
   http://localhost/squir
   ```
   - **Users:** click *Register*, create an account, then set up your PIN and username.
   - **Administrator:** log in from the same login page with the admin account defined in `database/squirdb.sql` (section 15, *Seed: Administrator Account*). Change that password right after your first login, especially on a live server.
   - **Sample users:** if you imported `seed_data.sql`, log in with any sample user's email (e.g. `juandc@example.com`), password `123456789`, and PIN `123456`.

### Deploying to a live server (e.g. InfinityFree)

1. Upload the project files, including the `vendor/` folder (or run `composer install` if the host allows it).
2. Create a MySQL database on the host and import `database/squirdb.sql` (edit the `CREATE DATABASE` / `USE` lines to match your hosting database name).
3. Create the `.env` on the server with `APP_ENV=production`, the host's database credentials, and your own `VAULT_ENCRYPTION_KEY`.
4. Keep `MAIL_DEV_FALLBACK=false` and `MAIL_DEBUG=false`.

---

## 🩺 Troubleshooting

| Problem | Fix |
|---|---|
| Blank page or "temporarily unavailable" | Check `logs/php-error.log`. Most often the `.env` DB values are wrong or MySQL is not running. |
| Every page except login shows 404 | Enable `mod_rewrite` and set `AllowOverride All` in Apache, then restart Apache. |
| `vendor/autoload.php` not found | Run `composer install` in the project folder. |
| Verification code email not arriving | Check `SMTP_USERNAME` / `SMTP_PASSWORD` (must be an App Password). For local testing set `APP_ENV=local` and `MAIL_DEV_FALLBACK=true`. |
| Vault passwords show errors or cannot be decrypted | `VAULT_ENCRYPTION_KEY` is empty or was changed. Restore the original key. |
| Word / Excel / PDF export fails | Make sure the `zip`, `gd`, `mbstring`, and `xml` PHP extensions are enabled and Composer packages are installed. |

---

## 👨‍💻 Team

| Name | Role |
|---|---|
| Junio, Ian Christopher L. | Project Manager, Programmer |
| Andaya, Eilis Mae S. | UI/UX Designer |
| Biacan, Tiffany Quenn D. | System Tester & Documentation |
| Estrada, Nash Basti M. | UI/UX Designer |
| Gozo, Ace B. | System Tester |

---

## 📄 License

This project was developed for academic purposes.