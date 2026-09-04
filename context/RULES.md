# Squir — Coding and Implementation Rules

## 1. Core Principles
Squir code must follow:
- **SOLID**
- **DRY**
- **KISS**
- Security by default
- Scope discipline
- Clear naming
- Maintainable structure

## 2. Scope First
`PRD.md` is the first authority for feature scope.

Do not add a feature because:
- AI suggested it
- another app has it
- it looks impressive
- a library supports it
- it is technically interesting

If it is outside MVP, place it in future ideas instead.

## 3. KISS
Use the simplest implementation that correctly solves the requirement.

Avoid premature:
- frameworks
- service layers
- repositories
- factories
- complex routing
- abstractions

Complexity must have a real benefit.

## 4. SOLID

### Single Responsibility
Classes/functions have one clear responsibility.

### Open/Closed
Prefer extending well-defined behavior instead of editing unrelated logic repeatedly.

### Liskov Substitution
Only use inheritance where child classes can safely replace their parent.

### Interface Segregation
Interfaces, if used, stay small and relevant.

### Dependency Inversion
Use abstractions when they provide real value; do not create abstractions for theory alone.

## 5. DRY
Do not duplicate meaningful logic.

**Project rule:** if the same function or business logic is repeated **4x or more**, refactor it into a reusable helper/service/function when practical.

Do not turn tiny, unrelated similarities into unnecessary abstractions.

## 6. Naming
PHP classes: `PascalCase`  
Methods/functions: `camelCase`  
Database: `snake_case`  
Views: clear lowercase paths.

Examples:
```text
VaultController.php
ActivityLog.php
getUserById()
createFolder()
views/vault/index.php
user_id
created_at
```

## 7. PHP
- Use Native PHP OOP.
- Validate input server-side.
- Escape output.
- Use prepared statements.
- Do not put large SQL blocks in views.
- Do not trust client-side validation.
- Keep secrets/configuration out of source when appropriate.

## 8. Database
- Use PDO.
- Prepared statements for user-controlled values.
- Respect `squir_db` schema.
- Do not invent columns/tables in PHP.
- Update `SCHEMA.md` whenever schema changes.
- Preserve intended foreign-key behavior.

## 9. Authentication
Login passwords:
```php
password_hash($password, PASSWORD_DEFAULT);
password_verify($password, $hash);
```

Never:
- store plaintext login passwords
- compare plaintext passwords directly
- display password hashes

## 10. Vault Security
Login password = **hash** because it only needs verification.

Vault account password = **encrypt** because the original value must be retrievable for authorized viewing.

Never log:
- login passwords
- plaintext vault passwords
- encryption keys

## 11. Authorization
Authentication asks: "Who is the user?"  
Authorization asks: "Is this user allowed?"

Both are required.

Bad:
```php
$vault->getById($_GET['id']);
```

Preferred:
```php
$vault->getByIdForUser($_GET['id'], $_SESSION['user_id']);
```

Admin routes must verify admin privileges server-side.

## 12. Input Validation
Validate:
- required fields
- maximum lengths
- email
- IDs
- enum values
- dates
- URLs where applicable

Never rely only on HTML `required`/`maxlength`.

## 13. Output Escaping
For untrusted text:
```php
<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>
```

Do not render untrusted strings as raw HTML.

## 14. Controllers
Controllers:
- receive requests
- validate
- authorize
- call models/business logic
- redirect/render

Controllers should not become giant files.

## 15. Models
Models:
- perform database operations
- use prepared statements
- return data/results
- do not render HTML

Main models:
`User`, `Folder`, `Vault`, `Note`, `Task`, `Favorite`, `ActivityLog`.

## 16. Views
Views:
- render presentation
- use approved design system
- do not query the database
- avoid complex business logic
- escape output

Use shared components:
`header.php`, `navbar.php`, `sidebar.php`, `footer.php`.

## 17. Frontend
Use the teacher-provided template and its existing visual language.

Prefer existing:
- Tailwind utilities
- components
- assets
- shared styles

Do not introduce React or a second unrelated CSS framework.

## 18. JavaScript
JavaScript may handle:
- password reveal
- copy-to-clipboard
- confirmation dialogs
- modals
- small UI interactions

Server-side PHP remains authoritative for security and authorization.

## 19. Error Handling
- Do not expose database credentials.
- Do not expose sensitive SQL errors to users.
- Give useful user-facing messages.
- Log technical details only as appropriate.
- Handle expected failures gracefully.

## 20. Git
Prefer focused commits:
```text
feat: add vault CRUD
fix: validate folder ownership
style: improve dashboard
docs: update schema
refactor: extract shared validation
```

Avoid unrelated giant commits.

## 21. AI-Assisted Coding
For every AI-generated change:
1. Check `PRD.md`.
2. Check `ARCHITECTURE.md`.
3. Check `SCHEMA.md`.
4. Check `RULES.md`.
5. Test it.
6. Remove unnecessary code.

Never accept generated code just because it works once.

## 22. Change Protocol
```text
PRD
 ↓
DESIGN (if UI changes)
 ↓
SCHEMA (if database changes)
 ↓
ARCHITECTURE
 ↓
RULES
 ↓
Implementation
 ↓
Testing
 ↓
Documentation
```

## 23. Pre-Commit Checklist
- [ ] In PRD scope
- [ ] No unnecessary dependency
- [ ] No meaningful duplicated logic
- [ ] KISS/SOLID considered
- [ ] Ownership checks exist
- [ ] Admin checks are server-side
- [ ] Prepared SQL is used
- [ ] Output is escaped
- [ ] Secrets are protected
- [ ] Schema docs updated if needed
- [ ] UI follows DESIGN.md
- [ ] Tested
- [ ] No debug code/secrets committed

## 24. Final Rule
**Working, secure, understandable, and in-scope code is better than complicated code that only looks impressive.**
