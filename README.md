# Hisaab — Sustainable Finance System

A full-stack income & expense tracker built with **HTML, CSS, JavaScript, and PHP**, backed by **MySQL** via **XAMPP**.

## 1. Requirements
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 7.4+)

## 2. Project structure

```
hisaab/
├── index.php              Landing page
├── register.php           Sign up
├── login.php               Log in
├── logout.php              Destroy session
├── dashboard.php           Stats, chart, recent transactions
├── income.php              Income CRUD
├── expenses.php            Expenses CRUD
├── savings.php              Savings goals + add funds
├── reports.php              Category breakdowns
├── settings.php             Profile / password / preferences
├── config/
│   ├── db.php               MySQLi connection
│   └── functions.php        Shared helpers (money formatting, totals, etc.)
├── includes/
│   ├── auth_check.php       Session guard, included at top of every protected page
│   ├── head.php / topbar.php / footer_app.php / sidebar.php   Shared layout
├── css/style.css            Full responsive design system
├── js/script.js             Sidebar toggle, modals, delete confirmations
└── sql/hissab.sql           Database schema + seed categories
```

## 3. Features implemented
- Secure auth: `password_hash()` / `password_verify()`, prepared statements everywhere (SQL-injection safe)
- Session-based access control (`includes/auth_check.php`)
- Income & Expense tracking with categories, notes, monthly filtering, edit/delete modals
- Savings goals with progress bars and an "add funds" flow
- Dashboard with a live Chart.js cash-flow graph and recent-activity feed
- Reports page with per-category breakdown bars and a monthly savings rate
- Settings: edit profile, change password, set currency symbol
- Fully responsive: collapsible sidebar / hamburger menu below 768px, stacking grids, scrollable tables on small screens

## 4. Notes for your report / demo
- Passwords are hashed with PHP's built-in bcrypt (`PASSWORD_DEFAULT`) — never stored in plain text.
- All database queries use **prepared statements** (`mysqli_prepare` + `bind_param`) to prevent SQL injection.
- Each protected page starts a session and checks `$_SESSION['user_id']`; users can only see/edit their own rows (every query is scoped by `user_id`).
- The currency symbol (`Rs` by default) is configurable per-user in Settings → Preferences.
