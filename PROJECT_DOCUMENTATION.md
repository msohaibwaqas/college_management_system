<p align="center">
  <h1 align="center">🎓 College Management System (CMS)</h1>
  <p align="center"><strong>A Comprehensive, Enterprise-Grade Digital Platform for Modern Educational Institutions</strong></p>
  <p align="center">
    <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel"/>
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP"/>
    <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL"/>
    <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License"/>
  </p>
</p>

---

## 📋 Table of Contents

1. [Executive Summary](#-executive-summary)
2. [Key Value Propositions](#-key-value-propositions)
3. [System Architecture](#-system-architecture)
4. [Module Breakdown](#-module-breakdown)
   - [Student Information System (SIS)](#1-student-information-system-sis)
   - [Staff & HR Management](#2-staff--hr-management)
   - [Admissions Portal](#3-admissions-portal)
   - [Examination Management](#4-examination-management)
   - [Attendance Management](#5-attendance-management)
   - [Fee & Voucher Management](#6-fee--voucher-management)
   - [Timetable Engine](#7-timetable-engine)
   - [Learning Management System (LMS)](#8-learning-management-system-lms)
   - [Library Management System](#9-library-management-system)
   - [Public Website & Student Portal](#10-public-website--student-portal)
   - [Admin Dashboard, Analytics & Theming](#11-admin-dashboard-analytics--theming)
   - [User & Role Management](#12-user--role-management)
5. [Technical Specifications](#-technical-specifications)
6. [Security & Compliance](#-security--compliance)
7. [Deployment & Infrastructure](#-deployment--infrastructure)
8. [Why Choose CMS?](#-why-choose-cms)

---

## 🏛️ Executive Summary

The **College Management System (CMS)** is a fully integrated, web-based platform designed to digitize and streamline the complete operational lifecycle of educational institutions. Purpose-built for **Intermediate (HSSC)** and **Higher Education (BS/ADP)** programs, it replaces fragmented paper-based processes with a unified, real-time digital ecosystem.

CMS is currently deployed and **battle-tested in a real-world production environment**, managing thousands of student records, staff profiles, examination cycles, and financial transactions. It is not a prototype — it is a living, production-grade system built from the ground up to handle the unique administrative complexities of Pakistani educational institutions.

### At a Glance

| Metric | Detail |
|---|---|
| **Controllers** | 92 specialized controllers |
| **Models** | 114 Eloquent models |
| **View Templates** | 45+ view directories, 220+ Blade templates |
| **Supported Programs** | Intermediate (HSSC), BS, ADP |
| **User Roles** | Super Admin, Admin, Principal, Teacher, Student, Parent, Accountant |
| **Database Tables** | 118+ tables with full relational integrity |
| **Logic Services** | 5+ dedicated business logic services |

---

## ✨ Key Value Propositions

### 🎯 For Administration
- **80% reduction** in manual paperwork through automated admissions, fee vouchers, and result processing
- **Real-time dashboards** with enrollment statistics, fee collection trends, and program analytics
- **One-click bulk operations** for promotions, status updates, and report generation

### 👨‍🏫 For Teachers
- **Dedicated Teacher Panel** with subject-wise student lists, attendance marking, and result entry
- **Smart Attendance System** with date restrictions, Sunday detection, and 11:00 PM cutoffs
- **LMS Integration** for creating assignments, grading submissions, and messaging students directly

### 👨‍🎓 For Students
- **Self-Service Portal** to check results, view datesheets, track assignments, and download resources
- **Real-time Chat** with assigned teachers for academic queries
- **Fee Status Tracking** with online voucher viewing

### 🌐 For the Public
- **Professional Public Website** with department pages, faculty listings, photo gallery, and admission information
- **Online Admission Application** with real-time status tracking
- **Student Verification Portal** for employers and other institutions

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│                                       PUBLIC WEBSITE                                        │
│      Home • Programs • Staff • Gallery • Downloads • News • Contact • About • Scraper       │
│      Student Portal • Online Admissions • Duty Roster • Principal Archive • Verification    │
└───────────────────────────────┬───────────────────────────────┬─────────────────────────────┘
                                │                               │
┌───────────────────────────────▼───────────────────────────────▼─────────────────────────────┐
│                                    AUTHENTICATION LAYER                                     │
│      Login • Role Detection (RBAC) • Multi-Auth Guards • Two-Factor Auth • Sessions         │
└───────────────────────────────┬───────────────────────────────┬─────────────────────────────┘
                                │                               │
         ┌──────────────────────┼──────────────────────┬────────┴─────────────┐
         │                      │                      │                     │
    ┌────▼────┐           ┌─────▼─────┐          ┌─────▼─────┐         ┌─────▼─────┐
    │  ADMIN  │           │  TEACHER  │          │  STUDENT  │         │  PARENT   │
    │  PANEL  │           │   PANEL   │          │  PORTAL   │         │  PORTAL   │
    └────┬────┘           └─────┬─────┘          └─────┬─────┘         └─────┬─────┘
         │                      │                      │                     │
┌────────▼──────────────────────▼──────────────────────▼─────────────────────▼───────────────┐
│                                      CORE MODULES                                           │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐  │
│  │  Students  │ │   Staff    │ │   Exams    │ │    Fee     │ │ Attendance │ │ Timetable  │  │
│  │    SIS     │ │     HR     │ │  Results   │ │  Vouchers  │ │   & Gate   │ │   Engine   │  │
│  └────────────┘ └────────────┘ └────────────┘ └────────────┘ └────────────┘ └────────────┘  │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐  │
│  │    LMS     │ │ Admissions │ │  Library   │ │Notifications│ │Payroll & HR│ │  Leaves    │  │
│  │Assignments │ │   Portal   │ │ Management │ │ (SMS/Mail) │ │ Management │ │ Management │  │
│  └────────────┘ └────────────┘ └────────────┘ └────────────┘ └────────────┘ └────────────┘  │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐  │
│  │  Online    │ │ Transport  │ │  Hostel    │ │  Alumni &  │ │  Asset     │ │  Budget    │  │
│  │  Payment   │ │ Management │ │ Management │ │ Graduation │ │ Management │ │ Management │  │
│  └────────────┘ └────────────┘ └────────────┘ └────────────┘ └────────────┘ └────────────┘  │
│                                                                                             │
│  ┌───────────────────────────────────────────────────────────────────────────────────────┐  │
│  │                        THEMING ENGINE (Global Color Scheme)                           │  │
│  │                 Dynamic CSS Variables from Database • Real-time Preview                │  │
│  └───────────────────────────────────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────┬─────────────────────────────────────────────────────┘
                                        │
┌───────────────────────────────────────▼─────────────────────────────────────────────────────┐
│                                     DATA LAYER                                              │
│     MySQL 8.0 • 118+ Tables • Eloquent ORM • Soft Deletes • Audit Trail • Soft Filters      │
│     File Storage (Local/S3) • DomPDF Engine • Maatwebsite Excel • Symfony Scrapers          │
└─────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 📦 Module Breakdown

### 1. Student Information System (SIS)

A comprehensive student lifecycle manager from admission to graduation.

| Feature | Description |
|---|---|
| **Complete CRUD** | Create, view, edit, soft-delete, restore, and permanently delete student records |
| **Rich Profiles** | Personal info, father details, contacts, SSC/HSSC records, BISE records, attributes, blood groups, quotas |
| **Subject Enrollment** | Elective and compulsory subject assignment with teacher linkage |
| **Subject Change** | Mid-cycle subject modifications with automatic fee adjustment |
| **Bulk Operations** | Bulk delete, bulk status update, bulk promote (11th → 12th) |
| **CSV Export** | Export filtered student lists with customizable columns |
| **Trash & Recovery** | Soft-deleted students can be viewed, restored, or permanently removed |
| **ID Card Generation** | Auto-generate student ID cards with barcodes |
| **Advanced Search** | Filter by name, class, admission year, status, subject, and more |
| **Custom Reports** | Generate subject enrollment reports and student group analysis |
| **Section Assignment** | Assign students to specific teacher-managed sections for granular attendance |
| **Financial History** | Complete record of all fee vouchers, payments, and discounts per student |

**Technical Specs:**
- **Primary Model:** `Student`
- **Key Relationships:** `belongsTo(Father)`, `hasMany(FeeVoucher)`, `belongsToMany(Subject)`
- **Advanced Logic:** Automated age calculation, CNIC format validation, and unique roll number generation.

---

### 2. Staff & HR Management

Full-cycle human resource management for teaching and non-teaching staff.

| Feature | Description |
|---|---|
| **Staff Registry** | Complete profiles with designations, BPS grades, qualifications, specializations |
| **Service History** | Track appointment dates, promotions, transfers, and posting history |
| **Academic Qualifications** | Record degrees, certifications, and specializations |
| **Subject Specialization** | Map teachers to their specialization areas |
| **Student Assignment** | Assign/unassign students to teachers by subject and year |
| **Staff Panel** | Dedicated view for teachers to see their assigned students grouped by subject/class |
| **Award Lists** | Generate and print formal award lists with customizable layouts |
| **CSV Export** | Export staff data with filters by designation, department, and status |
| **Auto Account Creation** | Generate user login accounts for staff members with role assignment |
| **Attendance Sections** | Teachers can manage and view specific sections of students for routine attendance |
| **Document Wallet** | Store digital copies of staff qualifications and appointment letters |

**Technical Specs:**
- **Primary Model:** `Staff`
- **Key Relationships:** `hasMany(StaffAcademicQualification)`, `hasMany(StaffServiceHistory)`, `hasOne(TeacherProfile)`
- **Advanced Logic:** Automated BPS grade-based salary head mapping.

---

### 3. Admissions Portal

Dual-track admissions for both **Intermediate (HSSC)** and **Higher Education (BS/ADP)** programs.

#### Intermediate (HSSC) Admissions
| Feature | Description |
|---|---|
| **Online Applications** | Public-facing form with SSC/matric data, subject preferences, and document uploads |
| **Walk-in Applications** | Clerk-entered applications for in-person admissions |
| **Application Review** | Approve/reject applications with configurable admission settings |
| **Merit List Generation** | Auto-generate merit lists based on SSC marks and quota reservations |
| **Fee Voucher Generation** | Auto-calculate and generate admission fee vouchers |
| **Late Admissions** | Direct admit students who missed the merit cycle |
| **Configurable Settings** | Set admission year, open/close dates, fee amounts, and quota rules |

#### Higher Education (BS/ADP) Admissions
| Feature | Description |
|---|---|
| **Program Management** | Create and manage BS/ADP programs with semester structures |
| **HE Admissions** | Full CRUD with program, semester, and subject assignment |
| **Subject Enrollment** | Assign BS subjects to students per semester |
| **Teacher Assignment** | Map HE teachers to subjects and semesters |
| **Seamless Transition** | Students from Inter can be directly enrolled into HE programs |

**Technical Specs:**
- **Primary Models:** `AdmissionApplication`, `HeAdmission`
- **Logic Service:** `MeritListService`
- **Advanced Logic:** Weighted merit calculation (SSC 30% + HSSC 70%) and dynamic quota seat allocation.

---

### 4. Examination Management

The most comprehensive module — covering the entire examination lifecycle from datesheet creation to final result compilation.

#### Datesheet Management
| Feature | Description |
|---|---|
| **Manual Creation** | Create exam slots with subject, date, time, and venue |
| **AI-Assisted Generation** | Auto-generate conflict-free datesheets considering subject overlaps |
| **Summary View** | Visual overview of exam schedule with student counts per slot |
| **HE Datesheets** | Separate datesheet management for BS/ADP programs |

#### Seating Plans
| Feature | Description |
|---|---|
| **Auto-Generation** | Algorithmically assign students to rooms ensuring no adjacent same-subject students |
| **Attendance Sheets** | Generate printable room-wise attendance sheets |
| **HE Seating Plans** | Separate seating plan engine for semester exams |
| **Room Analysis** | Subject-wise student counts per room for better resource allocation |
| **Soft-Delete Safety** | Graceful handling of deleted student records (shown as "Empty/Vacant") to prevent runtime crashes |

#### Invigilator Management
| Feature | Description |
|---|---|
| **Auto-Assignment Algorithm** | Intelligent invigilator assignment considering duty count fairness, exemptions, and mandatory roles |
| **Duty Rules** | Configurable maximum duties per teacher and priority designations |
| **Exemptions** | Manage teacher exemptions (medical, administrative, etc.) |
| **Duty Roster** | Generate and display public duty rosters for transparency |
| **Manual Override** | Administrative ability to manually adjust assignments |
| **Conflict Detection** | Prevent double-booking of teachers across rooms |
| **HE Invigilators** | Separate invigilator system for HE exams |

#### Result Management
| Feature | Description |
|---|---|
| **Test Results** | Entry and tracking of periodic test marks (classwise/subjectwise) |
| **Final Results** | Comprehensive final result entry with part-wise marks per subject |
| **BISE Integration** | **Web scraper** that fetches official Board of Intermediate & Secondary Education results directly from BISE Bahawalpur website |
| **Individual Entry** | Search-and-enter results for individual students by roll number |
| **Batch Entry** | Enter marks for entire class/subject combinations at once |
| **Result Reports** | Final result reports with pass/fail statistics and subject-wise analysis |
| **HE Results** | Separate GPA-based result system for BS/ADP programs |
| **Change Notifications** | Automatic notifications when marks are modified |

**Technical Specs:**
- **Primary Models:** `Datesheet`, `SeatingPlan`, `Result`
- **Logic Service:** `Symfony DomCrawler` (for BISE Scraper)
- **Advanced Logic:** Conflict-detection algorithm for datesheets and room-alternation algorithm for seating plans to prevent cheating.

---

### 5. Attendance Management

Multi-mode attendance system covering both routine classes and campus entry/exit.

#### Subject-wise Attendance
| Feature | Description |
|---|---|
| **Teacher-based** | Each teacher marks attendance for their assigned students per subject |
| **Date Restrictions** | Teachers can only mark for **current date**, not past or future |
| **Time Cutoff** | Attendance marking automatically closes at **11:00 PM** |
| **Sunday Detection** | System prevents marking on Sundays |
| **Admin Override** | Admins and principals bypass date/time restrictions |
| **Existing Records** | System shows previously marked attendance for editing |
| **Bulk Upsert** | Optimized database writes using `upsert` for performance |
| **Section-Based Marking** | Teachers mark attendance for their specific assigned sections rather than entire classes |

#### Gate Attendance (Barcode)
| Feature | Description |
|---|---|
| **Barcode Scanning** | Students scan ID cards at campus gates |
| **In/Out Tracking** | First scan = entry time, second scan = exit time |
| **Multi-Format Support** | Accepts Student ID, padded barcodes, and college roll numbers |
| **Real-time Feedback** | Instant visual confirmation with student photo and class info |
| **Duplicate Prevention** | Prevents third scan after both in and out recorded |

#### HE Attendance
| Feature | Description |
|---|---|
| **Semester-wise** | Track attendance per BS/ADP subject and semester |
| **Program Filtering** | Filter attendance by program, semester, and subject |

**Technical Specs:**
- **Primary Model:** `Attendance`
- **Archiving Logic:** Monthly movement of records to `AttendanceArchive` for performance optimization.
- **Advanced Logic:** Barcode normalization (handling leading zeros and special prefixes).

---

### 6. Fee & Voucher Management

Complete fee lifecycle management from computation to payment confirmation.

| Feature | Description |
|---|---|
| **Auto-Calculation** | Fee computation engine (`FeeCalculationService`) with configurable fee heads |
| **Voucher Generation** | Generate individual or bulk vouchers for Inter and HE students |
| **Bulk Class 12 Vouchers** | One-click generation for all promoted Class 12 students |
| **Bulk HE Vouchers** | Generate semester-wise vouchers for entire BS/ADP programs |
| **Payment Confirmation** | Mark vouchers as paid with receipt number, bank, and date |
| **Fine Vouchers** | Create custom fine vouchers for individual students |
| **Voucher Cancellation** | Cancel pending vouchers with audit trail |
| **Bulk Delete** | Remove unpaid vouchers in batch |
| **Status Tracking** | Filter vouchers by status (Pending, Paid, Cancelled) |
| **Fee Heads** | Configurable fee components (Tuition, Lab, Library, Transport, etc.) |
| **Conditional Fees** | Admission fees based on program and subject group (e.g., higher fee for Computer groups) |
| **Auto-Difference** | Automatic calculation of fee difference when students change subjects mid-cycle |

**Technical Specs:**
- **Primary Models:** `FeeVoucher`, `FeeHead`
- **Logic Service:** `FeeCalculationService`
- **Advanced Logic:** Date-based late fee automation and multi-head refund tracking.

---

### 7. Timetable Engine

Intelligent timetable generation with multi-constraint optimization.

| Feature | Description |
|---|---|
| **Room Assignment** | Assigns rooms while preventing double-booking |
| **Conflict Detection** | Real-time validation for teacher, room, and student group conflicts |
| **Subject Groups** | Organize subjects into groups for scheduling efficiency |
| **Manual Creation** | Administrative ability to manually create individual slots |
| **Batch Management** | Generate separate timetables per admission year/batch |
| **HE Timetables** | Separate timetable system for BS/ADP programs |
| **Lifecycle Mgmt** | Timetables can be marked as `Active` or `Archived` to prevent scheduling clashes |
| **Timetable Cloning** | Clone an entire session's timetable to a new year as a starting base |
| **Time Slot CRUD** | Full management of lecture time slots with duration and sequence control |

**Technical Specs:**
- **Primary Model:** `Timetable`
- **Logic Service:** `TimetableValidatorService`
- **Advanced Logic:** Dynamic Subject Compatibility Matrix for automatic conflict resolution.

---

### 8. Learning Management System (LMS)

A built-in LMS that transforms the platform from administrative to academic — enabling digital teaching and learning.

#### Assignments
| Feature | Description |
|---|---|
| **Create Assignments** | Teachers create subject-specific assignments with due dates, marks, and file attachments |
| **Program Type Support** | Separate workflows for Intermediate and BS/ADP assignments |
| **Target Group Filtering** | Assign to specific classes (11th, 12th) or semesters |
| **Student Submissions** | Students upload homework/assignments in PDF, DOCX, JPG, PNG, or ZIP |
| **Grading & Feedback** | Teachers review, grade, and provide written feedback |
| **Edit & Delete** | Full assignment lifecycle management for teachers |

#### Real-time Messaging
| Feature | Description |
|---|---|
| **Teacher ↔ Student Chat** | Direct messaging between teachers and their assigned students |
| **Unread Badges** | Visual indicators showing unread message counts |
| **Read Receipts** | Messages marked as read when viewed by the recipient |
| **Student List** | Teachers see only their assigned students with admission details |

#### Dashboards
| Feature | Description |
|---|---|
| **Teacher Dashboard** | Total assignments, pending submissions count, recent activity |
| **Student Dashboard** | Assignment status, submission history, class info, performance link |

**Key Models:** `Assignment`, `AssignmentSubmission`, `ChatMessage`

---

### 9. Library Management System

A full-featured library module enabling book cataloging, borrowing, and resource management for students and staff.

#### Book Catalog
| Feature | Description |
|---|---|
| **Complete CRUD** | Add, edit, soft-delete books with full field support |
| **Bilingual Titles** | English and Urdu titles with RTL support |
| **Rich Metadata** | Ref#, ISBN, Acc#, Language, Book Type, Pages, Price, Publication Year, Remarks |
| **Author Management** | CRUD for authors with English and Urdu names |
| **Genre Management** | Categorize books by genre (Science, Literature, etc.) |
| **Location Tracking** | Map books to physical locations (Shelf A-3, Room 12, etc.) |
| **Advanced Search** | Search by title, author, ISBN, Ref#, or Acc# with genre/language filters |
| **Availability Tracking** | Real-time available copies computed from volumes minus active issues |

#### Book Issuing & Returns
| Feature | Description |
|---|---|
| **Issue to Anyone** | Issue books to both students and staff members |
| **AJAX Borrower Search** | Live search for students and staff by name or ID |
| **Due Date Tracking** | Configurable due dates with overdue detection |
| **Return Processing** | One-click return with optional fine amount |
| **Overdue Alerts** | Visual highlighting of overdue books with day count |
| **Issue History** | Complete audit trail of all borrowing transactions |

#### Import / Export
| Feature | Description |
|---|---|
| **CSV Export** | Download entire book catalog as CSV with all fields |
| **CSV Import** | Bulk import books from CSV with auto-creation of authors, genres, and locations |
| **Format Documentation** | Built-in CSV format guide for easy data migration |

#### Library Dashboard
| Feature | Description |
|---|---|
| **Statistics Cards** | Total books, total copies, available, issued, overdue, and authors count |
| **Quick Actions** | One-click access to search, issue, authors, genres, locations, and import/export |
| **Recent Activity** | Latest 10 book issues with borrower details and status |

**Key Models:** `Book`, `BookIssue`, `Author`, `Genre`, `BookLocation`

---

### 10. Public Website & Student Portal

A professional public-facing website that serves as the institution's digital front door.

| Feature | Description |
|---|---|
| **Homepage** | Dynamic content with statistics, recent news, and quick links |
| **Programs & Departments** | Detailed department pages with faculty, subjects, and student counts |
| **Staff Directory** | Complete faculty listing with designations and photos |
| **Photo Gallery** | Album-based photo gallery with image management |
| **Downloads Section** | Uploadable resources for public access |
| **News & Announcements** | Dynamic news section for institutional updates |
| **Principals Section** | Historical record of current and past principals |
| **Student Corner** | Self-service student search with result viewing (Inter & HE) |
| **Online Admission** | Public admission application form with real-time status tracking |
| **Duty Roster Lookup** | Public invigilator duty roster for exam transparency |
| **Contact & About** | Institutional information and contact details |
| **Principal Archive** | Historical record of past principals with their tenure and personal messages |
| **Dynamic Programs** | HSSC Programs page dynamically groups subjects into Compulsory and Elective Subject Groups |

**Key Controllers:** `PublicController`, `PublicAdmissionController`

---

### 11. Admin Dashboard, Analytics & Theming

A powerful analytics hub providing real-time institutional insights with full visual customization.

#### Dashboard Analytics
| Feature | Description |
|---|---|
| **Enrollment Statistics** | Active students by program, class, and admission year |
| **Staff Overview** | Total staff by designation, teaching vs. non-teaching ratios |
| **Fee Analytics** | Collection summaries, pending amounts, and payment trends |
| **Program Distribution** | Visual breakdown of BS/ADP enrollment across programs |
| **Subject Analytics** | Student counts per subject for resource planning |
| **Admission Trends** | Year-over-year admission statistics |
| **Role-Specific Views** | Different dashboard layouts for Admin, Principal, and Teacher |

#### Global Theming Engine
| Feature | Description |
|---|---|
| **Color Settings Panel** | Admin UI at `/admin/settings/colors` with live preview |
| **4 Configurable Colors** | Primary, Secondary, Highlight, and Success — all hex color picker |
| **Global Coverage** | Colors apply to **all** layouts: Admin panel, LMS, Library, Login page, and Public website |
| **CSS Variable Injection** | DB-driven `:root` overrides on every page load via `InstituteSetting::getColors()` |
| **Bootstrap Override** | Dynamic overrides for `.bg-primary`, `.btn-primary`, `.text-primary`, and all variants |
| **Derived Colors** | Hover, active, dark, mint, and light-bg colors auto-computed from primary/secondary |
| **Reset to Defaults** | One-click restore to the original institutional color palette |

**Key Controllers:** `DashboardController`, `ColorSettingsController`

---

### 12. User & Role Management

Enterprise-grade access control with granular permission management.

| Feature | Description |
|---|---|
| **Role-Based Access (RBAC)** | 5+ roles: Super Admin, Admin, Principal, Teacher, Student |
| **Granular Permissions** | Resource-level permissions (e.g., `students.create`, `results.edit`) |
| **Role Switching** | Users with multiple roles can switch context seamlessly |
| **Auto Account Creation** | Generate login accounts from staff or student records |
| **Two-Factor Authentication** | Optional 2FA for enhanced security |
| **Session Management** | Track active sessions and manage concurrent logins |
| **Profile Management** | Self-service profile editing with password change |
| **Audit Trail** | `created_by`, `updated_by`, `deleted_by` tracking on all records |

**Key Models:** `User`, `Roles`, `Permission`, `RolePermission`, `UserRole`, `UserPermission`, `Session`

---

### 13. Notification System (SMS + Email)

A centralized broadcast and transactional communication infrastructure.

| Feature | Description |
|---|---|
| **Multi-Channel** | Support for SMS (Local Gateways), Email (SMTP), and In-App notifications |
| **Templates** | Database-driven templates with dynamic placeholders like `{student_name}` |
| **Scheduled Jobs** | Automated dispatch of fee reminders and low attendance warnings |
| **Searchable Logs** | Complete audit trail of all sent messages with roll number tracking |

**Technical Specs:**
- **Logic Service:** `NotificationService`
- **Primary Models:** `NotificationTemplate`, `NotificationLog`
- **Advanced Logic:** Background queue processing with exponential backoff for failed SMS deliveries.

---

### 14. Staff Leave & Attendance

Complete lifecycle management for staff time-off and daily presence.

| Feature | Description |
|---|---|
| **Self-Marking** | Staff can mark attendance via their portal with geo-fencing (optional) |
| **Leave Approval** | Multi-level workflow: Staff → HOD → Principal → Admin |
| **Balance Tracking** | Automatic annual allocation of Casual, Medical, and Earned leaves |
| **Holiday Calendar** | Integrated institutional and national holiday management |

**Technical Specs:**
- **Primary Models:** `LeaveType`, `LeaveApplication`, `StaffDailyAttendance`
- **Advanced Logic:** Automatic deduction of leave balance upon final approval and monthly attendance summary generation.

---

### 15. Payroll & Salary Management

Automated financial management for institutional human resources.

| Feature | Description |
|---|---|
| **Salary Structures** | Define Earnings (Allowances) and Deductions per BPS grade or individual |
| **Attendance Linkage** | Automatic deductions for unauthorized absences and unpaid leaves |
| **Salary Slips** | Professional PDF generation for individual staff members |
| **Bank Exports** | Generate bank-compatible CSV/Excel files for bulk disbursement |

**Technical Specs:**
- **Primary Models:** `PayrollRun`, `SalarySlip`, `SalaryStructure`
- **Advanced Logic:** Rule-based calculation engine for income tax withholding and provident fund contributions.

---

### 16. Parent Portal

A dedicated, secure interface for parents to monitor their child's progress.

| Feature | Description |
|---|---|
| **Student Linking** | One parent account can manage multiple children enrolled in different programs |
| **Progress Tracking** | Real-time view of attendance, test results, and fee status |
| **Announcements** | Direct access to institutional notices and teacher feedback |
| **Voucher Access** | Download and view fee vouchers directly from the mobile-responsive portal |

**Technical Specs:**
- **Primary Model:** `Parent`
- **Relationship:** `belongsToMany(Student)` via `parent_student` pivot.
- **Advanced Logic:** Role-based data scoping ensuring parents only see their linked students' data.

---

### 17. Online Payment Integration

Modernizing fee collection via Pakistani payment gateways.

| Feature | Description |
|---|---|
| **Gateway Support** | Integration with JazzCash, EasyPaisa, and 1Link (via stubs/APIs) |
| **Async Callbacks** | Real-time voucher status updates via secure webhooks |
| **Transaction Logs** | Detailed record of every payment attempt for reconciliation |
| **Digital Receipts** | Automated PDF receipt generation upon successful transaction |

**Technical Specs:**
- **Logic Service:** `PaymentGatewayService`
- **Primary Model:** `OnlineTransaction`
- **Advanced Logic:** HMAC signature verification for secure communication with external gateways.

---

### 18. Bulk Data Import

Mass data entry engine for rapid institutional digitization.

| Feature | Description |
|---|---|
| **Excel Templates** | Downloadable pre-formatted templates for students, staff, and results |
| **Validation Engine** | Pre-import validation for duplicates, data types, and logic errors |
| **Upsert Logic** | Automatically update existing records or create new ones based on CNIC/Roll No |
| **Background Processing** | Handles files with 5,000+ rows without timing out via Laravel Queues |

**Technical Specs:**
- **Primary Models:** `ImportLog`, `ImportError`
- **Package:** `Maatwebsite/Laravel-Excel`
- **Advanced Logic:** Chunk-based reading to minimize memory footprint during massive imports.

---

### 19. Transport & Hostel Management

Management of auxiliary institutional services and infrastructure.

| Feature | Description |
|---|---|
| **Route Management** | Track bus routes, stops, pick-up times, and vehicle assignments |
| **Hostel Occupancy** | Room-wise student allotments with bed tracking and vacate history |
| **Automated Billing** | Transport and Hostel fees automatically added to student vouchers |
| **Inventory Tracking** | Track vehicle maintenance and hostel room repairs |

**Technical Specs:**
- **Primary Models:** `TransportRoute`, `Hostel`, `HostelRoom`, `HostelAllotment`
- **Advanced Logic:** Seating capacity validation to prevent over-allotment in buses and rooms.

---

### 20. Alumni & Graduation Module

Finalizing the student lifecycle and maintaining institutional heritage.

| Feature | Description |
|---|---|
| **Eligibility Engine** | Auto-check students for graduation based on results and fee clearance |
| **Certificate Issuance** | Generate Degree, DMC, Character, and Migration certificates in PDF |
| **Alumni Directory** | Searchable database of graduated students with career tracking |
| **Verification Portal** | Public interface for employers to verify student credentials |

**Technical Specs:**
- **Primary Models:** `GraduationRecord`, `IssuedCertificate`, `AlumniProfile`
- **Advanced Logic:** Dynamic template engine for certificate generation with unique QR verification.

---

### 21. Asset & Budget Management

Financial oversight and physical asset tracking for the institution.

| Feature | Description |
|---|---|
| **Asset Register** | Detailed tracking of IT equipment, furniture, and lab assets with barcodes |
| **Depreciation** | Automated calculation of asset value over time (Straight-line/Reducing balance) |
| **Budgeting** | Annual departmental budget allocation with real-time utilization tracking |
| **Expense Logging** | Record expenditures against budget heads with digital receipt attachments |

**Technical Specs:**
- **Primary Models:** `Asset`, `Budget`, `Expenditure`, `ProcurementRequest`
- **Advanced Logic:** Budget variance analysis and automated alerts for warranty expirations.

---

## 🔧 Technical Specifications

| Component | Technology |
|---|---|
| **Backend Framework** | Laravel 11.x (PHP 8.2+) |
| **Database** | MySQL 8.0 with InnoDB engine |
| **Frontend** | Blade Templates, Bootstrap 5, jQuery, Chart.js |
| **PDF Generation** | DomPDF (Barryvdh/Laravel-DomPDF) |
| **Excel/CSV** | Maatwebsite/Laravel-Excel, League/CSV |
| **Web Scraping** | Symfony DomCrawler (BISE integration) |
| **Authentication** | Laravel Sanctum + Session-based auth |
| **File Storage** | Laravel Filesystem (local + cloud-ready) |
| **Soft Deletes** | Enabled across all critical models |
| **API Ready** | RESTful architecture with Sanctum API tokens |

### Database Architecture

- **80+ relational tables** with proper foreign key constraints
- **Soft deletes** on all critical entities (students, staff, users, admissions)
- **Audit columns** (`created_by`, `updated_by`, `deleted_by`) for compliance
- **Optimized queries** with eager loading, upsert operations, and indexed lookups
- **Archive tables** for historical attendance data

---

## 🔒 Security & Compliance

| Feature | Implementation |
|---|---|
| **Authentication** | Bcrypt password hashing with configurable strength |
| **Authorization** | Multi-layer RBAC (Role → Permission → Resource) |
| **CSRF Protection** | Laravel's built-in CSRF token verification on all forms |
| **SQL Injection Prevention** | Eloquent ORM with parameterized queries |
| **XSS Protection** | Blade's automatic output escaping |
| **Session Security** | Configurable session lifetime, secure cookies |
| **Two-Factor Auth** | TOTP-based 2FA with recovery codes |
| **Input Validation** | Server-side validation on all form submissions |
| **Soft Deletes** | Data preservation for audit compliance |
| **File Upload Security** | MIME type validation, size limits, isolated storage |
| **License Protection** | Mandatory developer credits and license warnings on all printable views and standalone pages |
| **Notification Logs** | Enhanced logs with student roll numbers and search filters for accountability |

---

## 🚀 Deployment & Infrastructure

### System Requirements

| Requirement | Minimum |
|---|---|
| **PHP** | 8.2+ |
| **MySQL** | 8.0+ |
| **Web Server** | Apache 2.4+ or Nginx 1.18+ |
| **RAM** | 2 GB (4 GB recommended) |
| **Storage** | 20 GB (scales with attachments) |
| **OS** | Ubuntu 22.04 LTS / CentOS 8+ / Windows Server |

### Deployment Steps

```bash
# 1. Clone the repository
git clone https://github.com/your-org/cms.git
cd cms

# 2. Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Set up database
mysql -u root -e "CREATE DATABASE cms;"
php artisan migrate --seed

# 5. Create storage link
php artisan storage:link

# 6. Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 7. Start the application
php artisan serve
```

### Production Recommendations

- **SSL/TLS**: HTTPS with Let's Encrypt or commercial certificate
- **Queue Worker**: Laravel Horizon for email and notification queues
- **Caching**: Redis or Memcached for session and query caching
- **Backup**: Automated daily database backups with `spatie/laravel-backup`
- **Monitoring**: Laravel Telescope for debugging, Sentry for error tracking

---

---

## 🧠 Advanced Business Logic & Workflows

### 1. Timetable Engine Optimization
The Timetable Engine uses a **Conflict Detection Matrix** that evaluates three primary constraints:
- **Teacher Availability**: Ensures no teacher is double-booked across different rooms or subjects.
- **Room Capacity**: Validates student group size against physical room limits.
- **Subject Compatibility**: A specialized matrix determines which subjects share students (e.g., FSc Pre-Med vs. Pre-Eng), allowing non-conflicting subjects to run simultaneously while preventing overlaps for shared groups.

### 2. Fee Computation Engine (`FeeCalculationService`)
A multi-layered calculation service that computes vouchers in real-time:
- **Base Fees**: Automatically filtered by program type (Inter/HE) and gender.
- **Conditional Rules**: Applies specific heads (e.g., Computer Fund) only if the student is enrolled in relevant subjects.
- **Background Group Logic**: Different admission fees are charged based on the student's background (e.g., Pre-Medical vs. Humanities).
- **Subject Change Logic**: When a student changes subjects, the engine calculates the price difference between the old and new subject groups and generates a delta-voucher.

### 3. Examination Lifecycle
- **Datesheet Algorithm**: Generates a schedule that minimizes consecutive exams for the same student group.
- **Seating Plan Engine**: An alternation algorithm ensures students of the same subject do not sit adjacent to each other.
- **BISE Scraper**: A Symfony-based scraper that performs authenticated requests to Board websites (e.g., BISE Bahawalpur) to fetch and sync official student results automatically.

---

## 👨‍💻 Developer & Integration Guide

### 1. Theming Engine & Dynamic Styling
CMS uses a database-driven CSS variable system. The `InstituteSetting::getColors()` method fetches hex codes from the DB and injects them into a `:root` style block in the main layout. This allows institutional branding changes without any code deployment.

### 2. Service-Layer Architecture
All complex business logic is encapsulated in **Service Classes** (`app/Services`), keeping controllers lean and maintainable. This facilitates unit testing and reusability across web and API endpoints.

### 3. File & Media Handling
- **Storage Drivers**: Fully compatible with Local, S3, or DigitalOcean Spaces via Laravel's Filesystem abstraction.
- **Document Security**: Private files (e.g., staff appointment letters) are stored in `storage/app/private` and served via signed routes to ensure only authorized users can view them.

### 4. Audit & Compliance
Every critical database record uses the `SoftDeletes` trait and includes `created_by` / `updated_by` audit columns. The system maintains a global activity log for administrative actions to ensure institutional accountability.

---

## 🏆 Why Choose CMS?

### 1. Built for Pakistani Institutions
Unlike generic ERP systems, CMS understands the specific workflows of Punjab's educational boards — from BISE roll number formats to board exam result integration.

### 2. Production-Tested
This is not a demo or prototype. CMS is actively managing real students, real exams, and real finances in a live production environment.

### 3. Dual-Program Architecture
Seamlessly handle both **Intermediate (HSSC)** and **Higher Education (BS/ADP)** programs in a single unified system — no need for separate software.

### 4. All-in-One Platform
From online admissions to final results, from fee vouchers to timetable generation, from attendance tracking to LMS — everything in one place.

### 5. Self-Hosted & Secure
Your data stays on your servers. No recurring SaaS fees. No data leaving your institution. Full control, full ownership.

### 6. Extensible & Modern
Built on Laravel — the world's most popular PHP framework. Easy to extend, customize, and maintain with a vast ecosystem of packages and community support.

---

<p align="center">
  <strong>Ready to transform your institution?</strong><br>
  <em>Contact us to schedule a live demonstration.</em>
</p>

---

<p align="center">
  <sub>© 2024–2026 College Management System. All rights reserved.</sub><br>
  <sub>Built using Laravel, PHP, and MySQL.</sub>
</p>
