# Squir — Product Requirements Document (PRD)

## 1. Project Identity
**Project:** Squir: Your Personal Digital Vault and Productivity Companion  
**Type:** Web-based personal information management system  
**Target Users:** Students/individuals; secondary target: working individuals  
**Stack:** Native PHP (OOP), MySQL, XAMPP, HTML/CSS/JavaScript, Tailwind CSS through the provided PHP/PWA template.

## 2. Product Vision
Squir is a simple personal digital vault and productivity companion that helps users securely organize passwords, personal notes, and daily tasks in one place.

Core values:
- Simplicity
- Organization
- Security
- Productivity
- Easy access

## 3. Goals
1. User registration and secure login.
2. Password-vault CRUD.
3. Notes CRUD.
4. Task management.
5. Folder organization for passwords and notes.
6. Favorites for supported items.
7. Simple dashboard.
8. Search.
9. Administrator account and administration functions.
10. Activity logging for important security/account actions.

## 4. MVP Scope

### Authentication
- Registration
- Login/logout
- PHP sessions
- User/admin roles
- Account statuses: active, inactive, suspended

### Password Vault
- Create, view, edit, delete entries
- Title, account username, encrypted account password, website URL, notes
- Optional folder

### Notes
- Create, view, edit, delete
- Optional folder

### Tasks
- Create, edit, delete
- Status: pending, in_progress, completed
- Priority: low, medium, high
- Optional due date

### Folders
- Create, edit, delete
- Organize passwords and notes
- Deleting a folder sets related `folder_id` to NULL

### Favorites
- Favorite/unfavorite password, note, or task
- Show favorites
- Exactly one item reference per favorite row

### Dashboard
- Useful summary and quick access to major features

### Search
- Search user-owned stored information
- Never return another user's private records

### Administration
- Admin dashboard
- User management/viewing
- Activity-log review
- Account status management where implemented

## 5. Explicitly Out of MVP Scope
Do not add these unless the requirements are formally changed:
- React/Vue/Angular frontend
- Mobile app
- Browser extension
- External password synchronization
- Shared/team vaults
- Social features
- Payments/subscriptions
- AI productivity features
- Complex analytics
- Third-party authentication
- Biometric authentication
- Hardware security keys

## 6. Security Requirements
- Login passwords use `password_hash()` and `password_verify()`.
- Vault passwords must be encrypted before database storage because they must be retrievable for authorized viewing.
- Use PDO prepared statements.
- Validate and authorize all requests server-side.
- Verify record ownership before private CRUD operations.
- Admin routes require server-side admin authorization.
- Never log plaintext passwords or vault secrets.

## 7. Success Metrics
The MVP is successful when:
1. Registration/login works.
2. Vault CRUD works.
3. Vault passwords are encrypted at rest.
4. Notes CRUD works.
5. Tasks can be created, updated, completed, and deleted.
6. Passwords/notes can be organized with folders.
7. Favorites work for password/note/task.
8. Search respects ownership.
9. Admin can review users and activity logs.
10. Unauthorized users cannot access other users' data.
11. The application runs through XAMPP without React.
12. The implementation remains within this scope.

## 8. Scope-Control Rule
Before adding a feature, ask:
- Is it in the MVP?
- Does it directly support Squir's goals?
- Does it fit the current schema?
- Is it necessary now?
- Will it delay core MVP work?

If not, do not add it to the MVP.

## 9. Priority
**P0:** Authentication, authorization, vault, notes, tasks, folders, favorites, dashboard, search, admin, activity logs, security checks.  
**P1:** Filtering/sorting, polished empty states, confirmation dialogs, validation UX, responsive refinements.  
**P2:** Future ideas outside MVP.

## 10. Definition of Done
A feature is done only when its UI, server-side validation, authorization/ownership checks, database operations, error handling, tests, and documentation are complete and consistent with this context folder.
