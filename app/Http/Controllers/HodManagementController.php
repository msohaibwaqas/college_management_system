<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Staff;
use App\Models\User;
use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HodManagementController extends Controller
{
    /**
     * Display a listing of subjects and their HOD assignments.
     */
    public function index(Request $request)
    {
        $subjects = Subject::with([
            'hod.designationModel',
            'hod.user',
            'staffSubjectSpecializations' => function ($q) {
                $q->whereHas('staff', function ($sq) {
                    $sq->where('category', 'teaching')->where('status', 'active');
                });
            },
            'staffSubjectSpecializations.staff.designationModel',
            'staffSubjectSpecializations.staff.user',
        ])
        ->orderBy('name')
        ->get();

        return view('admin.hods.index', compact('subjects'));
    }

    /**
     * Assign a teacher as HOD for a subject.
     */
    public function assign(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'staff_id' => 'required|exists:staff,id',
        ]);

        $subject = Subject::findOrFail($request->subject_id);
        $newStaffId = (int) $request->staff_id;
        $oldStaffId = $subject->hod_staff_id;

        // Verify that the teacher actually teaches this subject
        $isSpecialized = DB::table('staff_subject_specializations')
            ->where('subject_id', $subject->id)
            ->where('staff_id', $newStaffId)
            ->exists();

        if (!$isSpecialized) {
            return back()->with('error', 'Selected staff member is not specialized in this subject.');
        }

        DB::transaction(function () use ($subject, $newStaffId, $oldStaffId) {
            // Update subject HOD
            $subject->update(['hod_staff_id' => $newStaffId]);

            $hodRole = Roles::firstOrCreate(
                ['name' => 'hod'],
                [
                    'description' => 'Head of Department - manages subject sections and student assignments',
                    'guard_name' => 'web',
                    'is_system_role' => 1,
                ]
            );

            // Grant HOD role to the new teacher's user account
            $newUser = User::where('staff_id', $newStaffId)->first();
            if ($newUser) {
                $newUser->roles()->syncWithoutDetaching([$hodRole->id]);
            }

            // If the old HOD changed, check if old staff is still HOD of any other subject
            if ($oldStaffId && $oldStaffId != $newStaffId) {
                $stillHod = Subject::where('hod_staff_id', $oldStaffId)->exists();
                if (!$stillHod) {
                    $oldUser = User::where('staff_id', $oldStaffId)->first();
                    if ($oldUser) {
                        $oldUser->roles()->detach($hodRole->id);
                        if (session('active_role') === 'hod' && auth()->id() === $oldUser->id) {
                            session(['active_role' => 'teacher']);
                        }
                    }
                }
            }
        });

        $staff = Staff::find($newStaffId);
        $staffName = $staff ? ($staff->first_name . ' ' . $staff->last_name) : 'Staff';

        return back()->with('success', "{$staffName} has been assigned as HOD for {$subject->name} successfully.");
    }

    /**
     * Unassign HOD from a subject.
     */
    public function unassign(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $subject = Subject::findOrFail($request->subject_id);
        $oldStaffId = $subject->hod_staff_id;

        if (!$oldStaffId) {
            return back()->with('info', 'No HOD is currently assigned to this subject.');
        }

        DB::transaction(function () use ($subject, $oldStaffId) {
            $subject->update(['hod_staff_id' => null]);

            $stillHod = Subject::where('hod_staff_id', $oldStaffId)->exists();
            if (!$stillHod) {
                $hodRole = Roles::where('name', 'hod')->first();
                if ($hodRole) {
                    $oldUser = User::where('staff_id', $oldStaffId)->first();
                    if ($oldUser) {
                        $oldUser->roles()->detach($hodRole->id);
                        if (session('active_role') === 'hod' && auth()->id() === $oldUser->id) {
                            session(['active_role' => 'teacher']);
                        }
                    }
                }
            }
        });

        return back()->with('success', "HOD removed from {$subject->name} successfully.");
    }

    /**
     * Auto-assign HOD for subjects with exactly one teacher.
     */
    public function autoAssignSingleTeachers(Request $request)
    {
        $subjects = Subject::whereNull('hod_staff_id')->get();
        $assignedCount = 0;

        $hodRole = Roles::firstOrCreate(
            ['name' => 'hod'],
            [
                'description' => 'Head of Department - manages subject sections and student assignments',
                'guard_name' => 'web',
                'is_system_role' => 1,
            ]
        );

        DB::transaction(function () use ($subjects, $hodRole, &$assignedCount) {
            foreach ($subjects as $subject) {
                $specializations = DB::table('staff_subject_specializations')
                    ->join('staff', 'staff.id', '=', 'staff_subject_specializations.staff_id')
                    ->where('staff_subject_specializations.subject_id', $subject->id)
                    ->where('staff.category', 'teaching')
                    ->where('staff.status', 'active')
                    ->select('staff.id')
                    ->get();

                if ($specializations->count() === 1) {
                    $staffId = $specializations->first()->id;
                    $subject->update(['hod_staff_id' => $staffId]);

                    $user = User::where('staff_id', $staffId)->first();
                    if ($user) {
                        $user->roles()->syncWithoutDetaching([$hodRole->id]);
                    }
                    $assignedCount++;
                }
            }
        });

        return back()->with('success', "Auto-assigned HOD for {$assignedCount} single-teacher department(s).");
    }
}
