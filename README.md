# LocalService Hub — Setup Guide
## PHP + MySQL + HTML/CSS/JS

---

## Project Structure

```
lsh-complete/
│
├── database.sql              ← Run this first in phpMyAdmin
│
├── frontend/                 ← All HTML pages + CSS + JS
│   ├── index.html            ← Home page
│   ├── login.html            ← Login page
│   ├── register.html         ← Registration (customer & provider)
│   ├── browse.html           ← Browse all providers
│   ├── booking.html          ← Book a service
│   ├── bookings.html         ← Customer: my bookings
│   ├── provider.html         ← Provider dashboard
│   ├── admin.html            ← Admin panel
│   ├── css/
│   │   └── style.css         ← All styles
│   └── js/
│       └── app.js            ← API helpers + shared functions
│
└── backend/                  ← PHP backend
    ├── config/
    │   └── db.php            ← Database connection (edit credentials here)
    ├── includes/
    │   └── session.php       ← Session / auth helpers
    └── api/
        ├── auth.php          ← Login, Register, Logout, Me
        ├── providers.php     ← List providers, Provider detail
        ├── bookings.php      ← Create booking, My bookings, Provider requests
        └── admin.php         ← Admin stats, All bookings, All providers
```

---

## Step 1 — Set up the Database

1. Open **phpMyAdmin** (usually http://localhost/phpmyadmin)
2. Click **Import** → choose `database.sql` → click **Go**
3. This creates the `lsh_db` database with all tables and demo data

---

## Step 2 — Configure Database Credentials

Open `backend/config/db.php` and update if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password (blank for XAMPP default)
define('DB_NAME', 'lsh_db');
```

---

## Step 3 — Place Files in XAMPP

Copy the entire `lsh-complete` folder into:
```
C:\xampp\htdocs\lsh-complete\
```

Then open your browser at:
```
http://localhost/lsh-complete/frontend/index.html
```

---

## Step 4 — Login with Demo Accounts

All demo passwords are: **123456**

| Role     | Email              |
|----------|--------------------|
| Customer | customer@lsh.com   |
| Provider | provider@lsh.com   |
| Admin    | admin@lsh.com      |

---

## API Endpoints (for reference)

| File              | Action               | Method | Description               |
|-------------------|----------------------|--------|---------------------------|
| auth.php          | login                | POST   | Login user                |
| auth.php          | register             | POST   | Register new user         |
| auth.php          | logout               | GET    | Logout                    |
| auth.php          | me                   | GET    | Get session user          |
| providers.php     | list                 | GET    | All providers (filterable)|
| providers.php     | detail               | GET    | Single provider           |
| bookings.php      | create               | POST   | Create booking            |
| bookings.php      | my-bookings          | GET    | Customer's bookings       |
| bookings.php      | provider-requests    | GET    | Provider's requests       |
| bookings.php      | update-status        | POST   | Accept/Reject/Complete    |
| admin.php         | stats                | GET    | Platform stats            |
| admin.php         | bookings             | GET    | All bookings              |
| admin.php         | providers            | GET    | All providers             |

---

## Requirements

- XAMPP (PHP 8.0+, MySQL 5.7+)
- Modern browser (Chrome, Firefox, Edge)
- Internet connection (for Font Awesome icons via CDN)
