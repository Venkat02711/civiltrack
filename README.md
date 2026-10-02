# CivilTrack - Construction Site & Daily-Wage Worker Management System

**CivilTrack** is a web-based local management system developed for construction site supervisors and managers to manage projects, temporary/daily-wage workers, attendance-driven wage calculations, payouts, and material purchases.

Built with **PHP, MySQL, HTML5, CSS3, and JavaScript** running locally on **XAMPP**.

---

## Key Modules & Features

- **Dashboard**: Real-time KPI summaries (Active Sites, Workers Working Today, Pending Payouts in ₹, Total Material Expenses, Total Site Cost), active projects overview, and recent activity log.
- **Sites Management**: Create, update, filter, and track construction sites. Individual site details show an automatic three-tier cost rollup (Labour + Materials + Other Expenses = Total Site Cost).
- **Workers Registry**: Register temporary/daily-wage workforce across trades (Mason, Helper, Carpenter, Electrician, Plumber, Painter, Bar Bender, General Worker) with individual daily wage rates.
- **Attendance & Daily Work**:
  - **Present**: 100% daily wage
  - **Half Day**: 50% daily wage
  - **Absent**: ₹0
  - Live wage preview and automated creation/synchronization of linked payout records.
  - Supports both **Single Attendance** and **Batch Attendance** for logging entire crews in one step.
- **Payouts**: Track pending vs. paid wages. Single-click "Pay Now", bulk payouts, and status filtering.
- **Materials Tracker**: Rapid entry for construction materials (Cement, Sand, Bricks, Steel, Pipes, Electrical, Hardware) with automatic `Quantity × Unit Price = Total Amount` calculation.
- **Admin Section**:
  - **Site Expenses**: Track equipment rentals, transport, water tankers, and utilities with site-level rollups.
  - **Printable Reports**: Print-friendly reports for Worker Payouts, Site Expenses, and Material Purchases (`@media print` clean formatting).
  - **User Management**: Add administrators/supervisors, change passwords with bcrypt `password_hash()`, and manage accounts.
  - **Settings**: System configuration, record counts, and database reset tool.

---

## Setup & Running on XAMPP

### 1. Installation Path
Place the project folder inside:
```
C:\xampp\htdocs\civiltrack\
```

### 2. Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### 3. Access the Application
Open your browser and navigate to:
```
http://localhost/civiltrack/
```

### 4. Database Initialization
If running on a new computer or clean installation, visit:
```
http://localhost/civiltrack/setup.php
```
Click **"Initialize Database"** to create all tables and load initial sample data.

### 5. Login Credentials
- **Username**: `admin`
- **Password**: `admin123`

---

## Database Architecture (`civiltrack`)

The database consists of 7 relational tables:
1. `users`: System administrators and supervisors with bcrypt hashed passwords.
2. `sites`: Project details, clients, locations, timelines, and status (`Active`, `On Hold`, `Completed`).
3. `workers`: Daily-wage workforce, contact info, trades, and daily wage rates.
4. `attendance`: Daily work records linked to worker and site (`Present`, `Half Day`, `Absent`) with auto-computed wages.
5. `payouts`: Wage payout ledger linked to attendance (`Pending`, `Paid`) with payment dates.
6. `materials`: Quick material purchase register with quantities, units, and auto-computed totals.
7. `expenses`: Machinery, transportation, utilities, and miscellaneous site expenses.

---

## Application Walkthrough & Workflow

1. **Dashboard Overview**: Check active projects, workforce present today, and current financial summaries.
2. **Projects / Sites**:
   - Create a site or open an active project like *Green Valley Residence*.
   - View the financial breakdown combining Labour, Material, and Other expenses.
3. **Workers Registry**:
   - Register a new daily worker (e.g. Mason @ ₹800/day).
   - Check worker details to see past work history and payment status.
4. **Attendance Logging**:
   - Record single or batch attendance for a site.
   - Present auto-calculates 100% wage, Half Day auto-calculates 50% wage.
5. **Payout Settlement**:
   - Go to Payouts to view newly generated pending payouts.
   - Click **Pay Now** to record disbursement with payment date.
6. **Material Purchases**:
   - Add a purchase (e.g. 20 Bags Cement @ ₹420 = ₹8,400).
   - See it reflected in both Materials list and Site details.
7. **Reports & Exports**:
   - Go to Admin -> Reports to review Worker Payouts, Site Expenses, or Material bills.
   - Use browser print to export or print formatted summary tables.

---

## Technical Details

- **Database API**: Standard MySQLi extension (`mysqli_connect`, `mysqli_query`, `mysqli_real_escape_string`, `mysqli_fetch_assoc`) for database operations.
- **XSS Prevention**: Output sanitized with `htmlspecialchars()`.
- **Password Security**: Bcrypt password hashing via `password_hash()` and `password_verify()`.
- **Session Authentication**: Native PHP session-based access control.
- **Zero External Dependencies**: All styling, JavaScript, and vector icons are bundled locally.
