# Squir — Design System

## 1. Design Purpose

This document defines the visual and interaction system for **Squir: Your Personal Digital Vault and Productivity Companion**.

The design must stay consistent across all user and admin pages and must support both **Light Mode** and **Dark Mode**.

The provided Squir dashboard references are the primary visual direction for the application:
- Warm, clean, minimalist Light Mode
- Premium dark, high-contrast Dark Mode
- Squir squirrel mascot and brown/orange brand identity
- Spacious dashboard layout
- Simple cards, clear typography, outlined icons, and restrained effects
- Same information architecture and component structure between light and dark themes

The goal is to avoid generic or "AI slop" interfaces. Every visual element should have a clear purpose.

---

# 2. Brand Identity

## Brand Name

**Squir**

## Product Name

**Squir: Your Personal Digital Vault and Productivity Companion**

## Brand Personality

Squir should feel:

- Secure
- Friendly
- Organized
- Calm
- Modern
- Approachable
- Trustworthy
- Productivity-focused

The interface should feel like a personal digital workspace rather than a corporate enterprise dashboard.

## Mascot

The squirrel mascot is part of the Squir visual identity.

Use the mascot primarily in:
- Logo/brand area
- Login/register pages where appropriate
- Empty states where appropriate
- Sidebar decoration where appropriate

Do not place the mascot everywhere. It should remain a recognizable brand element rather than becoming decoration.

---

# 3. Visual Direction

The main visual style is:

> **Warm Minimalism + Personal Productivity + Secure Digital Vault**

Reference characteristics:

### Light Mode
- Warm off-white page background
- White content surfaces
- Dark brown primary text
- Brown primary buttons
- Soft beige/brown borders
- Warm accent areas
- Clean outlined icons
- Very subtle shadows
- Generous whitespace

### Dark Mode
- Near-black page background
- Very dark charcoal surfaces
- White/light-gray text
- Brown/orange primary actions
- Orange/gold accents
- Subtle dark borders
- Minimal shadows
- Strong but controlled contrast

### Both Modes
The layout must remain structurally consistent.

Do not create a completely different interface for Dark Mode.

---

# 4. Color System

Use these tokens consistently.

## Brand Colors

### Brown

```text
Brown 900  #3B1F0F
Brown 800  #4A2812
Brown 700  #5A3217
Brown 600  #6B3B1E
Brown 500  #7A4524
```

Brown is the primary Squir brand color.

Use it for:
- Primary buttons
- Active navigation
- Important controls
- Branding
- Selected states

### Orange / Gold Accent

```text
Orange 500  #F59E0B
Orange 600  #D97706
Gold 500    #EAC513
```

Use accents sparingly for:
- Icons
- Favorites
- Highlights
- Important indicators
- Dark Mode active states
- Small decorative brand details

Do not turn the whole interface orange.

---

# 5. Light Mode Tokens

```text
Background       #FAF9F7
Surface          #FFFFFF
Surface Soft     #F7F1EA

Text Primary     #171717
Text Secondary   #5F5A55
Text Muted       #8A837C

Border           #E8E1DA
Border Strong    #D9CEC3

Primary          #5A3217
Primary Hover    #4A2812

Accent           #EAC513

Success          #16A34A
Warning          #D97706
Danger           #DC2626
Info             #2563EB
```

The Light Mode reference should feel warm rather than pure white/blue-gray.

Avoid using cold gray backgrounds as the dominant page background.

---

# 6. Dark Mode Tokens

```text
Background       #07090B
Surface          #0D1013
Surface Raised   #12161A
Surface Hover    #171B20

Text Primary     #F8FAFC
Text Secondary   #CBD5E1
Text Muted       #94A3B8

Border           #252A30
Border Strong    #343A42

Primary          #6B3B1E
Primary Hover    #7A4524

Accent           #F59E0B
Accent Soft      #3A260E

Success          #4ADE80
Warning          #FBBF24
Danger           #F87171
Info             #60A5FA
```

Dark Mode should be close to black, but not pure `#000000` everywhere.

The orange/brown accent should stand out against the dark background without overwhelming the interface.

---

# 7. Color Usage Rules

Use approximately:

