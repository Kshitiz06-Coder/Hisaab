# Hissab — Sustainable Finance System

A full-stack income & expense tracker built with **HTML, CSS, JavaScript, and PHP**, backed by **MySQL** via **XAMPP**. Matches the Figma flow: Main Page → Register/Login → Dashboard → Income / Expenses / Savings / Reports → Settings.

## 1. Requirements
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 7.4+)

## 2. Setup

1. **Copy the project folder**
   Copy the whole `hissab` folder into your XAMPP `htdocs` directory, e.g.:
   ```
   C:\xampp\htdocs\hissab      (Windows)
   /Applications/XAMPP/htdocs/hissab   (Mac)
   /opt/lampp/htdocs/hissab    (Linux)
   ```

2. **Start XAMPP**
   Open the XAMPP Control Panel and start **Apache** and **MySQL**.

3. **Create the database**
   - Go to [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Click **Import** → choose `sql/hissab.sql` → click **Go**
   - This creates the `hissab_db` database with all tables and default categories.

   *(Alternative: open the SQL tab in phpMyAdmin and paste the contents of `sql/hissab.sql`.)*

4. **Check the DB connection**
   Open `config/db.php` — the defaults match a stock XAMPP install:
   ```php
   $DB_HOST = 'localhost';
   $DB_USER = 'root';
   $DB_PASS = '';
   $DB_NAME = 'hissab_db';
   ```
   Change these only if your MySQL setup uses a different user/password.

5. **Open the app**
   Visit: [http://localhost/hissab/](http://localhost/hissab/)

   Click **Get Started** to register your first account, then you're straight into the dashboard.

## 3. Project structure

```
hissab/
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

## 4. Features implemented
- Secure auth: `password_hash()` / `password_verify()`, prepared statements everywhere (SQL-injection safe)
- Session-based access control (`includes/auth_check.php`)
- Income & Expense tracking with categories, notes, monthly filtering, edit/delete modals
- Savings goals with progress bars and an "add funds" flow
- Dashboard with a live Chart.js cash-flow graph and recent-activity feed
- Reports page with per-category breakdown bars and a monthly savings rate
- Settings: edit profile, change password, set currency symbol
- Fully responsive: collapsible sidebar / hamburger menu below 768px, stacking grids, scrollable tables on small screens

## 5. Notes for your report / demo
- Passwords are hashed with PHP's built-in bcrypt (`PASSWORD_DEFAULT`) — never stored in plain text.
- All database queries use **prepared statements** (`mysqli_prepare` + `bind_param`) to prevent SQL injection.
- Each protected page starts a session and checks `$_SESSION['user_id']`; users can only see/edit their own rows (every query is scoped by `user_id`).
- The currency symbol (`Rs` by default) is configurable per-user in Settings → Preferences.
