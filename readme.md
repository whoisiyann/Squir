# 🐿️ Squir — Your Personal Digital Vault and Productivity Companion

![PHP](https://img.shields.io/badge/PHP-8+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-Styling-06B6D4?logo=tailwindcss&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=black)
![PHPMailer](https://img.shields.io/badge/PHPMailer-OTP_Email-blue)
![Dompdf](https://img.shields.io/badge/Dompdf-PDF_Export-orange)
![Composer](https://img.shields.io/badge/Composer-Dependency_Manager-885630?logo=composer&logoColor=white)
![Encryption](https://img.shields.io/badge/Encryption-AES--256-red)
![Status](https://img.shields.io/badge/Status-Active-brightgreen)
![License](https://img.shields.io/badge/License-Academic-blue)

Squir is a web-based system that helps individuals keep their passwords, notes, tasks, and personal information organized in one secure, easy-to-use platform. It combines an **encrypted password vault**, **notes**, **task management**, and **folder organization** into a single dashboard, so users no longer need to juggle multiple apps to stay on top of their digital life.

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
- [Roadmap](#-roadmap)
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

---

## ✨ Features

### 🔐 Account & Access
- **User Registration & Login** — Create an account and log in securely (hashed passwords).
- **Forgot Password (OTP-based)** — Reset a forgotten account password using a 6-digit code emailed to the user, with expiry and attempt limits.
- **Vault PIN Lock** — A separate 6-digit PIN gate protects access to the vault, independent of the account password.
- **Forgot PIN (OTP-based)** — Users who forget their vault PIN can verify their account email and reset the PIN without needing the old one.
- **Administrator Accounts** — Admins are modeled as a separate entity from regular users in the database, laying the groundwork for a dedicated admin/moderation side of the system.

### 🔑 Password Vault
- Add, view, edit, and delete saved account credentials (site/app, username, password, URL, notes).
- Saved passwords are **encrypted at rest** (AES-256-CBC) and only decrypted for display.
- Organize credentials with **folders** and **tags**, and filter by either.
- Mark credentials as **favorites** for quick access.

### 📝 Notes Management
- Create, view, edit, and delete personal notes with rich text formatting.
- Organize notes into folders alongside vault items.

### ✅ Task Management
- Create tasks with due dates and track their progress.
- Visual task board grouped by status: **To Do → In Progress → Done**.
- Automatic due-date indicators (e.g., overdue, due soon) on the dashboard.

### 📁 Folder Organization
- Create custom folders to group related passwords and notes together.

### ⭐ Favorites
- Mark important passwords, notes, or tasks for one-click access.

### 📊 Dashboard
- At-a-glance counts of saved passwords, notes, tasks, and folders.
- Recently updated items.
- Task board overview.
- **World Clock widget** — track the current time across multiple countries/cities.

### 🔍 Search
- Global search across passwords, notes, and tasks from a single search bar.

### ⚙️ Account Settings
- Edit profile information.
- Change account password.
- Reset vault PIN (with the "Forgot PIN" recovery flow above).
- **Activity Log** — a timeline of account actions (logins, password/PIN changes, edits, exports, etc.), with the option to clear it.
- **Export My Data** — export vault/notes/tasks data as **JSON** or a formatted **PDF**, gated behind PIN re-verification.
- Delete account.

---

## 🛡️ Security

- Passwords are hashed with PHP's `password_hash()` (bcrypt) — never stored in plain text.
- Vault credentials are encrypted using **AES-256-CBC** before being saved to the database.
- The vault is gated by a separate numeric PIN, checked against weak/common PIN patterns on creation.
- Password and PIN recovery both use **hashed, time-limited, single-use OTP codes** (not reusable tokens), with a maximum attempt count before a new code is required.
- CSRF tokens are required on all state-changing form submissions.
- Sensitive actions (data export, account deletion) require re-authentication via PIN or password.
- All account activity is recorded in an audit trail (`activity_logs`).

---

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8+ (custom lightweight MVC — routes / controllers / models / views) |
| Database | MySQL (PDO, prepared statements) |
| Frontend | HTML5, Tailwind CSS, vanilla JavaScript, SweetAlert2 |
| Mail | PHPMailer (SMTP, used for OTP emails) |
| PDF Export | Dompdf |
| Build Tools | Gulp, Tailwind CLI, npm |
| Dependency Management | Composer (PHP), npm (frontend assets) |

---

## 🗄️ Database

Squir uses a normalized **MySQL** database (3NF) with dedicated tables for users, admins, vault items, notes, tasks, folders, tags, favorites, PIN/password reset codes, activity logs, and world clock data. Users and administrators are modeled as **separate entities**, each with their own credentials and access scope.

An entity-relationship diagram is included in the repository (`ERD_diagram.drawio`) for reference.

> 📌 Database setup instructions are in [Getting Started](#-getting-started) below.

---

## 📂 Project Structure

```
Squir/
├── app/
│   ├── controllers/     # Business logic (Auth, Vault, Notes, Tasks, Settings, etc.)
│   ├── models/          # Database models (User, Vault, Note, Task, PinReset, etc.)
│   ├── routes/          # Route handlers, mapped from index.php
│   └── views/           # PHP view templates
├── assets/
│   ├── css/             # Stylesheets (Tailwind output + custom CSS)
│   ├── js/              # Frontend JavaScript
│   └── images/          # Static images / icons
├── config/              # App configuration (constants, mail, encryption keys)
├── database/            # SQL schema and migrations
├── includes/            # Shared includes (bootstrap, DB connection, mailer, sidebar/header)
├── index.php            # Front controller / router
└── .htaccess            # URL rewriting
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.0 or higher
- MySQL / MariaDB
- Composer
- Node.js & npm (only needed if you plan to rebuild the Tailwind CSS assets)
- A local server stack such as XAMPP, Laragon, or WAMP

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/<your-username>/squir.git
   cd squir
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Set up the database**
   - Create a MySQL database (e.g. `squirdb`).
   - Import the schema:
     ```bash
     mysql -u root -p squirdb < database/squirdb.sql
     mysql -u root -p squirdb < database/world_clocks.sql
     mysql -u root -p squirdb < database/pin_resets_migration.sql
     ```

4. **Configure the database connection**
   Edit `includes/dbconnect.php` and update the constants to match your local setup:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3306');   // adjust to your MySQL port
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'squirdb');
   ```

5. **Configure environment variables**
   Create a `.env` file in the project root (used for email/OTP sending):
   ```env
   SMTP_USERNAME=your_gmail_address@gmail.com
   SMTP_PASSWORD=your_gmail_app_password
   ```
   > Use a [Google App Password](https://myaccount.google.com/apppasswords) rather than your regular Gmail password.
   >
   > If SMTP is left unconfigured, OTP codes are shown directly on-screen in development mode so the app remains testable without email.

6. **Set the vault encryption key**
   In `config/config.php`, set a strong, unique value for:
   ```php
   define('VAULT_ENCRYPTION_KEY', 'your-own-random-secret-key');
   ```

7. **(Optional) Rebuild frontend assets**
   ```bash
   npm install
   npm run start   # development
   npm run build   # production build
   ```

8. **Serve the app**
   Point your local server (Apache/Nginx) to the project root, making sure `mod_rewrite` is enabled so `.htaccess` routing works. Then visit:
   ```
   http://localhost/squir
   ```

---

## 🗺️ Roadmap

- [ ] Full administrator dashboard (user management, moderation, system-wide activity log)
- [ ] Two-factor authentication for login
- [ ] Shared/collaborative folders

---

## 👨‍💻 Team

- Junio, Ian Christopher L.
- Andaya, Eilis Mae S.
- Estrada, Nash Basti M.
- Biacan, Tiffany Quenn D.
- Gozo, Ace B.

---

## 📄 License

This project was developed for academic purposes.