- 70–80% neutral/background colors
- 15–20% surfaces and borders
- 5–10% brand/accent colors

Do not use accent colors for large areas unless necessary.

### Status Colors

Use semantic colors consistently:

- Green = success/completed
- Yellow/orange = warning/pending
- Red = danger/overdue/destructive
- Blue = informational

Never communicate important information using color alone.

---

# 8. Typography

Typography should be clean, readable, and friendly.

Use the font already supplied by the teacher/PWA template when it matches the visual reference.

If a replacement is necessary, use a modern sans-serif with strong readability.

## Type Scale

```text
Page Title       28–36px
Section Title    20–24px
Card Title       16–18px
Body             14–16px
Secondary        13–14px
Caption          12–13px
```

## Weight

```text
Regular          400
Medium           500
Semibold         600
Bold             700
```

Use bold primarily for:
- Page headings
- Important numbers
- Card titles
- Navigation labels where needed

Do not make every element bold.

---

# 9. Spacing System

Use a 4px-based spacing system.

```text
4px
8px
12px
16px
20px
24px
32px
40px
48px
64px
```

Preferred common spacing:

```text
Card padding       20–24px
Section gap        24–32px
Input padding      12–16px
Button padding     10–14px
Navigation gap     8–12px
Page padding       24–40px
```

Keep whitespace generous, especially on dashboard pages.

---

# 10. Border Radius

Use moderate rounded corners.

```text
Small        6px
Medium       8px
Large        12px
Card         12–16px
Pill         999px
```

Avoid excessive pill-shaped UI.

Buttons should normally use a moderate radius rather than a full pill.

---

# 11. Shadows

Shadows should be subtle.

### Light Mode

Use soft shadows primarily for:
- Dropdowns
- Modals
- Floating controls
- Important elevated cards

Normal cards should rely mostly on borders.

### Dark Mode

Prefer borders and contrast instead of heavy shadows.

Avoid large glowing shadows and neon effects.

---

# 12. Layout System

Squir uses a dashboard-oriented layout.

## Desktop

```text
┌─────────────────────────────────────────────────────┐
│ Sidebar │ Header / Search / User                    │
│         ├───────────────────────────────────────────┤
│         │ Page Heading                    Quick Add │
│         │                                           │
│         │ Summary Cards                             │
│         │                                           │
│         │ Main Content         Secondary Content    │
│         │                                           │
│         │ Additional Sections                       │
└─────────────────────────────────────────────────────┘
```

## Sidebar

The sidebar contains:

```text
Squir Logo
──────────────
Dashboard
Passwords
Notes
Tasks
Folders
Favorites
──────────────
Settings
Log Out
```

The active page should have a clear highlighted background.

## Header

The header may contain:

- Search
- Notifications
- User avatar
- User name
- Account dropdown
- Dark Mode toggle where appropriate

The header should remain visually lightweight.

---

# 13. Dashboard Design

The dashboard is the main visual reference for Squir.

## Greeting

Use a personal greeting:

```text
What's on your mind today, Ian?
Here's what's happening with your vault today.
```

The actual name must come from the authenticated user.

## Quick Add

The dashboard may contain a prominent:

```text
+ Quick Add
```

button.

It should provide quick access to approved MVP creation actions such as:
- Add Password
- Add Note
- Add Task
- New Folder

## Summary Cards

Summary cards should be simple and consistent.

Example:

```text
┌─────────────────────┐
│  🔒  Passwords      │
│      24             │
│      Total saved    │
└─────────────────────┘
```

Current MVP summary cards:

- Passwords
- Notes
- Tasks
- Folders

Do not add an Income card unless the product scope and database are officially expanded.

## Tasks Overview

Show a compact list of relevant tasks.

Each row can contain:
- Completion checkbox
- Task title
- Due date/status
- More-actions menu

Examples of states:
- Due today
- Due tomorrow
- Due date
- No due date

## Recent Items

Show recently accessed/created items.

Possible item types:
- Password
- Note
- Task

Each item should clearly indicate its type.

Favorites use the star icon.

## Folders

Show the user's folders as simple visual cards.

Each folder card may contain:
- Folder icon
- Folder name
- Item count

Include:

```text
+ New Folder
```

when appropriate.

---

