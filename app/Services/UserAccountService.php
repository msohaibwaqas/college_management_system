<?php

namespace App\Services;

use App\Models\User;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Roles;
use Illuminate\Support\Facades\Auth;

class UserAccountService
{
    /**
     * Create or restore a user account for an enrolled student.
     */
    public function createAccountForStudent(Student $student, ?int $currentUserId = null): array
    {
        $currentUserId = $currentUserId ?? Auth::id();
        $user = User::withTrashed()->where('student_id', $student->id)->first();

        if ($user && !$user->trashed()) {
            return ['success' => false, 'error' => 'This student already has an active user account.'];
        }

        $admission = $student->admissions->first() ?? $student->heAdmissions->first();
        $rollNo = $admission ? ($admission->college_roll_no ?? $admission->roll_no) : null;

        if (!$rollNo) {
            return ['success' => false, 'error' => 'Student must have a college roll number to create an account.'];
        }

        $accountStatus = ($admission->status === 'Active' || $admission->status === 'Admitted') ? 'active' : 'suspended';

        $role = Roles::where('name', 'student')->first();
        if (!$role) {
            return ['success' => false, 'error' => "Role 'student' not found. Please run the roles seeder."];
        }

        $username = strtolower(str_replace(' ', '', $rollNo));
        $email = $student->contact->email ?? $username . '@rsclosetor.com';

        $conflict = User::withTrashed()
            ->where(function ($q) use ($username, $email) {
                $q->where('username', $username)
                  ->orWhere('email', $email);
            })
            ->where('student_id', '!=', $student->id)
            ->first();

        if ($conflict) {
            return ['success' => false, 'error' => 'Conflict detected: Username or Email is already in use by another account.'];
        }

        if ($user) {
            $user->restore();
            $user->update([
                'username'   => $username,
                'email'      => $email,
                'password'   => '123',
                'status'     => $accountStatus,
                'updated_by' => $currentUserId,
            ]);
            $user->roles()->sync([$role->id]);
            $message = 'Student user account restored successfully!';
        } else {
            $user = User::create([
                'student_id'     => $student->id,
                'username'       => $username,
                'email'          => $email,
                'password'       => '123',
                'status'         => $accountStatus,
                'is_super_admin' => false,
                'created_by'     => $currentUserId,
            ]);
            $user->roles()->attach($role->id);
            $message = 'Student user account created successfully!';
        }

        return [
            'success'  => true,
            'message'  => "{$message} Username: {$username}, Password: 123",
            'username' => $username,
        ];
    }

    /**
     * Create or restore a user account for a staff member.
     */
    public function createAccountForStaff(Staff $staff, ?int $currentUserId = null): array
    {
        $currentUserId = $currentUserId ?? Auth::id();
        $user = User::withTrashed()->where('staff_id', $staff->id)->first();

        if ($user && !$user->trashed()) {
            return ['success' => false, 'error' => 'This staff member already has an active user account.'];
        }

        $roleName = ($staff->category === 'teaching') ? 'teacher' : 'clerk';
        $role = Roles::where('name', $roleName)->first();

        if (!$role) {
            $role = Roles::where('name', 'teacher')->first() ?: Roles::where('name', 'clerk')->first();
            if (!$role) {
                return ['success' => false, 'error' => "Default roles ('teacher' or 'clerk') not found. Please run the roles seeder."];
            }
        }

        $username = $staff->staff_id
            ? strtolower(str_replace(' ', '', $staff->staff_id))
            : strtolower(str_replace(' ', '', $staff->first_name . $staff->id));

        $email = $staff->email ?? $username . '@rsclosetor.com';

        $conflict = User::withTrashed()
            ->where(function ($q) use ($username, $email) {
                $q->where('username', $username)
                  ->orWhere('email', $email);
            })
            ->where('staff_id', '!=', $staff->id)
            ->first();

        if ($conflict) {
            return ['success' => false, 'error' => 'Conflict detected: Username or Email is already in use by another account.'];
        }

        if ($user) {
            $user->restore();
            $user->update([
                'username'   => $username,
                'email'      => $email,
                'password'   => '123',
                'status'     => 'active',
                'updated_by' => $currentUserId,
            ]);
            $user->roles()->sync([$role->id]);
            $message = 'Staff user account restored successfully!';
        } else {
            $user = User::create([
                'staff_id'       => $staff->id,
                'username'       => $username,
                'email'          => $email,
                'password'       => '123',
                'status'         => 'active',
                'is_super_admin' => false,
                'created_by'     => $currentUserId,
            ]);
            $user->roles()->attach($role->id);
            $message = 'Staff user account created successfully!';
        }

        return [
            'success'  => true,
            'message'  => "{$message} Username: {$username}, Password: 12345678A@",
            'username' => $username,
        ];
    }

    /**
     * Bulk create user accounts for students.
     */
    public function bulkCreateStudentAccounts(array $studentIds, ?int $currentUserId = null): array
    {
        $currentUserId = $currentUserId ?? Auth::id();
        $students = Student::with(['admissions', 'heAdmissions', 'contact', 'user'])->findMany($studentIds);
        $role = Roles::where('name', 'student')->first();

        if (!$role) {
            return ['success' => false, 'message' => 'Student role not found.'];
        }

        $created = 0;
        $errors = [];

        foreach ($students as $student) {
            if ($student->user) continue;

            $rollNo = $student->admissions->first()?->college_roll_no ?? $student->heAdmissions->first()?->roll_no;
            if (!$rollNo) {
                $errors[] = "Student {$student->id} lacks a roll number.";
                continue;
            }

            $username = strtolower(str_replace(' ', '', $rollNo));
            $email = $student->contact->email ?? $username . '@rsclosetor.com';

            if (User::where('username', $username)->orWhere('email', $email)->exists()) {
                $errors[] = "Conflict for student {$username}.";
                continue;
            }

            $user = User::create([
                'student_id' => $student->id,
                'username'   => $username,
                'email'      => $email,
                'password'   => '123',
                'status'     => 'active',
                'created_by' => $currentUserId,
            ]);

            $user->roles()->attach($role->id);
            $created++;
        }

        return [
            'success' => true,
            'created' => $created,
            'errors'  => $errors,
            'message' => "Successfully created {$created} student accounts." . (count($errors) > 0 ? ' Errors: ' . implode(', ', $errors) : ''),
        ];
    }

    /**
     * Reset user password to default '123' with mandatory reset flag.
     */
    public function resetPassword(User $user, ?int $currentUserId = null): void
    {
        $user->update([
            'password'             => '123',
            'must_change_password' => true,
            'password_changed_at'  => null,
            'updated_by'           => $currentUserId ?? Auth::id(),
        ]);
    }
}
