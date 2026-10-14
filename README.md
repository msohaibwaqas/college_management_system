<p align="center">
  <h1 align="center">🎓 College Management System (CMS)</h1>
  <p align="center"><strong>A Comprehensive, Enterprise-Grade Digital Platform for Modern Educational Institutions</strong></p>
  <p align="center">
    <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel"/>
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP"/>
    <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL"/>
    <img src="https://img.shields.io/badge/License-Custom_EULA-orange?style=for-the-badge" alt="License"/>
    <img src="https://img.shields.io/badge/Status-Production_Ready-brightgreen?style=for-the-badge" alt="Status"/>
  </p>
</p>

---

> **CMS is a battle-tested, production-grade web platform** designed to digitize and streamline the complete operational lifecycle of educational institutions. It is not a prototype — it is currently deployed and managing thousands of student records, staff profiles, examination cycles, and financial transactions at a real institution.

---

## 📋 Table of Contents

1. [Overview](#-overview)
2. [Features at a Glance](#-features-at-a-glance)
3. [System Architecture](#-system-architecture)
4. [Module Breakdown](#-module-breakdown)
5. [Tech Stack](#-tech-stack)
6. [Requirements](#-requirements)
7. [Installation](#-installation)
8. [Configuration](#-configuration)
9. [Quick Start](#-quick-start)
10. [Project Stats](#-project-stats)
11. [Security](#-security)
12. [Contributing](#-contributing)
13. [License](#-license)
14. [Author](#-author)

---

## 🏛️ Overview

The **College Management System (CMS)** is a fully integrated, web-based platform purpose-built for **Intermediate (HSSC)** and **Higher Education (BS/ADP)** programs. It replaces fragmented paper-based processes with a unified, real-time digital ecosystem.

### What makes it different?

- 🇵🇰 **Built for Pakistani Institutions** — understands BISE roll number formats, Punjab board workflows, and local fee structures
- 🔗 **Dual-Program Architecture** — manages Intermediate and BS/ADP programs in one unified system
- 🏠 **Self-Hosted & Secure** — your data stays on your servers, no SaaS subscriptions, full data ownership
- ⚡ **Production-Tested** — actively used in a live environment, not a demo

---

## ✨ Features at a Glance

| Stakeholder | Key Benefits |
|---|---|
| **Administration** | 80% reduction in manual paperwork, real-time dashboards, one-click bulk operations |
| **Teachers** | Dedicated panel, smart attendance with time restrictions, LMS for assignments & grading |
| **Students** | Self-service portal, real-time chat with teachers, fee tracking, result viewing |
| **Parents** | Dedicated portal to monitor attendance, results, and fee status for linked children |
| **Public** | Professional institution website, online admission applications, student verification |

---

## 🏗️ System Architecture

```
┌──────────────────────────────────────────────────────────────────────────┐
│                            PUBLIC WEBSITE                                │
│   Home • Programs • Staff • Gallery • Downloads • News • Admissions      │
│   Student Portal • Duty Roster • Principal Archive • Verification        │
└───────────────────────────────┬──────────────────────────────────────────┘
                                │
┌───────────────────────────────▼──────────────────────────────────────────┐
│                         AUTHENTICATION LAYER                             │
│       Login • RBAC Role Detection • Google OAuth • 2FA • Sessions        │
└────────┬──────────────┬──────────────┬───────────────┬───────────────────┘
         │              │              │               │
    ┌────▼────┐    ┌─────▼─────┐  ┌────▼────┐    ┌────▼────┐
    │  ADMIN  │    │  TEACHER  │  │ STUDENT │    │ PARENT  │
    │  PANEL  │    │   PANEL   │  │ PORTAL  │    │ PORTAL  │
    └────┬────┘    └─────┬─────┘  └────┬────┘    └────┬────┘
         │               │             │               │
┌────────▼───────────────▼─────────────▼───────────────▼──────────────────┐
│                              CORE MODULES                                │
│  Students SIS • Staff HR • Exams & Results • Fee Vouchers • Attendance   │
│  Timetable Engine • LMS • Admissions Portal • Library • Notifications    │
│  Payroll • Leaves • Transport • Hostel • Alumni • Assets • Budget        │
└───────────────────────────────┬──────────────────────────────────────────┘
                                │
┌───────────────────────────────▼──────────────────────────────────────────┐
│                              DATA LAYER                                  │
│  MySQL 8.0 • 118+ Tables • Eloquent ORM • Soft Deletes • Audit Trail     │
│  DomPDF • Maatwebsite Excel • Symfony DomCrawler • Barcode Generator     │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 📦 Module Breakdown

<details>
<summary><strong>1. 🎓 Student Information System (SIS)</strong></summary>

Complete student lifecycle management from admission to graduation.

- Full CRUD with soft-delete & restore
- Rich profiles: personal info, father details, SSC/HSSC records, BISE data, blood group, quotas
- Elective & compulsory subject enrollment with teacher linkage
- Bulk operations: bulk delete, bulk promote (11th → 12th), bulk status update
- Advanced search & filtering (name, class, year, subject, status)
- ID card generation with barcodes
- CSV export with customizable columns
- Section assignment for granular attendance management
- Complete financial history per student
</details>

<details>
<summary><strong>2. 👨‍💼 Staff & HR Management</strong></summary>

Full-cycle human resource management for teaching and non-teaching staff.

- Complete staff registry with BPS grades, designations, and qualifications
- Service history: appointments, promotions, transfers
- Subject specialization mapping
- Auto account creation with role assignment
- Attendance sections management
- Document wallet for digital qualification storage
- Award list generation and printing
- CSV export with filters by designation, department, and status
</details>

<details>
<summary><strong>3. 🏫 Admissions Portal</strong></summary>

Dual-track admissions for both Intermediate (HSSC) and Higher Education (BS/ADP) programs.

**Intermediate Admissions:**
- Public online application form with document uploads
- Walk-in application entry by clerks
- Configurable merit list generation (weighted: SSC 30% + HSSC 70%)
- Dynamic quota seat allocation
- Auto fee voucher generation on approval
- Late admission support

**Higher Education Admissions:**
- BS/ADP program management with semester structures
- Subject enrollment per semester
- Teacher-to-subject mapping
- Direct enrollment transition from Intermediate
</details>

<details>
<summary><strong>4. 📝 Examination Management</strong></summary>

The most comprehensive module — covering the entire exam lifecycle.

- **Datesheets**: Manual or AI-assisted conflict-free schedule generation
- **Seating Plans**: Anti-cheating algorithm ensures no adjacent same-subject students; handles soft-deleted student records gracefully
- **Invigilators**: Intelligent auto-assignment with duty fairness, exemptions, and conflict detection; public duty roster
- **Results**: Test and final result entry (individual & batch), subject-wise analysis, GPA for HE
- **BISE Integration**: Web scraper that fetches official board results directly from BISE Bahawalpur
</details>

<details>
<summary><strong>5. 📅 Attendance Management</strong></summary>

Multi-mode attendance covering routine classes and campus gate entry/exit.

- **Subject-wise**: Teacher marks attendance for their students; restricted to current date; closes at 11:00 PM; Sundays blocked automatically; admin override available
- **Section-Based**: Teachers mark for their specific assigned sections
- **Gate Attendance**: Barcode-scan based entry/exit tracking with real-time student photo display
- **HE Attendance**: Semester-wise tracking per BS/ADP subject
- Monthly archiving to `AttendanceArchive` for performance optimization
</details>

<details>
<summary><strong>6. 💰 Fee & Voucher Management</strong></summary>

Complete fee lifecycle from computation to payment confirmation.

- `FeeCalculationService`: Configurable multi-head fee engine
- Individual and bulk voucher generation (Inter & HE)
- Conditional fees based on subject group (e.g., Computer Fund only for CS students)
- Subject change delta-vouchers: auto-calculates price difference
- Payment confirmation with receipt number, bank, and date
- Fine vouchers, cancellation, bulk delete
- Date-based late fee automation
</details>

<details>
<summary><strong>7. 🗓️ Timetable Engine</strong></summary>

Intelligent multi-constraint timetable generation.

- Room assignment with double-booking prevention
- Real-time teacher, room, and student group conflict detection
- Subject Compatibility Matrix for simultaneous non-conflicting scheduling
- Timetable cloning from previous year
- Separate engines for Intermediate and BS/ADP programs
- Active/Archived lifecycle to prevent scheduling clashes
</details>

<details>
<summary><strong>8. 📚 Learning Management System (LMS)</strong></summary>

Built-in LMS transforming the platform from purely administrative to academic.

- Teachers create subject-specific assignments with due dates, marks, and file attachments
- Students submit in PDF, DOCX, JPG, PNG, or ZIP
- Teacher grading panel with written feedback
- Real-time Teacher ↔ Student messaging with unread badges and read receipts
- Separate dashboards for teachers (pending submissions) and students (assignment status)
</details>

<details>
<summary><strong>9. 📖 Library Management System</strong></summary>

Full-featured library module for book cataloging, borrowing, and resource management.

- Book catalog with bilingual (English/Urdu) titles and RTL support
- Rich metadata: ISBN, Acc#, Ref#, Pages, Price, Publication Year, Shelf Location
- Author, Genre, and Location CRUD
- Book issuing to students and staff with AJAX live search
- Overdue detection with visual alerts and fine tracking
- Bulk CSV import/export with auto-creation of authors and genres
- Real-time availability: `(Total Copies) - (Active Issues)`
</details>

<details>
<summary><strong>10. 🌐 Public Website & Student Portal</strong></summary>

Professional public-facing website serving as the institution's digital front door.

- Dynamic homepage with enrollment stats and recent announcements
- Department pages with faculty, subjects, and student counts
- Staff directory, photo gallery (album-based), and downloads section
- Student Corner: self-service result viewing (Inter & HE)
- Online admission application with real-time status tracking
- Public invigilator duty roster for exam transparency
- Historical principal archive with tenure details
</details>

<details>
<summary><strong>11. 🎨 Admin Dashboard & Theming Engine</strong></summary>

Powerful analytics hub with full visual customization.

- **Analytics**: Enrollment stats, fee collection trends, staff ratios, subject distribution, year-over-year admission trends
- **Theming**: 4 configurable colors (Primary, Secondary, Highlight, Success) via hex color picker
- **Coverage**: Colors apply to all layouts — Admin, LMS, Library, Login page, and Public website
- **Implementation**: DB-driven CSS `:root` variable injection on every page load
- One-click reset to default institutional palette
</details>

<details>
<summary><strong>12. 🔐 User & Role Management (RBAC)</strong></summary>

Enterprise-grade access control.

- Roles: Super Admin, Admin, Principal, Teacher, Student, Parent, Accountant
- Granular resource-level permissions (e.g., `students.create`, `results.edit`)
- Role switching for multi-role users
- Two-Factor Authentication (TOTP-based with recovery codes)
- Audit trail: `created_by`, `updated_by`, `deleted_by` on all records
- Auto account generation from staff/student records
</details>

<details>
<summary><strong>13–21. Additional Modules</strong></summary>

- **Notification System**: SMS, Email, and in-app notifications with template engine and delivery logs
- **Staff Leave & Attendance**: Self-marking, multi-level leave approval, balance tracking, holiday calendar
- **Payroll & Salary**: BPS grade-based salary structures, attendance-linked deductions, PDF salary slips, bank export
- **Parent Portal**: Multi-child monitoring with attendance, results, and fee voucher access
- **Online Payment**: JazzCash, EasyPaisa, 1Link integration with HMAC webhook verification
- **Bulk Data Import**: Excel import with validation engine, upsert logic, and queue-based processing for 5,000+ rows
- **Transport & Hostel**: Route management, room-wise allotments, automated billing, capacity validation
- **Alumni & Graduation**: Eligibility engine, certificate generation (Degree, DMC, Character, Migration), QR verification portal
- **Asset & Budget Management**: Asset register with depreciation, departmental budgeting, expense logging
</details>

---

## 🔧 Tech Stack

| Component | Technology |
|---|---|
| **Backend Framework** | Laravel 12.x (PHP 8.2+) |
| **Database** | MySQL 8.0 with InnoDB engine |
| **Frontend** | Blade Templates, Bootstrap 5, jQuery, Chart.js |
| **PDF Generation** | `barryvdh/laravel-dompdf` |
| **Excel / CSV** | `maatwebsite/excel`, League/CSV |
| **Web Scraping** | `symfony/dom-crawler`, `symfony/css-selector` |
| **Barcodes** | `picqer/php-barcode-generator` |
| **Authentication** | Laravel Sanctum + Session-based auth |
| **Social Login** | Laravel Socialite (Google OAuth) |
| **File Storage** | Laravel Filesystem (local + cloud-ready: S3, DO Spaces) |
| **Build Tool** | Vite |
| **Testing** | PHPUnit 11 |

---

## 📋 Requirements

| Requirement | Minimum |
|---|---|
| **PHP** | 8.2+ |
| **MySQL** | 8.0+ |
| **Composer** | 2.x |
| **Node.js** | 18+ |
| **Web Server** | Apache 2.4+ or Nginx 1.18+ |
| **RAM** | 2 GB (4 GB recommended) |
| **Storage** | 20 GB (scales with file attachments) |
| **OS** | Ubuntu 22.04 LTS / CentOS 8+ / Windows Server |

**Required PHP Extensions:** `pdo_mysql`, `mbstring`, `xml`, `bcmath`, `gd`, `fileinfo`, `zip`

---

## 🚀 Installation

### Option A — One-Command Setup (Recommended)

```bash
git clone https://github.com/msohaibwaqas/college_management_system.git cms
cd cms
cp .env.example .env
composer run setup
```

The `composer run setup` script handles: `composer install` → `key:generate` → `migrate` → `npm install` → `npm run build`.

### Option B — Manual Step-by-Step

```bash
# 1. Clone the repository
git clone https://github.com/msohaibwaqas/college_management_system.git cms
cd cms

# 2. Install PHP dependencies
composer install --optimize-autoloader

# 3. Install Node dependencies & build assets
npm install && npm run build

# 4. Configure environment
cp .env.example .env
php artisan key:generate

# 5. Set up the database
mysql -u root -p -e "CREATE DATABASE cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed

# 6. Create the public storage link
php artisan storage:link

# 7. Set correct permissions (Linux)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## ⚙️ Configuration

Copy `.env.example` to `.env` and fill in the required values:

```env
# Application
APP_NAME=SCMS
APP_ENV=production
APP_KEY=                          # Auto-generated via: php artisan key:generate
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cms
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Google OAuth (for social login — create at console.cloud.google.com)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URL="${APP_URL}/auth/google/callback"

# Mail (optional — set MAIL_MAILER=smtp for real emails)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@your-college.edu.pk"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## ⚡ Quick Start (Development)

```bash
# Start all services concurrently (server + queue + logs + vite)
composer run dev
```

This runs PHP dev server, queue listener, Laravel Pail log viewer, and Vite HMR simultaneously.

**Default admin credentials after seeding:**

> Check `database/seeders/` for the seeded admin user credentials and update them immediately after first login.

---

## 📊 Project Stats

| Metric | Count |
|---|---|
| **Controllers** | 92 specialized controllers |
| **Eloquent Models** | 114 models |
| **Blade Templates** | 220+ across 45+ view directories |
| **Database Tables** | 118+ with full relational integrity |
| **Supported Programs** | Intermediate (HSSC), BS, ADP |
| **User Roles** | 7 (Super Admin, Admin, Principal, Teacher, Student, Parent, Accountant) |
| **Service Classes** | 5+ dedicated business logic services |
| **Test Coverage** | PHPUnit Feature & Unit tests included |

---

## 🔒 Security

| Feature | Implementation |
|---|---|
| **Password Hashing** | Bcrypt with configurable rounds (`BCRYPT_ROUNDS=12`) |
| **Authorization** | Multi-layer RBAC (Role → Permission → Resource) |
| **CSRF Protection** | Laravel's built-in CSRF token on all forms |
| **SQL Injection** | Eloquent ORM with parameterized queries |
| **XSS Protection** | Blade's automatic HTML output escaping |
| **2FA** | TOTP-based with recovery codes |
| **File Uploads** | MIME validation, size limits, isolated private storage |
| **Audit Trail** | `created_by` / `updated_by` / `deleted_by` on all critical models |
| **Soft Deletes** | Data preservation for compliance — no hard deletes on critical entities |

**⚠️ Before deploying to production:**
- Set `APP_DEBUG=false` and `APP_ENV=production`
- Use HTTPS with a valid SSL certificate (Let's Encrypt recommended)
- Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- Set up a queue worker: `php artisan queue:work --daemon`

---

## 🤝 Contributing

Contributions are welcome! Here's how to get started:

1. **Fork** the repository
2. **Create a branch**: `git checkout -b feature/your-feature-name`
3. **Make your changes** and write tests if applicable
4. **Run tests**: `composer run test`
5. **Commit**: `git commit -m "feat: add your feature description"`
6. **Push**: `git push origin feature/your-feature-name`
7. **Open a Pull Request** — describe what you changed and why

### Development Guidelines

- Follow PSR-12 coding standards (enforced via Laravel Pint: `./vendor/bin/pint`)
- Keep controllers lean — put business logic in `app/Services/`
- All new models should use `SoftDeletes` and include `created_by` / `updated_by` audit columns
- Write migration files for all database changes — do not modify existing migrations

---

## 📄 License

This project is licensed under a **Custom End User License Agreement (EULA)**. See [LICENSE.md](LICENSE.md) for full details.

**Key points:**
- ✅ Free to use and self-host
- ✅ Free to study, extend, and modify for your own institution
- ❌ Redistribution or resale is **not permitted**
- ❌ Removal of developer attribution/footer credits is **not permitted**

---

## 👨‍💻 Author

**Muhammad Sohaib Waqas**

- GitHub: [@msohaibwaqas](https://github.com/msohaibwaqas)
- Facebook: [m.sohaib.waqas](https://www.facebook.com/m.sohaib.waqas)

Built with ❤️ using Laravel, PHP, and MySQL.

---

<p align="center">
  <sub>© 2024–2026 College Management System. Built by Muhammad Sohaib Waqas.</sub>
</p>