# 14. Navigation Design

## User Navigation

```text
Dashboard
Passwords
Notes
Tasks
Folders
Favorites
```

Secondary:

```text
Settings
Log Out
```

The order should remain consistent across pages.

## Admin Navigation

Admin navigation should be separate from the normal user's workspace.

Recommended:

```text
Admin Dashboard
Users
Activity Logs
```

Use the existing teacher-provided `dist/admin` template design where available.

---

# 15. Password Vault Design

The Password Vault is a security-sensitive page.

## List

Each password entry may display:

```text
Icon
Title
Username
Website
Folder
Favorite
Actions
```

Never show the actual password by default.

## Password Visibility

Use:

```text
••••••••••
```

with an intentional reveal action.

Possible actions:
- Reveal
- Copy
- Edit
- Delete
- Favorite

## Security UI

Avoid:
- Passwords in URLs
- Passwords in activity logs
- Passwords in browser-visible debug output
- Accidental automatic reveal

---

# 16. Notes Design

Notes should use a clean document/card layout.

Each note may show:

```text
Title
Preview
Folder
Updated date
Favorite
Actions
```

The full note should open in a dedicated view/edit screen.

---

# 17. Tasks Design

Tasks should visually communicate status without relying only on color.

Task row:

```text
○ Task title                 Due today   ⋮
```

Possible status:

```text
Pending
In Progress
Completed
```

Possible priority:

```text
Low
Medium
High
```

Completed tasks should have a clear visual distinction such as:
- checked checkbox
- muted text
- optional strikethrough

Do not make completed tasks disappear immediately unless the user explicitly filters them out.

---

# 18. Folders Design

Folder cards should remain visually simple.

Example:

```text
┌─────────────────┐
│       📁        │
│     School      │
│     15 items    │
└─────────────────┘
```

Folder colors should not become arbitrary per-folder colors.

Use the approved Squir palette.

---

# 19. Favorites Design

Favorites should provide one unified view of:

- Password favorites
- Note favorites
- Task favorites

Use a consistent star interaction.

Filled star:
```text
★
```

Unfavorited:
```text
☆
```

The star should have an accessible label such as:

```text
Add to favorites
Remove from favorites
```

---

# 20. Search Design

The global search field should be easy to find.

Placeholder:

```text
Search anything...
```

Search results should clearly identify:
- Item type
- Title
- Relevant metadata
- Available actions

Search must only return records the authenticated user is authorized to see.

---

# 21. Forms

All forms must use:

- Clear labels
- Appropriate input types
- Helpful placeholders when needed
- Visible validation states
- Clear error messages
- Clear success feedback
- Keyboard accessibility

Example:

```text
Title
[________________________]

Username
[________________________]

Password
[________________________]

Website
[________________________]

Folder
[ Select folder ▼ ]

        [ Cancel ] [ Save ]
```

Do not depend only on placeholder text as the label.

---

# 22. Buttons

## Primary

Used for the main action:

```text
+ Add Password
Save
Create Task
New Folder
```

Use Squir brown.

## Secondary

Used for supporting actions:

```text
Cancel
View All
Back
```

Use neutral/outlined styling.

## Destructive

Used for:

```text
Delete
Suspend User
```

Use danger styling and require confirmation for destructive actions.

---

# 23. Icons

Use one consistent icon style.

Preferred:
- Simple outlined icons
- Consistent stroke weight
- Familiar symbols

Examples:

```text
Dashboard    Home
Passwords    Lock
Notes        File
Tasks        Clipboard
Folders      Folder
Favorites    Star
Settings     Gear
Search       Magnifier
```

Do not mix many unrelated icon libraries or visual styles.

---

# 24. Dark Mode

Dark Mode must be a complete theme, not merely an inverted Light Mode.

## Requirements

- Persist the user's theme preference.
- Use near-black background.
- Use dark raised surfaces.
- Use light readable text.
- Keep orange/brown accents.
- Maintain accessible contrast.
- Avoid excessive glow.
- Keep the same layout and component hierarchy.

## Toggle

The UI may show:

```text
☾ Dark Mode    [ toggle ]
```

When enabled, the entire interface should switch consistently.

---

# 25. Responsive Design

