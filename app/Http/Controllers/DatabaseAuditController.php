<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseAuditController extends Controller
{
    /**
     * Display the database audit dashboard.
     */
    public function index()
    {
        $categories = [
            'invalid_sts_plus' => [
                'title' => 'Invalid Combined Section Strings (+)',
                'table' => 'student_teacher_subjects',
                'description' => 'Records where attendance section contains concatenated text like "M1 + E1 + M2".',
                'count' => DB::table('student_teacher_subjects')->where('attendance_section', 'like', '%+%')->count(),
                'samples' => DB::table('student_teacher_subjects')->where('attendance_section', 'like', '%+%')->limit(5)->get(['id', 'student_id', 'staff_id', 'subject_id', 'attendance_section']),
            ],
            'sts_no_student' => [
                'title' => 'Orphaned Teacher Assignments (No Student)',
                'table' => 'student_teacher_subjects',
                'description' => 'Teacher assignment records pointing to a deleted or non-existent student.',
                'count' => DB::table('student_teacher_subjects as sts')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'sts.student_id');
                })->count(),
                'samples' => DB::table('student_teacher_subjects as sts')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'sts.student_id');
                })->limit(5)->get(['sts.id', 'sts.student_id', 'sts.staff_id', 'sts.subject_id']),
            ],
            'sss_no_student' => [
                'title' => 'Orphaned Student Section Allocations (No Student)',
                'table' => 'student_subject_sections',
                'description' => 'Section allocation records pointing to a deleted or non-existent student.',
                'count' => DB::table('student_subject_sections as sss')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'sss.student_id');
                })->count(),
                'samples' => DB::table('student_subject_sections as sss')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'sss.student_id');
                })->limit(5)->get(['sss.id', 'sss.student_id', 'sss.subject_id', 'sss.section']),
            ],
            'ses_no_student' => [
                'title' => 'Orphaned Elective Subject Enrollments (No Student)',
                'table' => 'student_elective_subjects',
                'description' => 'Elective subject enrollments pointing to a deleted or non-existent student.',
                'count' => DB::table('student_elective_subjects as ses')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'ses.student_id');
                })->count(),
                'samples' => DB::table('student_elective_subjects as ses')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'ses.student_id');
                })->limit(5)->get(['ses.id', 'ses.student_id', 'ses.subject_id']),
            ],
            'ses_no_subject' => [
                'title' => 'Orphaned Elective Subject Enrollments (No Subject)',
                'table' => 'student_elective_subjects',
                'description' => 'Elective subject enrollments pointing to a deleted or non-existent subject.',
                'count' => DB::table('student_elective_subjects as ses')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects as sub')->whereColumn('sub.id', 'ses.subject_id');
                })->count(),
                'samples' => DB::table('student_elective_subjects as ses')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects as sub')->whereColumn('sub.id', 'ses.subject_id');
                })->limit(5)->get(['ses.id', 'ses.student_id', 'ses.subject_id']),
            ],
            'tt_no_subject' => [
                'title' => 'Orphaned Timetable Entries (No Subject)',
                'table' => 'timetable_entries',
                'description' => 'Timetable entries assigned to a deleted or non-existent subject.',
                'count' => DB::table('timetable_entries as te')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects as sub')->whereColumn('sub.id', 'te.subject_id');
                })->count(),
                'samples' => DB::table('timetable_entries as te')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects as sub')->whereColumn('sub.id', 'te.subject_id');
                })->limit(5)->get(['te.id', 'te.subject_id', 'te.section', 'te.day_of_week']),
            ],
            'tt_no_staff' => [
                'title' => 'Orphaned Timetable Entries (No Teacher)',
                'table' => 'timetable_entries',
                'description' => 'Timetable entries assigned to a deleted or non-existent teacher.',
                'count' => DB::table('timetable_entries as te')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('staff as st')->whereColumn('st.id', 'te.staff_id');
                })->count(),
                'samples' => DB::table('timetable_entries as te')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('staff as st')->whereColumn('st.id', 'te.staff_id');
                })->limit(5)->get(['te.id', 'te.staff_id', 'te.section', 'te.day_of_week']),
            ],
            'tt_no_room' => [
                'title' => 'Invalid Room IDs in Timetable Entries',
                'table' => 'timetable_entries',
                'description' => 'Timetable entries pointing to a deleted or non-existent room ID.',
                'count' => DB::table('timetable_entries as te')->whereNotNull('te.room_id')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('rooms as r')->whereColumn('r.id', 'te.room_id');
                })->count(),
                'samples' => DB::table('timetable_entries as te')->whereNotNull('te.room_id')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('rooms as r')->whereColumn('r.id', 'te.room_id');
                })->limit(5)->get(['te.id', 'te.room_id', 'te.section']),
            ],
            'att_no_student' => [
                'title' => 'Orphaned Attendance Records (No Student)',
                'table' => 'attendances',
                'description' => 'Attendance records logged for a deleted or non-existent student.',
                'count' => DB::table('attendances as a')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'a.student_id');
                })->count(),
                'samples' => DB::table('attendances as a')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'a.student_id');
                })->limit(5)->get(['a.id', 'a.student_id', 'a.attendance_date']),
            ],
            'results_no_student' => [
                'title' => 'Orphaned Exam Results (No Student)',
                'table' => 'results',
                'description' => 'Marks or exam results belonging to a deleted or non-existent student.',
                'count' => DB::table('results as r')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'r.student_id');
                })->count(),
                'samples' => DB::table('results as r')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'r.student_id');
                })->limit(5)->get(['r.id', 'r.student_id', 'r.subject_id', 'r.obtained_theory']),
            ],
            'results_no_subject' => [
                'title' => 'Orphaned Exam Results (No Subject)',
                'table' => 'results',
                'description' => 'Marks or exam results belonging to a deleted or non-existent subject.',
                'count' => DB::table('results as r')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects as sub')->whereColumn('sub.id', 'r.subject_id');
                })->count(),
                'samples' => DB::table('results as r')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects as sub')->whereColumn('sub.id', 'r.subject_id');
                })->limit(5)->get(['r.id', 'r.student_id', 'r.subject_id', 'r.obtained_theory']),
            ],
            'fee_vouchers_no_student' => [
                'title' => 'Orphaned Fee Vouchers (No Student)',
                'table' => 'fee_vouchers',
                'description' => 'Fee vouchers issued to a deleted or non-existent student.',
                'count' => DB::table('fee_vouchers as fv')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'fv.student_id');
                })->count(),
                'samples' => DB::table('fee_vouchers as fv')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'fv.student_id');
                })->limit(5)->get(['fv.id', 'fv.voucher_number', 'fv.student_id', 'fv.amount']),
            ],
            'fee_items_no_voucher' => [
                'title' => 'Orphaned Fee Voucher Items (No Voucher)',
                'table' => 'fee_voucher_items',
                'description' => 'Fee line items referencing a deleted or non-existent fee voucher.',
                'count' => DB::table('fee_voucher_items as fvi')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('fee_vouchers as fv')->whereColumn('fv.id', 'fvi.fee_voucher_id');
                })->count(),
                'samples' => DB::table('fee_voucher_items as fvi')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('fee_vouchers as fv')->whereColumn('fv.id', 'fvi.fee_voucher_id');
                })->limit(5)->get(['fvi.id', 'fvi.fee_voucher_id', 'fvi.amount']),
            ],
            'seating_details_no_plan' => [
                'title' => 'Orphaned Exam Seating Allocation (No Seating Plan)',
                'table' => 'seating_plan_details',
                'description' => 'Seat allocations referencing a deleted or non-existent seating plan.',
                'count' => DB::table('seating_plan_details as spd')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('seating_plans as sp')->whereColumn('sp.id', 'spd.seating_plan_id');
                })->count(),
                'samples' => DB::table('seating_plan_details as spd')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('seating_plans as sp')->whereColumn('sp.id', 'spd.seating_plan_id');
                })->limit(5)->get(['spd.id', 'spd.seating_plan_id', 'spd.student_id', 'spd.seat_no']),
            ],
            'user_roles_no_user' => [
                'title' => 'Orphaned User Role Grants (No User)',
                'table' => 'user_roles',
                'description' => 'Role mapping entries referencing a deleted or non-existent user ID.',
                'count' => DB::table('user_roles as ur')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('users as u')->whereColumn('u.id', 'ur.user_id');
                })->count(),
                'samples' => DB::table('user_roles as ur')->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('users as u')->whereColumn('u.id', 'ur.user_id');
                })->limit(5)->get(['ur.id', 'ur.user_id', 'ur.role_id']),
            ],
        ];

        $totalIssues = array_sum(array_column($categories, 'count'));

        return view('admin.database.audit', compact('categories', 'totalIssues'));
    }

    /**
     * Clean a specific category of orphan records.
     */
    public function cleanCategory(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
        ]);

        $category = $request->input('category');
        $cleanedCount = 0;

        switch ($category) {
            case 'invalid_sts_plus':
                $cleanedCount = DB::table('student_teacher_subjects')
                    ->where('attendance_section', 'like', '%+%')
                    ->update(['attendance_section' => null]);
                $message = "Fixed {$cleanedCount} invalid '+' section strings (set to NULL).";
                break;

            case 'sts_no_student':
                $cleanedCount = DB::table('student_teacher_subjects')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'student_teacher_subjects.student_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned teacher assignment records (No Student).";
                break;

            case 'sss_no_student':
                $cleanedCount = DB::table('student_subject_sections')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'student_subject_sections.student_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned student section records (No Student).";
                break;

            case 'ses_no_student':
                $cleanedCount = DB::table('student_elective_subjects')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'student_elective_subjects.student_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned elective subject enrollments (No Student).";
                break;

            case 'ses_no_subject':
                $cleanedCount = DB::table('student_elective_subjects')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('subjects')->whereColumn('subjects.id', 'student_elective_subjects.subject_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned elective subject enrollments (No Subject).";
                break;

            case 'tt_no_subject':
                $cleanedCount = DB::table('timetable_entries')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('subjects')->whereColumn('subjects.id', 'timetable_entries.subject_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned timetable entries (No Subject).";
                break;

            case 'tt_no_staff':
                $cleanedCount = DB::table('timetable_entries')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('staff')->whereColumn('staff.id', 'timetable_entries.staff_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned timetable entries (No Teacher).";
                break;

            case 'tt_no_room':
                $cleanedCount = DB::table('timetable_entries')
                    ->whereNotNull('room_id')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('rooms')->whereColumn('rooms.id', 'timetable_entries.room_id');
                    })->update(['room_id' => null]);
                $message = "Reset {$cleanedCount} timetable entries with invalid Room ID to NULL.";
                break;

            case 'att_no_student':
                $cleanedCount = DB::table('attendances')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'attendances.student_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned attendance records.";
                break;

            case 'results_no_student':
                $cleanedCount = DB::table('results')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'results.student_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned exam results (No Student).";
                break;

            case 'results_no_subject':
                $cleanedCount = DB::table('results')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('subjects')->whereColumn('subjects.id', 'results.subject_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned exam results (No Subject).";
                break;

            case 'fee_vouchers_no_student':
                $cleanedCount = DB::table('fee_vouchers')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'fee_vouchers.student_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned fee vouchers (No Student).";
                break;

            case 'fee_items_no_voucher':
                $cleanedCount = DB::table('fee_voucher_items')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('fee_vouchers')->whereColumn('fee_vouchers.id', 'fee_voucher_items.fee_voucher_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned fee voucher items (No Voucher).";
                break;

            case 'seating_details_no_plan':
                $cleanedCount = DB::table('seating_plan_details')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('seating_plans')->whereColumn('seating_plans.id', 'seating_plan_details.seating_plan_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned seating plan allocation items (No Seating Plan).";
                break;

            case 'user_roles_no_user':
                $cleanedCount = DB::table('user_roles')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))->from('users')->whereColumn('users.id', 'user_roles.user_id');
                    })->delete();
                $message = "Deleted {$cleanedCount} orphaned user role assignment items (No User).";
                break;

            default:
                return back()->with('error', 'Invalid category specified.');
        }

        return redirect()->route('admin.database.audit.index')->with('success', $message);
    }

    /**
     * Clean all detected orphan records across all categories.
     */
    public function cleanAll()
    {
        $totalCleaned = 0;

        DB::transaction(function () use (&$totalCleaned) {
            // 1. Invalid '+' section text
            $totalCleaned += DB::table('student_teacher_subjects')
                ->where('attendance_section', 'like', '%+%')
                ->update(['attendance_section' => null]);

            // 2. STS no student
            $totalCleaned += DB::table('student_teacher_subjects')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'student_teacher_subjects.student_id');
                })->delete();

            // 3. SSS no student
            $totalCleaned += DB::table('student_subject_sections')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'student_subject_sections.student_id');
                })->delete();

            // 4. SES no student
            $totalCleaned += DB::table('student_elective_subjects')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'student_elective_subjects.student_id');
                })->delete();

            // 5. SES no subject
            $totalCleaned += DB::table('student_elective_subjects')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects')->whereColumn('subjects.id', 'student_elective_subjects.subject_id');
                })->delete();

            // 6. Timetable no subject
            $totalCleaned += DB::table('timetable_entries')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects')->whereColumn('subjects.id', 'timetable_entries.subject_id');
                })->delete();

            // 7. Timetable no staff
            $totalCleaned += DB::table('timetable_entries')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('staff')->whereColumn('staff.id', 'timetable_entries.staff_id');
                })->delete();

            // 8. Timetable invalid room
            $totalCleaned += DB::table('timetable_entries')
                ->whereNotNull('room_id')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('rooms')->whereColumn('rooms.id', 'timetable_entries.room_id');
                })->update(['room_id' => null]);

            // 9. Attendance no student
            $totalCleaned += DB::table('attendances')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'attendances.student_id');
                })->delete();

            // 10. Results no student
            $totalCleaned += DB::table('results')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'results.student_id');
                })->delete();

            // 11. Results no subject
            $totalCleaned += DB::table('results')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects')->whereColumn('subjects.id', 'results.subject_id');
                })->delete();

            // 12. Fee vouchers no student
            $totalCleaned += DB::table('fee_vouchers')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'fee_vouchers.student_id');
                })->delete();

            // 13. Fee voucher items no voucher
            $totalCleaned += DB::table('fee_voucher_items')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('fee_vouchers')->whereColumn('fee_vouchers.id', 'fee_voucher_items.fee_voucher_id');
                })->delete();

            // 14. Seating details no plan
            $totalCleaned += DB::table('seating_plan_details')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('seating_plans')->whereColumn('seating_plans.id', 'seating_plan_details.seating_plan_id');
                })->delete();

            // 15. User roles no user
            $totalCleaned += DB::table('user_roles')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('users')->whereColumn('users.id', 'user_roles.user_id');
                })->delete();
        });

        return redirect()->route('admin.database.audit.index')->with('success', "Full database cleanup completed successfully! Cleaned / updated {$totalCleaned} record(s).");
    }
}
