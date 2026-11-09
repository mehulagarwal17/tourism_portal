# Online Tourism Portal - WAMP Server Hosting & Installation Guide

## System Specifications
- **Application:** Online Tourism Portal
- **Technology Stack:**
  - Frontend: HTML5, CSS3, Vanilla JavaScript, Bootstrap 5.3
  - Backend: Core PHP (PHP 8.0 or later) with native sessions
  - Database: MySQL with phpMyAdmin
  - Web Server: Apache 2.4+ (Standard WAMP Server / XAMPP / LAMP)

---

## 1. Prerequisites
Before beginning the installation, ensure you have one of the following installed:
- **WAMP Server** (Recommended on Windows, available from [wampserver.com](https://www.wampserver.com/))
- Alternatively: **XAMPP** or **MAMP** or **LAMP**

Ensure both **Apache** and **MySQL** services are running (indicated by a **Green WAMP icon** in your taskbar).

---

## 2. Step-by-Step Installation Instructions

### Step 1: Place the Project in WAMP's Web Root
1. Copy or extract this entire project folder into your WAMP `www` directory:
   ```
   C:\wamp64\www\tourism-portal\
   ```
   *(If using XAMPP, place it inside `C:\xampp\htdocs\tourism-portal\`)*

2. Confirm the directory structure looks like this:
   ```
   C:\wamp64\www\tourism-portal\
     ├── index.php
     ├── destinations.php
     ├── packages.php
     ├── login.php
     ├── register.php
     ├── config/
     │    └── database.php
     ├── database/
     │    └── tourism_portal.sql
     ├── includes/
     ├── user/
     ├── admin/
     └── assets/
   ```

---

### Step 2: Import the Database into MySQL via phpMyAdmin
1. Open your web browser and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```
2. Log in with your MySQL credentials:
   - Default Username: `root`
   - Default Password: *(leave blank or enter your local root password)*
3. In the top navigation bar, click on the **Import** tab.
4. Click **Choose File** and select:
   ```
   C:\wamp64\www\tourism-portal\database\tourism_portal.sql
   ```
5. Click **Import** (or **Go**) at the bottom of the page.
6. phpMyAdmin will execute the script, automatically creating the `tourism_portal` database and populating it with all tables, constraints, foreign keys, and sample data.

---

### Step 3: Verify Database Connection Configuration
Open `config/database.php` in any text editor (e.g. VS Code, Notepad++):
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tourism_portal');
define('DB_USER', 'root');
define('DB_PASS', '');       // Enter your MySQL password here if you set one
define('DB_PORT', 3306);
```
*Note: If your MySQL server is running on a different port (e.g. 3307 or 3308 in WAMP), update `DB_PORT` accordingly.*

---

### Step 4: Open and Test the Application
Open your web browser and visit:
```
http://localhost/tourism-portal/
```

---

## 3. Demonstration & Default Credentials

| User Role | Email Address | Password | Purpose |
|---|---|---|---|
| **System Administrator** | `admin@tourism.com` | `Admin@123` | Full access to central management dashboard, CRUD operations, booking approvals, review moderation |
| **Demo Tourist / User** | `user@example.com` | `User@123` | Booking workflow, customer profile, viewing vouchers, submitting ratings |
| **Alternative Tourist** | `sneha.patel@example.com` | `User@123` | Additional traveler account for testing multi-user isolation |

*Tip: On both the Login (`login.php`) and Admin Login (`admin/login.php`) pages, 1-click **"Auto-fill Credentials"** buttons are provided for fast testing.*

---

## 4. Key Functional Features & Architecture
1. **Public Discovery:**
   - Interactive search and filtering by state and country on `destinations.php`.
   - Budget, duration, and keyword filtering on `packages.php`.
   - Day-wise itinerary timeline and facility badges on `package-details.php`.
2. **User Authentication & Isolation:**
   - Registration with 10-digit phone and email uniqueness validation.
   - Session fixation defense (`session_regenerate_id()`) and password hashing (`BCRYPT`).
   - Tourists can only view their own bookings and profile.
3. **End-to-End Booking Workflow:**
   - Select a tour package and choose departure date & number of travelers.
   - Dynamic real-time calculation of total price via JavaScript.
   - Database record generated with unique booking reference (`BK-2026-XXXXX`).
   - Printable travel ticket voucher (`window.print()`) with print CSS.
4. **Administrator Control:**
   - Live KPI overview (Total Users, Destinations, Packages, Bookings, Revenue).
   - Destination management (Add, Edit, Delete).
   - Package management (Add, Edit, Availability Toggle, Delete).
   - Real-time booking status confirmation and cancellation.
   - User account management and review moderation.