## Desktop
Use the full sidebar and multi-column dashboard layout.

## Tablet
- Reduce content width
- Keep navigation accessible
- Reduce card columns where necessary

## Mobile
- Collapse sidebar into a menu
- Stack dashboard cards
- Stack content sections
- Make tables horizontally scrollable or responsive
- Keep primary actions easy to reach

Never allow content to become unreadably compressed.

---

# 26. Accessibility

Every page must support:

- Keyboard navigation
- Visible focus states
- Semantic HTML
- Accessible labels
- Sufficient color contrast
- Meaningful alt text
- Screen-reader-friendly controls
- No color-only status communication
- Reduced motion support

For icons used as buttons, provide an accessible name.

Example:

```html
<button aria-label="Reveal password">
```

---

# 27. Animation and Motion

Animation should be subtle and functional.

Preferred duration:

```text
150ms–250ms
```

Use motion for:
- Hover states
- Dropdowns
- Modals
- Theme transitions
- Small feedback interactions

Avoid:
- Constant floating animations
- Large page transitions
- Excessive bouncing
- Decorative particle effects
- Neon/glow animations

Respect:

```css
@media (prefers-reduced-motion: reduce)
```

---

# 28. CSS Organization

## Global

```text
assets/css/custom.css
```

Contains:
- Global variables
- Shared utilities
- Shared components
- Theme variables

## Page-Specific

```text
assets/css/dashboard.css
assets/css/vault.css
assets/css/folders.css
assets/css/notes.css
assets/css/tasks.css
assets/css/favorites.css
assets/css/search.css
assets/css/auth.css
assets/css/admin.css
```

Only add page-specific styles when the component cannot reasonably use existing shared styles.

Avoid duplicate CSS.

---

# 29. Template Integration

The teacher-provided PWA/Tailwind template is the visual foundation.

Use:
- Existing template assets
- Existing Tailwind configuration
- Existing layout patterns
- Existing admin design in `dist/admin`, if present
- Existing reusable header/sidebar/footer structures

Squir-specific UI should adapt the template rather than creating an unrelated visual system.

---

# 30. Design Anti-Patterns

Do NOT introduce:

- Random gradients
- Excessive glassmorphism
- Excessive rounded pills
- Giant typography everywhere
- Too many shadows
- Neon UI
- Unnecessary animations
- Random accent colors
- Excessive dashboard charts
- Decorative elements with no purpose
- Different component styles on every page
- Inconsistent dark/light behavior
- Fake statistics in production UI

---

# 31. Product Scope Visual Rule

The visual design must reflect the approved Squir MVP.

Current primary areas:

```text
Dashboard
Passwords
Notes
Tasks
Folders
Favorites
Search
Settings
Admin
```

**Income / Expenses / Savings are NOT currently part of the approved database or MVP.**

Therefore, the Income Summary shown in the visual reference is treated only as a design-reference element and must not be implemented unless the project requirements and database are formally updated.

---

# 32. Design Acceptance Checklist

Before considering a page complete:

### Branding
- [ ] Squir identity is visible
- [ ] Mascot is used appropriately
- [ ] Brown/orange/gold brand language is consistent

### Theme
- [ ] Light Mode works
- [ ] Dark Mode works
- [ ] Both modes preserve the same structure
- [ ] Contrast is accessible

### Layout
- [ ] Sidebar is consistent
- [ ] Header is consistent
- [ ] Spacing follows the 4px system
- [ ] Cards follow the same visual language
- [ ] Responsive behavior works

### Components
- [ ] Buttons are consistent
- [ ] Forms are accessible
- [ ] Icons use one visual style
- [ ] Empty states are useful
- [ ] Error/success states are clear

### Security UI
- [ ] Vault passwords are hidden by default
- [ ] Secrets are never exposed unnecessarily
- [ ] Destructive actions require confirmation

### Scope
- [ ] Feature exists in PRD
- [ ] No unnecessary feature was added
- [ ] No unrelated visual section was added
- [ ] Design matches Squir's actual database/features

### Quality
- [ ] No AI-slop decoration
- [ ] No duplicated CSS without reason
- [ ] No inconsistent colors
- [ ] No inconsistent spacing
- [ ] No unnecessary animations
