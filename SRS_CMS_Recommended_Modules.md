# Software Requirements Specification (SRS)
## College Management System — Recommended Modules
**Version:** 1.1  
**Date:** April 2026  
**Project:** College Management System (CMS) — Laravel 11.x  
**Prepared For:** Development Team  
**Status:** In Progress / Updated

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Module 1 — Notification System (SMS + Email)](#2-module-1--notification-system-sms--email)
3. [Module 2 — Staff Leave & Attendance Management](#3-module-2--staff-leave--attendance-management)
4. [Module 3 — Payroll & Salary Management](#4-module-3--payroll--salary-management)
5. [Module 4 — Parent Portal](#5-module-4--parent-portal)
6. [Module 5 — Online Payment Integration](#6-module-5--online-payment-integration)
7. [Module 6 — Bulk Excel Import](#7-module-6--bulk-excel-import)
8. [Module 7 — Transport & Hostel Management](#8-module-7--transport--hostel-management)
9. [Module 8 — Alumni & Graduation Module](#9-module-8--alumni--graduation-module)
10. [Module 9 — Asset & Budget Management](#10-module-9--asset--budget-management)
11. [Non-Functional Requirements (All Modules)](#11-non-functional-requirements-all-modules)
12. [Database Overview (All Modules)](#12-database-overview-all-modules)

---

## 1. Introduction

### 1.1 Purpose
This Software Requirements Specification (SRS) document describes the functional and non-functional requirements for nine recommended modules to be added to the existing College Management System (CMS). These modules address identified gaps in the current system and are required to fully digitize the institutional management lifecycle.

### 1.2 Scope
The CMS is a Laravel 11.x web application built for Pakistani educational institutions (Intermediate HSSC and Higher Education BS/ADP programs). The nine modules described in this document extend the existing 11-module system to cover staff HR, finance, communication, infrastructure, and student lifecycle management.

### 1.3 Existing System Summary
The current CMS includes: Student Information System (SIS), Staff & HR Management, Admissions Portal, Examination Management, Attendance Management (students), Fee & Voucher Management, Timetable Engine, Learning Management System (LMS), Library Management, Public Website & Student Portal, Admin Dashboard & Analytics, and User & Role Management (RBAC).

### 1.4 Definitions and Abbreviations

| Term | Definition |
|---|---|
| SRS | Software Requirements Specification |
| RBAC | Role-Based Access Control |
| SMS | Short Message Service |
| HE | Higher Education (BS/ADP programs) |
| HSSC | Higher Secondary School Certificate (Intermediate) |
| HOD | Head of Department |
| DXA | Document Extended Attribute (unit) |
| API | Application Programming Interface |
| PWA | Progressive Web Application |
| JTD | Job Title Designation |

### 1.5 Priority Legend

| Priority | Meaning |
|---|---|
| **P1 — Critical** | Must be built first; blocks other modules |
| **P2 — High** | Required for complete institutional management |
| **P3 — Medium** | Strongly recommended; significant operational value |
| **P4 — Low** | Enhances system completeness; lower urgency |

---

## 2. Module 1 — Notification System (SMS + Email)

**Priority:** P1 — Critical  
**Estimated Complexity:** High  
**Status:** Core Implemented & Refined
**Dependencies:** Existing Fee Module, Attendance Module, Examination Module, Admissions Module

### 2.1 Module Overview
The Notification System provides a centralized broadcast and transactional communication infrastructure for all stakeholders (Admin, Teacher, Student, Parent). It supports SMS delivery via Pakistani telecom gateways and email delivery via SMTP. All existing modules will be able to trigger notifications through a unified `NotificationService`.

### 2.2 Functional Requirements

#### 2.2.1 Notification Types

| # | Notification Type | Trigger | Channel | Recipients |
|---|---|---|---|---|
| N-01 | Fee Due Reminder | 7 days before due date (scheduled) | SMS + Email | Student, Parent |
| N-02 | Fee Overdue Alert | 1 day after due date (scheduled) | SMS | Student, Parent |
| N-03 | Fee Payment Confirmation | Voucher marked as paid | SMS + Email | Student |
| N-04 | Admission Application Received | Online form submitted | Email | Applicant |
| N-05 | Admission Status Update | Application approved/rejected | SMS + Email | Applicant |
| N-06 | Low Attendance Warning | Attendance falls below threshold (default 75%) | SMS + Email | Student, Parent |
| N-07 | Result Published | Admin publishes results | SMS + Email | Student |
| N-08 | Exam Datesheet Released | Datesheet saved/published | SMS + Email | All students of that class |
| N-09 | Assignment Posted | Teacher creates assignment | In-app + Email | Target student group |
| N-10 | Assignment Due Reminder | 24 hours before due date | In-app | Student |
| N-11 | Bulk Announcement | Admin creates a broadcast | SMS + Email + In-app | Configurable groups |
| N-12 | Password Reset | User requests reset | Email | User |
| N-13 | Account Created | Auto account created for staff/student | Email | New user |

#### 2.2.2 Notification Configuration
- **FR-N-01:** Admin must be able to enable or disable each notification type individually from a settings panel.
- **FR-N-02:** Admin must be able to configure the attendance threshold percentage that triggers the low attendance warning (default: 75%).
- **FR-N-03:** Admin must be able to configure the number of days before fee due date for the reminder (default: 7 days).
- **FR-N-04:** Each notification type must support a customizable message template with dynamic placeholders (e.g., `{student_name}`, `{due_date}`, `{amount}`, `{class}`).
- **FR-N-05:** System must support a global SMS on/off toggle and a global email on/off toggle.

#### 2.2.3 Bulk Announcements
- **FR-N-06:** Admin and Principal must be able to compose and send bulk announcements.
- **FR-N-07:** Announcement recipients must be filterable by: All Students, Class (11th/12th), Program (BS/ADP), Individual Subject Group, All Staff, Teaching Staff Only, Non-Teaching Staff Only, Custom selection.
- **FR-N-08:** Announcements must support scheduling (send at a future date/time).
- **FR-N-09:** Announcements must be stored and viewable in a notification log.

#### 2.2.4 Delivery Channels

**SMS Gateway:**
- **FR-N-10:** System must support integration with at least one Pakistani SMS gateway. Supported gateways: Jazz (Warid), Ufone, Zong, or a third-party aggregator (e.g., Twilio, MSG91, or a local provider with HTTP API).
- **FR-N-11:** SMS gateway credentials (API key, sender ID) must be configurable from admin settings, not hardcoded.
- **FR-N-12:** System must respect daily SMS sending limits defined by the gateway and queue excess messages.
- **FR-N-13:** Failed SMS deliveries must be logged with reason and retried up to 3 times with exponential backoff.

**Email (SMTP):**
- **FR-N-14:** System must send emails via SMTP. SMTP credentials must be configurable from admin settings.
- **FR-N-15:** All outgoing emails must use a branded HTML template with institution name, logo, and contact details.
- **FR-N-16:** Email unsubscribe mechanism is not required for transactional emails (fee, attendance, results) but must be present for bulk announcements.

**In-App Notifications:**
- **FR-N-17:** All authenticated users must see a notification bell icon in the navigation bar showing unread count.
- **FR-N-18:** Clicking the bell must display the last 30 notifications in a dropdown with mark-as-read functionality.
- **FR-N-19:** Marking all as read must be available as a single action.

#### 2.2.5 Notification Log
- **FR-N-20:** All sent notifications (SMS, email, in-app) must be logged with: recipient, type, channel, status (sent/failed/pending), timestamp, and message content.
- **FR-N-21:** Admin must be able to filter and search the notification log by student name or roll number.
- **FR-N-22:** Admin must be able to manually retry failed notifications from the log.
- **FR-N-23:** Notification logs must include the student's roll number for better traceability and accountability.

### 2.3 Technical Requirements

- **TR-N-01:** Use Laravel's Queue system (database or Redis driver) for all notification dispatch. No synchronous sending.
- **TR-N-02:** Create a dedicated `NotificationService` class that all modules call to dispatch notifications. No direct mailing/SMS calls from controllers.
- **TR-N-03:** Use Laravel's built-in `Notification` facade with custom channels for SMS.
- **TR-N-04:** Notification templates must be stored in the database (not only in Blade files) to allow admin editing without code deployment.
- **TR-N-05:** Schedule `php artisan notifications:send-scheduled` to run every 15 minutes via Laravel Scheduler (`app/Console/Kernel.php`).

### 2.4 Database Tables

| Table | Key Columns |
|---|---|
| `notification_templates` | `id`, `type`, `channel`, `subject`, `body`, `placeholders`, `is_active` |
| `notification_settings` | `id`, `key`, `value` (key-value config store) |
| `notification_logs` | `id`, `recipient_id`, `recipient_type`, `channel`, `type`, `message`, `status`, `attempts`, `sent_at`, `failed_reason` |
| `announcements` | `id`, `title`, `body`, `recipient_group`, `scheduled_at`, `sent_at`, `created_by` |
| `announcement_recipients` | `id`, `announcement_id`, `recipient_id`, `recipient_type`, `channel`, `status` |

### 2.5 User Roles & Permissions

| Role | Permissions |
|---|---|
| Super Admin | Full access to all notification settings, templates, logs, announcements |
| Admin | Send announcements, view logs |
| Principal | Send announcements to students and teachers |
| Teacher | Receive notifications only (no sending) |
| Student | Receive notifications, view in-app notification list |

---

## 3. Module 2 — Staff Leave & Attendance Management

**Priority:** P1 — Critical  
**Estimated Complexity:** High  
**Status:** Implemented
**Dependencies:** Existing Staff & HR Module, User & Role Module, Timetable Engine

### 3.1 Module Overview
This module manages the complete staff attendance and leave lifecycle. It covers daily attendance marking (manual and biometric-ready), leave application and multi-level approval workflows, leave balance tracking, public holiday management, and reporting. It integrates with the existing Staff HR profiles and will feed data into the Payroll module (Module 3).

### 3.2 Functional Requirements

#### 3.2.1 Staff Daily Attendance
- **FR-SA-01:** Admin and HOD must be able to mark daily attendance for all staff members (present, absent, late, half-day, on-leave).
- **FR-SA-02:** Staff members must be able to self-mark attendance (check-in/check-out) within a configurable time window (e.g., 7:30 AM to 9:30 AM for check-in).
- **FR-SA-03:** Late arrival (after configurable grace period, default 15 minutes) must be automatically flagged.
- **FR-SA-04:** Admin must be able to override attendance records for any staff member with a reason.
- **FR-SA-05:** Monthly attendance summary per staff member must be auto-calculated showing: total working days, present, absent, late, half-day, and on-leave counts.
- **FR-SA-06:** System must detect and respect public holidays (configurable holiday calendar) and not count them as absent.
- **FR-SA-07:** Staff attendance data must be exportable to CSV/Excel, filterable by department, designation, and date range.

#### 3.2.2 Leave Types
- **FR-SL-01:** Admin must be able to create and configure leave types with the following attributes: name, annual allocation (days), is paid/unpaid, is carry-forward allowed, max carry-forward days, applicable staff categories (teaching/non-teaching/all).
- **FR-SL-02:** Default leave types to be pre-seeded: Casual Leave (12 days), Earned/Annual Leave (30 days), Medical/Sick Leave (10 days), Maternity Leave (90 days, female staff only), Paternity Leave (3 days, male staff only), Study Leave (configurable), Hajj Leave (once in service), Without Pay Leave (unlimited, unpaid).

#### 3.2.3 Leave Balance Management
- **FR-SL-03:** System must auto-allocate leave balances at the start of each year (January 1st) based on leave type configuration.
- **FR-SL-04:** Leave balances must be viewable per staff member showing: allocated, used, pending (applied but not yet approved), and remaining.
- **FR-SL-05:** Admin must be able to manually adjust leave balances for any staff member with a reason and audit trail.
- **FR-SL-06:** Leave carry-forward must be processed automatically at year-end (configurable run date) based on leave type settings.

#### 3.2.4 Leave Application & Approval Workflow
- **FR-SL-07:** Staff members must be able to apply for leave from their panel specifying: leave type, from date, to date, reason, and optional supporting document upload (e.g., medical certificate).
- **FR-SL-08:** System must calculate the number of working days requested (excluding Sundays and public holidays) and show it before submission.
- **FR-SL-09:** System must validate that the staff member has sufficient balance before allowing submission (with a warning, not a hard block, for unpaid leave).
- **FR-SL-10:** Leave applications must follow a configurable approval chain. Default chain: Teacher → HOD → Principal → Admin. Each level can be enabled or disabled.
- **FR-SL-11:** Each approver in the chain must receive an in-app notification (and email if enabled) when a leave application reaches their level.
- **FR-SL-12:** Approvers must be able to approve, reject, or forward with comments from their panel.
- **FR-SL-13:** On final approval, attendance for the leave period must be automatically marked as "On Leave" and balance deducted.
- **FR-SL-14:** On rejection, the applicant must be notified with the rejection reason.
- **FR-SL-15:** Staff members must be able to cancel an approved leave application (if future dates) with a reinstatement of balance.

#### 3.2.5 Leave Calendar
- **FR-SL-16:** A monthly calendar view must show all approved leave for all staff, color-coded by leave type, visible to Admin and Principal.
- **FR-SL-17:** Teachers must be able to see their own leave calendar and submit new applications from the calendar view.

#### 3.2.6 Public Holiday Management
- **FR-SL-18:** Admin must be able to manage a public holiday calendar per academic year.
- **FR-SL-19:** Holidays must be categorized as: National Holiday, Regional Holiday, Institutional Holiday (college-specific off day).
- **FR-SL-20:** The system must auto-exclude holidays from leave day calculations and attendance summaries.

### 3.3 Technical Requirements

- **TR-SA-01:** Attendance records must use `upsert` operations for bulk marking (consistent with existing student attendance approach).
- **TR-SA-02:** Leave approval state must be stored as a state machine: `draft → pending → under_review → approved / rejected / cancelled`.
- **TR-SA-03:** All leave state transitions must be logged in an `leave_approval_logs` table with actor, action, timestamp, and comment.
- **TR-SA-04:** A scheduled command `php artisan leave:allocate-annual` must run on January 1st each year to bulk-allocate leave balances.

### 3.4 Database Tables

| Table | Key Columns |
|---|---|
| `leave_types` | `id`, `name`, `annual_days`, `is_paid`, `carry_forward`, `max_carry_forward`, `applicable_to` |
| `staff_leave_balances` | `id`, `staff_id`, `leave_type_id`, `year`, `allocated`, `used`, `carried_forward`, `adjusted` |
| `leave_applications` | `id`, `staff_id`, `leave_type_id`, `from_date`, `to_date`, `working_days`, `reason`, `document`, `status`, `created_at` |
| `leave_approval_logs` | `id`, `application_id`, `approver_id`, `action`, `comment`, `created_at` |
| `staff_daily_attendance` | `id`, `staff_id`, `date`, `status`, `check_in`, `check_out`, `remarks`, `marked_by` |
| `public_holidays` | `id`, `name`, `date`, `type`, `year` |

---

## 4. Module 3 — Payroll & Salary Management

**Priority:** P2 — High  
**Estimated Complexity:** High  
**Status:** Implemented
**Dependencies:** Staff & HR Module, Staff Leave & Attendance Module (Module 2)

### 4.1 Module Overview
The Payroll module automates monthly salary computation, slip generation, and payment tracking for all staff members. It integrates with HR profiles (designation, BPS grade, bank account details) and the Leave & Attendance module (to deduct for unauthorized absences and unpaid leave).

### 4.2 Functional Requirements

#### 4.2.1 Salary Structure Configuration
- **FR-P-01:** Admin must be able to define salary components (pay heads) of two types: Earnings (Allowances) and Deductions.
- **FR-P-02:** Default earnings heads to be pre-seeded: Basic Pay, House Rent Allowance (HRA), Medical Allowance, Conveyance Allowance, Special Allowance, Overtime.
- **FR-P-03:** Default deduction heads to be pre-seeded: Income Tax (Withholding), EOBI Contribution, PESSI/SESSI Contribution, Provident Fund, Loan Recovery, Penalty/Fine, Absence Deduction.
- **FR-P-04:** Each component must have a configurable calculation type: Fixed Amount, Percentage of Basic Pay, or Formula-based (e.g., `basic * 0.45`).
- **FR-P-05:** Admin must be able to assign a salary structure (combination of components) to individual staff members or by designation/BPS grade.
- **FR-P-06:** BPS (Basic Pay Scale) grade tables for Punjab government scales must be importable/configurable.

#### 4.2.2 Monthly Payroll Processing
- **FR-P-07:** Admin must be able to initiate payroll processing for a given month/year for all staff or a specific department.
- **FR-P-08:** System must auto-calculate each staff member's gross salary, all allowances, and all deductions based on their assigned salary structure.
- **FR-P-09:** System must auto-calculate absence deductions based on daily attendance records from Module 2 (unauthorized absences and unpaid leave days).
- **FR-P-10:** Admin must be able to add one-time bonuses or deductions for individual staff members before finalizing payroll.
- **FR-P-11:** Payroll must go through a review/approval process: `Draft → Reviewed → Approved → Paid`.
- **FR-P-12:** Once approved, payroll records must be locked from editing. Only Super Admin can unlock for correction.
- **FR-P-13:** System must detect and prevent duplicate payroll runs for the same month/year for the same staff member.

#### 4.2.3 Salary Slips
- **FR-P-14:** System must generate individual salary slips in PDF format for each staff member.
- **FR-P-15:** Salary slip must include: staff name, designation, department, month/year, all earning components with amounts, all deduction components with amounts, gross pay, total deductions, net pay, bank account details, and institution stamp/header.
- **FR-P-16:** Bulk PDF generation (all slips in one PDF or as a ZIP of individual PDFs) must be supported.
- **FR-P-17:** Staff members must be able to view and download their own salary slips from their panel (current and historical).

#### 4.2.4 Payment Tracking
- **FR-P-18:** Admin must be able to mark payroll as paid with payment date and mode (bank transfer, cash, cheque).
- **FR-P-19:** Bank transfer file generation (CSV in bank-specific format) must be supported for bulk salary disbursement.
- **FR-P-20:** Salary payment history must be searchable and filterable by staff, month, department, and payment status.

#### 4.2.5 Reporting
- **FR-P-21:** Monthly payroll summary report showing total salary expense by department.
- **FR-P-22:** Year-to-date salary report per staff member.
- **FR-P-23:** Income tax deduction report (for withholding tax compliance).
- **FR-P-24:** EOBI and PESSI/SESSI contribution reports.

### 4.3 Technical Requirements

- **TR-P-01:** Payroll calculations must be handled by a dedicated `PayrollCalculationService` class, not in controllers.
- **TR-P-02:** All monetary values must be stored as `decimal(12,2)` in the database. Never use `float`.
- **TR-P-03:** Payroll records must be immutable once status is `approved`. Implement model-level protection.
- **TR-P-04:** PDF generation must use the existing DomPDF (Barryvdh) integration.

### 4.4 Database Tables

| Table | Key Columns |
|---|---|
| `salary_components` | `id`, `name`, `type` (earning/deduction), `calculation_type`, `value`, `formula` |
| `salary_structures` | `id`, `name`, `applicable_to` (staff_id / designation_id / bps_grade) |
| `salary_structure_components` | `id`, `structure_id`, `component_id`, `override_value` |
| `payroll_runs` | `id`, `month`, `year`, `status`, `approved_by`, `paid_at`, `created_by` |
| `payroll_records` | `id`, `run_id`, `staff_id`, `basic_pay`, `gross_pay`, `total_deductions`, `net_pay` |
| `payroll_record_items` | `id`, `record_id`, `component_id`, `amount` |
| `staff_bank_details` | `id`, `staff_id`, `bank_name`, `branch`, `account_number`, `iban` |

---

## 5. Module 4 — Parent Portal

**Priority:** P2 — High  
**Estimated Complexity:** Medium  
**Status:** Implemented
**Dependencies:** Student Information System, Fee Module, Attendance Module, Examination Module, LMS Module, Notification System (Module 1)

### 5.1 Module Overview
The Parent Portal provides a read-only, secure web interface for parents and guardians to monitor their child's academic progress, attendance, fee status, and announcements. It requires no separate application — it is an additional role within the existing RBAC and a dedicated view layer in the existing application.

### 5.2 Functional Requirements

#### 5.2.1 Parent Account Management
- **FR-PP-01:** Admin must be able to create parent accounts linked to one or more student records (a single parent may have multiple children enrolled).
- **FR-PP-02:** Parent account creation must allow entry of: name, CNIC, relationship to student (Father/Mother/Guardian), mobile number, and email.
- **FR-PP-03:** If a student's father record already exists in the system (from SIS), the system must offer to link the existing record to a parent account to avoid duplication.
- **FR-PP-04:** Parent accounts must be auto-linked to the correct student(s) via student ID at creation.
- **FR-PP-05:** Parents must be able to reset their own password via their registered mobile number (OTP) or email.
- **FR-PP-06:** Admin must be able to activate, deactivate, or delete parent accounts.

#### 5.2.2 Parent Dashboard
- **FR-PP-07:** After login, parent must see a dashboard with summary cards for each linked child showing: current class/program, overall attendance percentage, pending fee amount, and last result summary.
- **FR-PP-08:** If a parent has multiple children, they must be able to switch between child profiles from the dashboard.

#### 5.2.3 Attendance View
- **FR-PP-09:** Parent must be able to view their child's monthly attendance summary per subject (present, absent, late).
- **FR-PP-10:** Parent must be able to view a calendar-style attendance view showing daily status.
- **FR-PP-11:** Low attendance warning (below threshold) must be prominently displayed.

#### 5.2.4 Results & Academic Performance
- **FR-PP-12:** Parent must be able to view their child's test results (subject-wise marks for each test).
- **FR-PP-13:** Parent must be able to view final exam results once published by admin.
- **FR-PP-14:** Result data must not be editable from the parent portal (read-only strictly enforced).

#### 5.2.5 Fee Status
- **FR-PP-15:** Parent must be able to view all fee vouchers for their child with status (pending, paid, cancelled).
- **FR-PP-16:** Parent must be able to view and download individual fee vouchers as PDF.
- **FR-PP-17:** Once online payment is integrated (Module 5), parent must be able to initiate payment for pending vouchers.

#### 5.2.6 Assignments & LMS
- **FR-PP-18:** Parent must be able to view a list of assignments given to their child, including due dates and submission status (submitted/not submitted).
- **FR-PP-19:** Parent must not be able to view assignment content or grades in detail — only submission status.

#### 5.2.7 Announcements
- **FR-PP-20:** Parents must receive institution-wide announcements sent via the Notification System (Module 1).
- **FR-PP-21:** Announcements must be viewable in a dedicated section of the parent portal.

### 5.3 Technical Requirements

- **TR-PP-01:** Parent role must be added to the existing RBAC system as a new role `parent` with strictly read-only permissions on student data.
- **TR-PP-02:** All data queries in the parent portal must be scoped to the logged-in parent's linked students only. No cross-student data leakage is acceptable.
- **TR-PP-03:** All parent portal routes must be under a `/parent` route group with a dedicated middleware `EnsureIsParent`.
- **TR-PP-04:** The parent portal must be mobile-responsive as parents are likely to use it on smartphones.

### 5.4 Database Tables

| Table | Key Columns |
|---|---|
| `parents` | `id`, `user_id`, `name`, `cnic`, `mobile`, `email`, `relationship` |
| `parent_student` | `id`, `parent_id`, `student_id` (pivot table, one parent → many students) |

---

## 6. Module 5 — Online Payment Integration

**Priority:** P2 — High  
**Estimated Complexity:** High  
**Status:** Implemented
**Dependencies:** Fee & Voucher Management Module

### 6.1 Module Overview
This module integrates the existing fee voucher system with Pakistani online payment gateways, enabling students and parents to pay fee vouchers directly through the portal without visiting a bank. It also provides a manual payment confirmation fallback.

### 6.2 Functional Requirements

#### 6.2.1 Gateway Integration
- **FR-OP-01:** System must integrate with at least one of the following payment gateways: JazzCash (Mobilink Microfinance), EasyPaisa (Telenor Microfinance), 1Link (bank interoperability), HBL Internet Banking (direct API).
- **FR-OP-02:** Gateway selection must be configurable from admin settings without code changes (gateway name, merchant ID, API keys, callback URLs).
- **FR-OP-03:** System must support multiple gateways simultaneously, allowing the student/parent to choose at checkout.
- **FR-OP-04:** All payment API credentials must be stored encrypted in the database.

#### 6.2.2 Payment Flow
- **FR-OP-05:** Student or Parent must be able to select one or more pending fee vouchers and initiate payment.
- **FR-OP-06:** System must display a payment summary (selected voucher amounts, any gateway surcharge, total payable) before redirecting to the payment gateway.
- **FR-OP-07:** On successful payment, gateway must call back the system's webhook URL, which must: verify the payment signature/hash, mark the voucher as paid, record the transaction ID, gateway name, and payment timestamp.
- **FR-OP-08:** On failed or cancelled payment, the voucher must remain in pending status and the student must be redirected back with an error message.
- **FR-OP-09:** System must handle duplicate callback calls idempotently (same transaction ID must not mark a voucher paid twice).
- **FR-OP-10:** Admin must be able to manually verify and confirm a payment if the callback fails but payment was successful at the gateway (manual reconciliation).

#### 6.2.3 Payment Receipts
- **FR-OP-11:** On successful payment, system must generate and send an SMS and email confirmation with a downloadable PDF receipt.
- **FR-OP-12:** The PDF receipt must include: student name, class, voucher number, payment date, amount, transaction ID, and gateway name.
- **FR-OP-13:** Students and parents must be able to download past payment receipts from their portal.

#### 6.2.4 Transaction Reporting
- **FR-OP-14:** Admin must have a dedicated transaction log showing all payment attempts (successful and failed) with: student, voucher, amount, gateway, status, transaction ID, and timestamp.
- **FR-OP-15:** Daily and monthly collection summary reports must include both manual (cash/bank) and online payment channels separately.
- **FR-OP-16:** Admin must be able to reconcile gateway-reported totals against system totals and flag discrepancies.

### 6.3 Technical Requirements

- **TR-OP-01:** Payment gateway communication must be handled via a dedicated `PaymentGatewayService` with a gateway-agnostic interface, allowing new gateways to be added as driver classes.
- **TR-OP-02:** Webhook endpoints must not require CSRF verification but must validate the gateway's HMAC signature before processing.
- **TR-OP-03:** All payment transactions must be logged before and after gateway communication (request payload and response payload) for debugging.
- **TR-OP-04:** Payment processing must be wrapped in database transactions to prevent partial state updates.

### 6.4 Database Tables

| Table | Key Columns |
|---|---|
| `payment_gateways` | `id`, `name`, `driver`, `merchant_id`, `api_key_encrypted`, `is_active` |
| `payment_transactions` | `id`, `voucher_id`, `gateway_id`, `amount`, `surcharge`, `total_paid`, `status`, `transaction_id`, `gateway_ref`, `initiated_at`, `completed_at` |
| `payment_gateway_logs` | `id`, `transaction_id`, `direction` (request/response), `payload`, `created_at` |

---

## 7. Module 6 — Bulk Excel Import

**Priority:** P2 — High  
**Estimated Complexity:** Medium  
**Status:** Implemented
**Dependencies:** Student Information System, Staff & HR Module, Examination Module (Results)

### 7.1 Module Overview
The Bulk Import module provides Excel/CSV-based mass data entry for students, staff, historical results, and attendance. It is especially critical for initial data migration from legacy paper-based or older software systems, and for ongoing batch operations such as importing a new batch of students.

### 7.2 Functional Requirements

#### 7.2.1 Import Types

| # | Import Type | Target Module | Key Fields |
|---|---|---|---|
| I-01 | Student Bulk Import | SIS | Name, Father Name, DOB, CNIC, Mobile, Class, Program, Section, Admission Year, Subject selections |
| I-02 | Staff Bulk Import | HR | Name, CNIC, Designation, BPS Grade, Department, Joining Date, Qualification, Mobile |
| I-03 | Test Results Import | Examination | Student roll no, Subject, Test name, Marks obtained, Total marks |
| I-04 | Final Results Import | Examination | Student roll no, Subject, Part marks, Total marks |
| I-05 | Attendance Import | Attendance | Student ID, Date, Status (P/A/L) — for historical data only |
| I-06 | Fee Voucher Bulk Import | Fee Module | Student ID, Fee head, Amount, Month, Year |

#### 7.2.2 Import Process
- **FR-I-01:** System must provide a downloadable Excel template for each import type with correct headers and a sample data row.
- **FR-I-02:** Templates must include a second sheet with dropdown validations and instructions.
- **FR-I-03:** Users must be able to upload an Excel or CSV file and trigger a validation preview before committing the import.
- **FR-I-04:** Validation preview must show: total rows detected, rows that will pass, rows with errors (with row number and error description), and rows that will update existing records vs create new ones.
- **FR-I-05:** Users must be able to proceed with valid rows only (skipping errors) or cancel the entire import.
- **FR-I-06:** On confirmation, the import must run as a background queue job and the user must be notified (in-app + email) when it is complete.
- **FR-I-07:** Import must support both insert (new records) and upsert (update existing records if a matching key is found, e.g., CNIC for staff, roll number for students).
- **FR-I-08:** Duplicate detection must be based on configurable unique keys (e.g., CNIC, form number, roll number).

#### 7.2.3 Validation Rules

- **FR-I-09:** All required fields must be validated as non-empty.
- **FR-I-10:** Date fields must be validated for format (DD/MM/YYYY or YYYY-MM-DD) and logical validity (e.g., date of birth cannot be in the future).
- **FR-I-11:** Numeric fields (marks, amounts) must be validated as numbers within allowed ranges.
- **FR-I-12:** Foreign key references (e.g., subject name, designation name) must be validated against existing records in the system.
- **FR-I-13:** CNIC fields must be validated for Pakistani CNIC format (13 digits, no dashes).
- **FR-I-14:** Duplicate rows within the same import file must be detected and flagged.

#### 7.2.4 Import Logs
- **FR-I-15:** Every import attempt (successful or failed) must be logged with: import type, file name, total rows, success count, error count, imported by, and timestamp.
- **FR-I-16:** Import logs must be viewable and filterable by admin.
- **FR-I-17:** A detailed error report for each failed import must be downloadable as an Excel file with original row data + error description column.

### 7.3 Technical Requirements

- **TR-I-01:** Use `Maatwebsite/Laravel-Excel` (already in the project) for reading Excel files and generating templates.
- **TR-I-02:** All import jobs must implement Laravel's `ShouldQueue` interface and run on the `imports` queue.
- **TR-I-03:** Chunk imports in batches of 100–500 rows using `WithChunkReading` to prevent memory exhaustion on large files.
- **TR-I-04:** Maximum file size: 10 MB. Maximum rows per file: 5,000. These limits must be validated before queueing.

### 7.4 Database Tables

| Table | Key Columns |
|---|---|
| `import_logs` | `id`, `type`, `filename`, `total_rows`, `success_count`, `error_count`, `status`, `imported_by`, `created_at` |
| `import_errors` | `id`, `log_id`, `row_number`, `row_data` (JSON), `error_message` |

---

## 8. Module 7 — Transport & Hostel Management

**Priority:** P3 — Medium  
**Estimated Complexity:** Medium  
**Status:** Implemented
**Dependencies:** Student Information System, Fee & Voucher Management Module

### 8.1 Module Overview
This module handles the management of college transport (bus routes, drivers, vehicle records) and hostel facilities (rooms, allotments, warden assignments, hostel fee). Both sub-modules integrate with the existing fee system to auto-generate applicable fee vouchers.

---

### 8.2 Transport Management

#### 8.2.1 Functional Requirements

- **FR-T-01:** Admin must be able to create and manage bus routes with: route name, start point, end point, stops (ordered list with pick-up times), assigned vehicle, assigned driver.
- **FR-T-02:** Admin must be able to maintain a vehicle registry with: vehicle registration number, type (bus/van/coaster), seating capacity, ownership (owned/rented), purchase/contract date.
- **FR-T-03:** Admin must be able to maintain a driver registry linked to the Staff module or as a standalone record, including: name, CNIC, license number, license expiry date, mobile number.
- **FR-T-04:** Admin must be able to assign students to routes (with specific stop selection).
- **FR-T-05:** System must prevent assigning more students to a route than the vehicle's seating capacity.
- **FR-T-06:** Transport fee must be configurable per route (different routes may have different fees) and integrated with the existing `FeeHead` system.
- **FR-T-07:** When a student is assigned to a route, the system must automatically generate a transport fee voucher for the current month (with option to set recurring for the academic year).
- **FR-T-08:** Admin must be able to generate route-wise student lists for printable attendance sheets for the driver.
- **FR-T-09:** Driver license expiry alerts must be generated 30 days before expiry (via Notification System Module 1).
- **FR-T-10:** Admin must be able to view and export transport fee collection status per route.

#### 8.2.2 Transport Database Tables

| Table | Key Columns |
|---|---|
| `transport_routes` | `id`, `name`, `start_point`, `end_point`, `vehicle_id`, `driver_id`, `monthly_fee` |
| `route_stops` | `id`, `route_id`, `stop_name`, `pickup_time`, `order` |
| `vehicles` | `id`, `registration_no`, `type`, `capacity`, `ownership`, `status` |
| `drivers` | `id`, `staff_id` (nullable), `name`, `cnic`, `license_no`, `license_expiry`, `mobile` |
| `student_transport` | `id`, `student_id`, `route_id`, `stop_id`, `assigned_from`, `assigned_to` |

---

### 8.3 Hostel Management

#### 8.3.1 Functional Requirements

- **FR-H-01:** Admin must be able to create and manage hostels with: name, type (boys/girls), total capacity, address.
- **FR-H-02:** Admin must be able to manage rooms within each hostel with: room number, floor, type (single/double/triple/dormitory), capacity, monthly rent.
- **FR-H-03:** Admin must be able to assign a warden to each hostel. Wardens must be linked to existing staff records.
- **FR-H-04:** Admin must be able to allot rooms to students with: allotment date, expected vacate date, and bed number (for shared rooms).
- **FR-H-05:** System must prevent over-allotment beyond room capacity.
- **FR-H-06:** Admin must be able to record room vacations (student left hostel) with a vacate date and remarks.
- **FR-H-07:** Hostel fee must be automatically generated as a fee voucher (linked to `FeeHead`) when a room is allotted to a student.
- **FR-H-08:** Warden must have a dedicated panel to view residents in their hostel, mark daily/weekly roll call, and log disciplinary incidents.
- **FR-H-09:** Admin must be able to view hostel occupancy reports showing: total rooms, occupied, vacant, and student list per room.
- **FR-H-10:** Admin must be able to log maintenance requests and repairs against specific rooms with status tracking.
- **FR-H-11:** Visitor log: Warden must be able to record student visitors with name, CNIC, relation, and visit date/time.

#### 8.3.2 Hostel Database Tables

| Table | Key Columns |
|---|---|
| `hostels` | `id`, `name`, `type` (boys/girls), `capacity`, `warden_id` |
| `hostel_rooms` | `id`, `hostel_id`, `room_number`, `floor`, `type`, `capacity`, `monthly_rent`, `status` |
| `hostel_allotments` | `id`, `student_id`, `room_id`, `bed_number`, `allotted_from`, `allotted_to`, `status` |
| `hostel_visitor_log` | `id`, `hostel_id`, `student_id`, `visitor_name`, `visitor_cnic`, `relation`, `visit_in`, `visit_out` |
| `hostel_maintenance` | `id`, `room_id`, `description`, `reported_by`, `status`, `resolved_at` |

---

## 9. Module 8 — Alumni & Graduation Module

**Priority:** P3 — Medium  
**Estimated Complexity:** Medium  
**Status:** Implemented
**Dependencies:** Student Information System, Examination Module, Fee & Voucher Management

### 9.1 Module Overview
The Alumni & Graduation module completes the student lifecycle by formalizing the transition from active student to alumnus. It handles graduation eligibility checks, degree/certificate issuance, alumni record management, and an alumni-facing portal.

### 9.2 Functional Requirements

#### 9.2.1 Graduation Processing
- **FR-AL-01:** Admin must be able to initiate a graduation process for a batch (by program and academic year) at the end of the academic cycle.
- **FR-AL-02:** System must auto-check graduation eligibility for each student based on configurable criteria: minimum attendance percentage, no outstanding fee, all required results entered and passing.
- **FR-AL-03:** A graduation eligibility report must be generated showing: eligible students (green), conditionally eligible (yellow — minor issues), and ineligible (red — with reason for each).
- **FR-AL-04:** Admin must be able to override the eligibility status of individual students with a reason (for special cases approved by Principal).
- **FR-AL-05:** On confirming graduation for eligible students, the system must: change student status to `graduated`, record the graduation date, and remove them from active class lists without deleting their records.

#### 9.2.2 Certificate & Document Issuance
- **FR-AL-06:** System must be able to generate the following documents in PDF: Degree/Passing Certificate, Detailed Marks Certificate (DMC), Provisional Certificate, Character Certificate, Bonafide Certificate, Migration Certificate.
- **FR-AL-07:** Each certificate type must have a configurable template (header, body text with dynamic fields, principal signature line, institution seal).
- **FR-AL-08:** Issued certificates must be logged with: certificate type, issue date, issued by, and a unique certificate number.
- **FR-AL-09:** Alumni must be able to request certificates from their portal (if portal access is maintained post-graduation).
- **FR-AL-10:** Admin must be able to track certificate requests with statuses: requested, under review, issued, dispatched.

#### 9.2.3 Alumni Records & Portal
- **FR-AL-11:** Upon graduation, a student record is automatically converted into an alumni record retaining all academic history.
- **FR-AL-12:** Alumni portal (read-only) must allow alumni to: view and download their academic records and issued certificates, update their current contact information (mobile, email, current city, employer), and view institution news/announcements.
- **FR-AL-13:** Admin must be able to search and filter the alumni directory by: graduation year, program, city, employer, and contact status.
- **FR-AL-14:** Admin must be able to export the alumni directory to Excel.
- **FR-AL-15:** Alumni must be able to request a verification letter (confirming their enrollment and graduation) for employers or other institutions.

#### 9.2.4 Student Verification (Public)
- **FR-AL-16:** The existing public student verification portal must be extended to verify alumni status (graduated, year, program) in addition to currently enrolled students.

### 9.3 Database Tables

| Table | Key Columns |
|---|---|
| `graduation_batches` | `id`, `program`, `academic_year`, `status`, `processed_by`, `created_at` |
| `graduation_records` | `id`, `batch_id`, `student_id`, `graduation_date`, `status`, `override_reason` |
| `certificate_templates` | `id`, `type`, `header_html`, `body_html`, `is_active` |
| `issued_certificates` | `id`, `student_id`, `type`, `certificate_number`, `issued_date`, `issued_by`, `status` |
| `alumni_profiles` | `id`, `student_id`, `current_city`, `employer`, `email`, `mobile`, `updated_at` |
| `certificate_requests` | `id`, `student_id`, `type`, `requested_at`, `status`, `processed_by` |

---

## 10. Module 9 — Asset & Budget Management

**Priority:** P4 — Low  
**Estimated Complexity:** Medium  
**Status:** Implemented
**Dependencies:** Staff & HR Module (for asset assignment), User & Role Module

### 10.1 Module Overview
This module enables the institution to track physical assets (computers, lab equipment, furniture, vehicles), manage departmental budgets, record expenditures, and process procurement requests. It provides financial oversight beyond student fees and staff salaries.

---

### 10.2 Asset Management

#### 10.2.1 Functional Requirements

- **FR-A-01:** Admin must be able to register assets with: name, category, sub-category, brand, model, serial number, purchase date, purchase price, vendor, warranty expiry, department/location, condition (new/good/fair/poor/scrap).
- **FR-A-02:** Asset categories must be configurable. Default categories: IT Equipment, Furniture, Lab Equipment, Audio/Visual, Vehicles, Books (non-library).
- **FR-A-03:** System must generate a unique Asset ID / barcode for each registered asset.
- **FR-A-04:** Admin must be able to assign assets to staff members or departments and record transfer history.
- **FR-A-05:** Admin must be able to record asset status changes: in-use, under maintenance, disposed, stolen, lost.
- **FR-A-06:** System must calculate asset depreciation using straight-line or reducing balance method (configurable per category).
- **FR-A-07:** Admin must be able to log maintenance records against assets with date, description, cost, and vendor.
- **FR-A-08:** Warranty expiry alerts must be sent 30 days before expiry (via Notification System Module 1).
- **FR-A-09:** Admin must be able to generate: asset register report, department-wise asset list, depreciation report, and scrap/disposal report.

#### 10.2.2 Asset Database Tables

| Table | Key Columns |
|---|---|
| `asset_categories` | `id`, `name`, `parent_id`, `depreciation_method`, `depreciation_rate` |
| `assets` | `id`, `asset_id_no`, `name`, `category_id`, `brand`, `model`, `serial_no`, `purchase_date`, `purchase_price`, `warranty_expiry`, `condition`, `status`, `department_id` |
| `asset_assignments` | `id`, `asset_id`, `assigned_to_type` (staff/department), `assigned_to_id`, `assigned_date`, `returned_date` |
| `asset_maintenance` | `id`, `asset_id`, `date`, `description`, `cost`, `vendor`, `status` |

---

### 10.3 Budget & Expense Management

#### 10.3.1 Functional Requirements

- **FR-B-01:** Admin must be able to create an annual budget for each department/cost center with line items.
- **FR-B-02:** Budget must be approved by Principal before it becomes active.
- **FR-B-03:** Admin must be able to record actual expenditures against budget line items with: date, amount, description, vendor, payment mode, and supporting document upload.
- **FR-B-04:** System must show budget utilization in real-time: budgeted vs actual vs remaining for each line item.
- **FR-B-05:** Admin must be able to record petty cash transactions (small daily expenses).
- **FR-B-06:** Staff members with department head role must be able to view their own department's budget and expenditures (read-only).
- **FR-B-07:** Admin must be able to manage a procurement request workflow: staff submits purchase request → HOD approves → Admin approves → Purchase order generated.
- **FR-B-08:** Approved purchase orders must be printable as formal PDF documents with institution letterhead.
- **FR-B-09:** Budget vs actual variance reports must be available monthly and annually.

#### 10.3.2 Budget Database Tables

| Table | Key Columns |
|---|---|
| `budgets` | `id`, `department_id`, `fiscal_year`, `total_amount`, `status`, `approved_by` |
| `budget_line_items` | `id`, `budget_id`, `category`, `description`, `allocated_amount` |
| `expenditures` | `id`, `line_item_id`, `amount`, `description`, `vendor`, `payment_mode`, `date`, `document`, `recorded_by` |
| `procurement_requests` | `id`, `requested_by`, `department_id`, `item_description`, `quantity`, `estimated_cost`, `status`, `approved_by` |
| `purchase_orders` | `id`, `request_id`, `vendor_name`, `items` (JSON), `total_amount`, `issued_date`, `status` |

---

## 11. Non-Functional Requirements (All Modules)

### 11.1 Performance
- **NFR-01:** Any page load (list views, dashboards) must return within 3 seconds under normal load (up to 200 concurrent users).
- **NFR-02:** Bulk operations (payroll run, bulk import, PDF batch generation) must be queued and processed asynchronously. No HTTP request should timeout waiting for bulk operations.
- **NFR-03:** Database queries in list views must use eager loading and pagination (maximum 50 records per page by default).

### 11.2 Security
- **NFR-04:** All new modules must enforce RBAC using the existing `Permission` middleware. No route must be accessible without an authenticated session.
- **NFR-05:** All form submissions must include CSRF tokens.
- **NFR-06:** All file uploads must be validated for MIME type and maximum file size before storage.
- **NFR-07:** Sensitive configuration (API keys, gateway credentials) must be stored encrypted using Laravel's `Crypt` facade, never in plaintext.
- **NFR-08:** Payroll and financial data must only be accessible to users with explicitly granted permissions; not inherited by default from parent roles.

### 11.3 Reliability & Data Integrity
- **NFR-09:** All new models must implement soft deletes (`SoftDeletes` trait) to preserve data for audit purposes.
- **NFR-10:** All new tables must include `created_by`, `updated_by`, and `deleted_by` audit columns.
- **NFR-11:** All critical operations (payroll processing, payment confirmation, graduation) must be wrapped in database transactions.
- **NFR-12:** Automated database backup must be extended to cover all new tables.

### 11.4 Usability
- **NFR-13:** All new modules must follow the existing UI design language (Bootstrap 5, existing admin panel layout, consistent button styles and table formats).
- **NFR-14:** All list views must support search, filter, and pagination.
- **NFR-15:** All list views must have a relevant CSV/Excel export button.
- **NFR-16:** All forms must provide inline validation feedback.

### 11.5 Maintainability
- **NFR-17:** Each new module must have its own Service class to encapsulate business logic (no fat controllers).
- **NFR-18:** All new database tables must include proper foreign key constraints with appropriate `ON DELETE` behavior.
- **NFR-19:** All configurable parameters (thresholds, defaults, toggles) must be stored in the `InstituteSetting` table (key-value) or a dedicated module settings table, not hardcoded.

---

## 12. Database Overview (All Modules)

The following table summarizes all new database tables introduced by the nine modules:

| Module | New Tables | Estimated Columns |
|---|---|---|
| Notification System | 5 tables | 45+ |
| Staff Leave & Attendance | 6 tables | 50+ |
| Payroll & Salary | 7 tables | 60+ |
| Parent Portal | 2 tables | 12 |
| Online Payment Integration | 3 tables | 30+ |
| Bulk Excel Import | 2 tables | 12 |
| Transport & Hostel | 9 tables | 70+ |
| Alumni & Graduation | 6 tables | 40+ |
| Asset & Budget Management | 9 tables | 70+ |
| **Total New Tables** | **49 tables** | **389+ columns** |

Combined with the existing 75+ tables, the extended CMS will operate on **124+ database tables** covering the full institutional management lifecycle.

---

*End of SRS Document*

---
*Document Version: 1.0 | College Management System | © 2024–2026*
